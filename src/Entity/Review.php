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

    #[ORM\Column(nullable: true)]
    private ?float $rating = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $legacyRubric = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    private ?Rubric $rubric = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Issue $issue = null;

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
        return $this->getIssue()?->getMagazine();
    }

    public function getYear(): ?int
    {
        return $this->getIssue()?->getYear();
    }

    public function getIssueNumber(): ?string
    {
        return $this->getIssue()?->getIssueNumber();
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

    public function getIssue(): ?Issue
    {
        return $this->issue;
    }

    public function setIssue(?Issue $issue): static
    {
        $this->issue = $issue;

        return $this;
    }
}

