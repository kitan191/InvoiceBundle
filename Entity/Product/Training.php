<?php

namespace FormaLibre\InvoiceBundle\Entity\Product;

use Doctrine\ORM\Mapping as ORM;
use Claroline\CoreBundle\Entity\User;

class Training
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    private $expDate;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\User",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     */
    protected $owner;

    /**
     * @ORM\OneToMany(
     *     targetEntity="FormaLibre\InvoiceBundle\Entity\Order",
     *     mappedBy="sharedWorkspace"
     * )
     **/
    private $orders;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setDuration($monthDuration)
    {
        $this->monthDuration = $monthDuration;
    }

    public function getMonthDuration()
    {
        return $this->monthDuration;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setPicturePath($path)
    {
        $this->picturePath = $path;
    }

    public function getPicturePath()
    {
        return $this->picturePath;
    }

    public function getOrders()
    {
        return $this->orders;
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
