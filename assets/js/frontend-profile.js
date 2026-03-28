/* Komponen profil member dan profil toko berbasis lokasi wilayah. */
(() => {
  const shared = window.VMPFrontend;
  if (!shared) {
    return;
  }

  const {
    request,
    fetchShippingList,
    formDataToObject,
    mapProvince,
    mapCity,
    mapSubdistrict,
  } = shared;

  // Membuat state lokasi bersama yang dipakai form profil member dan toko.
  const createProfileLocationState = (initial = {}) => ({
    provinces: [],
    cities: [],
    subdistricts: [],
    isLoadingProvinces: false,
    isLoadingCities: false,
    isLoadingSubdistricts: false,
    locationMessage: '',
    form: {
      province_id: String(initial?.province_id || ''),
      province_name: String(initial?.province_name || ''),
      city_id: String(initial?.city_id || ''),
      city_name: String(initial?.city_name || ''),
      subdistrict_id: String(initial?.subdistrict_id || ''),
      subdistrict_name: String(initial?.subdistrict_name || ''),
      postcode: String(initial?.postcode || ''),
      cod_enabled: !!initial?.cod_enabled,
      cod_city_ids: Array.isArray(initial?.cod_city_ids)
        ? initial.cod_city_ids.map((id) => String(id || ''))
        : [],
    },
    // Memuat daftar wilayah dan menyinkronkan nilai tersimpan ke dropdown.
    async init() {
      await this.loadProvinces();
      this.syncProvinceSelection();
      if (this.form.province_id) {
        await this.loadCities(this.form.province_id);
        this.syncCitySelection();
      }
      if (this.form.city_id) {
        await this.loadSubdistricts(this.form.city_id);
        this.syncSubdistrictSelection();
      }
    },
    // Memaksa nilai select tunggal setelah option selesai dirender.
    applySelectValue(refName, value) {
      this.$nextTick(() => {
        const field = this.$refs && this.$refs[refName] ? this.$refs[refName] : null;
        if (!field) return;
        field.value = String(value || '');
      });
    },
    // Memaksa nilai multi-select tetap sinkron dengan state Alpine.
    applyMultiSelectValue(refName, values) {
      this.$nextTick(() => {
        const field = this.$refs && this.$refs[refName] ? this.$refs[refName] : null;
        if (!field || !field.options) return;

        const selected = Array.isArray(values)
          ? values.map((value) => String(value || ''))
          : [];

        Array.from(field.options).forEach((option) => {
          option.selected = selected.includes(String(option.value || ''));
        });
      });
    },
    // Menyelaraskan provinsi tersimpan dengan data provinsi hasil API.
    syncProvinceSelection() {
      if (!Array.isArray(this.provinces) || this.provinces.length === 0) return;

      const selected = this.provinces.find(
        (row) => row.province_id === String(this.form.province_id || ''),
      );

      this.form.province_id = selected ? selected.province_id : '';
      this.form.province_name = selected ? selected.province : '';
      this.applySelectValue('provinceSelect', this.form.province_id);

      if (!selected) {
        this.form.city_id = '';
        this.form.city_name = '';
        this.form.subdistrict_id = '';
        this.form.subdistrict_name = '';
        this.applySelectValue('citySelect', '');
        this.applySelectValue('subdistrictSelect', '');
      }
    },
    // Menyelaraskan kota tersimpan dan kode pos dengan data hasil API.
    syncCitySelection() {
      if (!Array.isArray(this.cities) || this.cities.length === 0) return;

      const selected = this.cities.find(
        (row) => row.city_id === String(this.form.city_id || ''),
      );

      this.form.city_id = selected ? selected.city_id : '';
      this.form.city_name = selected
        ? `${selected.type ? `${selected.type} ` : ''}${selected.city_name}`
        : '';
      this.form.postcode = selected
        ? selected.postal_code || this.form.postcode
        : this.form.postcode;
      this.applySelectValue('citySelect', this.form.city_id);
      this.applyMultiSelectValue('codCitySelect', this.form.cod_city_ids);

      if (!selected) {
        this.form.subdistrict_id = '';
        this.form.subdistrict_name = '';
        this.applySelectValue('subdistrictSelect', '');
      }
    },
    // Menyelaraskan kecamatan tersimpan dengan data hasil API.
    syncSubdistrictSelection() {
      if (!Array.isArray(this.subdistricts) || this.subdistricts.length === 0) return;

      const selected = this.subdistricts.find(
        (row) => row.subdistrict_id === String(this.form.subdistrict_id || ''),
      );

      this.form.subdistrict_id = selected ? selected.subdistrict_id : '';
      this.form.subdistrict_name = selected ? selected.subdistrict_name : '';
      this.applySelectValue('subdistrictSelect', this.form.subdistrict_id);
    },
    // Mengambil daftar provinsi untuk kebutuhan form profil.
    async loadProvinces() {
      this.isLoadingProvinces = true;
      try {
        const rows = await fetchShippingList('shipping/provinces');
        this.provinces = rows.map(mapProvince);
        this.syncProvinceSelection();
      } catch (e) {
        this.locationMessage = e.message || 'Gagal memuat provinsi.';
      } finally {
        this.isLoadingProvinces = false;
      }
    },
    // Mengambil kota/kabupaten berdasarkan provinsi aktif.
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
        this.syncCitySelection();
      } catch (e) {
        this.cities = [];
        this.locationMessage = e.message || 'Gagal memuat kota/kabupaten.';
      } finally {
        this.isLoadingCities = false;
      }
    },
    // Mengambil kecamatan berdasarkan kota/kabupaten aktif.
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
        this.syncSubdistrictSelection();
      } catch (e) {
        this.subdistricts = [];
        this.locationMessage = e.message || 'Gagal memuat kecamatan.';
      } finally {
        this.isLoadingSubdistricts = false;
      }
    },
    // Mereset turunan lokasi saat provinsi diganti user.
    async onProvinceChange() {
      const selected = this.provinces.find(
        (row) => row.province_id === String(this.form.province_id || ''),
      );

      this.form.province_name = selected ? selected.province : '';
      this.form.city_id = '';
      this.form.city_name = '';
      this.form.subdistrict_id = '';
      this.form.subdistrict_name = '';
      this.form.postcode = '';
      this.form.cod_city_ids = [];
      this.subdistricts = [];
      await this.loadCities(this.form.province_id);
    },
    // Memperbarui kota aktif dan memuat kecamatan turunan.
    async onCityChange() {
      const selected = this.cities.find(
        (row) => row.city_id === String(this.form.city_id || ''),
      );

      this.form.city_name = selected
        ? `${selected.type ? `${selected.type} ` : ''}${selected.city_name}`
        : '';
      this.form.postcode = selected ? selected.postal_code || '' : '';
      this.form.subdistrict_id = '';
      this.form.subdistrict_name = '';
      await this.loadSubdistricts(this.form.city_id);
    },
    // Menyimpan nama kecamatan aktif ke state untuk payload form.
    onSubdistrictChange() {
      const selected = this.subdistricts.find(
        (row) => row.subdistrict_id === String(this.form.subdistrict_id || ''),
      );
      this.form.subdistrict_name = selected ? selected.subdistrict_name : '';
    },
    // Menyinkronkan kota COD terpilih saat multi-select berubah.
    syncCodCities() {
      this.form.cod_city_ids = Array.isArray(this.form.cod_city_ids)
        ? this.form.cod_city_ids.map((id) => String(id || ''))
        : [];
      this.applyMultiSelectValue('codCitySelect', this.form.cod_city_ids);
    },
    // Mengambil label kota COD dari daftar kota yang sudah dimuat.
    codCityName(cityId) {
      const selected = this.cities.find(
        (row) => row.city_id === String(cityId || ''),
      );
      return selected
        ? `${selected.type ? `${selected.type} ` : ''}${selected.city_name}`
        : '';
    },
  });

  // Menyediakan state lokasi mentah untuk kebutuhan template lain yang hanya butuh dropdown wilayah.
  const vmpStoreProfileLocation = (initial = {}) => createProfileLocationState(initial);

  // Komponen profil member yang menyimpan data akun dan alamat default checkout.
  const vmpMemberProfileForm = (initial = {}) => {
    const location = createProfileLocationState(initial);

    return {
      ...location,
      saving: false,
      saveMessage: '',
      saveError: '',
      // Memuat state lokasi awal untuk form profil member.
      async init() {
        await location.init.call(this);
      },
      // Mengirim perubahan profil member ke endpoint REST profil.
      async submit(event) {
        const formNode = event && event.target ? event.target : null;
        if (!formNode) {
          return;
        }

        this.saving = true;
        this.saveMessage = '';
        this.saveError = '';

        try {
          const raw = formDataToObject(formNode);
          const payload = {
            name: String(raw.customer_name || '').trim(),
            phone: String(raw.customer_phone || '').trim(),
            address: String(raw.customer_address || '').trim(),
            province_id: String(raw.customer_province_id || '').trim(),
            province_name: String(raw.customer_province_name || '').trim(),
            city_id: String(raw.customer_city_id || '').trim(),
            city_name: String(raw.customer_city_name || '').trim(),
            subdistrict_id: String(raw.customer_subdistrict_id || '').trim(),
            subdistrict_name: String(raw.customer_subdistrict_name || '').trim(),
            postcode: String(raw.customer_postcode || '').trim(),
          };

          const data = await request('profile/member', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
          });

          this.saveMessage = data.message || 'Profil member berhasil diperbarui.';
        } catch (e) {
          this.saveError = e.message || 'Profil member tidak dapat disimpan.';
        } finally {
          this.saving = false;
        }
      },
    };
  };

  // Komponen profil toko yang menyimpan alamat asal, kurir, dan COD.
  const vmpStoreProfileForm = (initial = {}) => {
    const location = createProfileLocationState(initial);

    return {
      ...location,
      saving: false,
      saveMessage: '',
      saveError: '',
      // Memuat state lokasi awal untuk form profil toko.
      async init() {
        await location.init.call(this);
      },
      // Mengirim perubahan profil toko ke endpoint REST profil.
      async submit(event) {
        const formNode = event && event.target ? event.target : null;
        if (!formNode) {
          return;
        }

        this.saving = true;
        this.saveMessage = '';
        this.saveError = '';

        try {
          const raw = formDataToObject(formNode);
          const payload = {
            name: String(raw.store_name || '').trim(),
            phone: String(raw.store_phone || '').trim(),
            whatsapp: String(raw.store_whatsapp || '').trim(),
            address: String(raw.store_address || '').trim(),
            province_id: String(raw.store_province_id || '').trim(),
            province_name: String(raw.store_province_name || '').trim(),
            city_id: String(raw.store_city_id || '').trim(),
            city_name: String(raw.store_city_name || '').trim(),
            subdistrict_id: String(raw.store_subdistrict_id || '').trim(),
            subdistrict_name: String(raw.store_subdistrict_name || '').trim(),
            postcode: String(raw.store_postcode || '').trim(),
            description: String(raw.store_description || '').trim(),
            avatar_id: String(raw.store_avatar_id || '').trim(),
            couriers: Array.isArray(raw.store_couriers)
              ? raw.store_couriers
              : raw.store_couriers
                ? [raw.store_couriers]
                : [],
            cod_enabled: !!raw.store_cod_enabled,
            cod_city_ids: Array.isArray(raw.store_cod_city_ids)
              ? raw.store_cod_city_ids
              : raw.store_cod_city_ids
                ? [raw.store_cod_city_ids]
                : [],
            cod_city_names: Array.isArray(raw.store_cod_city_names)
              ? raw.store_cod_city_names
              : raw.store_cod_city_names
                ? [raw.store_cod_city_names]
                : [],
          };

          const data = await request('profile/store', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
          });

          this.saveMessage = data.message || 'Profil toko berhasil diperbarui.';
        } catch (e) {
          this.saveError = e.message || 'Profil toko tidak dapat disimpan.';
        } finally {
          this.saving = false;
        }
      },
    };
  };

  window.vmpStoreProfileLocation = vmpStoreProfileLocation;
  window.vmpMemberProfileForm = vmpMemberProfileForm;
  window.vmpStoreProfileForm = vmpStoreProfileForm;

  document.addEventListener('alpine:init', () => {
    Alpine.data('vmpStoreProfileLocation', vmpStoreProfileLocation);
    Alpine.data('vmpMemberProfileForm', vmpMemberProfileForm);
    Alpine.data('vmpStoreProfileForm', vmpStoreProfileForm);
  });
})();
