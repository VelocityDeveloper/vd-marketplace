<?php
use VelocityMarketplace\Modules\Product\ProductData;
use VelocityMarketplace\Modules\Review\ReviewRepository;
use VelocityMarketplace\Modules\Review\StarSellerService;
use VelocityMarketplace\Support\Settings;

$product_id = get_the_ID();
$item = ProductData::map_post($product_id);
if (!$item) {
    status_header(404);
    nocache_headers();
    get_header();
    echo '<div class="container py-4 vmp-wrap"><div class="alert alert-warning mb-0">' . esc_html__('Produk tidak ditemukan.', 'velocity-marketplace') . '</div></div>';
    get_footer();
    return;
}

$categories = get_the_terms($product_id, 'vmp_product_cat');
$content = get_post_field('post_content', $product_id);
$main_image = $item['image'];
$gallery = is_array($item['gallery']) ? $item['gallery'] : [];
if ($main_image !== '' && !in_array($main_image, $gallery, true)) {
    array_unshift($gallery, $main_image);
}
if (empty($gallery) && $main_image !== '') {
    $gallery[] = $main_image;
}
$seller_id = (int) get_post_field('post_author', $product_id);
$seller_user = $seller_id > 0 ? get_userdata($seller_id) : false;
$seller_store_name = $seller_id > 0 ? (string) get_user_meta($seller_id, 'vmp_store_name', true) : '';
$seller_name = $seller_store_name !== '' ? $seller_store_name : ($seller_user && $seller_user->display_name !== '' ? $seller_user->display_name : ($seller_user ? $seller_user->user_login : __('Penjual', 'velocity-marketplace')));
$review_repo = new ReviewRepository();
$product_reviews = $review_repo->product_reviews($product_id, 20);
$review_summary = $review_repo->product_summary($product_id);
$seller_summary = (new StarSellerService())->summary($seller_id);
$store_profile_url = Settings::store_profile_url($seller_id);
$message_url = is_user_logged_in()
    ? add_query_arg(['tab' => 'messages', 'message_to' => $seller_id], Settings::profile_url())
    : wp_login_url(add_query_arg(['tab' => 'messages', 'message_to' => $seller_id], Settings::profile_url()));
$rating_average = isset($review_summary['rating_average']) ? (float) $review_summary['rating_average'] : 0.0;
$review_count = isset($review_summary['review_count']) ? (int) $review_summary['review_count'] : 0;
$rating_stars = str_repeat('&#9733;', max(0, min(5, (int) round($rating_average)))) . str_repeat('&#9734;', max(0, 5 - (int) round($rating_average)));

