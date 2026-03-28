<div class="container py-4 vmp-wrap" x-data="vmpCart()" x-init="init()">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h4 mb-0">Keranjang Belanja</h2>
            <small class="text-muted">Periksa kembali item sebelum melanjutkan ke proses checkout.</small>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-dark" :href="catalogUrl">Lanjut Belanja</a>
            <button class="btn btn-sm btn-outline-danger" type="button" @click="clearCart()" :disabled="loading || items.length === 0">Kosongkan Keranjang</button>
        </div>
    </div>

    <div class="alert alert-info py-2" x-show="message" x-text="message"></div>

    <div class="table-responsive border rounded" x-show="items.length > 0">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Produk</th>
                    <th class="text-end">Harga</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-end">Subtotal</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(item, index) in items" :key="item.id + '-' + optionKey(item) + '-' + index">
                    <template>
                        <template x-if="index === 0 || items[index - 1].seller_id !== item.seller_id">
                            <tr class="table-light">
                                <td colspan="5">
                                    <div class="d-flex justify-content-between align-items-center gap-3">
                                        <a
                                            class="fw-semibold text-decoration-none"
                                            :href="item.seller_url || '#'"
                                            x-text="'Toko: ' + (item.seller_name || 'Toko')"
                                        ></a>
                                        <div class="small text-muted" x-text="'Subtotal Toko: ' + formatPrice(sellerSubtotal(item.seller_id))"></div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img :src="item.image || placeholder" alt="" class="vmp-mini-thumb rounded">
                                    <div>
                                        <a class="text-decoration-none fw-semibold" :href="item.link" x-text="item.title"></a>
                                        <div class="small text-muted" x-text="optionText(item.options)"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end" x-text="formatPrice(item.price)"></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-outline-secondary" type="button" @click="changeQty(item, item.qty - 1)">-</button>
                                    <button class="btn btn-outline-secondary disabled" type="button" x-text="item.qty"></button>
                                    <button class="btn btn-outline-secondary" type="button" @click="changeQty(item, item.qty + 1)">+</button>
                                </div>
                            </td>
                            <td class="text-end fw-semibold" x-text="formatPrice(item.subtotal)"></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-danger" type="button" @click="remove(item)">Hapus</button>
                            </td>
                        </tr>
                    </template>
                </template>
            </tbody>
        </table>
    </div>

    <div class="text-center py-5 border rounded bg-light" x-show="!loading && items.length === 0">
        <div class="h5 mb-1">Belum ada produk di keranjang</div>
        <div class="text-muted mb-3">Pilih produk dari katalog untuk melanjutkan transaksi.</div>
        <a class="btn btn-dark btn-sm" :href="catalogUrl">Lihat Katalog</a>
    </div>

    <div class="row mt-4" x-show="items.length > 0">
        <div class="col-md-6 ms-auto">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Produk</span>
                        <strong x-text="count + ' pcs'"></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Subtotal Belanja</span>
                        <strong class="text-danger" x-text="formatPrice(total)"></strong>
                    </div>
                    <a class="btn btn-primary w-100" :href="checkoutUrl">Lanjut ke Checkout</a>
                </div>
            </div>
        </div>
    </div>
</div>
