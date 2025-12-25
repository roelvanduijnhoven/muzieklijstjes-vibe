<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Repository\ArtistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArtistController extends AbstractController
{
    #[Route('/artist/search', name: 'app_artist_search')]
    public function search(Request $request, ArtistRepository $artistRepository): Response
    {
        $query = $request->query->get('q');
        $artists = $artistRepository->searchByName($query);

        if (count($artists) === 1) {
            return $this->redirectToRoute('app_artist_show', ['id' => $artists[0]->getId(), 'slug' => $artists[0]->getSlug()]);
        }

        return $this->render('artist/search.html.twig', [
            'artists' => $artists,
            'query' => $query,
        ]);
    }

    #[Route('/artist/{id}/{slug}', name: 'app_artist_show', defaults: ['slug' => null])]
    public function show(Artist $artist, ?string $slug = null): Response
    {
        $expectedSlug = $artist->getSlug();
        if ($slug !== $expectedSlug) {
            return $this->redirectToRoute('app_artist_show', ['id' => $artist->getId(), 'slug' => $expectedSlug], 301);
        }

        return $this->render('artist/show.html.twig', [
            'artist' => $artist,
        ]);
    }
}

