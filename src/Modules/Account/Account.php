<?php

namespace VelocityMarketplace\Modules\Account;

use VelocityMarketplace\Support\Settings;

class Account
{
    public function register()
    {
        add_action('init', [$this, 'handle_actions']);
        add_action('user_register', [$this, 'apply_register_fields']);
    }

    public static function is_member($user_id = 0)
    {
        $user_id = $user_id > 0 ? (int) $user_id : get_current_user_id();
        if ($user_id <= 0) {
            return false;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $roles = is_array($user->roles) ? $user->roles : [];
        return in_array('vmp_member', $roles, true) || in_array('administrator', $roles, true);
    }

    public static function can_sell($user_id = 0)
    {
        return self::is_member($user_id);
    }

    public static function user_role_label($user_id = 0)
    {
        $user_id = $user_id > 0 ? (int) $user_id : get_current_user_id();
        if ($user_id <= 0) {
            return '';
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return '';
        }

        $roles = is_array($user->roles) ? $user->roles : [];
        if (in_array('administrator', $roles, true)) {
            return 'Admin';
        }
        if (in_array('vmp_member', $roles, true)) {
            return 'Member';
        }

        return 'User';
    }

    public function handle_actions()
    {
        if (isset($_GET['vmp_logout']) && $_GET['vmp_logout'] === '1') {
            $this->handle_logout();
            return;
        }
    }

    public function apply_register_fields($user_id)
    {
        $user = new \WP_User($user_id);
        $user->set_role('vmp_member');
    }

    private function handle_logout()
    {
        $nonce = isset($_GET['vmp_nonce']) ? sanitize_text_field((string) $_GET['vmp_nonce']) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'vmp_logout')) {
            return;
        }

        wp_logout();
        $this->redirect_with(['vmp_notice' => 'Logout berhasil.']);
    }

    private function redirect_with($params = [])
    {
        $target = wp_get_referer();
        if (!$target) {
            $target = Settings::profile_url();
        }
        if (!is_array($params)) {
            $params = [];
        }
        $url = add_query_arg($params, $target);
        wp_safe_redirect($url);
        exit;
    }
}

