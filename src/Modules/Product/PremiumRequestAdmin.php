<?php

namespace VelocityMarketplace\Modules\Product;

use VelocityMarketplace\Modules\Notification\NotificationRepository;
use VelocityMarketplace\Support\Contract;

class PremiumRequestAdmin
{
    public function register()
    {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'handle_actions']);
        add_filter('parent_file', [$this, 'fix_parent_menu']);
        add_filter('submenu_file', [$this, 'fix_submenu_file']);
    }

    public function admin_menu()
    {
        $pending_count = $this->pending_count();
        $menu_title = __('Pengajuan Premium', 'velocity-marketplace');
        if ($pending_count > 0) {
            $menu_title .= ' <span class="awaiting-mod"><span class="pending-count">' . (int) $pending_count . '</span></span>';
        }

        add_submenu_page(
            'wp-store',
            __('Pengajuan Premium', 'velocity-marketplace'),
            $menu_title,
            'manage_options',
            'vmp-premium-requests',
            [$this, 'render_page']
        );
    }

    public function fix_parent_menu($parent_file)
    {
        $page = isset($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : '';
        if ($page === 'vmp-premium-requests') {
            return 'wp-store';
        }

        return $parent_file;
    }

    public function fix_submenu_file($submenu_file)
    {
        $page = isset($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : '';
        if ($page === 'vmp-premium-requests') {
            return 'vmp-premium-requests';
        }

        return $submenu_file;
    }

    public function handle_actions()
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : '';
        $action = isset($_GET['vmp_premium_action']) ? sanitize_key((string) wp_unslash($_GET['vmp_premium_action'])) : '';
        $product_id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;

        if ($page !== 'vmp-premium-requests' || $action === '' || $product_id <= 0) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? (string) wp_unslash($_GET['_wpnonce']) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'vmp_premium_action_' . $product_id)) {
            return;
        }

        if (get_post_type($product_id) !== Contract::PRODUCT_POST_TYPE) {
            return;
        }

        $notice = '';
        if ($action === 'approve') {
            update_post_meta($product_id, 'premium_request', 0);
            update_post_meta($product_id, 'is_premium', 1);
            $notice = 'Pengajuan premium disetujui.';
            $this->notify_seller($product_id, 'Pengajuan premium disetujui.', 'Produk kamu sekarang masuk kategori premium.');
        } elseif ($action === 'reject') {
            update_post_meta($product_id, 'premium_request', 0);
            update_post_meta($product_id, 'is_premium', 0);
            $notice = 'Pengajuan premium ditolak.';
            $this->notify_seller($product_id, 'Pengajuan premium ditolak.', 'Pengajuan premium untuk produk kamu belum disetujui.');
        }

        if ($notice !== '') {
            wp_safe_redirect(add_query_arg([
                'page' => 'vmp-premium-requests',
                'vmp_notice' => $notice,
            ], admin_url('admin.php')));
            exit;
        }
    }

    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $notice = isset($_GET['vmp_notice']) ? sanitize_text_field((string) wp_unslash($_GET['vmp_notice'])) : '';
        $query = new \WP_Query([
            'post_type' => Contract::PRODUCT_POST_TYPE,
            'post_status' => ['publish', 'pending', 'draft'],
            'posts_per_page' => 100,
            'meta_query' => [
                [
                    'key' => 'premium_request',
                    'value' => '1',
                ],
            ],
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Pengajuan Premium', 'velocity-marketplace'); ?></h1>
            <?php if ($notice !== '') : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html($notice); ?></p></div>
            <?php endif; ?>

            <?php if (!$query->have_posts()) : ?>
                <p><?php echo esc_html__('Belum ada pengajuan premium yang menunggu review.', 'velocity-marketplace'); ?></p>
                <?php return; ?>
            <?php endif; ?>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Produk', 'velocity-marketplace'); ?></th>
                        <th><?php echo esc_html__('Seller', 'velocity-marketplace'); ?></th>
                        <th><?php echo esc_html__('Status Post', 'velocity-marketplace'); ?></th>
                        <th><?php echo esc_html__('Tanggal', 'velocity-marketplace'); ?></th>
                        <th><?php echo esc_html__('Aksi', 'velocity-marketplace'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($query->have_posts()) : $query->the_post(); ?>
                        <?php
                        $product_id = get_the_ID();
                        $seller_id = (int) get_post_field('post_author', $product_id);
                        $seller = $seller_id > 0 ? get_userdata($seller_id) : null;
                        $approve_url = wp_nonce_url(add_query_arg([
                            'page' => 'vmp-premium-requests',
                            'vmp_premium_action' => 'approve',
                            'product_id' => $product_id,
                        ], admin_url('admin.php')), 'vmp_premium_action_' . $product_id);
                        $reject_url = wp_nonce_url(add_query_arg([
                            'page' => 'vmp-premium-requests',
                            'vmp_premium_action' => 'reject',
                            'product_id' => $product_id,
                        ], admin_url('admin.php')), 'vmp_premium_action_' . $product_id);
                        ?>
                        <tr>
                            <td>
                                <strong><a href="<?php echo esc_url(get_edit_post_link($product_id)); ?>"><?php echo esc_html(get_the_title()); ?></a></strong>
                            </td>
                            <td><?php echo esc_html($seller && $seller->display_name !== '' ? $seller->display_name : 'Seller #' . $seller_id); ?></td>
                            <td><?php echo esc_html((string) get_post_status($product_id)); ?></td>
                            <td><?php echo esc_html(get_the_date('Y-m-d H:i', $product_id)); ?></td>
                            <td>
                                <a class="button button-primary button-small" href="<?php echo esc_url($approve_url); ?>"><?php echo esc_html__('Setujui', 'velocity-marketplace'); ?></a>
                                <a class="button button-secondary button-small" href="<?php echo esc_url($reject_url); ?>" onclick="return confirm('Tolak pengajuan premium ini?');"><?php echo esc_html__('Tolak', 'velocity-marketplace'); ?></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php
        wp_reset_postdata();
    }

    private function notify_seller($product_id, $title, $message)
    {
        $product_id = (int) $product_id;
        $seller_id = (int) get_post_field('post_author', $product_id);
        if ($seller_id <= 0) {
            return;
        }

        (new NotificationRepository())->add(
            $seller_id,
            'premium',
            $title,
            $message . ' Produk: ' . get_the_title($product_id) . '.',
            get_permalink($product_id)
        );
    }

    private function pending_count()
    {
        $counts = wp_count_posts(Contract::PRODUCT_POST_TYPE);
        if (!$counts) {
            return 0;
        }

        $query = new \WP_Query([
            'post_type' => Contract::PRODUCT_POST_TYPE,
            'post_status' => ['publish', 'pending', 'draft'],
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'premium_request',
                    'value' => '1',
                ],
            ],
        ]);

        return max(0, (int) $query->found_posts);
    }
}
