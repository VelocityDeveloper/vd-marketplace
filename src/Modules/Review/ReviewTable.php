<?php

namespace VelocityMarketplace\Modules\Review;

class ReviewTable
{
    public static function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'vmp_reviews';
    }

    public function create_table()
    {
        global $wpdb;

        $table_name = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            order_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            seller_id bigint(20) unsigned NOT NULL DEFAULT 0,
            rating tinyint(3) unsigned NOT NULL DEFAULT 0,
            title varchar(190) NOT NULL DEFAULT '',
            content longtext NOT NULL,
            image_ids text NULL,
            is_approved tinyint(1) unsigned NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY vmp_review_unique (product_id,order_id,user_id),
            KEY vmp_review_product (product_id),
            KEY vmp_review_order (order_id),
            KEY vmp_review_user (user_id),
            KEY vmp_review_seller (seller_id),
            KEY vmp_review_approved_created (is_approved,created_at)
        ) {$charset_collate};";

        dbDelta($sql);
    }
}
