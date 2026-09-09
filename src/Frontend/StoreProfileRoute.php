<?php

namespace VelocityMarketplace\Frontend;

use VelocityMarketplace\Support\Settings;

class StoreProfileRoute
{
    const REWRITE_SCHEMA_VERSION = '1';
    const REWRITE_SIGNATURE_OPTION = 'vmp_store_profile_rewrite_signature';

    public function register()
    {
        add_action('init', [$this, 'register_rewrite']);
        add_action('init', [$this, 'maybe_flush_rewrite'], 99);
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

    public function maybe_flush_rewrite()
    {
        $signature = $this->rewrite_signature();
        if ((string) get_option(self::REWRITE_SIGNATURE_OPTION, '') === $signature) {
            return;
        }

        flush_rewrite_rules(false);
        update_option(self::REWRITE_SIGNATURE_OPTION, $signature, false);
    }

    public function clear_rewrite_signature()
    {
        delete_option(self::REWRITE_SIGNATURE_OPTION);
    }

    private function rewrite_signature()
    {
        return md5(self::REWRITE_SCHEMA_VERSION . '|' . trim((string) Settings::store_profile_base_path(), '/'));
    }
}
