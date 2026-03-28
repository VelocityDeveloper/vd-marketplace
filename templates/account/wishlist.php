    <?php if (empty($wishlist_ids)) : ?>
        <div class="alert alert-info mb-0">Belum ada produk dalam daftar favorit. Tambahkan produk dari katalog untuk menyimpannya di sini.</div>
    <?php else : ?>
        <?php $wishlist_query = new \WP_Query(['post_type' => 'vmp_product','post_status' => 'publish','posts_per_page' => 100,'post__in' => $wishlist_ids,'orderby' => 'post__in']); ?>
        <div class="table-responsive border rounded"><table class="table table-sm table-hover mb-0"><thead class="table-light"><tr><th>Produk</th><th class="text-end">Harga</th><th class="text-end">Aksi</th></tr></thead><tbody>
        <?php while ($wishlist_query->have_posts()) : $wishlist_query->the_post(); $pid = get_the_ID(); $price = (float) get_post_meta($pid, 'price', true); ?>
            <tr>
                <td><a href="<?php echo esc_url(get_permalink($pid)); ?>" target="_blank"><?php the_title(); ?></a></td>
                <td class="text-end"><?php echo esc_html($money($price)); ?></td>
                <td class="text-end"><form method="post" class="d-inline"><input type="hidden" name="vmp_action" value="wishlist_remove"><input type="hidden" name="product_id" value="<?php echo esc_attr($pid); ?>"><?php wp_nonce_field('vmp_wishlist_remove_' . $pid, 'vmp_wishlist_nonce'); ?><button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button></form></td>
            </tr>
        <?php endwhile; wp_reset_postdata(); ?>
        </tbody></table></div>
    <?php endif; ?>
