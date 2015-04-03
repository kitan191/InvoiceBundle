<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Persistence\ObjectManager;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Entity\Payment;

/**
* @DI\Service("formalibre.manager.payment_manager")
*/
class PaymentManager
{
    private $em;

    /**
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct($em)
    {
        $this->em = $em;
    }

    public function getPendingBankTransfer($getQuery = false)
    {
        //approving state means we didn't got the money yet.
        $approvingState = Payment::STATE_APPROVING;

        $query = $this->em->createQuery("
            SELECT p FROM JMS\Payment\CoreBundle\Entity\Payment p
            JOIN p.paymentInstruction pi
            WHERE pi.paymentSystemName = 'bank_transfer'
            AND p.state = {$approvingState}
        ");

        if ($getQuery) return $query;

        return $query->getResult();
    }

    public function getBankTransferByCommunication($search, $getQuery = false)
    {
        //approving state means we didn't got the money yet.
        $approvingState = Payment::STATE_APPROVING;

        $query = $this->em->createQuery("
            SELECT p FROM JMS\Payment\CoreBundle\Entity\Payment p
            JOIN p.paymentInstruction pi
            WHERE p.state = {$approvingState}
            AND pi in (
                SELECT opi.id FROM FormaLibre\InvoiceBundle\Entity\Order o
                JOIN o.paymentInstruction opi
                WHERE o.extendedData LIKE :search
            )
        ");

        $query->setParameter('search', "%{$search}%");

        if ($getQuery) return $query;

        return $query->getResult();
    }

    public function getOrderFromPayment(Payment $payment)
    {
        $pi = $payment->getPaymentInstruction();

        return $this->em->getRepository('FormaLibre\InvoiceBundle\Entity\Order')
            ->findOneBy(array('paymentInstruction' => $pi));
    }
}
