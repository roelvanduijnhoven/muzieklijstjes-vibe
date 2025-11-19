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
    private ?int $rank = null;

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

    public function getRank(): ?int
    {
        return $this->rank;
    }

    public function setRank(?int $rank): static
    {
        $this->rank = $rank;

        return $this;
    }
}

