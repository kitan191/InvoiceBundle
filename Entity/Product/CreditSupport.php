<?php

namespace FormaLibre\InvoiceBundle\Entity\Product;

use Doctrine\ORM\Mapping as ORM;
use Claroline\CoreBundle\Entity\User;

class CreditSupport
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** ORM\Column(type="integer", nullable=false) */
    private $creditAmount;

    /** ORM\Column(type="integer", nullable=false) */
    private $creditUsed = 0;

    /**
     * @ORM\OneToMany(
     *     targetEntity="FormaLibre\InvoiceBundle\Entity\Order",
     *     mappedBy="sharedWorkspace"
     * )
     **/
    private $orders;

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

    public function addCreditUsed()
    {
        $this->creditUsed++;
    }

    public function getCreditUsed()
    {
        return $this->creditUsed;
    }

    public function getOrders()
    {
        return $this->orders;
    }

    public function setOwner(User $owner)
    {
        $this->owner = $owner;
    }
}
