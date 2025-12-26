<?php

namespace App\Repository;

use App\Entity\Specialty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SpecialtyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Specialty::class);
    }
    public function findTopSpecialties(int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.doctors', 'd')
            ->select('s as specialty', 'COUNT(d.id) as doctorCount')
            ->groupBy('s.id')
            ->orderBy('doctorCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
