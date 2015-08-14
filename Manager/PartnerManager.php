<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Persistence\ObjectManager;
use FormaLibre\InvoiceBundle\Entity\Partner;

/**
* @DI\Service("formalibre.manager.partner_manager")
*/
class PartnerManager
{
    private $em;

    /**
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct(
        $em
    ) {
        $this->em = $em;
        $this->partnerRepository = $em->getRepository('FormaLibre\InvoiceBundle\Entity\Partner');
    }
    
    public function findAll()
    {
        return $this->partnerRepository->findAll();
    }
    
    public function create(Partner $partner)
    {
        $this->em->persist($partner);
        $this->em->flush();
        
        return $partner;
    }
    
    public function activatePartner(Partner $partner, $boolActivated)
    {
        $partner->setIsActivated($boolActivated);
        $this->em->persist($partner);
        $this->em->flush();
    }
    
    public function getCharts(Partner $partner, $getQuery = false)
    {
        $users = $partner->getUsers();
        
        $dql = "
            SELECT c FROM FormaLibre\InvoiceBundle\Entity\Chart c
            JOIN c.owner u
            WHERE u.id IN (:users)
        ";

        $query = $this->em->createQuery($dql);
        $query->setParameter('users', $users);

        return ($getQuery) ? $query: $query->getResult();
    }
}
