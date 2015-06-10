<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\Payment\CoreBundle\Entity\Payment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use JMS\SecurityExtraBundle\Annotation as SEC;
use FormaLibre\InvoiceBundle\Entity\Order;

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
     *      "/admin/index",
     *      name="admin_invoice_index"
     * )
     * @EXT\Template
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function showInvoiceAction(Order $order)
    {

    }
}
