<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FormaLibre\InvoiceBundle\Repository;

use Doctrine\ORM\EntityRepository;

class OrderRepository extends EntityRepository
{
    public function getPayedOrders($getQuery = true)
    {
        $dql = "SELECT o from FormaLibre\InvoiceBundle\Entity\Order o
            WHERE o.product is not NULL";

        $query = $this->_em->createQuery($dql);

        return $getQuery ? $query: $query->getResult();
    }
}
