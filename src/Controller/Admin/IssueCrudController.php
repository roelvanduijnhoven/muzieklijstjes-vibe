<?php

namespace App\Controller\Admin;

use App\Entity\Issue;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class IssueCrudController extends AbstractCrudController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Issue::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewReviews = Action::new('viewReviews', 'Reviews', 'fa fa-star')
            ->linkToUrl(function (Issue $issue) {
                return $this->adminUrlGenerator
                    ->setController(ReviewCrudController::class)
                    ->setAction(Action::INDEX)
                    ->set('filters', ['issue' => ['comparison' => '=', 'value' => $issue->getId()]])
                    ->generateUrl();
            });

        $addReview = Action::new('addReview', 'Add Review', 'fa fa-plus')
            ->linkToUrl(function (Issue $issue) {
                return $this->adminUrlGenerator
                    ->setController(ReviewCrudController::class)
                    ->setAction(Action::NEW)
                    ->set('issue_id', $issue->getId())
                    ->generateUrl();
            });

        return $actions
            ->add(Crud::PAGE_DETAIL, $viewReviews)
            ->add(Crud::PAGE_EDIT, $viewReviews)
            ->add(Crud::PAGE_DETAIL, $addReview)
            ->add(Crud::PAGE_EDIT, $addReview);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('magazine'),
            IntegerField::new('year'),
            TextField::new('issueNumber'),
        ];
    }
}
