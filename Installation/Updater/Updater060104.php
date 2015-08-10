<?php

namespace FormaLibre\InvoiceBundle\Installation\Updater;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Claroline\InstallationBundle\Updater\Updater;

class Updater060104 extends Updater
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->ch = $this->container->get('claroline.config.platform_config_handler');
    }

    public function postUpdate()
    {
        $this->log('Adding formalibre_default_locale parameter...');
        $this->ch->setParameter('formalibre_default_locale', 'be');
    }
}
