<?php
use VelocityMarketplace\Modules\Product\ProductData;
use VelocityMarketplace\Modules\Review\ReviewRepository;
use VelocityMarketplace\Modules\Review\StarSellerService;
use VelocityMarketplace\Support\Settings;

$seller_id = isset($seller_id) ? (int) $seller_id : 0;
$seller = $seller_id > 0 ? get_userdata($seller_id) : false;

if (!$seller) {
    return '<div class="container py-4 vmp-wrap"><div class="alert alert-warning mb-0">Toko tidak ditemukan.</div></div>';
}

$store_name = (string) get_user_meta($seller_id, 'vmp_store_name', true);
$store_phone = (string) get_user_meta($seller_id, 'vmp_store_phone', true);
$store_whatsapp = (string) get_user_meta($seller_id, 'vmp_store_whatsapp', true);
$store_address = (string) get_user_meta($seller_id, 'vmp_store_address', true);
$store_description = (string) get_user_meta($seller_id, 'vmp_store_description', true);
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
$store_last_active = (string) get_user_meta($seller_id, 'vmp_last_active_at', true);
$store_last_active_text = '-';
if ($store_last_active !== '') {
    $timestamp = strtotime($store_last_active);
    if ($timestamp) {
        $store_last_active_text = human_time_diff($timestamp, current_time('timestamp')) . ' yang lalu';
    }
}

$display_name = $store_name !== '' ? $store_name : ($seller->display_name !== '' ? $seller->display_name : $seller->user_login);
$message_url = is_user_logged_in()
    ? add_query_arg(['tab' => 'messages', 'message_to' => $seller_id], Settings::profile_url())
    : wp_login_url(add_query_arg(['tab' => 'messages', 'message_to' => $seller_id], Settings::profile_url()));

$product_count_query = new \WP_Query([
    'post_type' => 'vmp_product',
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'author' => $seller_id,
    'fields' => 'ids',
]);
$total_products = (int) $product_count_query->found_posts;

