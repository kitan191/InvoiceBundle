<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;
use FormaLibre\InvoiceBundle\Manager\Exception\PaymentHandlingFailedException;
use FormaLibre\InvoiceBundle\Entity\Chart;
use FormaLibre\InvoiceBundle\Entity\Invoice;
use FormaLibre\InvoiceBundle\Entity\Order;
use FormaLibre\InvoiceBundle\Entity\Product;
use FormaLibre\InvoiceBundle\Form\SharedWorkspaceForm;
use JMS\Payment\CoreBundle\PluginController\Result;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;

class ChartController extends Controller
{
    /** @DI\Inject("doctrine.orm.entity_manager") */
    private $em;

    /** @DI\Inject("security.token_storage") */
    private $tokenStorage;

    /** @DI\Inject("formalibre.manager.chart_manager") */
    private $chartManager;

    /** @DI\Inject("formalibre.manager.invoice_manager") */
    private $invoiceManager;

    /** @DI\Inject("router") */
    private $router;
    
    /** @DI\Inject("session") */
    private $session;
    
    /** @DI\Inject("request") */
    private $request;
    
    /** @DI\Inject("security.authorization_checker") */
    private $authorization;


    /**
     * @EXT\Route(
     *      "/payment_pending/chart/{chart}",
     *      name="chart_payment_pending"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function pendingPaymentAction(Chart $chart)
    {
        if ($chart->getOwner() !== $this->tokenStorage->getToken()->getUser()) {
            throw new AccessDeniedException();
        }

        $chart->setExtendedData(array('communication' => $this->chartManager->getCommunication()));
        $extData = $chart->getExtendedData();
        $invoice = $this->invoiceManager->create($chart);
        $this->invoiceManager->send($invoice);
        $this->em->persist($chart);
        $this->em->flush();

        return array(
            'communication' => $extData['communication'],
            'chart' => $chart
        );
    }
    
        /**
     * @EXT\Route(
     *      "/payment/workspace/submit/order/{order}/product/{product}",
     *      name="workspace_product_payment_submit"
     * )
     *
     * @return Response
     */
    public function addOrderToChartAction(Order $order, Product $product)
    {
        $chart = $order->getChart();
        
        //check it wasn't already submitted
        if (false) {
            $content = $this->renderView(
                'FormaLibreInvoiceBundle:errors:orderAlreadySubmitedException.html.twig'
            );

            return new Response($content);
        }

        if ($this->session->has('form_price_data')) {
            $priceSolution = $this->session->get('form_price_data');
            $this->session->remove('form_price_data');
            $priceSolution = $this->em->getRepository('FormaLibreInvoiceBundle:PriceSolution')->find($priceSolution->getId());
        }

        $form = $this->createForm(new SharedWorkspaceForm($product));
        $form->handleRequest($this->request);

        if ($form->isValid()) {
                //do that stuff here
            if (!$this->authorization->isGranted('ROLE_USER')) {
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

            $priceSolution = $form->get('price')->getData();
        } 

        $order->setChart($chart);
        $order->setProduct($product);
        $chart->setOwner($this->tokenStorage->getToken()->getUser());
        $chart->setIpAdress($_SERVER['REMOTE_ADDR']);
        $order->setPriceSolution($priceSolution);
        $order->setChart($chart);
        $this->em->persist($chart);
        $this->em->persist($order);
        $this->em->flush();

        return new RedirectResponse($this->router->generate(
            'chart_payment_pending',
            array('chart' => $order->getChart()->getId()), true
        ));


        throw new \Exception('Errors were found: ' . $form->getErrorsAsString());
    }
}
