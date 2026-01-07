<?php

namespace App\Controller\Admin;

use App\Entity\Artist;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NullFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use App\Controller\Admin\AlbumCrudController;

class ArtistCrudController extends AbstractCrudController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Artist::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NullFilter::new('musicBrainzId')->setChoiceLabels('No MBID', 'Has MBID'));
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile('assets/js/admin/artist_musicbrainz.js?v=4');
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewOnSite = Action::new('viewOnSite', 'View on Site', 'fa fa-eye')
            ->linkToRoute('app_artist_show', function (Artist $artist): array {
                return $artist->getRouteParams();
            });

        $viewAlbums = Action::new('viewAlbums', 'View Albums', 'fa fa-music')
            ->linkToUrl(function (Artist $artist) {
                return $this->adminUrlGenerator
                    ->setController(AlbumCrudController::class)
                    ->setAction(Action::INDEX)
                    ->set('query', $artist->getName())
                    ->generateUrl();
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $viewOnSite)
            ->add(Crud::PAGE_DETAIL, $viewOnSite)
            ->add(Crud::PAGE_EDIT, $viewOnSite)
            ->add(Crud::PAGE_DETAIL, $viewAlbums)
            ->add(Crud::PAGE_EDIT, $viewAlbums);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['sortName' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name')->setTemplatePath('admin/field/link_to_edit.html.twig'),
            TextField::new('sortName')->hideOnIndex(),
            TextField::new('musicBrainzId')->hideOnIndex(),
            \EasyCorp\Bundle\EasyAdminBundle\Field\UrlField::new('wikipediaUrl')->hideOnIndex(),
            IntegerField::new('albumArtists.count', 'Album Count')->hideOnForm(),
        ];
    }
}

