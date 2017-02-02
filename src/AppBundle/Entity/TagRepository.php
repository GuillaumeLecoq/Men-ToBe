<?php

namespace AppBundle\Entity;

/**
 * FixTagRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TagRepository extends \Doctrine\ORM\EntityRepository
{
    public function getTotalTag()
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('COUNT(t)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getTag($where, $order = null, $limit = null, $offset = null)
    {
        return $tag = $this->findBy(
                $where,
                $order,
                $limit,
                $offset
            );
    }
}