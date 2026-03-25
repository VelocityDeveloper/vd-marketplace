<?php

namespace VelocityMarketplace\Core;

use VelocityMarketplace\Modules\Message\MessageTable;

class Upgrade
{
    const DB_VERSION = '1.6.0';

    public function register()
    {
        add_action('init', [$this, 'maybe_upgrade']);
    }

    public function activate()
    {
        $installer = new Installer();
        $installer->activate();

        $messages = new MessageTable();
        $messages->create_table();

        update_option(VMP_DB_VERSION_OPTION, self::DB_VERSION);
    }

    public function maybe_upgrade()
    {
        $version = (string) get_option(VMP_DB_VERSION_OPTION, '');
        if ($version === self::DB_VERSION) {
            return;
        }

        $this->activate();
    }
}
