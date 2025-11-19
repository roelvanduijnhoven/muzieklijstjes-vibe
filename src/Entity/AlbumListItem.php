<?php

namespace App\Entity;

use App\Repository\AlbumListItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlbumListItemRepository::class)]
class AlbumListItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'listItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AlbumList $albumList = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Album $album = null;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    #[ORM\Column(nullable: true)]
    private ?int $mentions = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAlbumList(): ?AlbumList
    {
        return $this->albumList;
    }

    public function setAlbumList(?AlbumList $albumList): static
    {
        $this->albumList = $albumList;

        return $this;
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

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getMentions(): ?int
    {
        return $this->mentions;
    }

    public function setMentions(?int $mentions): static
    {
        $this->mentions = $mentions;

        return $this;
    }
}

