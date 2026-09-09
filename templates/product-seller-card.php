<?php
use VelocityMarketplace\Modules\Product\ProductData;
use VelocityMarketplace\Modules\Review\StarSellerService;
use VelocityMarketplace\Support\Settings;

$product_id = isset($product_id) ? (int) $product_id : 0;
if ($product_id <= 0 || get_post_type($product_id) !== 'store_product') {
    return;
}

$seller_id = (int) get_post_field('post_author', $product_id);
if ($seller_id <= 0) {
    return;
}

$seller_user = get_userdata($seller_id);
if (!$seller_user) {
    return;
}

$seller_store_name = (string) get_user_meta($seller_id, 'vmp_store_name', true);
$seller_city = (string) get_user_meta($seller_id, 'vmp_store_city', true);
$seller_province = (string) get_user_meta($seller_id, 'vmp_store_province', true);
$seller_location = trim(implode(', ', array_filter([$seller_city, $seller_province])));
$seller_cod_enabled = !empty(get_user_meta($seller_id, 'vmp_cod_enabled', true));
$seller_avatar_id = (int) get_user_meta($seller_id, 'vmp_store_avatar_id', true);
$seller_avatar_url = $seller_avatar_id > 0 ? wp_get_attachment_image_url($seller_avatar_id, 'thumbnail') : '';
if ($seller_avatar_url === '') {
    $seller_avatar_url = ProductData::no_image_url();
}
$seller_last_active_at = (string) get_user_meta($seller_id, 'vmp_last_active_at', true);
$seller_last_active_text = '-';
if ($seller_last_active_at !== '') {
    $seller_last_active_ts = strtotime($seller_last_active_at);
    if ($seller_last_active_ts) {
        $seller_last_active_text = sprintf(__('%s yang lalu', 'velocity-marketplace'), human_time_diff($seller_last_active_ts, current_time('timestamp')));
    }
}
$seller_name = $seller_store_name !== ''
    ? $seller_store_name
    : ($seller_user && $seller_user->display_name !== '' ? $seller_user->display_name : ($seller_user ? $seller_user->user_login : __('Penjual', 'velocity-marketplace')));
$seller_summary = (new StarSellerService())->summary($seller_id);
$seller_rating_average = isset($seller_summary['rating_average']) ? (float) $seller_summary['rating_average'] : 0.0;
$seller_review_count = (int) ($seller_summary['rating_count'] ?? 0);
$seller_completed_orders = (int) ($seller_summary['completed_orders'] ?? 0);
$seller_product_count = (int) count_user_posts($seller_id, 'store_product', true);
$seller_joined_date = !empty($seller_user->user_registered) ? mysql2date('d M Y', $seller_user->user_registered) : '-';
$store_profile_url = Settings::store_profile_url($seller_id);
$message_url = is_user_logged_in()
    ? add_query_arg(['tab' => 'messages', 'message_to' => $seller_id], Settings::profile_url())
    : wp_login_url(add_query_arg(['tab' => 'messages', 'message_to' => $seller_id], Settings::profile_url()));
?>
<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="card-body p-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-5">
                <div class="row g-3 align-items-center">
                    <div class="col-3">
                        <a href="<?php echo esc_url($store_profile_url); ?>" class="ratio ratio-1x1 d-block rounded-circle overflow-hidden border bg-light">
                            <img src="<?php echo esc_url($seller_avatar_url); ?>" alt="<?php echo esc_attr($seller_name); ?>" class="w-100 h-100 object-fit-cover">
                        </a>
                    </div>
                    <div class="col-9">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <a href="<?php echo esc_url($store_profile_url); ?>" class="h5 fw-bold text-dark text-decoration-none mb-0"><?php echo esc_html($seller_name); ?></a>
                            <?php if (!empty($seller_summary['is_star_seller'])) : ?>
                                <span class="badge rounded-pill bg-warning text-dark"><?php echo esc_html__('Star Seller', 'velocity-marketplace'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="small text-muted mb-3"><?php echo esc_html(sprintf(__('Aktif %s', 'velocity-marketplace'), $seller_last_active_text)); ?></div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?php echo esc_url($message_url); ?>" class="btn btn-dark btn-sm"><?php echo esc_html__('Hubungi Toko', 'velocity-marketplace'); ?></a>
                            <a href="<?php echo esc_url($store_profile_url); ?>" class="btn btn-outline-dark btn-sm"><?php echo esc_html__('Kunjungi Toko', 'velocity-marketplace'); ?></a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="row row-cols-2 row-cols-md-3 g-4">
                    <div class="col">
                        <div class="small text-muted mb-1"><?php echo esc_html__('Rating Toko', 'velocity-marketplace'); ?></div>
                        <div class="d-flex flex-wrap align-items-baseline gap-2">
                            <span class="fw-semibold text-primary"><?php echo esc_html(number_format($seller_rating_average, 1, ',', '')); ?>/5</span>
                            <span class="small text-muted"><?php echo esc_html(sprintf(__('%d ulasan', 'velocity-marketplace'), $seller_review_count)); ?></span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="small text-muted mb-1"><?php echo esc_html__('Produk', 'velocity-marketplace'); ?></div>
                        <div class="fw-semibold text-primary"><?php echo esc_html((string) $seller_product_count); ?></div>
                    </div>
                    <div class="col">
                        <div class="small text-muted mb-1"><?php echo esc_html__('Pesanan Selesai', 'velocity-marketplace'); ?></div>
                        <div class="fw-semibold text-primary"><?php echo esc_html((string) $seller_completed_orders); ?></div>
                    </div>
                    <div class="col">
                        <div class="small text-muted mb-1"><?php echo esc_html__('Bergabung', 'velocity-marketplace'); ?></div>
                        <div class="fw-semibold"><?php echo esc_html($seller_joined_date); ?></div>
                    </div>
                    <div class="col">
                        <div class="small text-muted mb-1"><?php echo esc_html__('Pembayaran COD', 'velocity-marketplace'); ?></div>
                        <div class="fw-semibold"><?php echo esc_html($seller_cod_enabled ? __('Tersedia', 'velocity-marketplace') : __('Tidak Aktif', 'velocity-marketplace')); ?></div>
                    </div>
                    <div class="col">
                        <div class="small text-muted mb-1"><?php echo esc_html__('Lokasi', 'velocity-marketplace'); ?></div>
                        <div class="fw-semibold"><?php echo esc_html($seller_location !== '' ? $seller_location : '-'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
