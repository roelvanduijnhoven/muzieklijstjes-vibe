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
        // Pass initial data to the view so JS can pre-populate the select
        $collection = $form->getData(); // This is likely the Collection (if view is built after transform?) 
        // Wait, getData() on the form returns the model data (Collection) before transform?
        // Or after?
        // In buildView, `form->getData()` returns the NORMALIZED data (result of transform). 
        // But our transformer converts to string.
        // We need the original entities to get names.
        
        // Actually, we can access the model data via parent form or by checking how the data is passed.
        // Let's assume we can't easily get the entities from the string here without re-querying.
        
        // However, we can use the transformer's logic or just re-fetch.
        // But better: The `value` in the view is the string "1,2,3".
        
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
