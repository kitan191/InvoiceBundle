<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use FormaLibre\InvoiceBundle\Entity\Chart;
use FormaLibre\InvoiceBundle\Entity\Invoice;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Knp\Bundle\SnappyBundle\Snappy\LoggableGenerator;

/**
* @DI\Service("formalibre.manager.invoice_manager")
*/
class InvoiceManager
{
    /**
     * @DI\InjectParams({
     *     "om"         = @DI\Inject("claroline.persistence.object_manager"),
     *     "vatManager" = @DI\Inject("formalibre.manager.vat_manager"),
     *     "snappy"     = @DI\Inject("knp_snappy.pdf"),
     *     "templating" = @DI\Inject("templating"),
     *     "pdfDir"     = @DI\Inject("%claroline.param.pdf_directory%")
     * })
     */
    public function __construct(
        ObjectManager $om,
        VATManager $vatManager,
        LoggableGenerator $snappy,
        $templating,
        $pdfDir
    )
    {
        $this->om = $om;
        $this->vatManager = $vatManager;
        $this->snappy = $snappy;
        $this->templating = $templating;
        $this->pdfDir = $pdfDir;
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
        $this->om->persist($invoice);
        $this->om->flush();
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

    public function send(Invoice $invoice)
    {
        //
    }

    private function sendPendingMail(Chart $chart)
    {
        //throw new \Exception('send validation mail');
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

    public function sendSuccessMail(SharedWorkspace $sws, Order $order, $duration = null)
    {
        $workspace = $this->getWorkspaceData($sws);
        $snappy = $this->container->get('knp_snappy.pdf');
        $owner = $order->getOwner();
        $fieldRepo = $this->om->getRepository('ClarolineCoreBundle:Facet\FieldFacet');
        $valueRepo =  $this->om->getRepository('ClarolineCoreBundle:Facet\FieldFacetValue');
        $streetField = $fieldRepo->findOneByName('formalibre_street');
        $cpField = $fieldRepo->findOneByName('formalibre_cp');
        $townField = $fieldRepo->findOneByName('formalibre_town');
        $countryField = $fieldRepo->findOneByName('formalibre_country');
        $order->setValidationDate(new \DateTime());
        $this->om->persist($order);
        $this->om->flush();

        $streeFieldValue = $valueRepo->findOneBy(array('user' => $owner, 'fieldFacet' => $streetField));
        $street = $streeFieldValue ? $streeFieldValue->getValue(): 'N/A';
        $cpFieldValue = $valueRepo->findOneBy(array('user' => $owner, 'fieldFacet' => $cpField));
        $cp = $cpFieldValue ? $cpFieldValue->getValue(): 'N/A';
        $townFieldValue = $valueRepo->findOneBy(array('user' => $owner, 'fieldFacet' => $townField));
        $town = $townFieldValue ? $townFieldValue->getValue(): 'N/A';
        $countryFieldValue = $valueRepo->findOneBy(array('user' => $owner, 'fieldFacet' => $countryField));
        $country = $countryFieldValue ? $countryFieldValue->getValue(): 'N/A';
        $hasFreeMonth = $order->hasDiscount();
        $freeMonthAmount = $hasFreeMonth ? $this->ch->getParameter('formalibre_test_month_duration'): 0;

        $view = $this->container->get('templating')->render(
            'FormaLibreInvoiceBundle:pdf:invoice.html.twig',
            array(
                'order' => $order,
                'street' => $street,
                'cp' => $cp,
                'town' => $town,
                'country' => $country,
                'duration' => $duration,
                'sws' => $sws,
                'hasFreeMonth' => $hasFreeMonth,
                'freeMonthAmount' => $freeMonthAmount
            )
        );
        //@todo: the path should include the invoice numbe
        $path = $this->container->getParameter('claroline.param.pdf_directory') . '/invoice/' . $order->getId() . '.pdf';
        @mkdir($this->container->getParameter('claroline.param.pdf_directory'));
        @mkdir($this->container->getParameter('claroline.param.pdf_directory')) . '/invoice';
        $snappy->generateFromHtml($view, $path);
        $subject = $this->container->get('translator')->trans('formalibre_invoice', array(), 'invoice');
        $companyField = $countryField = $fieldRepo->findOneByName('formalibre_company_name');
        $companyFieldValue = $valueRepo->findOneBy(array('user' => $owner, 'fieldFacet' => $companyField));
        $company = $companyFieldValue ? $companyFieldValue->getValue(): null;
        $targetAdress = $this->ch->getParameter('formalibre_target_platform_url') . "/workspaces/{$sws->getRemoteId()}/open/tool/home";

        $body = $this->container->get('templating')->render(
            'FormaLibreInvoiceBundle:email:confirm_invoice.html.twig',
            array(
                'order' => $order,
                'company' => $company,
                'target_adress' => $targetAdress,
                'month_duration' => $duration,
                'sws' => $sws,
                'workspace' => $workspace,
                'hasFreeMonth' => $hasFreeMonth,
                'freeMonthAmount' => $freeMonthAmount
            )
        );

        return $this->mailManager->send($subject, $body, array($owner), null, array('attachment' => $path));
    }
}
