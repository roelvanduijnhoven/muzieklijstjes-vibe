<?php

namespace App\Entity;

use App\Repository\CriticRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity(repositoryClass: CriticRepository::class)]
class Critic
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
    private ?string $abbreviation = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(nullable: true)]
    
    private ?int $birthYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $deathYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $wikipediaUrl = null;

    #[ORM\OneToMany(targetEntity: AlbumList::class, mappedBy: 'critic')]
    private Collection $albumLists;

    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'critic')]
    private Collection $reviews;

    #[ORM\ManyToMany(targetEntity: Genre::class, inversedBy: 'critics')]
    private Collection $genres;

    #[ORM\ManyToMany(targetEntity: Feature::class, inversedBy: 'critics')]
    private Collection $features;

    public function __construct()
    {
        $this->albumLists = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->genres = new ArrayCollection();
        $this->features = new ArrayCollection();
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

    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }

    public function setAbbreviation(?string $abbreviation): static
    {
        $this->abbreviation = $abbreviation;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getBirthYear(): ?int
    {
        return $this->birthYear;
    }

    public function setBirthYear(?int $birthYear): static
    {
        $this->birthYear = $birthYear;

        return $this;
    }

    public function getDeathYear(): ?int
    {
        return $this->deathYear;
    }

    public function setDeathYear(?int $deathYear): static
    {
        $this->deathYear = $deathYear;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

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

    public function getSlug(): string
    {
        $slugger = new AsciiSlugger();
        return $slugger->slug($this->getName() ?? '')->lower()->toString();
    }

    /**
     * @return Collection<int, AlbumList>
     */
    public function getAlbumLists(): Collection
    {
        return $this->albumLists;
    }

    public function addAlbumList(AlbumList $albumList): static
    {
        if (!$this->albumLists->contains($albumList)) {
            $this->albumLists->add($albumList);
            $albumList->setCritic($this);
        }

        return $this;
    }

    public function removeAlbumList(AlbumList $albumList): static
    {
        if ($this->albumLists->removeElement($albumList)) {
            // set the owning side to null (unless already changed)
            if ($albumList->getCritic() === $this) {
                $albumList->setCritic(null);
            }
        }

        return $this;
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
            $review->setCritic($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getCritic() === $this) {
                $review->setCritic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Genre>
     */
    public function getGenres(): Collection
    {
        return $this->genres;
    }

    public function addGenre(Genre $genre): static
    {
        if (!$this->genres->contains($genre)) {
            $this->genres->add($genre);
        }

        return $this;
    }

    public function removeGenre(Genre $genre): static
    {
        $this->genres->removeElement($genre);

        return $this;
    }

    /**
     * @return Collection<int, Feature>
     */
    public function getFeatures(): Collection
    {
        return $this->features;
    }

    public function addFeature(Feature $feature): static
    {
        if (!$this->features->contains($feature)) {
            $this->features->add($feature);
        }

        return $this;
    }

    public function removeFeature(Feature $feature): static
    {
        $this->features->removeElement($feature);

        return $this;
    }
}
