<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Manager\MailManager;
use Claroline\CoreBundle\Manager\OauthManager;
use Claroline\CoreBundle\Manager\ApiManager;
use FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace;
use FormaLibre\InvoiceBundle\Entity\Product;
use FormaLibre\InvoiceBundle\Entity\PriceSolution;
use FormaLibre\InvoiceBundle\Entity\Order;
use FormaLibre\InvoiceBundle\Entity\FreeTestMonthUsage;
use FormaLibre\InvoiceBundle\Manager\Exception\PaymentHandlingFailedException;

/**
* @DI\Service("formalibre.manager.shared_workspace_manager")
*/
class SharedWorkspaceManager
{
    private $om;
    private $productRepository;
    private $targetPlatformUrl;
    private $logger;
    private $vatManager;
    private $ch;
    private $container;
    private $mailManager;
    private $templating;
    private $mailer;
    private $translator;

    /**
     * @DI\InjectParams({
     *     "om"           = @DI\Inject("claroline.persistence.object_manager"),
     *     "vatManager"   = @DI\Inject("formalibre.manager.vat_manager"),
     *     "logger"       = @DI\Inject("logger"),
     *     "ch"           = @DI\Inject("claroline.config.platform_config_handler"),
     *     "container"    = @DI\Inject("service_container"),
     *     "mailManager"  = @DI\Inject("claroline.manager.mail_manager"),
     *     "cryptography" = @DI\Inject("formalibre.manager.cryptography_manager"),
     *     "templating"   = @DI\Inject("templating"),
     *     "mailer"       = @DI\Inject("claroline.manager.mail_manager"),
     *     "translator"   = @DI\Inject("translator"),
     *     "oauthManager" = @DI\Inject("claroline.manager.oauth_manager"),
     *     "apiManager"   = @DI\Inject("claroline.manager.api_manager")
     * })
     */
    public function __construct(
        ObjectManager $om,
        VATManager $vatManager,
        $logger,
        $ch,
        $container,
        MailManager $mailManager,
        CryptographyManager $cryptography,
        $templating,
        MailManager $mailManager,
        $translator,
        OauthManager $oauthManager,
        ApiManager $apiManager
    )
    {
        $this->om                        = $om;
        $this->productRepository         = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Product');
        $this->sharedWorkspaceRepository = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace');
        $this->logger                    = $logger;
        $this->vatManager                = $vatManager;
        $this->ch                        = $ch;
        $this->container                 = $container;
        $this->mailManager               = $mailManager;
        $this->crypto                    = $cryptography;
        $this->templating                = $templating;
        $this->mailManager               = $mailManager;
        $this->translator                = $translator;
        $this->oauthManager              = $oauthManager;
        $this->apiManager                = $apiManager;
        $this->oauthHost                 = $ch->getParameter('formalibre_target_platform_url');
        $this->oauthId                   = $ch->getParameter('formalibre_target_id');
        $this->oauthSecret               = $ch->getParameter('formalibre_target_secret');
    }

    public function executeOrder($order)
    {
        $sws = $order->getSharedWorkspace() === null ?
            $this->addRemoteWorkspace($order):
            $this->addRemoteWorkspaceExpDate($order);
        $order->setSharedWorkspace($sws);
        $this->om->persist($order);
        $this->om->persist($sws);
        $this->om->flush();
        $this->sendMailOrderInfo($sws);

        return $sws;
    }

    public function addRemoteWorkspace(Order $order)
    {
        $sws = $this->addSharedWorkspace($order);
        $this->createRemoteSharedWorkspace($sws);

        return $sws;
    }

    public function addSharedWorkspace(Order $order)
    {
        $priceSolution = $order->getPriceSolution();
        $duration = $priceSolution->getMonthDuration();
        $product = $order->getProduct();
        $user = $order->getChart()->getOwner();
        //get the duration right
        $details = $product->getDetails();
        $expDate = new \DateTime();

        if ($this->hasFreeTestMonth($user)) {
            $duration += $this->ch->getParameter('formalibre_test_month_duration');
            $order->setHasDiscount(true);
            $this->useFreeTestMonth($user);
        }

        $interval =  new \DateInterval("P{$duration}M");
        $expDate->add($interval);
        $sws = new SharedWorkspace();
        $sws->setOwner($user);
        $sws->setMaxUser($details['max_users']);
        $sws->setMaxRes($details['max_resources']);
        $sws->setMaxStorage($details['max_storage']);
        $sws->setExpDate($expDate);
        $sws->setRemoteId(0); //if it wasn't created properly, 0 means somethung went wrong obv.
        $this->om->persist($sws);
        $this->om->flush();

        return $sws;
    }

