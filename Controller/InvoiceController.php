<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;

class InvoiceController extends Controller
{
    /** @DI\Inject("security.token_storage") */
    private $tokenStorage;

    /** @DI\Inject("formalibre.manager.product_manager") */
    private $productManager;

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
}
