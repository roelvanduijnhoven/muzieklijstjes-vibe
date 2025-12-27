<?php

namespace App\EventSubscriber;

use App\Entity\Album;
use App\Service\AlbumCoverService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist, priority: 10, connection: 'default')]
#[AsDoctrineListener(event: Events::preUpdate, priority: 10, connection: 'default')]
class AlbumCoverFetcherSubscriber
{
    public function __construct(
        private AlbumCoverService $albumCoverService
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Album) {
            return;
        }

        $this->albumCoverService->refreshCover($entity);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Album) {
            return;
        }

        // Check if musicBrainzId has changed
        if ($args->hasChangedField('musicBrainzId')) {
            $newFilename = $this->albumCoverService->refreshCover($entity);
            
            // handleCoverFetch has already updated the entity property $entity->setImageUrl(...)
            // Now we need to ensure this change is persisted.

            // 1. If imageUrl was already in the changeset (modified by user or other means)
            if ($args->hasChangedField('imageUrl')) {
                $args->setNewValue('imageUrl', $newFilename);
            } 
            // 2. If imageUrl was NOT modified, we must force Doctrine to see the change
            else {
                $em = $args->getObjectManager();
                $uow = $em->getUnitOfWork();
                $meta = $em->getClassMetadata(get_class($entity));
                $uow->recomputeSingleEntityChangeSet($meta, $entity);
            }
        }
    }
}

