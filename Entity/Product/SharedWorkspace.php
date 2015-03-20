<?php

namespace FormaLibre\InvoiceBundle\Entity\Product;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="formalibre__shared_workspace")
 * @ORM\Entity()
 */
class SharedWorkspace
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
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $autoSubscribe = false;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="FormaLibre\InvoiceBundle\Entity\Product",
     *     inversedBy="sharedWorkspaces"
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

    public function setAutoSubscribe($bool)
    {
        $this->autoSubscribe = false;
    }

    public function getAutoSubscribe()
    {
        return $this->autoSubscribe;
    }
}
