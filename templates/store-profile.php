<?php
use VelocityMarketplace\Modules\Product\ProductData;
use VelocityMarketplace\Modules\Account\Account;
use VelocityMarketplace\Modules\Review\RatingRenderer;
use VelocityMarketplace\Modules\Review\ReviewRepository;
use VelocityMarketplace\Modules\Review\StarSellerService;
use VelocityMarketplace\Support\Settings;

$seller_id = isset($seller_id) ? (int) $seller_id : 0;
$seller = $seller_id > 0 ? get_userdata($seller_id) : false;

if (!$seller || !Account::can_sell($seller_id)) {
    return '<div class="container py-4 vmp-wrap"><div class="alert alert-warning mb-0">' . esc_html__('Toko tidak ditemukan.', 'velocity-marketplace') . '</div></div>';
}

$store_name = (string) get_user_meta($seller_id, 'vmp_store_name', true);
$store_phone = (string) get_user_meta($seller_id, 'vmp_store_phone', true);
$store_whatsapp = (string) get_user_meta($seller_id, 'vmp_store_whatsapp', true);
$store_address = (string) get_user_meta($seller_id, 'vmp_store_address', true);
$store_description = (string) get_user_meta($seller_id, 'vmp_store_description', true);
$store_bank_details = (string) get_user_meta($seller_id, 'vmp_store_bank_details', true);
$store_city = (string) get_user_meta($seller_id, 'vmp_store_city', true);
$store_province = (string) get_user_meta($seller_id, 'vmp_store_province', true);
$store_subdistrict = (string) get_user_meta($seller_id, 'vmp_store_subdistrict', true);
$store_cod_enabled = !empty(get_user_meta($seller_id, 'vmp_cod_enabled', true));
$store_cod_city_names = get_user_meta($seller_id, 'vmp_cod_city_names', true);
if (!is_array($store_cod_city_names)) {
    $store_cod_city_names = [];
}
$store_avatar_id = (int) get_user_meta($seller_id, 'vmp_store_avatar_id', true);
$store_avatar_url = $store_avatar_id > 0 ? wp_get_attachment_image_url($store_avatar_id, 'medium') : '';
if ($store_avatar_url === '') {
    $store_avatar_url = ProductData::no_image_url();
}
$seller_summary = (new StarSellerService())->summary($seller_id);
$review_repo = new ReviewRepository();
$store_reviews = $review_repo->seller_reviews($seller_id, 6);
$seller_rating_average = isset($seller_summary['rating_average']) ? (float) $seller_summary['rating_average'] : 0.0;
$store_last_active = (string) get_user_meta($seller_id, 'vmp_last_active_at', true);
$store_last_active_text = '-';
if ($store_last_active !== '') {
    $timestamp = strtotime($store_last_active);
    if ($timestamp) {
        $store_last_active_text = sprintf(__('%s yang lalu', 'velocity-marketplace'), human_time_diff($timestamp, current_time('timestamp')));
    }
}

$display_name = $store_name !== '' ? $store_name : ($seller->display_name !== '' ? $seller->display_name : $seller->user_login);
$store_location = trim(implode(', ', array_filter([$store_subdistrict, $store_city, $store_province])));
$store_location = $store_location !== '' ? $store_location : '-';
$joined_date = !empty($seller->user_registered) ? mysql2date('d M Y', $seller->user_registered) : '-';
$completed_orders = (int) ($seller_summary['completed_orders'] ?? 0);
$message_url = is_user_logged_in()
    ? add_query_arg(['tab' => 'messages', 'message_to' => $seller_id], Settings::profile_url())
    : wp_login_url(add_query_arg(['tab' => 'messages', 'message_to' => $seller_id], Settings::profile_url()));

$product_count_query = new \WP_Query([
    'post_type' => 'store_product',
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'author' => $seller_id,
    'fields' => 'ids',
]);
$total_products = (int) $product_count_query->found_posts;

$product_query = new \WP_Query([
    'post_type' => 'store_product',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'author' => $seller_id,
    'orderby' => 'date',
    'order' => 'DESC',
]);

