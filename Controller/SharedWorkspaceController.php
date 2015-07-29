<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Entity\User;
use FormaLibre\InvoiceBundle\Form\SharedWorkspaceForm;
use FormaLibre\InvoiceBundle\Entity\Product;
use FormaLibre\InvoiceBundle\Entity\Order;
use FormaLibre\InvoiceBundle\Entity\Chart;
use FormaLibre\InvoiceBundle\Entity\PriceSolution;
use FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\Payment\CoreBundle\Entity\Payment;
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

    /** @DI\Inject("formalibre.manager.invoice_manager") */
    private $invoiceManager;

    /** @DI\Inject("formalibre.manager.shared_workspace_manager") */
    private $sharedWorkspaceManager;

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
        if ($user !== 'anon.' && !$this->sharedWorkspaceManager->hasFreeTestMonth($user)) {
            $hasFreeTest = false;
        }

        $order = new Order();
        $chart = new Chart();
        $order->setChart($chart);
        $this->em->persist($chart);
        $this->em->persist($order);
        $this->em->flush();
        $products = $this->get('formalibre.manager.product_manager')
            ->getProductsBy(array('type' => 'SHARED_WS', 'isActivated' => true));
        $forms = array();

        foreach ($products as $product) {
            //now we generate the forms !
            $form = $this->createForm(
                new SharedWorkspaceForm(
                    $product,
                    $this->router,
                    $this->em,
                    $this->translator,
                    $order,
                    $this->vatManager
                )
            );
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
     *      "/payment/workspace/submit/{product}/Order/{order}/chart/{chart}",
     *      name="workspace_product_payment_submit"
     * )
     *
     * @param $swsId the sharedWorkspaceId if it already exists (otherwise, if it's 0, we'll create a new one)
     * @param $chartId the chartId if it already exists (otherwise, if it's 0, we'll create a new one)
     * @return Response
     */
    public function addOrderToChartAction(Product $product, Order $order, Chart $chart)
    {
        if ($chart->getPaymentInstruction()) {
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
            $this->vatManager
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
                    'chart' => $chart->getId()
                ));
                $this->session->set('redirect_route', $redirectRoute);
                $route = $this->router->generate('claro_security_login', array());

                return new RedirectResponse($route);
            }

            $instruction = $form->get('payment')->getData();
            $priceSolution = $form->get('price')->getData();
        }

        if ($instruction && $priceSolution) {
            $priceSolution = $this->em->getRepository('FormaLibreInvoiceBundle:PriceSolution')->find($priceSolution->getId());
            $order->setProduct($product);
            $chart->setOwner($this->tokenStorage->getToken()->getUser());
            $this->ppc->createPaymentInstruction($instruction);
            $chart->setPaymentInstruction($instruction);
            $chart->setIpAdress($_SERVER['REMOTE_ADDR']);
            $order->setPriceSolution($priceSolution);
            $order->setChart($chart);
            $this->em->persist($chart);
            $this->em->persist($order);
            $this->em->flush();
            $extData = $instruction->getExtendedData();

            return new RedirectResponse($extData->get('return_url'));

        } else {
            throw new \Exception('Shared workspace invoice data not found');
        }

        throw new \Exception('Errors were found: ' . $form->getErrorsAsString());
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
            throw new AccessDeniedException();
        }

        $order = new Order();
        $chart = new Chart();
        $order->setChart($chart);
        $order->setSharedWorkspace($sws);
        $product = $this->sharedWorkspaceManager->getLastOrder($sws)->getProduct();
        $order->setProduct($product);
        $this->em->persist($chart);
        $this->em->persist($order);
        $this->em->flush();
        $formType = new SharedWorkspaceForm(
            $product,
            $this->router,
            $this->em,
            $this->translator,
            $order,
            $this->vatManager
        );
        $form = $this->createForm($formType)->createView();
        $workspace = $this->sharedWorkspaceManager->getWorkspaceData($sws);

        return array('form' => $form, 'chart' => $chart, 'product' => $product, 'order' => $order, 'workspace' => $workspace);
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

        if (!$this->sharedWorkspaceManager->hasFreeTestMonth($user)) {
            $content = $this->renderView(
                'FormaLibreInvoiceBundle:errors:freeTestMonthUsedException.html.twig'
            );

            return new Response($content);;
        }

        $chart = new Chart();
        $ps = $this->productManager->getPriceSolution($product, 1);
        $order = new Order();
        $chart->setOwner($user);
        $chart->setIpAdress($_SERVER['REMOTE_ADDR']);
        $order->setPriceSolution($ps);
        $order->setProduct($product);
        $chart->addOrder($order);
        $order->setChart($chart);
        $this->em->persist($order);
        $this->em->persist($chart);
        $this->sharedWorkspaceManager->useFreeTestMonth($user);
        $invoice = $this->invoiceManager->create($chart);
        $this->invoiceManager->validate($invoice);

        return new RedirectResponse($this->router->generate('claro_desktop_open', array()));
    }
}
