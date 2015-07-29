<?php

namespace FormaLibre\InvoiceBundle\Manager;

use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Persistence\ObjectManager;
use FormaLibre\InvoiceBundle\Entity\Product\CreditSupport;
use JMS\DiExtraBundle\Annotation as DI;

/**
* @DI\Service("formalibre.manager.credit_support_manager")
*/
class CreditSupportManager
{
    private $om;
    private $creditRepo;

    /**
     * @DI\InjectParams({
     *     "om" = @DI\Inject("claroline.persistence.object_manager")
     * })
     */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
        $this->creditRepo =
            $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Product\CreditSupport');
    }

    public function persistCreditSupport(CreditSupport $creditSupport)
    {
        $this->om->persist($creditSupport);
        $this->om->flush();
    }

    public function addCreditsToUser(User $user, $nbCredits)
    {
        $creditSupport = $this->getCreditSupportByUser($user);

        if (is_null($creditSupport)) {
            $creditSupport = new CreditSupport();
            $creditSupport->setOwner($user);
        }
        $creditSupport->addCredits($nbCredits);
        $this->persistCreditSupport($creditSupport);
    }

    public function removeCreditsFromUser(User $user, $nbCredits)
    {
        $creditSupport = $this->getCreditSupportByUser($user);

        if (is_null($creditSupport)) {
            $creditSupport = new CreditSupport();
            $creditSupport->setOwner($user);
        }
        $creditSupport->removeCredits($nbCredits);
        $this->persistCreditSupport($creditSupport);
    }

    public function useCredits (User $user, $nbCredits)
    {
        $creditSupport = $this->getCreditSupportByUser($user);

        if (is_null($creditSupport)) {
            $creditSupport = new CreditSupport();
            $creditSupport->setOwner($user);
        }
        $creditSupport->useCredits($nbCredits);
        $this->persistCreditSupport($creditSupport);
    }

    public function getNbRemainingCredits(User $user)
    {
        $creditSupport = $this->getCreditSupportByUser($user);

        return is_null($creditSupport) ? 0 : $creditSupport->getCreditAmount();
    }


    /*********************************************
     * Access to CreditSupportRepository methods *
     *********************************************/

    public function getCreditSupportByUser(User $user)
    {
        return $this->creditRepo->findCreditSupportByUser($user);
    }
}
