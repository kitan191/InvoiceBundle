<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FormaLibre\InvoiceBundle\DependencyInjection\Factory;

use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\Payment\PaypalBundle\Client\Authentication\TokenAuthenticationStrategy;
use JMS\Payment\PaypalBundle\Client\Client;
use JMS\Payment\CoreBundle\Cryptography\MCryptEncryptionService;

/**
 * @DI\Service("formalibre.payal_payment_factory")
 */
class PaypalPaymentFactory
{
    private $httpUtils;
    private $configHandler;
    private $session;

    /**
     * @DI\InjectParams({
     *     "configHandler" = @DI\Inject("claroline.config.platform_config_handler")
     * })
     */
    public function __construct(PlatformConfigurationHandler $configHandler)
    {
        $this->configHandler = $configHandler;
    }

    public function getAuthenticationStrategyToken()
    {
        return new TokenAuthenticationStrategy(
            $this->configHandler->getParameter('jms_payment_paypal_username'),
            $this->configHandler->getParameter('jms_payment_paypal_password'),
            $this->configHandler->getParameter('jms_payment_paypal_signature')
        );
    }

    public function getClient()
    {
        return new Client(
            $this->getAuthenticationStrategyToken(),
            $this->configHandler->getParameter('jms_payment_paypal_debug')
        );
    }

    public function getEncryptionService()
    {
        return new MCryptEncryptionService(
            $this->configHandler->getParameter('jms_payment_core_secret'),
            'rijndael-256',
            'ctr'
        );
    }
}
