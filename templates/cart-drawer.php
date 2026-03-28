<div class="vmp-cart-shortcut" x-data="vmpCart({ drawer: true })" x-init="init()">
    <button
        type="button"
        class="vmp-cart-shortcut__toggle"
        @click="openDrawer()"
        :aria-expanded="open ? 'true' : 'false'"
        aria-controls="vmp-cart-drawer-panel"
        aria-label="Buka keranjang"
    >
        <span class="vmp-cart-shortcut__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="20" r="1.5"></circle>
                <circle cx="18" cy="20" r="1.5"></circle>
                <path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8H18a1 1 0 0 0 1-.8L21 7H7.2"></path>
            </svg>
        </span>
        <span class="vmp-cart-shortcut__badge" x-show="count > 0" x-cloak x-text="count"></span>
    </button>

    <div class="vmp-cart-drawer" :class="{ 'is-open': open }" x-cloak>
        <button type="button" class="vmp-cart-drawer__backdrop" @click="closeDrawer()" aria-label="Tutup keranjang"></button>

        <aside
            id="vmp-cart-drawer-panel"
            class="vmp-cart-drawer__panel"
            role="dialog"
            aria-modal="true"
            aria-label="Keranjang"
        >
            <div class="vmp-cart-drawer__header">
                <div>
                    <h3 class="vmp-cart-drawer__title">Keranjang</h3>
                    <div class="vmp-cart-drawer__meta" x-text="count > 0 ? count + ' produk' : 'Belum ada produk'"></div>
                </div>
                <button type="button" class="vmp-cart-drawer__close" @click="closeDrawer()" aria-label="Tutup">
                    <span class="vmp-cart-drawer__close-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6L6 18"></path>
                            <path d="M6 6l12 12"></path>
                        </svg>
                    </span>
                </button>
            </div>

            <div class="vmp-cart-drawer__message" x-show="message" x-text="message"></div>

            <div class="vmp-cart-drawer__body">
                <div class="vmp-cart-drawer__loading" x-show="loading">Memuat keranjang...</div>

                <div class="vmp-cart-drawer__empty" x-show="!loading && items.length === 0">
                    <div class="vmp-cart-drawer__empty-title">Keranjang masih kosong</div>
                    <div class="vmp-cart-drawer__empty-text">Tambahkan produk untuk melanjutkan transaksi.</div>
                    <a class="vmp-cart-drawer__browse" :href="catalogUrl">Lihat Katalog</a>
                </div>

                <div class="vmp-cart-drawer__items" x-show="items.length > 0">
                    <template x-for="(item, index) in items" :key="item.id + '-' + optionKey(item) + '-' + index">
                        <div>
                            <div class="vmp-cart-drawer__seller" x-show="index === 0 || items[index - 1].seller_id !== item.seller_id">
                                <div class="vmp-cart-drawer__seller-row">
                                    <a
                                        class="vmp-cart-drawer__seller-label"
                                        :href="item.seller_url || '#'"
                                        x-text="item.seller_name || 'Toko'"
                                    ></a>
                                    <span class="vmp-cart-drawer__seller-subtotal" x-text="formatPrice(sellerSubtotal(item.seller_id))"></span>
                                </div>
                            </div>
                            <article class="vmp-cart-drawer__item">
                                <a class="vmp-cart-drawer__thumb-wrap" :href="item.link">
                                    <img class="vmp-cart-drawer__thumb" :src="item.image || placeholder" alt="">
                                </a>
                                <div class="vmp-cart-drawer__item-content">
                                    <a class="vmp-cart-drawer__item-title" :href="item.link" x-text="item.title"></a>
                                    <div class="vmp-cart-drawer__item-option" x-show="optionText(item.options)" x-text="optionText(item.options)"></div>
                                    <div class="vmp-cart-drawer__item-price" x-text="formatPrice(item.price) + ' x ' + item.qty + ' = ' + formatPrice(item.subtotal)"></div>
                                    <div class="vmp-cart-drawer__qty">
                                        <button type="button" class="vmp-cart-drawer__qty-btn" @click="changeQty(item, item.qty - 1)">-</button>
                                        <span class="vmp-cart-drawer__qty-value" x-text="item.qty"></span>
                                        <button type="button" class="vmp-cart-drawer__qty-btn" @click="changeQty(item, item.qty + 1)">+</button>
                                    </div>
                                </div>
                                <button type="button" class="vmp-cart-drawer__remove" @click="remove(item)" aria-label="Hapus produk">
                                    <span class="vmp-cart-drawer__remove-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 6L6 18"></path>
                                            <path d="M6 6l12 12"></path>
                                        </svg>
                                    </span>
                                </button>
                            </article>
                        </div>
                    </template>
                </div>
            </div>

            <div class="vmp-cart-drawer__footer" x-show="items.length > 0">
                <div class="vmp-cart-drawer__actions">
                    <a class="vmp-cart-drawer__cart-link" :href="cartUrl">Lihat Keranjang</a>
                    <button class="vmp-cart-drawer__clear" type="button" @click="clearCart()" :disabled="loading || items.length === 0">Kosongkan</button>
                </div>
                <div class="vmp-cart-drawer__summary">
                    <div class="vmp-cart-drawer__summary-label">Total</div>
                    <div class="vmp-cart-drawer__summary-value" x-text="formatPrice(total)"></div>
                </div>
                <a class="vmp-cart-drawer__checkout" :href="checkoutUrl">Checkout</a>
            </div>
        </aside>
    </div>
</div>
