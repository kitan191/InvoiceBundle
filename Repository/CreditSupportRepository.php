<?php

namespace FormaLibre\InvoiceBundle\Repository;

use Claroline\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

class CreditSupportRepository extends EntityRepository
{
    public function findCreditSupportByUser(User $user)
    {
        $dql = '
            SELECT c
            FROM FormaLibre\InvoiceBundle\Entity\Product\CreditSupport c
            WHERE c.owner = :user
        ';
        $query = $this->_em->createQuery($dql);
        $query->setParameter('user', $user);

        return $query->getOneOrNullResult();
    }
}
