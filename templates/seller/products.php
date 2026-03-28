<?php
use VelocityMarketplace\Modules\Product\ProductFields;
    $product_captcha_html = \VelocityMarketplace\Modules\Captcha\CaptchaBridge::render();
    $edit_product_id = isset($_GET['edit_product']) ? (int) $_GET['edit_product'] : 0;
    $edit_product = null;
    if ($edit_product_id > 0 && get_post_type($edit_product_id) === 'vmp_product') {
        $author_id = (int) get_post_field('post_author', $edit_product_id);
        if ($author_id === $current_user_id || current_user_can('manage_options')) {
            $edit_product = get_post($edit_product_id);
        }
    }

    $defaults = [
        'title' => $edit_product ? $edit_product->post_title : '',
        'description' => $edit_product ? $edit_product->post_content : '',
        'category_id' => 0,
    ];

    if ($edit_product) {
        $terms = wp_get_post_terms($edit_product_id, 'vmp_product_cat', ['fields' => 'ids']);
        if (!is_wp_error($terms) && !empty($terms)) {
            $defaults['category_id'] = (int) $terms[0];
        }
    }

    $featured_image_id = $edit_product ? (int) get_post_thumbnail_id($edit_product_id) : 0;
    $featured_image_url = $featured_image_id > 0 ? wp_get_attachment_image_url($featured_image_id, 'medium') : '';
    $cats = get_terms(['taxonomy' => 'vmp_product_cat','hide_empty' => false]);
    $products_query = new \WP_Query(['post_type' => 'vmp_product','post_status' => ['publish', 'pending', 'draft'],'posts_per_page' => 30,'author' => $current_user_id,'orderby' => 'date','order' => 'DESC']);
    ?>
    <div class="row g-3">
        <div class="col-lg-7">
            <?php if (!$profile_complete) : ?><div class="alert alert-warning">Lengkapi <strong>Profil Toko</strong> terlebih dahulu sebelum menambahkan produk baru.</div><?php endif; ?>
            <div class="card border-0 shadow-sm"><div class="card-body">
                <h3 class="h6 mb-3"><?php echo $edit_product ? 'Ubah Produk' : 'Tambah Produk'; ?></h3>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="vmp_action" value="seller_save_product">
                    <input type="hidden" name="product_id" value="<?php echo esc_attr($edit_product ? $edit_product_id : 0); ?>">
                    <?php wp_nonce_field('vmp_seller_product', 'vmp_seller_product_nonce'); ?>
                    <div class="row g-2">
                        <div class="col-md-8"><label class="form-label">Nama Produk</label><input type="text" name="title" class="form-control" required value="<?php echo esc_attr($defaults['title']); ?>"></div>
                        <div class="col-md-4"><label class="form-label">Kategori</label><select name="category_id" class="form-select"><option value="0">Pilih kategori</option><?php if (!is_wp_error($cats)) : foreach ($cats as $cat) : ?><option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected((int) $defaults['category_id'], (int) $cat->term_id); ?>><?php echo esc_html($cat->name); ?></option><?php endforeach; endif; ?></select></div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <div class="vmp-editor-wrap">
                                <?php
                                wp_editor($defaults['description'], 'vmp_product_description_' . ($edit_product ? $edit_product_id : 'new'), [
                                    'textarea_name' => 'description',
                                    'textarea_rows' => 10,
                                    'media_buttons' => false,
                                    'teeny' => false,
                                    'quicktags' => true,
                                ]);
                                ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Gambar Utama</label>
                            <div class="vmp-media-field" data-multiple="0">
                                <input type="hidden" id="featured_image_id" name="featured_image_id" class="vmp-media-field__input" value="<?php echo esc_attr($featured_image_id); ?>">
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <button type="button" class="btn btn-outline-dark btn-sm vmp-media-field__open" data-title="Gambar Utama" data-button="Gunakan gambar ini">Pilih dari Media Library</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm vmp-media-field__clear" <?php disabled($featured_image_id <= 0); ?>>Hapus Gambar</button>
                                </div>
                                <div class="vmp-media-field__preview" data-placeholder="Belum ada gambar utama.">
                                    <?php if ($featured_image_url) : ?>
                                        <div class="vmp-media-field__grid vmp-media-field__grid--single">
                                            <div class="vmp-media-field__item" data-id="<?php echo esc_attr((string) $featured_image_id); ?>">
                                                <img src="<?php echo esc_url($featured_image_url); ?>" alt="Gambar utama produk" class="vmp-media-field__image">
                                                <button type="button" class="btn-close vmp-media-field__remove" aria-label="Hapus gambar"></button>
                                            </div>
                                        </div>
                                    <?php else : ?>
                                        <div class="vmp-media-field__empty text-muted small">Belum ada gambar yang dipilih.</div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-text">Pilih atau unggah gambar utama melalui media library WordPress.</div>
                            </div>
                        </div>
                        <?php echo ProductFields::render_sections($edit_product ? $edit_product_id : 0, 'frontend'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                    <?php if ($product_captcha_html !== '') : ?><div class="mt-3"><?php echo $product_captcha_html; ?></div><?php endif; ?>
                    <div class="mt-3 d-flex gap-2"><button class="btn btn-primary btn-sm" type="submit" <?php disabled(!$profile_complete); ?>><?php echo $edit_product ? 'Simpan Perubahan' : 'Simpan Produk'; ?></button><?php if ($edit_product) : ?><a href="<?php echo esc_url(add_query_arg(['tab' => 'seller_products'], remove_query_arg('edit_product'))); ?>" class="btn btn-outline-secondary btn-sm">Batal</a><?php endif; ?></div>
                </form>
            </div></div>
        </div>
        <div class="col-lg-5"><div class="card border-0 shadow-sm"><div class="card-body"><h3 class="h6 mb-2">Daftar Produk</h3>
            <?php if ($products_query->have_posts()) : ?>
                <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Judul</th><th>Status</th><th></th></tr></thead><tbody>
                <?php while ($products_query->have_posts()) : $products_query->the_post(); $pid = get_the_ID(); $delete_url = add_query_arg(['tab' => 'seller_products','vmp_delete_product' => $pid,'vmp_nonce' => wp_create_nonce('vmp_delete_product_' . $pid)]); $premium = !empty(get_post_meta($pid, 'premium_request', true)); ?>
                    <tr><td><a href="<?php echo esc_url(get_permalink($pid)); ?>" target="_blank"><?php the_title(); ?></a><div class="small text-muted"><?php echo esc_html($money((float) get_post_meta($pid, 'price', true))); ?></div><?php if ($premium) : ?><div class="small text-muted">Pengajuan premium sedang ditinjau.</div><?php endif; ?></td><td><?php echo esc_html(get_post_status($pid)); ?></td><td class="text-end"><a class="btn btn-outline-dark btn-sm" href="<?php echo esc_url(add_query_arg(['tab' => 'seller_products', 'edit_product' => $pid])); ?>">Ubah</a> <a class="btn btn-outline-danger btn-sm" href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Hapus produk ini?')">Hapus</a></td></tr>
                <?php endwhile; wp_reset_postdata(); ?>
                </tbody></table></div>
            <?php else : ?>
                <div class="small text-muted">Belum ada produk yang ditambahkan.</div>
            <?php endif; ?>
        </div></div></div>
    </div>


