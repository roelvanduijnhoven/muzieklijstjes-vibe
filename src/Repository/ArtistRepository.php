<?php

namespace App\Repository;

use App\Entity\Artist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Artist>
 *
 * @method Artist|null find($id, $lockMode = null, $lockVersion = null)
 * @method Artist|null findOneBy(array $criteria, array $orderBy = null)
 * @method Artist[]    findAll()
 * @method Artist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArtistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Artist::class);
    }

    /**
     * @return Artist[]
     */
    public function searchByName(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<array{artist: Artist, albumCount: int, reviewCount: int, listCount: int}>
     */
    public function searchByNameWithCounts(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->select('a as artist, COUNT(DISTINCT album.id) as albumCount, COUNT(DISTINCT review.id) as reviewCount, COUNT(DISTINCT albumList.id) as listCount')
            ->leftJoin('a.albumArtists', 'albumArtist')
            ->leftJoin('albumArtist.album', 'album')
            ->leftJoin('album.reviews', 'review')
            ->leftJoin(
                'App\Entity\AlbumListItem',
                'albumListItem',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'albumListItem.album = album'
            )
            ->leftJoin('albumListItem.albumList', 'albumList')
            ->andWhere('a.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->groupBy('a.id')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

