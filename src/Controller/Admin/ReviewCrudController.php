<?php

namespace App\Controller\Admin;

use App\Entity\Album;
use App\Entity\Review;
use App\Entity\Issue;
use App\Form\EventSubscriber\FilterRubricsByIssueMagazineSubscriber;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\Form\FormBuilderInterface;

class ReviewCrudController extends AbstractCrudController
{
    public function __construct(
        private FilterRubricsByIssueMagazineSubscriber $filterRubricsByIssueMagazineSubscriber,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Review::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('album'))
            ->add(EntityFilter::new('issue'));
    }

    public function configureAssets(Assets $assets): Assets
    {
        return parent::configureAssets($assets)
            ->addJsFile('assets/js/admin/review_rubrics.js?v=4');
    }

    public function createEntity(string $entityFqcn)
    {
        $entity = parent::createEntity($entityFqcn);
        
        $issueId = $this->getContext()->getRequest()->query->get('issue_id');
        if ($issueId) {
            $issue = $this->container->get('doctrine')->getRepository(Issue::class)->find($issueId);
            if ($issue) {
                $entity->setIssue($issue);
            }
        }

        return $entity;
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $builder = parent::createNewFormBuilder($entityDto, $formOptions, $context);
        $builder->addEventSubscriber($this->filterRubricsByIssueMagazineSubscriber);
        return $builder;
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $builder = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        $builder->addEventSubscriber($this->filterRubricsByIssueMagazineSubscriber);
        return $builder;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->setTemplatePath('admin/field/link_to_edit.html.twig');
        yield AssociationField::new('album')->autocomplete();
        yield AssociationField::new('critic')->autocomplete();
        yield AssociationField::new('issue')->autocomplete();
        yield AssociationField::new('rubric');
        yield NumberField::new('rating');
        yield UrlField::new('sourceUrl')
            ->setHelp('Optional: link to the online page where this review was originally published.')
            ->hideOnIndex();
        yield IntegerField::new('year')->hideOnForm();
        yield TextField::new('issueNumber')->hideOnForm();
    }
}

