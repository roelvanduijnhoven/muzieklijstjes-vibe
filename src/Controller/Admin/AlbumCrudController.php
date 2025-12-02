<?php

namespace App\Controller\Admin;

use App\Entity\Album;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use App\Enum\AlbumFormat;

class AlbumCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Album::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setSearchFields(['title', 'artist.name'])
            ->setAutofocusSearch(true);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('artist'),
            TextField::new('title')->setTemplatePath('admin/field/link_to_edit.html.twig'),
            IntegerField::new('releaseYear'),
            TextField::new('catalogueNumber')->hideOnIndex(),
            ChoiceField::new('format')->setChoices([
                'CD' => AlbumFormat::CD,
                'DVD' => AlbumFormat::DVD,
                'Video' => AlbumFormat::VIDEO,
                'LP' => AlbumFormat::LP,
                'Unknown' => AlbumFormat::UNKNOWN,
            ])->hideOnIndex(),
            UrlField::new('externalUrl')->hideOnIndex(),
            UrlField::new('wikipediaUrl')->hideOnIndex(),
            BooleanField::new('ownedByHans'),
            ImageField::new('imageUrl', 'Cover')
                ->setBasePath($this->getParameter('app.album_cover_base_url'))
                ->hideOnForm(),
        ];
    }
}
