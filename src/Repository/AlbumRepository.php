<?php

namespace App\Repository;

use App\Entity\Album;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Album>
 *
 * @method Album|null find($id, $lockMode = null, $lockVersion = null)
 * @method Album|null findOneBy(array $criteria, array $orderBy = null)
 * @method Album[]    findAll()
 * @method Album[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlbumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Album::class);
    }

    /**
     * @return Album[]
     */
    public function searchByTitle(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.title LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<array{album: Album, reviewCount: int, listCount: int}>
     */
    public function searchByTitleWithCounts(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->select('a as album, COUNT(DISTINCT r.id) as reviewCount, COUNT(DISTINCT al.id) as listCount')
            ->leftJoin('a.reviews', 'r')
            ->leftJoin(
                'App\Entity\AlbumListItem',
                'ali',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'ali.album = a'
            )
            ->leftJoin('ali.albumList', 'al')
            ->andWhere('a.title LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->groupBy('a.id')
            ->orderBy('a.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<array{album: Album, score: int}>
     */
    public function findMostListedAlbums(int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->select('a as album, COUNT(DISTINCT al.id) as score')
            ->join(
                'App\Entity\AlbumListItem', 
                'ali', 
                \Doctrine\ORM\Query\Expr\Join::WITH, 
                'ali.album = a'
            )
            ->join('ali.albumList', 'al')
            // Count lists that are important
            ->where('al.important = :important')
            ->setParameter('important', true)
            ->groupBy('a')
            ->orderBy('score', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<array{album: Album, score: int}>
     */
    public function findMostListedAlbumsByYear(int $year, int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->select('a as album, COUNT(DISTINCT al.id) as score')
            ->join(
                'App\Entity\AlbumListItem', 
                'ali', 
                \Doctrine\ORM\Query\Expr\Join::WITH, 
                'ali.album = a'
            )
            ->join('ali.albumList', 'al')
            // Only count Top Level lists (lists that are not aggregated in others) for the year chart
            ->leftJoin('al.aggregatedIn', 'agg')
            ->where('al.releaseYear = :year')
            ->andWhere('agg.id IS NULL')
            ->setParameter('year', $year)
            ->groupBy('a')
            ->orderBy('score', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<array{album: Album, reviewCount: int, listCount: int}>
     */
    public function findAlbumsWithCountsByArtist(\App\Entity\Artist $artist): array
    {
        return $this->createQueryBuilder('a')
            ->select('a as album, COUNT(DISTINCT r.id) as reviewCount, COUNT(DISTINCT al.id) as listCount')
            ->leftJoin('a.reviews', 'r')
            ->join('a.albumArtists', 'aa')
            ->leftJoin(
                'App\Entity\AlbumListItem', 
                'ali', 
                \Doctrine\ORM\Query\Expr\Join::WITH, 
                'ali.album = a'
            )
            ->leftJoin('ali.albumList', 'al')
            ->leftJoin('al.aggregatedIn', 'agg')
            ->where('aa.artist = :artist')
            // Only count lists that are NOT aggregated in other lists (Top Level Lists)
            // But we must be careful: if we filter by agg.id IS NULL, we might exclude lists that are aggregated 
            // but effectively we want to count the "unique independent mentions".
            // The user said "if multiple individual lists are contained in an aggregate".
            // If we count the aggregate (which has agg.id IS NULL), and exclude the source (which has agg.id NOT NULL).
            // This works.
            // But we must ensure that we don't filter out the ALBUM if it has 0 lists.
            // Since we use LEFT JOIN for list items, if no list items, count is 0.
            // But the WHERE clause on 'agg' might filter rows?
            // "agg.id IS NULL" matches if there is NO aggregate.
            // If there is no list, al is null, agg is null. So it matches.
            ->andWhere('agg.id IS NULL') 
            ->setParameter('artist', $artist)
            ->groupBy('a.id')
            ->orderBy('a.releaseYear', 'ASC')
            ->addOrderBy('a.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
