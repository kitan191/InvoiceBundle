<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Entity\User;
use FormaLibre\InvoiceBundle\Form\SharedWorkspaceForm;
use FormaLibre\InvoiceBundle\Entity\Product;
use FormaLibre\InvoiceBundle\Entity\Order;
use FormaLibre\InvoiceBundle\Entity\Chart;
use FormaLibre\InvoiceBundle\Entity\PriceSolution;
use FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace;
use FormaLibre\InvoiceBundle\Form\WorkspaceNameEditType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SharedWorkspaceController extends Controller
{
    private $authorization;
    private $ch;
    private $em;
    private $formFactory;
    private $invoiceManager;
    private $paymentManager;
    private $productManager;
    private $request;
    private $router;
    private $session;
    private $sharedWorkspaceManager;
    private $tokenStorage;
    private $translator;
    private $vatManager;
    private $friendRepo;
    private $campusPlatform;

    /**
     * @DI\InjectParams({
     *     "authorization"          = @DI\Inject("security.authorization_checker"),
     *     "ch"                     = @DI\Inject("claroline.config.platform_config_handler"),
     *     "em"                     = @DI\Inject("doctrine.orm.entity_manager"),
     *     "formFactory"            = @DI\Inject("form.factory"),
     *     "invoiceManager"         = @DI\Inject("formalibre.manager.invoice_manager"),
     *     "paymentManager"         = @DI\Inject("formalibre.manager.payment_manager"),
     *     "productManager"         = @DI\Inject("formalibre.manager.product_manager"),
     *     "request"                = @DI\Inject("request"),
     *     "router"                 = @DI\Inject("router"),
     *     "session"                = @DI\Inject("session"),
     *     "sharedWorkspaceManager" = @DI\Inject("formalibre.manager.shared_workspace_manager"),
     *     "tokenStorage"           = @DI\Inject("security.token_storage"),
     *     "translator"             = @DI\Inject("translator"),
     *     "vatManager"             = @DI\Inject("formalibre.manager.vat_manager")
     * })
     */
    public function __construct(
        $authorization,
        $ch,
        $em,
        $formFactory,
        $invoiceManager,
        $paymentManager,
        $productManager,
        $request,
        $router,
        $session,
        $sharedWorkspaceManager,
        $tokenStorage,
        $translator,
        $vatManager
    )
    {
        $this->authorization = $authorization;
        $this->ch = $ch;
        $this->em = $em;
        $this->formFactory = $formFactory;
        $this->invoiceManager = $invoiceManager;
        $this->paymentManager = $paymentManager;
        $this->productManager = $productManager;
        $this->request = $request;
        $this->router = $router;
        $this->session = $session;
        $this->sharedWorkspaceManager = $sharedWorkspaceManager;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->vatManager = $vatManager;
        $this->friendRepo = $this->em->getRepository('Claroline\CoreBundle\Entity\Oauth\FriendRequest');
        $this->campusPlatform = $this->friendRepo->findOneByName($this->ch->getParameter('campusName'));
    }

    /**
     * @EXT\Route(
     *      "/products/form",
     *      name="workspace_products_form"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function formsAction()
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $hasFreeTest = true;

        //it would be better if I was able to avoid creating a new order everytime...
        if ($user !== 'anon.' && !$this->sharedWorkspaceManager->hasFreeTestMonth($user)) {
            $hasFreeTest = false;
        }

        $products = $this->get('formalibre.manager.product_manager')
            ->getProductsBy(array('type' => 'SHARED_WS', 'isActivated' => true));
        $forms = array();

        foreach ($products as $product) {
            //now we generate the forms !
            $form = $this->createForm(new SharedWorkspaceForm($product));
            $forms[] = array(
                'form' => $form->createView(),
                'product' => $product
            );
        }

        return array('forms' => $forms, 'hasFreeTest' => $hasFreeTest);
    }

    /**
     * @EXT\Template
     */
    public function iframeFormsAction()
    {
        return $this->formsAction();
    }

    /**
     * @EXT\Route(
     *      "/payment/workspace/submit/{product}",
     *      name="workspace_product_payment_submit"
     * )
     *
     * @param $swsId the sharedWorkspaceId if it already exists (otherwise, if it's 0, we'll create a new one)
     * @param $chartId the chartId if it already exists (otherwise, if it's 0, we'll create a new one)
     * @return Response
     */
    public function addOrderToChartAction(Product $product)
    {
        //check it wasn't already submitted
        if (false) {
            $content = $this->renderView(
                'FormaLibreInvoiceBundle:errors:orderAlreadySubmitedException.html.twig'
            );

            return new Response($content);
        }

        if ($this->session->has('form_price_data')) {
            $priceSolution = $this->session->get('form_price_data');
            $this->session->remove('form_price_data');
        }

        $form = $this->createForm(new SharedWorkspaceForm($product    ));
        $form->handleRequest($this->request);

        if ($form->isValid()) {
                //do that stuff here
            if (!$this->authorization->isGranted('ROLE_USER')) {
                $this->session->set('form_price_data', $form->get('price')->getData());
                $redirectRoute =  $this->router->generate('workspace_product_payment_submit', array(
                    'order' => $order->getId(),
                    'product' => $product->getId(),
                    'chart' => $chart->getId()
                ));
                $this->session->set('redirect_route', $redirectRoute);
                $route = $this->router->generate('claro_security_login', array());

                return new RedirectResponse($route);
            }

            $priceSolution = $form->get('price')->getData();
        }

        $order = new Order();
        $chart = new Chart();
        $order->setChart($chart);
        $priceSolution = $this->em->getRepository('FormaLibreInvoiceBundle:PriceSolution')->find($priceSolution->getId());
        $order->setProduct($product);
        $chart->setOwner($this->tokenStorage->getToken()->getUser());
        $chart->setIpAdress($_SERVER['REMOTE_ADDR']);
        $order->setPriceSolution($priceSolution);
        $order->setChart($chart);
        $this->em->persist($chart);
        $this->em->persist($order);
        $this->em->flush();

        return new RedirectResponse($this->router->generate(
            'chart_payment_pending',
            array('chart' => $order->getChart()->getId()), true
        ));


        throw new \Exception('Errors were found: ' . $form->getErrorsAsString());
    }

    /**
     * @EXT\Route(
     *      "/renew/test/workspace/{remoteId}",
     *      name="renew_test_workspace"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function renewWorkspaceAction($remoteId)
    {
        $sws = $this->em->getRepository('FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace')
            ->findOneByRemoteId($remoteId);

        if (!$sws) {
            throw new \Exception('unknown remote id');
        }

        if ($this->tokenStorage->getToken()->getUser() !== $sws->getOwner()) {
            throw new AccessDeniedException();
        }

        $order = new Order();
        $chart = new Chart();
        $order->setChart($chart);
        $order->setSharedWorkspace($sws);
        $product = $this->sharedWorkspaceManager->getLastOrder($sws)->getProduct();
        $order->setProduct($product);
        $this->em->persist($chart);
        $this->em->persist($order);
        $this->em->flush();
        $formType = new SharedWorkspaceForm($product);
        $form = $this->createForm($formType)->createView();
        $workspace = $this->sharedWorkspaceManager->getWorkspaceData($sws);

        return array(
            'form' => $form,
            'chart' => $chart,
            'product' => $product,
            'order' => $order,
            'workspace' => $workspace
        );
    }

    /**
     * @EXT\Route(
     *      "/free_test/{product}",
     *      name="formalibre_free_test_workspace"
     * )
     * @return Response
     */
    public function createFreeTestWorkspace(Product $product)
    {
        if (!$this->authorization->isGranted('ROLE_USER')) {
            $redirectRoute =  $this->router->generate(
                'formalibre_free_test_workspace',
                array('product' => $product->getId())
            );
            $this->session->set('redirect_route', $redirectRoute);
            $route = $this->router->generate('claro_security_login', array());

            return new RedirectResponse($route);
        }

        $user = $this->tokenStorage->getToken()->getUser();

        if (!$this->sharedWorkspaceManager->hasFreeTestMonth($user)) {
            $content = $this->renderView(
                'FormaLibreInvoiceBundle:errors:freeTestMonthUsedException.html.twig'
            );

            return new Response($content);;
        }

        $chart = new Chart();
        $ps = $this->productManager->getPriceSolution($product, 1);
        $order = new Order();
        $chart->setOwner($user);
        $chart->setIpAdress($_SERVER['REMOTE_ADDR']);
        $order->setPriceSolution($ps);
        $order->setProduct($product);
        $chart->addOrder($order);
        $order->setChart($chart);
        $this->em->persist($order);
        $this->em->persist($chart);
        $this->sharedWorkspaceManager->useFreeTestMonth($user);
        $invoice = $this->invoiceManager->create($chart);
        $this->invoiceManager->validate($invoice);
        $invoice->setPaymentSystemName('none');
        $this->em->persist($invoice);
        $this->em->flush();

        return new RedirectResponse($this->router->generate('claro_desktop_open', array()));
    }

    /**
     * @EXT\Route(
     *      "/my/shared/workspaces/desktop/tool/index",
     *      name="formalibre_my_shared_workspaces_desktop_tool_index"
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template
     *
     * @return Response
     */
    public function mySharedWorkspacesDesktopToolIndexAction(User $authenticatedUser)
    {
        $sharedWorkspaces = $this->sharedWorkspaceManager
            ->getSharedWorkspaceByUser($authenticatedUser);
        $workspaceDatas = array();

        foreach ($sharedWorkspaces as $sharedWorkspace) {
            $el = array();
            $workspace = $this->sharedWorkspaceManager->getWorkspaceData($sharedWorkspace);
            $additionalDatas = $this->sharedWorkspaceManager->getWorkspaceAdditionalDatas($sharedWorkspace);
            $el['shared_workspace'] = $sharedWorkspace;

            if ($workspace) {
                $el['workspace'] = $workspace;
            } else {
                $el['workspace'] = array('code' => 0, 'name' => null, 'expiration_date' => 0);
            }

            if ($additionalDatas) {
                $el['workspace_additional_datas'] = $additionalDatas;
            } else {
                $el['workspace_additional_datas'] = array(
                    'used_storage' => -1,
                    'nb_users' => -1,
                    'nb_resources' => -1
                );
            }

            $sws = $this->sharedWorkspaceManager->getLastOrder($sharedWorkspace);

            if ($sws) {
                $el['product'] = $sws->getProduct();
            }

            $workspaceDatas[] = $el;
        }

        return array('workspaceDatas' => $workspaceDatas, 'campusPlatform' => $this->campusPlatform);
    }

    /**
     * @EXT\Route(
     *     "/shared/workspace/{sharedWorkspace}/remote/name/edit/form",
     *     name="formalibre_shared_workspace_name_edit_form",
     *     options={"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template("FormaLibreInvoiceBundle:SharedWorkspace:sharedWorkspaceNameEditModalForm.html.twig")
     */
    public function sharedWorkspaceNameEditFormAction(
        User $authenticatedUser,
        SharedWorkspace $sharedWorkspace
    )
    {
        $this->checkSharedWorkspaceEditionAccess($authenticatedUser, $sharedWorkspace);
        $workspace = $this->sharedWorkspaceManager->getWorkspaceData($sharedWorkspace);
        $form = $this->formFactory->create(new WorkspaceNameEditType($workspace['name']));

        return array(
            'form' => $form->createView(),
            'sharedWorkspace' => $sharedWorkspace
        );
    }

    /**
     * @EXT\Route(
     *     "/shared/workspace/{sharedWorkspace}/remoted/name/edit",
     *     name="formalibre_shared_workspace_name_edit",
     *     options={"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template("FormaLibreInvoiceBundle:SharedWorkspace:sharedWorkspaceNameEditModalForm.html.twig")
     */
    public function sharedWorkspaceNameEditAction(
        User $authenticatedUser,
        SharedWorkspace $sharedWorkspace
    )
    {
        $this->checkSharedWorkspaceEditionAccess($authenticatedUser, $sharedWorkspace);
        $workspace = $this->sharedWorkspaceManager->getWorkspaceData($sharedWorkspace);
        $form = $this->formFactory->create(new WorkspaceNameEditType($workspace['name']));
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            $workspaceName = $form->get('name')->getData();
            $datas = $this->sharedWorkspaceManager->editShareWorkspaceRemoteName(
                $workspace,
                $workspaceName
            );

            return new JsonResponse($datas, 200);
        } else {

            return array(
                'form' => $form->createView(),
                'sharedWorkspace' => $sharedWorkspace
            );
        }
    }

    private function checkSharedWorkspaceEditionAccess(
        User $user,
        SharedWorkspace $sharedWorkspace
    )
    {
        if ($user->getId() !== $sharedWorkspace->getOwner()->getId()) {

            throw new AccessDeniedException();
        }
    }
}
