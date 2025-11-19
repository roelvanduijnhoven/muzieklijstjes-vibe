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
}

