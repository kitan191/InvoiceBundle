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
use Claroline\CoreBundle\Event\GenericDatasEvent;
use FormaLibre\InvoiceBundle\Manager\CreditSupportManager;
use Claroline\CoreBundle\Event\UserCreatedEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;

/**
* @DI\Service()
*/
class Listener
{
    private $container;
    private $httpKernel;
    private $creditManager;

    /**
    * @DI\InjectParams({
    *     "container"     = @DI\Inject("service_container"),
    *     "httpKernel"    = @DI\Inject("http_kernel"),
    *     "creditManager" = @DI\Inject("formalibre.manager.credit_support_manager")
    * })
    */
    public function __construct(
        ContainerInterface $container,
        HttpKernelInterface $httpKernel,
        CreditSupportManager $creditManager
    )
    {
        $this->container = $container;
        $this->httpKernel = $httpKernel;
        $this->creditManager = $creditManager;
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
    public function onDisplayAdminIndex(OpenAdministrationToolEvent $event)
    {
        $event->setResponse($this->openAdminIndex());
    }

    private function openAdminIndex()
    {
        $params = array('_controller' => 'FormaLibreInvoiceBundle:Administration:index');
        $params['page'] = 1;
        $params['search'] = '';
        $subRequest = $this->container->get('request')->duplicate(array(), null, $params);
        $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);

        return $response;
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

    /**
    * @DI\Observe("administration_tool_formalibre_product_creator")
    *
    * @param DisplayToolEvent $event
    */
    public function onDisplayAdminProduct(OpenAdministrationToolEvent $event)
    {
       $event->setResponse($this->openAdminProducts());
    }

    private function openAdminProducts()
    {
       $params = array('_controller' => 'FormaLibreInvoiceBundle:Administration:productIndex');
       $subRequest = $this->container->get('request')->duplicate(array(), null, $params);
       $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);

       return $response;
    }
    
   /**
    * @DI\Observe("administration_tool_formalibre_partners_admin_tool")
    *
    * @param DisplayToolEvent $event
    */
    public function onDisplayPartners(OpenAdministrationToolEvent $event)
    {
       $event->setResponse($this->openAdminPartners());
    }
    
    private function openAdminPartners()
    {
       $params = array('_controller' => 'FormaLibreInvoiceBundle:Administration:partnerIndex');
       $subRequest = $this->container->get('request')->duplicate(array(), null, $params);
       $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);

       return $response;
    }

    /**
     * @DI\Observe("open_tool_desktop_formalibre_my_shared_workspaces_tool")
     *
     * @param DisplayToolEvent $event
     */
    public function onMySharedWorkspacesDesktopToolOpen(DisplayToolEvent $event)
    {
        $params = array();
        $params['_controller'] = 'FormaLibreInvoiceBundle:SharedWorkspace:mySharedWorkspacesDesktopToolIndex';
        $subRequest = $this->container->get('request')->duplicate(array(), null, $params);
        $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        $event->setContent($response->getContent());
        $event->stopPropagation();
    }

    /**
    * @DI\Observe("administration_tool_formalibre_shared_workspaces_admin_tool")
    *
    * @param OpenAdministrationToolEvent $event
    */
    public function onSharedWorkspacesAdminToolOpen(OpenAdministrationToolEvent $event)
    {
        $params = array('_controller' => 'FormaLibreInvoiceBundle:AdminSharedWorkspaces:sharedWorkspacesAdminToolIndex');
        $subRequest = $this->container->get('request')->duplicate(array(), null, $params);
        $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        $event->setResponse($response);
    }

    /**
     * @DI\Observe("open_tool_formalibre_my_orders")
     *
     * @param DisplayToolEvent $event
     */
    public function onMyInvoicesDesktopToolOpen(DisplayToolEvent $event)
    {
        $params = array();
        $params['_controller'] = 'FormaLibreInvoiceBundle:InvoiceController:list';
        $subRequest = $this->container->get('request')->duplicate(array(), null, $params);
        $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        $event->setContent($response->getContent());
        $event->stopPropagation();
    }

    /**
     * @DI\Observe("formalibre_request_nb_remaining_credits")
     *
     * @param GenericDatasEvent $event
     */
    public function onNbRemainingCreditsRequest(GenericDatasEvent $event)
    {
        $user = $event->getDatas();
        $nbCredits = $this->creditManager->getNbRemainingCredits($user);
        $event->setResponse($nbCredits);
    }
    
    /**
     * @DI\Observe("user_created_event")
     *
     * @param UserCreatedEvent $event
     */
    public function onUserCreated(UserCreatedEvent $event)
    {
        $user = $event->getUser();
        $session = $this->container->get('session');
        
        if ($session->has('partner_code')) {
            $code = $session->get('partner_code');
            $em = $this->container->get('doctrine.orm.entity_manager');
            $partner = $em->getRepository('FormaLibre\InvoiceBundle\Entity\Partner')
                ->findOneByCode('SUPER');
            if ($partner) {
                $partner->addUser($user);
                $em->persist($partner);
                $em->flush();
            }
        }
    }
    
    /**
     * @DI\Observe("kernel.request")
     *
     * Sets the platform language.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernel::MASTER_REQUEST != $event->getRequestType()) {
            // ne rien faire si ce n'est pas la requête principale
            return;
        }
        
        $request = $event->getRequest();
        $code = $request->query->get('partner_code');
        $this->container->get('session')->set('partner_code', $code);
    }
}
