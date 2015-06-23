<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use FormaLibre\InvoiceBundle\Entity\Invoice;

class InvoiceController extends Controller
{
    /** @DI\Inject("security.token_storage") */
    private $tokenStorage;

    /** @DI\Inject("security.authorization_checker") */
    private $authorization;

    /** @DI\Inject("formalibre.manager.product_manager") */
    private $productManager;

    /** @DI\Inject("formalibre.manager.invoice_manager") */
    private $invoiceManager;

    /**
     * @EXT\Route(
     *      "/admin/invoice/{invoice}/show",
     *      name="admin_invoice_show"
     * )
     * @EXT\Template
     */
    public function showAction(invoice $invoice)
    {
        if (
            $invoice->getChart()->getOwner() !== $this->tokenStorage->getToken()->getUser() &&
            !$this->authorization->isGranted('ROLE_ADMIN')
        ) {
            throw new AccessDeniedException;
        }

        return array('invoice' => $invoice);
    }

    /**
     * @EXT\Route(
     *      "/download/invoice/{invoice}",
     *      name="invoice_download"
     * )
     *
     * @return Response
     */
    public function downloadAction(Invoice $invoice)
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if ($invoice->getChart()->getOwner() !== $user &&
            !$this->authorization->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException;
        }

        $file = $this->invoiceManager->getPdf($invoice);
        $response = new StreamedResponse();

        $response->setCallBack(
            function () use ($file) {
                readfile($file);
            }
        );

        $response->headers->set('Content-Transfer-Encoding', 'octet-stream');
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition', 'attachment; filename=invoice.pdf');
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Connection', 'close');

        return $response;
    }
}
