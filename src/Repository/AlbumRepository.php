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
            ->join('a.artist', 'ar')
            ->addSelect('ar')
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
            ->join('a.artist', 'ar')
            ->addSelect('ar')
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
}
