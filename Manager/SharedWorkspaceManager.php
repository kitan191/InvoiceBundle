<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Manager\MailManager;
use Claroline\CoreBundle\Manager\OauthManager;
use Claroline\CoreBundle\Manager\ApiManager;
use Claroline\CoreBundle\Form\WorkspaceType;
use Claroline\CoreBundle\Pager\PagerFactory;
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
    private $apiManager;
    private $ch;
    private $container;
    private $formFactory;
    private $logger;
    private $mailManager;
    private $oauthManager;
    private $om;
    private $templating;
    private $translator;
    private $vatManager;
    private $friendRepo;
    private $productRepo;
    private $sharedWorkspaceRepo;
    private $targetFriend;
    private $pagerFactory;

    /**
     * @DI\InjectParams({
     *     "apiManager"   = @DI\Inject("claroline.manager.api_manager"),
     *     "ch"           = @DI\Inject("claroline.config.platform_config_handler"),
     *     "container"    = @DI\Inject("service_container"),
     *     "formFactory"  = @DI\Inject("form.factory"),
     *     "logger"       = @DI\Inject("logger"),
     *     "mailManager"  = @DI\Inject("claroline.manager.mail_manager"),
     *     "oauthManager" = @DI\Inject("claroline.manager.oauth_manager"),
     *     "om"           = @DI\Inject("claroline.persistence.object_manager"),
     *     "pagerFactory" = @DI\Inject("claroline.pager.pager_factory"),
     *     "templating"   = @DI\Inject("templating"),
     *     "translator"   = @DI\Inject("translator"),
     *     "vatManager"   = @DI\Inject("formalibre.manager.vat_manager")
     * })
     */
    public function __construct(
        ApiManager $apiManager,
        $ch,
        $container,
        $formFactory,
        $logger,
        MailManager $mailManager,
        OauthManager $oauthManager,
        ObjectManager $om,
        PagerFactory $pagerFactory,
        $templating,
        $translator,
        VATManager $vatManager
    )
    {
        $this->apiManager          = $apiManager;
        $this->formFactory         = $formFactory;
        $this->om                  = $om;
        $this->logger              = $logger;
        $this->ch                  = $ch;
        $this->container           = $container;
        $this->mailManager         = $mailManager;
        $this->templating          = $templating;
        $this->mailManager         = $mailManager;
        $this->translator          = $translator;
        $this->oauthManager        = $oauthManager;
        $this->vatManager          = $vatManager;
        $this->pagerFactory        = $pagerFactory;

        $this->friendRepo          = $this->om->getRepository('Claroline\CoreBundle\Entity\Oauth\FriendRequest');
        $this->productRepo         = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Product');
        $this->sharedWorkspaceRepo = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace');
        $this->targetFriend   = $this->friendRepo->findOneByName($ch->getParameter('campusName'));
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

        $serverOutput = $this->apiManager->url($this->targetFriend, $url, $payload, 'POST');
        $data = json_decode($serverOutput, true);

        if ($data === null) {
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

        $serverOutput = $this->apiManager->url($this->targetFriend, $url, $payload, 'POST');
        $workspace = json_decode($serverOutput, true);

        if ($workspace === null || isset($workspace['errors'])) {
            $this->handleError($sws, $serverOutput, $url);
        }

        if (array_key_exists('id', $workspace)) {
            $sws->setRemoteId($workspace['id']);
            $this->om->persist($sws);
            $this->om->flush();

            return;
        }

        $this->handleError($sws, $serverOutput, $url);
    }

    public function createRemoteWorkspace(SharedWorkspace $sws, User $user, $name, $code)
    {
        $url = 'api/workspaces/' . $user->getUsername() . '/users.json';

        $payload = array(
            'workspace_form[name]' => $name,
            'workspace_form[code]' => $code,
            'workspace_form[maxStorageSize]' => $sws->getMaxStorage(),
            'workspace_form[maxUsers]' => $sws->getMaxUser(),
            'workspace_form[maxUploadResources]' => $sws->getMaxRes(),
            'workspace_form[endDate]' => $sws->getExpDate()->format('d-m-Y')
        );

        $serverOutput = $this->apiManager->url($this->targetFriend, $url, $payload, 'POST');
        $datas = json_decode($serverOutput, true);

        if (array_key_exists('id', $datas)) {
            $sws->setRemoteId($datas['id']);
            $this->om->persist($sws);
            $this->om->flush();

            return 'success';
        } else {

            return $datas;
        }
    }

    public function getSharedWorkspaces($withPager = true, $page = 1, $max = 20)
    {
        $sharedWorkspaces = $this->sharedWorkspaceRepo->findAll();

        return $withPager ?
            $this->pagerFactory->createPagerFromArray($sharedWorkspaces, $page, $max) :
            $users;
    }

    public function getSharedWorkspaceByUser(User $user)
    {
        return $this->sharedWorkspaceRepo->findByOwner($user);
    }

    public function getAllSharedWorkspaces()
    {
        return $this->sharedWorkspaceRepo->findAll();
    }

    public function getAllRemoteWorkspacesDatas()
    {
        $url = 'api/workspaces.json';
        $serverOutput = $this->apiManager->url($this->targetFriend, $url);

        return json_decode($serverOutput, true);
    }

    public function getNonPersonalRemoteWorkspacesDatas()
    {
        $url = 'api/non/personal/workspaces.json';
        $serverOutput = $this->apiManager->url($this->targetFriend, $url);

        return json_decode($serverOutput, true);
    }

    public function getAllWorkspacesDatas()
    {
        $url = 'api/workspaces.json';
        $serverOutput = $this->apiManager->url($this->targetFriend, $url);
        $workspace = json_decode($serverOutput, true);

        if ($workspace === null || array_key_exists('error', $workspace)) {

            return array();
        }

        return $workspace;
    }

    public function getWorkspaceData(SharedWorkspace $sws)
    {
        $url = 'api/workspaces/' . $sws->getRemoteId() . '.json';
        $serverOutput = $this->apiManager->url($this->targetFriend, $url);
        $workspace = json_decode($serverOutput, true);

        if ($workspace === null || array_key_exists('error', $workspace)) {
            //throw new \Exception($serverOutput);
            return array();
        }
        
        return $workspace;
    }

    public function getWorkspaceAdditionalDatas(SharedWorkspace $sws)
    {
        $url = 'api/workspaces/' . $sws->getRemoteId() . '/additional/datas.json';
        $serverOutput = $this->apiManager->url($this->targetFriend, $url);
        $workspace = json_decode($serverOutput, true);

        if ($workspace === null || array_key_exists('error', $workspace)) {
            //throw new \Exception($serverOutput);
            return array();
        }
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
        $serverOutput = $this->apiManager->url($this->targetFriend, $url, $payload, 'PUT');
        $workspace = json_decode($serverOutput, true);

        //add date here

        if ($workspace === null || isset($workspace['errors'])) {
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

    public function editSharedWorkspaceRemoteName(array $workspace, $workspaceName)
    {
        $workspaceType = new WorkspaceType();
        $workspaceType->enableApi();
        $form = $this->formFactory->create($workspaceType);
        $expDate = \DateTime::createFromFormat(\DateTime::ATOM, $workspace['endDate']);
        $payload = $this->apiManager->formEncode($workspace, $form, $workspaceType);
        $payload['workspace_form[name]'] = $workspaceName;
        $payload['workspace_form[endDate]'] = $expDate->format('d-m-Y');
        $url = 'api/workspaces/' . $workspace['id'] . '/users/' . $workspace['creator']['username'] . '.json';
        $serverOutput = $this->apiManager->url($this->targetFriend, $url, $payload, 'PUT');
        $datas = json_decode($serverOutput, true);

        if (is_null($datas) || isset($datas['errors'])) {

            throw new \Exception($serverOutput);
        }

        return $datas;
    }

    public function editSharedWorkspaceOwner(SharedWorkspace $sharedWorkspace, User $owner)
    {
        $remoteUser = $this->getRemoteUser($owner->getUsername());
        $remoteWorkspace = $this->getWorkspaceData($sharedWorkspace);

        if (isset($remoteUser['error']['code']) && $remoteUser['error']['code'] === 404) {

            $remoteUser = $this->createRemoteUser($owner);
        }

        if (isset($remoteUser['id']) && isset($remoteWorkspace['id'])) {
            $url = 'api/workspaces/' . $remoteWorkspace['id'] . '/owners/' . $remoteUser['id'] . '.json';
            $serverOutput = $this->apiManager->url($this->targetFriend, $url, array(), 'PUT');
            $datas = json_decode($serverOutput, true);

            if (is_null($datas) || isset($datas['errors'])) {

                throw new \Exception($serverOutput);
            }
            $sharedWorkspace->setOwner($owner);
            $this->om->persist($sharedWorkspace);
            $this->om->flush();

            return $datas;
        } else {

            return null;
        }
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

    public function getRemoteUser($username)
    {
        $url = 'api/users/' . $username . '.json';
        $serverOutput = $this->apiManager->url($this->targetFriend, $url);

        return json_decode($serverOutput, true);
    }

    public function createRemoteUser(User $user)
    {
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
        $serverOutput = $this->apiManager->url($this->targetFriend, $url, $payload, 'POST');
        $datas = json_decode($serverOutput, true);

        return $datas;
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
