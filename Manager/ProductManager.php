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
     *     "om" = @DI\Inject("claroline.persistence.object_manager"),
     *     "vatManager" = @DI\Inject("formalibre.manager.vat_manager"),
     *     "logger" = @DI\Inject("logger"),
     *     "ch" = @DI\Inject("claroline.config.platform_config_handler"),
     *     "mailManager" = @DI\Inject("claroline.manager.mail_manager"),
     *     "container" = @DI\Inject("service_container")
     * })
     */
    public function __construct(
        ObjectManager $om,
        VATManager $vatManager,
        $logger,
        $ch,
        $mailManager,
        $container
    )
    {
        $this->om                        = $om;
        $this->productRepository         = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Product');
        $this->sharedWorkspaceRepository = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace');
        $this->logger                    = $logger;
        $this->vatManager                = $vatManager;
        $this->ch                        = $ch;
        $this->mailManager               = $mailManager;
        $this->container                 = $container;
    }

    public function getProductsByType($type)
    {
        return $this->productRepository->findByType($type);
    }
}
