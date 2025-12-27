<?php

namespace App\Controller;

use App\Entity\AlbumList;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AlbumListController extends AbstractController
{
    #[Route('/list/{id}/{slug}', name: 'app_list_show', defaults: ['slug' => null])]
    public function show(AlbumList $albumList, Request $request, ?string $slug = null): Response
    {
        $expectedSlug = $albumList->getSlug();
        if ($slug !== $expectedSlug) {
            // Preserve query parameters
            $params = array_merge(
                ['id' => $albumList->getId(), 'slug' => $expectedSlug],
                $request->query->all()
            );
            return $this->redirectToRoute('app_list_show', $params, 301);
        }

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
                $artistA = $a->getAlbum()->getArtist();
                $artistB = $b->getAlbum()->getArtist();
                
                $valA = $artistA->getSortName() ?? $artistA->getName();
                $valB = $artistB->getSortName() ?? $artistB->getName();
                
                $cmp = strcasecmp($valA, $valB);
                
                return $direction === 'asc' ? $cmp : -$cmp;
            });
        } elseif ($sort === 'year') {
            usort($items, function($a, $b) use ($direction) {
                $yearA = $a->getAlbum()->getReleaseYear() ?? 0;
                $yearB = $b->getAlbum()->getReleaseYear() ?? 0;
                return $direction === 'asc' ? $yearA <=> $yearB : $yearB <=> $yearA;
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
        
        return $this->render('album_list/show.html.twig', [
            'list' => $albumList,
            'items' => $items,
            'hasMentions' => $hasMentions,
            'currentSort' => $sort,
            'currentDirection' => $direction,
        ]);
    }
}
