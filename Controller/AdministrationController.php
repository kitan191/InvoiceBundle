<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\Payment\CoreBundle\Entity\Payment;

class AdministrationController extends Controller
{
    /** @DI\Inject("formalibre.manager.payment_manager") */
    private $paymentManager;

    /** @DI\Inject("claroline.pager.pager_factory") */
    private $pagerFactory;

    /**
     * @EXT\Route(
     *      "/admin/open/{page}",
     *      name="admin_invoice_open",
     *      defaults={"page"=1, "search"=""},
     *      options = {"expose"=true}
     * )
     *
     * @EXT\Route(
     *      "/admin/open/{page}/search/{search}",
     *      name="admin_invoice_open_search",
     *      defaults={"page"=1},
     *      options = {"expose"=true}
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function openAction($page, $search)
    {
        $query = $search === '' ?
            $this->paymentManager->getPendingBankTransfer(true) :
            $this->paymentManager->getBankTransferByCommunication($search, true);
        $pager = $this->pagerFactory->createPager($query, $page, 25);

        return array('pager' => $pager, 'search' => $search);
    }
}
    /**
     * @EXT\Route(
     *     "/page/{page}/max/{max}/order/{order}/direction/{direction}",
     *     name="claro_admin_user_list",
     *     defaults={"page"=1, "search"="", "max"=50, "order"="id","direction"="ASC"},
     *     options = {"expose"=true}
     * )
     * @EXT\Route(
     *     "/users/page/{page}/search/{search}/max/{max}/order/{order}/direction/{direction}",
     *     name="claro_admin_user_list_search",
     *     defaults={"page"=1, "max"=50, "order"="id","direction"="ASC"},
     *     options = {"expose"=true}
     * )
