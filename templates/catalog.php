<?php
$pp = isset($per_page) ? (int) $per_page : 12;
if ($pp <= 0) {
    $pp = 12;
}

$categories = get_terms([
    'taxonomy' => 'vmp_product_cat',
    'hide_empty' => false,
]);
$label_options = [
    'new' => 'Baru',
    'limited' => 'Terbatas',
    'best' => 'Terlaris',
];
?>
<div class="container py-4 vmp-wrap" x-data="vmpCatalog(<?php echo esc_attr($pp); ?>)" x-init="init()">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h2 class="h4 mb-0">Katalog Produk</h2>
            <small class="text-muted">Temukan produk berdasarkan nama, kategori, label, jenis toko, dan rentang harga.</small>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Nama Produk</label>
                    <input type="search" class="form-control form-control-sm" placeholder="Cari nama produk" x-model="search" @keydown.enter.prevent="fetchProducts(1)">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Kategori</label>
                    <select class="form-select form-select-sm" x-model="cat" @change="fetchProducts(1)">
                        <option value="">Semua Kategori</option>
                        <?php foreach ((array) $categories as $category) : ?>
                            <?php if (!is_object($category) || empty($category->term_id)) { continue; } ?>
                            <option value="<?php echo esc_attr((string) $category->term_id); ?>"><?php echo esc_html((string) $category->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Label</label>
                    <select class="form-select form-select-sm" x-model="label" @change="fetchProducts(1)">
                        <option value="">Semua Label</option>
                        <?php foreach ($label_options as $label_value => $label_name) : ?>
                            <option value="<?php echo esc_attr($label_value); ?>"><?php echo esc_html($label_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Jenis Toko</label>
                    <select class="form-select form-select-sm" x-model="storeType" @change="fetchProducts(1)">
                        <option value="">Semua Toko</option>
                        <option value="star_seller">Star Seller</option>
                        <option value="regular">Toko Biasa</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Harga Minimum</label>
                    <input type="number" min="0" step="1000" class="form-control form-control-sm" x-model="minPrice" @keydown.enter.prevent="fetchProducts(1)">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Harga Maksimum</label>
                    <input type="number" min="0" step="1000" class="form-control form-control-sm" x-model="maxPrice" @keydown.enter.prevent="fetchProducts(1)">
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Urutkan</label>
                    <select class="form-select form-select-sm" x-model="sort" @change="fetchProducts(1)">
                        <option value="latest">Terbaru</option>
                        <option value="price_asc">Harga Terendah</option>
                        <option value="price_desc">Harga Tertinggi</option>
                        <option value="name_asc">Nama A-Z</option>
                        <option value="name_desc">Nama Z-A</option>
                        <option value="popular">Terpopuler</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button class="btn btn-sm btn-primary" type="button" @click="fetchProducts(1)">Terapkan Filter</button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="resetFilters()">Atur Ulang</button>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info py-2" x-show="message" x-text="message"></div>
    <div class="row g-3" x-show="!loading && items.length > 0">
        <template x-for="item in items" :key="item.id">
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card h-100 shadow-sm border-0">
                    <a :href="item.link" class="vmp-thumb-wrap">
                        <img :src="item.image || placeholder" class="card-img-top vmp-thumb" :alt="item.title">
                    </a>
                    <div class="card-body d-flex flex-column">
                        <h3 class="card-title h6 mb-1" x-text="item.title"></h3>
                        <div class="small text-muted mb-2" x-show="item.label" x-text="item.label"></div>
                        <div class="fw-semibold text-danger mb-1" x-text="formatPrice(item.price)"></div>
                        <div class="small text-muted mb-1" x-text="stockText(item.stock)"></div>
                        <div class="small text-muted mb-3" x-text="ratingText(item)"></div>
                        <div class="mt-auto d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-dark flex-grow-1" @click="addToCart(item)">Tambah Keranjang</button>
                            <button
                                type="button"
                                class="btn btn-sm"
                                :class="isWishlisted(item.id) ? 'btn-danger' : 'btn-outline-secondary'"
                                @click="toggleWishlist(item)"
                                title="Wishlist"
                            >&hearts;</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div class="py-5 text-center" x-show="loading">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="small text-muted mt-2">Memuat daftar produk...</div>
    </div>

    <div class="py-5 text-center border rounded bg-light" x-show="!loading && items.length === 0">
        <div class="h5 mb-1">Produk tidak ditemukan</div>
        <div class="text-muted">Ubah kata kunci atau filter untuk melihat hasil lainnya.</div>
    </div>

    <div class="d-flex justify-content-center align-items-center gap-2 mt-4" x-show="totalPages > 1">
        <button class="btn btn-outline-secondary btn-sm" type="button" :disabled="currentPage <= 1" @click="fetchProducts(currentPage - 1)">Sebelumnya</button>
        <span class="small text-muted">Halaman <span x-text="currentPage"></span> / <span x-text="totalPages"></span></span>
        <button class="btn btn-outline-secondary btn-sm" type="button" :disabled="currentPage >= totalPages" @click="fetchProducts(currentPage + 1)">Berikutnya</button>
    </div>
</div>
