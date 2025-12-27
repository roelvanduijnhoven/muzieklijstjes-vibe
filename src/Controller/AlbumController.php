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

        // Filter out items that belong to source lists (which are aggregated in other lists)
        // because the aggregate list will have its own item now (materialized).
        foreach ($listItems as $item) {
            $list = $item->getAlbumList();
            
            // Check if this list is aggregated in something else
            if ($list->getAggregatedIn()->isEmpty()) {
                $finalItems[] = $item;
            }
        }
        
        // Sort by year desc
        usort($finalItems, function ($a, $b) {
            $yearA = $a->getAlbumList()->getReleaseYear() ?? 0;
            $yearB = $b->getAlbumList()->getReleaseYear() ?? 0;
            if ($yearA === $yearB) {
                 return strcmp($a->getAlbumList()->getTitle() ?? '', $b->getAlbumList()->getTitle() ?? '');
            }
            return $yearB <=> $yearA;
        });

        return $this->render('album/show.html.twig', [
            'album' => $album,
            'listItems' => $finalItems,
            'listCount' => count($finalItems),
            'coverBaseUrl' => $this->getParameter('app.album_cover_base_url'),
        ]);
    }
}
