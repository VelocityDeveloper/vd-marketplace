<?php

namespace VelocityMarketplace\Modules\Account\Handlers;

use VelocityMarketplace\Modules\Wishlist\WishlistRepository;

class WishlistActionHandler extends BaseActionHandler
{
    public function remove()
    {
        $nonce = isset($_POST['vmp_wishlist_nonce']) ? (string) wp_unslash($_POST['vmp_wishlist_nonce']) : '';
        $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        if ($product_id <= 0 || $nonce === '' || !wp_verify_nonce($nonce, 'vmp_wishlist_remove_' . $product_id)) {
            $this->redirect_with(['vmp_error' => 'Aksi wishlist tidak valid.', 'tab' => 'wishlist']);
        }

        $repo = new WishlistRepository();
        $repo->remove($product_id);

        $this->redirect_with([
            'vmp_notice' => 'Produk dihapus dari wishlist.',
            'tab' => 'wishlist',
        ]);
    }
}
