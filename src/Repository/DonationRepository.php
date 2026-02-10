<?php

namespace App\Repository;

use App\Entity\Donation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DonationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Donation::class);
    }

    /**
     * Custom method to handle sorting through joined tables
     */
    public function findAllSorted(string $sort, string $direction): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.type', 't')
            ->leftJoin('d.donateur', 'u');

        // Logic to sort by sub-fields
        if ($sort === 'type') {
            $qb->orderBy('t.libelle', $direction);
        } elseif ($sort === 'donateur') {
            $qb->orderBy('u.nom', $direction);
        } else {
            // Sort by fields directly on the Donation entity (id, dateDon, description)
            $qb->orderBy('d.' . $sort, $direction);
        }

        return $qb->getQuery()->getResult();
    }
}
