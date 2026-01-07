<?php

namespace App\Form\DataTransformer;

use App\Entity\AlbumArtist;
use App\Entity\Artist;
use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\DataTransformerInterface;

class AlbumArtistsToIdStringTransformer implements DataTransformerInterface
{
    /**
     * @var array<int, AlbumArtist>
     */
    private array $originalAlbumArtists = [];

    public function __construct(
        private ArtistRepository $artistRepository
    ) {
    }

    /**
     * Transforms a collection of AlbumArtists to a comma-separated string of Artist IDs.
     *
     * @param Collection<int, AlbumArtist>|null $albumArtists
     * @return string
     */
    public function transform($albumArtists): string
    {
        if (null === $albumArtists) {
            return '';
        }

        $this->originalAlbumArtists = [];
        $ids = [];

        foreach ($albumArtists as $albumArtist) {
            $artist = $albumArtist->getArtist();
            if ($artist) {
                $this->originalAlbumArtists[$artist->getId()] = $albumArtist;
                $ids[] = $artist->getId();
            }
        }

        return implode(',', $ids);
    }

    /**
     * Transforms a comma-separated string of Artist IDs to a collection of AlbumArtists.
     *
     * @param string|null $value
     * @return Collection<int, AlbumArtist>
     */
    public function reverseTransform($value): Collection
    {
        if (!$value) {
            return new ArrayCollection();
        }

        $ids = explode(',', $value);
        $newCollection = new ArrayCollection();
        $position = 0;

        foreach ($ids as $id) {
            $id = trim($id);
            if (empty($id)) {
                continue;
            }

            $albumArtist = null;

            // Check if we have an existing AlbumArtist for this Artist
            if (isset($this->originalAlbumArtists[$id])) {
                $albumArtist = $this->originalAlbumArtists[$id];
            } else {
                $artist = $this->artistRepository->find($id);
                if ($artist) {
                    $albumArtist = new AlbumArtist();
                    $albumArtist->setArtist($artist);
                }
            }

            if ($albumArtist) {
                $albumArtist->setPosition($position);
                $newCollection->add($albumArtist);
                $position++;
            }
        }

        return $newCollection;
    }
}

