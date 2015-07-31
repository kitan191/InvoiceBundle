<?php

namespace FormaLibre\InvoiceBundle\Installation;

use Claroline\InstallationBundle\Additional\AdditionalInstaller as BaseInstaller;

class AdditionalInstaller extends BaseInstaller
{
    protected $logger;

    public function postUpdate($currentVersion, $targetVersion)
    {
        switch (true) {
            case version_compare($currentVersion, '6.0.2', '<'):
                $updater = new Updater\Updater060002($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
        }
    }
}
