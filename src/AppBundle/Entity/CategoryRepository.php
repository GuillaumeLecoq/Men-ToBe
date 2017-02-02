<?php
/**
 * Created by PhpStorm.
 * User: pira
 * Date: 21/04/2016
 * Time: 10:05
 */

namespace AppBundle\Entity;

class CategoryRepository extends \Doctrine\ORM\EntityRepository
{
    /** Function return a list of category */
    public function getCategory($where, $order = null, $limit = null, $offset = null) {
        return $result = $this->findBy(
            $where,
            $order,
            $limit,
            $offset
        );
    }
}