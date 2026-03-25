<?php

namespace VelocityMarketplace\Frontend;

use VelocityMarketplace\Support\Settings;
use VelocityMarketplace\Modules\Product\ProductData;

class Assets
{
    public function register()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue()
    {
        $context = $this->get_enqueue_context();
        if (empty($context['enabled'])) {
            return;
        }

        wp_register_script(
            'alpinejs',
            'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js',
            [],
            null,
            true
        );

        wp_enqueue_style(
            'velocity-marketplace-frontend-css',
            VMP_URL . 'assets/css/frontend.css',
            [],
            VMP_VERSION
        );

        wp_enqueue_script(
            'velocity-marketplace-frontend-js',
            VMP_URL . 'assets/js/frontend.js',
            [],
            VMP_VERSION,
            true
        );

        wp_enqueue_script('alpinejs');

        if (!empty($context['profile'])) {
            wp_enqueue_style(
                'velocity-marketplace-dashboard-css',
                VMP_URL . 'assets/css/dashboard.css',
                ['velocity-marketplace-frontend-css'],
                VMP_VERSION
            );

            wp_enqueue_script(
                'velocity-marketplace-dashboard-js',
                VMP_URL . 'assets/js/dashboard.js',
                [],
                VMP_VERSION,
                true
            );

            wp_enqueue_script(
                'velocity-marketplace-media-js',
                VMP_URL . 'assets/js/media.js',
                [],
                VMP_VERSION,
                true
            );

            wp_enqueue_media();
            if (function_exists('wp_enqueue_editor')) {
                wp_enqueue_editor();
            }
        }

        $pages = get_option(VMP_PAGES_OPTION, []);
        $catalog_url = $this->resolve_page_url($pages, 'katalog', '/katalog/');
        $cart_url = $this->resolve_page_url($pages, 'keranjang', '/keranjang/');
        $checkout_url = $this->resolve_page_url($pages, 'checkout', '/checkout/');
        $profile_url = $this->resolve_page_url($pages, 'myaccount', '/myaccount/');
        $currency = Settings::currency();
        $currency_symbol = Settings::currency_symbol();
        $payment_methods = Settings::payment_methods();
        $customer_profile = $this->customer_profile_payload();

        wp_localize_script('velocity-marketplace-frontend-js', 'vmpSettings', [
            'restUrl' => esc_url_raw(rest_url('velocity-marketplace/v1/')),
            'nonce' => wp_create_nonce('wp_rest'),
            'catalogUrl' => esc_url_raw($catalog_url),
            'cartUrl' => esc_url_raw($cart_url),
            'checkoutUrl' => esc_url_raw($checkout_url),
            'profileUrl' => esc_url_raw($profile_url),
            'currency' => $currency,
            'currencySymbol' => $currency_symbol,
            'paymentMethods' => $payment_methods,
            'isLoggedIn' => is_user_logged_in(),
            'currentUserId' => get_current_user_id(),
            'canManageOptions' => current_user_can('manage_options'),
            'noImageUrl' => esc_url_raw(ProductData::no_image_url()),
            'customerProfile' => $customer_profile,
        ]);
    }

    private function customer_profile_payload()
    {
        if (!is_user_logged_in()) {
            return [
                'name' => '',
                'email' => '',
                'phone' => '',
                'address' => '',
                'postal_code' => '',
                'destination_province_id' => '',
                'destination_province_name' => '',
                'destination_city_id' => '',
                'destination_city_name' => '',
                'destination_subdistrict_id' => '',
                'destination_subdistrict_name' => '',
            ];
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        return [
            'name' => (string) get_user_meta($user_id, 'vmp_name', true) ?: ($user && $user->display_name !== '' ? (string) $user->display_name : ''),
            'email' => $user ? (string) $user->user_email : '',
            'phone' => (string) get_user_meta($user_id, 'vmp_phone', true),
            'address' => (string) get_user_meta($user_id, 'vmp_address', true),
            'postal_code' => (string) get_user_meta($user_id, 'vmp_postcode', true),
            'destination_province_id' => (string) get_user_meta($user_id, 'vmp_province_id', true),
            'destination_province_name' => (string) get_user_meta($user_id, 'vmp_province', true),
            'destination_city_id' => (string) get_user_meta($user_id, 'vmp_city_id', true),
            'destination_city_name' => (string) get_user_meta($user_id, 'vmp_city', true),
            'destination_subdistrict_id' => (string) get_user_meta($user_id, 'vmp_subdistrict_id', true),
            'destination_subdistrict_name' => (string) get_user_meta($user_id, 'vmp_subdistrict', true),
        ];
    }

    private function get_enqueue_context()
    {
        if (is_admin()) {
            return [
                'enabled' => false,
                'profile' => false,
            ];
        }

        if (is_post_type_archive('vmp_product') || is_singular('vmp_product')) {
            return [
                'enabled' => true,
                'profile' => false,
            ];
        }

        if (is_page()) {
            global $post;
            if ($post && isset($post->post_content)) {
                $content = (string) $post->post_content;
                $enabled = $this->content_has_any_shortcode($content, [
                    'vmp_catalog',
                    'vmp_products',
                    'vmp_product_card',
                    'vmp_thumbnail',
                    'vmp_price',
                    'vmp_add_to_cart',
                    'vmp_add_to_wishlist',
                    'vmp_cart',
                    'vmp_checkout',
                    'vmp_profile',
                    'vmp_tracking',
                    'vmp_store_profile',
                ]);
                $profile = $this->content_has_any_shortcode($content, [
                    'vmp_profile',
                ]);

                return [
                    'enabled' => $enabled,
                    'profile' => $profile,
                ];
            }
        }

        return [
            'enabled' => false,
            'profile' => false,
        ];
    }

    private function content_has_any_shortcode($content, $shortcodes)
    {
        foreach ((array) $shortcodes as $shortcode) {
            if (has_shortcode($content, (string) $shortcode)) {
                return true;
            }
        }

        return false;
    }

    private function resolve_page_url($pages, $key, $fallback)
    {
        if (is_array($pages) && isset($pages[$key])) {
            $url = get_permalink((int) $pages[$key]);
            if ($url) {
                return $url;
            }
        }

        return site_url($fallback);
    }
}
