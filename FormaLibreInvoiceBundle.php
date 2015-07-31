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
class FormaLibreInvoiceBundle extends PluginBundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new DynamicConfigPass());
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
