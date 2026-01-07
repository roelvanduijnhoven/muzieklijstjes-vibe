<?php

namespace App\Repository;

use App\Entity\AlbumList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AlbumList>
 *
 * @method AlbumList|null find($id, $lockMode = null, $lockVersion = null)
 * @method AlbumList|null findOneBy(array $criteria, array $orderBy = null)
 * @method AlbumList[]    findAll()
 * @method AlbumList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlbumListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlbumList::class);
    }

    /**
     * @return AlbumList[]
     */
    public function findByCritic(\App\Entity\Critic $critic, string $sort = 'title', string $direction = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('al')
            ->where('al.critic = :critic')
            ->setParameter('critic', $critic);

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        switch ($sort) {
            case 'year':
                $qb->orderBy('al.releaseYear', $direction);
                break;
            case 'type':
                $qb->orderBy('al.type', $direction);
                break;
            case 'count':
                $qb->leftJoin('al.listItems', 'li')
                   ->groupBy('al.id')
                   ->orderBy('COUNT(li.id)', $direction);
                break;
            case 'title':
            default:
                $qb->orderBy('al.title', $direction);
                break;
        }

        return $qb->getQuery()->getResult();
    }
}

