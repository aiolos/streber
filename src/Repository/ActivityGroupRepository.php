<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class ActivityGroupRepository extends EntityRepository
{
    public function findAll()
    {
        return $this->findBy([], ['date' => 'DESC']);
    }
}
