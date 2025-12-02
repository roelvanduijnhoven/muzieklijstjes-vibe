<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Album $album = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Critic $critic = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Magazine $magazine = null;

    #[ORM\Column(nullable: true)]
    private ?int $year = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $issueNumber = null;

    #[ORM\Column(nullable: true)]
    private ?float $rating = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $legacyRubric = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    private ?Rubric $rubric = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAlbum(): ?Album
    {
        return $this->album;
    }

    public function setAlbum(?Album $album): static
    {
        $this->album = $album;

        return $this;
    }

    public function getCritic(): ?Critic
    {
        return $this->critic;
    }

    public function setCritic(?Critic $critic): static
    {
        $this->critic = $critic;

        return $this;
    }

    public function getMagazine(): ?Magazine
    {
        return $this->magazine;
    }

    public function setMagazine(?Magazine $magazine): static
    {
        $this->magazine = $magazine;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getIssueNumber(): ?string
    {
        return $this->issueNumber;
    }

    public function setIssueNumber(?string $issueNumber): static
    {
        $this->issueNumber = $issueNumber;

        return $this;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(?float $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getLegacyRubric(): ?string
    {
        return $this->legacyRubric;
    }

    public function setLegacyRubric(?string $legacyRubric): static
    {
        $this->legacyRubric = $legacyRubric;

        return $this;
    }

    public function getRubric(): ?Rubric
    {
        return $this->rubric;
    }

    public function setRubric(?Rubric $rubric): static
    {
        $this->rubric = $rubric;

        return $this;
    }
}