    public function createRemoteSharedWorkspace(SharedWorkspace $sws)
    {
        $user   = $sws->getOwner();
        $url    = 'api/users.json';
        $type   = 'GET';
        $tmppw  = uniqid();

        $payload = array(
            'profile_form_creation[username]' => $user->getUsername(),
            'profile_form_creation[firstName]' => $user->getFirstName(),
            'profile_form_creation[lastName]' => $user->getLastName(),
            'profile_form_creation[mail]' => $user->getMail(),
            'profile_form_creation[administrativeCode]' => $user->getUsername(),
            'profile_form_creation[plainPassword][first]' => $tmppw,
            'profile_form_creation[plainPassword][second]' => $tmppw,
        );

        $this->apiManager->url($this->oauthHost, $url, $this->oauthId, $this->oauthSecret, $payload, 'POST');
        $url = 'api/workspaces.json';

        $payload = array(
            'workspace_form[name]' => uniqid(),
            'workspace_form[code]' => uniqid(),
            'workspace_form[maxStorageSize]' => $sws->getMaxStorage(),
            'workspace_form[maxUsers]' => $sws->getMaxUser(),
            'workspace_form[maxUploadResources]' => $sws->getMaxRes(),
            'workspace_form[endDate]' => $sws->getExpDate()->getTimeStamp()
        );

        $serverOutput = $this->apiManager->url($this->oauthHost, $url, $this->oauthId, $this->oauthSecret, $payload, 'POST');
        $workspace = json_decode($serverOutput);

        if ($workspace === null) {
            $this->handleError($sws, $serverOutput, $url);
        }

        var_dump($workspace);
        throw new \Exception();

        if (property_exists($workspace, 'id')) {
            $sws->setRemoteId($workspace->id);
            $this->om->persist($sws);
            $this->om->flush();

            return;
        }

        $this->handleError($sws, $serverOutput, $url);
    }

    public function getSharedWorkspaceByUser(User $user)
    {
        return $this->sharedWorkspaceRepository->findByOwner($user);
    }

    public function getWorkspaceData(SharedWorkspace $sws)
    {
        $id = $sws->getRemoteId();
        $targetUrl = $this->ch->getParameter('formalibre_target_platform_url') . '/workspacesubscription/workspace/' . $id;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $serverOutput = curl_exec($ch);
        curl_close($ch);

        return json_decode($serverOutput);
    }

    public function addRemoteWorkspaceExpDate(Order $order)
    {
        $sws = $order->getSharedWorkspace();
        $monthDuration = $order->getPriceSolution()->getMonthDuration();
        $product = $order->getProduct();
        $details = $product->getDetails();
        $expDate = $sws->getExpDate();
        $now = new \DateTime();

        if ($now->getTimeStamp() > $expDate->getTimeStamp()) {
            $expDate = $now;
        }

        $interval =  new \DateInterval("P{$monthDuration}M");
        $expDate->add($interval);
        $payload = json_encode(array('expiration_date' => $expDate->getTimeStamp()));
        $targetUrl = $this->ch->getParameter('formalibre_target_platform_url') . '/workspacesubscription/workspace/' . $sws->getRemoteId() . '/exp_date/increase';
        $serverOutput = $this->crypto->sendPost($payload, $targetUrl);
        $data = json_decode($serverOutput);

        if ($data === null) {
            $this->handleError($sws, $serverOutput, $targetUrl);
        }

        //double equal because it's a string
        if ($data->code == 200) {
            $updatedDate = new \DateTime();
            $updatedDate->setTimeStamp($expDate->getTimeStamp());
            $sws->setExpDate($updatedDate);
            $this->om->persist($sws);
            $this->om->flush();

            return $sws;
        }

        $this->handleError($sws, $serverOutput, $targetUrl);
    }

    public function hasFreeTestMonth($user)
    {
        if ($user === 'anon.') return true;

        $repo = $this->om->getRepository('FormaLibreInvoiceBundle:FreeTestMonthUsage');
        $users = $repo->findByUser($user);

        return count($users) >= 1 ? false: true;
    }

    public function useFreeTestMonth(User $user)
    {
        $fmu = new FreeTestMonthUsage();
        $fmu->setUser($user);
        $this->om->persist($fmu);
        $this->om->flush();
    }

    public function isProductAvailableFor(SharedWorkspace $sws, Product $product)
    {
        $workspace = $this->getWorkspaceData($sws);
        $ut = $this->container->get('claroline.utilities.misc');
        $productData = $product->getDetails();

        if ($workspace->user_amount > $productData['max_users']) return false;
        if ($ut->getRealFileSize($workspace->storage_used) > $ut->getRealFileSize($productData['max_storage'])) return false;
        if ($workspace->count_resources > $productData['max_resources']) return false;

        return true;
    }

    public function getLastOrder(SharedWorkspace $sws)
    {
        $orders = $sws->getOrders();

        return $orders[0];
    }

    public function sendMailOrderInfo(SharedWorkspace $sws)
    {
        $workspace = $this->getWorkspaceData($sws);
        $subject = $this->translator->trans('formalibre_invoice', array(), 'invoice');

        $body = $this->templating->render(
            'FormaLibreInvoiceBundle:SharedWorkspace:mail_info.html.twig', array(
                'code' => $workspace->code,
                'name' => $workspace->name,
                'expirationDate' => $sws->getExpDate()
            )
        );

        $this->mailManager->send($subject, $body, array($sws->getOwner()));
    }

    /**************************************************************************/
    /************************ ERROR HANDLING **********************************/
    /**************************************************************************/

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
}
