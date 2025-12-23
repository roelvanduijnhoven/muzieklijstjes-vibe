<?php

namespace App\Controller;

use App\Entity\Album;
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

        return $this->render('album/show.html.twig', [
            'album' => $album,
            'listItems' => $listItems,
            'coverBaseUrl' => $this->getParameter('app.album_cover_base_url'),
        ]);
    }
}