get_header();
?>
<div class="container py-4 vmp-wrap">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="vmp-product-gallery" data-gallery-title="<?php echo esc_attr($item['title']); ?>">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <?php if (!empty($gallery)) : ?>
                        <button type="button" class="vmp-gallery-stage" data-gallery-open aria-label="<?php echo esc_attr__('Lihat gambar penuh', 'velocity-marketplace'); ?>">
                            <img
                                src="<?php echo esc_url($gallery[0]); ?>"
                                alt="<?php echo esc_attr($item['title']); ?>"
                                class="vmp-single-image"
                                data-gallery-main
                            >
                        </button>
                    <?php else : ?>
                        <div class="vmp-single-image vmp-single-image--empty d-flex align-items-center justify-content-center text-muted"><?php echo esc_html__('Tidak ada gambar', 'velocity-marketplace'); ?></div>
                    <?php endif; ?>
                </div>

                <?php if (count($gallery) > 1) : ?>
                    <div class="vmp-gallery-thumbs-wrap mt-3">
                        <button type="button" class="vmp-gallery-arrow vmp-gallery-arrow--prev" data-gallery-prev aria-label="<?php echo esc_attr__('Thumbnail sebelumnya', 'velocity-marketplace'); ?>">
                            <span aria-hidden="true">&#8249;</span>
                        </button>
                        <div class="vmp-gallery-thumbs" data-gallery-track>
                            <?php foreach ($gallery as $index => $image_url) : ?>
                                <button
                                    type="button"
                                    class="vmp-gallery-thumb<?php echo $index === 0 ? ' is-active' : ''; ?>"
                                    data-gallery-thumb
                                    data-index="<?php echo esc_attr((string) $index); ?>"
                                    data-image="<?php echo esc_url($image_url); ?>"
                                    aria-label="<?php echo esc_attr(sprintf(__('%1$s image %2$d', 'velocity-marketplace'), $item['title'], ($index + 1))); ?>"
                                >
                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($item['title']); ?>" class="vmp-single-thumb">
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="vmp-gallery-arrow vmp-gallery-arrow--next" data-gallery-next aria-label="<?php echo esc_attr__('Thumbnail berikutnya', 'velocity-marketplace'); ?>">
                            <span aria-hidden="true">&#8250;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="d-none" data-gallery-links>
                    <?php foreach ($gallery as $index => $image_url) : ?>
                        <a href="<?php echo esc_url($image_url); ?>" data-gallery-link data-index="<?php echo esc_attr((string) $index); ?>">
                            <?php echo esc_html($item['title'] . ' ' . ($index + 1)); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="mb-2">
                <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
                    <?php foreach ($categories as $term) : ?>
                        <span class="badge bg-light text-dark border"><?php echo esc_html($term->name); ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($item['is_premium'])) : ?>
                    <span class="badge bg-warning text-dark"><?php echo esc_html__('Premium', 'velocity-marketplace'); ?></span>
                <?php endif; ?>
            </div>
            <h1 class="h3 mb-2"><?php echo esc_html($item['title']); ?></h1>
            <?php if (!empty($item['label'])) : ?>
                <div class="text-muted mb-2"><?php echo esc_html($item['label']); ?></div>
            <?php endif; ?>
            <div class="d-flex flex-wrap align-items-center gap-2 small text-muted mb-2">
                <span class="vmp-rating-stars"><?php echo wp_kses_post($rating_stars); ?></span>
                <span><?php echo esc_html(number_format($rating_average, 1, ',', '.')); ?>/5</span>
                <span><?php echo esc_html(sprintf(__('(%d ulasan)', 'velocity-marketplace'), $review_count)); ?></span>
            </div>
            <div class="mb-3"><?php echo do_shortcode('[vmp_price id="' . (int) $item['id'] . '" class="h5"]'); ?></div>

            <div class="row g-2 small mb-3">
                <div class="col-sm-6"><strong><?php echo esc_html__('SKU:', 'velocity-marketplace'); ?></strong> <?php echo esc_html($item['sku'] !== '' ? $item['sku'] : '-'); ?></div>
                <div class="col-sm-6"><strong><?php echo esc_html__('Minimal Pesanan:', 'velocity-marketplace'); ?></strong> <?php echo esc_html((string) (int) ($item['min_order'] ?? 1)); ?></div>
                <div class="col-sm-6"><strong><?php echo esc_html__('Berat:', 'velocity-marketplace'); ?></strong> <?php echo esc_html(number_format((float) ($item['weight'] ?? 0), 0, ',', '.')); ?> gr</div>
                <div class="col-sm-6"><strong><?php echo esc_html__('Stok:', 'velocity-marketplace'); ?></strong>
                    <?php
                    if ($item['stock'] === null || $item['stock'] === '') {
                        echo esc_html__('Tidak terbatas', 'velocity-marketplace');
                    } else {
                        echo esc_html((float) $item['stock'] > 0 ? (string) (int) $item['stock'] : __('Stok habis', 'velocity-marketplace'));
                    }
                    ?>
                </div>
            </div>

            <?php if (!empty($item['variant_options'])) : ?>
                <div class="mb-3">
                    <div class="fw-semibold mb-1"><?php echo esc_html($item['variant_name']); ?></div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($item['variant_options'] as $opt) : ?>
                            <span class="badge bg-light text-dark border"><?php echo esc_html((string) $opt); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($item['price_adjustment_options'])) : ?>
                <div class="mb-3">
                    <div class="fw-semibold mb-1"><?php echo esc_html($item['price_adjustment_name']); ?></div>
                    <div class="d-flex flex-column gap-1 small">
                        <?php foreach ($item['price_adjustment_options'] as $opt) : ?>
                            <div>
                                <?php echo esc_html((string) ($opt['label'] ?? '')); ?>
                                <span class="text-muted">
                                    (<?php echo esc_html('+' . number_format((float) ($opt['amount'] ?? 0), 0, ',', '.')); ?>)
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2 mb-4">
                <?php echo do_shortcode('[vmp_add_to_cart id="' . (int) $item['id'] . '" text="' . esc_attr__('Tambah Keranjang', 'velocity-marketplace') . '" class="btn btn-dark"]'); ?>
                <?php echo do_shortcode('[vmp_add_to_wishlist id="' . (int) $item['id'] . '" text="' . esc_attr__('Wishlist', 'velocity-marketplace') . '" class="btn btn-outline-secondary vmp-wishlist-button"]'); ?>
                <a href="<?php echo esc_url(site_url('/cart/')); ?>" class="btn btn-outline-dark"><?php echo esc_html__('Lihat Keranjang', 'velocity-marketplace'); ?></a>
            </div>

            <?php if ($seller_id > 0) : ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                    <div class="small text-muted mb-1"><?php echo esc_html__('Toko', 'velocity-marketplace'); ?></div>
                        <div class="fw-semibold mb-1"><?php echo esc_html($seller_name); ?></div>
                        <div class="small text-muted mb-3">
                            <?php if (!empty($seller_summary['is_star_seller'])) : ?>
                                <span class="badge bg-warning text-dark me-1"><?php echo esc_html__('Star Seller', 'velocity-marketplace'); ?></span>
                            <?php endif; ?>
                            <?php echo esc_html(number_format((float) ($seller_summary['rating_average'] ?? 0), 1, ',', '.')); ?>/5
                            <?php echo esc_html(sprintf(__(' from %d reviews', 'velocity-marketplace'), (int) ($seller_summary['rating_count'] ?? 0))); ?>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?php echo esc_url($store_profile_url); ?>" class="btn btn-outline-dark btn-sm"><?php echo esc_html__('Kunjungi Toko', 'velocity-marketplace'); ?></a>
                            <a href="<?php echo esc_url($message_url); ?>" class="btn btn-dark btn-sm"><?php echo esc_html__('Hubungi Toko', 'velocity-marketplace'); ?></a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <h2 class="h5 mb-3"><?php echo esc_html__('Deskripsi Produk', 'velocity-marketplace'); ?></h2>
            <div class="vmp-content">
                <?php echo wp_kses_post(apply_filters('the_content', $content)); ?>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1"><?php echo esc_html__('Ulasan Produk', 'velocity-marketplace'); ?></h2>
                    <div class="small text-muted"><?php echo esc_html__('Ulasan hanya dapat dikirim dari pesanan yang sudah selesai.', 'velocity-marketplace'); ?></div>
                </div>
                <div class="text-end">
                    <div class="vmp-rating-stars"><?php echo wp_kses_post($rating_stars); ?></div>
                    <div class="small text-muted"><?php echo esc_html(number_format($rating_average, 1, ',', '.') . '/5 ' . sprintf(__('dari %d ulasan', 'velocity-marketplace'), $review_count)); ?></div>
                </div>
            </div>
            <?php if (empty($product_reviews)) : ?>
                <div class="text-muted small"><?php echo esc_html__('There are no reviews for this product yet.', 'velocity-marketplace'); ?></div>
            <?php else : ?>
                <div class="vmp-review-list">
                    <?php foreach ($product_reviews as $review) : ?>
                        <?php
                        $item_stars = str_repeat('&#9733;', max(0, min(5, (int) ($review['rating'] ?? 0)))) . str_repeat('&#9734;', max(0, 5 - (int) ($review['rating'] ?? 0)));
                        ?>
                        <div class="vmp-review-item">
                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                <div>
                                    <div class="fw-semibold"><?php echo esc_html((string) ($review['user_name'] ?? __('Member', 'velocity-marketplace'))); ?></div>
                                    <div class="vmp-rating-stars small"><?php echo wp_kses_post($item_stars); ?></div>
                                </div>
                                <div class="small text-muted"><?php echo esc_html(mysql2date('d-m-Y H:i', (string) ($review['created_at'] ?? ''))); ?></div>
                            </div>
                            <?php if (!empty($review['title'])) : ?>
                                <div class="fw-semibold mt-2"><?php echo esc_html((string) $review['title']); ?></div>
                            <?php endif; ?>
                            <div class="mt-1 text-muted"><?php echo nl2br(esc_html((string) ($review['content'] ?? ''))); ?></div>
                            <?php if (!empty($review['image_urls']) && is_array($review['image_urls'])) : ?>
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <?php foreach ($review['image_urls'] as $review_image_url) : ?>
                                        <a href="<?php echo esc_url((string) $review_image_url); ?>" target="_blank" rel="noopener" class="text-decoration-none">
                                            <img src="<?php echo esc_url((string) $review_image_url); ?>" alt="<?php echo esc_attr__('Foto ulasan', 'velocity-marketplace'); ?>" class="border rounded" style="width:88px; height:88px; object-fit:cover;">
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php get_footer(); ?>
