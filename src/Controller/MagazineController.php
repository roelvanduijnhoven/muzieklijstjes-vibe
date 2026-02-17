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

        // Fetch issues
        $issues = $magazine->getIssues()->toArray();
        usort($issues, function ($a, $b) {
            if ($a->getYear() !== $b->getYear()) {
                return $b->getYear() <=> $a->getYear(); // DESC year
            }
            return strnatcmp((string)$a->getIssueNumber(), (string)$b->getIssueNumber()); // ASC number
        });

        $groupedIssues = [];
        foreach ($issues as $issue) {
            $groupedIssues[$issue->getYear()][] = $issue;
        }

        return $this->render('magazine/show.html.twig', [
            'magazine' => $magazine,
            'groupedIssues' => $groupedIssues,
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

