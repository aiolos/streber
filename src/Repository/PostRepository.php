<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\ORM\EntityRepository;

class PostRepository extends EntityRepository
{
    /**
     * @param Post $post
     * @return Post
     */
    public function findNextPost($post)
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.date > :date')
            ->andWhere('p.status = :status')
            ->setParameter('date', $post->getDate())
            ->setParameter('status', Post::STATUS_PUBLISHED)
            ->orderBy('p.date', 'ASC')
            ->getQuery();

        return $qb->setMaxResults(1)->getOneOrNullResult();
    }
    /**
     * @param Post $post
     * @return Post
     */
    public function findPreviousPost($post)
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.date < :date')
            ->andWhere('p.status = :status')
            ->setParameter('date', $post->getDate())
            ->setParameter('status', Post::STATUS_PUBLISHED)
            ->orderBy('p.date', 'DESC')
            ->getQuery();

        return $qb->setMaxResults(1)->getOneOrNullResult();
    }
}
