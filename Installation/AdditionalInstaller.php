<?php

namespace FormaLibre\InvoiceBundle\Installation;

use Claroline\InstalationBundle\Additional\AdditinalInstaller as BaseInstaller;

class AdditionalInsaller extends BaseInstaller
{
    private $logger;

    public function __construct()
    {
        $self = $this;
        $this->logger = function ($message) use ($self) {
            $self->log($message);
        }
    }

    public function preUpdate($currentVersion, $targetVersion)
    {
        case version_compare(version_compare($currentVersion, '5.0.4', '<'):
            $updater = new Updater\Updater050004($this->container);
            $updater->setLogger($this->logger);
            $updater->postUpdate();
    }

    public function postUpdate($currentVersion, $targetVersion)
    {
    }
}
