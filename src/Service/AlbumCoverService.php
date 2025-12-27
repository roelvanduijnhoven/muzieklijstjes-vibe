<?php

namespace App\Service;

use App\Entity\Album;
use Symfony\Component\String\Slugger\AsciiSlugger;

class AlbumCoverService
{
    public function __construct(
        private MusicBrainzService $musicBrainzService,
        private ImageStorageService $imageStorageService
    ) {
    }

    public function refreshCover(Album $album): ?string
    {
        $mbid = $album->getMusicBrainzId();

        if (!$mbid) {
            $album->setImageUrl(null);
            return null;
        }

        $coverUrl = $this->musicBrainzService->fetchCoverArtUrl($mbid);

        if (!$coverUrl) {
            $album->setImageUrl(null);
            return null;
        }

        $slugger = new AsciiSlugger();
        $slug = $slugger->slug(sprintf('%s-%s', $album->getArtist()?->getName() ?? 'unknown', $album->getTitle()))->lower();
        $filename = sprintf('%s-%s.jpg', $slug, time());
        $destinationPath = 'albums/' . $filename;

        $success = $this->imageStorageService->fetchAndStore($coverUrl, $destinationPath);

        if ($success) {
            $album->setImageUrl($destinationPath);
            return $destinationPath;
        }

        $album->setImageUrl(null);
        return null;
    }
}

