<?php

namespace VelocityMarketplace\Modules\Product;

class ProductArchive
{
    public function register()
    {
        add_action('pre_get_posts', [$this, 'filter_archive_query']);
    }

    public function filter_archive_query($query)
    {
        if (!($query instanceof \WP_Query) || is_admin() || !$query->is_main_query() || !$query->is_post_type_archive('vmp_product')) {
            return;
        }

        $filters = (new ProductQuery())->normalize_filters($_GET);
        (new ProductQuery())->apply_to_query($query, $filters);
    }
}
