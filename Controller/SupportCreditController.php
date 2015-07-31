<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Claroline\CoreBundle\Entity\User;
use FormaLibre\InvoiceBundle\Manager\CreditSupportManager;
use FormaLibre\InvoiceBundle\Manager\ProductManager;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SupportCreditController extends Controller
{
    private $creditSupportManager;
    private $productManager;
    private $tokenStorage;

    /**
     * @DI\InjectParams({
     *     "creditSupportManager" = @DI\Inject("formalibre.manager.credit_support_manager"),
     *     "productManager"       = @DI\Inject("formalibre.manager.product_manager"),
     *     "tokenStorage"         = @DI\Inject("security.token_storage")
     * })
     */
    public function __construct(
        CreditSupportManager $creditSupportManager,
        ProductManager $productManager,
        TokenStorageInterface $tokenStorage
    )
    {
        $this->creditSupportManager = $creditSupportManager;
        $this->productManager = $productManager;
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

        $order = new Order();
        $chart = new Chart();
        $order->setChart($chart);
        $this->em->persist($chart);
        $this->em->persist($order);
        $this->em->flush();
        $products = $this->productManager->getProductsBy(
            array('type' => 'SUPPORT_CREDITS', 'isActivated' => true)
        );
        $forms = array();

        foreach ($products as $product) {
//            //now we generate the forms !
//            $form = $this->createForm(
//                new SharedWorkspaceForm(
//                    $product,
//                    $this->router,
//                    $this->em,
//                    $this->translator,
//                    $order,
//                    $this->vatManager
//                )
//            );
//            $forms[] = array(
//                'form' => $form->createView(),
//                'product' => $product,
//                'order' => $order
//            );
        }

        return array('forms' => $forms);
    }
}
