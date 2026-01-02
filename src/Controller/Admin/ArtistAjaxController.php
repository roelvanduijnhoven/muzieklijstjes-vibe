<?php

namespace App\Controller\Admin;

use App\Service\MusicBrainzService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ArtistAjaxController extends AbstractController
{
    public function __construct(
        private MusicBrainzService $musicBrainzService
    ) {
    }

    #[Route('/admin/ajax/artist/search-musicbrainz', name: 'admin_ajax_artist_search_musicbrainz')]
    public function searchMusicBrainz(Request $request): JsonResponse
    {
        try {
            $query = $request->query->get('query');
            if (!$query) {
                return new JsonResponse(['error' => 'No query provided'], 400);
            }

            $mbid = $this->musicBrainzService->searchArtist($query);

            if ($mbid === null) {
                // Return 404 explicitly if not found, but in JSON
                 return new JsonResponse([
                     'error' => 'No artist found on MusicBrainz',
                     'api_url' => $this->musicBrainzService->getArtistSearchUrl($query),
                     'web_url' => $this->musicBrainzService->getArtistWebSearchUrl($query)
                 ], 404);
            }

            return new JsonResponse([
                'mbid' => $mbid,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'api_url' => isset($query) ? $this->musicBrainzService->getArtistSearchUrl($query) : null,
                'web_url' => isset($query) ? $this->musicBrainzService->getArtistWebSearchUrl($query) : null
            ], 500);
        }
    }
}

