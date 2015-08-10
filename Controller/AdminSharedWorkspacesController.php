<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Persistence\ObjectManager;
use FormaLibre\InvoiceBundle\Entity\Chart;
use FormaLibre\InvoiceBundle\Entity\Order;
use FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace;
use FormaLibre\InvoiceBundle\Form\SharedWorkspaceType;
use FormaLibre\InvoiceBundle\Manager\InvoiceManager;
use FormaLibre\InvoiceBundle\Manager\ProductManager;
use FormaLibre\InvoiceBundle\Manager\SharedWorkspaceManager;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\SecurityExtraBundle\Annotation as SEC;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
* @SEC\PreAuthorize("canOpenAdminTool('formalibre_shared_workspaces_admin_tool')")
*/
class AdminSharedWorkspacesController extends Controller
{
    private $ch;
    private $formFactory;
    private $invoiceManager;
    private $om;
    private $productManager;
    private $request;
    private $router;
    private $sharedWorkspaceManager;
    private $translator;

    private $friendRepo;
    private $campusPlatform;

    /**
     * @DI\InjectParams({
     *     "ch"                     = @DI\Inject("claroline.config.platform_config_handler"),
     *     "formFactory"            = @DI\Inject("form.factory"),
     *     "invoiceManager"         = @DI\Inject("formalibre.manager.invoice_manager"),
     *     "om"                     = @DI\Inject("claroline.persistence.object_manager"),
     *     "productManager"         = @DI\Inject("formalibre.manager.product_manager"),
     *     "requestStack"           = @DI\Inject("request_stack"),
     *     "router"                 = @DI\Inject("router"),
     *     "sharedWorkspaceManager" = @DI\Inject("formalibre.manager.shared_workspace_manager"),
     *     "translator"             = @DI\Inject("translator")
     * })
     */
    public function __construct(
        $ch,
        FormFactory $formFactory,
        InvoiceManager $invoiceManager,
        ObjectManager $om,
        ProductManager $productManager,
        RequestStack $requestStack,
        RouterInterface $router,
        SharedWorkspaceManager $sharedWorkspaceManager,
        TranslatorInterface $translator
    )
    {
        $this->ch = $ch;
        $this->formFactory = $formFactory;
        $this->invoiceManager = $invoiceManager;
        $this->om = $om;
        $this->productManager = $productManager;
        $this->request = $requestStack->getCurrentRequest();
        $this->router = $router;
        $this->sharedWorkspaceManager = $sharedWorkspaceManager;
        $this->translator = $translator;

        $this->friendRepo = $this->om->getRepository('Claroline\CoreBundle\Entity\Oauth\FriendRequest');
        $this->campusPlatform = $this->friendRepo->findOneByName($this->ch->getParameter('campusName'));
    }

    /**
     * @EXT\Route(
     *     "/admin/tool/index/page/{page}/max/{max}",
     *     name="formalibre_admin_shared_workspaces_admin_tool_index",
     *     defaults={"page"=1, "max"=20},
     * )
     * @EXT\Template
     */
    public function sharedWorkspacesAdminToolIndexAction($page = 1, $max = 20)
    {
        $sharedWorkspaces = $this->sharedWorkspaceManager->getSharedWorkspaces(true, $page, $max);
        $tempWorkspaces = $this->sharedWorkspaceManager->getAllWorkspacesDatas();
        $workspaces = array();
        $workspaceDatas = array();

        foreach ($tempWorkspaces as $workspace) {
            $id = $workspace['id'];
            $workspaces[$id] = $workspace;
        }

        foreach ($sharedWorkspaces as $sharedWorkspace) {
            $el = array();
            $remoteId = $sharedWorkspace->getRemoteId();
            $el['shared_workspace'] = $sharedWorkspace;

            if (isset($workspaces[$remoteId])) {
                $el['workspace'] = $workspaces[$remoteId];
            } else {
                $el['workspace'] = array('code' => 0, 'name' => null, 'expiration_date' => 0);
            }

            $sws = $this->sharedWorkspaceManager->getLastOrder($sharedWorkspace);

            if ($sws) {
                $el['product'] = $sws->getProduct();
            }

            $workspaceDatas[] = $el;
        }

        return array(
            'sharedWorkspaces' => $sharedWorkspaces,
            'workspaceDatas' => $workspaceDatas,
            'campusPlatform' => $this->campusPlatform,
            'max' => $max
        );
    }

