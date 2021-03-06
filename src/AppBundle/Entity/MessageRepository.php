<?php

namespace AppBundle\Entity;

/**
 * MessageRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MessageRepository extends \Doctrine\ORM\EntityRepository
{
	public function getTotalMessageByUser($user)
	{
		$qb = $this->createQueryBuilder('m');
		$qb->select('COUNT(m)')
		    ->Where('m.owner = :owner')
		    ->setParameter('owner', $user);

		$total = $qb->getQuery()->getSingleScalarResult();

        return $total;
	}
}
