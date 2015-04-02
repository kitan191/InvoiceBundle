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

    /**
     * @EXT\Route(
     *      "/admin/open",
     *      name="admin_invoice_open"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function openAction()
    {
        $transfers = $this->paymentManager->getPendingBankTransfer();

        return array('transfers' => $transfers);
    }
}
