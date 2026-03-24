<?php
use VelocityMarketplace\Modules\Order\OrderData;
use VelocityMarketplace\Modules\Shipping\ShippingController;
    $transfer_captcha_html = \VelocityMarketplace\Modules\Captcha\CaptchaBridge::render();
    $invoice = isset($_GET['invoice']) ? sanitize_text_field((string) wp_unslash($_GET['invoice'])) : '';
    if ($invoice !== '') {
        $query = new \WP_Query([
            'post_type' => 'vmp_order',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => [
                'relation' => 'AND',
                ['key' => 'vmp_user_id', 'value' => (string) $current_user_id, 'compare' => '='],
                ['key' => 'vmp_invoice', 'value' => $invoice, 'compare' => '='],
            ],
        ]);
    } else {
        $query = new \WP_Query([
            'post_type' => 'vmp_order',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'meta_key' => 'vmp_user_id',
            'meta_value' => (string) $current_user_id,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }
    ?>
    <?php if (!$query->have_posts()) : ?>
        <div class="alert alert-info mb-0">Belum ada riwayat belanja.</div>
    <?php elseif ($invoice !== '') : ?>
        <?php
        $query->the_post();
        $order_id = get_the_ID();
        $invoice_meta = (string) get_post_meta($order_id, 'vmp_invoice', true);
        $items = get_post_meta($order_id, 'vmp_items', true);
        $total = (float) get_post_meta($order_id, 'vmp_total', true);
        $status = (string) get_post_meta($order_id, 'vmp_status', true);
        $payment = (string) get_post_meta($order_id, 'vmp_payment_method', true);
        $shipping = get_post_meta($order_id, 'vmp_shipping', true);
        $shipping_groups = OrderData::shipping_groups($order_id);
        $notes = (string) get_post_meta($order_id, 'vmp_notes', true);
        $transfer_proof_id = (int) get_post_meta($order_id, 'vmp_transfer_proof_id', true);
        $transfer_proof_url = $transfer_proof_id > 0 ? wp_get_attachment_url($transfer_proof_id) : '';
        $receipt_no = (string) get_post_meta($order_id, 'vmp_receipt_no', true);
        $receipt_courier = (string) get_post_meta($order_id, 'vmp_receipt_courier', true);
        if (!is_array($items)) {
            $items = [];
        }
        if (!is_array($shipping)) {
            $shipping = [];
        }
        $order_seller_ids = [];
        foreach ($items as $item) {
            $line_seller_id = isset($item['seller_id']) ? (int) $item['seller_id'] : 0;
            if ($line_seller_id > 0) {
                $order_seller_ids[] = $line_seller_id;
            }
        }
        $order_seller_ids = array_values(array_unique($order_seller_ids));
        ?>
        <div class="card border-0 shadow-sm mb-3"><div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div><strong>Invoice:</strong> <?php echo esc_html($invoice_meta); ?></div>
                    <div><strong>Tanggal:</strong> <?php echo esc_html(get_the_date('d-m-Y H:i', $order_id)); ?></div>
                    <div><strong>Status:</strong> <?php echo esc_html(OrderData::status_label($status)); ?></div>
                    <div><strong>Pembayaran:</strong> <?php echo esc_html($payment !== '' ? $payment : '-'); ?></div>
                </div>
                <div class="col-md-6">
                    <div><strong>Tujuan:</strong> <?php echo esc_html(trim((string) (($shipping['subdistrict_destination_name'] ?? '') . ', ' . ($shipping['city_destination_name'] ?? '') . ', ' . ($shipping['province_destination_name'] ?? '')), ', ')); ?></div>
                    <div><strong>Total Ongkir:</strong> <?php echo esc_html(number_format((float) ($shipping['cost'] ?? 0), 0, ',', '.')); ?></div>
                </div>
            </div>
            <hr>
            <div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>Produk</th><th class="text-end">Harga</th><th class="text-center">Qty</th><th class="text-end">Subtotal</th></tr></thead><tbody>
            <?php foreach ($items as $item) : $price = (float) ($item['price'] ?? 0); $qty = (int) ($item['qty'] ?? 0); $line_subtotal = isset($item['subtotal']) ? (float) $item['subtotal'] : ($price * $qty); ?>
                <tr><td><?php echo esc_html((string) ($item['title'] ?? '-')); ?></td><td class="text-end"><?php echo esc_html(number_format($price, 0, ',', '.')); ?></td><td class="text-center"><?php echo esc_html($qty); ?></td><td class="text-end"><?php echo esc_html(number_format($line_subtotal, 0, ',', '.')); ?></td></tr>
            <?php endforeach; ?>
            </tbody><tfoot><tr><th colspan="3" class="text-end">Total</th><th class="text-end text-danger"><?php echo esc_html(number_format($total, 0, ',', '.')); ?></th></tr></tfoot></table></div>
            <div class="small text-muted mt-2">Catatan: <?php echo esc_html($notes !== '' ? $notes : '-'); ?></div>
            <?php if ($transfer_proof_url) : ?><a href="<?php echo esc_url($transfer_proof_url); ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2">Lihat Bukti Transfer</a><?php endif; ?>
            <?php if (!empty($shipping_groups)) : ?>
                <div class="mt-3">
                    <h3 class="h6 mb-2">Pengiriman per Toko</h3>
                    <div class="row g-3">
                        <?php foreach ($shipping_groups as $shipping_group) : ?>
                            <?php
                            $group_seller_id = (int) ($shipping_group['seller_id'] ?? 0);
                            $group_seller = $group_seller_id > 0 ? get_userdata($group_seller_id) : null;
                            $group_seller_name = (string) ($shipping_group['seller_name'] ?? '');
                            if ($group_seller_name === '' && $group_seller && $group_seller->display_name !== '') {
                                $group_seller_name = $group_seller->display_name;
                            }
                            $group_receipt_no = (string) ($shipping_group['receipt_no'] ?? '');
                            $group_receipt_courier = (string) ($shipping_group['receipt_courier'] ?? ($shipping_group['courier'] ?? ''));
                            $waybill_data = null;
                            if ($group_receipt_no !== '' && $group_receipt_courier !== '') {
                                $maybe_waybill = ShippingController::fetch_waybill($group_receipt_no, $group_receipt_courier);
                                if (!is_wp_error($maybe_waybill) && is_array($maybe_waybill)) {
                                    $waybill_data = $maybe_waybill;
                                }
                            }
                            ?>
                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                        <div>
                                            <div class="fw-semibold"><?php echo esc_html($group_seller_name !== '' ? $group_seller_name : 'Toko'); ?></div>
                                            <div class="small text-muted">
                                                <?php echo esc_html((string) ($shipping_group['courier_name'] ?? strtoupper((string) ($shipping_group['courier'] ?? '-')))); ?>
                                                <?php if (!empty($shipping_group['service'])) : ?>
                                                    <?php echo esc_html(' - ' . (string) $shipping_group['service']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="small text-muted"><?php echo esc_html('Resi: ' . ($group_receipt_no !== '' ? $group_receipt_no : '-')); ?></div>
                                        </div>
                                        <div class="text-end">
                                            <div class="small text-muted">Ongkir</div>
                                            <div class="fw-semibold text-danger"><?php echo esc_html(number_format((float) ($shipping_group['cost'] ?? 0), 0, ',', '.')); ?></div>
                                        </div>
                                    </div>
                                    <?php if ($waybill_data && !empty($waybill_data['data'])) : ?>
                                        <div class="mt-3">
                                            <div class="small fw-semibold mb-2">Tracking Resi</div>
                                            <?php
                                            $tracking_rows = [];
                                            if (isset($waybill_data['data']['manifest']) && is_array($waybill_data['data']['manifest'])) {
                                                $tracking_rows = $waybill_data['data']['manifest'];
                                            } elseif (isset($waybill_data['data']['history']) && is_array($waybill_data['data']['history'])) {
                                                $tracking_rows = $waybill_data['data']['history'];
                                            }
                                            ?>
                                            <?php if (!empty($tracking_rows)) : ?>
                                                <div class="small">
                                                    <?php foreach ($tracking_rows as $tracking_row) : ?>
                                                        <div class="border-top py-2">
                                                            <div class="fw-semibold"><?php echo esc_html((string) ($tracking_row['manifest_description'] ?? $tracking_row['description'] ?? '-')); ?></div>
                                                            <div class="text-muted"><?php echo esc_html(trim((string) (($tracking_row['manifest_date'] ?? '') . ' ' . ($tracking_row['manifest_time'] ?? $tracking_row['date'] ?? '')))); ?></div>
                                                            <?php if (!empty($tracking_row['city_name'])) : ?><div class="text-muted"><?php echo esc_html((string) $tracking_row['city_name']); ?></div><?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else : ?>
                                                <div class="small text-muted">Data tracking belum tersedia.</div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!empty($order_seller_ids)) : ?>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <?php foreach ($order_seller_ids as $seller_contact_id) : $seller_user = get_userdata($seller_contact_id); ?>
                        <a href="<?php echo esc_url(add_query_arg(['tab' => 'messages', 'message_to' => $seller_contact_id, 'message_order' => $order_id])); ?>" class="btn btn-sm btn-outline-dark">
                            <?php echo esc_html('Pesan ' . ($seller_user && $seller_user->display_name !== '' ? $seller_user->display_name : 'Seller')); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div></div>

        <div class="card border-0 shadow-sm"><div class="card-body">
            <h3 class="h6 mb-2">Upload Bukti Transfer</h3>
            <form method="post" enctype="multipart/form-data" class="row g-2">
                <input type="hidden" name="vmp_action" value="buyer_upload_transfer">
                <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
                <?php wp_nonce_field('vmp_upload_transfer_' . $order_id, 'vmp_transfer_nonce'); ?>
                <div class="col-md-8"><input type="file" name="transfer_proof" class="form-control" accept="image/*,.pdf" required></div>
                <?php if ($transfer_captcha_html !== '') : ?><div class="col-12"><?php echo $transfer_captcha_html; ?></div><?php endif; ?>
                <div class="col-md-4"><button type="submit" class="btn btn-dark w-100">Kirim Bukti Transfer</button></div>
            </form>
        </div></div>
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <div class="table-responsive border rounded"><table class="table table-sm table-hover mb-0"><thead class="table-light"><tr><th>Invoice</th><th>Tanggal</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Aksi</th></tr></thead><tbody>
        <?php while ($query->have_posts()) : $query->the_post(); $order_id = get_the_ID(); $invoice_meta = (string) get_post_meta($order_id, 'vmp_invoice', true); $status = (string) get_post_meta($order_id, 'vmp_status', true); $total = (float) get_post_meta($order_id, 'vmp_total', true); ?>
            <tr><td><?php echo esc_html($invoice_meta); ?></td><td><?php echo esc_html(get_the_date('d-m-Y H:i', $order_id)); ?></td><td><?php echo esc_html(OrderData::status_label($status)); ?></td><td class="text-end"><?php echo esc_html(number_format($total, 0, ',', '.')); ?></td><td class="text-end"><a class="btn btn-sm btn-outline-dark" href="<?php echo esc_url(add_query_arg(['tab' => 'orders', 'invoice' => $invoice_meta])); ?>">Detail</a></td></tr>
        <?php endwhile; wp_reset_postdata(); ?>
        </tbody></table></div>
    <?php endif; ?>


