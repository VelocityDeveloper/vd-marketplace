<?php
$customer_name = (string) get_user_meta($current_user_id, 'vmp_name', true);
$customer_phone = (string) get_user_meta($current_user_id, 'vmp_phone', true);
$customer_address = (string) get_user_meta($current_user_id, 'vmp_address', true);
$customer_subdistrict = (string) get_user_meta($current_user_id, 'vmp_subdistrict', true);
$customer_city = (string) get_user_meta($current_user_id, 'vmp_city', true);
$customer_province = (string) get_user_meta($current_user_id, 'vmp_province', true);
$customer_subdistrict_id = (string) get_user_meta($current_user_id, 'vmp_subdistrict_id', true);
$customer_city_id = (string) get_user_meta($current_user_id, 'vmp_city_id', true);
$customer_province_id = (string) get_user_meta($current_user_id, 'vmp_province_id', true);
$customer_postcode = (string) get_user_meta($current_user_id, 'vmp_postcode', true);
$customer_email = '';
$customer_user = get_userdata($current_user_id);
if ($customer_user) {
    $customer_email = (string) $customer_user->user_email;
}
if ($customer_name === '' && $customer_user && $customer_user->display_name !== '') {
    $customer_name = (string) $customer_user->display_name;
}
$location_state = [
    'province_id' => $customer_province_id,
    'province_name' => $customer_province,
    'city_id' => $customer_city_id,
    'city_name' => $customer_city,
    'subdistrict_id' => $customer_subdistrict_id,
    'subdistrict_name' => $customer_subdistrict,
    'postcode' => $customer_postcode,
];
?>
<div class="card border-0 shadow-sm" x-data='vmpStoreProfileLocation(<?php echo wp_json_encode($location_state); ?>)' x-init="init()">
    <div class="card-body">
        <h3 class="h6 mb-3">Profil Akun</h3>
        <p class="text-muted small mb-3">Simpan data profil utama supaya checkout berikutnya terisi otomatis dan profil toko memakai data yang sama.</p>
        <form method="post" class="row g-3">
            <input type="hidden" name="vmp_action" value="save_customer_profile">
            <input type="hidden" name="tab" value="account_profile">
            <?php wp_nonce_field('vmp_customer_profile', 'vmp_customer_profile_nonce'); ?>
            <div class="col-md-6">
                <label class="form-label">Nama</label>
                <input type="text" name="customer_name" class="form-control" value="<?php echo esc_attr($customer_name); ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Telepon</label>
                <input type="text" name="customer_phone" class="form-control" value="<?php echo esc_attr($customer_phone); ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="<?php echo esc_attr($customer_email); ?>" readonly>
                <div class="form-text">Email checkout akan mengikuti akun WordPress.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Kode Pos</label>
                <input type="text" name="customer_postcode" class="form-control" x-model="form.postcode">
            </div>
            <div class="col-12">
                <label class="form-label">Alamat</label>
                <textarea name="customer_address" class="form-control" rows="3" required><?php echo esc_textarea($customer_address); ?></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Provinsi</label>
                <select name="customer_province_id" class="form-select" x-ref="provinceSelect" x-model="form.province_id" @change="onProvinceChange()" :disabled="isLoadingProvinces">
                    <option value="">- Pilih Provinsi -</option>
                    <template x-for="prov in provinces" :key="prov.province_id">
                        <option :value="prov.province_id" x-text="prov.province"></option>
                    </template>
                </select>
                <input type="hidden" name="customer_province_name" :value="form.province_name">
            </div>
            <div class="col-md-4">
                <label class="form-label">Kota/Kabupaten</label>
                <select name="customer_city_id" class="form-select" x-ref="citySelect" x-model="form.city_id" @change="onCityChange()" :disabled="!form.province_id || isLoadingCities">
                    <option value="">- Pilih Kota/Kabupaten -</option>
                    <template x-for="city in cities" :key="city.city_id">
                        <option :value="city.city_id" x-text="(city.type ? city.type + ' ' : '') + city.city_name"></option>
                    </template>
                </select>
                <input type="hidden" name="customer_city_name" :value="form.city_name">
            </div>
            <div class="col-md-4">
                <label class="form-label">Kecamatan</label>
                <select name="customer_subdistrict_id" class="form-select" x-ref="subdistrictSelect" x-model="form.subdistrict_id" @change="onSubdistrictChange()" :disabled="!form.city_id || isLoadingSubdistricts">
                    <option value="">- Pilih Kecamatan -</option>
                    <template x-for="subdistrict in subdistricts" :key="subdistrict.subdistrict_id">
                        <option :value="subdistrict.subdistrict_id" x-text="subdistrict.subdistrict_name"></option>
                    </template>
                </select>
                <input type="hidden" name="customer_subdistrict_name" :value="form.subdistrict_name">
            </div>
            <div class="col-12">
                <div class="small text-muted" x-show="locationMessage" x-text="locationMessage"></div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-dark">Simpan Profil Akun</button>
            </div>
        </form>
    </div>
</div>
