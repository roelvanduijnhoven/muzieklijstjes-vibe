<?php

namespace App\Controller\Admin;

use App\Entity\AlbumList;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Doctrine\ORM\QueryBuilder;

class AggregateAlbumListCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AlbumList::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.type = :aggregateType')
           ->setParameter('aggregateType', AlbumList::TYPE_AGGREGATE);

        return $qb;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Aggregate Lists')
            ->setPageTitle('new', 'Create Aggregate List')
            ->setPageTitle('edit', 'Edit Aggregate List');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title')->setTemplatePath('admin/field/link_to_edit.html.twig');
        
        yield IntegerField::new('releaseYear');
        yield TextareaField::new('description')->hideOnIndex();
        yield BooleanField::new('important');
        yield BooleanField::new('visible');
        yield UrlField::new('externalUrl')->hideOnIndex();
        yield AssociationField::new('genre');
        yield AssociationField::new('magazine');
        
        // Select other lists to aggregate
        yield AssociationField::new('sources')
            ->setLabel('Source Lists')
            ->setQueryBuilder(function (QueryBuilder $qb) {
                return $qb->andWhere('entity.type != :aggregateType')
                    ->setParameter('aggregateType', AlbumList::TYPE_AGGREGATE);
            })
            ->autocomplete(); 
    }

    public function createEntity(string $entityFqcn)
    {
        $entity = new AlbumList();
        $entity->setType(AlbumList::TYPE_AGGREGATE);
        return $entity;
    }
}
