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
use App\Form\Type\AlbumArtistsType;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
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
            ->addJsFile('https://code.jquery.com/jquery-3.7.1.min.js') // Fix for legacy scripts expecting jQuery
            ->addJsFile('https://code.jquery.com/ui/1.13.2/jquery-ui.min.js') // Fix for TomSelect drag_drop requiring jQuery UI sortable
            ->addCssFile('https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css')
            ->addJsFile('https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js')
            ->addJsFile('assets/js/admin/album_musicbrainz.js?v=3')
            ->addJsFile('assets/js/admin/album_artists.js?v=1')
            ->addCssFile('assets/css/admin/tom-select.dark.css?v=1');
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
            ->setSearchFields(['title', 'albumArtists.artist.name'])
            ->setAutofocusSearch(true);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            // Use Field::new because EasyAdmin fails to guess the type for OneToMany with extra attributes
            // when we use a custom form type. However, EasyAdmin still tries to introspect.
            // When using Field::new without specifying a type, it checks Doctrine metadata.
            // Type 4 is OneToMany.
            // If we use CollectionField, it works for OneToMany but we want our custom form type.
            // If we use AssociationField, it expects an entity relation.
            // The issue is likely that we changed it to Field::new which triggers type guessing.
            // Let's try explicitly setting it as CollectionField but overriding the form type entirely.
            AssociationField::new('albumArtists')
                ->setFormType(AlbumArtistsType::class)
                ->setFormTypeOption('by_reference', false)
                // Use a template that renders the simple widget without trying to render association magic
                // Actually, since we changed the parent type to TextType, EasyAdmin might just render it as a text field.
                ->addCssClass('js-album-artists-field')
                ->hideOnIndex(),
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
