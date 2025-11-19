<?php

namespace App\Repository;

use App\Entity\Critic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Critic>
 *
 * @method Critic|null find($id, $lockMode = null, $lockVersion = null)
 * @method Critic|null findOneBy(array $criteria, array $orderBy = null)
 * @method Critic[]    findAll()
 * @method Critic[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CriticRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Critic::class);
    }
}

