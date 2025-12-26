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


    public function findTopDoctors(int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.appointments', 'a')
            ->select('d as doctor', 'COUNT(a.id) as appCount')
            ->groupBy('d.id')
            ->orderBy('appCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

