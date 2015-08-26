<?php

namespace FormaLibre\InvoiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Claroline\CoreBundle\Entity\User;

/**
 * @ORM\Table(name="formalibre__partner")
 * @ORM\Entity()
 * @DoctrineAssert\UniqueEntity("code")
 */
class Partner
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(unique=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string")
     */
    private $code;


    /**
     * @ORM\ManyToMany(
     *     targetEntity="Claroline\CoreBundle\Entity\User",
     *     cascade={"persist"}
     * )
     */
    private $users;
    
    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $isActivated = true;
    
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setCode($code)
    {
        $this->code = $code;
    }
    
    public function getCode()
    {
        return $this->code;
    }
    
    public function getUsers()
    {
        return $this->users;
    }
    
    public function addUser(User $user)
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }
    }

    public function removeUser(User $user)
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
        }
    }
    
    public function setIsActivated($bool)
    {
        $this->isActivated = $bool;
    }
    
    public function isActivated()
    {
        return $this->isActivated;
    }
}
