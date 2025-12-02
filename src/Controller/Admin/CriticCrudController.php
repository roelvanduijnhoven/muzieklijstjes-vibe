<?php

namespace App\Controller\Admin;

use App\Entity\Critic;
use EasyCorp\Bundle\EasyAdminBundle\Config\Config;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class CriticCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Critic::class;
    }

    public function configureConfig(Config $config): Config
    {
        return $config
            ->setDefaultSort(['sortName' => 'ASC', 'abbreviation' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name')->setTemplatePath('admin/field/link_to_edit.html.twig'),
            TextField::new('sortName')->hideOnIndex(),
            TextField::new('abbreviation', 'Abbreviation (Code)')->hideOnIndex(),
            IntegerField::new('birthYear')->hideOnIndex(),
            IntegerField::new('deathYear')->hideOnIndex(),
            UrlField::new('url')->hideOnIndex(),
            UrlField::new('wikipediaUrl')->hideOnIndex(),
            TextEditorField::new('bio')->hideOnIndex(),
            AssociationField::new('genres')->autocomplete(),
            AssociationField::new('features')->autocomplete(),
        ];
    }
}

