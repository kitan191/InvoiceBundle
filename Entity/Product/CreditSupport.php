<?php

namespace FormaLibre\InvoiceBundle\Entity\Product;

use Doctrine\ORM\Mapping as ORM;
use Claroline\CoreBundle\Entity\User;

/**
 * @ORM\Table(name="formalibre__credit_support")
 * @ORM\Entity(repositoryClass="FormaLibre\InvoiceBundle\Repository\CreditSupportRepository")
 */
class CreditSupport
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="credit_amount", type="integer", nullable=false)
     */
    private $creditAmount = 0;

    /**
     * @ORM\Column(name="credit_used", type="integer", nullable=false)
     */
    private $creditUsed = 0;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\User",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     */
    protected $owner;

    public function getId()
    {
        return $this->id;
    }

    public function setCreditAmount($creditAmount)
    {
        $this->creditAmount = $creditAmount;
    }

    public function getCreditAmount()
    {
        return $this->creditAmount;
    }

    public function addCredits($nbCredits)
    {
        $this->creditAmount += $nbCredits;
    }

    public function removeCredits($nbCredits)
    {
        $this->creditAmount -= $nbCredits;
    }

    public function setCreditUsed($creditUsed)
    {
        $this->creditUsed = $creditUsed;
    }

    public function getCreditUsed()
    {
        return $this->creditUsed;
    }

    public function useCredits($nbCredits)
    {
        $this->creditUsed += $nbCredits;
        $this->creditAmount -= $nbCredits;
    }

    public function setOwner(User $owner)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }
}
