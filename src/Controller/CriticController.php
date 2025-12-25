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
            return $this->redirectToRoute('app_critic_show', ['id' => $critics[0]->getId()]);
        }

        return $this->render('critic/search.html.twig', [
            'critics' => $critics,
            'query' => $query,
        ]);
    }

    #[Route('/critic/{id}/{slug}', name: 'app_critic_show', defaults: ['slug' => null])]
    public function show(Critic $critic, ?string $slug = null): Response
    {
        $expectedSlug = $critic->getSlug();
        if ($slug !== $expectedSlug) {
            return $this->redirectToRoute('app_critic_show', ['id' => $critic->getId(), 'slug' => $expectedSlug], 301);
        }

        return $this->render('critic/show.html.twig', [
            'critic' => $critic,
        ]);
    }
}

