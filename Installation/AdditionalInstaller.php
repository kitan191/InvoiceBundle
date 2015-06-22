<?php

namespace FormaLibre\InvoiceBundle\Installation;

use Claroline\InstallationBundle\Additional\AdditionalInstaller as BaseInstaller;

class AdditionalInstaller extends BaseInstaller
{
    protected $logger;

    public function preUpdate($currentVersion, $targetVersion)
    {
        switch (true) {
            case version_compare($currentVersion, '5.1.0', '<'):
                $updater = new Updater\Updater050100($this->container);
                $updater->setLogger($this->logger);
                $updater->preUpdate();
        }
    }

    public function postUpdate($currentVersion, $targetVersion)
    {
        switch (true) {
            case version_compare($currentVersion, '5.1.0', '<'):
                $updater = new Updater\Updater050100($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
        }
    }
}
