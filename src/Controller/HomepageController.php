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
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        // Only show important (canon) lists on the homepage
        $lists = $entityManager->getRepository(AlbumList::class)->findBy(
            ['important' => true],
            ['title' => 'ASC'],
            $limit,
            $offset
        );

        return $this->render('homepage/index.html.twig', [
            'lists' => $lists,
            'page' => $page,
        ]);
    }
}
