<?php

namespace App\Repository;

use App\Entity\ActivityMap;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ActivityMap|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActivityMap|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActivityMap[]    findAll()
 * @method ActivityMap[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityMapRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ActivityMap::class);
    }

    // /**
    //  * @return ActivitiesMap[] Returns an array of ActivitiesMap objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ActivitiesMap
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