$product_query = new \WP_Query([
    'post_type' => 'vmp_product',
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
<div class="container py-4 vmp-wrap">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-4 align-items-start">
                <div class="col-md-3 col-lg-2">
                    <img src="<?php echo esc_url($store_avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>" class="img-fluid rounded border">
                </div>
                <div class="col-md-9 col-lg-10">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h1 class="h4 mb-0"><?php echo esc_html($display_name); ?></h1>
                        <?php if (!empty($seller_summary['is_star_seller'])) : ?>
                            <span class="badge bg-warning text-dark">Star Seller</span>
                        <?php endif; ?>
                    </div>
                    <div class="row g-2 small mb-3">
                        <div class="col-md-6"><strong>Total Produk:</strong> <?php echo esc_html((string) $total_products); ?></div>
                        <div class="col-md-6"><strong>Bergabung:</strong> <?php echo esc_html(!empty($seller->user_registered) ? mysql2date('d-m-Y', $seller->user_registered) : '-'); ?></div>
                        <div class="col-md-6"><strong>Rating Toko:</strong> <?php echo esc_html(number_format((float) ($seller_summary['rating_average'] ?? 0), 1, ',', '.') . '/5 dari ' . (int) ($seller_summary['rating_count'] ?? 0) . ' ulasan'); ?></div>
                        <div class="col-md-6"><strong>Order Selesai:</strong> <?php echo esc_html((string) (int) ($seller_summary['completed_orders'] ?? 0)); ?></div>
                        <div class="col-md-6"><strong>Terakhir Aktif:</strong> <?php echo esc_html($store_last_active_text); ?></div>
                        <div class="col-md-6"><strong>Lokasi:</strong> <?php echo esc_html(trim(implode(', ', array_filter([$store_subdistrict, $store_city, $store_province])))); ?></div>
                        <div class="col-md-6"><strong>Telepon:</strong> <?php echo esc_html($store_phone !== '' ? $store_phone : '-'); ?></div>
                        <?php if ($store_whatsapp !== '') : ?>
                            <div class="col-md-6"><strong>WhatsApp:</strong> <?php echo esc_html($store_whatsapp); ?></div>
                        <?php endif; ?>
                        <div class="col-md-6"><strong>COD:</strong> <?php echo esc_html($store_cod_enabled ? 'Tersedia' : 'Tidak aktif'); ?></div>
                    </div>
                    <?php if ($store_description !== '') : ?>
                        <div class="mb-3 text-muted"><?php echo wp_kses_post(wpautop($store_description)); ?></div>
                    <?php endif; ?>
                    <?php if ($store_address !== '') : ?>
                        <div class="small text-muted mb-3"><strong>Alamat Pengiriman:</strong> <?php echo esc_html($store_address); ?></div>
                    <?php endif; ?>
                    <?php if ($store_cod_enabled && !empty($store_cod_city_names)) : ?>
                        <div class="small text-muted mb-3"><strong>Kota COD:</strong> <?php echo esc_html(implode(', ', array_filter(array_map('strval', $store_cod_city_names)))); ?></div>
                    <?php endif; ?>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?php echo esc_url($message_url); ?>" class="btn btn-dark">Hubungi Toko</a>
                        <a href="<?php echo esc_url(Settings::store_profile_url($seller_id)); ?>" class="btn btn-outline-dark">Muat Ulang Profil</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h2 class="h5 mb-0">Ulasan Toko</h2>
                    <small class="text-muted">Ulasan terbaru dari pembeli atas produk yang dijual toko ini.</small>
                </div>
                <div class="small text-muted">
                    <?php echo esc_html(number_format((float) ($seller_summary['rating_average'] ?? 0), 1, ',', '.') . '/5 dari ' . (int) ($seller_summary['rating_count'] ?? 0) . ' ulasan'); ?>
                </div>
            </div>
            <?php if (empty($store_reviews)) : ?>
                <div class="text-muted small">Belum ada ulasan untuk toko ini.</div>
            <?php else : ?>
                <div class="vmp-review-list">
                    <?php foreach ($store_reviews as $review) : ?>
                        <?php $review_stars = str_repeat('&#9733;', max(0, min(5, (int) ($review['rating'] ?? 0)))) . str_repeat('&#9734;', max(0, 5 - (int) ($review['rating'] ?? 0))); ?>
                        <div class="vmp-review-item">
                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                <div>
                                    <div class="fw-semibold"><?php echo esc_html((string) ($review['user_name'] ?? 'Member')); ?></div>
                                    <div class="small text-muted"><?php echo wp_kses_post($review_stars); ?></div>
                                </div>
                                <div class="small text-muted"><?php echo esc_html(mysql2date('d-m-Y H:i', (string) ($review['created_at'] ?? ''))); ?></div>
                            </div>
                            <?php if (!empty($review['title'])) : ?>
                                <div class="fw-semibold mt-2"><?php echo esc_html((string) $review['title']); ?></div>
                            <?php endif; ?>
                            <div class="small mt-1"><?php echo esc_html((string) ($review['content'] ?? '')); ?></div>
                            <?php if (!empty($review['product_title'])) : ?>
                                <div class="small text-muted mt-2">
                                    Produk:
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

    <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h2 class="h5 mb-0">Produk Toko</h2>
            <small class="text-muted">Daftar produk terbaru dari toko ini</small>
        </div>
    </div>

    <?php if (empty($products)) : ?>
        <div class="alert alert-info mb-0">Belum ada produk aktif yang ditampilkan oleh toko ini.</div>
    <?php else : ?>
        <div class="row g-3">
            <?php foreach ($products as $item) : ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm overflow-hidden">
                        <a href="<?php echo esc_url((string) ($item['link'] ?? '#')); ?>" class="text-decoration-none text-dark">
                            <img src="<?php echo esc_url((string) ($item['image'] ?? ProductData::no_image_url())); ?>" alt="<?php echo esc_attr((string) ($item['title'] ?? 'Produk')); ?>" class="card-img-top" style="aspect-ratio:1/1; object-fit:cover;">
                        </a>
                        <div class="card-body d-flex flex-column">
                            <a href="<?php echo esc_url((string) ($item['link'] ?? '#')); ?>" class="fw-semibold text-decoration-none text-dark mb-2"><?php echo esc_html((string) ($item['title'] ?? 'Produk')); ?></a>
                            <div class="text-danger fw-semibold mb-3"><?php echo esc_html(Settings::currency_symbol() . ' ' . number_format((float) ($item['price'] ?? 0), 0, ',', '.')); ?></div>
                            <div class="small text-muted mb-3"><?php echo esc_html(number_format((float) ($item['rating_average'] ?? 0), 1, ',', '.') . '/5 dari ' . (int) ($item['review_count'] ?? 0) . ' ulasan'); ?></div>
                            <div class="mt-auto d-flex flex-wrap gap-2">
                                <?php echo do_shortcode('[vmp_add_to_cart id="' . (int) ($item['id'] ?? 0) . '" text="Tambah Keranjang" class="btn btn-sm btn-dark"]'); ?>
                                <a href="<?php echo esc_url((string) ($item['link'] ?? '#')); ?>" class="btn btn-sm btn-outline-dark">Detail</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
