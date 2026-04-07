<?php

namespace VelocityMarketplace\Modules\Account;

use VelocityMarketplace\Support\Settings;

class Account
{
    public function register()
    {
        add_action('init', [$this, 'ensure_member_role'], 5);
        add_action('init', [$this, 'handle_actions']);
        add_action('user_register', [$this, 'apply_register_fields']);
        add_action('login_enqueue_scripts', [$this, 'customize_login_logo']);
        add_filter('show_admin_bar', [$this, 'filter_admin_bar']);
        add_filter('login_headerurl', [$this, 'filter_login_header_url']);
        add_filter('login_headertext', [$this, 'filter_login_header_text']);
        add_filter('login_redirect', [$this, 'filter_login_redirect'], 10, 3);
        add_filter('wp_store_profile_tabs', [$this, 'extend_core_profile_tabs'], 20, 2);
        add_filter('wp_store_profile_panels', [$this, 'extend_core_profile_panels'], 20, 2);
        add_filter('wp_store_tracking_query_param', [$this, 'filter_tracking_query_param'], 20, 2);
        add_filter('wp_store_tracking_input_label', [$this, 'filter_tracking_input_label'], 20, 3);
        add_filter('wp_store_tracking_input_placeholder', [$this, 'filter_tracking_input_placeholder'], 20, 3);
        add_filter('wp_store_tracking_submit_label', [$this, 'filter_tracking_submit_label'], 20, 3);
        add_filter('wp_store_tracking_empty_help', [$this, 'filter_tracking_empty_help'], 20, 3);
        add_filter('wp_store_tracking_resolved_order_id', [$this, 'resolve_tracking_order_id'], 20, 3);
        add_action('wp_store_tracking_after_order_content', [$this, 'render_tracking_marketplace_extension'], 20, 2);
    }

    public function ensure_member_role()
    {
        if (!get_role('vd_member')) {
            add_role('vd_member', __('Member Marketplace', 'velocity-marketplace'), [
                'read' => true,
                'upload_files' => true,
            ]);
        }

        if (get_option('vmp_roles_normalized_to_vd_member') === '1') {
            return;
        }

        $legacy_users = get_users([
            'role__in' => ['vmp_member'],
            'fields' => ['ID'],
            'number' => -1,
        ]);

        foreach ((array) $legacy_users as $legacy_user) {
            $user_id = isset($legacy_user->ID) ? (int) $legacy_user->ID : 0;
            if ($user_id <= 0) {
                continue;
            }

            $user = new \WP_User($user_id);
            $user->set_role('vd_member');
        }

        remove_role('vmp_member');
        update_option('vmp_roles_normalized_to_vd_member', '1', false);
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
        return in_array('vd_member', $roles, true) || in_array('administrator', $roles, true);
    }

    public static function can_manage_store_profile($user_id = 0)
    {
        return self::is_member($user_id);
    }

    public static function can_sell($user_id = 0)
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
        if (in_array('administrator', $roles, true)) {
            return true;
        }

        if (!in_array('vd_member', $roles, true)) {
            return false;
        }

        // Satu titik aturan seller aktif. Kalau nanti logikanya berkembang,
        // perubahan cukup dipusatkan di helper ini tanpa menyentuh semua caller.
        return self::seller_flag_enabled($user_id);
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
        if (self::can_sell($user_id)) {
            return 'Seller';
        }
        if (in_array('vd_member', $roles, true)) {
            return 'Member';
        }

