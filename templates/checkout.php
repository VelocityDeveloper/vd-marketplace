<?php
$captcha_html = \VelocityMarketplace\Modules\Captcha\CaptchaBridge::render('#vmp-checkout-form');
$settings = get_option('velocity_marketplace_settings', []);
if (!is_array($settings)) {
    $settings = [];
}
$active_payment_methods = isset($settings['payment_methods']) && is_array($settings['payment_methods'])
    ? array_values(array_unique(array_map('sanitize_key', $settings['payment_methods'])))
    : ['bank'];
if (empty($active_payment_methods)) {
    $active_payment_methods = ['bank'];
}
$payment_labels = [
    'bank' => 'Transfer Bank',
    'duitku' => 'Duitku',
    'paypal' => 'PayPal',
    'cod' => 'COD',
];
?>
<div class="container py-4 vmp-wrap" x-data="vmpCheckout()" x-init="init()">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h4 mb-0">Checkout</h2>
            <small class="text-muted">Captcha dari plugin Velocity Addons</small>
        </div>
        <a class="btn btn-sm btn-outline-dark" :href="cartUrl">Kembali ke Keranjang</a>
    </div>

    <div class="alert alert-danger py-2" x-show="errorMessage" x-text="errorMessage"></div>
    <div class="alert alert-success py-2" x-show="successMessage" x-text="successMessage"></div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form id="vmp-checkout-form" @submit.prevent="submitOrder()">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama</label>
                                <input type="text" class="form-control" x-model.trim="form.name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Telepon</label>
                                <input type="text" class="form-control" x-model.trim="form.phone" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" x-model.trim="form.email" placeholder="opsional">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode Pos</label>
                                <input type="text" class="form-control" x-model.trim="form.postal_code">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea class="form-control" rows="3" x-model.trim="form.address" required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Provinsi</label>
                                <select class="form-select" x-model="form.destination_province_id" @change="onProvinceChange()" :disabled="isLoadingProvinces">
                                    <option value="">- Pilih Provinsi -</option>
                                    <template x-for="prov in provinces" :key="prov.province_id">
                                        <option :value="prov.province_id" x-text="prov.province"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kota/Kabupaten</label>
                                <select class="form-select" x-model="form.destination_city_id" @change="onCityChange()" :disabled="!form.destination_province_id || isLoadingCities">
                                    <option value="">- Pilih Kota/Kabupaten -</option>
                                    <template x-for="city in cities" :key="city.city_id">
                                        <option :value="city.city_id" x-text="(city.type ? city.type + ' ' : '') + city.city_name"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pembayaran</label>
                                <select class="form-select" x-model="form.payment_method">
                                    <?php foreach ($active_payment_methods as $method) :
                                        $label = isset($payment_labels[$method]) ? $payment_labels[$method] : strtoupper($method);
                                        ?>
                                        <option value="<?php echo esc_attr($method); ?>"><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kecamatan</label>
                                <select class="form-select" x-model="form.destination_subdistrict_id" @change="onSubdistrictChange()" :disabled="!form.destination_city_id || isLoadingSubdistricts">
                                    <option value="">- Pilih Kecamatan -</option>
                                    <template x-for="subdistrict in subdistricts" :key="subdistrict.subdistrict_id">
                                        <option :value="subdistrict.subdistrict_id" x-text="subdistrict.subdistrict_name"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-12" x-show="shippingContextMessage">
                                <div class="alert alert-warning py-2 mb-0" x-text="shippingContextMessage"></div>
                            </div>
                            <div class="col-12" x-show="shippingGroups.length > 0">
                                <label class="form-label">Pilih Ongkir per Toko</label>
                                <div class="row g-3">
                                    <template x-for="group in shippingGroups" :key="group.seller_id">
                                        <div class="col-12">
                                            <div class="border rounded p-3">
                                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                                    <div>
                                                        <div class="fw-semibold" x-text="group.seller_name"></div>
                                                        <div class="small text-muted">
                                                            <span x-text="group.items_count + ' item'"></span>
                                                            <span> | </span>
                                                            <span x-text="formatPrice(group.subtotal)"></span>
                                                            <span> | </span>
                                                            <span x-text="(group.weight_grams / 1000).toFixed(2) + ' kg'"></span>
                                                        </div>
                                                    </div>
                                                    <div class="small text-muted d-flex flex-wrap gap-2">
                                                        <template x-for="courier in group.couriers" :key="courier.code">
                                                            <span class="badge bg-secondary" x-text="courier.name"></span>
                                                        </template>
                                                    </div>
                                                </div>
                                                <div class="small text-muted mb-2" x-show="group.loading">Memuat opsi ongkir...</div>
                                                <div class="small text-danger mb-2" x-show="group.message" x-text="group.message"></div>
                                                <div class="row g-2" x-show="group.services.length > 0">
                                                    <template x-for="opt in group.services" :key="group.seller_id + ':' + opt.code + ':' + opt.service">
                                                        <div class="col-md-6">
                                                            <button type="button" class="btn btn-outline-dark w-100 text-start" @click="selectShipping(group, opt)" :class="group.selectedKey === (opt.code + ':' + opt.service) ? 'active' : ''">
                                                                <div class="fw-semibold" x-text="opt.name + ' ' + opt.service"></div>
                                                                <div class="small text-muted" x-text="opt.description || '-'"></div>
                                                                <div class="small text-muted" x-text="opt.etd ? ('Estimasi ' + opt.etd) : ''"></div>
                                                                <div class="fw-semibold text-danger" x-text="formatPrice(opt.cost)"></div>
                                                            </button>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control" rows="2" x-model.trim="form.notes"></textarea>
                            </div>
                        </div>

                        <?php if (!empty($captcha_html)) : ?>
                            <div class="mt-3">
                                <?php echo $captcha_html; ?>
                            </div>
                        <?php endif; ?>

                        <button class="btn btn-primary mt-4" type="submit" :disabled="submitting || items.length === 0">
                            <span x-show="!submitting">Konfirmasi Pesanan</span>
                            <span x-show="submitting">Memproses...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="h6">Ringkasan Pesanan</h3>
                    <div class="small text-muted mb-3" x-text="items.length + ' produk di keranjang'"></div>
                    <template x-for="item in items" :key="item.id + '-' + optionKey(item)">
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <div class="pe-2">
                                <div class="fw-semibold small" x-text="item.title"></div>
                                <div class="text-muted vmp-xs" x-text="item.qty + ' x ' + formatPrice(item.price)"></div>
                            </div>
                            <div class="fw-semibold small" x-text="formatPrice(item.subtotal)"></div>
                        </div>
                    </template>
                    <div class="d-flex justify-content-between pt-3">
                        <span>Ongkir</span>
                        <strong x-text="formatPrice(form.shipping_cost || 0)"></strong>
                    </div>
                    <div class="d-flex justify-content-between pt-2">
                        <span>Total</span>
                        <strong class="text-danger" x-text="formatPrice(total)"></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

