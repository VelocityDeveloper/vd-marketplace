    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm"><div class="card-body">
                <h3 class="h6 mb-3">Kirim Pesan</h3>
                <?php if (empty($message_contacts)) : ?>
                    <div class="small text-muted mb-0">Kontak pesan belum tersedia. Kontak akan muncul setelah ada transaksi atau percakapan.</div>
                <?php else : ?>
                    <form method="post" class="row g-2">
                        <input type="hidden" name="vmp_action" value="message_send">
                        <?php wp_nonce_field('vmp_message_send', 'vmp_message_nonce'); ?>
                        <div class="col-12">
                            <label class="form-label">Kepada</label>
                            <select name="recipient_id" class="form-select" required>
                                <option value="">- Pilih Kontak -</option>
                                <?php foreach ($message_contacts as $contact) : ?>
                                    <option value="<?php echo esc_attr((string) $contact['id']); ?>" <?php selected($selected_message_to, (int) $contact['id']); ?>>
                                        <?php echo esc_html($contact['name'] . ($contact['role'] !== '' ? ' (' . $contact['role'] . ')' : '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Invoice Terkait</label>
                            <?php if ($selected_message_order > 0 && $selected_message_invoice !== '') : ?>
                                <input type="hidden" name="order_id" value="<?php echo esc_attr((string) $selected_message_order); ?>">
                                <input type="text" class="form-control" value="<?php echo esc_attr($selected_message_invoice); ?>" readonly>
                                <div class="form-text">Pesan ini ditautkan ke invoice yang dipilih dari detail order.</div>
                            <?php else : ?>
                                <input type="number" min="0" name="order_id" class="form-control" value="" placeholder="Opsional: ID order">
                                <div class="form-text">Opsional. Isi hanya jika kamu memang tahu ID order terkait.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Pesan</label>
                            <textarea name="message" class="form-control" rows="5" placeholder="Tulis pesan ke buyer atau seller..." required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-dark">Kirim Pesan</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div></div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Percakapan</h3>
                    <div class="small text-muted">Pesan masuk yang belum dibaca: <?php echo esc_html((string) $message_unread_count); ?></div>
                </div>
                <?php if (empty($messages)) : ?>
                    <div class="small text-muted">Belum ada pesan.</div>
                <?php else : ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($messages as $row) : ?>
                            <div class="list-group-item px-0 <?php echo !empty($row['incoming']) && empty($row['is_read']) ? 'bg-light' : ''; ?>">
                                <div class="d-flex justify-content-between gap-3 flex-wrap">
                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <span class="fw-semibold"><?php echo esc_html($row['partner_name']); ?></span>
                                            <span class="badge <?php echo !empty($row['incoming']) ? 'bg-primary' : 'bg-secondary'; ?>"><?php echo !empty($row['incoming']) ? 'Masuk' : 'Keluar'; ?></span>
                                            <?php if (!empty($row['order_invoice'])) : ?>
                                                <span class="badge bg-light text-dark border"><?php echo esc_html('Invoice: ' . $row['order_invoice']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mb-1"><?php echo wp_kses_post(wpautop((string) $row['message'])); ?></div>
                                        <div class="small text-muted"><?php echo esc_html(mysql2date('d-m-Y H:i', (string) $row['created_at'])); ?></div>
                                    </div>
                                    <div class="d-flex gap-1 align-items-start">
                                        <a href="<?php echo esc_url(add_query_arg(['tab' => 'messages', 'message_to' => (int) $row['partner_id'], 'message_order' => (int) $row['order_id']])); ?>" class="btn btn-sm btn-outline-dark">Balas</a>
                                        <?php if (!empty($row['incoming']) && empty($row['is_read'])) : ?>
                                            <form method="post">
                                                <input type="hidden" name="vmp_action" value="message_mark_read">
                                                <input type="hidden" name="message_id" value="<?php echo esc_attr((string) $row['id']); ?>">
                                                <?php wp_nonce_field('vmp_message_mark_read_' . $row['id'], 'vmp_message_nonce'); ?>
                                                <button type="submit" class="btn btn-sm btn-outline-success">Dibaca</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div></div>
        </div>
    </div>
