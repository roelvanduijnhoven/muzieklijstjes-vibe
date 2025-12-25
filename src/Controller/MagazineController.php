<?php

namespace App\Controller;

use App\Entity\Magazine;
use App\Repository\MagazineRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MagazineController extends AbstractController
{
    #[Route('/magazine/{id}/{slug}', name: 'app_magazine_show', requirements: ['id' => '\d+'], defaults: ['slug' => null])]
    public function show(Magazine $magazine, MagazineRepository $magazineRepository, ?string $slug = null): Response
    {
        $expectedSlug = $magazine->getSlug();
        if ($slug !== $expectedSlug) {
            return $this->redirectToRoute('app_magazine_show', ['id' => $magazine->getId(), 'slug' => $expectedSlug], 301);
        }

        // Get critics who reviewed the most for this magazine
        // We need to count reviews by critic where magazine is this magazine
        $criticsStats = $magazineRepository->findTopCritics($magazine, 10);

        return $this->render('magazine/show.html.twig', [
            'magazine' => $magazine,
            'criticsStats' => $criticsStats,
        ]);
    }

    #[Route('/magazine/name/{name}', name: 'app_magazine_show_by_name')]
    public function showByName(string $name, MagazineRepository $magazineRepository): Response
    {
        $magazine = $magazineRepository->findOneBy(['name' => $name]);

        if (!$magazine) {
            throw $this->createNotFoundException('Magazine not found');
        }

        return $this->show($magazine, $magazineRepository);
    }
}

