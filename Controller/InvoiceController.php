<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;

class InvoiceController extends Controller
{
    /** @DI\Inject("security.context") */
    private $sc;

    /** @DI\Inject("formalibre.manager.product_manager") */
    private $productManager;

    /**
     * @EXT\Route(
     *      "/show",
     *      name="invoice/show/all"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function showAction()
    {
        $user = $this->sc->getToken()->getUser();
        $sharedWorkspaces = $this->productManager->getSharedWorkspaceByUser($user);

        return array(
            'sharedWorkspaces' => $sharedWorkspaces
        );
    }
}