        return 'User';
    }

    public function handle_actions()
    {
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'vmp_last_active_at', current_time('mysql'));
        }

        if (isset($_GET['vmp_logout']) && $_GET['vmp_logout'] === '1') {
            $this->handle_logout();
            return;
        }
    }

    public function apply_register_fields($user_id)
    {
        $user = new \WP_User($user_id);
        $user->set_role('vd_member');
        update_user_meta($user_id, '_store_is_seller', 0);
    }

    // Flag seller dasar sengaja dipisah agar future logic seperti moderation,
    // subscription, atau capability tambahan bisa dibungkus di sini.
    private static function seller_flag_enabled($user_id)
    {
        return !empty(get_user_meta((int) $user_id, '_store_is_seller', true));
    }

    public function filter_admin_bar($show)
    {
        if (current_user_can('manage_options')) {
            return $show;
        }

        return false;
    }

    public function filter_login_redirect($redirect_to, $requested_redirect_to, $user)
    {
        if (!($user instanceof \WP_User)) {
            return $redirect_to;
        }

        if (in_array('administrator', (array) $user->roles, true)) {
            return $requested_redirect_to !== '' ? $requested_redirect_to : $redirect_to;
        }

        if (self::is_member((int) $user->ID)) {
            return add_query_arg(['tab' => 'account_profile'], Settings::profile_url());
        }

        return $redirect_to;
    }

    public function customize_login_logo()
    {
        $logo = $this->login_logo_data();
        if (empty($logo['url'])) {
            return;
        }

        $width = max(120, min(320, (int) ($logo['width'] ?? 320)));
        $height = max(60, min(140, (int) ($logo['height'] ?? 100)));
        ?>
        <style>
            body.login div#login h1 a {
                background-image: url('<?php echo esc_url($logo['url']); ?>');
                background-size: contain;
                background-position: center center;
                background-repeat: no-repeat;
                width: <?php echo (int) $width; ?>px;
                height: <?php echo (int) $height; ?>px;
                max-width: 100%;
            }
        </style>
        <?php
    }

    public function filter_login_header_url($url)
    {
        return home_url('/');
    }

    public function filter_login_header_text($text)
    {
        return get_bloginfo('name');
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

    private function login_logo_data()
    {
        $logo_id = (int) get_theme_mod('custom_logo');
        if ($logo_id <= 0) {
            return [];
        }

        $image = wp_get_attachment_image_src($logo_id, 'full');
        if (!is_array($image) || empty($image[0])) {
            return [];
        }

        return [
            'url' => (string) $image[0],
            'width' => isset($image[1]) ? (int) $image[1] : 0,
            'height' => isset($image[2]) ? (int) $image[2] : 0,
        ];
    }

    public function extend_core_profile_tabs($tabs, $context = [])
    {
        $user_id = isset($context['user_id']) ? (int) $context['user_id'] : get_current_user_id();
        if ($user_id <= 0 || !is_user_logged_in()) {
            return $tabs;
        }

        $message_count = (new \VelocityMarketplace\Modules\Message\MessageRepository())->unread_count($user_id);
        $notification_count = (new \VelocityMarketplace\Modules\Notification\NotificationRepository())->unread_count($user_id);

        $tabs['messages'] = [
            'key' => 'messages',
            'label' => __('Pesan', 'velocity-marketplace'),
            'priority' => 50,
            'badge_binding' => 'messageUnreadCount',
            'badge_initial' => $message_count,
        ];

        $tabs['notifications'] = [
            'key' => 'notifications',
            'label' => __('Notifikasi', 'velocity-marketplace'),
            'priority' => 60,
            'badge_binding' => 'notificationUnreadCount',
            'badge_initial' => $notification_count,
        ];

        if (self::can_manage_store_profile($user_id)) {
            $tabs['seller_profile'] = [
                'key' => 'seller_profile',
                'label' => __('Profil Toko', 'velocity-marketplace'),
                'priority' => 100,
            ];
        }

        if (self::can_manage_store_profile($user_id)) {
            $tabs['seller_home'] = [
                'key' => 'seller_home',
                'label' => __('Beranda Toko', 'velocity-marketplace'),
                'priority' => 110,
            ];
            $tabs['seller_report'] = [
                'key' => 'seller_report',
                'label' => __('Laporan', 'velocity-marketplace'),
                'priority' => 120,
            ];
            $tabs['seller_products'] = [
                'key' => 'seller_products',
                'label' => __('Produk', 'velocity-marketplace'),
                'priority' => 130,
            ];
        }

        return $tabs;
    }

    public function extend_core_profile_panels($panels, $context = [])
    {
        $user_id = isset($context['user_id']) ? (int) $context['user_id'] : get_current_user_id();
        if ($user_id <= 0 || !is_user_logged_in()) {
            return $panels;
        }

        $panels['messages'] = [
            'key' => 'messages',
            'priority' => 50,
            'render_callback' => [$this, 'render_core_messages_panel'],
        ];

        $panels['notifications'] = [
            'key' => 'notifications',
            'priority' => 60,
            'render_callback' => [$this, 'render_core_notifications_panel'],
        ];

        if (self::can_manage_store_profile($user_id)) {
            $panels['seller_profile'] = [
                'key' => 'seller_profile',
                'priority' => 100,
                'render_callback' => [$this, 'render_core_seller_profile_panel'],
            ];
        }

        if (self::can_manage_store_profile($user_id)) {
            $panels['seller_home'] = [
                'key' => 'seller_home',
                'priority' => 110,
                'render_callback' => [$this, 'render_core_seller_home_panel'],
            ];
            $panels['seller_report'] = [
                'key' => 'seller_report',
                'priority' => 120,
                'render_callback' => [$this, 'render_core_seller_report_panel'],
            ];
            $panels['seller_products'] = [
                'key' => 'seller_products',
                'priority' => 130,
                'render_callback' => [$this, 'render_core_seller_products_panel'],
            ];
        }

        return $panels;
    }

    public function filter_tracking_query_param($query_param, $atts = [])
    {
        return 'invoice';
    }

    public function filter_tracking_input_label($label, $query_param = 'order', $atts = [])
    {
        return 'Kode Invoice';
    }

    public function filter_tracking_input_placeholder($placeholder, $query_param = 'order', $atts = [])
    {
        return 'Masukkan Kode Invoice';
    }

    public function filter_tracking_submit_label($label, $query_param = 'order', $atts = [])
    {
        return 'Lihat Status Pesanan';
    }

    public function filter_tracking_empty_help($help, $query_param = 'order', $atts = [])
    {
        return 'Masukkan kode invoice di form berikut untuk melihat status pesanan.';
    }

    public function resolve_tracking_order_id($order_id, $input = '', $context = [])
    {
        $order_id = (int) $order_id;
        if ($order_id > 0) {
            return $order_id;
        }

        $invoice = sanitize_text_field((string) $input);
        if ($invoice === '') {
            return 0;
        }

        $query = new \WP_Query([
            'post_type' => 'store_order',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'vmp_invoice',
                    'value' => $invoice,
                    'compare' => '=',
                ],
            ],
        ]);

        if (empty($query->posts)) {
            return 0;
        }

        return (int) $query->posts[0];
    }

    public function render_tracking_marketplace_extension($order_id, $context = [])
    {
        $order_id = (int) $order_id;
        if ($order_id <= 0 || get_post_type($order_id) !== 'store_order') {
            return;
        }

        $html = \VelocityMarketplace\Frontend\Template::render('components/tracking-marketplace-fulfillment', [
            'order_id' => $order_id,
            'current_user_id' => get_current_user_id(),
            'currency' => isset($context['currency']) ? (string) $context['currency'] : 'Rp',
        ]);

        if ($html !== '') {
            echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }

    public function render_core_messages_panel($context = [])
    {
        $user_id = isset($context['user_id']) ? (int) $context['user_id'] : get_current_user_id();
        $message_context = $this->message_panel_context($user_id);
        return \VelocityMarketplace\Frontend\Template::render('account/messages', $message_context);
    }

    public function render_core_notifications_panel($context = [])
    {
        $user_id = isset($context['user_id']) ? (int) $context['user_id'] : get_current_user_id();
        $notification_repo = new \VelocityMarketplace\Modules\Notification\NotificationRepository();
        return \VelocityMarketplace\Frontend\Template::render('account/notifications', [
            'notifications' => $notification_repo->all($user_id),
        ]);
    }

    public function render_core_seller_home_panel($context = [])
    {
        $user_id = isset($context['user_id']) ? (int) $context['user_id'] : get_current_user_id();
        if (!self::can_sell($user_id)) {
            return $this->render_inactive_seller_panel(
                __('Beranda Toko', 'velocity-marketplace'),
                __('Aktifkan seller di tab Profil Toko untuk melihat ringkasan operasional toko Anda.', 'velocity-marketplace')
            );
        }

        return \VelocityMarketplace\Frontend\Template::render('seller/home', [
            'current_user_id' => $user_id,
            'money' => $this->money_formatter(),
            'profile_complete' => $this->profile_complete($user_id),
        ]);
    }

    public function render_core_seller_report_panel($context = [])
    {
        $user_id = isset($context['user_id']) ? (int) $context['user_id'] : get_current_user_id();
        if (!self::can_sell($user_id)) {
            return $this->render_inactive_seller_panel(
                __('Laporan', 'velocity-marketplace'),
                __('Aktifkan seller di tab Profil Toko untuk membuka laporan penjualan toko.', 'velocity-marketplace')
            );
        }

        return \VelocityMarketplace\Frontend\Template::render('seller/report', [
            'current_user_id' => $user_id,
            'money' => $this->money_formatter(),
        ]);
    }

    public function render_core_seller_products_panel($context = [])
    {
        $user_id = isset($context['user_id']) ? (int) $context['user_id'] : get_current_user_id();
        if (!self::can_sell($user_id)) {
            return $this->render_inactive_seller_panel(
                __('Produk', 'velocity-marketplace'),
                __('Aktifkan seller di tab Profil Toko untuk mulai menambahkan dan mengelola produk.', 'velocity-marketplace')
            );
        }

        return \VelocityMarketplace\Frontend\Template::render('seller/products', [
            'current_user_id' => $user_id,
            'profile_complete' => $this->profile_complete($user_id),
        ]);
    }

    public function render_core_seller_profile_panel($context = [])
    {
        $user_id = isset($context['user_id']) ? (int) $context['user_id'] : get_current_user_id();
        return \VelocityMarketplace\Frontend\Template::render('seller/profile', [
            'current_user_id' => $user_id,
        ]);
    }

    private function profile_complete($user_id)
    {
        $store_name = (string) get_user_meta((int) $user_id, 'vmp_store_name', true);
        $store_address = (string) get_user_meta((int) $user_id, 'vmp_store_address', true);
        return $store_name !== '' && $store_address !== '';
    }

    private function render_inactive_seller_panel($title, $message)
    {
        return \VelocityMarketplace\Frontend\Template::render('seller/inactive-state', [
            'title' => (string) $title,
            'message' => (string) $message,
            'profile_url' => add_query_arg(['tab' => 'seller_profile'], Settings::profile_url()),
        ]);
    }

    private function money_formatter()
    {
        return static function ($value) {
            $symbol = \VelocityMarketplace\Support\Settings::currency_symbol();
            return esc_html($symbol) . ' ' . number_format((float) $value, 0, ',', '.');
        };
    }

    private function message_panel_context($user_id)
    {
        $message_repo = new \VelocityMarketplace\Modules\Message\MessageRepository();
        $selected_message_to = isset($_GET['message_to']) ? (int) wp_unslash($_GET['message_to']) : 0;
        $selected_message_order = isset($_GET['message_order']) ? (int) wp_unslash($_GET['message_order']) : 0;
        $selected_message_invoice = $selected_message_order > 0 ? (string) get_post_meta($selected_message_order, 'vmp_invoice', true) : '';
        $message_contacts = $message_repo->contacts($user_id);
        $selected_contact_exists = false;

        foreach ($message_contacts as $contact_row) {
            if ((int) ($contact_row['id'] ?? 0) === $selected_message_to) {
                $selected_contact_exists = true;
                break;
            }
        }

        if ($selected_message_to > 0 && !$selected_contact_exists) {
            $selected_user = get_userdata($selected_message_to);
            if ($selected_user && (current_user_can('manage_options') || $message_repo->can_contact($user_id, $selected_message_to))) {
                $message_contacts[] = [
                    'id' => $selected_message_to,
                    'name' => $selected_user->display_name !== '' ? $selected_user->display_name : $selected_user->user_login,
                    'role' => self::user_role_label($selected_message_to),
                ];
            }
        }

        if ($selected_message_to > 0) {
            $message_repo->mark_thread_read($selected_message_to, $user_id);
        }

        $message_contacts = $message_repo->contacts($user_id);
        $message_thread = $selected_message_to > 0 ? $message_repo->thread($selected_message_to, $user_id, 200) : [];
        $selected_message_contact = null;

        foreach ($message_contacts as $contact_row) {
            if ((int) ($contact_row['id'] ?? 0) === $selected_message_to) {
                $selected_message_contact = $contact_row;
                break;
            }
        }

        if (!$selected_message_contact && $selected_message_to > 0) {
            $selected_user = get_userdata($selected_message_to);
            if ($selected_user && (current_user_can('manage_options') || $message_repo->can_contact($user_id, $selected_message_to))) {
                $selected_message_contact = [
                    'id' => $selected_message_to,
                    'name' => $selected_user->display_name !== '' ? $selected_user->display_name : $selected_user->user_login,
                    'role' => self::user_role_label($selected_message_to),
                    'last_message' => '',
                    'last_created_at' => '',
                    'last_order_id' => 0,
                    'last_order_invoice' => '',
                    'unread_count' => 0,
                ];
                array_unshift($message_contacts, $selected_message_contact);
            }
        }

        return [
            'current_user_id' => $user_id,
            'message_contacts' => $message_contacts,
            'selected_message_to' => $selected_message_to,
            'selected_message_order' => $selected_message_order,
            'selected_message_invoice' => $selected_message_invoice,
            'message_thread' => $message_thread,
            'selected_message_contact' => $selected_message_contact,
        ];
    }
}

