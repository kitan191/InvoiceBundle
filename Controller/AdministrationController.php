<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\Payment\CoreBundle\Entity\Payment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use JMS\SecurityExtraBundle\Annotation as SEC;
use FormaLibre\InvoiceBundle\Entity\Order;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FormaLibre\InvoiceBundle\Entity\Invoice;

/**
* @SEC\PreAuthorize("canOpenAdminTool('formalibre_admin_invoice')")
*/
class AdministrationController extends Controller
{
    /** @DI\Inject("formalibre.manager.invoice_manager") */
    private $invoiceManager;

    /** @DI\Inject("claroline.pager.pager_factory") */
    private $pagerFactory;

    /** @DI\Inject("%claroline.param.pdf_directory%") */
    private $pdfDirectory;

    /** @DI\Inject("security.authorization_checker") */
    private $authorization;

    /** @DI\Inject("formalibre.manager.shared_workspace_manager") */
    private $sharedWorkspaceManager;

    /** @DI\Inject("payment.plugin_controller") */
    private $ppc;

    /** @DI\Inject("router") */
    private $router;

    /**
     * @EXT\Route(
     *      "/admin/index",
     *      name="admin_invoice_index"
     * )
     * @EXT\Template
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @EXT\Route(
     *      "/admin/open/pending/{page}",
     *      name="admin_invoice_open_pending",
     *      defaults={"page"=1, "search"=""},
     *      options = {"expose"=true}
     * )
     *
     * @EXT\Route(
     *      "/admin/open/pending/{page}/search/{search}",
     *      name="admin_invoice_open_pending_search",
     *      defaults={"page"=1},
     *      options = {"expose"=true}
     * )
     * @EXT\Template
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function openPendingAction($page, $search)
    {
        $pager = $this->pagerFactory->createPager($this->invoiceManager->getUnpayed(true), $page, 25);

        return array('pager' => $pager, 'search' => $search);
    }

    /**
     * @EXT\Route(
     *      "/admin/open/invoice/{page}",
     *      name="admin_invoice_open_invoice",
     *      defaults={"page"=1, "search"=""},
     *      options = {"expose"=true}
     * )
     * @EXT\Route(
     *      "/admin/open/invoice/{page}/search/{search}",
     *      name="admin_invoice_open_invoice_search",
     *      defaults={"page"=1},
     *      options = {"expose"=true}
     * )
     * @EXT\Template
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function showInvoicesAction($page, $search)
    {
        $query = $this->invoiceManager->getPayed(true);
        $pager = $this->pagerFactory->createPager($query, $page, 25);

        return array('pager' => $pager, 'search' => $search);
    }

    /**
     * @EXT\Route(
     *      "/bank_transfer_validate/{invoice}",
     *      name="formalibre_validate_bank_transfer"
     * )
     * @EXT\Template
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function validateBankTransferAction(Invoice $invoice)
    {
        //the admin is the only one able to do this.
        if (!$this->authorization->isGranted('ROLE_ADMIN')) {
            throw new \AccessDeniedException();
        }

        $payments = $invoice->getChart()->getPaymentInstruction()->getPayments();
        $payment = $payments[0];
        $this->ppc->approve($payment, $invoice->getTotalAmount());
        $this->invoiceManager->validate($invoice);
        $route = $this->router->generate('admin_invoice_open_pending');

        return new RedirectResponse($route);
    }

    /**
     * @EXT\Route(
     *      "/export/{format}",
     *      name="formalibre_export_invoice",
     *      defaults={"format"="xls"}
     * )
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function exportAction($format)
    {
        //the admin is the only one able to do this.
        if (!$this->authorization->isGranted('ROLE_ADMIN')) {
            throw new \AccessDeniedException();
        }

        $invoices = $this->invoiceManager->getAllInvoices();
        $file = $this->invoiceManager->export(
            $invoices, $this->container->get('claroline.exporter.' . $format)
        );

        $response = new StreamedResponse();

        $response->setCallBack(
            function () use ($file) {
                readfile($file);
            }
        );
        $response->headers->set('Content-Transfer-Encoding', 'octet-stream');
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition', 'attachment; filename=users.' . $format);

        switch ($format) {
            case 'csv': $response->headers->set('Content-Type', 'text/csv'); break;
            case 'xls': $response->headers->set('Content-Type', 'application/vnd.ms-excel'); break;
        }

        $response->headers->set('Connection', 'close');

        return $response;
    }
}
