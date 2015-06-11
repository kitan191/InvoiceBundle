<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;

/**
* @DI\Service("formalibre.manager.cryptographt_manager")
*/
class CryptographyManager
{
    private $logger;
    private $ch;

    /**
     * @DI\InjectParams({
     *     "logger" = @DI\Inject("logger"),
     *     "ch" = @DI\Inject("claroline.config.platform_config_handler")
     * })
     */
    public function __construct(
        $logger,
        $ch
    )
    {
        $this->logger                    = $logger;
        $this->ch                        = $ch;
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
}
