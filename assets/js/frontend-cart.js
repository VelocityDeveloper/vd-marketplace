/* Komponen keranjang untuk load item, ubah qty, hapus, dan kosongkan cart. */
(() => {
  const shared = window.VMPFrontend;
  if (!shared) {
    return;
  }

  const { cfg, request, cartHelpers } = shared;

  // Menyediakan state Alpine untuk halaman keranjang dan drawer keranjang.
  const vmpCart = (config = {}) => ({
    ...cartHelpers,
    drawer: !!config.drawer,
    open: false,
    loading: false,
    items: [],
    total: 0,
    count: 0,
    message: '',
    cartUrl: cfg.cartUrl || '/keranjang/',
    checkoutUrl: cfg.checkoutUrl || '/checkout/',
    catalogUrl: cfg.catalogUrl || '/katalog/',
    // Memuat isi keranjang awal dan mendengar update global cart.
    async init() {
      await this.fetchCart();
      window.addEventListener('vmp:cart-updated', () => {
        this.fetchCart();
      });
      window.addEventListener('keydown', (event) => {
        if (this.drawer && this.open && event.key === 'Escape') {
          this.closeDrawer();
        }
      });
    },
    // Membuka panel drawer dan mengunci scroll body bila mode drawer aktif.
    openDrawer() {
      if (!this.drawer) return;
      this.open = true;
      document.body.classList.add('vmp-cart-drawer-open');
    },
    // Menutup panel drawer dan mengembalikan scroll body normal.
    closeDrawer() {
      if (!this.drawer) return;
      this.open = false;
      document.body.classList.remove('vmp-cart-drawer-open');
    },
    // Mengambil isi keranjang terbaru dari REST API.
    async fetchCart() {
      this.loading = true;
      this.message = '';
      try {
        const data = await request('cart', { method: 'GET' });
        this.items = Array.isArray(data.items) ? data.items : [];
        this.total = Number(data.total || 0);
        this.count = Number(data.count || 0);
      } catch (e) {
        this.items = [];
        this.total = 0;
        this.count = 0;
        this.message = e.message || 'Keranjang tidak dapat dimuat.';
      } finally {
        this.loading = false;
      }
    },
    // Menghitung subtotal semua item dari satu seller untuk tampilan group header.
    sellerSubtotal(sellerId) {
      return this.items.reduce((sum, item) => {
        return Number(item?.seller_id || 0) === Number(sellerId || 0)
          ? sum + Number(item?.subtotal || 0)
          : sum;
      }, 0);
    },
    // Memperbarui jumlah item tertentu di keranjang.
    async changeQty(item, qty) {
      if (qty < 0) return;
      try {
        await request('cart', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id: item.id,
            qty,
            options: item.options || {},
          }),
        });
        window.dispatchEvent(new CustomEvent('vmp:cart-updated'));
      } catch (e) {
        this.message = e.message || 'Jumlah produk tidak dapat diperbarui.';
      }
    },
    // Menghapus item dari keranjang dengan qty nol.
    async remove(item) {
      await this.changeQty(item, 0);
    },
    // Mengosongkan seluruh isi keranjang user.
    async clearCart() {
      try {
        await request('cart', { method: 'DELETE' });
        window.dispatchEvent(new CustomEvent('vmp:cart-updated'));
      } catch (e) {
        this.message = e.message || 'Keranjang tidak dapat dikosongkan.';
      }
    },
  });

  window.vmpCart = vmpCart;
  document.addEventListener('alpine:init', () => {
    Alpine.data('vmpCart', vmpCart);
  });
})();

