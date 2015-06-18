<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use FormaLibre\InvoiceBundle\Entity\Chart;
use FormaLibre\InvoiceBundle\Entity\Invoice;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Knp\Bundle\SnappyBundle\Snappy\LoggableGenerator;
use Claroline\CoreBundle\Manager\MailManager;

/**
* @DI\Service("formalibre.manager.invoice_manager")
*/
class InvoiceManager
{
    /**
     * @DI\InjectParams({
     *     "om"           = @DI\Inject("claroline.persistence.object_manager"),
     *     "vatManager"   = @DI\Inject("formalibre.manager.vat_manager"),
     *     "snappy"       = @DI\Inject("knp_snappy.pdf"),
     *     "templating"   = @DI\Inject("templating"),
     *     "pdfDir"       = @DI\Inject("%claroline.param.pdf_directory%"),
     *     "mailManager"  = @DI\Inject("claroline.manager.mail_manager"),
     *     "translator"   = @DI\Inject("translator"),
     *     "em"           = @DI\Inject("doctrine.orm.entity_manager"),
     *     "orderManager" = @DI\Inject("formalibre.manager.order_manager"),
     * })
     */
    public function __construct(
        ObjectManager $om,
        VATManager $vatManager,
        LoggableGenerator $snappy,
        $templating,
        $pdfDir,
        MailManager $mailManager,
        $translator,
        $em,
        OrderManager $orderManager
    )
    {
        $this->om = $om;
        $this->vatManager = $vatManager;
        $this->snappy = $snappy;
        $this->templating = $templating;
        $this->pdfDir = $pdfDir;
        $this->mailManager = $mailManager;
        $this->translator = $translator;
        $this->em = $em;
        $this->orderManager = $orderManager;
    }

    public function create(Chart $chart)
    {
        $invoice = new Invoice();
        $invoice->setChart($chart);
        $user = $chart->getOwner();
        $vatRate = $this->vatManager->getVatFromOwner($user) ?
            0: $this->vatManager->getVATRate($this->vatManager->getCountryCodeFromOwner($user));
        $invoice->setVatRate($vatRate);
        $netTotal = 0;

        foreach ($chart->getOrders() as $order) {
            $netTotal += $order->getPriceSolution()->getPrice() * $order->getQuantity();
        }

        $invoice->setAmount($netTotal);
        $invoice->setVatAmount($netTotal * $vatRate);
        $invoice->setTotalAmount($netTotal + $netTotal * $vatRate);
        $invoice->setPaymentSystemName($chart->getPaymentInstruction()->getPaymentSystemName());
        $chart->setInvoice($invoice);
        $this->om->persist($chart);
        $this->om->persist($invoice);
        $this->om->flush();

        $pdfInvoice = $this->getPdf($invoice);
        $subject = $this->translator->trans('formalibre_invoice', array(), 'invoice');
        $body = $this->templating->render(
            'FormaLibreInvoiceBundle:email:confirm_bank_transfer.html.twig',
            array('invoice' => $invoice)
        );

        $this->mailManager->send($subject, $body, array($user), null, array('attachment' => $pdfInvoice));

        return $invoice;
    }

    public function getPdf(Invoice $invoice)
    {
        if (file_exists($path = $this->pdfDir . '/invoice/' . $invoice->getId() . '.pdf')) @unlink($path);

        @mkdir($this->pdfDir);
        @mkdir($this->pdfDir . '/invoice');

        $view = $this->templating->render(
            'FormaLibreInvoiceBundle:pdf:invoice.html.twig',
            array('chart' => $invoice->getChart())
        );

        $this->snappy->generateFromHtml($view, $path);

        return $path;
    }

    public function getUnpayed($getQuery = false)
    {
        $dql = "
            SELECT i FROM FormaLibre\InvoiceBundle\Entity\Invoice i
            WHERE i.isPayed = false
            and i.paymentSystemName = 'bank_transfer'
        ";

        $query = $this->em->createQuery($dql);

        return ($getQuery) ? $query: $query->getResult();
    }

    public function validate(Invoice $invoice)
    {
        $chart = $invoice->getChart();
        $orders = $chart->getOrders();

        foreach ($orders as $order) {
            $this->orderManager->complete($order);
        }

        //send mail and so on...
    }

    public function sendSuccessMail(SharedWorkspace $sws, Order $order, $duration = null)
    {

    }
}
