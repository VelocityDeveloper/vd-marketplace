<?php
use VelocityMarketplace\Modules\Product\ProductData;

$product_id = get_the_ID();
$item = ProductData::map_post($product_id);
if (!$item) {
    status_header(404);
    nocache_headers();
    get_header();
    echo '<div class="container py-4 vmp-wrap"><div class="alert alert-warning mb-0">Produk tidak ditemukan.</div></div>';
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

get_header();
?>
<div class="container py-4 vmp-wrap">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="vmp-product-gallery" data-gallery-title="<?php echo esc_attr($item['title']); ?>">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <?php if (!empty($gallery)) : ?>
                        <button type="button" class="vmp-gallery-stage" data-gallery-open aria-label="Lihat gambar penuh">
                            <img
                                src="<?php echo esc_url($gallery[0]); ?>"
                                alt="<?php echo esc_attr($item['title']); ?>"
                                class="vmp-single-image"
                                data-gallery-main
                            >
                        </button>
                    <?php else : ?>
                        <div class="vmp-single-image vmp-single-image--empty d-flex align-items-center justify-content-center text-muted">No Image</div>
                    <?php endif; ?>
                </div>

                <?php if (count($gallery) > 1) : ?>
                    <div class="vmp-gallery-thumbs-wrap mt-3">
                        <button type="button" class="vmp-gallery-arrow vmp-gallery-arrow--prev" data-gallery-prev aria-label="Thumbnail sebelumnya">
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
                                    aria-label="<?php echo esc_attr($item['title'] . ' gambar ' . ($index + 1)); ?>"
                                >
                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($item['title']); ?>" class="vmp-single-thumb">
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="vmp-gallery-arrow vmp-gallery-arrow--next" data-gallery-next aria-label="Thumbnail berikutnya">
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
                    <span class="badge bg-warning text-dark">Premium</span>
                <?php endif; ?>
            </div>
            <h1 class="h3 mb-2"><?php echo esc_html($item['title']); ?></h1>
            <?php if (!empty($item['label'])) : ?>
                <div class="text-muted mb-2"><?php echo esc_html($item['label']); ?></div>
            <?php endif; ?>
            <div class="mb-3"><?php echo do_shortcode('[vm_price id="' . (int) $item['id'] . '" class="h5"]'); ?></div>

            <div class="row g-2 small mb-3">
                <div class="col-sm-6"><strong>SKU:</strong> <?php echo esc_html($item['sku'] !== '' ? $item['sku'] : '-'); ?></div>
                <div class="col-sm-6"><strong>Minimal Order:</strong> <?php echo esc_html((string) (int) ($item['min_order'] ?? 1)); ?></div>
                <div class="col-sm-6"><strong>Berat:</strong> <?php echo esc_html(number_format((float) ($item['weight'] ?? 0), 0, ',', '.')); ?> gr</div>
                <div class="col-sm-6"><strong>Stok:</strong>
                    <?php
                    if ($item['stock'] === null || $item['stock'] === '') {
                        echo esc_html('Tidak dibatasi');
                    } else {
                        echo esc_html((float) $item['stock'] > 0 ? (string) (int) $item['stock'] : 'Habis');
                    }
                    ?>
                </div>
            </div>

            <?php if (!empty($item['basic_options'])) : ?>
                <div class="mb-3">
                    <div class="fw-semibold mb-1"><?php echo esc_html($item['basic_name']); ?></div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($item['basic_options'] as $opt) : ?>
                            <span class="badge bg-light text-dark border"><?php echo esc_html((string) $opt); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($item['advanced_options'])) : ?>
                <div class="mb-3">
                    <div class="fw-semibold mb-1"><?php echo esc_html($item['advanced_name']); ?></div>
                    <div class="d-flex flex-column gap-1 small">
                        <?php foreach ($item['advanced_options'] as $opt) : ?>
                            <div>
                                <?php echo esc_html((string) ($opt['label'] ?? '')); ?>
                                <?php if (!empty($opt['price'])) : ?>
                                    <span class="text-muted">(<?php echo esc_html('+' . number_format((float) $opt['price'], 0, ',', '.')); ?>)</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2 mb-4">
                <?php echo do_shortcode('[vm_add_to_cart id="' . (int) $item['id'] . '" text="Tambah Keranjang" class="btn btn-dark"]'); ?>
                <?php echo do_shortcode('[vm_add_to_wishlist id="' . (int) $item['id'] . '" text="Wishlist" class="btn btn-outline-secondary"]'); ?>
                <a href="<?php echo esc_url(site_url('/keranjang/')); ?>" class="btn btn-outline-dark">Lihat Keranjang</a>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Deskripsi Produk</h2>
            <div class="vmp-content">
                <?php echo wp_kses_post(apply_filters('the_content', $content)); ?>
            </div>
        </div>
    </div>
</div>
<?php get_footer(); ?>
