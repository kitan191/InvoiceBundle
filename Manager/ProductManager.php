<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\User;
use FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace;

/**
* @DI\Service("formalibre.manager.product_manager")
*/
class ProductManager
{
    private $om;
    private $productRepository;

    /**
     * @DI\InjectParams({
     *     "om" = @DI\Inject("claroline.persistence.object_manager")
     * })
     */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
        $this->productRepository = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Product');
        $this->sharedWorkspaceRepository = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace');
    }

    public function getProductsByType($type)
    {
        return $this->productRepository->findByType($type);
    }

    public function addSharedWorkspace(
        User $user,
        $maxUser,
        $maxRes,
        $maxStorage,
        $duration
    )
    {
        //get the duration right

        $sws = new SharedWorkspace();
        $sws->setOwner($user);
        $sws->setMaxUser($maxUser);
        $sws->setMaxRes($maxRes);
        $sws->setMaxStorage($maxStorage);
        $sws->setExpDate(new \DateTime());
        $this->om->persist($sws);
        $this->om->flush();
    }

    public function getSharedWorkspaceByUser(User $user)
    {
        return $this->sharedWorkspaceRepository->findByOwner($user);
    }
}
