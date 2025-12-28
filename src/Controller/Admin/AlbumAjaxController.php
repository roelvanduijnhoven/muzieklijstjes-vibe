<?php

namespace App\Controller\Admin;

use App\Entity\Artist;
use App\Repository\ArtistRepository;
use App\Service\MusicBrainzService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AlbumAjaxController extends AbstractController
{
    public function __construct(
        private MusicBrainzService $musicBrainzService,
        private ArtistRepository $artistRepository
    ) {
    }

    #[Route('/admin/ajax/album/search-musicbrainz', name: 'admin_ajax_album_search_musicbrainz')]
    public function searchMusicBrainz(Request $request): JsonResponse
    {
        try {
            $artistId = $request->query->get('artistId');
            $albumTitle = $request->query->get('albumTitle');

            if (!$artistId || !$albumTitle) {
                return new JsonResponse(['error' => 'Artist ID and Album Title are required'], 400);
            }

            // Fetch the artist to get their MBID
            $artist = $this->artistRepository->find($artistId);
            if (!$artist) {
                return new JsonResponse(['error' => 'Artist not found'], 404);
            }

            if (!$artist->getMusicBrainzId()) {
                return new JsonResponse(['error' => 'Selected artist does not have a MusicBrainz ID set. Please update the artist first.'], 400);
            }

            $mbid = $this->musicBrainzService->searchAlbumByArtist($artist->getMusicBrainzId(), $albumTitle);

            if ($mbid === null) {
                 return new JsonResponse([
                     'error' => 'No album found on MusicBrainz for this artist',
                     'api_url' => $this->musicBrainzService->getAlbumSearchUrl($artist->getMusicBrainzId(), $albumTitle),
                     'web_url' => $this->musicBrainzService->getAlbumWebSearchUrl($artist->getMusicBrainzId(), $albumTitle)
                 ], 404);
            }

            return new JsonResponse([
                'mbid' => $mbid,
            ]);
        } catch (\Throwable $e) {
            $webUrl = (isset($artist) && isset($albumTitle) && $artist->getMusicBrainzId()) 
                ? $this->musicBrainzService->getAlbumWebSearchUrl($artist->getMusicBrainzId(), $albumTitle) 
                : null;
            $apiUrl = (isset($artist) && isset($albumTitle) && $artist->getMusicBrainzId()) 
                ? $this->musicBrainzService->getAlbumSearchUrl($artist->getMusicBrainzId(), $albumTitle) 
                : null;

            return new JsonResponse([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'api_url' => $apiUrl,
                'web_url' => $webUrl
            ], 500);
        }
    }
}

