<?php

namespace App\Controller;

use App\Entity\Critic;
use App\Repository\CriticRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CriticController extends AbstractController
{
    #[Route('/critic/search', name: 'app_critic_search')]
    public function search(Request $request, CriticRepository $criticRepository): Response
    {
        $query = $request->query->get('q');
        $critics = $criticRepository->searchByName($query);

        if (count($critics) === 1) {
            return $this->redirectToRoute('app_critic_show', ['id' => $critics[0]->getId(), 'slug' => $critics[0]->getSlug()]);
        }

        return $this->render('critic/search.html.twig', [
            'critics' => $critics,
            'query' => $query,
        ]);
    }

    #[Route('/critic/{id}/{slug}', name: 'app_critic_show', defaults: ['slug' => null])]
    public function show(
        Critic $critic, 
        Request $request,
        \App\Repository\AlbumListRepository $albumListRepository,
        \App\Repository\ReviewRepository $reviewRepository,
        ?string $slug = null
    ): Response
    {
        $expectedSlug = $critic->getSlug();
        if ($slug !== $expectedSlug) {
            return $this->redirectToRoute('app_critic_show', ['id' => $critic->getId(), 'slug' => $expectedSlug] + $request->query->all(), 301);
        }

        // List Sorting
        $listSort = $request->query->get('list_sort', 'title');
        $listDir = $request->query->get('list_dir', 'asc');
        $sortedLists = $albumListRepository->findByCritic($critic, $listSort, $listDir);

        // Review Sorting
        $reviewSort = $request->query->get('review_sort', 'album');
        $reviewDir = $request->query->get('review_dir', 'asc');
        $sortedReviews = $reviewRepository->findByCritic($critic, $reviewSort, $reviewDir);

        return $this->render('critic/show.html.twig', [
            'critic' => $critic,
            'sortedLists' => $sortedLists,
            'listSort' => $listSort,
            'listDir' => $listDir,
            'sortedReviews' => $sortedReviews,
            'reviewSort' => $reviewSort,
            'reviewDir' => $reviewDir,
        ]);
    }
}

