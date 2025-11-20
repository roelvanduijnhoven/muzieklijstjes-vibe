<?php

namespace App\Controller;

use App\Entity\AlbumList;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AlbumListController extends AbstractController
{
    #[Route('/list/{id}', name: 'app_list_show')]
    public function show(AlbumList $albumList): Response
    {
        // Get items and sort based on list type
        $items = $albumList->getListItems()->toArray();
        
        // Determine if this list has mentions
        $hasMentions = false;
        foreach ($items as $item) {
            if ($item->getMentions() !== null) {
                $hasMentions = true;
                break;
            }
        }
        
        // Sort items
        if ($hasMentions) {
            // Sort by mentions descending (highest first)
            usort($items, function($a, $b) {
                $mentionsA = $a->getMentions() ?? 0;
                $mentionsB = $b->getMentions() ?? 0;
                return $mentionsB <=> $mentionsA;
            });
        } elseif ($albumList->getType() === AlbumList::TYPE_ORDERED) {
            // Sort by position ascending (lowest first)
            usort($items, function($a, $b) {
                $posA = $a->getPosition() ?? PHP_INT_MAX;
                $posB = $b->getPosition() ?? PHP_INT_MAX;
                return $posA <=> $posB;
            });
        }
        // TYPE_UNORDERED and TYPE_AGGREGATE lists: no sorting needed
        
        $computedItems = [];
        if ($albumList->getType() === AlbumList::TYPE_AGGREGATE) {
            $scores = [];
            $albums = [];

            foreach ($albumList->getSources() as $sourceList) {
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

            arsort($scores);

            foreach ($scores as $albumId => $score) {
                $computedItems[] = [
                    'album' => $albums[$albumId],
                    'score' => $score,
                ];
            }
        }

        return $this->render('album_list/show.html.twig', [
            'list' => $albumList,
            'items' => $items,
            'hasMentions' => $hasMentions,
            'computedItems' => $computedItems,
        ]);
    }
}

