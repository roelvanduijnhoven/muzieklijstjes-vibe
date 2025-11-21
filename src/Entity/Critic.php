<?php

namespace App\Entity;

use App\Repository\CriticRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

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

    #[ORM\OneToMany(targetEntity: AlbumList::class, mappedBy: 'critic')]
    private Collection $albumLists;

    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'critic')]
    private Collection $reviews;

    public function __construct()
    {
        $this->albumLists = new ArrayCollection();
        $this->reviews = new ArrayCollection();
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
}
