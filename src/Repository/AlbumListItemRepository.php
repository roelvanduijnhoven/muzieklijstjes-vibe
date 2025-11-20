<?php

namespace App\Repository;

use App\Entity\AlbumListItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AlbumListItem>
 *
 * @method AlbumListItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method AlbumListItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method AlbumListItem[]    findAll()
 * @method AlbumListItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlbumListItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlbumListItem::class);
    }

    /**
     * @return AlbumListItem[]
     */
    public function findByAlbumId(int $albumId): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.albumList', 'l')
            ->addSelect('l')
            ->where('i.album = :albumId')
            ->setParameter('albumId', $albumId)
            ->orderBy('l.releaseYear', 'DESC')
            ->addOrderBy('l.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

