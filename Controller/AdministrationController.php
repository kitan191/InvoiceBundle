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
use FormaLibre\InvoiceBundle\Form\PartnerType;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use FormaLibre\InvoiceBundle\Entity\Invoice;
use FormaLibre\InvoiceBundle\Entity\Partner;

/**
* @SEC\PreAuthorize("canOpenAdminTool('formalibre_admin_invoice')")
*/
class AdministrationController extends Controller
{
    /** @DI\Inject("formalibre.manager.invoice_manager") */
    private $invoiceManager;

    /** @DI\Inject("formalibre.manager.product_manager") */
    private $productManager;
    
    /** @DI\Inject("formalibre.manager.partner_manager") */
    private $partnerManager;

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

    /** @DI\Inject("router") */
    private $router;

    /** @DI\Inject("claroline.persistence.object_manager") */
    private $om;

    /**
     * @EXT\Route(
     *      "/admin/index",
     *      name="admin_invoice_index"
     * )
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {
        return $this->render(
            'FormaLibreInvoiceBundle:Administration:indexInvoices.html.twig',
            array()
        );
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
     *      "/admin/open/invoice/{page}/isFree/{isFree}/from/{from}/to/{to}",
     *      name="admin_invoice_open_invoice",
     *      defaults={"page"=1, "search"="", "isFree"="false", "from": "1420153200", "to": "1451689200"},
     *      options = {"expose"=true}
     * )
     * @EXT\Route(
     *      "/admin/open/invoice/{page}/search/{search}/{isFree}/from/{from}/to/{to}",
     *      name="admin_invoice_open_invoice_search",
     *      defaults={"page"=1, "isFree"="false", "from": "1420153200", "to": "1451689200"},
     *      options = {"expose"=true}
     * )
     * @EXT\Template
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function showInvoicesAction($page, $search, $isFree, $from, $to)
    {
        $boolFree = $isFree === "true" ? true: false;
        $query = $boolFree ?
            $this->invoiceManager->getInvoices(true, $search, $from, $to, true):
            $this->invoiceManager->getFree($search, $from, $to, true);
        $pager = $this->pagerFactory->createPager($query, $page, 25);

        return array(
            'pager' => $pager,
            'search' => $search,
            'isFree' => $isFree,
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

        $this->invoiceManager->validate($invoice);
        $route = $this->router->generate('admin_invoice_open_pending');

        return new RedirectResponse($route);
    }

    /**
     * @EXT\Route(
     *      "/export/{format}/from/{from}/to/{to}/isFree/{isFree}/search/{search}",
     *      name="formalibre_export_invoice",
     *      defaults={"format"="xls", "search" = ""}
     * )
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function exportAction($format, $search, $isFree, $from, $to)
    {
        //the admin is the only one able to do this.
        if (!$this->authorization->isGranted('ROLE_ADMIN')) {
            throw new \AccessDeniedException();
        }

        $boolFree = $isFree === "true" ? true: false;
        $invoices = $boolFree ?
        $this->invoiceManager->getInvoices(true, $search, $from, $to, false):
        $this->invoiceManager->getFree($search, $from, $to, false);

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
     *      "/product/{product}/form/edit",
     *      name="formalibre_product_edit_form",
     *      options = {"expose"=true}
     * )
     * @Security("has_role('ROLE_ADMIN')")
     * @EXT\Template
     *
     * @return Response
     */
    public function editProductFormAction(Product $product)
    {
        $type = $product->getType();
        $form = $this->createForm($this->productManager->getEditFormByType($type));

        return array('form' => $form->createView(), 'product' => $product);
    }

    /**
     * @EXT\Route(
     *      "/product/{product}/edit",
     *      name="formalibre_product_edit",
     *      options = {"expose"=true}
     * )
     * @Security("has_role('ROLE_ADMIN')")
     * @EXT\Template
     *
     * @return Response
     */
    public function editProductAction(Product $product)
    {
        $type = $product->getType();
        $form = $this->createForm($this->productManager->getEditFormByType($type));
        $form->handleRequest($this->get('request'));

        if ($form->isValid()) {
            //do some stuff
            $details = $product->getDetails();

            foreach ($form->getIterator() as $el) {
                //var_dump($el);
                $details[$el->getName()] = $form->get($el->getName())->getData();
            }

            $product->setDetails($details);
            $this->productManager->persist($product);

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
           'FormaLibreInvoiceBundle:Administration:editProductForm.html.twig',
           array('form' => $form->createView(), 'product' => $product)
       );
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
    public function addProductAction($type)
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

    /**
     * @EXT\Route(
     *      "/invoice/{invoice}/remove",
     *      name="formalibre_invoice_remove",
     *      options = {"expose"=true}
     * )
     * @Security("has_role('ROLE_ADMIN')")
     * @return Response
     */
    public function removeInvoice(Invoice $invoice)
    {
        $this->invoiceManager->remove($invoice);

        return new Response('success');
    }
    
    /**
     * @EXT\Route(
     *      "/partners/index",
     *      name="formalibre_partners_index",
     *      options = {"expose"=true}
     * )
     * @Security("has_role('ROLE_ADMIN')")
     * @EXT\Template
     */
    public function partnerIndexAction()
    {
        $partners = $this->partnerManager->findAll();
        
        return array('partners' => $partners);
    }
    
    /**
     * @EXT\Route(
     *      "/partner/form",
     *      name="formalibre_partner_create_form",
     *      options = {"expose"=true}
     * )
     * @Security("has_role('ROLE_ADMIN')")
     * @EXT\Template
     *
     * @return Response
     */
    public function addPartnerFormAction()
    {
        $form = $this->createForm(new PartnerType());

        return array('form' => $form->createView());
    }
    
    /**
     * @EXT\Route(
     *      "/partner/create",
     *      name="formalibre_partner_create",
     *      options = {"expose"=true}
     * )
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function addPartnerAction()
    {
        $form = $this->createForm(new PartnerType, new Partner());
        $form->handleRequest($this->get('request'));

        if ($form->isValid()) {
            $partner = $form->getData();
            $partner = $this->partnerManager->create($partner);

            return new JsonResponse(
                array(
                    'id' => $partner->getId(),
                    'code' => $partner->getCode(),
                    'name' => $partner->getName()
                )
            );
        }

        return $this->render(
            'FormaLibreInvoiceBundle:Administration:addPartnerForm.html.twig',
            array('form' => $form->createView())
        );
    }
    
    /**
     * @EXT\Route(
     *      "/show/partner/{partner}/chart/{page}",
     *      name="formalibre_admin_show_partner_charts",
     *      defaults={"page"=1},
     *      options = {"expose"=true}
     * )
     * @EXT\Template
     */
    public function showPartnerChartsAction(Partner $partner, $page)
    {
        $query = $this->partnerManager->getCharts($partner, true);
        $pager = $this->pagerFactory->createPager($query, $page, 25);

        return array(
            'pager' => $pager,
            'page' => $page,
            'partner' => $partner
        );
    }
    
        /**
     * @EXT\Route(
     *      "/partner/{partner}/activate/{isActivated}",
     *      name="formalibre_activate_partner",
     *      options = {"expose"=true}
     * )
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function activatePartnerAction(Partner $partner, $isActivated)
    {
        $boolActivated = $isActivated === 'true' ? true: false;
        $this->productManager->activatePartner($partner, $boolActivated);

        return new Response('success');
    }
}
