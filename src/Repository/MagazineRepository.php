<?php

namespace App\Repository;

use App\Entity\Magazine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Magazine>
 *
 * @method Magazine|null find($id, $lockMode = null, $lockVersion = null)
 * @method Magazine|null findOneBy(array $criteria, array $orderBy = null)
 * @method Magazine[]    findAll()
 * @method Magazine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MagazineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Magazine::class);
    }

    /**
     * @return array<array{critic: \App\Entity\Critic, reviewCount: int}>
     */
    public function findTopCritics(Magazine $magazine, int $limit = 10): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('c as critic, COUNT(r.id) as reviewCount')
            ->from('App\Entity\Critic', 'c')
            ->join('c.reviews', 'r')
            ->where('r.magazine = :magazine')
            ->setParameter('magazine', $magazine)
            ->groupBy('c.id')
            ->orderBy('reviewCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

