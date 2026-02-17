<?php

namespace App\Controller;

use App\Entity\Issue;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IssueController extends AbstractController
{
    #[Route('/issue/{id}', name: 'app_issue_show', requirements: ['id' => '\d+'])]
    public function show(Issue $issue): Response
    {
        return $this->render('issue/show.html.twig', [
            'issue' => $issue,
        ]);
    }
}
