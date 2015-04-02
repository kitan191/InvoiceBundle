<?php

namespace FormaLibre\InvoiceBundle\Payment;

use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Model\PaymentInterface;

class BankTransferPlugin extends AbstractPlugin
{
    public function checkPaymentInstruction(PaymentInstructionInterface $instruction)
    {

    }

    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        $data = $transaction->getExtendedData();
        //this should set the status to "pending"
        $actionRequest = new ActionRequiredException('User has not done the bank transfer yet');
        $actionRequest->setFinancialTransaction($transaction);
        $actionRequest->setAction(new VisitUrl($data->get('pending_url')));
        throw $actionRequest;
    }

    public function processes($name)
    {
        return 'bank_transfer' === $name;
    }
}
