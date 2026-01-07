<?php

namespace App\Form\Type;

use App\Form\DataTransformer\AlbumArtistsToIdStringTransformer;
use App\Repository\ArtistRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AlbumArtistsType extends AbstractType
{
    public function __construct(
        private ArtistRepository $artistRepository,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new AlbumArtistsToIdStringTransformer($this->artistRepository));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // Add ID attribute if missing so JS can find it easily
        if (!isset($view->vars['attr']['id'])) {
             // Generate a consistent ID if possible, or let Symfony do it.
             // Symfony usually sets 'id' in $view->vars['id'].
             // We want to force a class or specific ID if needed.
        }
        $view->vars['attr']['id'] = $view->vars['id']; // Ensure ID is in attrs for some JS access
        
        $value = $view->vars['value'];
        $initialOptions = [];
        
        if ($value) {
            $ids = explode(',', $value);
            foreach ($ids as $id) {
                if ($artist = $this->artistRepository->find($id)) {
                    $initialOptions[] = [
                        'id' => $artist->getId(),
                        'text' => $artist->getName(),
                    ];
                }
            }
        }

        $view->vars['attr']['data-initial-options'] = json_encode($initialOptions);
        $view->vars['attr']['data-search-url'] = $this->urlGenerator->generate('admin_api_artist_search');
        $view->vars['attr']['class'] = ($view->vars['attr']['class'] ?? '') . ' js-album-artists-select';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            // Allow options passed by EasyAdmin's AssociationField
            'class' => null,
            'multiple' => true,
            'query_builder' => null,
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
