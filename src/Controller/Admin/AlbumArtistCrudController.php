<?php

namespace App\Controller\Admin;

use App\Entity\AlbumArtist;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class AlbumArtistCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AlbumArtist::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('artist');
        yield IntegerField::new('position');
    }
}

