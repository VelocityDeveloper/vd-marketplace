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
            legacy_post_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY sender_id (sender_id),
            KEY recipient_id (recipient_id),
            KEY order_id (order_id),
            KEY is_read (is_read),
            KEY created_at (created_at),
            UNIQUE KEY legacy_post_id (legacy_post_id)
        ) {$charset};";

        dbDelta($sql);
    }

    public function migrate_legacy_posts()
    {
        global $wpdb;

        $table = self::table_name();
        $posts_table = $wpdb->posts;
        $postmeta_table = $wpdb->postmeta;

        $legacy_rows = $wpdb->get_results(
            "SELECT ID, post_author, post_content, post_date
            FROM {$posts_table}
            WHERE post_type = 'vmp_message' AND post_status = 'publish'
            ORDER BY ID ASC"
        );

        if (empty($legacy_rows)) {
            return;
        }

        foreach ($legacy_rows as $row) {
            $legacy_id = isset($row->ID) ? (int) $row->ID : 0;
            if ($legacy_id <= 0) {
                continue;
            }

            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE legacy_post_id = %d LIMIT 1",
                    $legacy_id
                )
            );
            if ($exists) {
                continue;
            }

            $sender_id = isset($row->post_author) ? (int) $row->post_author : 0;
            $recipient_id = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT meta_value FROM {$postmeta_table} WHERE post_id = %d AND meta_key = 'recipient_id' LIMIT 1",
                    $legacy_id
                )
            );
            $order_id = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT meta_value FROM {$postmeta_table} WHERE post_id = %d AND meta_key = 'order_id' LIMIT 1",
                    $legacy_id
                )
            );
            $is_read = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT meta_value FROM {$postmeta_table} WHERE post_id = %d AND meta_key = 'is_read' LIMIT 1",
                    $legacy_id
                )
            );

            if ($sender_id <= 0 || $recipient_id <= 0) {
                continue;
            }

            $wpdb->insert(
                $table,
                [
                    'sender_id' => $sender_id,
                    'recipient_id' => $recipient_id,
                    'order_id' => max(0, $order_id),
                    'message' => (string) ($row->post_content ?? ''),
                    'is_read' => $is_read > 0 ? 1 : 0,
                    'legacy_post_id' => $legacy_id,
                    'created_at' => !empty($row->post_date) ? (string) $row->post_date : current_time('mysql'),
                ],
                ['%d', '%d', '%d', '%s', '%d', '%d', '%s']
            );
        }
    }
}
