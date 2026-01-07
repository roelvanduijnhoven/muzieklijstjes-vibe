<?php

namespace App\Entity;

use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity(repositoryClass: ArtistRepository::class)]
class Artist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sortName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $wikipediaUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $musicBrainzId = null;

    #[ORM\OneToMany(mappedBy: 'artist', targetEntity: AlbumArtist::class)]
    private Collection $albumArtists;

    public function __construct()
    {
        $this->albumArtists = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSortName(): ?string
    {
        return $this->sortName;
    }

    public function setSortName(?string $sortName): static
    {
        $this->sortName = $sortName;

        return $this;
    }

    public function getWikipediaUrl(): ?string
    {
        return $this->wikipediaUrl;
    }

    public function setWikipediaUrl(?string $wikipediaUrl): static
    {
        $this->wikipediaUrl = $wikipediaUrl;

        return $this;
    }

    public function getMusicBrainzId(): ?string
    {
        return $this->musicBrainzId;
    }

    public function setMusicBrainzId(?string $musicBrainzId): static
    {
        $this->musicBrainzId = $musicBrainzId;

        return $this;
    }

    public function getSlug(): string
    {
        $slugger = new AsciiSlugger();
        return $slugger->slug($this->getName() ?? '')->lower()->toString();
    }

    public function getRouteParams(): array
    {
        return [
            'id' => $this->getId(),
            'slug' => $this->getSlug(),
        ];
    }

    /**
     * @return Collection<int, Album>
     */
    public function getAlbums(): Collection
    {
        $albums = new ArrayCollection();
        foreach ($this->albumArtists as $albumArtist) {
            $albums->add($albumArtist->getAlbum());
        }
        
        // Emulate previous ordering: releaseYear ASC, title ASC
        $iterator = $albums->getIterator();
        $iterator->uasort(function ($a, $b) {
            if ($a->getReleaseYear() === $b->getReleaseYear()) {
                return strcmp($a->getTitle(), $b->getTitle());
            }
            return $a->getReleaseYear() <=> $b->getReleaseYear();
        });
        
        return new ArrayCollection(iterator_to_array($iterator));
    }

    public function addAlbum(Album $album): static
    {
        foreach ($this->albumArtists as $albumArtist) {
            if ($albumArtist->getAlbum() === $album) {
                return $this;
            }
        }

        $albumArtist = new AlbumArtist();
        $albumArtist->setArtist($this);
        $albumArtist->setAlbum($album);
        $albumArtist->setPosition(0);
        $this->addAlbumArtist($albumArtist);

        return $this;
    }

    public function removeAlbum(Album $album): static
    {
        foreach ($this->albumArtists as $albumArtist) {
            if ($albumArtist->getAlbum() === $album) {
                $this->removeAlbumArtist($albumArtist);
                break;
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AlbumArtist>
     */
    public function getAlbumArtists(): Collection
    {
        return $this->albumArtists;
    }

    public function addAlbumArtist(AlbumArtist $albumArtist): static
    {
        if (!$this->albumArtists->contains($albumArtist)) {
            $this->albumArtists->add($albumArtist);
            $albumArtist->setArtist($this);
        }

        return $this;
    }

    public function removeAlbumArtist(AlbumArtist $albumArtist): static
    {
        if ($this->albumArtists->removeElement($albumArtist)) {
            // set the owning side to null (unless already changed)
            if ($albumArtist->getArtist() === $this) {
                $albumArtist->setArtist(null);
            }
        }

        return $this;
    }
}
