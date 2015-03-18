<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;
use FormaLibre\InvoiceBundle\Entity\Order;
use JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\PluginController\Result;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;

class InvoiceController extends Controller
{
    /** @DI\Inject */
    private $request;

    /** @DI\Inject */
    private $router;

    /** @DI\Inject("doctrine.orm.entity_manager") */
    private $em;

    /** @DI\Inject("payment.plugin_controller") */
    private $ppc;

    /** @DI\Inject("formalibre.manager.invoice_manager") */
    private $invoiceManager;

    /**
     * @EXT\Route(
     *      "/payment/workspace/form",
     *      name="workspace_payment_form"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function formWorkspaceAction()
    {
        $amount = 50;
        $order = new Order($amount, uniqid());
        $this->em->persist($order);
        $this->em->flush($order);

        $form = $this->createForm('jms_choose_payment_method', null, array(
            'amount'   => $order->getAmount(),
            'currency' => 'EUR',
            'default_method' => 'payment_paypal', // Optional
            'predefined_data' => array(
                'paypal_express_checkout' => array(
                    'return_url' => $this->router->generate('workspace_payment_complete', array(
                        'order' => $order->getId(),
                    ), true),
                    'cancel_url' => $this->router->generate('workspace_payment_cancel', array(
                        'order' => $order->getId(),
                    ), true),
                    'checkout_params' => array(
                        'L_PAYMENTREQUEST_0_DESC0' => 'some event that the user is trying to buy',
                        'L_PAYMENTREQUEST_0_QTY0' => '1',
                        'L_PAYMENTREQUEST_0_AMT0'=> $order->getAmount(), // if you get 10413 , then visit the api errors documentation , this number should be the total amount (usually the same as the price )
                        //'L_PAYMENTREQUEST_0_ITEMCATEGORY0'=> 'Digital'
                    )
                ),
            ),
        ));

        return array('form' => $form->createView(), 'order' => $order);
    }

    /**
     * @EXT\Route(
     *      "/payment/workspace/submit/{order}",
     *      name="workspace_payment_submit"
     * )
     *
     * @return Response
     */
    public function submitWorkspaceAction(Order $order)
    {
        $form = $this->createForm('jms_choose_payment_method', null, array(
            'amount'   => $order->getAmount(),
            'currency' => 'EUR',
            'default_method' => 'payment_paypal', // Optional
            'predefined_data' => array(
                'paypal_express_checkout' => array(
                    'return_url' => $this->router->generate('workspace_payment_complete', array(
                        'order' => $order->getId(),
                    ), true),
                    'cancel_url' => $this->router->generate('workspace_payment_cancel', array(
                        'order' => $order->getId(),
                    ), true),
                    'checkout_params' => array(
                        'L_PAYMENTREQUEST_0_DESC0' => 'some event that the user is trying to buy',
                        'L_PAYMENTREQUEST_0_QTY0' => '1',
                        'L_PAYMENTREQUEST_0_AMT0'=> $order->getAmount(), // if you get 10413 , then visit the api errors documentation , this number should be the total amount (usually the same as the price )
                        //'L_PAYMENTREQUEST_0_ITEMCATEGORY0'=> 'Digital'
                    )
                ),
            ),
        ));

        $form->handleRequest($this->request);

        if ($form->isValid()) {
            $instruction = $form->getData();
            $this->ppc->createPaymentInstruction($instruction);
            $order->setPaymentInstruction($instruction);
            $this->em->persist($order);
            $this->em->flush($order);

            return new RedirectResponse($this->router->generate('workspace_payment_complete', array(
                'order' => $order->getId(),
            )));
        }

        return $this->render(
            'FormaLibreInvoiceBundle:Invoice:formWorkspace.html.twig',
            array('form' => $form->createView(), 'order' => $order)
        );
    }

    /**
     * @EXT\Route(
     *      "/payment_complete/{order}",
     *      name="workspace_payment_complete"
     * )
     *
     * @return Response
     */
    public function submitAction(Order $order)
    {
        $instruction = $order->getPaymentInstruction();

        if (null === $pendingTransaction = $instruction->getPendingTransaction()) {
            $payment = $this->ppc->createPayment(
                $instruction->getId(),
                $instruction->getAmount() - $instruction->getDepositedAmount()
            );
        } else {
            $payment = $pendingTransaction->getPayment();
        }

        $result = $this->ppc->approveAndDeposit($payment->getId(), $payment->getTargetAmount());
        if (Result::STATUS_PENDING === $result->getStatus()) {
            $ex = $result->getPluginException();
            if ($ex instanceof ActionRequiredException) {
                $action = $ex->getAction();
                if ($action instanceof VisitUrl) {
                    return new RedirectResponse($action->getUrl());
                }
                throw $ex;
            }
        } else if (Result::STATUS_SUCCESS !== $result->getStatus()) {
            throw new \RuntimeException('Transaction was not successful: '. $result->getReasonCode());
        }

        //$this->invoiceManager->
        return new Response ('it worked man !');
    }

    /**
     * @EXT\Route(
     *      "/payment_cancel",
     *      name="workspace_payment_cancel"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function cancelAction(Order $order)
    {

    }

    /** @DI\LookupMethod("form.factory") */
    protected function getFormFactory() { }
}
