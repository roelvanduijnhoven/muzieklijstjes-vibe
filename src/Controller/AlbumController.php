<?php

namespace App\Controller;

use App\Entity\Album;
use App\Repository\AlbumListItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AlbumController extends AbstractController
{
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
