<?php
use VelocityMarketplace\Modules\Product\ProductData;
use VelocityMarketplace\Modules\Product\ProductQuery;

$product_query = new ProductQuery();
$filters = $product_query->normalize_filters($_GET);
$categories = get_terms([
    'taxonomy' => 'vmp_product_cat',
    'hide_empty' => false,
]);
$label_options = $product_query->label_options();
$archive_url = get_post_type_archive_link('vmp_product');
$pages = get_option(VMP_PAGES_OPTION, []);
$catalog_url = site_url('/catalog/');
if (is_array($pages) && !empty($pages['katalog'])) {
    $maybe_catalog_url = get_permalink((int) $pages['katalog']);
    if ($maybe_catalog_url) {
        $catalog_url = $maybe_catalog_url;
    }
}
$current_args = array_filter([
    'search' => (string) ($filters['search'] ?? ''),
    'product_cat' => (int) ($filters['cat'] ?? 0),
    'product_label' => (string) ($filters['label'] ?? ''),
    'store_type' => (string) ($filters['store_type'] ?? ''),
    'min_price' => $filters['min_price'] !== '' ? (string) $filters['min_price'] : '',
    'max_price' => $filters['max_price'] !== '' ? (string) $filters['max_price'] : '',
    'sort' => (string) ($filters['sort'] ?? ''),
], static function ($value) {
    return $value !== '' && $value !== 0;
});

