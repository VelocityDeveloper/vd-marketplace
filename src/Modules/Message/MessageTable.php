<?php

namespace VelocityMarketplace\Modules\Message;

class MessageTable
{
    public static function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'vmp_messages';
    }

    public function create_table()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = self::table_name();
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) unsigned NOT NULL,
            recipient_id bigint(20) unsigned NOT NULL,
            order_id bigint(20) unsigned NOT NULL DEFAULT 0,
            message longtext NOT NULL,
            is_read tinyint(1) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY sender_id (sender_id),
            KEY recipient_id (recipient_id),
            KEY order_id (order_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) {$charset};";

        dbDelta($sql);
    }
}
