<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\Payment\CoreBundle\Entity\Payment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use JMS\SecurityExtraBundle\Annotation as SEC;
use FormaLibre\InvoiceBundle\Entity\Order;
use FormaLibre\InvoiceBundle\Entity\Product;
use FormaLibre\InvoiceBundle\Entity\PriceSolution;
use FormaLibre\InvoiceBundle\Form\PriceSolutionForm;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use FormaLibre\InvoiceBundle\Entity\Invoice;

/**
* @SEC\PreAuthorize("canOpenAdminTool('formalibre_admin_invoice')")
*/
class AdministrationController extends Controller
{
    /** @DI\Inject("formalibre.manager.invoice_manager") */
    private $invoiceManager;

    /** @DI\Inject("formalibre.manager.product_manager") */
    private $productManager;

    /** @DI\Inject("claroline.pager.pager_factory") */
    private $pagerFactory;

    /** @DI\Inject("%claroline.param.pdf_directory%") */
    private $pdfDirectory;

    /** @DI\Inject("security.authorization_checker") */
    private $authorization;

    /** @DI\Inject("formalibre.manager.shared_workspace_manager") */
    private $sharedWorkspaceManager;

    /** @DI\Inject("formalibre.manager.price_solution_manager") */
    private $priceSolutionManager;

    /** @DI\Inject("payment.plugin_controller") */
    private $ppc;

    /** @DI\Inject("router") */
    private $router;

    /** @DI\Inject("claroline.persistence.object_manager") */
    private $om;

    /**
     * @EXT\Route(
     *      "/admin/index",
     *      name="admin_invoice_index"
     * )
     * @EXT\Template
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @EXT\Route(
     *      "/admin/open/pending/{page}",
     *      name="admin_invoice_open_pending",
     *      defaults={"page"=1, "search"=""},
     *      options = {"expose"=true}
     * )
     *
     * @EXT\Route(
     *      "/admin/open/pending/{page}/search/{search}",
     *      name="admin_invoice_open_pending_search",
     *      defaults={"page"=1},
     *      options = {"expose"=true}
     * )
     * @EXT\Template
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function openPendingAction($page, $search)
    {
        $pager = $this->pagerFactory->createPager($this->invoiceManager->getUnpayed(true), $page, 25);

        return array('pager' => $pager, 'search' => $search);
    }

    /**
     * @EXT\Route(
     *      "/admin/open/invoice/{page}/isPayed/{isPayed}/from/{from}/to/{to}",
     *      name="admin_invoice_open_invoice",
     *      defaults={"page"=1, "search"="", "isPayed"="true", "from": "1420153200", "to": "1451689200"},
     *      options = {"expose"=true}
     * )
     * @EXT\Route(
     *      "/admin/open/invoice/{page}/search/{search}/{isPayed}/from/{from}/to/{to}",
     *      name="admin_invoice_open_invoice_search",
     *      defaults={"page"=1, "isPayed"="true", "from": "1420153200", "to": "1451689200"},
     *      options = {"expose"=true}
     * )
     * @EXT\Template
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function showInvoicesAction($page, $search, $isPayed, $from, $to)
    {
        $boolPayed = $isPayed === "true" ? true: false;
        $query = $this->invoiceManager->getInvoices($boolPayed, $search, $from, $to, true);
        $pager = $this->pagerFactory->createPager($query, $page, 25);

        return array(
            'pager' => $pager,
            'search' => $search,
            'isPayed' => $isPayed,
            'page' => $page,
            'from' => $from,
            'to' => $to
        );
    }

    /**
     * @EXT\Route(
     *      "/bank_transfer_validate/{invoice}",
     *      name="formalibre_validate_bank_transfer"
     * )
     * @EXT\Template
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function validateBankTransferAction(Invoice $invoice)
    {
        //the admin is the only one able to do this.
        if (!$this->authorization->isGranted('ROLE_ADMIN')) {
            throw new \AccessDeniedException();
        }

        $payments = $invoice->getChart()->getPaymentInstruction()->getPayments();
        $payment = $payments[0];
        $this->ppc->approve($payment, $invoice->getTotalAmount());
        $this->invoiceManager->validate($invoice);
        $route = $this->router->generate('admin_invoice_open_pending');

        return new RedirectResponse($route);
    }

    /**
     * @EXT\Route(
     *      "/export/{format}/from/{from}/to/{to}/isPayed/{isPayed}/search/{search}",
     *      name="formalibre_export_invoice",
     *      defaults={"format"="xls", "search" = ""}
     * )
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function exportAction($format, $search, $isPayed, $from, $to)
    {
        $boolPayed = $isPayed === "true" ? true: false;

        //the admin is the only one able to do this.
        if (!$this->authorization->isGranted('ROLE_ADMIN')) {
            throw new \AccessDeniedException();
        }

        $invoices = $this->invoiceManager->getInvoices($boolPayed, $search, $from, $to, false);
        $file = $this->invoiceManager->export(
            $invoices, $this->container->get('claroline.exporter.' . $format)
        );

        $response = new StreamedResponse();

        $response->setCallBack(
            function () use ($file) {
                readfile($file);
            }
        );
        $response->headers->set('Content-Transfer-Encoding', 'octet-stream');
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition', 'attachment; filename=users.' . $format);

        switch ($format) {
            case 'csv': $response->headers->set('Content-Type', 'text/csv'); break;
            case 'xls': $response->headers->set('Content-Type', 'application/vnd.ms-excel'); break;
        }

        $response->headers->set('Connection', 'close');

        return $response;
    }

    /******************************************/
    /*************** PRODUCTS *****************/
    /******************************************/

