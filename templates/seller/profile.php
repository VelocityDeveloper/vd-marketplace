    <?php
    $store_name = (string) get_user_meta($current_user_id, 'vmp_store_name', true);
    $store_phone = (string) get_user_meta($current_user_id, 'vmp_store_phone', true);
    $store_whatsapp = (string) get_user_meta($current_user_id, 'vmp_store_whatsapp', true);
    $store_address = (string) get_user_meta($current_user_id, 'vmp_store_address', true);
    $store_description = (string) get_user_meta($current_user_id, 'vmp_store_description', true);
    $store_subdistrict = (string) get_user_meta($current_user_id, 'vmp_store_subdistrict', true);
    $store_city = (string) get_user_meta($current_user_id, 'vmp_store_city', true);
    $store_province = (string) get_user_meta($current_user_id, 'vmp_store_province', true);
    $store_subdistrict_id = (string) get_user_meta($current_user_id, 'vmp_store_subdistrict_id', true);
    $store_city_id = (string) get_user_meta($current_user_id, 'vmp_store_city_id', true);
    $store_province_id = (string) get_user_meta($current_user_id, 'vmp_store_province_id', true);
    $store_postcode = (string) get_user_meta($current_user_id, 'vmp_store_postcode', true);
    $store_couriers = get_user_meta($current_user_id, 'vmp_couriers', true);
    if (!is_array($store_couriers)) {
        $store_couriers = [];
    }
    $cod_enabled = !empty(get_user_meta($current_user_id, 'vmp_cod_enabled', true));
    $cod_city_ids = get_user_meta($current_user_id, 'vmp_cod_city_ids', true);
    $cod_city_names = get_user_meta($current_user_id, 'vmp_cod_city_names', true);
    if (!is_array($cod_city_ids)) {
        $cod_city_ids = [];
    }
    if (!is_array($cod_city_names)) {
        $cod_city_names = [];
    }
    $avatar_id = (int) get_user_meta($current_user_id, 'vmp_store_avatar_id', true);
    $avatar_url = $avatar_id > 0 ? wp_get_attachment_image_url($avatar_id, 'thumbnail') : '';
    $courier_options = \VelocityMarketplace\Support\Settings::courier_labels();
    $location_state = [
        'province_id' => $store_province_id,
        'province_name' => $store_province,
        'city_id' => $store_city_id,
        'city_name' => $store_city,
        'subdistrict_id' => $store_subdistrict_id,
        'subdistrict_name' => $store_subdistrict,
        'postcode' => $store_postcode,
        'cod_enabled' => $cod_enabled,
        'cod_city_ids' => array_values(array_map('strval', $cod_city_ids)),
    ];
    ?>
    <div class="card border-0 shadow-sm" x-data='vmpStoreProfileForm(<?php echo wp_json_encode($location_state); ?>)' x-init="init()"><div class="card-body">
        <h3 class="h6 mb-3">Profil Toko</h3>
        <p class="text-muted small mb-3">Data pada halaman ini digunakan untuk profil toko publik, alamat asal pengiriman, dan pengaturan operasional penjualan.</p>
        <div class="alert alert-success py-2" x-show="saveMessage" x-text="saveMessage" style="display:none;"></div>
        <div class="alert alert-danger py-2" x-show="saveError" x-text="saveError" style="display:none;"></div>
        <form method="post" class="row g-3" @submit.prevent="submit($event)">
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
                                    <img src="<?php echo esc_url($avatar_url); ?>" alt="Foto profil toko" class="vmp-media-field__image">
                                    <button type="button" class="btn-close vmp-media-field__remove" aria-label="Hapus foto"></button>
                                </div>
                            </div>
                        <?php else : ?>
                            <div class="vmp-media-field__empty text-muted small">Belum ada foto yang dipilih.</div>
                        <?php endif; ?>
                    </div>
                    <div class="form-text">Pilih foto profil toko dari media library WordPress.</div>
                </div>
            </div>
            <div class="col-md-6"><label class="form-label">Kontak Telepon</label><input type="text" name="store_phone" class="form-control" value="<?php echo esc_attr($store_phone); ?>"></div>
            <div class="col-md-6"><label class="form-label">WhatsApp</label><input type="text" name="store_whatsapp" class="form-control" value="<?php echo esc_attr($store_whatsapp); ?>"></div>
            <div class="col-12"><label class="form-label">Alamat Toko</label><textarea name="store_address" class="form-control" rows="3" required><?php echo esc_textarea($store_address); ?></textarea></div>
            <div class="col-md-4">
                <label class="form-label">Provinsi</label>
                <select name="store_province_id" class="form-select" x-ref="provinceSelect" x-model="form.province_id" @change="onProvinceChange()" :disabled="isLoadingProvinces">
                    <option value="">Pilih provinsi</option>
                    <template x-for="prov in provinces" :key="prov.province_id">
                        <option :value="prov.province_id" x-text="prov.province"></option>
                    </template>
                </select>
                <input type="hidden" name="store_province_name" :value="form.province_name">
            </div>
            <div class="col-md-4">
                <label class="form-label">Kota/Kabupaten</label>
                <select name="store_city_id" class="form-select" x-ref="citySelect" x-model="form.city_id" @change="onCityChange()" :disabled="!form.province_id || isLoadingCities">
                    <option value="">Pilih kota atau kabupaten</option>
                    <template x-for="city in cities" :key="city.city_id">
                        <option :value="city.city_id" x-text="(city.type ? city.type + ' ' : '') + city.city_name"></option>
                    </template>
                </select>
                <input type="hidden" name="store_city_name" :value="form.city_name">
            </div>
            <div class="col-md-4">
                <label class="form-label">Kecamatan</label>
                <select name="store_subdistrict_id" class="form-select" x-ref="subdistrictSelect" x-model="form.subdistrict_id" @change="onSubdistrictChange()" :disabled="!form.city_id || isLoadingSubdistricts">
                    <option value="">Pilih kecamatan</option>
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
            <div class="col-12"><label class="form-label">Kurir Pengiriman</label><div class="d-flex flex-wrap gap-3"><?php foreach ($courier_options as $key => $label) : ?><label class="form-check-label"><input class="form-check-input me-1" type="checkbox" name="store_couriers[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $store_couriers, true)); ?>><?php echo esc_html($label); ?></label><?php endforeach; ?></div></div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="store_cod_enabled" name="store_cod_enabled" value="1" <?php checked($cod_enabled); ?> x-model="form.cod_enabled">
                    <label class="form-check-label" for="store_cod_enabled">Aktifkan COD</label>
                </div>
                <div class="form-text">Layanan COD diatur per kota dan hanya berlaku untuk pesanan yang memenuhi area pengiriman toko.</div>
            </div>
            <div class="col-12" x-show="form.cod_enabled">
                <label class="form-label">Kota COD</label>
                <select class="form-select" multiple size="6" x-ref="codCitySelect" x-model="form.cod_city_ids" @change="syncCodCities()">
                    <template x-for="city in cities" :key="'cod-' + city.city_id">
                        <option :value="city.city_id" x-text="(city.type ? city.type + ' ' : '') + city.city_name"></option>
                    </template>
                </select>
                <div class="form-text">Pilih satu atau beberapa kota dalam provinsi yang sama untuk layanan COD.</div>
                <template x-for="(cityId, index) in form.cod_city_ids" :key="'cod-hidden-' + cityId">
                    <div>
                        <input type="hidden" name="store_cod_city_ids[]" :value="cityId">
                        <input type="hidden" name="store_cod_city_names[]" :value="codCityName(cityId)">
                    </div>
                </template>
                <?php if (!empty($cod_city_names)) : ?>
                    <div class="small text-muted mt-2">
                        Kota yang tersimpan:
                        <?php echo esc_html(implode(', ', array_filter(array_map('strval', $cod_city_names)))); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-12 d-flex justify-content-between align-items-center"><div><?php if ($is_star_seller) : ?><span class="badge bg-warning text-dark">Star Seller Aktif</span><?php else : ?><span class="badge bg-secondary">Star Seller Belum Aktif</span><?php endif; ?></div><button type="submit" class="btn btn-dark" :disabled="saving" x-text="saving ? 'Menyimpan...' : 'Simpan Profil Toko'">Simpan Profil Toko</button></div>
        </form>
    </div></div>
