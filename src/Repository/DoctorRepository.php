<?php

namespace App\Repository;

use App\Entity\Doctor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Doctor>
 */
class DoctorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Doctor::class);
    }

    public function findBySearchCriteria(?string $specialty, ?string $ville, ?string $nom)
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.specialty', 's')
            ->addSelect('s');

        if ($specialty) {
            $qb->andWhere('s.nom LIKE :specialty')
               ->setParameter('specialty', '%' . $specialty . '%');
        }

        if ($ville) {
            $qb->andWhere('d.ville LIKE :ville')
               ->setParameter('ville', '%' . $ville . '%');
        }

        if ($nom) {
            $qb->andWhere('d.nom LIKE :nom OR d.prenom LIKE :nom')
               ->setParameter('nom', '%' . $nom . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function findTopRatedDoctors(int $limit = 10)
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.reviews', 'r')
            ->addSelect('AVG(r.rating) as HIDDEN avg_rating')
            ->groupBy('d.id')
            ->orderBy('avg_rating', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
