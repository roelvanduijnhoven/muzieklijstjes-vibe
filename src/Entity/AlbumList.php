<?php

namespace App\Entity;

use App\Repository\AlbumListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlbumListRepository::class)]
class AlbumList
{
    public const TYPE_ORDERED = 'ordered';
    public const TYPE_UNORDERED = 'unordered';
    public const TYPE_MENTIONED = 'mentioned';
    public const TYPE_AGGREGATE = 'aggregate';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(nullable: true)]
    private ?int $releaseYear = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $important = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalUrl = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $visible = true;

    #[ORM\Column(nullable: true)]
    private ?int $periodStart = null;

    #[ORM\Column(nullable: true)]
    private ?int $periodEnd = null;

    #[ORM\Column(nullable: true)]
    private ?int $numberOfEntries = null;

    #[ORM\ManyToOne(inversedBy: 'albumLists')]
    private ?Magazine $magazine = null;

    #[ORM\ManyToOne(inversedBy: 'albumLists')]
    private ?Critic $critic = null;

    #[ORM\ManyToOne(inversedBy: 'albumLists')]
    private ?Genre $genre = null;

    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'aggregatedIn')]
    private Collection $sources;

    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'aggregatedIn')]
    private Collection $aggregatedIn;

    #[ORM\OneToMany(targetEntity: AlbumListItem::class, mappedBy: 'albumList', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $listItems;

    public function __construct()
    {
        $this->sources = new ArrayCollection();
        $this->aggregatedIn = new ArrayCollection();
        $this->listItems = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }

    public function getListItemCount(): int
    {
        return $this->listItems->count();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getReleaseYear(): ?int
    {
        return $this->releaseYear;
    }

    public function setReleaseYear(?int $releaseYear): static
    {
        $this->releaseYear = $releaseYear;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getCritic(): ?Critic
    {
        return $this->critic;
    }

    public function setCritic(?Critic $critic): static
    {
        $this->critic = $critic;

        return $this;
    }

    public function getGenre(): ?Genre
    {
        return $this->genre;
    }

    public function setGenre(?Genre $genre): static
    {
        $this->genre = $genre;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSources(): Collection
    {
        return $this->sources;
    }

    public function addSource(self $source): static
    {
        if (!$this->sources->contains($source)) {
            $this->sources->add($source);
        }

        return $this;
    }

    public function removeSource(self $source): static
    {
        $this->sources->removeElement($source);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getAggregatedIn(): Collection
    {
        return $this->aggregatedIn;
    }

    public function addAggregatedIn(self $aggregatedIn): static
    {
        if (!$this->aggregatedIn->contains($aggregatedIn)) {
            $this->aggregatedIn->add($aggregatedIn);
            $aggregatedIn->addSource($this);
        }

        return $this;
    }

    public function removeAggregatedIn(self $aggregatedIn): static
    {
        if ($this->aggregatedIn->removeElement($aggregatedIn)) {
            $aggregatedIn->removeSource($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, AlbumListItem>
     */
    public function getListItems(): Collection
    {
        return $this->listItems;
    }

    public function addListItem(AlbumListItem $listItem): static
    {
        if (!$this->listItems->contains($listItem)) {
            $this->listItems->add($listItem);
            $listItem->setAlbumList($this);
        }

        return $this;
    }

    public function removeListItem(AlbumListItem $listItem): static
    {
        if ($this->listItems->removeElement($listItem)) {
            // set the owning side to null (unless already changed)
            if ($listItem->getAlbumList() === $this) {
                $listItem->setAlbumList(null);
            }
        }

        return $this;
    }

    public function isImportant(): bool
    {
        return $this->important;
    }

    public function setImportant(bool $important): static
    {
        $this->important = $important;

        return $this;
    }

    public function getExternalUrl(): ?string
    {
        return $this->externalUrl;
    }

    public function setExternalUrl(?string $externalUrl): static
    {
        $this->externalUrl = $externalUrl;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function getPeriodStart(): ?int
    {
        return $this->periodStart;
    }

    public function setPeriodStart(?int $periodStart): static
    {
        $this->periodStart = $periodStart;

        return $this;
    }

    public function getPeriodEnd(): ?int
    {
        return $this->periodEnd;
    }

    public function setPeriodEnd(?int $periodEnd): static
    {
        $this->periodEnd = $periodEnd;

        return $this;
    }

    public function getNumberOfEntries(): ?int
    {
        return $this->numberOfEntries;
    }

    public function setNumberOfEntries(?int $numberOfEntries): static
    {
        $this->numberOfEntries = $numberOfEntries;

        return $this;
    }
}
