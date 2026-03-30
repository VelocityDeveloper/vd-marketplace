<?php
use VelocityMarketplace\Modules\Order\OrderData;
use VelocityMarketplace\Modules\Shipping\ShippingController;

$transfer_captcha_html = \VelocityMarketplace\Modules\Captcha\CaptchaBridge::render();
$payment_labels = [
    'bank' => __('Bank Transfer', 'velocity-marketplace'),
    'duitku' => 'Duitku',
    'paypal' => 'PayPal',
    'cod' => 'COD',
];
$invoice = isset($_GET['invoice']) ? sanitize_text_field((string) wp_unslash($_GET['invoice'])) : '';
$tracking_order = null;

if ($invoice !== '') {
    $tracking_query = new \WP_Query([
        'post_type' => 'vmp_order',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'meta_query' => [
            ['key' => 'vmp_invoice', 'value' => $invoice, 'compare' => '='],
        ],
    ]);

    if ($tracking_query->have_posts()) {
        $tracking_query->the_post();
        $candidate_id = get_the_ID();
        $owner_id = (int) get_post_meta($candidate_id, 'vmp_user_id', true);
        $can_view = $owner_id === $current_user_id || current_user_can('manage_options') || OrderData::has_seller($candidate_id, $current_user_id);
        if ($can_view) {
            $tracking_order = get_post($candidate_id);
        }
        wp_reset_postdata();
    }
}
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="row g-2">
            <input type="hidden" name="tab" value="tracking">
            <div class="col-md-8">
                <label class="form-label"><?php echo esc_html__('Kode Invoice', 'velocity-marketplace'); ?></label>
                <input type="text" name="invoice" class="form-control" value="<?php echo esc_attr($invoice); ?>" placeholder="<?php echo esc_attr__('Contoh: VMP-20260304-123456', 'velocity-marketplace'); ?>" required>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-dark w-100" type="submit"><?php echo esc_html__('Lihat Detail Pesanan', 'velocity-marketplace'); ?></button>
            </div>
        </form>
    </div>
</div>

<?php if ($invoice !== '' && !$tracking_order) : ?>
    <div class="alert alert-warning mb-0"><?php echo esc_html__('Invoice tidak ditemukan atau Anda tidak memiliki akses ke pesanan ini.', 'velocity-marketplace'); ?></div>
