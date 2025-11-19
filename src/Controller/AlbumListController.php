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
        return $this->render('album_list/show.html.twig', [
            'list' => $albumList,
        ]);
    }
}

