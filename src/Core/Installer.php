<?php

namespace VelocityMarketplace\Core;

class Installer
{
    public function activate()
    {
        $this->ensure_roles();
        $this->create_default_pages();
        $this->seed_default_settings();
    }

    private function create_default_pages()
    {
        $pages = [
            'katalog' => [
                'title' => 'Katalog',
                'content' => '[vmp_catalog]',
            ],
            'keranjang' => [
                'title' => 'Keranjang',
                'content' => '[vmp_cart]',
            ],
            'checkout' => [
                'title' => 'Checkout',
                'content' => '[vmp_checkout]',
            ],
            'myaccount' => [
                'title' => 'My Account',
                'content' => '[vmp_profile]',
            ],
            'tracking' => [
                'title' => 'Lacak Pesanan',
                'content' => '[vmp_tracking]',
            ],
            'toko' => [
                'title' => 'Profil Toko',
                'content' => '[vmp_store_profile]',
            ],
        ];

        $stored = get_option(VMP_PAGES_OPTION, []);
        if (!is_array($stored)) {
            $stored = [];
        }

        foreach ($pages as $slug => $page) {
            $existing = get_page_by_path($slug);
            if ($existing && isset($existing->ID)) {
                $stored[$slug] = (int) $existing->ID;
                continue;
            }

            $post_id = wp_insert_post([
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_title' => $page['title'],
                'post_name' => $slug,
                'post_content' => $page['content'],
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            ]);

            if (!is_wp_error($post_id) && $post_id) {
                $stored[$slug] = (int) $post_id;
            }
        }

        update_option(VMP_PAGES_OPTION, $stored);
    }

    private function seed_default_settings()
    {
        $current = get_option(VMP_SETTINGS_OPTION, []);
        if (!is_array($current)) {
            $current = [];
        }

        $defaults = [
            'currency' => 'IDR',
            'currency_symbol' => 'Rp',
            'default_order_status' => 'pending_payment',
            'payment_methods' => ['bank', 'duitku', 'paypal'],
            'seller_product_status' => 'publish',
            'bank_accounts' => [],
        ];

        update_option(VMP_SETTINGS_OPTION, array_merge($defaults, $current));
    }

    private function ensure_roles()
    {
        remove_role('vmp_customer');
        remove_role('vmp_seller');

        add_role('vmp_member', 'Marketplace Member', [
            'read' => true,
            'upload_files' => true,
        ]);
    }
}
