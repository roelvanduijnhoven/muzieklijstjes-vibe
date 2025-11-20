<?php

namespace App\Controller\Admin;

use App\Entity\AlbumList;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Doctrine\ORM\QueryBuilder;

class StandardAlbumListCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AlbumList::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.type != :aggregateType')
           ->setParameter('aggregateType', AlbumList::TYPE_AGGREGATE);

        return $qb;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Standard Lists')
            ->setPageTitle('new', 'Create Standard List')
            ->setPageTitle('edit', 'Edit Standard List');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title');
        yield ChoiceField::new('type')
            ->setChoices([
                'Ordered' => AlbumList::TYPE_ORDERED,
                'Unordered' => AlbumList::TYPE_UNORDERED,
            ]);
        yield IntegerField::new('releaseYear');
        yield TextareaField::new('description')->hideOnIndex();
        yield BooleanField::new('important');
        yield AssociationField::new('magazine');
        yield AssociationField::new('critic');
        
        yield CollectionField::new('listItems')
            ->useEntryCrudForm(AlbumListItemCrudController::class)
            ->setEntryIsComplex(true)
            ->setLabel('Albums');
    }
}

