<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use FormaLibre\InvoiceBundle\Entity\Product;

class ProductController extends Controller
{
    /**
     * @EXT\Route(
     *      "/{product}/show",
     *      name="show_product"
     * )
     * @EXT\Template
     *
     * @return Response
     */
    public function showAction(Product $product)
    {
        return array('product' => $product);
    }

    /**
     * @EXT\Route(
     *     "/product/{product}/infos",
     *     name="product_infos",
     *     options={"expose"=true}
     * )
     *
     * @return Response
     */
    public function productInfosAction(Product $product)
    {
        $priceSolutions = $product->getPriceSolutions();
        $solutionsDatas = array();

        foreach ($priceSolutions as $solution) {
            $solutionsDatas[] = array(
                'id' => $solution->getId(),
                'duration' => $solution->getMonthDuration(),
                'price' => $solution->getPrice()
            );
        }

        $datas = array(
            'details' => $product->getDetails(),
            'priceSolutions' => $solutionsDatas
        );

        return new JsonResponse($datas, 200);
    }
}
