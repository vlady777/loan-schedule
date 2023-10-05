<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Euribor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Euribor>
 *
 * @method Euribor|null find($id, $lockMode = null, $lockVersion = null)
 * @method Euribor|null findOneBy(array $criteria, array $orderBy = null)
 * @method Euribor[]    findAll()
 * @method Euribor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EuriborRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Euribor::class);
    }
}
