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
    private $sc;
    private $ch;
    private $mailManager;
    private $container;

    /**
     * @DI\InjectParams({
     *     "om" = @DI\Inject("claroline.persistence.object_manager"),
     *     "vatManager" = @DI\Inject("formalibre.manager.vat_manager"),
     *     "logger" = @DI\Inject("logger"),
     *     "sc" = @DI\Inject("security.context"),
     *     "ch" = @DI\Inject("claroline.config.platform_config_handler"),
     *     "mailManager" = @DI\Inject("claroline.manager.mail_manager"),
     *     "container" = @DI\Inject("service_container")
     * })
     */
    public function __construct(
        ObjectManager $om,
        VATManager $vatManager,
        $logger,
        $sc,
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
        $this->sc                        = $sc;
        $this->ch                        = $ch;
        $this->mailManager               = $mailManager;
        $this->container                 = $container;
    }

    public function getProductsByType($type)
    {
        return $this->productRepository->findByType($type);
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
        $targetUrl = $this->ch->getParameter('formalibre_target_platform_url') . '/workspacesub=ription/workspace/' . $id;
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
        if (!$this->ch->getParameter('formalibre_encrypt')) {
            return $payload;
        }

        $key = pack('H*', $this->ch->getParameter('formalibre_encryption_secret_encrypt'));
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

    public function endOrder(Order $order, $addVat = true)
    {
        $order->setCountryCode($this->vatManager->getClientLocation());
        $order->setIpAddress($_SERVER['REMOTE_ADDR']);
        $order->setOwner($order->getOwner());

        if ($addVat) {
            $order->setAmount($order->getPriceSolution()->getPrice());
            $order->setVatRate($this->vatManager->getVATRate($this->vatManager->getClientLocation()));
            $order->setVatAmount($this->vatManager->getVAT($order->getAmount()));
        }
        //add the vat number here ()
        $order->setIsExecuted(true);
    }

    public function handleError(SharedWorkspace $sws, $serverOutput = null, $target = null)
    {
        $this->sendMailError($sws, $serverOutput, $target);

        throw new PaymentHandlingFailedException();
    }

    public function sendSuccessMail(SharedWorkspace $sws, Order $order)
    {
        $user = $this->sc->getToken()->getUser();
        $snappy = $this->container->get('knp_snappy.pdf');
        $owner = $order->getOwner();
        $fieldRepo = $this->om->getRepository('ClarolineCoreBundle:Facet\FieldFacet');
        $valueRepo =  $this->om->getRepository('ClarolineCoreBundle:Facet\FieldFacetValue');

        $streetField = $fieldRepo->findOneByName('formalibre_street');
        $cpField = $fieldRepo->findOneByName('formalibre_cp');
        $townField = $fieldRepo->findOneByName('formalibre_town');
        $countryField = $fieldRepo->findOneByName('formalibre_country');
        $order->setValidationDate(new \DateTime());
        $this->om->persist($order);
        $this->om->flush();

        $view = $this->container->get('templating')->render(
            'FormaLibreInvoiceBundle:pdf:invoice.html.twig',
            array(
                'order' => $order,
                'street' => $valueRepo->findOneBy(array('user' => $user, 'fieldFacet' => $streetField))->getValue(),
                'cp' => $valueRepo->findOneBy(array('user' => $user, 'fieldFacet' => $cpField))->getValue(),
                'town' => $valueRepo->findOneBy(array('user' => $user, 'fieldFacet' => $townField))->getValue(),
                'country' => $valueRepo->findOneBy(array('user' => $user, 'fieldFacet' => $countryField))->getValue()
            )
        );
        //@todo: the path should include the invoice numbe
        $path = $this->container->getParameter('claroline.param.pdf_directory') . '/invoice/' . $order->getId() . '.pdf';
        @mkdir($this->container->getParameter('claroline.param.pdf_directory'));
        @mkdir($this->container->getParameter('claroline.param.pdf_directory')) . '/invoice';
        $snappy->generateFromHtml($view, $path);
        $subject = $this->container->get('translator')->trans('formalibre_invoice', array(), 'platform');
        $body = $this->container->get('templating')->render(
            'FormaLibreInvoiceBundle:Mail:workspace_subscription.html.twig'
        );

        return $this->mailManager->send($subject, $body, array($user), null, array('attachment' => $path));
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

    public function executeWorkspaceOrder(Order $order, $duration, $swsId = 0, $addOrderVat = false)
    {
        $this->endOrder($order, $addOrderVat);
        $sws = $this->om->getRepository("FormaLibreInvoiceBundle:Product\SharedWorkspace")->find($swsId);

        if ($sws === null) {
            $sws = $this->addRemoteWorkspace($order, $duration);
        } else {
            $this->addRemoteWorkspaceExpDate($order, $sws);
        }

        $this->sendSuccessMail($sws, $order);
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

        return count($users) > 1 ? false: true;
    }

    public function useFreeTestMonth(User $user)
    {
        $fmu = new FreeTestMonthUsage();
        $fmu->setUser($user);
        $this->om->persist($fmu);
        $this->om->flush();
    }

    public function getByCode($code)
    {
        return $this->productRepository->findOneByCode($code);
    }
}
