<?php

namespace App\Controller;

use App\Service\CampaignSynchronisationService;
use App\Service\GoogleAdsLibraryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HealthController extends AbstractController
{
    #[Route('/up', name: 'app_health')]
    public function index(): Response
    {
        // TODO#Roel Query an actual image from Qdrant.

        return new Response('OK');
    }
}