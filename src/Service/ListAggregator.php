<?php

namespace App\Service;

use App\Entity\AlbumList;
use App\Entity\AlbumListItem;
use Doctrine\ORM\EntityManagerInterface;

class ListAggregator
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function aggregate(AlbumList $aggregateList): void
    {
        if ($aggregateList->getType() !== AlbumList::TYPE_AGGREGATE) {
            return;
        }

        // 1. Calculate scores from sources
        $scores = [];
        $albums = [];

        foreach ($aggregateList->getSources() as $sourceList) {
            foreach ($sourceList->getListItems() as $item) {
                $album = $item->getAlbum();
                if (!$album) {
                    continue;
                }

                $albumId = $album->getId();
                if (!isset($scores[$albumId])) {
                    $scores[$albumId] = 0;
                    $albums[$albumId] = $album;
                }
                $scores[$albumId]++;
            }
        }

        // 2. Clear existing items
        // We do this manually to avoid issues with collection management during flush
        $existingItems = $aggregateList->getListItems();
        foreach ($existingItems as $item) {
            $this->entityManager->remove($item);
        }
        $existingItems->clear();

        // 3. Create new items based on scores
        // Sort by score descending
        arsort($scores);

        $position = 1;
        foreach ($scores as $albumId => $score) {
            $newItem = new AlbumListItem();
            $newItem->setAlbum($albums[$albumId]);
            $newItem->setAlbumList($aggregateList);
            $newItem->setMentions($score);
            $newItem->setPosition($position++);

            $this->entityManager->persist($newItem);
            $aggregateList->addListItem($newItem);
        }

        // Note: We do not flush here to allow the caller to manage the transaction
        // But since we might be called from postUpdate, we might need to be careful about flushing.
        // For now, we assume the caller handles flush or we are in a safe lifecycle phase.
    }
}

