<?php

namespace FormaLibre\InvoiceBundle;

use Claroline\CoreBundle\Library\PluginBundle;
use Claroline\KernelBundle\Bundle\ConfigurationBuilder;
use FormaLibre\InvoiceBundle\Installation\AdditionalInstaller;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Claroline\KernelBundle\Bundle\AutoConfigurableInterface;
use FormaLibre\InvoiceBundle\DependencyInjection\Compiler\DynamicConfigPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Claroline\KernelBundle\Bundle\ConfigurationProviderInterface;

/**
 * Bundle class.
 */
class FormaLibreInvoiceBundle extends PluginBundle implements AutoConfigurableInterface, ConfigurationProviderInterface
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new DynamicConfigPass());
    }

    /**
     * @todo find a way to remove this without breaking everything
     */
    public function suggestConfigurationFor(Bundle $bundle, $environment)
    {
        $bundleClass = get_class($bundle);
        $config = new ConfigurationBuilder();

        $simpleConfigs = array(
            'JMS\Payment\CoreBundle\JMSPaymentCoreBundle' => 'jms_payment_core',
            'JMS\Payment\PaypalBundle\JMSPaymentPaypalBundle' => 'jms_payment_paypal'
        );

        if (isset($simpleConfigs[$bundleClass])) {
            return $config->addContainerResource($this->buildPath($simpleConfigs[$bundleClass]));
        }
    }

    public function getConfiguration($environment)
    {
        $config = new ConfigurationBuilder();

        return $config->addRoutingResource(__DIR__ . '/Resources/config/routing.yml', null, 'invoice');
    }

    /*
    public function getAdditionalInstaller()
    {
        return new AdditionalInstaller();
    }
    */

    public function hasMigrations()
    {
        return true;
    }

    public function getRequiredFixturesDirectory($environment)
    {
        return 'DataFixtures';
    }

    private function buildPath($file, $folder = 'suggested')
    {
        return __DIR__ . "/Resources/config/{$folder}/{$file}.yml";
    }

    public function getAdditionalInstaller()
    {
        return new AdditionalInstaller();
    }
}
