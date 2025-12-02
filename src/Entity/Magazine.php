<?php

namespace App\Entity;

use App\Repository\MagazineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MagazineRepository::class)]
class Magazine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $abbreviation = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $highestPossibleRating = null;

    #[ORM\OneToMany(targetEntity: AlbumList::class, mappedBy: 'magazine')]
    private Collection $albumLists;

    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'magazine')]
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

    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }

    public function setAbbreviation(?string $abbreviation): static
    {
        $this->abbreviation = $abbreviation;

        return $this;
    }

    public function getHighestPossibleRating(): ?int
    {
        return $this->highestPossibleRating;
    }

    public function setHighestPossibleRating(?int $highestPossibleRating): static
    {
        $this->highestPossibleRating = $highestPossibleRating;

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
            $albumList->setMagazine($this);
        }

        return $this;
    }

    public function removeAlbumList(AlbumList $albumList): static
    {
        if ($this->albumLists->removeElement($albumList)) {
            // set the owning side to null (unless already changed)
            if ($albumList->getMagazine() === $this) {
                $albumList->setMagazine(null);
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
            $review->setMagazine($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getMagazine() === $this) {
                $review->setMagazine(null);
            }
        }

        return $this;
    }
}
