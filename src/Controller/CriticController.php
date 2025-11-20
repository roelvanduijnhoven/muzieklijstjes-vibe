<?php

namespace App\Controller;

use App\Entity\Critic;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CriticController extends AbstractController
{
    #[Route('/critic/{id}', name: 'app_critic_show')]
    public function show(Critic $critic): Response
    {
        return $this->render('critic/show.html.twig', [
            'critic' => $critic,
        ]);
    }
}

