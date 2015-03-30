<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\User;
use FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace;
use FormaLibre\InvoiceBundle\Entity\Product;
use FormaLibre\InvoiceBundle\Entity\PriceSolution;
use FormaLibre\InvoiceBundle\Entity\Order;

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
    private $sc;

    /**
     * @DI\InjectParams({
     *     "om" = @DI\Inject("claroline.persistence.object_manager"),
     *     "secret" = @DI\Inject("%formalibre_encryption_secret%"),
     *     "targetPlatformUrl" = @DI\Inject("%formalibre_target_platform_url%"),
     *     "vatManager" = @DI\Inject("formalibre.manager.vat_manager"),
     *     "logger" = @DI\Inject("logger"),
     *     "sc" = @DI\Inject("security.context"),
     *     "encrypt" = @DI\Inject("%formalibre_encrypt%")
     * })
     */
    public function __construct(ObjectManager $om, $secret, $targetPlatformUrl, $vatManager, $logger, $sc, $encrypt)
    {
        $this->secret = $secret;
        $this->om = $om;
        $this->productRepository = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Product');
        $this->sharedWorkspaceRepository = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace');
        $this->targetPlatformUrl = $targetPlatformUrl;
        $this->logger = $logger;
        $this->vatManager = $vatManager;
        $this->sc = $sc;
        $this->encrypt = $encrypt;
    }

    public function getProductsByType($type)
    {
        return $this->productRepository->findByType($type);
    }

    public function addSharedWorkspace(User $user, Order $order)
    {
        $product = $order->getProduct();
        $priceSolution = $order->getPriceSolution();
        //get the duration right
        $details = $product->getDetails();
        $monthDuration = $priceSolution->getMonthDuration();
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
        $targetUrl = $this->targetPlatformUrl . '/workspacesubscription/create';
        $serverOutput = $this->sendPost($payload, $targetUrl);
        $data = json_decode($serverOutput);
        //double equal because it's a string

        if ($data->code == 200) {
            $id = $data->workspace->id;
            $sws->setRemoteId($id);
            $this->om->persist($sws);
            $this->om->flush();

            return;
        }

        //send a mail with ws Ids.

        throw new \Exception('An error occured during the workspace creation');
    }

    public function getSharedWorkspaceByUser(User $user)
    {
        return $this->sharedWorkspaceRepository->findByOwner($user);
    }

    public function getWorkspaceData(SharedWorkspace $sws)
    {
        $id = $sws->getRemoteId();
        $targetUrl = $this->targetPlatformUrl . '/workspacesubscription/workspace/' . $id;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $serverOutput = curl_exec($ch);
        curl_close($ch);

        return json_decode($serverOutput);
    }

    public function addRemoteWorkspaceExpDate($order, SharedWorkspace $sws)
    {
        $product = $order->getProduct();
        $priceSolution = $order->getPriceSolution();
        //get the duration right
        $details = $product->getDetails();
        $monthDuration = $priceSolution->getMonthDuration();
        $expDate = $sws->getExpDate();
        $interval =  new \DateInterval("P{$monthDuration}M");
        $expDate->add($interval);
        $payload = json_encode(array('expiration_date' => $expDate->getTimeStamp()));
        $payload = $this->encrypt($payload);
        $targetUrl = $targetUrl = $this->targetPlatformUrl . '/workspacesubscription/workspace/' . $sws->getRemoteId() . '/exp_date/increase';
        $serverOutput = $this->sendPost($payload, $targetUrl);
        $data = json_decode($serverOutput);
        //double equal because it's a string

        if ($data->code == 200) {
            $updatedDate = new \DateTime();
            $updatedDate->setTimeStamp($expDate->getTimeStamp());
            $sws->setExpDate($updatedDate);
            $this->om->persist($sws);
            $this->om->flush();

            return;
        }

        throw new \Exception('An error occured during the expiration date increase');
    }

    private function sendPost($payload, $url)
    {
        $qs = http_build_query(array('payload' => $payload));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $qs);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $serverOutput = curl_exec($ch);
        $this->logger->debug($serverOutput);
        curl_close($ch);

        return $serverOutput;
    }

    private function encrypt($payload)
    {
        if (!$this->encrypt) {
            return $payload;
        }

        $key = pack('H*', $this->secret);
        $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_192, MCRYPT_MODE_CBC);
        $iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        $ciphertext = mcrypt_encrypt(
            MCRYPT_RIJNDAEL_192, //aes
            $key,
            $payload,
            MCRYPT_MODE_CBC,
            $iv
        );

        # prepend the IV for it to be available for decryption
        $ciphertext = $iv . $ciphertext;

        # encode the resulting cipher text so it can be represented by a string
        $ciphertextencoded = base64_encode($ciphertext);

        return $ciphertextencoded;
    }

    public function endOrder(Order $order)
    {
        $order->setCountryCode($this->vatManager->getClientLocation());
        $order->setAmount($order->getPriceSolution()->getPrice());
        $order->setVatRate($this->vatManager->getVATRate($this->vatManager->getClientLocation()));
        $order->setVatAmount($this->vatManager->getVAT($order->getAmount()));
        $order->setIpAddress($_SERVER['REMOTE_ADDR']);
        $order->setOwner($this->sc->getToken()->getUser());
        //add the vat number here ()
        $order->setIsExecuted(true);
    }
}
