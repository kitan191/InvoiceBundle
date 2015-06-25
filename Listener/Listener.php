<?php
/*
* This file is part of the Claroline Connect package.
*
* (c) Claroline Consortium <consortium@claroline.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace FormaLibre\InvoiceBundle\Listener;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Event\DisplayToolEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Claroline\CoreBundle\Event\OpenAdministrationToolEvent;
use Claroline\CoreBundle\Event\DisplayWidgetEvent;

/**
* @DI\Service()
*/
class Listener
{
    private $container;
    private $httpKernel;

    /**
    * @DI\InjectParams({
    *   "container" = @DI\Inject("service_container"),
    *    "httpKernel" = @DI\Inject("http_kernel")
    * })
    */
    public function __construct(
        ContainerInterface $container,
        HttpKernelInterface $httpKernel
    )
    {
        $this->container = $container;
        $this->httpKernel = $httpKernel;
        $this->tokenStorage = $this->container->get('security.token_storage');
        $this->sharedWorkspaceManager = $this->container->get('formalibre.manager.shared_workspace_manager');
    }

   /**
    * @DI\Observe("open_tool_desktop_formalibre_invoice")
    *
    * @param DisplayToolEvent $event
    */
    public function onDisplayForms(DisplayToolEvent $event)
    {
        $event->setContent($this->getDisplayedForms());
    }

    private function getDisplayedForms()
    {
        $params = array('_controller' => 'FormaLibreInvoiceBundle:SharedWorkspace:forms');
        $subRequest = $this->container->get('request')->duplicate(array(), null, $params);
        $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);

        return $response->getContent();
    }

    /**
     * @DI\Observe("administration_tool_formalibre_admin_invoice")
     *
     * @param DisplayToolEvent $event
     */
    public function onDisplayAdminInvoice(OpenAdministrationToolEvent $event)
    {
        $event->setResponse($this->openAdminPendingOperations());
    }

    private function openAdminPendingOperations()
    {
        $params = array('_controller' => 'FormaLibreInvoiceBundle:Administration:index');
        $params['page'] = 1;
        $params['search'] = '';
        $subRequest = $this->container->get('request')->duplicate(array(), null, $params);
        $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);

        return $response;
    }

    /**
     * @DI\Observe("widget_formalibre_purchased")
     *
     * @param DisplayToolEvent $event
     */
     public function onDisplayPurchased(DisplayWidgetEvent $event)
     {
         $event->setContent($this->getDisplayPurchased());
     }

     private function getDisplayPurchased()
     {
        $user = $this->tokenStorage->getToken()->getUser();
        $sharedWorkspaces = $this->sharedWorkspaceManager->getSharedWorkspaceByUser($user);
        $workspaceData = array();

        foreach ($sharedWorkspaces as $sharedWorkspace) {
            $el = array();
            $workspace = $this->sharedWorkspaceManager->getWorkspaceData($sharedWorkspace);
            $el['shared_workspace'] = $sharedWorkspace;

            if ($workspace) {
                $el['workspace'] = $workspace;
            } else {
                $el['workspace'] = array('code' => 0, 'name' => null, 'expiration_date' => 0);
            }

            $sws = $this->sharedWorkspaceManager->getLastOrder($sharedWorkspace);
            if ($sws) $el['product'] = $sws->getProduct();

            $workspaceData[] = $el;
        }

        $content = $this->container->get('templating')->render(
            'FormaLibreInvoiceBundle:MyPurchase:widget.html.twig',
            array('workspace_data' => $workspaceData)
        );

        return $content;
     }

     /**
      * @DI\Observe("widget_formalibre_invoice")
      *
      * @param DisplayToolEvent $event
      */
      public function onDisplayInvoice(DisplayWidgetEvent $event)
      {
          $event->setContent($this->getDisplayedInvoice());
      }

      private function getDisplayedInvoice()
      {
          $charts = $this->container->get('formalibre.manager.chart_manager')
            ->getByUser($this->tokenStorage->getToken()->getUser());

          $content = $this->container->get('templating')->render(
              'FormaLibreInvoiceBundle:Invoice:widget.html.twig',
              array('charts' => $charts)
          );

          return $content;
      }

      /**
       * @DI\Observe("widget_formalibre_campus")
       *
       * @param DisplayToolEvent $event
       */
       public function onDisplayCampus(DisplayWidgetEvent $event)
       {
           $event->setContent($this->getDisplayCampus());
       }

       private function getDisplayCampus()
       {
           $user = $this->tokenStorage->getToken()->getUser();

           $content = $this->container->get('templating')->render(
               'FormaLibreInvoiceBundle:Campus:widget.html.twig',
               array('user' => $user)
           );

           return $content;
       }
}
