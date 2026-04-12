<?php

namespace App\EventSubscriber;

use App\Exception\DeletionBlockedException;
use App\Service\DeletionUsageInspector;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

class DeletionGuardSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DeletionUsageInspector $deletionUsageInspector
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [Events::onFlush];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        foreach ($entityManager->getUnitOfWork()->getScheduledEntityDeletions() as $entity) {
            $blockingUsages = $this->deletionUsageInspector->getBlockingUsages($entityManager, $entity);
            if ($blockingUsages === []) {
                continue;
            }

            throw DeletionBlockedException::forEntity(
                $this->deletionUsageInspector->getEntityLabel($entity),
                $blockingUsages
            );
        }
    }
}
