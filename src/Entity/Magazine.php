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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\OneToMany(targetEntity: AlbumList::class, mappedBy: 'magazine')]
    private Collection $albumLists;

    public function __construct()
    {
        $this->albumLists = new ArrayCollection();
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

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

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
}
