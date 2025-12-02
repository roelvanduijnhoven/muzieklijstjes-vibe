<?php

namespace App\Controller\Admin;

use App\Entity\AlbumList;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Doctrine\ORM\QueryBuilder;

class OrderedAlbumListCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AlbumList::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.type = :type')
           ->setParameter('type', AlbumList::TYPE_ORDERED);

        return $qb;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Ordered Lists')
            ->setPageTitle('new', 'Create Ordered List')
            ->setPageTitle('edit', 'Edit Ordered List');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title')->setTemplatePath('admin/field/link_to_edit.html.twig');
        
        yield IntegerField::new('releaseYear');
        yield IntegerField::new('periodStart')->hideOnIndex();
        yield IntegerField::new('periodEnd')->hideOnIndex();
        yield TextareaField::new('description')->hideOnIndex();
        yield BooleanField::new('important');
        yield BooleanField::new('visible');
        yield UrlField::new('externalUrl')->hideOnIndex();
        yield AssociationField::new('genre');
        yield AssociationField::new('magazine');
        yield AssociationField::new('critic');
        
        yield IntegerField::new('listItemCount', 'Albums')
            ->onlyOnIndex();

        yield CollectionField::new('listItems')
            ->useEntryCrudForm(OrderedAlbumListItemCrudController::class)
            ->setEntryIsComplex(true)
            ->setLabel('Ranked Albums')
            ->hideOnIndex();
    }

    public function createEntity(string $entityFqcn)
    {
        $entity = new AlbumList();
        $entity->setType(AlbumList::TYPE_ORDERED);
        return $entity;
    }
}
