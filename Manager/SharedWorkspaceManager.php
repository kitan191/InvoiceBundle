<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Manager\MailManager;
use Claroline\CoreBundle\Manager\OauthManager;
use Claroline\CoreBundle\Manager\ApiManager;
use Claroline\CoreBundle\Form\WorkspaceType;
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
    private $formFactory;

    /**
     * @DI\InjectParams({
     *     "om"           = @DI\Inject("claroline.persistence.object_manager"),
     *     "vatManager"   = @DI\Inject("formalibre.manager.vat_manager"),
     *     "logger"       = @DI\Inject("logger"),
     *     "ch"           = @DI\Inject("claroline.config.platform_config_handler"),
     *     "container"    = @DI\Inject("service_container"),
     *     "mailManager"  = @DI\Inject("claroline.manager.mail_manager"),
     *     "templating"   = @DI\Inject("templating"),
     *     "mailer"       = @DI\Inject("claroline.manager.mail_manager"),
     *     "translator"   = @DI\Inject("translator"),
     *     "oauthManager" = @DI\Inject("claroline.manager.oauth_manager"),
     *     "apiManager"   = @DI\Inject("claroline.manager.api_manager"),
     *     "formFactory"  = @DI\Inject("form.factory")
     * })
     */
    public function __construct(
        ObjectManager $om,
        VATManager $vatManager,
        $logger,
        $ch,
        $container,
        MailManager $mailManager,
        $templating,
        MailManager $mailManager,
        $translator,
        OauthManager $oauthManager,
        ApiManager $apiManager,
        $formFactory
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
        $this->templating                = $templating;
        $this->mailManager               = $mailManager;
        $this->translator                = $translator;
        $this->oauthManager              = $oauthManager;
        $this->apiManager                = $apiManager;
        $this->friendRepo                = $this->om->getRepository('Claroline\CoreBundle\Entity\Oauth\FriendRequest');
        $this->campusPlatform            = $this->friendRepo->findOneByName($ch->getParameter('campusName'));
        $this->formFactory               = $formFactory;
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

        $serverOutput = $this->apiManager->url($this->campusPlatform, $url, $payload, 'POST');
        $data = json_decode($serverOutput, true);

        if ($data === null || isset($data['error'])) {
            $this->handleError($sws, $serverOutput, $url);
        }

        $url = 'api/workspaces/' . $user->getUsername() . '/users.json';

        $payload = array(
            'workspace_form[name]' => uniqid(),
            'workspace_form[code]' => uniqid(),
            'workspace_form[maxStorageSize]' => $sws->getMaxStorage(),
            'workspace_form[maxUsers]' => $sws->getMaxUser(),
            'workspace_form[maxUploadResources]' => $sws->getMaxRes(),
            'workspace_form[endDate]' => $sws->getExpDate()->format('d-m-Y')
        );

        $serverOutput = $this->apiManager->url($this->campusPlatform, $url, $payload, 'POST');
        $workspace = json_decode($serverOutput);

        if ($workspace === null) {
            $this->handleError($sws, $serverOutput, $url);
        }

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
        $url = 'api/workspaces/' . $sws->getRemoteId() . '.json';
        $serverOutput = $this->apiManager->url($this->campusPlatform, $url);

        return json_decode($serverOutput, true);
    }

    public function addRemoteWorkspaceExpDate(Order $order)
    {
        $sws = $order->getSharedWorkspace();
        $user = $sws->getOwner();
        $monthDuration = $order->getPriceSolution()->getMonthDuration();
        $product = $order->getProduct();
        $details = $product->getDetails();
        $workspace = $this->getWorkspaceData($sws);
        $expDate = \DateTime::createFromFormat(\DateTime::ATOM, $workspace['endDate']);
        $now = new \DateTime();

        if ($now->getTimeStamp() > $expDate->getTimeStamp()) {
            $expDate = $now;
        }

        $interval =  new \DateInterval("P{$monthDuration}M");
        $expDate->add($interval);

        $workspaceType = new WorkspaceType();
        $workspaceType->enableApi();
        $form = $this->formFactory->create($workspaceType);

        $payload = $this->apiManager->formEncode($workspace, $form, $workspaceType);
        $payload['workspace_form[endDate]'] = $expDate->format('d-m-Y');
        $url = 'api/workspaces/' . $sws->getRemoteId() . '/users/' . $user->getUsername() . '.json';
        $serverOutput = $this->apiManager->url($this->campusPlatform, $url, $payload, 'PUT');
        $workspace = json_decode($serverOutput);

        //add date here

        if ($workspace === null) {
            $this->handleError($sws, $serverOutput, $url);
        }

        if (array_key_exists('id', $workspace)) {
            $updatedDate = new \DateTime();
            $updatedDate->setTimeStamp($expDate->getTimeStamp());
            $sws->setExpDate($updatedDate);
            $this->om->persist($sws);
            $this->om->flush();

            return $sws;
        }

        $this->handleError($sws, $serverOutput, $url);
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
                'code' => $workspace['code'],
                'name' => $workspace['name'],
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
