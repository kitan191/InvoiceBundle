<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Manager\MailManager;
use FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace;
use FormaLibre\InvoiceBundle\Entity\Product;
use FormaLibre\InvoiceBundle\Entity\PriceSolution;
use FormaLibre\InvoiceBundle\Manager\Exception\PaymentHandlingFailedException;
use FormaLibre\InvoiceBundle\Form\SharedWorkspaceCreationForm;

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

    public function getProductsBy(array $array)
    {
        return $this->productRepository->findBy($array);
    }

    //there is no const array yet in php =/ maybe it's 5.5 or so. I don't rememeber.
    public function getAvailableProductsType()
    {
        return array(
            'SHARED_WS' => 'shared_workspace',
            'TRAINING' => 'training',
            'CREDIT_SUPPORT' => 'credit_support',
            'TECHNICAL_SUPPORT' => 'technical_support'
        );
    }

    public function activateProduct(Product $product, $boolActivated)
    {
        $product->setIsActivated($boolActivated);
        $this->om->persist($product);
        $this->om->flush();
    }

    public function getFormByType($type)
    {
        switch ($type) {
            case 'SHARED_WS':
                $form = new SharedWorkspaceCreationForm(); break;
            default: throw new \Exception('Unknown type.');
        }

        return $form;
    }

    public function createFromFormByType($form, $type)
    {
        $code = $form->get('code')->getData();

        switch ($type) {
            case 'SHARED_WS':
                $product = $this->createSharedWorkspace(
                    $code,
                    $type,
                    $form->get('max_users')->getData(),
                    $form->get('max_storage')->getData(),
                    $form->get('max_resources')->getData()
                );
                break;
            default: throw new \Exception('Unknown type.');
        }

        return $product;
    }

    public function createSharedWorkspace(
        $code,
        $type,
        $maxUsers,
        $maxStorage,
        $maxResources
    ) {
        $details = array(
            'max_users' => $maxUsers,
            'max_resources' => $maxResources,
            'max_storage' => $maxStorage
        );

        $product = new Product();
        $product->setCode($code);
        $product->setType($type);
        $product->setDetails($details);
        $this->om->persist($product);
        $this->om->flush();

        return $product;
    }

    public function remove(Product $product)
    {
        $this->om->remove($product);
        $this->om->flush();
    }
}