<?php elseif ($tracking_order) : ?>
    <?php
    $tracking_id = (int) $tracking_order->ID;
    $tracking_status = (string) get_post_meta($tracking_id, 'vmp_status', true);
    $tracking_payment = (string) get_post_meta($tracking_id, 'vmp_payment_method', true);
    $tracking_shipping = get_post_meta($tracking_id, 'vmp_shipping', true);
    $tracking_groups = OrderData::shipping_groups($tracking_id);
    $tracking_subtotal = (float) get_post_meta($tracking_id, 'vmp_subtotal', true);
    $tracking_total = (float) get_post_meta($tracking_id, 'vmp_total', true);
    $tracking_shipping_total = (float) get_post_meta($tracking_id, 'vmp_shipping_total', true);
    $tracking_coupon_code = (string) get_post_meta($tracking_id, 'vmp_coupon_code', true);
    $tracking_coupon_scope = (string) get_post_meta($tracking_id, 'vmp_coupon_scope', true);
    $tracking_coupon_discount = (float) get_post_meta($tracking_id, 'vmp_coupon_discount', true);
    $tracking_coupon_product_discount = (float) get_post_meta($tracking_id, 'vmp_coupon_product_discount', true);
    $tracking_coupon_shipping_discount = (float) get_post_meta($tracking_id, 'vmp_coupon_shipping_discount', true);
    $tracking_notes = (string) get_post_meta($tracking_id, 'vmp_notes', true);
    $tracking_transfer_proof_id = (int) get_post_meta($tracking_id, 'vmp_transfer_proof_id', true);
    $tracking_transfer_proof_url = $tracking_transfer_proof_id > 0 ? wp_get_attachment_url($tracking_transfer_proof_id) : '';
    $tracking_bank_accounts = get_post_meta($tracking_id, 'vmp_bank_accounts', true);
    if (!is_array($tracking_bank_accounts)) {
        $tracking_bank_accounts = [];
    }
    if ($tracking_payment === 'bank' && empty($tracking_bank_accounts)) {
        $tracking_bank_accounts = \VelocityMarketplace\Support\Settings::bank_accounts();
    }
    $tracking_transfer_uploaded_at = (string) get_post_meta($tracking_id, 'vmp_transfer_uploaded_at', true);
    $tracking_created_at = (string) get_post_meta($tracking_id, 'vmp_created_at', true);
    $tracking_owner_id = (int) get_post_meta($tracking_id, 'vmp_user_id', true);
    $is_tracking_owner = $tracking_owner_id === $current_user_id || current_user_can('manage_options');
    if (!is_array($tracking_shipping)) {
        $tracking_shipping = [];
    }
    if ($tracking_shipping_total <= 0) {
        $tracking_shipping_total = (float) ($tracking_shipping['cost'] ?? 0);
    }
    $payment_status_text = $tracking_transfer_proof_url !== ''
        ? __('Bukti pembayaran sudah diunggah.', 'velocity-marketplace')
        : (in_array($tracking_payment, ['duitku', 'paypal'], true) ? __('Waiting for payment confirmation from the gateway.', 'velocity-marketplace') : __('Waiting for payment.', 'velocity-marketplace'));
    ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-lg-6">
                    <h3 class="h6 mb-3"><?php echo esc_html__('Informasi Pesanan', 'velocity-marketplace'); ?></h3>
                    <div class="small mb-1"><strong><?php echo esc_html__('Invoice:', 'velocity-marketplace'); ?></strong> <?php echo esc_html($invoice); ?></div>
                    <div class="small mb-1"><strong><?php echo esc_html__('Status:', 'velocity-marketplace'); ?></strong> <?php echo esc_html(OrderData::status_label($tracking_status)); ?></div>
                    <div class="small mb-1"><strong><?php echo esc_html__('Metode Pembayaran:', 'velocity-marketplace'); ?></strong> <?php echo esc_html($tracking_payment !== '' ? ($payment_labels[$tracking_payment] ?? strtoupper($tracking_payment)) : '-'); ?></div>
                    <div class="small mb-1"><strong><?php echo esc_html__('Tanggal:', 'velocity-marketplace'); ?></strong> <?php echo esc_html($tracking_created_at !== '' ? $tracking_created_at : get_the_date('d-m-Y H:i', $tracking_id)); ?></div>
                    <div class="small mb-1"><strong><?php echo esc_html__('Tujuan:', 'velocity-marketplace'); ?></strong> <?php echo esc_html(trim((string) (($tracking_shipping['subdistrict_destination_name'] ?? '') . ', ' . ($tracking_shipping['city_destination_name'] ?? '') . ', ' . ($tracking_shipping['province_destination_name'] ?? '')), ', ')); ?></div>
                    <div class="small text-muted mt-2"><?php echo esc_html__('Catatan Pesanan:', 'velocity-marketplace'); ?> <?php echo esc_html($tracking_notes !== '' ? $tracking_notes : '-'); ?></div>
                </div>
                <div class="col-lg-6">
                    <h3 class="h6 mb-3"><?php echo esc_html__('Detail Pembayaran', 'velocity-marketplace'); ?></h3>
                    <div class="small mb-1"><strong><?php echo esc_html__('Subtotal Produk:', 'velocity-marketplace'); ?></strong> Rp <?php echo esc_html(number_format($tracking_subtotal, 0, ',', '.')); ?></div>
                    <div class="small mb-1"><strong><?php echo esc_html__('Total Ongkir:', 'velocity-marketplace'); ?></strong> Rp <?php echo esc_html(number_format($tracking_shipping_total, 0, ',', '.')); ?></div>
                    <div class="small mb-1"><strong><?php echo esc_html__('Kupon:', 'velocity-marketplace'); ?></strong> <?php echo esc_html($tracking_coupon_code !== '' ? $tracking_coupon_code : '-'); ?></div>
                    <?php if ($tracking_coupon_code !== '') : ?>
                        <div class="small mb-1"><strong><?php echo esc_html__('Cakupan Kupon:', 'velocity-marketplace'); ?></strong> <?php echo esc_html($tracking_coupon_scope === 'shipping' ? __('Diskon Ongkir', 'velocity-marketplace') : __('Diskon Produk', 'velocity-marketplace')); ?></div>
                    <?php endif; ?>
                    <?php if ($tracking_coupon_product_discount > 0) : ?>
                        <div class="small mb-1"><strong><?php echo esc_html__('Diskon Produk:', 'velocity-marketplace'); ?></strong> -Rp <?php echo esc_html(number_format($tracking_coupon_product_discount, 0, ',', '.')); ?></div>
                    <?php endif; ?>
                    <?php if ($tracking_coupon_shipping_discount > 0) : ?>
                        <div class="small mb-1"><strong><?php echo esc_html__('Diskon Ongkir:', 'velocity-marketplace'); ?></strong> -Rp <?php echo esc_html(number_format($tracking_coupon_shipping_discount, 0, ',', '.')); ?></div>
                    <?php endif; ?>
                    <?php if ($tracking_coupon_discount > 0 && $tracking_coupon_product_discount <= 0 && $tracking_coupon_shipping_discount <= 0) : ?>
                        <div class="small mb-1"><strong><?php echo esc_html__('Coupon Discount:', 'velocity-marketplace'); ?></strong> -Rp <?php echo esc_html(number_format($tracking_coupon_discount, 0, ',', '.')); ?></div>
                    <?php endif; ?>
                    <div class="small mb-1"><strong><?php echo esc_html__('Total Bayar:', 'velocity-marketplace'); ?></strong> Rp <?php echo esc_html(number_format($tracking_total, 0, ',', '.')); ?></div>
                    <div class="small mb-1"><strong><?php echo esc_html__('Status Pembayaran:', 'velocity-marketplace'); ?></strong> <?php echo esc_html($payment_status_text); ?></div>
                    <div class="small mb-1"><strong><?php echo esc_html__('Bukti Pembayaran:', 'velocity-marketplace'); ?></strong> <?php echo esc_html($tracking_transfer_proof_url !== '' ? __('Sudah Diunggah', 'velocity-marketplace') : __('Belum tersedia', 'velocity-marketplace')); ?></div>
                    <?php if ($tracking_transfer_uploaded_at !== '') : ?>
                        <div class="small mb-1"><strong><?php echo esc_html__('Waktu Upload:', 'velocity-marketplace'); ?></strong> <?php echo esc_html($tracking_transfer_uploaded_at); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($tracking_transfer_proof_url) : ?>
                <div class="mt-3">
                    <a href="<?php echo esc_url($tracking_transfer_proof_url); ?>" class="btn btn-sm btn-outline-primary" target="_blank"><?php echo esc_html__('Lihat Bukti Pembayaran', 'velocity-marketplace'); ?></a>
                </div>
            <?php endif; ?>

            <?php if ($tracking_payment === 'bank' && !empty($tracking_bank_accounts)) : ?>
                <div class="mt-4 border-top pt-3">
                    <h3 class="h6 mb-2"><?php echo esc_html__('Rekening Tujuan Transfer', 'velocity-marketplace'); ?></h3>
                    <div class="small text-muted mb-3"><?php echo esc_html__('Gunakan salah satu rekening berikut untuk membayar pesanan ini.', 'velocity-marketplace'); ?></div>
                    <div class="row g-3">
                        <?php foreach ($tracking_bank_accounts as $bank_account) : ?>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold"><?php echo esc_html((string) ($bank_account['bank_name'] ?? '-')); ?></div>
                                    <div class="small text-muted mt-2"><?php echo esc_html__('Nomor Rekening', 'velocity-marketplace'); ?></div>
                                    <div class="fw-semibold"><?php echo esc_html((string) ($bank_account['account_number'] ?? '-')); ?></div>
                                    <div class="small text-muted mt-2"><?php echo esc_html__('Atas Nama', 'velocity-marketplace'); ?></div>
                                    <div><?php echo esc_html((string) ($bank_account['account_holder'] ?? '-')); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($is_tracking_owner && !in_array($tracking_status, ['completed', 'cancelled', 'refunded'], true)) : ?>
                <div class="mt-4 border-top pt-3">
                    <h3 class="h6 mb-2"><?php echo esc_html__('Unggah Bukti Transfer', 'velocity-marketplace'); ?></h3>
                    <form method="post" enctype="multipart/form-data" class="row g-2">
                        <input type="hidden" name="vmp_action" value="buyer_upload_transfer">
                        <input type="hidden" name="order_id" value="<?php echo esc_attr($tracking_id); ?>">
                        <input type="hidden" name="redirect_tab" value="tracking">
                        <?php wp_nonce_field('vmp_upload_transfer_' . $tracking_id, 'vmp_transfer_nonce'); ?>
                        <div class="col-md-8">
                            <input type="file" name="transfer_proof" class="form-control" accept="image/*,.pdf" required>
                        </div>
                        <?php if ($transfer_captcha_html !== '') : ?><div class="col-12"><?php echo $transfer_captcha_html; ?></div><?php endif; ?>
                        <div class="col-md-4">
                            <button class="btn btn-dark w-100" type="submit"><?php echo esc_html__('Unggah Bukti Transfer', 'velocity-marketplace'); ?></button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <?php if (!empty($tracking_groups)) : ?>
                <div class="mt-4">
                    <h3 class="h6 mb-3"><?php echo esc_html__('Pengiriman per Toko', 'velocity-marketplace'); ?></h3>
                    <?php foreach ($tracking_groups as $group) : ?>
                        <?php
                        $receipt = (string) ($group['receipt_no'] ?? '');
                        $courier = (string) ($group['receipt_courier'] ?? ($group['courier'] ?? ''));
                        $waybill = null;
                        if ($receipt !== '' && $courier !== '') {
                            $maybe_waybill = ShippingController::fetch_waybill($receipt, $courier);
                            if (!is_wp_error($maybe_waybill) && is_array($maybe_waybill)) {
                                $waybill = $maybe_waybill;
                            }
                        }
                        $tracking_rows = [];
                        if ($waybill && isset($waybill['data']['manifest']) && is_array($waybill['data']['manifest'])) {
                            $tracking_rows = $waybill['data']['manifest'];
                        } elseif ($waybill && isset($waybill['data']['history']) && is_array($waybill['data']['history'])) {
                            $tracking_rows = $waybill['data']['history'];
                        }
                        ?>
                        <div class="border rounded p-3 mt-3">
                            <div class="fw-semibold"><?php echo esc_html((string) ($group['seller_name'] ?? __('Toko', 'velocity-marketplace'))); ?></div>
                            <div class="small mt-1"><strong><?php echo esc_html__('Kurir:', 'velocity-marketplace'); ?></strong> <?php echo esc_html($courier !== '' ? $courier : '-'); ?></div>
                            <div class="small"><strong><?php echo esc_html__('Layanan:', 'velocity-marketplace'); ?></strong> <?php echo esc_html((string) ($group['service'] ?? '-')); ?></div>
                            <div class="small"><strong><?php echo esc_html__('Nomor Resi:', 'velocity-marketplace'); ?></strong> <?php echo esc_html($receipt !== '' ? $receipt : '-'); ?></div>
                            <div class="small"><strong><?php echo esc_html__('Pengiriman:', 'velocity-marketplace'); ?></strong> Rp <?php echo esc_html(number_format((float) ($group['cost'] ?? 0), 0, ',', '.')); ?></div>
                            <?php if (!empty($tracking_rows)) : ?>
                                <div class="mt-2">
                                    <?php foreach ($tracking_rows as $row) : ?>
                                        <div class="border-top py-2">
                                            <div class="fw-semibold"><?php echo esc_html((string) ($row['manifest_description'] ?? $row['description'] ?? '-')); ?></div>
                                            <div class="small text-muted"><?php echo esc_html(trim((string) (($row['manifest_date'] ?? '') . ' ' . ($row['manifest_time'] ?? $row['date'] ?? '')))); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
