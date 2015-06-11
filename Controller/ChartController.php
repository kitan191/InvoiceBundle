<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ChartController extends Controller
{
    /** @DI\Inject("formalibre.manager.chart_manager") */
    private $chartManager;

    /** @DI\Inject("security.token_storage") */
    private $tokenStorage;

    /**
     * @EXT\Route(
     *      "/payment/chart/submit/{chart}",
     *      name="formalibre_invoice_chart_submit"
     * )
     *
     * @param $swsId the sharedWorkspaceId if it already exists (otherwise, if it's 0, we'll create a new one)
     * @return Response
     */
    public function submitChartAction(Chart $chart)
    {
        $currentUser = $this->tokenStorage->getToken()->getUser();

        if ($chart->getOwner() !== $currentUser()) {
            throw new \AccessDeniedException();
        }

        $this->chartManager->submitChart($chart); 
    }
}
