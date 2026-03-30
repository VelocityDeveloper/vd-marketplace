/* Shared helper untuk request, format data, dan util frontend. */
(() => {
  const cfg = window.vmpSettings || {};
  const currencyCode =
    cfg.currency && String(cfg.currency).toUpperCase() === 'USD' ? 'USD' : 'IDR';
  const currencySymbol =
    typeof cfg.currencySymbol === 'string' && cfg.currencySymbol.trim() !== ''
      ? cfg.currencySymbol.trim()
      : currencyCode === 'USD'
        ? '$'
        : 'Rp';
  const paymentMethods = Array.isArray(cfg.paymentMethods)
    ? cfg.paymentMethods
    : ['bank'];
  const customerProfile =
    cfg.customerProfile && typeof cfg.customerProfile === 'object'
      ? cfg.customerProfile
      : {};
  const placeholder =
    typeof cfg.noImageUrl === 'string' && cfg.noImageUrl.trim() !== ''
      ? cfg.noImageUrl.trim()
      : "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0nNDAwJyBoZWlnaHQ9JzMwMCcgdmlld0JveD0nMCAwIDQwMCAzMDAnIHhtbG5zPSdodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2Zyc+PHJlY3Qgd2lkdGg9JzQwMCcgaGVpZ2h0PSczMDAnIGZpbGw9JyNlZWVmZjEnLz48dGV4dCB4PSc1MCUnIHk9JzUwJScgZG9taW5hbnQtYmFzZWxpbmU9J21pZGRsZScgdGV4dC1hbmNob3I9J21pZGRsZScgZmlsbD0nIzYwNzA4MCcgZm9udC1mYW1pbHk9J3NhbnMtc2VyaWYnIGZvbnQtc2l6ZT0nMTYnPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==";

  // Menyusun URL endpoint REST dari base URL yang dilocalize ke frontend.
  const api = (path) => `${cfg.restUrl || ''}${path || ''}`;

  // Menjalankan request REST dengan nonce WordPress dan parsing error standar.
  const request = async (path, options = {}) => {
    const opt = Object.assign(
      {
        credentials: 'same-origin',
        headers: {},
      },
      options,
    );

    if (cfg.nonce) {
      opt.headers['X-WP-Nonce'] = cfg.nonce;
    }

    const res = await fetch(api(path), opt);
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      throw new Error(data.message || 'Permintaan tidak dapat diproses.');
    }

    return data;
  };

  // Menampilkan label status singkat pada tombol lalu mengembalikannya lagi.
  const flashButtonLabel = (button, nextLabel) => {
    if (!button) return;

    const original =
      button.dataset.defaultLabel || button.textContent.trim() || 'Proses';
    button.dataset.defaultLabel = original;
    button.textContent = nextLabel;

    window.setTimeout(() => {
      button.textContent = button.dataset.defaultLabel || original;
    }, 1400);
  };

  // Menghasilkan ikon wishlist Bootstrap sesuai status aktif tombol.
  const wishlistIconSvg = (active = false) =>
    active
      ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-heart-fill" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 1.314C12.438-3.248 23.534 4.735 8 15C-7.534 4.736 3.562-3.248 8 1.314"/></svg>'
      : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-heart" viewBox="0 0 16 16"><path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143c.06.055.119.112.176.171a3 3 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15"/></svg>';

  // Memformat angka ke mata uang aktif marketplace.
  const money = (value) => {
    const num = Number(value || 0);
    try {
      return new Intl.NumberFormat(currencyCode === 'USD' ? 'en-US' : 'id-ID', {
        style: 'currency',
        currency: currencyCode,
        minimumFractionDigits: 0,
      }).format(num);
    } catch (e) {
      return `${currencySymbol} ${num.toLocaleString('id-ID')}`;
    }
  };

  // Membuat kunci stabil dari kombinasi opsi cart item.
  const optionKey = (item) => {
    try {
      return JSON.stringify(item && item.options ? item.options : {});
    } catch (e) {
      return '';
    }
  };

  // Menormalkan payload provinsi dari API wilayah.
  const mapProvince = (row) => ({
    province_id: String((row && row.province_id) || ''),
    province: String((row && row.province) || ''),
  });

  // Menormalkan payload kota/kabupaten dari API wilayah.
  const mapCity = (row) => ({
    city_id: String((row && row.city_id) || ''),
    city_name: String((row && row.city_name) || ''),
    type: String((row && row.type) || ''),
    province: String((row && row.province) || ''),
    postal_code: String((row && row.postal_code) || ''),
  });

  // Menormalkan payload kecamatan dari API wilayah.
  const mapSubdistrict = (row) => ({
    subdistrict_id: String((row && row.subdistrict_id) || ''),
    subdistrict_name: String((row && row.subdistrict_name) || ''),
  });

  // Mengambil daftar wilayah pengiriman dan memastikan hasilnya selalu array.
  const fetchShippingList = async (path) => {
    const data = await request(path, { method: 'GET' });
    return Array.isArray(data.data) ? data.data : [];
  };

  // Mengumpulkan field captcha dari form jika captcha aktif.
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
      out['g-recaptcha-response'] = grecaptchaInput.value;
      out.g_recaptcha_response = grecaptchaInput.value;
    }

    return out;
  };

  // Mengubah FormData ke object biasa agar mudah dipakai payload REST.
  const formDataToObject = (formNode) => {
    const payload = {};
    if (!formNode) {
      return payload;
    }

    const formData = new FormData(formNode);
    for (const [rawKey, value] of formData.entries()) {
      const key = rawKey.endsWith('[]') ? rawKey.slice(0, -2) : rawKey;
      if (rawKey.endsWith('[]')) {
        if (!Array.isArray(payload[key])) {
          payload[key] = [];
        }
        payload[key].push(value);
        continue;
      }

      if (Object.prototype.hasOwnProperty.call(payload, key)) {
        if (!Array.isArray(payload[key])) {
          payload[key] = [payload[key]];
        }
        payload[key].push(value);
        continue;
      }

      payload[key] = value;
    }

    return payload;
  };

  const defaultPaymentMethod =
    paymentMethods.length > 0 && typeof paymentMethods[0] === 'string'
      ? paymentMethods[0]
      : 'bank';

  // Helper tampilan yang dipakai bersama oleh komponen cart dan checkout.
  const cartHelpers = {
    money,
    placeholder,
    optionKey,
    // Merangkum opsi produk agar mudah ditampilkan di cart dan checkout.
    optionText(options) {
      if (!options || typeof options !== 'object') return '';
      const lines = [];
      Object.entries(options).forEach(([key, value]) => {
        if (key === 'variant' || key === 'price_adjustment') return;
        if (value === null || value === undefined) return;

        const label = String(key || '').trim();
        const text = String(value || '').trim();
        if (!label || !text) return;

        lines.push(`${label}: ${text}`);
      });
      return lines.join(' | ');
    },
    // Menyediakan alias format harga agar template Alpine lebih ringkas.
    formatPrice(value) {
      return money(value);
    },
  };

  window.VMPFrontend = {
    cfg,
    currencyCode,
    currencySymbol,
    paymentMethods,
    customerProfile,
    placeholder,
    api,
    request,
    flashButtonLabel,
    wishlistIconSvg,
    money,
    optionKey,
    mapProvince,
    mapCity,
    mapSubdistrict,
    fetchShippingList,
    gatherCaptcha,
    formDataToObject,
    defaultPaymentMethod,
    cartHelpers,
  };
})();
