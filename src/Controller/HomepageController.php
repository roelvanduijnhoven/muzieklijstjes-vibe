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
        $aggregatedImportantAlbums = $entityManager->getRepository(\App\Entity\Album::class)
            ->findMostListedAlbums($limit);
        
        // Aggregate overview of albums from 2025 lists
        $aggregated2025Albums = $entityManager->getRepository(\App\Entity\Album::class)
            ->findMostListedAlbumsByYear(2025, $limit);

        return $this->render('homepage/index.html.twig', [
            'aggregatedImportantAlbums' => $aggregatedImportantAlbums,
            'aggregated2025Albums' => $aggregated2025Albums,
        ]);
    }
}
