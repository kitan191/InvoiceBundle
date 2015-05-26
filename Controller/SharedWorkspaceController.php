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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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

    /** @DI\Inject("security.token_storage") */
    private $tokenStorage;

    /** @DI\Inject("security.authorization_checker") */
    private $authorization;

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
        $user = $this->tokenStorage->getToken()->getUser();
        $hasFreeTest = true;

        //it would be better if I was able to avoid creating a new order everytime...
        if ($user !== 'anon.' && !$this->productManager->hasFreeTestMonth($user)) {
            $hasFreeTest = false;
        }

        $order = new Order();

        if ($user !== 'anon.') {
            $order->setOwner($user);
        }

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

        return array('forms' => $forms, 'hasFreeTest' => $hasFreeTest);
    }

    /**
     * @EXT\Template
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
        $sws = $this->em->getRepository('FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace')
            ->findOneByRemoteId($swsId);

        if ($order->getPaymentInstruction()) {
            $content = $this->renderView(
                'FormaLibreInvoiceBundle:errors:orderAlreadySubmitedException.html.twig'
            );

            return new Response($content);
        }

        if ($this->session->has('form_payment_data')) {
            $instruction = $this->session->get('form_payment_data');
            $priceSolution = $this->session->get('form_price_data');
            $this->session->remove('form_payment_data');
            $this->session->remove('form_price_data');
        }

        $form = $this->createForm(new SharedWorkspaceForm(
            $product,
            $this->router,
            $this->em,
            $this->translator,
            $order,
            $this->vatManager,
            $swsId
        ));
        $form->handleRequest($this->request);

        if ($form->isValid()) {
                //do that stuff here
            if (!$this->authorization->isGranted('ROLE_USER')) {
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
            if ($this->productManager->hasFreeTestMonth($order->getOwner())) $order->setHasDiscount(true);
            $order->setOwner($this->tokenStorage->getToken()->getUser());
            $this->ppc->createPaymentInstruction($instruction);
            $order->setPaymentInstruction($instruction);
            $order->setPriceSolution($priceSolution);
            $order->setAmount($instruction->getAmount());
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
        $sws = $this->em->getRepository('FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace')
            ->findOneByRemoteId($swsId);

        if (
            $order->getOwner() !== $this->tokenStorage->getToken()->getUser()
            && $this->authorization->isGranted('ROLE_ADMIN') === false
        ) {
            throw new AccessDeniedException();
        }

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

            $content = $this->renderView(
                'FormaLibreInvoiceBundle:errors:paymentPendingException.html.twig'
            );

            return new Response($content);

        } else if (Result::STATUS_SUCCESS !== $result->getStatus()) {
            throw new \RuntimeException('Transaction was not successful: '. $result->getReasonCode());
        }

        try {
            $duration = $order->hasDiscount() ?
                $order->getPriceSolution()->getMonthDuration() + $this->container->get('claroline.config.platform_config_handler')->getParameter('formalibre_test_month_duration'):
                $order->getPriceSolution()->getMonthDuration();

            $this->productManager->executeWorkspaceOrder(
                $order,
                $duration,
                $sws
            );
        } catch (PaymentHandlingFailedException $e) {
            $content = $this->renderView(
                'FormaLibreInvoiceBundle:errors:paymentHandlingFailedException.html.twig'
            );

            return new Response($content);
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
        if ($order->getOwner() !== $this->tokenStorage->getToken()->getUser()) {
            throw new AccessDeniedException();
        }

        $instruction = $order->getPaymentInstruction();
        $extra = $instruction->getExtendedData();
        $order->setExtendedData(
            array(
                'communication' => $extra->get('communication'),
                'shared_workspace_id' => $extra->get('shared_workspace_id')
            )
        );
        $this->em->persist($order);
        $this->em->flush();
        $this->productManager->sendBankTransferPendingMail($order);
        $freeMonthAmount = $order->hasDiscount() ? $this->container->get('claroline.config.platform_config_handler')->getParameter('formalibre_test_month_duration'): 0;
        if ($order->hasDiscount()) $this->productManager->useFreeTestMonth($order->getOwner());

        return array(
            'communication' => $extra->get('communication'),
            'order' => $order,
            'freeMonthAmount' => $freeMonthAmount,
            'hasFreeMonth' => $order->hasDiscount()
        );
    }

    /**
     * @EXT\Route(
     *      "/renew/test/workspace/{remoteId}",
     *      name="renew_test_workspace"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function renewWorkspaceAction($remoteId)
    {
        $sws = $this->em->getRepository('FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace')
            ->findOneByRemoteId($remoteId);

        if (!$sws) {
            throw new \Exception('unknown remote id');
        }

        if ($this->tokenStorage->getToken()->getUser() !== $sws->getOwner()) {
            throw new \AccessDeniedException();
        }

        $order = new Order();
        $order->setOwner($this->tokenStorage->getToken()->getUser());
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

    /**
     * @EXT\Route(
     *      "/bank_transfer_validate/{payment}",
     *      name="formalibre_validate_bank_transfer",
     *      defaults={"swsId" = 0}
     * )
     * @EXT\Template
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function validateBankTransferAction(Payment $payment)
    {
        //the admin is the only one able to do this.
        if (!$this->authorization->isGranted('ROLE_ADMIN')) {
            throw new \AccessDeniedException();
        }

        $order = $this->paymentManager->getOrderFromPayment($payment);
        $extra = $order->getExtendedData();
        $sws = $sws = $this->em->getRepository('FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace')
            ->find($extra['shared_workspace_id']);
        $this->ppc->approve($payment, $order->getPaymentInstruction()->getAmount());
        $duration = $order->hasDiscount() ?
            $order->getPriceSolution()->getMonthDuration() + $this->container->get('claroline.config.platform_config_handler')->getParameter('formalibre_test_month_duration'):
            $order->getPriceSolution()->getMonthDuration();
        $this->productManager->executeWorkspaceOrder(
            $order,
            $duration,
            $sws
        );
        $route = $this->router->generate('admin_invoice_open');

        return new RedirectResponse($route);
    }

    /**
     * @EXT\Route(
     *      "/free_test/{product}",
     *      name="formalibre_free_test_workspace"
     * )
     * @return Response
     */
    public function createFreeTestWorkspace(Product $product)
    {
        if (!$this->authorization->isGranted('ROLE_USER')) {
            $redirectRoute =  $this->router->generate(
                'formalibre_free_test_workspace',
                array('product' => $product->getId())
            );
            $this->session->set('redirect_route', $redirectRoute);
            $route = $this->router->generate('claro_security_login', array());

            return new RedirectResponse($route);
        }

        $user = $this->tokenStorage->getToken()->getUser();

        if (!$this->productManager->hasFreeTestMonth($user)) {
            $content = $this->renderView(
                'FormaLibreInvoiceBundle:errors:freeTestMonthUsedException.html.twig'
            );

            return new Response($content);;
        }

        $order = new Order();
        $order->setOwner($user);
        $order->setProduct($product);

        $this->productManager->executeWorkspaceOrder(
            $order,
            $this->container->get('claroline.config.platform_config_handler')->getParameter('formalibre_test_month_duration'),
            null,
            true
        );

        return new RedirectResponse($this->router->generate('claro_desktop_open', array()));
    }
}
