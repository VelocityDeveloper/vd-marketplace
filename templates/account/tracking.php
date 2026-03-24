<?php
use VelocityMarketplace\Modules\Order\OrderData;
use VelocityMarketplace\Modules\Shipping\ShippingController;
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
    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <form method="get" class="row g-2">
            <input type="hidden" name="tab" value="tracking">
            <div class="col-md-8"><label class="form-label">Kode Invoice</label><input type="text" name="invoice" class="form-control" value="<?php echo esc_attr($invoice); ?>" placeholder="Contoh: VMP-20260304-123456" required></div>
            <div class="col-md-4 d-flex align-items-end"><button class="btn btn-dark w-100" type="submit">Lacak</button></div>
        </form>
    </div></div>

    <?php if ($invoice !== '' && !$tracking_order) : ?>
        <div class="alert alert-warning mb-0">Invoice tidak ditemukan atau kamu tidak punya akses.</div>
    <?php elseif ($tracking_order) : ?>
        <?php
        $tracking_id = (int) $tracking_order->ID;
        $tracking_status = (string) get_post_meta($tracking_id, 'vmp_status', true);
        $tracking_payment = (string) get_post_meta($tracking_id, 'vmp_payment_method', true);
        $tracking_shipping = get_post_meta($tracking_id, 'vmp_shipping', true);
        $tracking_groups = OrderData::shipping_groups($tracking_id);
        if (!is_array($tracking_shipping)) {
            $tracking_shipping = [];
        }
        ?>
        <div class="card border-0 shadow-sm"><div class="card-body"><div class="row g-3"><div class="col-md-6"><div><strong>Invoice:</strong> <?php echo esc_html($invoice); ?></div><div><strong>Status:</strong> <?php echo esc_html(OrderData::status_label($tracking_status)); ?></div><div><strong>Metode Bayar:</strong> <?php echo esc_html($tracking_payment !== '' ? $tracking_payment : '-'); ?></div></div><div class="col-md-6"><div><strong>Tujuan:</strong> <?php echo esc_html(trim((string) (($tracking_shipping['subdistrict_destination_name'] ?? '') . ', ' . ($tracking_shipping['city_destination_name'] ?? '') . ', ' . ($tracking_shipping['province_destination_name'] ?? '')), ', ')); ?></div><div><strong>Total Ongkir:</strong> <?php echo esc_html(number_format((float) ($tracking_shipping['cost'] ?? 0), 0, ',', '.')); ?></div></div></div>
            <?php if (!empty($tracking_groups)) : ?>
                <div class="mt-3">
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
                            <div class="fw-semibold"><?php echo esc_html((string) ($group['seller_name'] ?? 'Toko')); ?></div>
                            <div><strong>Kurir:</strong> <?php echo esc_html($courier !== '' ? $courier : '-'); ?></div>
                            <div><strong>Layanan:</strong> <?php echo esc_html((string) ($group['service'] ?? '-')); ?></div>
                            <div><strong>No Resi:</strong> <?php echo esc_html($receipt !== '' ? $receipt : '-'); ?></div>
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
        </div></div>
    <?php endif; ?>