$products = [];
if ($product_query->have_posts()) {
    while ($product_query->have_posts()) {
        $product_query->the_post();
        $item = ProductData::map_post(get_the_ID());
        if ($item) {
            $products[] = $item;
        }
    }
    wp_reset_postdata();
}
?>
<div class="container vmp-wrap">
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="row g-4 g-lg-5 align-items-start">
                <div class="col-6 col-sm-4 col-lg-3 col-xl-2 mx-auto mx-sm-0">
                    <div class="ratio ratio-1x1 rounded-3 overflow-hidden bg-light border">
                        <img src="<?php echo esc_url($store_avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>" class="w-100 h-100 object-fit-cover">
                    </div>
                </div>
                <div class="col-12 col-sm-8 col-lg-9 col-xl-10">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 border-bottom pb-4 mb-4">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <h1 class="h4 fw-bold my-0"><?php echo esc_html($display_name); ?></h1>
                                <?php if (!empty($seller_summary['is_star_seller'])) : ?>
                                    <span class="badge rounded-pill bg-warning text-dark"><?php echo esc_html__('Star Seller', 'velocity-marketplace'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted small"><?php echo esc_html($store_location); ?></div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?php echo esc_url($message_url); ?>" class="btn btn-dark"><?php echo esc_html__('Hubungi Toko', 'velocity-marketplace'); ?></a>
                            <a href="#produk-toko" class="btn btn-outline-dark"><?php echo esc_html__('Lihat Produk', 'velocity-marketplace'); ?></a>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6 col-xl-3">
                            <div class="h-100 rounded-3 bg-light p-3">
                                <div class="small text-muted mb-1"><?php echo esc_html__('Total Produk', 'velocity-marketplace'); ?></div>
                                <div class="h5 fw-bold mb-0"><?php echo esc_html((string) $total_products); ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="h-100 rounded-3 bg-light p-3">
                                <div class="small text-muted mb-1"><?php echo esc_html__('Pesanan Selesai', 'velocity-marketplace'); ?></div>
                                <div class="h5 fw-bold mb-0"><?php echo esc_html((string) $completed_orders); ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="h-100 rounded-3 bg-light p-3">
                                <div class="small text-muted mb-1"><?php echo esc_html__('Rating Toko', 'velocity-marketplace'); ?></div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="h5 fw-bold mb-0"><?php echo esc_html(number_format($seller_rating_average, 1, ',', '')); ?>/5</span>
                                    <?php echo RatingRenderer::summary_html($seller_rating_average, null, ['size' => 14, 'show_count' => false, 'show_value' => false]); ?>
                                </div>
                                <div class="small text-muted mt-1"><?php echo esc_html(sprintf(__('%d ulasan', 'velocity-marketplace'), (int) ($seller_summary['rating_count'] ?? 0))); ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="h-100 rounded-3 bg-light p-3">
                                <div class="small text-muted mb-1"><?php echo esc_html__('Bergabung', 'velocity-marketplace'); ?></div>
                                <div class="fw-semibold"><?php echo esc_html($joined_date); ?></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($store_description !== '') : ?>
                        <div class="vmp-content border-start border-3 ps-3 text-muted mb-4"><?php echo wp_kses_post(wpautop($store_description)); ?></div>
                    <?php endif; ?>

                    <div class="row g-3 small">
                        <div class="col-sm-6 col-lg-3">
                            <div class="text-muted mb-1"><?php echo esc_html__('Terakhir Aktif', 'velocity-marketplace'); ?></div>
                            <div class="fw-semibold"><?php echo esc_html($store_last_active_text); ?></div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="text-muted mb-1"><?php echo esc_html__('Telepon', 'velocity-marketplace'); ?></div>
                            <div class="fw-semibold"><?php echo esc_html($store_phone !== '' ? $store_phone : '-'); ?></div>
                        </div>
                        <?php if ($store_whatsapp !== '') : ?>
                            <div class="col-sm-6 col-lg-3">
                                <div class="text-muted mb-1"><?php echo esc_html__('WhatsApp', 'velocity-marketplace'); ?></div>
                                <div class="fw-semibold"><?php echo esc_html($store_whatsapp); ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="col-sm-6 col-lg-3">
                            <div class="text-muted mb-1"><?php echo esc_html__('Pembayaran COD', 'velocity-marketplace'); ?></div>
                            <div class="fw-semibold"><?php echo esc_html($store_cod_enabled ? __('Tersedia', 'velocity-marketplace') : __('Tidak Aktif', 'velocity-marketplace')); ?></div>
                        </div>
                        <?php if ($store_address !== '') : ?>
                            <div class="col-12 pt-2">
                                <div class="text-muted mb-1"><?php echo esc_html__('Alamat Pengiriman', 'velocity-marketplace'); ?></div>
                                <div class="fw-semibold"><?php echo esc_html($store_address); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($store_cod_enabled && !empty($store_cod_city_names)) : ?>
                            <div class="col-12">
                                <div class="text-muted mb-1"><?php echo esc_html__('Area COD', 'velocity-marketplace'); ?></div>
                                <div class="fw-semibold"><?php echo esc_html(implode(', ', array_filter(array_map('strval', $store_cod_city_names)))); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (current_user_can('manage_options') && $store_bank_details !== '') : ?>
                        <div class="alert alert-light border rounded-3 small mt-4 mb-0">
                            <div class="fw-semibold mb-1"><?php echo esc_html__('Rekening Pencairan Seller', 'velocity-marketplace'); ?></div>
                            <div class="text-muted mb-2"><?php echo esc_html__('Blok ini hanya terlihat oleh admin.', 'velocity-marketplace'); ?></div>
                            <div class="font-monospace"><?php echo nl2br(esc_html($store_bank_details)); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 mb-5">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h2 class="h4 fw-bold mb-1 mt-0"><?php echo esc_html__('Ulasan Toko', 'velocity-marketplace'); ?></h2>
                    <small class="text-muted"><?php echo esc_html__('Ulasan pembeli terbaru untuk produk yang dijual toko ini.', 'velocity-marketplace'); ?></small>
                </div>
                <div class="small bg-light rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2 align-self-start align-self-md-auto">
                    <?php echo RatingRenderer::summary_html($seller_rating_average, null, ['size' => 14, 'show_count' => false, 'show_value' => false]); ?>
                    <span><?php echo esc_html(number_format($seller_rating_average, 1, ',', '.') . '/5 dari ' . (int) ($seller_summary['rating_count'] ?? 0) . ' ulasan'); ?></span>
                </div>
            </div>
            <?php if (empty($store_reviews)) : ?>
                <div class="bg-light rounded-3 p-4 text-center text-muted small"><?php echo esc_html__('Belum ada ulasan untuk toko ini.', 'velocity-marketplace'); ?></div>
            <?php else : ?>
                <div class="vmp-review-list">
                    <?php foreach ($store_reviews as $review) : ?>
                        <?php $review_rating = max(0, min(5, (int) ($review['rating'] ?? 0))); ?>
                        <div class="vmp-review-item">
                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                <div>
                                    <div class="fw-semibold"><?php echo esc_html((string) ($review['user_name'] ?? __('Member', 'velocity-marketplace'))); ?></div>
                                    <?php echo RatingRenderer::summary_html($review_rating, null, ['size' => 16, 'show_count' => false, 'show_value' => false, 'class' => 'small']); ?>
                                </div>
                                <div class="small text-muted"><?php echo esc_html(mysql2date('d-m-Y H:i', (string) ($review['created_at'] ?? ''))); ?></div>
                            </div>
                            <?php if (!empty($review['title'])) : ?>
                                <div class="fw-semibold mt-2"><?php echo esc_html((string) $review['title']); ?></div>
                            <?php endif; ?>
                            <div class="small mt-1"><?php echo esc_html((string) ($review['content'] ?? '')); ?></div>
                            <?php if (!empty($review['product_title'])) : ?>
                                <div class="small text-muted mt-2">
                                    <?php echo esc_html__('Produk:', 'velocity-marketplace'); ?>
                                    <?php if (!empty($review['product_link'])) : ?>
                                        <a href="<?php echo esc_url((string) $review['product_link']); ?>"><?php echo esc_html((string) $review['product_title']); ?></a>
                                    <?php else : ?>
                                        <?php echo esc_html((string) $review['product_title']); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="produk-toko" class="d-flex justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h2 class="h4 fw-bold mb-1"><?php echo esc_html__('Produk Toko', 'velocity-marketplace'); ?></h2>
            <small class="text-muted"><?php echo esc_html__('Produk terbaru dari toko ini.', 'velocity-marketplace'); ?></small>
        </div>
    </div>

    <?php if (empty($products)) : ?>
        <div class="alert alert-info mb-0"><?php echo esc_html__('Toko ini belum memiliki produk aktif.', 'velocity-marketplace'); ?></div>
    <?php else : ?>
        <div class="row g-3">
            <?php foreach ($products as $item) : ?>
                <?php
                $product_rating_average = isset($item['rating_average']) ? (float) $item['rating_average'] : 0.0;
                $product_rating_average_rounded = max(0, min(5, (int) round($product_rating_average)));
                ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden">
                        <a href="<?php echo esc_url((string) ($item['link'] ?? '#')); ?>" class="ratio ratio-1x1 text-decoration-none bg-light">
                            <img src="<?php echo esc_url((string) ($item['image'] ?? ProductData::no_image_url())); ?>" alt="<?php echo esc_attr((string) ($item['title'] ?? __('Produk', 'velocity-marketplace'))); ?>" class="w-100 h-100 object-fit-cover">
                        </a>
                        <div class="card-body d-flex flex-column p-3">
                            <a href="<?php echo esc_url((string) ($item['link'] ?? '#')); ?>" class="fw-semibold text-decoration-none text-dark mb-3"><?php echo esc_html((string) ($item['title'] ?? __('Produk', 'velocity-marketplace'))); ?></a>
                            <?php echo wps_product_price_html((int) ($item['id'] ?? 0), ['wrapper_class' => 'mb-2', 'sale_group_class' => 'd-flex align-items-baseline gap-2 flex-wrap', 'sale_class' => 'text-danger fw-semibold', 'regular_class' => 'text-muted small', 'price_class' => 'text-danger fw-semibold', 'show_empty' => false]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <?php if (!empty($item['seller_city'])) : ?>
                                <div class="small text-muted mb-2"><?php echo esc_html((string) $item['seller_city']); ?></div>
                            <?php endif; ?>
                            <div class="small text-muted mb-3 d-flex flex-wrap align-items-center gap-1">
                                <?php echo RatingRenderer::summary_html($product_rating_average, null, ['size' => 14, 'show_count' => false, 'show_value' => false]); ?>
                                <span><?php echo esc_html(number_format($product_rating_average, 1, ',', '.') . '/5 dari ' . (int) ($item['review_count'] ?? 0) . ' ulasan'); ?></span>
                            </div>
                            <div class="mt-auto d-flex flex-wrap gap-2">
                                <a href="<?php echo esc_url((string) ($item['link'] ?? '#')); ?>" class="btn btn-sm btn-outline-dark"><?php echo esc_html__('Detail', 'velocity-marketplace'); ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
