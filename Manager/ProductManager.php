<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Manager\MailManager;
use Claroline\CoreBundle\Entity\User;
use FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace;
use FormaLibre\InvoiceBundle\Entity\Product;
use FormaLibre\InvoiceBundle\Entity\PriceSolution;
use FormaLibre\InvoiceBundle\Entity\Order;
use FormaLibre\InvoiceBundle\Entity\FreeTestMonthUsage;
use FormaLibre\InvoiceBundle\Manager\Exception\PaymentHandlingFailedException;

/**
* @DI\Service("formalibre.manager.product_manager")
*/
class ProductManager
{
    private $om;
    private $productRepository;
    private $targetPlatformUrl;
    private $logger;
    private $vatManager;
    private $ch;
    private $mailManager;
    private $container;

    /**
     * @DI\InjectParams({
     *     "om" = @DI\Inject("claroline.persistence.object_manager")
     * })
     */
    public function __construct(
        ObjectManager $om
    )
    {
        $this->om                        = $om;
        $this->productRepository         = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Product');
        $this->priceSolutionRepository   = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\PriceSolution');
    }

    public function getProductsByType($type)
    {
        return $this->productRepository->findByType($type);
    }

    public function getPriceSolution(Product $product, $duration)
    {
        return $this->priceSolutionRepository->findOneBy(array('product' => $product, 'monthDuration' => $duration));
    }
}
