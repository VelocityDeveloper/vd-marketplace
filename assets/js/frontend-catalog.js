/* Komponen katalog produk dan aksi wishlist/cart di halaman listing. */
(() => {
  const shared = window.VMPFrontend;
  if (!shared) {
    return;
  }

  const { cfg, api, request, money, placeholder, wishlistIconSvg } = shared;

  // Menyediakan state Alpine untuk filter, pagination, dan aksi katalog.
  const vmpCatalog = (perPage = 12) => ({
    loading: false,
    items: [],
    wishlistIds: [],
    currentPage: 1,
    totalPages: 1,
    total: 0,
    search: '',
    sort: 'latest',
    cat: '',
    label: '',
    storeType: '',
    minPrice: '',
    maxPrice: '',
    message: '',
    placeholder,
    perPage,
    // Memuat wishlist user dan halaman katalog pertama saat komponen aktif.
    async init() {
      if (cfg.isLoggedIn) {
        await this.fetchWishlist();
      }
      await this.fetchProducts(1);
    },
    // Mengambil daftar favorit agar tombol wishlist sinkron dengan akun login.
    async fetchWishlist() {
      try {
        const data = await request('wishlist', { method: 'GET' });
        this.wishlistIds = Array.isArray(data.items)
          ? data.items.map((id) => Number(id))
          : [];
      } catch (e) {
        this.wishlistIds = [];
      }
    },
    // Mengambil daftar produk berdasarkan filter, sort, dan halaman aktif.
    async fetchProducts(nextPage = 1) {
      this.loading = true;
      this.message = '';
      try {
        const url = new URL(api('products'));
        url.searchParams.set('page', String(nextPage));
        url.searchParams.set('per_page', String(this.perPage));
        if (this.search) url.searchParams.set('search', this.search);
        if (this.sort) url.searchParams.set('sort', this.sort);
        if (this.cat) url.searchParams.set('cat', this.cat);
        if (this.label) url.searchParams.set('label', this.label);
        if (this.storeType) url.searchParams.set('store_type', this.storeType);
        if (this.minPrice !== '' && this.minPrice !== null) {
          url.searchParams.set('min_price', String(this.minPrice));
        }
        if (this.maxPrice !== '' && this.maxPrice !== null) {
          url.searchParams.set('max_price', String(this.maxPrice));
        }

        const res = await fetch(url.toString(), { credentials: 'same-origin' });
        const data = await res.json();
        if (!res.ok) {
          throw new Error(data.message || 'Katalog produk tidak dapat dimuat.');
        }

        this.items = Array.isArray(data.items) ? data.items : [];
        this.currentPage = Number(data.page || nextPage || 1);
        this.totalPages = Number(data.pages || 1);
        this.total = Number(data.total || this.items.length);
      } catch (e) {
        this.items = [];
        this.currentPage = 1;
        this.totalPages = 1;
        this.message = e.message || 'Terjadi kendala saat memuat produk.';
      } finally {
        this.loading = false;
      }
    },
    // Memformat harga produk untuk tampilan kartu katalog.
    formatPrice(value) {
      return money(value);
    },
    // Mengubah nilai stok menjadi label yang mudah dibaca user.
    stockText(stock) {
      if (stock === null || stock === undefined || stock === '') {
        return 'Stok tidak dibatasi';
      }
      const n = Number(stock || 0);
      return n > 0 ? `Stok: ${n}` : 'Stok habis';
    },
    // Menyusun ringkasan rating produk dari nilai rata-rata dan jumlah ulasan.
    ratingText(item) {
      const avg = Number(item?.rating_average || 0);
      const count = Number(item?.review_count || 0);
      if (count <= 0) {
        return 'Belum ada ulasan';
      }
      return `${avg.toFixed(1)} / 5 dari ${count} ulasan`;
    },
    // Mengembalikan semua filter katalog ke kondisi awal.
    resetFilters() {
      this.search = '';
      this.sort = 'latest';
      this.cat = '';
      this.label = '';
      this.storeType = '';
      this.minPrice = '';
      this.maxPrice = '';
      this.fetchProducts(1);
    },
    // Menambahkan produk dari katalog ke keranjang dengan opsi default teraman.
    async addToCart(item) {
      const options = {};
      if (Array.isArray(item.variant_options) && item.variant_options.length > 0) {
        options.variant = item.variant_options[0];
      }
      if (
        Array.isArray(item.price_adjustment_options) &&
        item.price_adjustment_options.length > 0 &&
        item.price_adjustment_options[0].label
      ) {
        options.price_adjustment = item.price_adjustment_options[0].label;
      }

      try {
        await request('cart', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id: item.id,
            qty: 1,
            options,
          }),
        });
        this.message = 'Produk berhasil ditambahkan ke keranjang.';
        window.dispatchEvent(new CustomEvent('vmp:cart-updated'));
      } catch (e) {
        this.message = e.message || 'Produk tidak dapat ditambahkan ke keranjang.';
      }
    },
    // Mengecek apakah produk sudah ada di daftar favorit user.
    isWishlisted(productId) {
      return this.wishlistIds.includes(Number(productId));
    },
    // Menghasilkan ikon hati outline/fill untuk tombol wishlist katalog.
    wishlistIcon(active) {
      return wishlistIconSvg(!!active);
    },
    // Menambah atau menghapus produk dari daftar favorit user login.
    async toggleWishlist(item) {
      if (!cfg.isLoggedIn) {
        this.message = 'Masuk terlebih dahulu untuk menggunakan daftar favorit.';
        return;
      }

      try {
        const data = await request('wishlist', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ product_id: item.id }),
        });
        this.wishlistIds = Array.isArray(data.items)
          ? data.items.map((id) => Number(id))
          : this.wishlistIds;
        this.message = data.active
          ? 'Produk berhasil disimpan ke daftar favorit.'
          : 'Produk dihapus dari daftar favorit.';
      } catch (e) {
        this.message = e.message || 'Daftar favorit tidak dapat diperbarui.';
      }
    },
  });

  window.vmpCatalog = vmpCatalog;
  document.addEventListener('alpine:init', () => {
    Alpine.data('vmpCatalog', vmpCatalog);
  });
})();

