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

    public function addSharedWorkspace(User $user, Order $order, $monthDuration)
    {
        $product = $order->getProduct();
        //get the duration right
        $details = $product->getDetails();
        $expDate = new \DateTime();
        $interval =  new \DateInterval("P{$monthDuration}M");
        $expDate->add($interval);
        $sws = new SharedWorkspace();
        $sws->setOwner($user);
        $sws->setMaxUser($details['max_users']);
        $sws->setMaxRes($details['max_resources']);
        $sws->setMaxStorage($details['max_storage']);
        $sws->setExpDate($expDate);
        $sws->setProduct($order->getProduct());
        $sws->setRemoteId(0); //if it wasn't created properly, 0 means somethung went wrong obv.
        $this->om->persist($sws);
        $this->om->flush();

        return $sws;
    }

    public function createRemoteSharedWorkspace(SharedWorkspace $sws, User $user)
    {
        $userJson = array(
            'username' => $user->getUsername(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getMail()
        );

        $workspaceJson = array(
            'max_storage' => $sws->getMaxStorage(),
            'max_user' => $sws->getMaxUser(),
            'max_resource' => $sws->getMaxRes(),
            'expiration_date' => $sws->getExpDate()->getTimeStamp()
        );

        $payload = json_encode(array(
            'user' => $userJson,
            'workspace' => $workspaceJson
        ));

        $payload = $this->encrypt($payload);
        $targetUrl = $this->ch->getParameter('formalibre_target_platform_url') . '/workspacesubscription/create';
        $serverOutput = $this->sendPost($payload, $targetUrl);
        $data = json_decode($serverOutput);

        if ($data === null) {
            $this->handleError($sws, $serverOutput, $targetUrl);
        }

        if ($data->code == 200) {
            $id = $data->workspace->id;
            $sws->setRemoteId($id);
            $this->om->persist($sws);
            $this->om->flush();

            return;
        }

        $this->handleError($sws, $serverOutput, $targetUrl);
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

    public function addRemoteWorkspaceExpDate($order, SharedWorkspace $sws, $monthDuration)
    {
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
        $payload = $this->encrypt($payload);
        $targetUrl = $this->ch->getParameter('formalibre_target_platform_url') . '/workspacesubscription/workspace/' . $sws->getRemoteId() . '/exp_date/increase';
        $serverOutput = $this->sendPost($payload, $targetUrl);
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

            return;
        }

        $this->handleError($sws, $serverOutput, $targetUrl);
    }

    private function addRemoteWorkspace(Order $order, $duration)
    {
        $user = $order->getOwner();
        $sws = $this->addSharedWorkspace($user, $order, $duration);
        $this->createRemoteSharedWorkspace($sws, $user);

        return $sws;
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

    public function executeWorkspaceOrder(Order $order, $duration, $sws = null, $isTestOrder = false)
    {
        $this->endOrder($order, !$isTestOrder);

        if ($sws === null) {
            $sws = $this->addRemoteWorkspace($order, $duration);
        } else {
            $this->addRemoteWorkspaceExpDate($order, $sws, $duration);
        }

        $sws->setIsTest($isTestOrder);
        $this->om->persist($sws);
        $this->om->flush();
        $hasFreeMonth = $this->hasFreeTestMonth($order->getOwner());
        $this->sendSuccessMail($sws, $order, $duration, $hasFreeMonth);
        if ($this->hasFreeTestMonth($order->getOwner())) $this->useFreeTestMonth($order->getOwner());
    }
}
