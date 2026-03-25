    <?php
    $store_phone = (string) get_user_meta($current_user_id, 'vmp_phone', true);
    $store_whatsapp = (string) get_user_meta($current_user_id, 'vmp_whatsapp', true);
    $store_description = (string) get_user_meta($current_user_id, 'vmp_description', true);
    $store_subdistrict = (string) get_user_meta($current_user_id, 'vmp_subdistrict', true);
    $store_city = (string) get_user_meta($current_user_id, 'vmp_city', true);
    $store_province = (string) get_user_meta($current_user_id, 'vmp_province', true);
    $store_subdistrict_id = (string) get_user_meta($current_user_id, 'vmp_subdistrict_id', true);
    $store_city_id = (string) get_user_meta($current_user_id, 'vmp_city_id', true);
    $store_province_id = (string) get_user_meta($current_user_id, 'vmp_province_id', true);
    $store_postcode = (string) get_user_meta($current_user_id, 'vmp_postcode', true);
    $store_couriers = get_user_meta($current_user_id, 'vmp_couriers', true);
    if (!is_array($store_couriers)) {
        $store_couriers = [];
    }
    $avatar_id = (int) get_user_meta($current_user_id, 'vmp_avatar_id', true);
    $avatar_url = $avatar_id > 0 ? wp_get_attachment_image_url($avatar_id, 'thumbnail') : '';
    $courier_options = [
        'jne' => 'JNE',
        'pos' => 'POS Indonesia',
        'tiki' => 'TIKI',
        'sicepat' => 'SiCepat',
        'jnt' => 'J&T',
        'ninja' => 'Ninja Xpress',
        'wahana' => 'Wahana',
        'lion' => 'Lion Parcel',
    ];
    $location_state = [
        'province_id' => $store_province_id,
        'province_name' => $store_province,
        'city_id' => $store_city_id,
        'city_name' => $store_city,
        'subdistrict_id' => $store_subdistrict_id,
        'subdistrict_name' => $store_subdistrict,
        'postcode' => $store_postcode,
    ];
    ?>
    <div class="card border-0 shadow-sm" x-data='vmpStoreProfileLocation(<?php echo wp_json_encode($location_state); ?>)' x-init="init()"><div class="card-body">
        <h3 class="h6 mb-3">Pengaturan Toko</h3>
        <p class="text-muted small mb-3">Data toko memakai profil akun yang sama. Bagian khusus toko di halaman ini hanya pengaturan tampilan toko dan kurir.</p>
        <form method="post" class="row g-3">
            <input type="hidden" name="vmp_action" value="save_store_profile">
            <input type="hidden" name="tab" value="seller_profile">
            <?php wp_nonce_field('vmp_store_profile', 'vmp_store_profile_nonce'); ?>
            <div class="col-md-6"><label class="form-label">Nama Toko</label><input type="text" name="store_name" class="form-control" value="<?php echo esc_attr($store_name); ?>" required></div>
            <div class="col-md-6">
                <label class="form-label">Foto Profil Toko</label>
                <div class="vmp-media-field" data-multiple="0">
                    <input type="hidden" name="store_avatar_id" class="vmp-media-field__input" value="<?php echo esc_attr((string) $avatar_id); ?>">
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <button type="button" class="btn btn-outline-dark btn-sm vmp-media-field__open" data-title="Foto Profil Toko" data-button="Gunakan foto ini">Pilih dari Media Library</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm vmp-media-field__clear" <?php disabled($avatar_id <= 0); ?>>Hapus Foto</button>
                    </div>
                    <div class="vmp-media-field__preview" data-placeholder="Belum ada foto profil toko.">
                        <?php if ($avatar_url) : ?>
                            <div class="vmp-media-field__grid vmp-media-field__grid--single">
                                <div class="vmp-media-field__item" data-id="<?php echo esc_attr((string) $avatar_id); ?>">
                                    <img src="<?php echo esc_url($avatar_url); ?>" alt="Avatar toko" class="vmp-media-field__image">
                                    <button type="button" class="btn-close vmp-media-field__remove" aria-label="Hapus foto"></button>
                                </div>
                            </div>
                        <?php else : ?>
                            <div class="vmp-media-field__empty text-muted small">Belum ada foto dipilih.</div>
                        <?php endif; ?>
                    </div>
                    <div class="form-text">Pilih foto profil toko langsung dari media library WordPress.</div>
                </div>
            </div>
            <div class="col-md-6"><label class="form-label">Kontak Telepon</label><input type="text" name="store_phone" class="form-control" value="<?php echo esc_attr($store_phone); ?>"></div>
            <div class="col-md-6"><label class="form-label">WhatsApp</label><input type="text" name="store_whatsapp" class="form-control" value="<?php echo esc_attr($store_whatsapp); ?>"></div>
            <div class="col-12"><label class="form-label">Alamat Toko</label><textarea name="store_address" class="form-control" rows="3" required><?php echo esc_textarea($store_address); ?></textarea></div>
            <div class="col-md-4">
                <label class="form-label">Provinsi</label>
                <select name="store_province_id" class="form-select" x-ref="provinceSelect" x-model="form.province_id" @change="onProvinceChange()" :disabled="isLoadingProvinces">
                    <option value="">- Pilih Provinsi -</option>
                    <template x-for="prov in provinces" :key="prov.province_id">
                        <option :value="prov.province_id" x-text="prov.province"></option>
                    </template>
                </select>
                <input type="hidden" name="store_province_name" :value="form.province_name">
            </div>
            <div class="col-md-4">
                <label class="form-label">Kota/Kabupaten</label>
                <select name="store_city_id" class="form-select" x-ref="citySelect" x-model="form.city_id" @change="onCityChange()" :disabled="!form.province_id || isLoadingCities">
                    <option value="">- Pilih Kota/Kabupaten -</option>
                    <template x-for="city in cities" :key="city.city_id">
                        <option :value="city.city_id" x-text="(city.type ? city.type + ' ' : '') + city.city_name"></option>
                    </template>
                </select>
                <input type="hidden" name="store_city_name" :value="form.city_name">
            </div>
            <div class="col-md-4">
                <label class="form-label">Kecamatan</label>
                <select name="store_subdistrict_id" class="form-select" x-ref="subdistrictSelect" x-model="form.subdistrict_id" @change="onSubdistrictChange()" :disabled="!form.city_id || isLoadingSubdistricts">
                    <option value="">- Pilih Kecamatan -</option>
                    <template x-for="subdistrict in subdistricts" :key="subdistrict.subdistrict_id">
                        <option :value="subdistrict.subdistrict_id" x-text="subdistrict.subdistrict_name"></option>
                    </template>
                </select>
                <input type="hidden" name="store_subdistrict_name" :value="form.subdistrict_name">
            </div>
            <div class="col-md-3">
                <label class="form-label">Kode Pos</label>
                <input type="text" name="store_postcode" class="form-control" x-model="form.postcode">
            </div>
            <div class="col-12"><label class="form-label">Deskripsi Toko</label><textarea name="store_description" class="form-control" rows="3"><?php echo esc_textarea($store_description); ?></textarea></div>
            <div class="col-12">
                <div class="small text-muted" x-show="locationMessage" x-text="locationMessage"></div>
            </div>
            <div class="col-12"><label class="form-label">Kurir yang Digunakan</label><div class="d-flex flex-wrap gap-3"><?php foreach ($courier_options as $key => $label) : ?><label class="form-check-label"><input class="form-check-input me-1" type="checkbox" name="store_couriers[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $store_couriers, true)); ?>><?php echo esc_html($label); ?></label><?php endforeach; ?></div></div>
            <div class="col-12 d-flex justify-content-between align-items-center"><div><?php if ($is_star_seller) : ?><span class="badge bg-warning text-dark">Star Seller Aktif</span><?php else : ?><span class="badge bg-secondary">Star Seller Belum Aktif</span><?php endif; ?></div><button type="submit" class="btn btn-dark">Simpan Profil Toko</button></div>
        </form>
    </div></div>
