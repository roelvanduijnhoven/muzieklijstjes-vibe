<?php

namespace App\Controller\Admin;

use App\Entity\AlbumListItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class AlbumListItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AlbumListItem::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('album')
                ->autocomplete() // Crucial for performance
                ->setRequired(true),
            IntegerField::new('position'),
            IntegerField::new('mentions')->hideOnIndex(),
        ];
    }
}

