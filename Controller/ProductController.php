<?php

namespace FormaLibre\InvoiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
}
