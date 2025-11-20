<?php

namespace App\Controller\Admin;

use App\Entity\Magazine;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class MagazineCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Magazine::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            UrlField::new('website'),
        ];
    }
}

