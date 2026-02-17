<?php

namespace App\Entity;

use App\Repository\IssueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IssueRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_issue_magazine_year_number', columns: ['magazine_id', 'year', 'issue_number'])]
class Issue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'issues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Magazine $magazine = null;

    #[ORM\Column(length: 20)]
    private ?string $issueNumber = null;

    #[ORM\Column]
    private ?int $year = null;

    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'issue')]
    private Collection $reviews;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getIssueNumber(): ?string
    {
        return $this->issueNumber;
    }

    public function setIssueNumber(string $issueNumber): static
    {
        $this->issueNumber = $issueNumber;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

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
            $review->setIssue($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getIssue() === $this) {
                $review->setIssue(null);
            }
        }

        return $this;
    }
    
    public function __toString(): string
    {
        return sprintf('%s #%s (%d)', $this->getMagazine()->getName(), $this->getIssueNumber(), $this->getYear());
    }
}
