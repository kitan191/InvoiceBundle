<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Claroline\CoreBundle\Persistence\ObjectManager;
use FormaLibre\InvoiceBundle\Entity\Chart;
use FormaLibre\InvoiceBundle\Entity\Order;
use FormaLibre\InvoiceBundle\Manager\CreditSupportManager;
use FormaLibre\InvoiceBundle\Manager\ProductManager;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SupportCreditController extends Controller
{
    private $authorization;
    private $creditSupportManager;
    private $om;
    private $productManager;
    private $request;
    private $router;
    private $session;
    private $tokenStorage;

    /**
     * @DI\InjectParams({
     *     "authorization"        = @DI\Inject("security.authorization_checker"),
     *     "creditSupportManager" = @DI\Inject("formalibre.manager.credit_support_manager"),
     *     "om"                   = @DI\Inject("claroline.persistence.object_manager"),
     *     "productManager"       = @DI\Inject("formalibre.manager.product_manager"),
     *     "requestStack"         = @DI\Inject("request_stack"),
     *     "router"               = @DI\Inject("router"),
     *     "session"              = @DI\Inject("session"),
     *     "tokenStorage"         = @DI\Inject("security.token_storage")
     * })
     */
    public function __construct(
        AuthorizationCheckerInterface $authorization,
        CreditSupportManager $creditSupportManager,
        ObjectManager $om,
        ProductManager $productManager,
        RequestStack $requestStack,
        RouterInterface $router,
        SessionInterface $session,
        TokenStorageInterface $tokenStorage
    )
    {
        $this->authorization = $authorization;
        $this->creditSupportManager = $creditSupportManager;
        $this->om = $om;
        $this->productManager = $productManager;
        $this->request = $requestStack->getCurrentRequest();
        $this->router = $router;
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @EXT\Route(
     *      "/support/credits/products/purchase/form",
     *      name="formalibre_support_credits_products_purchase_form"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function supportCreditsPurchaseFormAction()
    {
        $user = $this->tokenStorage->getToken()->getUser();

        $products = $this->productManager->getProductsBy(
            array('type' => 'SUPPORT_CREDITS', 'isActivated' => true)
        );

        return array('products' => $products);
    }

    /**
     * @EXT\Route(
     *      "/support/credits/products/purchase",
     *      name="formalibre_support_credits_products_purchase"
     * )
     * @EXT\Template("")
     *
     * @return Response
     */
    public function supportCreditsPurchaseAction()
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $productId = null;

        if ($this->session->has('support_credit_purchase_form_product_id')) {
            $productId = $this->session->get('support_credit_purchase_form_product_id');
            $this->session->remove('support_credit_purchase_form_product_id');
        }

        $datas = $this->request->request->all();

        if (!is_null($productId) ||
            (isset($datas['support_credit_purchase_form']) &&
            isset($datas['support_credit_purchase_form']['product']))) {

            $productId = is_null($productId) ?
                intval($datas['support_credit_purchase_form']['product']) :
                $productId;

            if (!$this->authorization->isGranted('ROLE_USER')) {
                $this->session->set('support_credit_purchase_form_product_id', $productId);

                $redirectRoute =
                    $this->router->generate('formalibre_support_credits_products_purchase');
                $this->session->set('redirect_route', $redirectRoute);
                $route = $this->router->generate('claro_security_login', array());

                return new RedirectResponse($route);
            }

            $product = $this->productManager->getProductById($productId);

            if (is_null($product)) {

                return new RedirectResponse(
                    $this->router->generate('formalibre_support_credits_products_purchase_form')
                );
            }

            $priceSolutions = $product->getPriceSolutions();
            $priceSolution = $priceSolutions[0];

            $order = new Order();
            $chart = new Chart();
            $order->setChart($chart);
            $order->setProduct($product);
            $chart->setOwner($user);
            $chart->setIpAdress($_SERVER['REMOTE_ADDR']);
            $order->setPriceSolution($priceSolution);
            $order->setChart($chart);
            $this->om->persist($chart);
            $this->om->persist($order);
            $this->om->flush();

            return new RedirectResponse($this->router->generate(
                'chart_payment_pending',
                array('chart' => $chart->getId()), true
            ));

//            return new RedirectResponse(
//                $this->router->generate('formalibre_support_credits_products_purchase_thanks')
//            );
        } else {

            return new RedirectResponse(
                $this->router->generate('formalibre_support_credits_products_purchase_form')
            );
        }
    }

    /**
     * @EXT\Route(
     *      "/support/credits/products/purchase/thanks",
     *      name="formalibre_support_credits_products_purchase_thanks"
     * )
     * @EXT\Template()
     *
     * @return Response
     */
    public function supportCreditsPurchaseThanksAction()
    {
        return array();
    }
}
