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

        $pages = get_option('velocity_marketplace_pages', []);
        $catalog_url = $this->resolve_page_url($pages, 'katalog', '/katalog/');
        $cart_url = $this->resolve_page_url($pages, 'keranjang', '/keranjang/');
        $checkout_url = $this->resolve_page_url($pages, 'checkout', '/checkout/');
        $profile_url = $this->resolve_page_url($pages, 'myaccount', '/myaccount/');
        $currency = Settings::currency();
        $currency_symbol = Settings::currency_symbol();
        $payment_methods = Settings::payment_methods();

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
            'noImageUrl' => esc_url_raw(ProductData::no_image_url()),
        ]);
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
                    'velocity_marketplace_catalog',
                    'velocity_marketplace_products',
                    'velocity_marketplace_product_card',
                    'velocity_marketplace_thumbnail',
                    'velocity_marketplace_price',
                    'velocity_marketplace_add_to_cart',
                    'velocity_marketplace_add_to_wishlist',
                    'velocity_marketplace_cart',
                    'velocity_marketplace_checkout',
                    'velocity_marketplace_profile',
                    'velocity_marketplace_tracking',
                    'vm_catalog',
                    'vm_products',
                    'vm_product_card',
                    'vm_thumbnail',
                    'vm_price',
                    'vm_add_to_cart',
                    'vm_add_to_wishlist',
                    'vm_cart',
                    'vm_checkout',
                    'vm_profile',
                    'vm_tracking',
                ]);
                $profile = $this->content_has_any_shortcode($content, [
                    'velocity_marketplace_profile',
                    'vm_profile',
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
