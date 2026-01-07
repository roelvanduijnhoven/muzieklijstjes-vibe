<?php

namespace App\Repository;

use App\Entity\AlbumArtist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AlbumArtist>
 *
 * @method AlbumArtist|null find($id, $lockMode = null, $lockVersion = null)
 * @method AlbumArtist|null findOneBy(array $criteria, array $orderBy = null)
 * @method AlbumArtist[]    findAll()
 * @method AlbumArtist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlbumArtistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlbumArtist::class);
    }
}

