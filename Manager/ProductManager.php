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

    public function handleError(SharedWorkspace $sws, $serverOutput = null, $target = null)
    {
        $this->sendMailError($sws, $serverOutput, $target);

        throw new PaymentHandlingFailedException();
    }

    public function sendMailError(SharedWorkspace $sws, $serverOutput = null, $targetUrl = null)
    {
        $subject = 'Erreur lors de la gestion des espaces commerciaux.';
        $body = '<div> Un espace d\'activité a été payé par ' . $sws->getOwner()->getUsername() . ' </div>';
        $body = '<div> Son email est ' . $sws->getOwner()->getMail() . ' </div>';
        $body .= '<div> Une erreur est survenue après son payment </div>';
        $body .= '<div> La commande consiste en un espace dont la date d\'expiration est ' . $sws->getExpDate()->format(\DateTime::RFC2822) . '</div>';
        $body .= "<div> Nombre d'utilisateur: {$sws->getMaxUser()} - Nombre de ressource: {$sws->getMaxRes()} - Taille maximale: {$sws->getMaxStorage()} </div>";
        $to = $this->ch->getParameter('formalibre_commercial_email_support');

        if ($targetUrl) {
            $body .= "<div>target: {$targetUrl}</div>";
        }

        if ($serverOutput) {
            $body .= "<div>{$serverOutput}</div>";
        }

        $this->mailManager->send(
            $subject,
            $body,
            array(),
            null,
            array('to' => array($to))
        );
    }

    public function getByCode($code)
    {
        return $this->productRepository->findOneByCode($code);
    }
}