$render_filter_form = static function ($form_class = '') use ($archive_url, $filters, $categories, $label_options) {
    ?>
    <form method="get" action="<?php echo esc_url($archive_url); ?>" class="<?php echo esc_attr(trim('vmp-archive-filter-form ' . $form_class)); ?>">
        <div class="vmp-archive-filter-group">
            <label class="form-label small mb-1"><?php echo esc_html__('Nama Produk', 'velocity-marketplace'); ?></label>
            <input type="search" name="search" class="form-control form-control-sm" placeholder="<?php echo esc_attr__('Search product name', 'velocity-marketplace'); ?>" value="<?php echo esc_attr((string) ($filters['search'] ?? '')); ?>">
        </div>

        <div class="vmp-archive-filter-group">
            <label class="form-label small mb-1"><?php echo esc_html__('Kategori', 'velocity-marketplace'); ?></label>
            <select name="product_cat" class="form-select form-select-sm">
                <option value=""><?php echo esc_html__('Semua Kategori', 'velocity-marketplace'); ?></option>
                <?php foreach ((array) $categories as $category) : ?>
                    <?php if (!is_object($category) || empty($category->term_id)) { continue; } ?>
                    <option value="<?php echo esc_attr((string) $category->term_id); ?>" <?php selected((int) ($filters['cat'] ?? 0), (int) $category->term_id); ?>><?php echo esc_html((string) $category->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="vmp-archive-filter-group">
            <label class="form-label small mb-1"><?php echo esc_html__('Label', 'velocity-marketplace'); ?></label>
            <select name="product_label" class="form-select form-select-sm">
                <option value=""><?php echo esc_html__('Semua Label', 'velocity-marketplace'); ?></option>
                <?php foreach ($label_options as $label_value => $label_name) : ?>
                    <option value="<?php echo esc_attr($label_value); ?>" <?php selected((string) ($filters['label'] ?? ''), (string) $label_value); ?>><?php echo esc_html($label_name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="vmp-archive-filter-group">
            <label class="form-label small mb-1"><?php echo esc_html__('Jenis Toko', 'velocity-marketplace'); ?></label>
            <select name="store_type" class="form-select form-select-sm">
                <option value=""><?php echo esc_html__('Semua Toko', 'velocity-marketplace'); ?></option>
                <option value="star_seller" <?php selected((string) ($filters['store_type'] ?? ''), 'star_seller'); ?>><?php echo esc_html__('Star Seller', 'velocity-marketplace'); ?></option>
                <option value="regular" <?php selected((string) ($filters['store_type'] ?? ''), 'regular'); ?>><?php echo esc_html__('Toko Biasa', 'velocity-marketplace'); ?></option>
            </select>
        </div>

        <div class="vmp-archive-filter-group">
            <label class="form-label small mb-1"><?php echo esc_html__('Harga Minimum', 'velocity-marketplace'); ?></label>
            <input type="number" name="min_price" min="0" step="1000" class="form-control form-control-sm" value="<?php echo esc_attr($filters['min_price'] !== '' ? (string) $filters['min_price'] : ''); ?>">
        </div>

        <div class="vmp-archive-filter-group">
            <label class="form-label small mb-1"><?php echo esc_html__('Harga Maksimum', 'velocity-marketplace'); ?></label>
            <input type="number" name="max_price" min="0" step="1000" class="form-control form-control-sm" value="<?php echo esc_attr($filters['max_price'] !== '' ? (string) $filters['max_price'] : ''); ?>">
        </div>

        <div class="vmp-archive-filter-group">
            <label class="form-label small mb-1"><?php echo esc_html__('Urutkan', 'velocity-marketplace'); ?></label>
            <select name="sort" class="form-select form-select-sm">
                <option value="latest" <?php selected((string) ($filters['sort'] ?? 'latest'), 'latest'); ?>><?php echo esc_html__('Terbaru', 'velocity-marketplace'); ?></option>
                <option value="price_asc" <?php selected((string) ($filters['sort'] ?? ''), 'price_asc'); ?>><?php echo esc_html__('Harga Terendah', 'velocity-marketplace'); ?></option>
                <option value="price_desc" <?php selected((string) ($filters['sort'] ?? ''), 'price_desc'); ?>><?php echo esc_html__('Harga Tertinggi', 'velocity-marketplace'); ?></option>
                <option value="name_asc" <?php selected((string) ($filters['sort'] ?? ''), 'name_asc'); ?>><?php echo esc_html__('Name A-Z', 'velocity-marketplace'); ?></option>
                <option value="name_desc" <?php selected((string) ($filters['sort'] ?? ''), 'name_desc'); ?>><?php echo esc_html__('Name Z-A', 'velocity-marketplace'); ?></option>
                <option value="popular" <?php selected((string) ($filters['sort'] ?? ''), 'popular'); ?>><?php echo esc_html__('Most Popular', 'velocity-marketplace'); ?></option>
            </select>
        </div>

        <div class="d-grid gap-2 pt-2">
            <button type="submit" class="btn btn-sm btn-dark"><?php echo esc_html__('Terapkan Filter', 'velocity-marketplace'); ?></button>
            <a href="<?php echo esc_url($archive_url); ?>" class="btn btn-sm btn-outline-secondary"><?php echo esc_html__('Atur Ulang', 'velocity-marketplace'); ?></a>
        </div>
    </form>
    <?php
};

get_header();
?>
<div class="container py-4 vmp-wrap">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1"><?php post_type_archive_title(); ?></h1>
            <div class="text-muted"><?php echo esc_html__('Browse products available in the marketplace.', 'velocity-marketplace'); ?></div>
        </div>
        <a href="<?php echo esc_url($catalog_url); ?>" class="btn btn-sm btn-outline-dark"><?php echo esc_html__('Buka Katalog Interaktif', 'velocity-marketplace'); ?></a>
    </div>

    <div class="d-flex d-lg-none justify-content-between align-items-center gap-2 mb-3">
        <div class="small text-muted">
            <?php echo esc_html(sprintf(_n('%d product found', '%d products found', (int) $wp_query->found_posts, 'velocity-marketplace'), (int) $wp_query->found_posts)); ?>
        </div>
        <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="offcanvas" data-bs-target="#vmpArchiveFilterCanvas" aria-controls="vmpArchiveFilterCanvas">
            <?php echo esc_html__('Filter Produk', 'velocity-marketplace'); ?>
        </button>
    </div>

    <div class="row g-4 vmp-archive-layout">
        <aside class="col-lg-3 d-none d-lg-block">
            <div class="card border-0 shadow-sm vmp-archive-filter-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="h6 mb-0"><?php echo esc_html__('Filter', 'velocity-marketplace'); ?></h2>
                        <span class="small text-muted"><?php echo esc_html(sprintf(_n('%d product', '%d products', (int) $wp_query->found_posts, 'velocity-marketplace'), (int) $wp_query->found_posts)); ?></span>
                    </div>
                    <?php $render_filter_form(); ?>
                </div>
            </div>
        </aside>

        <div class="col-12 col-lg-9">
            <div class="d-none d-lg-flex justify-content-between align-items-center gap-3 mb-3">
                <div class="small text-muted"><?php echo esc_html(sprintf(_n('%d product found', '%d products found', (int) $wp_query->found_posts, 'velocity-marketplace'), (int) $wp_query->found_posts)); ?></div>
            </div>

            <?php if (have_posts()) : ?>
                <div class="row g-3">
                    <?php while (have_posts()) : the_post(); ?>
                        <?php $item = ProductData::map_post(get_the_ID()); ?>
                        <?php if (!$item) : continue; endif; ?>
                        <div class="col-6 col-md-4 col-xxl-3">
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
                                            echo esc_html__('Stok tidak terbatas', 'velocity-marketplace');
                                        } else {
                                            echo esc_html((float) $item['stock'] > 0 ? sprintf(__('Stok: %d', 'velocity-marketplace'), (int) $item['stock']) : __('Stok habis', 'velocity-marketplace'));
                                        }
                                        ?>
                                    </div>
                                    <div class="mt-auto d-flex gap-2">
                                        <?php echo do_shortcode('[vmp_add_to_cart id="' . (int) $item['id'] . '" class="btn btn-sm btn-dark flex-grow-1"]'); ?>
                                        <?php echo do_shortcode('[vmp_add_to_wishlist id="' . (int) $item['id'] . '" class="btn btn-sm btn-outline-secondary vmp-wishlist-button"]'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div class="mt-4">
                    <?php
                    echo wp_kses_post(paginate_links([
                        'prev_text' => __('Previous', 'velocity-marketplace'),
                        'next_text' => __('Next', 'velocity-marketplace'),
                        'add_args' => $current_args,
                        'type' => 'list',
                    ]));
                    ?>
                </div>
            <?php else : ?>
                <div class="alert alert-info mb-0"><?php echo esc_html__('No products are available right now.', 'velocity-marketplace'); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-start vmp-archive-filter-canvas" tabindex="-1" id="vmpArchiveFilterCanvas" aria-labelledby="vmpArchiveFilterCanvasLabel">
    <div class="offcanvas-header">
        <div>
            <h2 class="offcanvas-title h5 mb-0" id="vmpArchiveFilterCanvasLabel"><?php echo esc_html__('Filter Produk', 'velocity-marketplace'); ?></h2>
            <div class="small text-muted"><?php echo esc_html(sprintf(_n('%d product found', '%d products found', (int) $wp_query->found_posts, 'velocity-marketplace'), (int) $wp_query->found_posts)); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="<?php echo esc_attr__('Close', 'velocity-marketplace'); ?>"></button>
    </div>
    <div class="offcanvas-body">
        <?php $render_filter_form('vmp-archive-filter-form--mobile'); ?>
    </div>
</div>
<?php get_footer(); ?>
