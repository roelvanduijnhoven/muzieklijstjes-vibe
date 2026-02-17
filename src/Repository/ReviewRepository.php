<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @return Review[]
     */
    public function findByCritic(\App\Entity\Critic $critic, string $sort = 'album', string $direction = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.album', 'a')
            ->leftJoin('r.issue', 'i')
            ->leftJoin('i.magazine', 'm')
            ->where('r.critic = :critic')
            ->setParameter('critic', $critic);

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        switch ($sort) {
            case 'rating':
                $qb->orderBy('r.rating', $direction);
                break;
            case 'magazine':
                $qb->orderBy('m.name', $direction);
                break;
            case 'year':
                $qb->orderBy('i.year', $direction);
                break;
            case 'album':
            default:
                $qb->orderBy('a.title', $direction);
                break;
        }

        return $qb->getQuery()->getResult();
    }
}

