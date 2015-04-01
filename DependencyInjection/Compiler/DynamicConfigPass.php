<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FormaLibre\InvoiceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DynamicConfigPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     *
     * Rewrites previous service definitions in order to force the dumped container to use
     * dynamic configuration parameters. Technique may vary depending on the target service
     * (see for example https://github.com/opensky/OpenSkyRuntimeConfigBundle).
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        //paypal auth
        $paypal = new Definition();
        $paypal->setFactoryService('formalibre.payal_payment_factory');
        $paypal->setFactoryMethod('getAuthenticationStrategyToken');
        $paypal->setClass('JMS\Payment\PaypalBundle\Client\Authentication\TokenAuthenticationStrategy');
        $container->removeDefinition('payment.paypal.authentication_strategy.token');
        $container->setDefinition('payment.paypal.authentication_strategy.token', $paypal);

        //paypal client
        $client = new Definition();
        $client->setFactoryService('formalibre.payal_payment_factory');
        $client->setFactoryMethod('getClient');
        $client->setClass('JMS\Payment\PaypalBundle\Client\Client');
        $container->removeDefinition('payment.paypal.client');
        $container->setDefinition('payment.paypal.client', $client);

        $mcrypt = new Definition();
        $mcrypt->setFactoryService('formalibre.payal_payment_factory');
        $mcrypt->setFactoryMethod('getEncryptionService');
        $mcrypt->setClass('JMS\Payment\CoreBundle\Cryptography\MCryptEncryptionService');
        $container->removeDefinition('payment.encryption_service');
        $container->setDefinition('payment.encryption_service', $mcrypt);
    }
}
