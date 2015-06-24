<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use FormaLibre\InvoiceBundle\Entity\Chart;
use FormaLibre\InvoiceBundle\Entity\Invoice;
use Claroline\CoreBundle\Entity\User;
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
     *     "container"    = @DI\Inject("service_container")
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
        OrderManager $orderManager,
        $container
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
        $this->invoiceRepository = $em->getRepository('FormaLibreInvoiceBundle:Invoice');
        $this->container = $container;
    }

    public function create(Chart $chart)
    {
        //if it already has an invoice, we don't create an other one...
        if ($chart->getInvoice()) return $chart->getInvoice();

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
        $invoice->setInvoiceNumber($this->getInvoiceCode());
        $chart->getPaymentInstruction() ?
            $invoice->setPaymentSystemName($chart->getPaymentInstruction()->getPaymentSystemName()):
            $invoice->setPaymentSystemName('none');
        $chart->setInvoice($invoice);
        $this->om->persist($chart);
        $this->om->persist($invoice);
        $this->om->flush();

        return $invoice;
    }

    public function send(Invoice $invoice)
    {
        $pdfInvoice = $this->getPdf($invoice);
        $subject = $this->translator->trans('formalibre_invoice', array(), 'invoice');
        $body = $this->templating->render(
            'FormaLibreInvoiceBundle:Invoice:email.html.twig',
            array('invoice' => $invoice)
        );

        $this->mailManager->send($subject, $body, array($invoice->getChart()->getOwner()), null, array('attachment' => $pdfInvoice));
    }

    public function getPdf(Invoice $invoice)
    {
        if (file_exists($path = $this->pdfDir . '/invoice/' . $invoice->getInvoiceNumber() . '.pdf')) @unlink($path);

        @mkdir($this->pdfDir);
        @mkdir($this->pdfDir . '/invoice');

        $extra = $invoice->getChart()->getExtendedData();
        $communication = isset($extra['communication']) ? $extra['communication']: null;

        $view = $this->templating->render(
            'FormaLibreInvoiceBundle:pdf:invoice.html.twig',
            array(
                'chart' => $invoice->getChart(),
                'communication' => $communication
            )
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

    public function getPayed($getQuery = false)
    {
        $dql = "
            SELECT i FROM FormaLibre\InvoiceBundle\Entity\Invoice i
            WHERE i.isPayed = true
        ";

        $query = $this->em->createQuery($dql);

        return ($getQuery) ? $query: $query->getResult();
    }

    public function validate(Invoice $invoice)
    {
        $chart = $invoice->getChart();
        $validDate = new \DateTime();
        $chart->setValidationDate($validDate);
        $orders = $chart->getOrders();

        foreach ($orders as $order) {
            $this->orderManager->complete($order);
        }

        $invoice->setIsPayed(true);
        $this->om->persist($invoice);
        $this->om->persist($chart);
        $this->om->flush();
    }

    public function getInvoiceCode()
    {
        $base = date('y') . date('m') . date('d');

        $dql = "
            SELECT i FROM FormaLibre\InvoiceBundle\Entity\Invoice i
            WHERE i.invoiceNumber LIKE :base
        ";

        $query = $this->em->createQuery($dql);
        $query->setParameter('base', $base . '%');
        $results = $query->getResult();

        $amt = count($results);
        $amt++;
        $code = str_pad($amt, 4, '0', STR_PAD_LEFT);

        return $base . $code;
    }

    /**
     * Export a list of invoice
     */
    public function export(array $invoices, $exporter)
    {
        $invExt = $this->container->get('forma_libre.invoice_bundle.twig.invoice_extension');
        $titles = array('number', 'date', 'amount', 'first name', 'last name', 'email', 'vat number', 'company name');
        $data = array();

        foreach ($invoices as $invoice) {
            $chart = $invoice->getChart();
            $data[] = array(
                $invoice->getInvoiceNumber(),
                $invoice->getChart()->getCreationDate()->format($this->translator->trans('date_range.format.with_hours', array(), 'platform')),
                $invoice->getTotalAmount(),
                $invoice->getChart()->getOwner()->getFirstName(),
                $invoice->getChart()->getOwner()->getLastName(),
                $invoice->getChart()->getOwner()->getMail(),
                $invExt->getFieldValue($invoice->getChart()->getOwner(), 'formalibre_vat'),
                $invExt->getFieldValue($invoice->getChart()->getOwner(), 'formalibre_company_name')
            );
        }

        return $exporter->export($titles, $data);
    }

    public function getAllInvoices($getQuery = false)
    {
        if ($getQuery) return $this->em->createQuery("SELECT i FROM FormaLibre\InvoiceBundle\Entity\Invoice i");

        return $this->invoiceRepository->findAll();
    }
}
