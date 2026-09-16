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
$seller_phone = metadata_exists('user', $seller_id, 'vmp_store_phone') ? trim((string) get_user_meta($seller_id, 'vmp_store_phone', true)) : '';
$seller_whatsapp = metadata_exists('user', $seller_id, 'vmp_store_whatsapp') ? preg_replace('/\D+/', '', (string) get_user_meta($seller_id, 'vmp_store_whatsapp', true)) : '';
$seller_email = sanitize_email((string) $seller_user->user_email);
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
                            <a href="<?php echo esc_url($message_url); ?>" class="btn btn-dark btn-sm" aria-label="<?php echo esc_attr__('Hubungi Toko', 'velocity-marketplace'); ?>" title="<?php echo esc_attr__('Hubungi Toko', 'velocity-marketplace'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M2 2a2 2 0 0 0-2 2v8.5A1.5 1.5 0 0 0 1.5 14H2v1.5a.5.5 0 0 0 .854.354L4.707 14H14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zm3 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0m4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0m4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/></svg>
                            </a>
                            <a href="<?php echo esc_url($store_profile_url); ?>" class="btn btn-outline-dark btn-sm" aria-label="<?php echo esc_attr__('Kunjungi Toko', 'velocity-marketplace'); ?>" title="<?php echo esc_attr__('Kunjungi Toko', 'velocity-marketplace'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M2.97 1.35A1 1 0 0 1 3.73 1h8.54a1 1 0 0 1 .76.35l2.609 3.044A1.5 1.5 0 0 1 16 5.37v.255a2.375 2.375 0 0 1-4.25 1.458A2.37 2.37 0 0 1 9.875 8 2.37 2.37 0 0 1 8 7.083 2.37 2.37 0 0 1 6.125 8a2.37 2.37 0 0 1-1.875-.917A2.375 2.375 0 0 1 0 5.625V5.37a1.5 1.5 0 0 1 .361-.976zM1.5 8.27V14h5v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h4V8.27a3.4 3.4 0 0 1-2.75.026A3.37 3.37 0 0 1 9.875 9 3.37 3.37 0 0 1 8 8.296 3.37 3.37 0 0 1 6.125 9a3.37 3.37 0 0 1-1.875-.704A3.4 3.4 0 0 1 1.5 8.27"/></svg>
                            </a>
                            <?php if ($seller_phone !== '') : ?>
                                <a href="tel:<?php echo esc_attr($seller_phone); ?>" class="btn btn-outline-dark btn-sm" aria-label="<?php echo esc_attr__('Telepon', 'velocity-marketplace'); ?>" title="<?php echo esc_attr__('Telepon', 'velocity-marketplace'); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.6 17.6 0 0 0 4.168 6.608 17.6 17.6 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.75 1.75 0 0 1-1.657-.459L5.482 8.062a1.75 1.75 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58z"/></svg></a>
                            <?php endif; ?>
                            <?php if ($seller_whatsapp !== '') : ?>
                                <a href="<?php echo esc_url('https://wa.me/' . $seller_whatsapp); ?>" class="btn btn-outline-success btn-sm" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr__('WhatsApp', 'velocity-marketplace'); ?>" title="<?php echo esc_attr__('WhatsApp', 'velocity-marketplace'); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93a7.9 7.9 0 0 0-2.327-5.607M7.998 14.527a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.25a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.591-6.592 6.591m3.615-4.934c-.197-.1-1.17-.578-1.353-.646-.182-.066-.315-.1-.445.1-.133.197-.513.646-.63.775-.116.133-.232.15-.43.05-1.17-.584-1.938-1.044-2.71-2.373-.205-.353.205-.329.584-1.094.066-.133.033-.249-.017-.349-.05-.1-.445-1.074-.61-1.47-.16-.389-.323-.336-.445-.342-.116-.008-.249-.008-.381-.008a.74.74 0 0 0-.53.249c-.182.197-.696.68-.696 1.657s.713 1.922.812 2.054c.1.133 1.404 2.142 3.4 3.003.476.205.847.328 1.137.42.478.152.913.13 1.257.079.384-.058 1.17-.48 1.336-.943.165-.464.165-.861.116-.943-.05-.083-.182-.133-.38-.232"/></svg></a>
                            <?php endif; ?>
                            <?php if ($seller_email !== '') : ?>
                                <a href="mailto:<?php echo esc_attr($seller_email); ?>" class="btn btn-outline-dark btn-sm" aria-label="<?php echo esc_attr__('Email', 'velocity-marketplace'); ?>" title="<?php echo esc_attr__('Email', 'velocity-marketplace'); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583 6.674 8.788l-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z"/></svg></a>
                            <?php endif; ?>
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
