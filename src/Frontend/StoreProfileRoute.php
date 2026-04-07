<?php

namespace VelocityMarketplace\Frontend;

use VelocityMarketplace\Support\Settings;

class StoreProfileRoute
{
    public function register()
    {
        add_action('init', [$this, 'register_rewrite']);
        add_filter('query_vars', [$this, 'register_query_vars']);
    }

    public function register_rewrite()
    {
        $base_path = trim((string) Settings::store_profile_base_path(), '/');
        if ($base_path === '') {
            $base_path = 'store';
        }

        add_rewrite_rule(
            '^' . preg_quote($base_path, '#') . '/([^/]+)/?$',
            'index.php?pagename=' . $base_path . '&vmp_store_user=$matches[1]',
            'top'
        );
    }

    public function register_query_vars($vars)
    {
        $vars[] = 'vmp_store_user';
        return $vars;
    }
}
