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

/**
* @SEC\PreAuthorize("canOpenAdminTool('formalibre_admin_invoice')")
*/
class AdministrationController extends Controller
{
    /** @DI\Inject("formalibre.manager.payment_manager") */
    private $paymentManager;

    /** @DI\Inject("formalibre.manager.order_manager") */
    private $orderManager;

    /** @DI\Inject("claroline.pager.pager_factory") */
    private $pagerFactory;

    /** @DI\Inject("%claroline.param.pdf_directory%") */
    private $pdfDirectory;

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
        $query = $search === '' ?
            $this->paymentManager->getPendingBankTransfer(true) :
            $this->paymentManager->getBankTransferByCommunication($search, true);
        $pager = $this->pagerFactory->createPager($query, $page, 25);

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
        $query = $this->orderManager->getPayedOrders(true);
        $pager = $this->pagerFactory->createPager($query, $page, 25);

        return array('pager' => $pager, 'search' => $search);
    }

    /**
     * @EXT\Route(
     *      "/admin/invoice/{order}/show",
     *      name="admin_invoice_show"
     * )
     * @EXT\Template
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function showInvoiceAction(Order $order)
    {
        return array('order' => $order);
    }

    /**
     * @EXT\Route(
     *      "/admin/invoice/{order}/download",
     *      name="admin_invoice_download"
     * )
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function downloadInvoiceAction(Order $order)
    {
        $response = new StreamedResponse();
        $file = $this->pdfDirectory. '/invoice/' . $order->getId() . '.pdf';

        $response->setCallBack(
            function () use ($file) {
                readfile($file);
            }
        );

        $response->headers->set('Content-Transfer-Encoding', 'octet-stream');
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . urlencode('invoice.pdf'));
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Connection', 'close');

        return $response;
    }

    /**
     * @EXT\Route(
     *      "/bank_transfer_validate/{payment}",
     *      name="formalibre_validate_bank_transfer",
     *      defaults={"swsId" = 0}
     * )
     * @EXT\Template
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function validateBankTransferAction(Payment $payment)
    {
        //the admin is the only one able to do this.
        if (!$this->authorization->isGranted('ROLE_ADMIN')) {
            throw new \AccessDeniedException();
        }

/*
        $order = $this->paymentManager->getOrderFromPayment($payment);
        $extra = $order->getExtendedData();
        $sws = $sws = $this->em->getRepository('FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace')
            ->find($extra['shared_workspace_id']);
        $this->ppc->approve($payment, $order->getPaymentInstruction()->getAmount());
        $duration = $order->hasDiscount() ?
            $order->getPriceSolution()->getMonthDuration() + $this->container->get('claroline.config.platform_config_handler')->getParameter('formalibre_test_month_duration'):
            $order->getPriceSolution()->getMonthDuration();
        $this->productManager->executeWorkspaceOrder(
            $order,
            $duration,
            $sws
        );
        $route = $this->router->generate('admin_invoice_open_pending');

        return new RedirectResponse($route);*/
    }
}
