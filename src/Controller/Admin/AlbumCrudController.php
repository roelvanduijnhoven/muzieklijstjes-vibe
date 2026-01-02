<?php

namespace App\Controller\Admin;

use App\Entity\Album;
use App\Service\AlbumCoverService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
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
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use App\Enum\AlbumFormat;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NullFilter;
use App\Controller\Admin\ArtistCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class AlbumCrudController extends AbstractCrudController
{
    public function __construct(
        private AlbumCoverService $albumCoverService,
        private EntityManagerInterface $entityManager,
        private AdminUrlGenerator $adminUrlGenerator
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Album::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NullFilter::new('musicBrainzId')->setChoiceLabels('No MBID', 'Has MBID'));
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile('assets/js/admin/album_musicbrainz.js?v=2');
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewOnSite = Action::new('viewOnSite', 'View on Site', 'fa fa-eye')
            ->linkToRoute('app_album_show', function (Album $album): array {
                return $album->getRouteParams();
            });

        $refreshCover = Action::new('refreshCover', 'Refresh cover', 'fa fa-image')
            ->linkToCrudAction('refreshCover')
            ->displayIf(fn (Album $album) => $album->getMusicBrainzId() !== null);

        $jumpToArtist = Action::new('jumpToArtist', 'Jump to Artist', 'fa fa-user')
            ->linkToUrl(function (Album $album) {
                return $this->adminUrlGenerator
                    ->setController(ArtistCrudController::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($album->getArtist()->getId())
                    ->generateUrl();
            })
            ->displayIf(fn (Album $album) => $album->getArtist() !== null);

        return $actions
            ->add(Crud::PAGE_INDEX, $viewOnSite)
            ->add(Crud::PAGE_DETAIL, $viewOnSite)
            ->add(Crud::PAGE_EDIT, $viewOnSite)
            ->add(Crud::PAGE_DETAIL, $refreshCover)
            ->add(Crud::PAGE_EDIT, $refreshCover)
            ->add(Crud::PAGE_DETAIL, $jumpToArtist)
            ->add(Crud::PAGE_EDIT, $jumpToArtist);
    }

    public function refreshCover(AdminContext $context): Response
    {
        // Manually fetch the entity to be safe against Context issues
        $id = $context->getRequest()->query->get('entityId');
        $entity = $this->entityManager->getRepository(Album::class)->find($id);

        if (!$entity instanceof Album) {
            $this->addFlash('danger', 'Album not found.');
            return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
        }

        $newPath = $this->albumCoverService->refreshCover($entity);
        $this->entityManager->flush();

        if ($newPath) {
            $this->addFlash('success', 'Cover refreshed.');
        } else {
            $this->addFlash('warning', 'No cover found; cover cleared.');
        }

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
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
            TextField::new('label')->hideOnIndex(),
            TextField::new('musicBrainzId')->hideOnIndex(),
            ChoiceField::new('format')->setChoices([
                'CD' => AlbumFormat::CD,
                'DVD' => AlbumFormat::DVD,
                'Video' => AlbumFormat::VIDEO,
                'LP' => AlbumFormat::LP,
                'Unknown' => AlbumFormat::UNKNOWN,
            ])->hideOnIndex(),
            UrlField::new('wikipediaUrl')->hideOnIndex(),
            BooleanField::new('ownedByHans'),
            ImageField::new('imageUrl', 'Cover')
                ->setBasePath($this->getParameter('app.album_cover_base_url'))
                ->hideOnForm(),
        ];
    }
}
