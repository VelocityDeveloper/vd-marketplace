<?php

namespace VelocityMarketplace\Modules\Account;

use VelocityMarketplace\Support\Settings;

class Account
{
    public function register()
    {
        add_action('init', [$this, 'handle_actions']);
        add_action('register_form', [$this, 'render_register_fields']);
        add_filter('registration_errors', [$this, 'validate_register_fields'], 10, 3);
        add_action('user_register', [$this, 'apply_register_fields']);
        add_action('login_footer', [$this, 'reposition_register_fields']);
    }

    public static function is_seller($user_id = 0)
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
        return in_array('vmp_seller', $roles, true) || in_array('administrator', $roles, true);
    }

    public function handle_actions()
    {
        if (isset($_GET['vmp_logout']) && $_GET['vmp_logout'] === '1') {
            $this->handle_logout();
            return;
        }
    }

    public function render_register_fields()
    {
        $role_type = isset($_REQUEST['vmp_role_type']) ? sanitize_key((string) wp_unslash($_REQUEST['vmp_role_type'])) : 'customer';
        if (!in_array($role_type, ['customer', 'seller'], true)) {
            $role_type = 'customer';
        }

        $name = isset($_REQUEST['vmp_name']) ? sanitize_text_field((string) wp_unslash($_REQUEST['vmp_name'])) : '';
        $phone = isset($_REQUEST['vmp_phone']) ? sanitize_text_field((string) wp_unslash($_REQUEST['vmp_phone'])) : '';
        ?>
        <div id="vmp-register-extra-fields">
            <p>
                <label for="vmp_role_type"><?php esc_html_e('Daftar Sebagai'); ?><br>
                    <select name="vmp_role_type" id="vmp_role_type">
                        <option value="customer" <?php selected($role_type, 'customer'); ?>>Customer</option>
                        <option value="seller" <?php selected($role_type, 'seller'); ?>>Seller</option>
                    </select>
                </label>
            </p>
            <p>
                <label for="vmp_name"><?php esc_html_e('Nama'); ?><br>
                    <input type="text" name="vmp_name" id="vmp_name" class="input" value="<?php echo esc_attr($name); ?>" size="25">
                </label>
            </p>
            <p>
                <label for="vmp_phone"><?php esc_html_e('No HP'); ?><br>
                    <input type="text" name="vmp_phone" id="vmp_phone" class="input" value="<?php echo esc_attr($phone); ?>" size="25">
                </label>
            </p>
        </div>
        <?php
    }

    public function reposition_register_fields()
    {
        $action = isset($_REQUEST['action']) ? sanitize_key((string) wp_unslash($_REQUEST['action'])) : 'login';
        if ($action !== 'register') {
            return;
        }
        ?>
        <script>
            (function () {
                var form = document.getElementById('registerform');
                var extra = document.getElementById('vmp-register-extra-fields');
                if (!form || !extra) {
                    return;
                }

                form.insertBefore(extra, form.firstChild);
            })();
        </script>
        <?php
    }

    public function validate_register_fields($errors, $sanitized_user_login, $user_email)
    {
        $role_type = isset($_POST['vmp_role_type']) ? sanitize_key((string) wp_unslash($_POST['vmp_role_type'])) : 'customer';
        if (!in_array($role_type, ['customer', 'seller'], true)) {
            $errors->add('vmp_role_type', __('Pilihan tipe akun tidak valid.'));
        }

        return $errors;
    }

    public function apply_register_fields($user_id)
    {
        $role_type = isset($_POST['vmp_role_type']) ? sanitize_key((string) wp_unslash($_POST['vmp_role_type'])) : 'customer';
        $name = isset($_POST['vmp_name']) ? sanitize_text_field((string) wp_unslash($_POST['vmp_name'])) : '';
        $phone = isset($_POST['vmp_phone']) ? sanitize_text_field((string) wp_unslash($_POST['vmp_phone'])) : '';

        $role = $role_type === 'seller' ? 'vmp_seller' : 'vmp_customer';
        $user = new \WP_User($user_id);
        $user->set_role($role);

        if ($name !== '') {
            update_user_meta($user_id, 'first_name', $name);
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $name,
            ]);
        }

        if ($phone !== '') {
            update_user_meta($user_id, 'vmp_phone', $phone);
        }
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

