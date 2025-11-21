<?php

namespace App\Controller;

use App\Entity\AlbumList;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AlbumListController extends AbstractController
{
    #[Route('/list/{id}', name: 'app_list_show')]
    public function show(AlbumList $albumList, Request $request): Response
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

        $sort = $request->query->get('sort');
        $direction = $request->query->get('direction', 'asc');
        
        // Sort items
        if ($sort === 'album') {
            usort($items, function($a, $b) use ($direction) {
                $valA = $a->getAlbum()->getTitle();
                $valB = $b->getAlbum()->getTitle();
                return $direction === 'asc' ? strcasecmp($valA, $valB) : strcasecmp($valB, $valA);
            });
        } elseif ($sort === 'artist') {
            usort($items, function($a, $b) use ($direction) {
                $valA = $a->getAlbum()->getArtist()->getName();
                $valB = $b->getAlbum()->getArtist()->getName();
                return $direction === 'asc' ? strcasecmp($valA, $valB) : strcasecmp($valB, $valA);
            });
        } elseif ($sort === 'position') {
            if ($hasMentions) {
                usort($items, function($a, $b) use ($direction) {
                    $mentionsA = $a->getMentions() ?? 0;
                    $mentionsB = $b->getMentions() ?? 0;
                    return $direction === 'asc' ? $mentionsA <=> $mentionsB : $mentionsB <=> $mentionsA;
                });
            } else {
                usort($items, function($a, $b) use ($direction) {
                    $posA = $a->getPosition() ?? PHP_INT_MAX;
                    $posB = $b->getPosition() ?? PHP_INT_MAX;
                    return $direction === 'asc' ? $posA <=> $posB : $posB <=> $posA;
                });
            }
        } elseif ($hasMentions) {
            // Default sort by mentions descending (highest first)
            usort($items, function($a, $b) {
                $mentionsA = $a->getMentions() ?? 0;
                $mentionsB = $b->getMentions() ?? 0;
                return $mentionsB <=> $mentionsA;
            });
        } elseif ($albumList->getType() === AlbumList::TYPE_ORDERED) {
            // Default sort by position ascending (lowest first)
            usort($items, function($a, $b) {
                $posA = $a->getPosition() ?? PHP_INT_MAX;
                $posB = $b->getPosition() ?? PHP_INT_MAX;
                return $posA <=> $posB;
            });
        }
        // TYPE_UNORDERED and TYPE_AGGREGATE lists: no sorting needed by default
        
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

            foreach ($scores as $albumId => $score) {
                $computedItems[] = [
                    'album' => $albums[$albumId],
                    'score' => $score,
                ];
            }

            // Sort computed items
            if ($sort === 'album') {
                usort($computedItems, function($a, $b) use ($direction) {
                    $valA = $a['album']->getTitle();
                    $valB = $b['album']->getTitle();
                    return $direction === 'asc' ? strcasecmp($valA, $valB) : strcasecmp($valB, $valA);
                });
            } elseif ($sort === 'artist') {
                usort($computedItems, function($a, $b) use ($direction) {
                    $valA = $a['album']->getArtist()->getName();
                    $valB = $b['album']->getArtist()->getName();
                    return $direction === 'asc' ? strcasecmp($valA, $valB) : strcasecmp($valB, $valA);
                });
            } elseif ($sort === 'position') {
                usort($computedItems, function($a, $b) use ($direction) {
                    return $direction === 'asc' ? $a['score'] <=> $b['score'] : $b['score'] <=> $a['score'];
                });
            } else {
                // Default sort by score descending
                usort($computedItems, function($a, $b) {
                    return $b['score'] <=> $a['score'];
                });
            }
        }

        return $this->render('album_list/show.html.twig', [
            'list' => $albumList,
            'items' => $items,
            'hasMentions' => $hasMentions,
            'computedItems' => $computedItems,
            'currentSort' => $sort,
            'currentDirection' => $direction,
        ]);
    }
}