    /**
     * @EXT\Route(
     *      "/product/index",
     *      name="formalibre_product_index"
     * )
     * @Security("has_role('ROLE_ADMIN')")
     * @EXT\Template

     * @return Response
     */
    public function productIndexAction()
    {
        $products = $this->productManager->getAvailableProductsType();

        return array('products' => $products);
    }

    /**
     * @EXT\Route(
     *      "/product/list/{code}",
     *      name="formalibre_show_products"
     * )
     * @Security("has_role('ROLE_ADMIN')")
     * @EXT\Template
     *
     * @return Response
     */
    public function productsListAction($code)
    {
        $products = $this->productManager->getProductsByType($code);

        return array('products' => $products, 'code' => $code);
    }

    /**
     * @EXT\Route(
     *      "/product/{product}/activate/{isActivated}",
     *      name="formalibre_activate_products",
     *      options = {"expose"=true}
     * )
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function activateProductAction(Product $product, $isActivated)
    {
        $boolActivated = $isActivated === 'true' ? true: false;
        $this->productManager->activateProduct($product, $boolActivated);

        return new Response('success');
    }

    /**
     * @EXT\Route(
     *      "/price_solution/product/{product}/form",
     *      name="formalibre_price_solution_form",
     *      options = {"expose"=true}
     * )
     * @Security("has_role('ROLE_ADMIN')")
     * @EXT\Template
     * @return Response
     */
    public function priceSolutionCreationFormAction(Product $product)
    {
        $form = $this->createForm(new PriceSolutionForm());

        return array('form' => $form->createView(), 'product' => $product);
    }

    /**
     * @EXT\Route(
     *      "/price_solution/product/{product}/create",
     *      name="formalibre_price_solution_create",
     *      options = {"expose"=true}
     * )
     * @Security("has_role('ROLE_ADMIN')")

     * @return Response
     */
    public function addPriceSolutionAction(Product $product)
    {
        $form = $this->createForm(new PriceSolutionForm());
        $form->handleRequest($this->get('request'));

        if ($form->isValid()) {
            $priceSolution = $this->priceSolutionManager->create(
                $product,
                $form->get('price')->getData(),
                $form->get('monthDuration')->getData()
            );

            return new JsonResponse(
                array(
                    'price' => $priceSolution->getPrice(),
                    'monthDuration' => $priceSolution->getMonthDuration(),
                    'id' => $priceSolution->getId(),
                    'product_id' => $product->getId()
                )
            );
        }

       return $this->render(
           'FormaLibreInvoiceBundle:Administration:priceSolutionCreationForm.html.twig',
           array('form' => $form->createView(), 'product' => $product)
       );
    }

    /**
     * @EXT\Route(
     *      "/price_solution/{priceSolution}/remove",
     *      name="formalibre_price_solution_remove",
     *      options = {"expose"=true}
     * )
     * @Security("has_role('ROLE_ADMIN')")

     * @return Response
     */
    public function removePriceSolution(PriceSolution $priceSolution)
    {
        $this->priceSolutionManager->remove($priceSolution);

        return new Response('success');
    }

    /**
     * @EXT\Route(
     *      "/product/{type}/form",
     *      name="formalibre_product_create_form",
     *      options = {"expose"=true}
     * )
     * @Security("has_role('ROLE_ADMIN')")
     * @EXT\Template
     *
     * @return Response
     */
    public function addProductFormAction($type)
    {
        $form = $this->createForm($this->productManager->getFormByType($type));

        return array('form' => $form->createView(), 'type' => $type);
    }

    /**
     * @EXT\Route(
     *      "/product/{type}/create",
     *      name="formalibre_product_create",
     *      options = {"expose"=true}
     * )
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function addProductForm($type)
    {
        $form = $this->createForm($this->productManager->getFormByType($type));
        $form->handleRequest($this->get('request'));

        if ($form->isValid()) {
            $product = $this->productManager->createFromFormByType($form, $type);

            return new JsonResponse(
                array(
                    'id' => $product->getId(),
                    'code' => $product->getCode(),
                    'type' => $product->getType(),
                    'details' => $product->getDetails(),
                    'priceSolutions' => array()
                )
            );
        }

        return $this->render(
            'FormaLibreInvoiceBundle:Administration:addProductForm.html.twig',
            array('form' => $form->createView(), 'type' => $type)
        );
    }

    /**
     * @EXT\Route(
     *      "/product/{product}/remove",
     *      name="formalibre_product_remove",
     *      options = {"expose"=true}
     * )
     * @Security("has_role('ROLE_ADMIN')")

     * @return Response
     */
    public function removeProduct(Product $product)
    {
        $this->productManager->remove($product);

        return new Response('success');
    }
}
