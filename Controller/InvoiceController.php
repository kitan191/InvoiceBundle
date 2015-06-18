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

    /** @DI\Inject("formalibre.manager.product_manager") */
    private $productManager;

    /** @DI\Inject("formalibre.manager.invoice_manager") */
    private $invoiceManager;


    /**
     * @EXT\Route(
     *      "/show",
     *      name="invoice_show_all"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function showAction()
    {
        return array('data' => array());

        $user = $this->tokenStorage->getToken()->getUser();
        $sharedWorkspaces = $this->productManager->getSharedWorkspaceByUser($user);
        $data = array();

        foreach ($sharedWorkspaces as $sharedWorkspace) {
            $el = array();
            $workspace = $this->productManager->getWorkspaceData($sharedWorkspace);
            $el['shared_workspace'] = $sharedWorkspace;

            if ($workspace) {
                $el['workspace'] = $workspace;
            } else {
                $el['workspace'] = array('code' => 0, 'name' => null, 'expiration_date' => 0);
            }

            $data[] = $el;
        }

        return array('data' => $data);
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
    {/*
        return new Response($this->renderView(
            'FormaLibreInvoiceBundle:pdf:invoice.html.twig',
            array('chart' => $invoice->getChart())
        ));
*/
        $user = $this->tokenStorage->getToken()->getUser();

        if ($invoice->getChart()->getOwner() !== $user) {
            throw new \AccessDeniedException;
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
