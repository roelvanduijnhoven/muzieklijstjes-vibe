<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\AlbumList;
use App\Entity\AlbumListItem;
use App\Repository\AlbumListItemRepository;
use App\Repository\AlbumRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AlbumController extends AbstractController
{
    #[Route('/album/search', name: 'app_album_search')]
    public function search(Request $request, AlbumRepository $albumRepository): Response
    {
        $query = $request->query->get('q');
        $albums = $albumRepository->searchByTitle($query);

        if (count($albums) === 1) {
            return $this->redirectToRoute('app_album_show', ['id' => $albums[0]->getId()]);
        }

        return $this->render('album/search.html.twig', [
            'albums' => $albums,
            'query' => $query,
        ]);
    }

    #[Route('/album/{id}', name: 'app_album_show')]
    public function show(Album $album, AlbumListItemRepository $albumListItemRepository): Response
    {
        $listItems = $albumListItemRepository->findByAlbumId($album->getId());
        $finalItems = [];

        foreach ($listItems as $item) {
            $list = $item->getAlbumList();
            $parents = $list->getAggregatedIn();

            if ($parents->isEmpty()) {
                // If it's not part of another list, show it
                if (!isset($finalItems[$list->getId()])) {
                    $finalItems[$list->getId()] = $item;
                }
            } else {
                // If it is part of another list (aggregate), show the parent(s) instead
                foreach ($parents as $parent) {
                    if (!isset($finalItems[$parent->getId()])) {
                        // Check if we have a real item for this parent in the original result
                        $parentItem = null;
                        foreach ($listItems as $orig) {
                            if ($orig->getAlbumList()->getId() === $parent->getId()) {
                                $parentItem = $orig;
                                break;
                            }
                        }
                        
                        // If not found in result, create a transient item
                        if (!$parentItem) {
                            $parentItem = new AlbumListItem();
                            $parentItem->setAlbum($album);
                            $parentItem->setAlbumList($parent);
                        }
                        
                        $finalItems[$parent->getId()] = $parentItem;
                    }
                }
            }
        }
        
        // Note: This logic handles 1 level of aggregation. 
        // If A is in B, and B is in C:
        // - Processing A adds B.
        // - Processing B (if present) adds C.
        // Result: B and C. 
        // If B was not in original results, we only see B (created transiently).
        // Since transient B is not in $listItems loop, we won't process it to find C.
        // To handle deep recursion, we'd need to loop until stable, but 1 level is likely sufficient for now.

        // Also check if any items in finalItems are themselves aggregated in other items in finalItems?
        // e.g. We added B from A. Now we have B. Is B in C (which might be in finalItems)?
        // Let's do a quick pass to clean up 'intermediate' aggregates if their parents are also present.
        
        $cleanedItems = [];
        foreach ($finalItems as $item) {
            $list = $item->getAlbumList();
            $parents = $list->getAggregatedIn();
            
            $hasParentInFinal = false;
            foreach ($parents as $parent) {
                if (isset($finalItems[$parent->getId()])) {
                    $hasParentInFinal = true;
                    break;
                }
            }
            
            if (!$hasParentInFinal) {
                $cleanedItems[] = $item;
            }
        }
        
        // Sort by year desc (since we might have mixed order now)
        usort($cleanedItems, function ($a, $b) {
            $yearA = $a->getAlbumList()->getReleaseYear() ?? 0;
            $yearB = $b->getAlbumList()->getReleaseYear() ?? 0;
            if ($yearA === $yearB) {
                 return strcmp($a->getAlbumList()->getTitle(), $b->getAlbumList()->getTitle());
            }
            return $yearB <=> $yearA;
        });

        return $this->render('album/show.html.twig', [
            'album' => $album,
            'listItems' => $cleanedItems,
            'listCount' => count($cleanedItems),
            'coverBaseUrl' => $this->getParameter('app.album_cover_base_url'),
        ]);
    }
}
