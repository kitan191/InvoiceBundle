<?php

namespace FormaLibre\InvoiceBundle\Repository;

use Doctrine\ORM\EntityRepository;

class InvoiceRepository extends EntityRepository
{
    public function findInvoicesBySearchedUsers($search)
    {
        $dql = '
            SELECT i
            FROM FormaLibre\InvoiceBundle\Entity\Invoice i
            JOIN i.chart c
            JOIN c.owner o
            WHERE UPPER(o.firstName) LIKE :search
            OR UPPER(o.lastName) LIKE :search
            OR UPPER(o.username) LIKE :search
            OR UPPER(o.mail) LIKE :search
        ';
        $query = $this->_em->createQuery($dql);
        $upperSearch = strtoupper($search);
        $query->setParameter('search', "%{$upperSearch}%");

        return $query->getResult();
    }
}
