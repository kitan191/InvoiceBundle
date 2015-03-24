<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;
use FormaLibre\InvoiceBundle\Form\SharedWorkspaceForm;
use FormaLibre\InvoiceBundle\Entity\Product;
use FormaLibre\InvoiceBundle\Entity\Order;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\Payment\CoreBundle\PluginController\Result;
use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;

class SharedWorkspaceController extends Controller
{
    /** @DI\Inject */
    private $request;

    /** @DI\Inject */
    private $router;

    /** @DI\Inject("doctrine.orm.entity_manager") */
    private $em;

    /** @DI\Inject("payment.plugin_controller") */
    private $ppc;

    /** @DI\Inject("translator") */
    private $translator;

    /** @DI\Inject("security.context") */
    private $sc;

    /** @DI\Inject("session") */
    private $session;

    /**
     * @EXT\Route(
     *      "/products/form",
     *      name="workspace_products_form"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function formsAction()
    {
        $products = $this->get('formalibre.manager.product_manager')->getProductsByType('SHARED_WS');
        $forms = array();

        foreach ($products as $product) {
            //now we generate the forms !
            $form = $this->createForm(new SharedWorkspaceForm($product, $this->router, $this->em, $this->translator));
            $forms[] = array(
                'form' => $form->createView(),
                'product' => $product
            );
        }

        return array('forms' => $forms);
    }

    /**
     * @EXT\Route(
     *      "/payment/workspace/submit/{product}",
     *      name="workspace_product_payment_submit"
     * )
     *
     * @return Response
     */
    public function submitWorkspaceAction(Product $product)
    {
        if ($this->session->has('form_payment_data')) {
            $instruction = $this->session->get('form_payment_data');
            $priceSolution = $this->session->get('form_price_data');
            $this->session->remove('form_payment_data');
            $this->session->remove('form_price_data');
        }

        $form = $this->createForm(new SharedWorkspaceForm($product, $this->router, $this->em, $this->translator));
        $form->handleRequest($this->request);

        if ($form->isValid()) {
                //do that stuff here
            if (!$this->sc->isGranted('ROLE_USER')) {
                $this->session->set('form_payment_data', $form->get('payment')->getData());
                $this->session->set('form_price_data', $form->get('price')->getData());
                $redirectRoute =  $this->router->generate('workspace_product_payment_submit', array('product' => $product->getId()));
                $this->session->set('redirect_route', $redirectRoute);
                $route = $this->router->generate('claro_security_login', array());

                return new RedirectResponse($route);
            }

            $instruction = $form->get('payment')->getData();
            $priceSolution = $form->get('price')->getData();
        }

        if ($instruction && $priceSolution) {
            //refresh
            $priceSolution = $this->em->getRepository('FormaLibreInvoiceBundle:PriceSolution')->find($priceSolution->getId());
            $order = new Order();
            $order->setProduct($product);
            $this->ppc->createPaymentInstruction($instruction);
            $order->setPaymentInstruction($instruction);
            $order->setPriceSolution($priceSolution);
            $this->em->persist($order);
            $this->em->flush($order);

            return new RedirectResponse($this->router->generate('workspace_product_payment_complete', array(
                'order' => $order->getId(),
            )));
        } else {
            throw new \Exception('Shared workspace invoice data not found');
        }

        throw new \Exception('Errors were found: ' . $form->getErrorsAsString());
    }

    /**
     * @EXT\Route(
     *      "/payment_complete/workspace/{order}",
     *      name="workspace_product_payment_complete"
     * )
     *
     * @return Response
     */
    public function completePaymentAction(Order $order)
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
    }

    /**
     * @EXT\Route(
     *      "/payment_cancel",
     *      name="workspace_product_payment_cancel"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function cancelAction(Order $order)
    {

    }
}
