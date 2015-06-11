<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use FormaLibre\InvoiceBundle\Entity\Chart;

/**
* @DI\Service("formalibre.manager.chart_manager")
*/
class ChartManager
{
    /**
     * @DI\InjectParams({
     *     "invoiceManager" = @DI\Inject("formalibre.manager.invoice_manager")
     * })
     */
    public function __construct(
        InvoiceManager $invoiceManager
    )
    {
        $this->invoiceManager = $invoiceManager;
    }

    public function submitChart(Chart $chart)
    {
        $invoice = $this->invoiceManager->create($chart);
        $this->invoiceManager->send($invoice);
    }
}
