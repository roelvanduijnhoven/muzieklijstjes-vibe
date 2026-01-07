<?php

namespace App\Entity;

use App\Repository\AlbumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity(repositoryClass: AlbumRepository::class)]
class Album
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column]
    private ?int $releaseYear = null;

    #[ORM\OneToMany(mappedBy: 'album', targetEntity: AlbumArtist::class, orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $albumArtists;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $imageFetchFailed = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(type: 'string', enumType: \App\Enum\AlbumFormat::class, nullable: true)]
    private ?\App\Enum\AlbumFormat $format = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $wikipediaUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $musicBrainzId = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $ownedByHans = false;

    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'album', orphanRemoval: true)]
    private Collection $reviews;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
        $this->albumArtists = new ArrayCollection();
    }

    public function getArtistNames(): string
    {
        if ($this->albumArtists->isEmpty()) {
            return 'Unknown Artist';
        }

        $names = [];
        foreach ($this->albumArtists as $albumArtist) {
            if ($artist = $albumArtist->getArtist()) {
                $names[] = $artist->getName();
            }
        }
        
        return implode(', ', $names);
    }

    public function __toString(): string
    {
        $artistNames = $this->getArtistNames();
        $releaseYear = $this->releaseYear ? sprintf(' (%s)', $this->releaseYear) : '';
        return sprintf('%s - %s%s', $artistNames, $this->title ?? '', $releaseYear);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getReleaseYear(): ?int
    {
        return $this->releaseYear;
    }

    public function setReleaseYear(int $releaseYear): static
    {
        $this->releaseYear = $releaseYear;

        return $this;
    }

    public function getArtist(): ?Artist
    {
        if ($this->albumArtists->isEmpty()) {
            return null;
        }
        // Assuming ordered by position due to OrderBy annotation or insertion order
        return $this->albumArtists->first()->getArtist();
    }

    public function setArtist(?Artist $artist): static
    {
        // Remove existing artists
        foreach ($this->albumArtists as $albumArtist) {
            $this->removeAlbumArtist($albumArtist);
        }

        if ($artist) {
            $albumArtist = new AlbumArtist();
            $albumArtist->setArtist($artist);
            $albumArtist->setAlbum($this);
            $albumArtist->setPosition(0);
            $this->addAlbumArtist($albumArtist);
        }

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function isImageFetchFailed(): bool
    {
        return $this->imageFetchFailed;
    }

    public function setImageFetchFailed(bool $imageFetchFailed): static
    {
        $this->imageFetchFailed = $imageFetchFailed;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getFormat(): ?\App\Enum\AlbumFormat
    {
        return $this->format;
    }

    public function setFormat(?\App\Enum\AlbumFormat $format): static
    {
        $this->format = $format;

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

    public function isOwnedByHans(): bool
    {
        return $this->ownedByHans;
    }

    public function setOwnedByHans(bool $ownedByHans): static
    {
        $this->ownedByHans = $ownedByHans;

        return $this;
    }

    public function getSlug(): string
    {
        $slugger = new AsciiSlugger();
        $artist = $this->getArtist();
        $artistName = $artist ? $artist->getName() : '';
        $title = $this->getTitle() ?? '';
        return $slugger->slug(sprintf('%s-%s', $artistName, $title))->lower()->toString();
    }

    public function getRouteParams(): array
    {
        return [
            'id' => $this->getId(),
            'slug' => $this->getSlug(),
        ];
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setAlbum($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getAlbum() === $this) {
                // Review cannot exist without Album (nullable=false), so this might be tricky. 
                // But standard generated code usually checks for nullability.
                // In my Review entity, setAlbum takes ?Album but JoinColumn is nullable=false.
                // So setting it to null would violate DB constraint if flushed.
                // But here we just break the link. 
                // However, Review::setAlbum param type allows null.
                // Let's just try to set it to null in memory.
                // Actually, the generated code for nullable=false usually doesn't set it to null in remove, 
                // or throws exception. But orphanRemoval is true in Album, so removing it from collection should delete it.
                // So we don't need to set side to null if orphanRemoval is true.
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
            $albumArtist->setAlbum($this);
        }

        return $this;
    }

    public function removeAlbumArtist(AlbumArtist $albumArtist): static
    {
        if ($this->albumArtists->removeElement($albumArtist)) {
            // set the owning side to null (unless already changed)
            if ($albumArtist->getAlbum() === $this) {
                $albumArtist->setAlbum(null);
            }
        }

        return $this;
    }
}
