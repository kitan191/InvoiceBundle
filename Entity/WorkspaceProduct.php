<?php

namespace FormaLibre\InvoiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;

/**
 * @ORM\Table(name="formalibre_workspace_product")
 * @ORM\Entity()
 */
class WorkspaceProduct
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\User",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     */
    protected $owner;


    /**
     * @ORM\Column(name="code", length=256)
     */
    private $code;

    /**
     * @ORM\Column(name="name", length=256)
     */
    private $name;

    /**
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    private $expDate;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private $maxSize;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $maxUser;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $maxRes;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="FormaLibre\InvoiceBundle\Entity\Product"
     * )
     * @ORM\JoinColumn(name="product_id", onDelete="SET NULL")
     */
    private $product;

    public function getId()
    {
        return $this->id;
    }
    
    public function setOwner(User $user)
    {
        $this->owner = $owner;
    }
    
    public function setName($name)
    {
        $this->name = $name;
    }
    
    public function setCode($code)
    {
        $this->code = $code;
    }
    
    public function setExpDate(\DateTime $expDate)
    {
        $this->expDate = $expDate;
    }
    
    public function setMaxSize($maxSize)
    {
        $this->maxSize = $maxSize;
    }
    
    public function setMaxUser($maxUser)
    {
        $this->maxUser = $maxUser;
    }
    
    public function setMaxRes($maxRes)
    {
        $this->maxRes = $maxRes;
    }
}
