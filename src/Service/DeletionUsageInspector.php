<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionClass;
use Symfony\Component\String\Inflector\EnglishInflector;

class DeletionUsageInspector
{
    private EnglishInflector $inflector;

    public function __construct()
    {
        $this->inflector = new EnglishInflector();
    }

    /**
     * @return array<int, array{label: string, count: int}>
     */
    public function getBlockingUsages(EntityManagerInterface $entityManager, object $entity): array
    {
        $entityMetadata = $entityManager->getClassMetadata($entity::class);
        if ($entityMetadata->getIdentifierValues($entity) === []) {
            return [];
        }

        $blockingCounts = [];

        foreach ($entityManager->getMetadataFactory()->getAllMetadata() as $referencingMetadata) {
            foreach ($referencingMetadata->getAssociationNames() as $associationName) {
                if ($referencingMetadata->isAssociationInverseSide($associationName)) {
                    continue;
                }

                if ($referencingMetadata->getAssociationTargetClass($associationName) !== $entityMetadata->getName()) {
                    continue;
                }

                $count = $this->countReferences($entityManager, $referencingMetadata, $associationName, $entity);
                if ($count === 0) {
                    continue;
                }

                $label = $this->pluralizeEntityLabel(
                    $this->humanizeClassName($referencingMetadata->getName()),
                    $count
                );

                $blockingCounts[$label] = ($blockingCounts[$label] ?? 0) + $count;
            }
        }

        ksort($blockingCounts);

        $blockingUsages = [];
        foreach ($blockingCounts as $label => $count) {
            $blockingUsages[] = [
                'label' => $label,
                'count' => $count,
            ];
        }

        return $blockingUsages;
    }

    public function getEntityLabel(object $entity): string
    {
        return $this->humanizeClassName($entity::class);
    }

    private function countReferences(
        EntityManagerInterface $entityManager,
        ClassMetadata $referencingMetadata,
        string $associationName,
        object $entity
    ): int {
        $alias = 'entity';
        $queryBuilder = $entityManager->createQueryBuilder()
            ->select(sprintf('COUNT(%s)', $alias))
            ->from($referencingMetadata->getName(), $alias)
            ->setParameter('referencedEntity', $entity);

        if ($referencingMetadata->isCollectionValuedAssociation($associationName)) {
            $queryBuilder->andWhere(sprintf(':referencedEntity MEMBER OF %s.%s', $alias, $associationName));
        } else {
            $queryBuilder->andWhere(sprintf('%s.%s = :referencedEntity', $alias, $associationName));
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function humanizeClassName(string $className): string
    {
        $shortName = (new ReflectionClass($className))->getShortName();

        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $shortName));
    }

    private function pluralizeEntityLabel(string $label, int $count): string
    {
        if ($count === 1) {
            return $label;
        }

        $pluralized = $this->inflector->pluralize($label);

        return $pluralized[0] ?? sprintf('%ss', $label);
    }
}
