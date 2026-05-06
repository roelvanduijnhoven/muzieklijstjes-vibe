<?php

namespace App\Controller\Admin;

use App\Entity\Issue;
use App\Repository\IssueRepository;
use App\Repository\RubricRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ReviewAjaxController extends AbstractController
{
    public function __construct(
        private IssueRepository $issueRepository,
        private RubricRepository $rubricRepository,
    ) {
    }

    #[Route('/admin/ajax/review/rubrics', name: 'admin_ajax_review_rubrics')]
    public function rubrics(Request $request): JsonResponse
    {
        $issueId = $request->query->get('issueId');
        if (!$issueId) {
            return new JsonResponse(['rubrics' => []]);
        }

        $issue = $this->issueRepository->find($issueId);
        if (!$issue instanceof Issue || !$issue->getMagazine()) {
            return new JsonResponse(['rubrics' => []]);
        }

        $rubrics = $this->rubricRepository->findBy(
            ['magazine' => $issue->getMagazine()],
            ['name' => 'ASC']
        );

        $payload = array_map(static function ($rubric) {
            return [
                'id' => $rubric->getId(),
                'text' => $rubric->getName(),
            ];
        }, $rubrics);

        return new JsonResponse(['rubrics' => $payload]);
    }
}

