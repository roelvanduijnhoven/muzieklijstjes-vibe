<?php

namespace App\EventSubscriber;

use App\Entity\AlbumList;
use App\Entity\AlbumListItem;
use App\Service\ListAggregator;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\PersistentCollection;

class AlbumListAggregationSubscriber implements EventSubscriberInterface
{
    /** @var array<int, AlbumList> */
    private array $listsToAggregate = [];
    
    /** @var array<int, bool> */
    private array $listsBeingAggregated = [];
    
    private bool $isFlushing = false;
    private bool $enabled = true;

    public function __construct(
        private ListAggregator $listAggregator
    ) {
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
            Events::postFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        if (!$this->enabled) {
            return;
        }

        $uow = $args->getObjectManager()->getUnitOfWork();

        // Helper to add list if not already being processed
        $addList = function (?AlbumList $list) {
            if (!$list) return;
            $id = $list->getId();
            if (!$id) return; // Should not happen for existing entities, but new ones? 
            
            if (isset($this->listsBeingAggregated[$id])) {
                return;
            }
            $this->listsToAggregate[$id] = $list;
        };

        // Check Scheduled Insertions
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof AlbumListItem) {
                $addList($entity->getAlbumList());
                 if ($entity->getAlbumList()) {
                    foreach ($entity->getAlbumList()->getAggregatedIn() as $parent) {
                        $addList($parent);
                    }
                }
            }
            if ($entity instanceof AlbumList && $entity->getType() === AlbumList::TYPE_AGGREGATE) {
                $addList($entity);
            }
        }

        // Check Scheduled Updates
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof AlbumListItem) {
                $addList($entity->getAlbumList());
                 if ($entity->getAlbumList()) {
                    foreach ($entity->getAlbumList()->getAggregatedIn() as $parent) {
                        $addList($parent);
                    }
                }
            }
            if ($entity instanceof AlbumList) {
                if ($entity->getType() === AlbumList::TYPE_AGGREGATE) {
                    $addList($entity);
                }
                foreach ($entity->getAggregatedIn() as $parent) {
                    $addList($parent);
                }
            }
        }

        // Check Scheduled Deletions
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof AlbumListItem) {
                $addList($entity->getAlbumList());
                 if ($entity->getAlbumList()) {
                    foreach ($entity->getAlbumList()->getAggregatedIn() as $parent) {
                        $addList($parent);
                    }
                }
            }
        }

        // Check Collection Updates (e.g. sources changed)
        foreach ($uow->getScheduledCollectionUpdates() as $col) {
            /** @var PersistentCollection $col */
            $owner = $col->getOwner();
            $mapping = $col->getMapping();

            if ($owner instanceof AlbumList) {
                if ($mapping['fieldName'] === 'sources') {
                    if ($owner->getType() === AlbumList::TYPE_AGGREGATE) {
                        $addList($owner);
                    }
                    foreach ($owner->getAggregatedIn() as $parent) {
                        $addList($parent);
                    }
                }
                if ($mapping['fieldName'] === 'aggregatedIn') {
                    // If a list is added/removed from an aggregate, the aggregate needs update
                    foreach ($col->getInsertDiff() as $aggregate) {
                        $addList($aggregate);
                    }
                    foreach ($col->getDeleteDiff() as $aggregate) {
                        $addList($aggregate);
                    }
                }
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (!$this->enabled) {
            return;
        }

        if ($this->isFlushing) {
            return;
        }

        if (empty($this->listsToAggregate)) {
            return;
        }

        $entityManager = $args->getObjectManager();
        $todo = $this->listsToAggregate;
        $this->listsToAggregate = [];

        $this->isFlushing = true;
        
        // Track what we are processing to avoid cycles in onFlush
        foreach ($todo as $id => $list) {
            $this->listsBeingAggregated[$id] = true;
        }

        try {
            foreach ($todo as $list) {
                // Refresh to ensure we have latest state if needed, though usually not needed in same request
                // unless deleted?
                if (!$entityManager->contains($list)) {
                    continue; 
                }
                $this->listAggregator->aggregate($list);
            }

            $entityManager->flush();
        } finally {
            $this->isFlushing = false;
            $this->listsBeingAggregated = [];
        }
    }
}
