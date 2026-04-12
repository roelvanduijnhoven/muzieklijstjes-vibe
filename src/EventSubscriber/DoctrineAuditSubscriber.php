<?php

namespace App\EventSubscriber;

use App\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\PersistentCollection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

class DoctrineAuditSubscriber implements EventSubscriberInterface
{
    /** @var array<string, array{entity: object, entityType: string, action: string, changes: array<string, mixed>, occurredAt: \DateTimeImmutable, actorIdentifier: ?string, context: ?array<string, mixed>}> */
    private array $pendingEntries = [];

    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onClear,
            Events::onFlush,
            Events::postFlush,
        ];
    }

    public function onClear(OnClearEventArgs $args): void
    {
        $this->pendingEntries = [];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if (!$this->shouldAudit($entity)) {
                continue;
            }

            $this->queueEntry(
                $entity,
                AuditLog::ACTION_INSERT,
                $this->extractEntityState($entityManager, $entity),
            );
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (!$this->shouldAudit($entity)) {
                continue;
            }

            $changes = [];
            foreach ($unitOfWork->getEntityChangeSet($entity) as $field => $values) {
                if (count($values) !== 2) {
                    continue;
                }

                $changes[$field] = [
                    'old' => $this->normalizeValue($entityManager, $values[0]),
                    'new' => $this->normalizeValue($entityManager, $values[1]),
                ];
            }

            if ($changes === []) {
                continue;
            }

            $this->queueEntry($entity, AuditLog::ACTION_UPDATE, $changes);
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if (!$this->shouldAudit($entity)) {
                continue;
            }

            $this->queueEntry(
                $entity,
                AuditLog::ACTION_DELETE,
                $this->extractEntityState($entityManager, $entity),
            );
        }

        foreach ($unitOfWork->getScheduledCollectionUpdates() as $collection) {
            if (!$collection instanceof PersistentCollection) {
                continue;
            }

            $this->queueCollectionChange($entityManager, $collection);
        }

        foreach ($unitOfWork->getScheduledCollectionDeletions() as $collection) {
            if (!$collection instanceof PersistentCollection) {
                continue;
            }

            $this->queueCollectionChange($entityManager, $collection);
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->pendingEntries === []) {
            return;
        }

        $entityManager = $args->getObjectManager();
        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        $entries = array_values($this->pendingEntries);
        $this->pendingEntries = [];

        $connection = $entityManager->getConnection();

        foreach ($entries as $entry) {
            $entityId = $this->stringifyIdentifier($entityManager, $entry['entity']);
            $changes = $entry['changes'];

            if ($entry['action'] === AuditLog::ACTION_INSERT && array_key_exists('id', $changes) && $changes['id'] === null) {
                $changes['id'] = $entityId;
            }

            $connection->executeStatement(
                'INSERT INTO audit_log (action, entity_type, entity_id, actor_identifier, changes, context, occurred_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $entry['action'],
                    $entry['entityType'],
                    $entityId,
                    $entry['actorIdentifier'],
                    $this->encodeJson($changes),
                    $entry['context'] === null ? null : $this->encodeJson($entry['context']),
                    $entry['occurredAt']->format('Y-m-d H:i:s'),
                ],
            );
        }
    }

    private function queueCollectionChange(EntityManagerInterface $entityManager, PersistentCollection $collection): void
    {
        $owner = $collection->getOwner();
        if (!is_object($owner) || !$this->shouldAudit($owner)) {
            return;
        }

        if (isset($this->pendingEntries[$this->buildQueueKey($owner, AuditLog::ACTION_INSERT)])
            || isset($this->pendingEntries[$this->buildQueueKey($owner, AuditLog::ACTION_DELETE)])) {
            return;
        }

        $added = array_values(array_map(
            fn (mixed $value): mixed => $this->normalizeValue($entityManager, $value),
            $collection->getInsertDiff(),
        ));
        $removed = array_values(array_map(
            fn (mixed $value): mixed => $this->normalizeValue($entityManager, $value),
            $collection->getDeleteDiff(),
        ));

        if ($added === [] && $removed === []) {
            return;
        }

        $change = [];
        if ($added !== []) {
            $change['added'] = $added;
        }
        if ($removed !== []) {
            $change['removed'] = $removed;
        }

        $this->queueEntry(
            $owner,
            AuditLog::ACTION_UPDATE,
            [$this->getCollectionFieldName($collection) => $change],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function extractEntityState(EntityManagerInterface $entityManager, object $entity): array
    {
        $metadata = $entityManager->getClassMetadata($this->getEntityClass($entity));
        $state = [];

        foreach ($metadata->getFieldNames() as $fieldName) {
            $state[$fieldName] = $this->normalizeValue($entityManager, $metadata->getFieldValue($entity, $fieldName));
        }

        foreach ($metadata->getAssociationNames() as $associationName) {
            if (!$metadata->isSingleValuedAssociation($associationName)) {
                continue;
            }

            $state[$associationName] = $this->normalizeValue($entityManager, $metadata->getFieldValue($entity, $associationName));
        }

        ksort($state);

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(): ?array
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            $context = array_filter([
                'source' => 'cli',
                'command' => $_SERVER['argv'][1] ?? null,
            ], static fn (mixed $value): bool => $value !== null && $value !== '');

            return $context === [] ? null : $context;
        }

        $context = array_filter([
            'source' => 'http',
            'method' => $request->getMethod(),
            'route' => $request->attributes->get('_route'),
            'path' => $request->getPathInfo(),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return $context === [] ? null : $context;
    }

    private function resolveActorIdentifier(): ?string
    {
        $user = $this->security->getUser();

        if ($user instanceof UserInterface) {
            return $user->getUserIdentifier();
        }

        return null;
    }

    /**
     * @param array<string, mixed> $changes
     */
    private function queueEntry(object $entity, string $action, array $changes): void
    {
        $key = $this->buildQueueKey($entity, $action);
        $entry = $this->pendingEntries[$key] ?? [
            'entity' => $entity,
            'entityType' => $this->getEntityClass($entity),
            'action' => $action,
            'changes' => [],
            'occurredAt' => new \DateTimeImmutable(),
            'actorIdentifier' => $this->resolveActorIdentifier(),
            'context' => $this->buildContext(),
        ];

        $entry['changes'] = array_replace($entry['changes'], $changes);

        $this->pendingEntries[$key] = $entry;
    }

    private function shouldAudit(object $entity): bool
    {
        return str_starts_with($this->getEntityClass($entity), 'App\\Entity\\') && !$entity instanceof AuditLog;
    }

    private function buildQueueKey(object $entity, string $action): string
    {
        return sprintf('%s:%s', $action, spl_object_id($entity));
    }

    private function getCollectionFieldName(PersistentCollection $collection): string
    {
        $mapping = $collection->getMapping();

        if (is_array($mapping) && isset($mapping['fieldName'])) {
            return $mapping['fieldName'];
        }

        if (is_object($mapping) && property_exists($mapping, 'fieldName')) {
            return $mapping->fieldName;
        }

        return 'collection';
    }

    private function stringifyIdentifier(EntityManagerInterface $entityManager, object $entity): ?string
    {
        $identifierValues = $entityManager->getClassMetadata($this->getEntityClass($entity))->getIdentifierValues($entity);

        if ($identifierValues === []) {
            return null;
        }

        if (count($identifierValues) === 1) {
            return (string) reset($identifierValues);
        }

        return $this->encodeJson($identifierValues);
    }

    private function normalizeValue(EntityManagerInterface $entityManager, mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if (is_array($value)) {
            return array_map(
                fn (mixed $item): mixed => $this->normalizeValue($entityManager, $item),
                $value,
            );
        }

        if (is_object($value) && str_starts_with($this->getEntityClass($value), 'App\\Entity\\')) {
            return [
                'entityType' => $this->getEntityClass($value),
                'entityId' => $this->stringifyIdentifier($entityManager, $value),
            ];
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return is_object($value) ? $value::class : $value;
    }

    private function encodeJson(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    private function getEntityClass(object $entity): string
    {
        $className = $entity::class;

        if (str_contains($className, '\\__CG__\\')) {
            $parentClass = get_parent_class($entity);

            if (is_string($parentClass)) {
                return $parentClass;
            }
        }

        return $className;
    }
}
