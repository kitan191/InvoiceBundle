<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Entity\User;
use FormaLibre\InvoiceBundle\Form\SharedWorkspaceForm;
use FormaLibre\InvoiceBundle\Entity\Product;
use FormaLibre\InvoiceBundle\Entity\Order;
use FormaLibre\InvoiceBundle\Entity\PriceSolution;
use FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\Payment\CoreBundle\PluginController\Result;
use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use FormaLibre\InvoiceBundle\Manager\Exception\PaymentHandlingFailedException;
use JMS\Payment\CoreBundle\Model\PaymentInterface;

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

    /** @DI\Inject("formalibre.manager.product_manager") */
    private $productManager;

    /** @DI\Inject("formalibre.manager.payment_manager") */
    private $paymentManager;

    /** @DI\Inject("formalibre.manager.vat_manager") */
    private $vatManager;

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
        //it would be better if I was able to avoid creating a new order everytime...
        $order = new Order();
        $this->em->persist($order);
        $this->em->flush();
        $products = $this->get('formalibre.manager.product_manager')->getProductsByType('SHARED_WS');
        $forms = array();

        foreach ($products as $product) {
            //now we generate the forms !
            $form = $this->createForm(new SharedWorkspaceForm($product, $this->router, $this->em, $this->translator, $order, $this->vatManager));
            $forms[] = array(
                'form' => $form->createView(),
                'product' => $product,
                'order' => $order
            );
        }

        return array('forms' => $forms);
    }

    /**
     * @EXT\Route(
     *      "/products/form/iframe",
     *      name="workspace_products_form_iframe"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function iframeFormsAction()
    {
        return $this->formsAction();
    }

    /**
     * @EXT\Route(
     *      "/payment/workspace/submit/{product}/Order/{order}/{swsId}",
     *      name="workspace_product_payment_submit",
     *      defaults={"swsId" = 0}
     * )
     *
     * @param $swsId the sharedWorkspaceId if it already exists (otherwise, if it's 0, we'll create a new one)
     * @return Response
     */
    public function submitWorkspaceAction(Product $product, Order $order, $swsId)
    {
        if ($this->session->has('form_payment_data')) {
            $instruction = $this->session->get('form_payment_data');
            $priceSolution = $this->session->get('form_price_data');
            $this->session->remove('form_payment_data');
            $this->session->remove('form_price_data');
        }

        $form = $this->createForm(new SharedWorkspaceForm($product, $this->router, $this->em, $this->translator, $order, $this->vatManager, $swsId));
        $form->handleRequest($this->request);

        if ($form->isValid()) {
                //do that stuff here
            if (!$this->sc->isGranted('ROLE_USER')) {
                $this->session->set('form_payment_data', $form->get('payment')->getData());
                $this->session->set('form_price_data', $form->get('price')->getData());
                $redirectRoute =  $this->router->generate('workspace_product_payment_submit', array(
                    'order' => $order->getId(),
                    'product' => $product->getId(),
                    'swsId' => $swsId
                ));
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
            $order->setProduct($product);
            $order->setOwner($this->sc->getToken()->getUser());
            $this->ppc->createPaymentInstruction($instruction);
            $order->setPaymentInstruction($instruction);
            $order->setPriceSolution($priceSolution);
            $this->em->persist($order);
            $this->em->flush($order);
            $extData = $instruction->getExtendedData();

            return new RedirectResponse($extData->get('return_url'));

        } else {
            throw new \Exception('Shared workspace invoice data not found');
        }

        throw new \Exception('Errors were found: ' . $form->getErrorsAsString());
    }

    /**
     * @EXT\Route(
     *      "/payment_complete/workspace/{order}/{swsId}",
     *      name="workspace_product_payment_complete",
     *      defaults={"swsId" = 0}
     * )

     * @param $swsId the sharedWorkspaceId if it already exists (otherwise, if it's 0, we'll create a new one)
     * @return Response
     */
    public function completePaymentAction(Order $order, $swsId)
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

        $this->productManager->endOrder($order);
        /*
        $payment->setState(PaymentInterface::STATE_APPROVED);
        $this->em->persist($payment);
        $this->em->flush();
        */

        try {
            if ($swsId == 0) {
                $this->addRemoteWorkspace($order);
            } else {
                $sws = $this->em->getRepository("FormaLibreInvoiceBundle:Product\SharedWorkspace")->find($swsId);
                $this->productManager->addRemoteWorkspaceExpDate($order, $sws);
            }
        } catch (PaymentHandlingFailedException $e) {

            $content = $this->renderView(
                'FormaLibreInvoiceBundle:errors:paymentHandlingFailedException.html.twig'
            );

            return new Response($content);
        }

        if ($this->sc->isGranted('ROLE_ADMIN')) {
            return new RedirectResponse($this->router->generate('admin_invoice_open', array()));
        }

        return new RedirectResponse($this->router->generate('invoice_show_all', array()));
    }


    /**
     * @EXT\Route(
     *      "/payment_pending/workspace/{order}",
     *      name="workspace_product_payment_pending"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function pendingPaymentAction(Order $order)
    {
        $instruction = $order->getPaymentInstruction();
        $extra = $instruction->getExtendedData();

        return array('communication' => $extra->get('communication'));
    }

    /**
     * @EXT\Route(
     *      "/shared/workspace/{sws}",
     *      name="shared_workspace_expiration_increase_form"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function increaseExpirationDateFormAction(SharedWorkspace $sws)
    {
        $order = new Order();
        $this->em->persist($order);
        $this->em->flush();
        $product = $sws->getProduct();
        $formType = new SharedWorkspaceForm(
            $product,
            $this->router,
            $this->em,
            $this->translator,
            $order,
            $this->vatManager,
            $sws->getId()
        );

        $form = $this->createForm($formType)->createView();
        $workspace = $this->productManager->getWorkspaceData($sws);

        return array('form' => $form, 'product' => $product, 'order' => $order, 'sws' => $sws, 'workspace' => $workspace);
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
        return new RedirectResponse($this->router->generate('claro_desktop_open', array()));
    }

    private function addRemoteWorkspace(Order $order)
    {
        $user = $order->getOwner();
        $sws = $this->productManager->addSharedWorkspace($user, $order);
        $this->productManager->createRemoteSharedWorkspace($sws, $user);
    }

    /**
     * @EXT\Route(
     *      "/bank_transfer_validate/{payment}",
     *      name="formalibre_validate_bank_transfer"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function validateBankTransferAction(Payment $payment)
    {
        /* do some stuff
        $this->em->persist($payment);
        $this->em->flush();
        $order = $this->paymentManager->getOrderFromPayment($payment);
        */

        return new RedirectResponse($this->router->generate('workspace_product_payment_complete', array('order' => $order->getId())));
    }
}
