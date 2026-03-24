<?php
use VelocityMarketplace\Modules\Order\OrderData;
use VelocityMarketplace\Modules\Shipping\ShippingController;
?>
<?php $seller_order_ids = OrderData::seller_orders_query($current_user_id, 120); ?>
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <h3 class="h6 mb-2">Status Seller</h3>
                <div class="mb-2">Label: <?php echo $is_star_seller ? '<span class="badge bg-warning text-dark">Star Seller</span>' : '<span class="badge bg-secondary">Seller Biasa</span>'; ?></div>
                <div class="small text-muted">Order masuk: <strong><?php echo esc_html(count($seller_order_ids)); ?></strong></div>
                <?php if (!$profile_complete) : ?><div class="alert alert-warning py-2 mt-2 mb-0">Lengkapi profil toko dulu sebelum tambah produk baru.</div><?php endif; ?>
            </div></div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <h3 class="h6 mb-2">Order Masuk</h3>
                <?php if (empty($seller_order_ids)) : ?>
                    <div class="small text-muted">Belum ada order masuk.</div>
                <?php else : ?>
                    <div class="accordion" id="vmpSellerOrders">
                        <?php foreach ($seller_order_ids as $idx => $order_id) :
                            $invoice_meta = (string) get_post_meta($order_id, 'vmp_invoice', true);
                            $status = (string) get_post_meta($order_id, 'vmp_status', true);
                            $customer = get_post_meta($order_id, 'vmp_customer', true);
                            $seller_items = OrderData::seller_items($order_id, $current_user_id);
                            $seller_total = OrderData::seller_total($order_id, $current_user_id);
                            $seller_shipping = OrderData::seller_shipping_group($order_id, $current_user_id);
                            $transfer_proof_id = (int) get_post_meta($order_id, 'vmp_transfer_proof_id', true);
                            $transfer_proof_url = $transfer_proof_id > 0 ? wp_get_attachment_url($transfer_proof_id) : '';
                            $receipt_no = (string) (($seller_shipping['receipt_no'] ?? '') ?: get_post_meta($order_id, 'vmp_receipt_no', true));
                            $receipt_courier = (string) (($seller_shipping['receipt_courier'] ?? '') ?: ($seller_shipping['courier'] ?? get_post_meta($order_id, 'vmp_receipt_courier', true)));
                            $waybill_data = null;
                            if ($receipt_no !== '' && $receipt_courier !== '') {
                                $maybe_waybill = ShippingController::fetch_waybill($receipt_no, $receipt_courier);
                                if (!is_wp_error($maybe_waybill) && is_array($maybe_waybill)) {
                                    $waybill_data = $maybe_waybill;
                                }
                            }
                            if (!is_array($customer)) {
                                $customer = [];
                            }
                            ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="vmpOrderHeading<?php echo esc_attr($order_id); ?>">
                                    <button class="accordion-button <?php echo $idx > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#vmpOrderCollapse<?php echo esc_attr($order_id); ?>" aria-expanded="<?php echo $idx === 0 ? 'true' : 'false'; ?>">
                                        <?php echo esc_html($invoice_meta); ?> | <?php echo esc_html(OrderData::status_label($status)); ?> | <?php echo esc_html($money($seller_total)); ?>
                                    </button>
                                </h2>
                                <div id="vmpOrderCollapse<?php echo esc_attr($order_id); ?>" class="accordion-collapse collapse <?php echo $idx === 0 ? 'show' : ''; ?>" data-bs-parent="#vmpSellerOrders">
                                    <div class="accordion-body">
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div><strong>Pembeli:</strong> <?php echo esc_html($customer['name'] ?? '-'); ?></div>
                                                <div><strong>Telepon:</strong> <?php echo esc_html($customer['phone'] ?? '-'); ?></div>
                                                <div><strong>Alamat:</strong> <?php echo esc_html($customer['address'] ?? '-'); ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div><strong>Kurir:</strong> <?php echo esc_html($receipt_courier !== '' ? $receipt_courier : '-'); ?></div>
                                                <div><strong>No Resi:</strong> <?php echo esc_html($receipt_no !== '' ? $receipt_no : '-'); ?></div>
                                                <div><strong>Ongkir Toko Ini:</strong> <?php echo esc_html($money((float) ($seller_shipping['cost'] ?? 0))); ?></div>
                                                <?php if ($transfer_proof_url) : ?>
                                                    <a href="<?php echo esc_url($transfer_proof_url); ?>" class="btn btn-sm btn-outline-primary mt-2" target="_blank">Lihat Bukti Transfer</a>
                                                <?php else : ?>
                                                    <div class="small text-muted mt-1">Bukti transfer belum diupload.</div>
                                                <?php endif; ?>
                                                <?php $buyer_contact_id = isset($customer['user_id']) ? (int) $customer['user_id'] : (int) get_post_meta($order_id, 'vmp_user_id', true); ?>
                                                <?php if ($buyer_contact_id > 0) : ?>
                                                    <div class="mt-2">
                                                        <a href="<?php echo esc_url(add_query_arg(['tab' => 'messages', 'message_to' => $buyer_contact_id, 'message_order' => $order_id])); ?>" class="btn btn-sm btn-outline-dark">Kirim Pesan ke Pembeli</a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php
                                        $tracking_rows = [];
                                        if ($waybill_data && isset($waybill_data['data']['manifest']) && is_array($waybill_data['data']['manifest'])) {
                                            $tracking_rows = $waybill_data['data']['manifest'];
                                        } elseif ($waybill_data && isset($waybill_data['data']['history']) && is_array($waybill_data['data']['history'])) {
                                            $tracking_rows = $waybill_data['data']['history'];
                                        }
                                        ?>
                                        <?php if (!empty($tracking_rows)) : ?>
                                            <div class="border rounded p-3 mb-3">
                                                <div class="fw-semibold mb-2">Tracking Resi</div>
                                                <?php foreach ($tracking_rows as $tracking_row) : ?>
                                                    <div class="border-top py-2">
                                                        <div class="fw-semibold"><?php echo esc_html((string) ($tracking_row['manifest_description'] ?? $tracking_row['description'] ?? '-')); ?></div>
                                                        <div class="small text-muted"><?php echo esc_html(trim((string) (($tracking_row['manifest_date'] ?? '') . ' ' . ($tracking_row['manifest_time'] ?? $tracking_row['date'] ?? '')))); ?></div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="table-responsive mb-3"><table class="table table-sm table-hover mb-0"><thead class="table-light"><tr><th>Produk</th><th class="text-center">Qty</th><th class="text-end">Subtotal</th></tr></thead><tbody>
                                        <?php foreach ($seller_items as $line) : ?>
                                            <tr><td><?php echo esc_html(isset($line['title']) ? (string) $line['title'] : '-'); ?></td><td class="text-center"><?php echo esc_html((string) ((int) ($line['qty'] ?? 0))); ?></td><td class="text-end"><?php echo esc_html($money((float) ($line['subtotal'] ?? 0))); ?></td></tr>
                                        <?php endforeach; ?>
                                        </tbody></table></div>

                                        <form method="post" class="row g-2">
                                            <input type="hidden" name="vmp_action" value="seller_update_order">
                                            <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
                                            <?php wp_nonce_field('vmp_seller_order_' . $order_id, 'vmp_seller_order_nonce'); ?>
                                            <div class="col-md-3"><label class="form-label">Status</label><select name="order_status" class="form-select form-select-sm"><?php foreach ($status_labels as $status_key => $status_text) : ?><option value="<?php echo esc_attr($status_key); ?>" <?php selected($status, $status_key); ?>><?php echo esc_html($status_text); ?></option><?php endforeach; ?></select></div>
                                            <div class="col-md-3"><label class="form-label">Kurir</label><input type="text" name="receipt_courier" class="form-control form-control-sm" value="<?php echo esc_attr($receipt_courier); ?>" placeholder="JNE/SICEPAT/JNT"></div>
                                            <div class="col-md-3"><label class="form-label">No Resi</label><input type="text" name="receipt_no" class="form-control form-control-sm" value="<?php echo esc_attr($receipt_no); ?>" placeholder="Nomor resi"></div>
                                            <div class="col-md-3"><label class="form-label">Catatan</label><input type="text" name="seller_note" class="form-control form-control-sm" placeholder="Catatan untuk pembeli"></div>
                                            <div class="col-12 text-end"><button type="submit" class="btn btn-sm btn-dark">Simpan Update Order</button></div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div></div>
        </div>
    </div>