    /**
     * @EXT\Route(
     *     "/shared/workspace/{sharedWorkspace}/owner/{user}/edit",
     *     name="formalibre_admin_shared_workspace_owner_edit",
     *     options={"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     */
    public function sharedWorkspaceOwnerEditAction(
        SharedWorkspace $sharedWorkspace,
        User $user
    )
    {
        $this->sharedWorkspaceManager->editSharedWorkspaceOwner($sharedWorkspace, $user);

        return new JsonResponse('success', 200);
    }

    /**
     * @EXT\Route(
     *     "/shared/workspace/create/form",
     *     name="formalibre_admin_shared_workspace_create_form",
     *     options={"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template()
     */
    public function sharedWorkspaceCreateFormAction()
    {
        $products = $this->productManager->getProductsBy(
            array('type' => 'SHARED_WS'),
            array('code' => 'ASC')
        );
        $product = (count($products) > 0) ? $products[0] : null;
        $workspaces = $this->sharedWorkspaceManager->getNonPersonalRemoteWorkspacesDatas();
        $form = $this->formFactory->create(
            new SharedWorkspaceType($this->translator, $product, $workspaces),
            new SharedWorkspace()
        );

        return array('form' => $form->createView());
    }

    /**
     * @EXT\Route(
     *     "/shared/workspace/create",
     *     name="formalibre_admin_shared_workspace_create",
     *     options={"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template("FormaLibreInvoiceBundle:AdminSharedWorkspaces:sharedWorkspaceCreateForm.html.twig")
     */
    public function sharedWorkspaceCreateAction()
    {
        $products = $this->productManager->getProductsBy(
            array('type' => 'SHARED_WS'),
            array('code' => 'ASC')
        );
        $product = (count($products) > 0) ? $products[0] : null;
        $workspaces = $this->sharedWorkspaceManager->getNonPersonalRemoteWorkspacesDatas();
        $sharedWorkspace = new SharedWorkspace();
        $form = $this->formFactory->create(
            new SharedWorkspaceType($this->translator, $product, $workspaces),
            $sharedWorkspace
        );
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            $owner = $sharedWorkspace->getOwner();
            $remoteUser = $this->sharedWorkspaceManager->getRemoteUser($owner->getUsername());

            if (isset($remoteUser['error']['code'])) {

                $remoteUser = $this->sharedWorkspaceManager->createRemoteUser($owner);
            }
            $remoteWorkspaceId = $form->get('remoteWorkspace')->getData();

            if (!is_null($remoteWorkspaceId)) {
                $sharedWorkspace->setRemoteId($remoteWorkspaceId);
                $this->om->persist($sharedWorkspace);
                $datas = 'success';
            } else {
                $name = $form->get('name')->getData();
                $code = $form->get('code')->getData();
                $datas = $this->sharedWorkspaceManager->createRemoteWorkspace(
                    $sharedWorkspace,
                    $owner,
                    $name,
                    $code
                );
            }

            if ($datas === 'success') {
                $product = $form->get('product')->getData();
                $priceSolutionId = $form->get('price')->getData();
                $priceSolutions = $product->getPriceSolutions();
                $priceSolution = $priceSolutions[$priceSolutionId];
                $chart = new Chart();
                $chart->setOwner($owner);
                $now = new \DateTime();
                $chart->setCreationDate($now);
                $chart->setValidationDate($now);
                $order = new Order();
                $order->setPriceSolution($priceSolution);
                $order->setProduct($product);
                $order->setSharedWorkspace($sharedWorkspace);
                $order->setChart($chart);
                $this->om->persist($order);
                $chart->addOrder($order);
                $this->om->persist($chart);
                $this->invoiceManager->create($chart, 'bank_transfer', true);

                return new RedirectResponse(
                    $this->router->generate('formalibre_admin_shared_workspaces_admin_tool_index')
                );
            } else {

                $form->addError(
                    new FormError(
                        $this->translator->trans('probably_invalid_code', array(), 'invoice')
                    )
                );
            }
        }

        return array('form' => $form->createView());
    }
}
