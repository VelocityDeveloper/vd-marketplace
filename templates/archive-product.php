<?php
use VelocityMarketplace\Modules\Product\ProductData;

get_header();
?>
<div class="container py-4 vmp-wrap">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1"><?php post_type_archive_title(); ?></h1>
            <div class="text-muted">Daftar produk marketplace</div>
        </div>
        <a href="<?php echo esc_url(site_url('/katalog/')); ?>" class="btn btn-sm btn-outline-dark">Mode Katalog Interaktif</a>
    </div>

    <?php if (have_posts()) : ?>
        <div class="row g-3">
            <?php while (have_posts()) : the_post(); ?>
                <?php $item = ProductData::map_post(get_the_ID()); ?>
                <?php if (!$item) : continue; endif; ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card h-100 shadow-sm border-0 vmp-product-card">
                        <?php echo do_shortcode('[vmp_thumbnail id="' . (int) $item['id'] . '"]'); ?>
                        <div class="card-body d-flex flex-column">
                            <h2 class="card-title h6 mb-1"><a href="<?php echo esc_url($item['link']); ?>" class="text-decoration-none text-dark"><?php echo esc_html($item['title']); ?></a></h2>
                            <?php if (!empty($item['label'])) : ?>
                                <div class="small text-muted mb-2"><?php echo esc_html($item['label']); ?></div>
                            <?php endif; ?>
                            <?php echo do_shortcode('[vmp_price id="' . (int) $item['id'] . '"]'); ?>
                            <div class="small text-muted mb-3">
                                <?php
                                if ($item['stock'] === null || $item['stock'] === '') {
                                    echo esc_html('Stok tidak dibatasi');
                                } else {
                                    echo esc_html((float) $item['stock'] > 0 ? 'Stok: ' . (int) $item['stock'] : 'Stok habis');
                                }
                                ?>
                            </div>
                            <div class="mt-auto d-flex gap-2">
                                <?php echo do_shortcode('[vmp_add_to_cart id="' . (int) $item['id'] . '" class="btn btn-sm btn-dark flex-grow-1"]'); ?>
                                <?php echo do_shortcode('[vmp_add_to_wishlist id="' . (int) $item['id'] . '" class="btn btn-sm btn-outline-secondary"]'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="mt-4">
            <?php
            echo wp_kses_post(paginate_links([
                'prev_text' => 'Prev',
                'next_text' => 'Next',
                'type' => 'list',
            ]));
            ?>
        </div>
    <?php else : ?>
        <div class="alert alert-info mb-0">Belum ada produk yang dipublish.</div>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
