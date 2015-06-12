<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\FormaLibre\InvoiceBundle\Entity\Chart;
use Claroline\FormaLibre\InvoiceBundle\Entity\Invoice;
use Claroline\CoreBundle\Persistence\ObjectManager;

/**
* @DI\Service("formalibre.manager.invoice_manager")
*/
class InvoiceManager
{
    /**
     * @DI\InjectParams({
     *     "om" = @DI\Inject("claroline.persistence.object_manager")
     * })
     */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    public function create(Chart $chart)
    {
        $invoice = new Invoice();
        $invoice->setChart($chart);
        $this->om->persist($invoice);
        $this->om->flush();
    }

    public function send(Invoice $invoice)
    {
        throw new \Exception('send invoice yolo !');
    }
/*
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
    }*/
}
