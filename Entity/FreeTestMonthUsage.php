<?php

namespace FormaLibre\InvoiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="formalibre__free_test_month_usage")
 * @ORM\Entity()
 */
class FreeTestMonthUsage
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
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     */
    private $user;

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}
