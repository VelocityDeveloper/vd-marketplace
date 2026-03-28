<?php

namespace VelocityMarketplace\Core;

use VelocityMarketplace\Support\Settings;

class SettingsPage
{
    private $page_hook = '';

    public function register()
    {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_setting']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_menu()
    {
        $this->page_hook = add_submenu_page(
            'edit.php?post_type=vmp_product',
            'Pengaturan Marketplace',
            'Pengaturan',
            'manage_options',
            'vmp-settings',
            [$this, 'render_page']
        );
    }

    public function register_setting()
    {
        register_setting(
            'vmp_settings_group',
            VMP_SETTINGS_OPTION,
            [$this, 'sanitize_settings']
        );
    }

    public function sanitize_settings($input)
    {
        $service = new SettingsService();
        return $service->sanitize($input);
    }

    public function enqueue_assets($hook)
    {
        if ($hook !== $this->page_hook) {
            return;
        }

        wp_register_script(
            'alpinejs',
            'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js',
            [],
            null,
            true
        );

        wp_enqueue_script(
            'velocity-marketplace-admin-settings-js',
            VMP_URL . 'assets/js/admin-settings.js',
            [],
            VMP_VERSION,
            true
        );

        wp_enqueue_script('alpinejs');

        $service = new SettingsService();
        wp_localize_script('velocity-marketplace-admin-settings-js', 'vmpAdminSettings', [
            'restUrl' => esc_url_raw(rest_url('velocity-marketplace/v1/settings')),
            'nonce' => wp_create_nonce('wp_rest'),
            'initialSettings' => $service->get_settings_payload(),
            'popularBanks' => Settings::popular_bank_labels(),
        ]);
    }

    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>Pengaturan Velocity Marketplace</h1>
            <style>
                .vmp-admin-settings {
                    max-width: 1180px;
                    margin-top: 20px;
                }
                .vmp-admin-settings__notice {
                    margin: 0 0 16px;
                }
                .vmp-settings-tabs {
                    display: flex;
                    gap: 8px;
                    margin: 0 0 16px;
                    flex-wrap: wrap;
                }
                .vmp-settings-tab {
                    border: 1px solid #dcdcde;
                    background: #fff;
                    color: #1d2327;
                    border-radius: 999px;
                    padding: 10px 16px;
                    font-size: 13px;
                    font-weight: 600;
                    line-height: 1;
                    cursor: pointer;
                    transition: all .18s ease;
                }
                .vmp-settings-tab:hover {
                    border-color: #2271b1;
                    color: #2271b1;
                }
                .vmp-settings-tab.is-active {
                    background: #2271b1;
                    border-color: #2271b1;
                    color: #fff;
                }
                .vmp-settings-panel {
                    display: none;
                }
                .vmp-settings-panel.is-active {
                    display: block;
                }
                .vmp-settings-card {
                    background: #fff;
                    border: 1px solid #dcdcde;
                    border-radius: 10px;
                    padding: 20px;
                }
                .vmp-settings-card + .vmp-settings-card {
                    margin-top: 16px;
                }
                .vmp-bank-settings__title {
                    margin: 0 0 6px;
                    font-size: 16px;
                    font-weight: 600;
                }
                .vmp-bank-settings__desc {
                    margin: 0 0 16px;
                    color: #50575e;
                }
                .vmp-bank-settings__section + .vmp-bank-settings__section {
                    margin-top: 24px;
                }
                .vmp-bank-settings__section-title {
                    margin: 0 0 12px;
                    font-size: 14px;
                    font-weight: 600;
                }
                .vmp-bank-settings__toolbar {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 12px;
                    margin-bottom: 12px;
                    flex-wrap: wrap;
                }
                .vmp-bank-settings__toolbar .description {
                    margin: 0;
                }
                .vmp-bank-settings__empty {
                    margin: 0 0 12px;
                    color: #50575e;
                    font-style: italic;
                }
                .vmp-bank-settings__rows {
                    display: flex;
                    flex-direction: column;
                    gap: 12px;
                }
                .vmp-bank-settings__row {
                    display: grid;
                    grid-template-columns: minmax(220px, 260px) minmax(220px, 1fr) minmax(220px, 1fr);
                    gap: 12px;
                    align-items: end;
                    padding: 14px;
                    border: 1px solid #dcdcde;
                    border-radius: 8px;
                    background: #fff;
                }
                .vmp-bank-settings__field label {
                    display: block;
                    margin-bottom: 6px;
                    font-weight: 500;
                }
                .vmp-bank-settings__field input,
                .vmp-bank-settings__field select {
                    width: 100%;
                    max-width: none;
                }
                .vmp-bank-settings__row-actions {
                    display: flex;
                    justify-content: flex-end;
                    grid-column: 1 / -1;
                }
                .vmp-admin-settings__footer {
                    margin-top: 16px;
                }
                @media (max-width: 1100px) {
                    .vmp-bank-settings__row {
                        grid-template-columns: 1fr;
                    }
                }
            </style>
            <div class="vmp-admin-settings" x-data="vmpAdminSettingsPage()" x-init="init()">
                <div class="notice notice-success is-dismissible vmp-admin-settings__notice" x-show="saveMessage" style="display:none;">
                    <p x-text="saveMessage"></p>
                </div>
                <div class="notice notice-error is-dismissible vmp-admin-settings__notice" x-show="saveError" style="display:none;">
                    <p x-text="saveError"></p>
                </div>

                <div class="vmp-settings-tabs" role="tablist" aria-label="Pengaturan Marketplace">
                    <button type="button" class="vmp-settings-tab" :class="{ 'is-active': activeTab === 'general' }" @click="setTab('general')">Pengaturan Umum</button>
                    <button type="button" class="vmp-settings-tab" :class="{ 'is-active': activeTab === 'bank' }" @click="setTab('bank')">Pengaturan Bank</button>
                </div>

                <div class="vmp-settings-panel" :class="{ 'is-active': activeTab === 'general' }">
                    <div class="vmp-settings-card">
                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><label for="vmp_currency">Mata Uang</label></th>
                                    <td>
                                        <select id="vmp_currency" x-model="form.currency">
                                            <option value="IDR">IDR</option>
                                            <option value="USD">USD</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="vmp_currency_symbol">Simbol Mata Uang</label></th>
                                    <td>
                                        <input id="vmp_currency_symbol" type="text" class="regular-text" x-model="form.currency_symbol">
                                        <p class="description">Contoh: Rp, $, USD.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="vmp_default_order_status">Status Order Default</label></th>
                                    <td>
                                        <select id="vmp_default_order_status" x-model="form.default_order_status">
                                            <option value="pending_payment">Pending Payment</option>
                                            <option value="pending_verification">Pending Verification</option>
                                            <option value="processing">Processing</option>
                                            <option value="shipped">Shipped</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                            <option value="refunded">Refunded</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Metode Pembayaran Aktif</th>
                                    <td>
                                        <label><input type="checkbox" value="bank" x-model="form.payment_methods"> Transfer Bank</label><br>
                                        <label><input type="checkbox" value="duitku" x-model="form.payment_methods"> Duitku</label><br>
                                        <label><input type="checkbox" value="paypal" x-model="form.payment_methods"> PayPal</label><br>
                                        <label><input type="checkbox" value="cod" x-model="form.payment_methods"> COD</label>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="vmp_seller_product_status">Status Produk Member Baru</label></th>
                                    <td>
                                        <select id="vmp_seller_product_status" x-model="form.seller_product_status">
                                            <option value="pending">Pending Review</option>
                                            <option value="publish">Langsung Publish</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="vmp_shipping_api_key">API Key Ongkir</label></th>
                                    <td>
                                        <input id="vmp_shipping_api_key" type="text" class="regular-text" x-model="form.shipping_api_key">
                                        <p class="description">Digunakan untuk memuat data wilayah, menghitung ongkir, dan mengambil informasi pelacakan pengiriman.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="vmp-settings-panel" :class="{ 'is-active': activeTab === 'bank' }">
                    <div class="vmp-settings-card">
                        <h2 class="vmp-bank-settings__title">Rekening Transfer Bank</h2>
                        <p class="vmp-bank-settings__desc">Rekening pada bagian ini digunakan sebagai tujuan pembayaran saat pembeli memilih metode transfer bank.</p>

                        <div class="vmp-bank-settings__section">
                            <h3 class="vmp-bank-settings__section-title">Rekening Bank Populer</h3>
                            <div class="vmp-bank-settings__toolbar">
                                <p class="description">Pilih bank dari daftar populer, lalu isi nomor rekening dan nama pemilik rekening.</p>
                                <button type="button" class="button button-secondary" @click="addPopularBank()">Tambah Rekening Populer</button>
                            </div>
                            <p class="vmp-bank-settings__empty" x-show="!form.popular_bank_accounts.length">Belum ada rekening bank populer.</p>
                            <div class="vmp-bank-settings__rows">
                                <template x-for="(row, index) in form.popular_bank_accounts" :key="'popular-' + index">
                                    <div class="vmp-bank-settings__row">
                                        <div class="vmp-bank-settings__field">
                                            <label>Nama Bank</label>
                                            <select :value="row.bank_code || ''" @change="row.bank_code = $event.target.value">
                                                <option value="">Pilih bank</option>
                                                <template x-for="bank in popularBankEntries" :key="bank.code">
                                                    <option :value="bank.code" :selected="(row.bank_code || '') === bank.code" x-text="bank.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="vmp-bank-settings__field">
                                            <label>Nomor Rekening</label>
                                            <input type="text" class="regular-text" x-model="row.account_number" placeholder="Contoh: 1234567890">
                                        </div>
                                        <div class="vmp-bank-settings__field">
                                            <label>Atas Nama</label>
                                            <input type="text" class="regular-text" x-model="row.account_holder" placeholder="Contoh: PT Velocity Marketplace">
                                        </div>
                                        <div class="vmp-bank-settings__row-actions">
                                            <button type="button" class="button-link-delete" @click="removePopularBank(index)">Hapus</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="vmp-bank-settings__section">
                            <h3 class="vmp-bank-settings__section-title">Rekening Bank Lainnya</h3>
                            <div class="vmp-bank-settings__toolbar">
                                <p class="description">Tambahkan rekening dari bank lain di luar daftar populer jika diperlukan.</p>
                                <button type="button" class="button button-secondary" @click="addCustomBank()">Tambah Rekening Lainnya</button>
                            </div>
                            <p class="vmp-bank-settings__empty" x-show="!form.custom_bank_accounts.length">Belum ada rekening bank lainnya.</p>
                            <div class="vmp-bank-settings__rows">
                                <template x-for="(row, index) in form.custom_bank_accounts" :key="'custom-' + index">
                                    <div class="vmp-bank-settings__row">
                                        <div class="vmp-bank-settings__field">
                                            <label>Nama Bank</label>
                                            <input type="text" class="regular-text" x-model="row.bank_name" placeholder="Masukkan nama bank lain">
                                        </div>
                                        <div class="vmp-bank-settings__field">
                                            <label>Nomor Rekening</label>
                                            <input type="text" class="regular-text" x-model="row.account_number" placeholder="Contoh: 1234567890">
                                        </div>
                                        <div class="vmp-bank-settings__field">
                                            <label>Atas Nama</label>
                                            <input type="text" class="regular-text" x-model="row.account_holder" placeholder="Contoh: PT Velocity Marketplace">
                                        </div>
                                        <div class="vmp-bank-settings__row-actions">
                                            <button type="button" class="button-link-delete" @click="removeCustomBank(index)">Hapus</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="vmp-admin-settings__footer">
                    <button type="button" class="button button-primary button-large" @click="save()" :disabled="saving" x-text="saving ? 'Menyimpan...' : 'Simpan Pengaturan'">Simpan Pengaturan</button>
                </div>
            </div>
        </div>
        <?php
    }
}
