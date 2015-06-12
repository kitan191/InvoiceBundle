<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use FormaLibre\InvoiceBundle\Entity\Chart;
use FormaLibre\InvoiceBundle\Entity\Order;
use Claroline\CoreBundle\Persistence\ObjectManager;

/**
* @DI\Service("formalibre.manager.chart_manager")
*/
class ChartManager
{
    /**
     * @DI\InjectParams({
     *     "invoiceManager"         = @DI\Inject("formalibre.manager.invoice_manager"),
     *     "sharedWorkspaceManager" = @DI\Inject("formalibre.manager.shared_workspace_manager"),
     *     "om"                     = @DI\Inject("claroline.persistence.object_manager")
     * })
     */
    public function __construct(
        InvoiceManager $invoiceManager,
        ObjectManager $om,
        SharedWorkspaceManager $sharedWorkspaceManager
    )
    {
        $this->invoiceManager = $invoiceManager;
        $this->sharedWorkspaceManager = $sharedWorkspaceManager;
        $this->om = $om;
        $this->chartRepository = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Chart');
    }

    public function getChart($chartId)
    {
        return $chartId === 0 ? new Chart(): $this->chartRepository->find($chartId);
    }

    public function addOrder(Order $order, Chart $chart = null)
    {
        if (!$chart) $chart = new Chart();
        $order->setChart($chart);
    }

    public function submit(Chart $chart)
    {
        $invoice = $this->invoiceManager->create($chart);
        $this->invoiceManager->send($invoice);
    }

    public function validate(Chart $chart)
    {
        $orders = $chart->getOrders();

        foreach ($order as $order) {
            switch ($order->getProduct()->getType()) {
                case 'SHARED_WS': $this->completeSharedWorkspaceOrder($order); break;
            }
        }
    }

    public function completeSharedWorkspaceOrder(Order $order)
    {
        $duration = $order->hasDiscount() ?
            $order->getPriceSolution()->getMonthDuration() + $this->container->get('claroline.config.platform_config_handler')->getParameter('formalibre_test_month_duration'):
            $order->getPriceSolution()->getMonthDuration();

        $this->sharedWorkspaceManager->executeWorkspaceOrder(
            $order,
            $duration,
            $order->getSharedWorkspace()
        );
    }

    public function sendBankTransferPendingMail(Chart $chart)
    {
        throw new \Exception('pending mail sent');
        /*
        $user = $order->getOwner();
        $subject = $this->container->get('translator')->trans('formalibre_invoice', array(), 'invoice');
        $valueRepo =  $this->om->getRepository('ClarolineCoreBundle:Facet\FieldFacetValue');
        $fieldRepo = $this->om->getRepository('ClarolineCoreBundle:Facet\FieldFacet');
        $companyField = $fieldRepo->findOneByName('formalibre_company_name');
        $field = $valueRepo->findOneBy(array('user' => $user, 'fieldFacet' => $companyField));
        $company = $field ? $field->getValue(): null;
        $instruction = $order->getPaymentInstruction();
        $extra = $instruction->getExtendedData();
        $hasFreeMonth = $order->hasDiscount();
        $freeMonthAmount = $hasFreeMonth ? $this->ch->getParameter('formalibre_test_month_duration'): 0;

        $body = $this->container->get('templating')->render(
            'FormaLibreInvoiceBundle:email:confirm_bank_transfer.html.twig',
            array(
                'order' => $order,
                'company' => $company,
                'communication' => $extra->get('communication'),
                'freeMonthAmount' => $freeMonthAmount,
                'hasFreeMonth' => $hasFreeMonth
            )
        );

        return $this->mailManager->send($subject, $body, array($user));
        */
    }
}
