<?php

namespace App\Controller;

use App\Entity\AlbumList;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        $limit = 50;

        // Aggregate overview of albums from important lists
        $aggregatedAlbums = $entityManager->getRepository(\App\Entity\Album::class)
            ->findMostListedAlbums($limit);

        return $this->render('homepage/index.html.twig', [
            'aggregatedAlbums' => $aggregatedAlbums,
        ]);
    }
}
