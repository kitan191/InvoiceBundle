<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;
use FormaLibre\InvoiceBundle\Manager\Exception\PaymentHandlingFailedException;
use FormaLibre\InvoiceBundle\Entity\Chart;
use FormaLibre\InvoiceBundle\Entity\Invoice;
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

    /**
     * @EXT\Route(
     *      "/payment_complete/chart/{chart}",
     *      name="chart_payment_complete"
     * )
     * @return Response
     */
    public function completePaymentAction(Chart $chart)
    {
        if (
            $chart->getOwner() !== $this->tokenStorage->getToken()->getUser()
            && $this->authorization->isGranted('ROLE_ADMIN') === false
        ) {
            throw new AccessDeniedException();
        }

        $invoice = $chart->getInvoice();
        
        try {
            $this->invoiceManager->validate($invoice);
            $this->invoiceManager->send($invoice);
        } catch (PaymentHandlingFailedException $e) {
            $content = $this->renderView(
                'FormaLibreInvoiceBundle:errors:paymentHandlingFailedException.html.twig'
            );

            return new Response($content);
        }

        return new RedirectResponse($this->router->generate('claro_desktop_open', array()));
    }

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
        $this->em->persist($chart);
        $this->em->flush();

        return array(
            'communication' => $extData['communication'],
            'chart' => $chart
        );
    }

    /**
     * @EXT\Route(
     *      "/payment_cancel",
     *      name="chart_payment_cancel"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function cancelAction(Chart $chart)
    {
        return new RedirectResponse($this->router->generate('claro_desktop_open', array()));
    }
}
