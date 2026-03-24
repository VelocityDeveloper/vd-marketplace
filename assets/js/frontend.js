(() => {
  const cfg = window.vmpSettings || {};
  const currencyCode =
    cfg.currency && String(cfg.currency).toUpperCase() === "USD" ? "USD" : "IDR";
  const currencySymbol =
    typeof cfg.currencySymbol === "string" && cfg.currencySymbol.trim() !== ""
      ? cfg.currencySymbol.trim()
      : currencyCode === "USD"
        ? "$"
        : "Rp";
  const paymentMethods = Array.isArray(cfg.paymentMethods)
    ? cfg.paymentMethods
    : ["bank"];
  const placeholder =
    typeof cfg.noImageUrl === "string" && cfg.noImageUrl.trim() !== ""
      ? cfg.noImageUrl.trim()
      : "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0nNDAwJyBoZWlnaHQ9JzMwMCcgdmlld0JveD0nMCAwIDQwMCAzMDAnIHhtbG5zPSdodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2Zyc+PHJlY3Qgd2lkdGg9JzQwMCcgaGVpZ2h0PSczMDAnIGZpbGw9JyNlZWVmZjEnLz48dGV4dCB4PSc1MCUnIHk9JzUwJScgZG9taW5hbnQtYmFzZWxpbmU9J21pZGRsZScgdGV4dC1hbmNob3I9J21pZGRsZScgZmlsbD0nIzYwNzA4MCcgZm9udC1mYW1pbHk9J3NhbnMtc2VyaWYnIGZvbnQtc2l6ZT0nMTYnPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==";

  const api = (path) => `${cfg.restUrl || ""}${path || ""}`;

  const request = async (path, options = {}) => {
    const opt = Object.assign(
      {
        credentials: "same-origin",
        headers: {},
      },
      options,
    );
    if (cfg.nonce) {
      opt.headers["X-WP-Nonce"] = cfg.nonce;
    }
    const res = await fetch(api(path), opt);
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      throw new Error(data.message || "Request gagal");
    }
    return data;
  };

  const flashButtonLabel = (button, nextLabel) => {
    if (!button) return;
    const original =
      button.dataset.defaultLabel || button.textContent.trim() || "Proses";
    button.dataset.defaultLabel = original;
    button.textContent = nextLabel;
    window.setTimeout(() => {
      button.textContent = button.dataset.defaultLabel || original;
    }, 1400);
  };

  const money = (value) => {
    const num = Number(value || 0);
    try {
      return new Intl.NumberFormat(currencyCode === "USD" ? "en-US" : "id-ID", {
        style: "currency",
        currency: currencyCode,
        minimumFractionDigits: 0,
      }).format(num);
    } catch (e) {
      return `${currencySymbol} ${num.toLocaleString("id-ID")}`;
    }
  };

  const optionKey = (item) => {
    try {
      return JSON.stringify(item && item.options ? item.options : {});
    } catch (e) {
      return "";
    }
  };

  const mapProvince = (row) => ({
    province_id: String((row && row.province_id) || ""),
    province: String((row && row.province) || ""),
  });

  const mapCity = (row) => ({
    city_id: String((row && row.city_id) || ""),
    city_name: String((row && row.city_name) || ""),
    type: String((row && row.type) || ""),
    province: String((row && row.province) || ""),
    postal_code: String((row && row.postal_code) || ""),
  });

  const mapSubdistrict = (row) => ({
    subdistrict_id: String((row && row.subdistrict_id) || ""),
    subdistrict_name: String((row && row.subdistrict_name) || ""),
  });

  const fetchShippingList = async (path) => {
    const data = await request(path, { method: "GET" });
    return Array.isArray(data.data) ? data.data : [];
  };

  const gatherCaptcha = (formNode) => {
    const out = {};
    if (!formNode) return out;

    const token = formNode.querySelector("input[name='vd_captcha_token']");
    const input = formNode.querySelector("input[name='vd_captcha_input']");
    const grecaptchaInput = formNode.querySelector(
      "textarea[name='g-recaptcha-response'], input[name='g-recaptcha-response']",
    );

    if (token && token.value) out.vd_captcha_token = token.value;
    if (input && input.value) out.vd_captcha_input = input.value;
    if (grecaptchaInput && grecaptchaInput.value) {
      out["g-recaptcha-response"] = grecaptchaInput.value;
      out.g_recaptcha_response = grecaptchaInput.value;
    }
    return out;
  };

  const defaultPaymentMethod =
    paymentMethods.length > 0 && typeof paymentMethods[0] === "string"
      ? paymentMethods[0]
      : "bank";

  const cartHelpers = {
    placeholder,
    optionKey,
    optionText(options) {
      if (!options || typeof options !== "object") return "";
      const lines = [];
      if (options.basic) lines.push(`Basic: ${options.basic}`);
      if (options.advanced) lines.push(`Advanced: ${options.advanced}`);
      return lines.join(" | ");
    },
    formatPrice(value) {
      return money(value);
    },
  };

  const vmpCatalog = (perPage = 12) => ({
    loading: false,
    items: [],
    wishlistIds: [],
    currentPage: 1,
    totalPages: 1,
    total: 0,
    search: "",
    sort: "latest",
    message: "",
    placeholder,
    perPage,
    async init() {
      if (cfg.isLoggedIn) {
        await this.fetchWishlist();
      }
      await this.fetchProducts(1);
    },
    async fetchWishlist() {
      try {
        const data = await request("wishlist", { method: "GET" });
        this.wishlistIds = Array.isArray(data.items)
          ? data.items.map((id) => Number(id))
          : [];
      } catch (e) {
        this.wishlistIds = [];
      }
    },
    async fetchProducts(nextPage = 1) {
      this.loading = true;
      this.message = "";
      try {
        const url = new URL(api("products"));
        url.searchParams.set("page", String(nextPage));
        url.searchParams.set("per_page", String(this.perPage));
        if (this.search) url.searchParams.set("search", this.search);
        if (this.sort) url.searchParams.set("sort", this.sort);

        const res = await fetch(url.toString(), { credentials: "same-origin" });
        const data = await res.json();
        if (!res.ok) {
          throw new Error(data.message || "Gagal memuat katalog");
        }

        this.items = Array.isArray(data.items) ? data.items : [];
        this.currentPage = Number(data.page || nextPage || 1);
        this.totalPages = Number(data.pages || 1);
        this.total = Number(data.total || this.items.length);
      } catch (e) {
        this.items = [];
        this.currentPage = 1;
        this.totalPages = 1;
        this.message = e.message || "Terjadi kesalahan saat memuat produk.";
      } finally {
        this.loading = false;
      }
    },
    formatPrice(value) {
      return money(value);
    },
    stockText(stock) {
      if (stock === null || stock === undefined || stock === "") {
        return "Stok tidak dibatasi";
      }
      const n = Number(stock || 0);
      return n > 0 ? `Stok: ${n}` : "Stok habis";
    },
    async addToCart(item) {
      const options = {};
      if (Array.isArray(item.basic_options) && item.basic_options.length > 0) {
        options.basic = item.basic_options[0];
      }
      if (
        Array.isArray(item.advanced_options) &&
        item.advanced_options.length > 0 &&
        item.advanced_options[0].label
      ) {
        options.advanced = item.advanced_options[0].label;
      }

      try {
        await request("cart", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            id: item.id,
            qty: 1,
            options,
          }),
        });
        this.message = "Produk ditambahkan ke keranjang.";
        window.dispatchEvent(new CustomEvent("vmp:cart-updated"));
      } catch (e) {
        this.message = e.message || "Gagal menambahkan ke keranjang.";
      }
    },
    isWishlisted(productId) {
      return this.wishlistIds.includes(Number(productId));
    },
    async toggleWishlist(item) {
      if (!cfg.isLoggedIn) {
        this.message = "Login dulu untuk pakai wishlist.";
        return;
      }

      try {
        const data = await request("wishlist", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ product_id: item.id }),
        });
        this.wishlistIds = Array.isArray(data.items)
          ? data.items.map((id) => Number(id))
          : this.wishlistIds;
        this.message = data.active
          ? "Produk ditambahkan ke wishlist."
          : "Produk dihapus dari wishlist.";
      } catch (e) {
        this.message = e.message || "Gagal update wishlist.";
      }
    },
  });

  const vmpCart = () => ({
    ...cartHelpers,
    loading: false,
    items: [],
    total: 0,
    count: 0,
    message: "",
    cartUrl: cfg.cartUrl || "/keranjang/",
    checkoutUrl: cfg.checkoutUrl || "/checkout/",
    catalogUrl: cfg.catalogUrl || "/katalog/",
    async init() {
      await this.fetchCart();
      window.addEventListener("vmp:cart-updated", () => {
        this.fetchCart();
      });
    },
    async fetchCart() {
      this.loading = true;
      this.message = "";
      try {
        const data = await request("cart", { method: "GET" });
        this.items = Array.isArray(data.items) ? data.items : [];
        this.total = Number(data.total || 0);
        this.count = Number(data.count || 0);
      } catch (e) {
        this.items = [];
        this.total = 0;
        this.count = 0;
        this.message = e.message || "Gagal mengambil keranjang.";
      } finally {
        this.loading = false;
      }
    },
    async changeQty(item, qty) {
      if (qty < 0) return;
      try {
        await request("cart", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            id: item.id,
            qty,
            options: item.options || {},
          }),
        });
        await this.fetchCart();
      } catch (e) {
        this.message = e.message || "Gagal memperbarui qty.";
      }
    },
    async remove(item) {
      await this.changeQty(item, 0);
    },
    async clearCart() {
      try {
        await request("cart", { method: "DELETE" });
        await this.fetchCart();
      } catch (e) {
        this.message = e.message || "Gagal mengosongkan keranjang.";
      }
    },
  });

  const vmpCheckout = () => ({
    ...cartHelpers,
    loading: false,
    isLoadingProvinces: false,
    isLoadingCities: false,
    isLoadingSubdistricts: false,
    submitting: false,
    items: [],
    subtotal: 0,
    total: 0,
    provinces: [],
    cities: [],
    subdistricts: [],
    shippingGroups: [],
    shippingContextMessage: "",
    errorMessage: "",
    successMessage: "",
    cartUrl: cfg.cartUrl || "/keranjang/",
    form: {
      name: "",
      email: "",
      phone: "",
      address: "",
      postal_code: "",
      notes: "",
      payment_method: defaultPaymentMethod,
      destination_province_id: "",
      destination_province_name: "",
      destination_city_id: "",
      destination_city_name: "",
      destination_subdistrict_id: "",
      destination_subdistrict_name: "",
      shipping_cost: 0,
    },
    async init() {
      await this.fetchCart();
      await this.loadProvinces();
      await this.loadCheckoutContext();
    },
    async fetchCart() {
      this.loading = true;
      try {
        const data = await request("cart", { method: "GET" });
        this.items = Array.isArray(data.items) ? data.items : [];
        this.subtotal = Number(data.total || 0);
        this.recalculateTotal();
      } catch (e) {
        this.items = [];
        this.subtotal = 0;
        this.total = 0;
        this.errorMessage = e.message || "Gagal memuat keranjang.";
      } finally {
        this.loading = false;
      }
    },
    async loadProvinces() {
      if (this.provinces.length > 0) return;
      this.isLoadingProvinces = true;
      try {
        const rows = await fetchShippingList("shipping/provinces");
        this.provinces = rows.map(mapProvince);
      } catch (e) {
        this.shippingContextMessage = e.message || "Gagal memuat provinsi.";
      } finally {
        this.isLoadingProvinces = false;
      }
    },
    async loadCities(provinceId) {
      if (!provinceId) {
        this.cities = [];
        return;
      }
      this.isLoadingCities = true;
      try {
        const rows = await fetchShippingList(
          `shipping/cities?province=${encodeURIComponent(provinceId)}`,
        );
        this.cities = rows.map(mapCity);
      } catch (e) {
        this.cities = [];
        this.shippingContextMessage = e.message || "Gagal memuat kota.";
      } finally {
        this.isLoadingCities = false;
      }
    },
    async loadSubdistricts(cityId) {
      if (!cityId) {
        this.subdistricts = [];
        return;
      }
      this.isLoadingSubdistricts = true;
      try {
        const rows = await fetchShippingList(
          `shipping/subdistricts?city=${encodeURIComponent(cityId)}`,
        );
        this.subdistricts = rows.map(mapSubdistrict);
      } catch (e) {
        this.subdistricts = [];
        this.shippingContextMessage = e.message || "Gagal memuat kecamatan.";
      } finally {
        this.isLoadingSubdistricts = false;
      }
    },
    async loadCheckoutContext() {
      try {
        const data = await request("shipping/checkout-context", { method: "GET" });
        this.shippingGroups = Array.isArray(data.data?.groups)
          ? data.data.groups.map((group) => ({
              ...group,
              services: [],
              selectedKey: "",
              selected: null,
              loading: false,
              message: "",
            }))
          : [];
        this.shippingContextMessage = "";
      } catch (e) {
        this.shippingGroups = [];
        this.shippingContextMessage = e.message || "Context ongkir belum siap.";
      }
    },
    async onProvinceChange() {
      const selected = this.provinces.find(
        (row) => row.province_id === String(this.form.destination_province_id || ""),
      );
      this.form.destination_province_name = selected ? selected.province : "";
      this.form.destination_city_id = "";
      this.form.destination_city_name = "";
      this.form.destination_subdistrict_id = "";
      this.form.destination_subdistrict_name = "";
      this.form.shipping_cost = 0;
      this.form.postal_code = "";
      this.subdistricts = [];
      this.resetSellerShippingSelections();
      await this.loadCities(this.form.destination_province_id);
      this.recalculateTotal();
    },
    async onCityChange() {
      const selected = this.cities.find(
        (row) => row.city_id === String(this.form.destination_city_id || ""),
      );
      this.form.destination_city_name = selected
        ? `${selected.type ? `${selected.type} ` : ""}${selected.city_name}`
        : "";
      this.form.postal_code = selected ? selected.postal_code || "" : "";
      this.form.destination_subdistrict_id = "";
      this.form.destination_subdistrict_name = "";
      this.form.shipping_cost = 0;
      this.resetSellerShippingSelections();
      await this.loadSubdistricts(this.form.destination_city_id);
      this.recalculateTotal();
    },
    async onSubdistrictChange() {
      const selected = this.subdistricts.find(
        (row) => row.subdistrict_id === String(this.form.destination_subdistrict_id || ""),
      );
      this.form.destination_subdistrict_name = selected
        ? selected.subdistrict_name
        : "";
      this.form.shipping_cost = 0;
      this.resetSellerShippingSelections();
      this.recalculateTotal();
      if (this.form.destination_subdistrict_id) {
        await Promise.all(this.shippingGroups.map((group) => this.loadShippingOptions(group)));
      }
    },
    resetSellerShippingSelections() {
      this.shippingGroups = this.shippingGroups.map((group) => ({
        ...group,
        services: [],
        selectedKey: "",
        selected: null,
        loading: false,
        message: "",
      }));
    },
    async loadShippingOptions(group) {
      if (!this.form.destination_subdistrict_id || !group?.seller_id) return;
      group.loading = true;
      group.message = "";
      try {
        const data = await request("shipping/calculate", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            seller_id: group.seller_id,
            destination_subdistrict: this.form.destination_subdistrict_id,
          }),
        });
        group.services = Array.isArray(data.data?.services)
          ? data.data.services
          : [];
        if (group.services.length === 0) {
          group.message = "Tidak ada layanan pengiriman tersedia.";
        }
      } catch (e) {
        group.services = [];
        group.message = e.message || "Gagal menghitung ongkir.";
      } finally {
        group.loading = false;
      }
    },
    selectShipping(group, opt) {
      group.selectedKey = `${opt.code}:${opt.service}`;
      group.selected = {
        seller_id: Number(group.seller_id || 0),
        courier: String(opt.code || ""),
        courier_name: String(opt.name || ""),
        service: String(opt.service || ""),
        description: String(opt.description || ""),
        cost: Number(opt.cost || 0),
        etd: String(opt.etd || ""),
      };
      this.recalculateTotal();
    },
    recalculateTotal() {
      const shippingTotal = this.shippingGroups.reduce((sum, group) => {
        return sum + Number(group?.selected?.cost || 0);
      }, 0);
      this.form.shipping_cost = shippingTotal;
      this.total = Number(this.subtotal || 0) + shippingTotal;
    },
    async submitOrder() {
      this.errorMessage = "";
      this.successMessage = "";

      if (!this.form.name || !this.form.phone || !this.form.address) {
        this.errorMessage = "Nama, telepon, dan alamat wajib diisi.";
        return;
      }
      if (!Array.isArray(this.items) || this.items.length === 0) {
        this.errorMessage = "Keranjang kosong.";
        return;
      }
      if (!this.form.destination_province_id || !this.form.destination_city_id || !this.form.destination_subdistrict_id) {
        this.errorMessage = "Provinsi, kota, dan kecamatan tujuan wajib dipilih.";
        return;
      }
      if (!this.shippingGroups.length) {
        this.errorMessage = "Data pengiriman per toko belum tersedia.";
        return;
      }
      if (this.shippingGroups.some((group) => !group.selected)) {
        this.errorMessage = "Pilih layanan ongkir untuk setiap toko.";
        return;
      }

      this.submitting = true;
      try {
        const formNode = document.getElementById("vmp-checkout-form");
        const captchaFields = gatherCaptcha(formNode);
        const payload = Object.assign({}, this.form, {
          shipping_groups: this.shippingGroups.map((group) => group.selected),
        }, captchaFields);

        const data = await request("checkout", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        });

        this.successMessage = data.message || "Pesanan berhasil dibuat.";
        this.items = [];
        this.subtotal = 0;
        this.total = 0;
        this.shippingGroups = [];
        window.dispatchEvent(new CustomEvent("vmp:cart-updated"));

        if (data.redirect) {
          setTimeout(() => {
            window.location.href = data.redirect;
          }, 1200);
        }
      } catch (e) {
        this.errorMessage = e.message || "Gagal membuat pesanan.";
      } finally {
        this.submitting = false;
      }
    },
  });

  const vmpStoreProfileLocation = (initial = {}) => ({
    provinces: [],
    cities: [],
    subdistricts: [],
    isLoadingProvinces: false,
    isLoadingCities: false,
    isLoadingSubdistricts: false,
    locationMessage: "",
    form: {
      province_id: String(initial?.province_id || ""),
      province_name: String(initial?.province_name || ""),
      city_id: String(initial?.city_id || ""),
      city_name: String(initial?.city_name || ""),
      subdistrict_id: String(initial?.subdistrict_id || ""),
      subdistrict_name: String(initial?.subdistrict_name || ""),
      postcode: String(initial?.postcode || ""),
    },
    async init() {
      await this.loadProvinces();
      if (this.form.province_id) {
        await this.loadCities(this.form.province_id);
      }
      if (this.form.city_id) {
        await this.loadSubdistricts(this.form.city_id);
      }
    },
    async loadProvinces() {
      this.isLoadingProvinces = true;
      try {
        const rows = await fetchShippingList("shipping/provinces");
        this.provinces = rows.map(mapProvince);
      } catch (e) {
        this.locationMessage = e.message || "Gagal memuat provinsi.";
      } finally {
        this.isLoadingProvinces = false;
      }
    },
    async loadCities(provinceId) {
      if (!provinceId) {
        this.cities = [];
        return;
      }
      this.isLoadingCities = true;
      try {
        const rows = await fetchShippingList(
          `shipping/cities?province=${encodeURIComponent(provinceId)}`,
        );
        this.cities = rows.map(mapCity);
      } catch (e) {
        this.cities = [];
        this.locationMessage = e.message || "Gagal memuat kota/kabupaten.";
      } finally {
        this.isLoadingCities = false;
      }
    },
    async loadSubdistricts(cityId) {
      if (!cityId) {
        this.subdistricts = [];
        return;
      }
      this.isLoadingSubdistricts = true;
      try {
        const rows = await fetchShippingList(
          `shipping/subdistricts?city=${encodeURIComponent(cityId)}`,
        );
        this.subdistricts = rows.map(mapSubdistrict);
      } catch (e) {
        this.subdistricts = [];
        this.locationMessage = e.message || "Gagal memuat kecamatan.";
      } finally {
        this.isLoadingSubdistricts = false;
      }
    },
    async onProvinceChange() {
      const selected = this.provinces.find(
        (row) => row.province_id === String(this.form.province_id || ""),
      );
      this.form.province_name = selected ? selected.province : "";
      this.form.city_id = "";
      this.form.city_name = "";
      this.form.subdistrict_id = "";
      this.form.subdistrict_name = "";
      this.form.postcode = "";
      this.subdistricts = [];
      await this.loadCities(this.form.province_id);
    },
    async onCityChange() {
      const selected = this.cities.find(
        (row) => row.city_id === String(this.form.city_id || ""),
      );
      this.form.city_name = selected
        ? `${selected.type ? `${selected.type} ` : ""}${selected.city_name}`
        : "";
      this.form.postcode = selected ? selected.postal_code || "" : "";
      this.form.subdistrict_id = "";
      this.form.subdistrict_name = "";
      await this.loadSubdistricts(this.form.city_id);
    },
    onSubdistrictChange() {
      const selected = this.subdistricts.find(
        (row) => row.subdistrict_id === String(this.form.subdistrict_id || ""),
      );
      this.form.subdistrict_name = selected ? selected.subdistrict_name : "";
    },
  });

  const initActionButtons = () => {
    document.addEventListener("click", async (event) => {
      const cartButton = event.target.closest(".vmp-action-add-to-cart");
      if (cartButton) {
        event.preventDefault();

        const productId = Number(cartButton.dataset.productId || 0);
        const basic = String(cartButton.dataset.basic || "").trim();
        const advanced = String(cartButton.dataset.advanced || "").trim();
        const options = {};
        if (basic) options.basic = basic;
        if (advanced) options.advanced = advanced;

        if (productId <= 0) return;

        cartButton.disabled = true;
        try {
          await request("cart", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              id: productId,
              qty: 1,
              options,
            }),
          });
          flashButtonLabel(cartButton, "Ditambahkan");
          window.dispatchEvent(new CustomEvent("vmp:cart-updated"));
        } catch (e) {
          flashButtonLabel(cartButton, "Gagal");
        } finally {
          window.setTimeout(() => {
            cartButton.disabled = false;
          }, 500);
        }
        return;
      }

      const wishlistButton = event.target.closest(".vmp-action-toggle-wishlist");
      if (wishlistButton) {
        event.preventDefault();

        if (!cfg.isLoggedIn) {
          flashButtonLabel(wishlistButton, "Login dulu");
          return;
        }

        const productId = Number(wishlistButton.dataset.productId || 0);
        if (productId <= 0) return;

        wishlistButton.disabled = true;
        try {
          const data = await request("wishlist", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ product_id: productId }),
          });
          const active = !!data.active;
          wishlistButton.classList.toggle("btn-danger", active);
          wishlistButton.classList.toggle("btn-outline-secondary", !active);
          wishlistButton.setAttribute("aria-pressed", active ? "true" : "false");
          flashButtonLabel(wishlistButton, active ? "Tersimpan" : "Dihapus");
        } catch (e) {
          flashButtonLabel(wishlistButton, "Gagal");
        } finally {
          window.setTimeout(() => {
            wishlistButton.disabled = false;
          }, 500);
        }
      }
    });
  };

  const initProductGallery = () => {
    const galleries = document.querySelectorAll(".vmp-product-gallery");
    if (!galleries.length) return;

    let lightbox = null;
    let lightboxImage = null;
    let lightboxCounter = null;
    let activeGallery = null;
    let activeIndex = 0;

    const ensureLightbox = () => {
      if (lightbox) return;

      const wrapper = document.createElement("div");
      wrapper.className = "vmp-lightbox";
      wrapper.innerHTML = `
        <div class="vmp-lightbox__dialog" role="dialog" aria-modal="true" aria-label="Galeri Produk">
          <button type="button" class="vmp-lightbox__close" data-lightbox-close aria-label="Tutup">&times;</button>
          <button type="button" class="vmp-lightbox__nav vmp-lightbox__nav--prev" data-lightbox-prev aria-label="Gambar sebelumnya">&#8249;</button>
          <div class="vmp-lightbox__viewport">
            <img src="" alt="" class="vmp-lightbox__image" data-lightbox-image>
          </div>
          <button type="button" class="vmp-lightbox__nav vmp-lightbox__nav--next" data-lightbox-next aria-label="Gambar berikutnya">&#8250;</button>
          <div class="vmp-lightbox__counter" data-lightbox-counter></div>
        </div>
      `;

      document.body.appendChild(wrapper);
      lightbox = wrapper;
      lightboxImage = wrapper.querySelector("[data-lightbox-image]");
      lightboxCounter = wrapper.querySelector("[data-lightbox-counter]");

      wrapper.addEventListener("click", (event) => {
        if (event.target === wrapper || event.target.closest("[data-lightbox-close]")) {
          closeLightbox();
          return;
        }
        if (event.target.closest("[data-lightbox-prev]")) {
          event.preventDefault();
          stepLightbox(-1);
          return;
        }
        if (event.target.closest("[data-lightbox-next]")) {
          event.preventDefault();
          stepLightbox(1);
        }
      });

      document.addEventListener("keydown", (event) => {
        if (!lightbox || !lightbox.classList.contains("is-open")) return;

        if (event.key === "Escape") {
          closeLightbox();
        } else if (event.key === "ArrowLeft") {
          stepLightbox(-1);
        } else if (event.key === "ArrowRight") {
          stepLightbox(1);
        }
      });
    };

    const getImages = (gallery) =>
      Array.from(gallery.querySelectorAll("[data-gallery-link]")).map((link) => ({
        href: String(link.getAttribute("href") || ""),
        title: String(link.textContent || "").trim(),
      }));

    const renderActiveThumb = (gallery, index) => {
      gallery.querySelectorAll("[data-gallery-thumb]").forEach((button) => {
        const isActive = Number(button.dataset.index || 0) === index;
        button.classList.toggle("is-active", isActive);
        if (isActive) {
          button.scrollIntoView({
            behavior: "smooth",
            block: "nearest",
            inline: "center",
          });
        }
      });
    };

    const setGalleryImage = (gallery, index) => {
      const images = getImages(gallery);
      if (!images.length) return;

      const normalizedIndex = Math.max(0, Math.min(index, images.length - 1));
      const main = gallery.querySelector("[data-gallery-main]");
      if (main) {
        main.src = images[normalizedIndex].href;
        main.alt =
          images[normalizedIndex].title ||
          gallery.dataset.galleryTitle ||
          "Gallery image";
      }

      gallery.dataset.activeIndex = String(normalizedIndex);
      renderActiveThumb(gallery, normalizedIndex);
    };

    const updateThumbNav = (gallery) => {
      const track = gallery.querySelector("[data-gallery-track]");
      const prev = gallery.querySelector("[data-gallery-prev]");
      const next = gallery.querySelector("[data-gallery-next]");
      if (!track || !prev || !next) return;

      const maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
      prev.disabled = track.scrollLeft <= 4;
      next.disabled = track.scrollLeft >= maxScroll - 4;
    };

    const openLightbox = (gallery, index) => {
      ensureLightbox();
      activeGallery = gallery;
      activeIndex = index;
      syncLightbox();
      lightbox.classList.add("is-open");
      document.body.style.overflow = "hidden";
    };

    const closeLightbox = () => {
      if (!lightbox) return;
      lightbox.classList.remove("is-open");
      document.body.style.overflow = "";
    };

    const syncLightbox = () => {
      if (!activeGallery || !lightboxImage) return;

      const images = getImages(activeGallery);
      if (!images.length) return;

      if (activeIndex < 0) activeIndex = images.length - 1;
      if (activeIndex >= images.length) activeIndex = 0;

      const current = images[activeIndex];
      lightboxImage.src = current.href;
      lightboxImage.alt =
        current.title || activeGallery.dataset.galleryTitle || "Gallery image";
      if (lightboxCounter) {
        lightboxCounter.textContent = `${activeIndex + 1} / ${images.length}`;
      }
      setGalleryImage(activeGallery, activeIndex);
    };

    const stepLightbox = (step) => {
      if (!activeGallery) return;
      activeIndex += step;
      syncLightbox();
    };

    galleries.forEach((gallery) => {
      const thumbs = gallery.querySelectorAll("[data-gallery-thumb]");
      const stage = gallery.querySelector("[data-gallery-open]");
      const track = gallery.querySelector("[data-gallery-track]");
      const prev = gallery.querySelector("[data-gallery-prev]");
      const next = gallery.querySelector("[data-gallery-next]");

      gallery.dataset.activeIndex = gallery.dataset.activeIndex || "0";

      thumbs.forEach((button) => {
        button.addEventListener("click", (event) => {
          event.preventDefault();
          const nextIndex = Number(button.dataset.index || 0);
          setGalleryImage(gallery, nextIndex);
        });
      });

      if (stage) {
        stage.addEventListener("click", (event) => {
          event.preventDefault();
          openLightbox(gallery, Number(gallery.dataset.activeIndex || 0));
        });
      }

      if (track && prev && next) {
        const moveThumbs = (direction) => {
          const amount = Math.max(180, Math.floor(track.clientWidth * 0.7));
          track.scrollBy({
            left: direction * amount,
            behavior: "smooth",
          });
        };

        prev.addEventListener("click", (event) => {
          event.preventDefault();
          moveThumbs(-1);
        });

        next.addEventListener("click", (event) => {
          event.preventDefault();
          moveThumbs(1);
        });

        track.addEventListener("scroll", () => updateThumbNav(gallery), {
          passive: true,
        });
        window.addEventListener("resize", () => updateThumbNav(gallery));
        updateThumbNav(gallery);
      }

      setGalleryImage(gallery, Number(gallery.dataset.activeIndex || 0));
    });
  };

  window.vmpCatalog = vmpCatalog;
  window.vmpCart = vmpCart;
  window.vmpCheckout = vmpCheckout;
  window.vmpStoreProfileLocation = vmpStoreProfileLocation;

  document.addEventListener("alpine:init", () => {
    Alpine.data("vmpCatalog", vmpCatalog);
    Alpine.data("vmpCart", vmpCart);
    Alpine.data("vmpCheckout", vmpCheckout);
    Alpine.data("vmpStoreProfileLocation", vmpStoreProfileLocation);
  });

  document.addEventListener("DOMContentLoaded", () => {
    initActionButtons();
    initProductGallery();
  });
})();
