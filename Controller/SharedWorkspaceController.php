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
        $order = new Order();
        $this->em->persist($order);
        $this->em->flush();
        $products = $this->get('formalibre.manager.product_manager')->getProductsByType('SHARED_WS');
        $forms = array();

        foreach ($products as $product) {
            //now we generate the forms !
            $form = $this->createForm(new SharedWorkspaceForm($product, $this->router, $this->em, $this->translator, $order));
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

        $form = $this->createForm(new SharedWorkspaceForm($product, $this->router, $this->em, $this->translator, $order, $swsId));
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
            $this->ppc->createPaymentInstruction($instruction);
            $order->setPaymentInstruction($instruction);
            $order->setPriceSolution($priceSolution);
            $this->em->persist($order);
            $this->em->flush($order);

            return new RedirectResponse($this->router->generate('workspace_product_payment_complete', array(
                'order' => $order->getId(),
                'swsId' => $swsId
            )));
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

        if ($swsId == 0) {
            $this->addRemoteWorkspace($this->sc->getToken()->getUser(), $order);
        } else {
            $sws = $this->em->getRepository("FormaLibreInvoiceBundle:Product\SharedWorkspace")->find($swsId);
            $this->productManager->addRemoteWorkspaceExpDate($order, $sws);
        }

        return new RedirectResponse($this->router->generate('invoice_show_all', array()));
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

    private function addRemoteWorkspace(
        User $user,
        Order $order
    )
    {
        $sws = $this->productManager->addSharedWorkspace($user, $order);
        $this->productManager->createRemoteSharedWorkspace($sws, $user);
    }
}
