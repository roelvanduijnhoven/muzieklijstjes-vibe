<?php

namespace App\Form\EventSubscriber;

use App\Entity\Issue;
use App\Entity\Rubric;
use App\Entity\Review;
use App\Repository\IssueRepository;
use App\Repository\RubricRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class FilterRubricsByIssueMagazineSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private IssueRepository $issueRepository,
        private RubricRepository $rubricRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetData',
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
        ];
    }

    public function onPreSetData(FormEvent $event): void
    {
        $review = $event->getData();
        $form = $event->getForm();

        if (!$review instanceof Review) {
            $this->addRubricField($form, null);
            return;
        }

        $magazineId = $review->getIssue()?->getMagazine()?->getId();
        $this->addRubricField($form, $magazineId);
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (!is_array($data)) {
            $this->addRubricField($form, null);
            return;
        }

        $issueId = $this->extractSubmittedId($data['issue'] ?? null);
        if (!$issueId) {
            $this->addRubricField($form, null);
            return;
        }

        $issue = $this->issueRepository->find($issueId);
        $magazineId = $issue instanceof Issue ? $issue->getMagazine()?->getId() : null;
        $this->addRubricField($form, $magazineId);
    }

    private function addRubricField(FormInterface $form, ?int $magazineId): void
    {
        $disabled = $magazineId === null;

        $form->add('rubric', EntityType::class, [
            'class' => Rubric::class,
            'required' => false,
            'placeholder' => $disabled ? 'Select an issue first' : '—',
            'disabled' => $disabled,
            'choice_label' => 'name',
            'query_builder' => function () use ($magazineId) {
                $qb = $this->rubricRepository->createQueryBuilder('r')
                    ->orderBy('r.name', 'ASC');

                if ($magazineId !== null) {
                    $qb->andWhere('r.magazine = :magazineId')
                        ->setParameter('magazineId', $magazineId);
                } else {
                    // No issue selected yet -> no rubrics available
                    $qb->andWhere('1 = 0');
                }

                return $qb;
            },
            'help' => 'Rubrics are limited to the magazine of the selected issue.',
        ]);
    }

    private function extractSubmittedId(mixed $value): ?string
    {
        if (is_scalar($value) && $value !== '') {
            return (string) $value;
        }

        if (!is_array($value)) {
            return null;
        }

        foreach (['autocomplete', 'id', 'entityId'] as $key) {
            if (isset($value[$key]) && is_scalar($value[$key]) && $value[$key] !== '') {
                return (string) $value[$key];
            }
        }

        $firstValue = reset($value);
        if (is_scalar($firstValue) && $firstValue !== '') {
            return (string) $firstValue;
        }

        return null;
    }
}

