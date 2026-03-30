<?php
use VelocityMarketplace\Modules\Order\OrderData;
use VelocityMarketplace\Modules\Shipping\ShippingController;

$invoice = isset($_GET['invoice']) ? sanitize_text_field((string) wp_unslash($_GET['invoice'])) : '';
$order = null;
if ($invoice !== '') {
    $query = new \WP_Query([
        'post_type' => 'vmp_order',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => 'vmp_invoice',
                'value' => $invoice,
                'compare' => '=',
            ],
        ],
    ]);

    if ($query->have_posts()) {
        $query->the_post();
        $order = get_post(get_the_ID());
        wp_reset_postdata();
    }
}
?>
<div class="container py-4 vmp-wrap">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h4 mb-0"><?php echo esc_html__('Lacak Pesanan', 'velocity-marketplace'); ?></h2>
            <small class="text-muted"><?php echo esc_html__('Masukkan kode invoice untuk melihat status pesanan dan pengiriman.', 'velocity-marketplace'); ?></small>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="get" class="row g-2">
                <div class="col-md-8">
                    <label class="form-label"><?php echo esc_html__('Kode Invoice', 'velocity-marketplace'); ?></label>
                    <input type="text" name="invoice" class="form-control" value="<?php echo esc_attr($invoice); ?>" placeholder="<?php echo esc_attr__('Contoh: VMP-20260304-123456', 'velocity-marketplace'); ?>" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-dark w-100"><?php echo esc_html__('Lihat Status Pesanan', 'velocity-marketplace'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($invoice !== '' && !$order) : ?>
        <div class="alert alert-warning mb-0"><?php echo esc_html__('Invoice tidak ditemukan.', 'velocity-marketplace'); ?></div>
    <?php elseif ($order) : ?>
        <?php
        $order_id = (int) $order->ID;
        $status = (string) get_post_meta($order_id, 'vmp_status', true);
        $payment = (string) get_post_meta($order_id, 'vmp_payment_method', true);
        $shipping = get_post_meta($order_id, 'vmp_shipping', true);
        $shipping_groups = OrderData::shipping_groups($order_id);
        $subtotal = (float) get_post_meta($order_id, 'vmp_subtotal', true);
        $shipping_total = (float) get_post_meta($order_id, 'vmp_shipping_total', true);
        $total = (float) get_post_meta($order_id, 'vmp_total', true);
        $coupon_code = (string) get_post_meta($order_id, 'vmp_coupon_code', true);
        $coupon_scope = (string) get_post_meta($order_id, 'vmp_coupon_scope', true);
        $coupon_discount = (float) get_post_meta($order_id, 'vmp_coupon_discount', true);
        $coupon_product_discount = (float) get_post_meta($order_id, 'vmp_coupon_product_discount', true);
        $coupon_shipping_discount = (float) get_post_meta($order_id, 'vmp_coupon_shipping_discount', true);
        $bank_accounts = get_post_meta($order_id, 'vmp_bank_accounts', true);
        if (!is_array($bank_accounts)) {
            $bank_accounts = [];
        }
        if ($payment === 'bank' && empty($bank_accounts)) {
            $bank_accounts = \VelocityMarketplace\Support\Settings::bank_accounts();
        }
        if ($shipping_total <= 0) {
            $shipping_total = (float) ($shipping['cost'] ?? 0);
        }
        if (!is_array($shipping)) {
            $shipping = [];
        }
        ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div><strong><?php echo esc_html__('Invoice:', 'velocity-marketplace'); ?></strong> <?php echo esc_html($invoice); ?></div>
                        <div><strong><?php echo esc_html__('Status:', 'velocity-marketplace'); ?></strong> <?php echo esc_html(OrderData::status_label($status)); ?></div>
                        <div><strong><?php echo esc_html__('Metode Pembayaran:', 'velocity-marketplace'); ?></strong> <?php echo esc_html($payment !== '' ? strtoupper($payment) : '-'); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div><strong><?php echo esc_html__('Tujuan:', 'velocity-marketplace'); ?></strong> <?php echo esc_html(trim((string) (($shipping['subdistrict_destination_name'] ?? '') . ', ' . ($shipping['city_destination_name'] ?? '') . ', ' . ($shipping['province_destination_name'] ?? '')), ', ')); ?></div>
                        <div><strong><?php echo esc_html__('Subtotal Produk:', 'velocity-marketplace'); ?></strong> Rp <?php echo esc_html(number_format($subtotal, 0, ',', '.')); ?></div>
                        <div><strong><?php echo esc_html__('Total Ongkir:', 'velocity-marketplace'); ?></strong> Rp <?php echo esc_html(number_format($shipping_total, 0, ',', '.')); ?></div>
                        <div><strong><?php echo esc_html__('Kupon:', 'velocity-marketplace'); ?></strong> <?php echo esc_html($coupon_code !== '' ? $coupon_code : '-'); ?></div>
                        <?php if ($coupon_code !== '') : ?>
                            <div><strong><?php echo esc_html__('Cakupan Kupon:', 'velocity-marketplace'); ?></strong> <?php echo esc_html($coupon_scope === 'shipping' ? __('Diskon Ongkir', 'velocity-marketplace') : __('Diskon Produk', 'velocity-marketplace')); ?></div>
                        <?php endif; ?>
                        <?php if ($coupon_product_discount > 0) : ?>
                            <div><strong><?php echo esc_html__('Diskon Produk:', 'velocity-marketplace'); ?></strong> -Rp <?php echo esc_html(number_format($coupon_product_discount, 0, ',', '.')); ?></div>
                        <?php endif; ?>
                        <?php if ($coupon_shipping_discount > 0) : ?>
                            <div><strong><?php echo esc_html__('Diskon Ongkir:', 'velocity-marketplace'); ?></strong> -Rp <?php echo esc_html(number_format($coupon_shipping_discount, 0, ',', '.')); ?></div>
                        <?php endif; ?>
                        <?php if ($coupon_discount > 0 && $coupon_product_discount <= 0 && $coupon_shipping_discount <= 0) : ?>
                            <div><strong><?php echo esc_html__('Coupon Discount:', 'velocity-marketplace'); ?></strong> -Rp <?php echo esc_html(number_format($coupon_discount, 0, ',', '.')); ?></div>
                        <?php endif; ?>
                        <div><strong><?php echo esc_html__('Total Bayar:', 'velocity-marketplace'); ?></strong> Rp <?php echo esc_html(number_format($total, 0, ',', '.')); ?></div>
                    </div>
                </div>
                <?php if ($payment === 'bank' && !empty($bank_accounts)) : ?>
                    <div class="mt-4 border-top pt-3">
                        <h3 class="h6 mb-2"><?php echo esc_html__('Rekening Tujuan Transfer', 'velocity-marketplace'); ?></h3>
                        <div class="small text-muted mb-3"><?php echo esc_html__('Gunakan salah satu rekening berikut untuk membayar pesanan ini.', 'velocity-marketplace'); ?></div>
                        <div class="row g-3">
                            <?php foreach ($bank_accounts as $bank_account) : ?>
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
                <?php if (!empty($shipping_groups)) : ?>
                    <div class="mt-3">
                        <?php foreach ($shipping_groups as $shipping_group) : ?>
                            <?php
                            $seller_name = (string) ($shipping_group['seller_name'] ?? __('Toko', 'velocity-marketplace'));
                            $receipt_no = (string) ($shipping_group['receipt_no'] ?? '');
                            $receipt_courier = (string) ($shipping_group['receipt_courier'] ?? ($shipping_group['courier'] ?? ''));
                            $waybill = null;
                            if ($receipt_no !== '' && $receipt_courier !== '') {
                                $maybe_waybill = ShippingController::fetch_waybill($receipt_no, $receipt_courier);
                                if (!is_wp_error($maybe_waybill) && is_array($maybe_waybill)) {
                                    $waybill = $maybe_waybill;
                                }
                            }
                            ?>
                            <div class="border rounded p-3 mb-3">
                                <div class="fw-semibold"><?php echo esc_html($seller_name); ?></div>
                                <div><strong><?php echo esc_html__('Kurir:', 'velocity-marketplace'); ?></strong> <?php echo esc_html($receipt_courier !== '' ? $receipt_courier : '-'); ?></div>
                                <div><strong><?php echo esc_html__('Layanan:', 'velocity-marketplace'); ?></strong> <?php echo esc_html((string) ($shipping_group['service'] ?? '-')); ?></div>
                                <div><strong><?php echo esc_html__('Nomor Resi:', 'velocity-marketplace'); ?></strong> <?php echo esc_html($receipt_no !== '' ? $receipt_no : '-'); ?></div>
                                <?php
                                $tracking_rows = [];
                                if ($waybill && isset($waybill['data']['manifest']) && is_array($waybill['data']['manifest'])) {
                                    $tracking_rows = $waybill['data']['manifest'];
                                } elseif ($waybill && isset($waybill['data']['history']) && is_array($waybill['data']['history'])) {
                                    $tracking_rows = $waybill['data']['history'];
                                }
                                ?>
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
</div>

