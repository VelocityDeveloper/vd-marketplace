<?php

namespace VelocityMarketplace\Core;

use VelocityMarketplace\Modules\Message\MessageTable;

class Upgrade
{
    const DB_VERSION = '1.4.0';

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
        $messages->migrate_legacy_posts();

        update_option('velocity_marketplace_db_version', self::DB_VERSION);
    }

    public function maybe_upgrade()
    {
        $version = (string) get_option('velocity_marketplace_db_version', '');
        if ($version === self::DB_VERSION) {
            return;
        }

        $this->activate();
    }
}
