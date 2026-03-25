<div class="row g-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h3 class="h6 mb-3">Kontak Pesan</h3>
                <?php if (empty($message_contacts)) : ?>
                    <div class="small text-muted mb-0">Belum ada kontak pesan. Mulai chat dari detail order atau tombol pesan pada halaman produk.</div>
                <?php else : ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($message_contacts as $contact) : ?>
                            <?php
                            $contact_id = (int) ($contact['id'] ?? 0);
                            $is_active = $selected_message_to === $contact_id;
                            $thread_url = add_query_arg([
                                'tab' => 'messages',
                                'message_to' => $contact_id,
                            ]);
                            ?>
                            <a href="<?php echo esc_url($thread_url); ?>" class="list-group-item list-group-item-action border mb-2 px-2<?php echo $is_active ? ' active' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="min-w-0">
                                        <div class="fw-semibold"><?php echo esc_html((string) ($contact['name'] ?? 'User')); ?></div>
                                        <?php if (!empty($contact['role'])) : ?>
                                            <div class="small <?php echo $is_active ? 'text-white-50' : 'text-muted'; ?>"><?php echo esc_html((string) $contact['role']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($contact['last_message'])) : ?>
                                            <div class="small mt-1 <?php echo $is_active ? 'text-white-50' : 'text-muted'; ?>"><?php echo esc_html((string) $contact['last_message']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($contact['last_order_invoice'])) : ?>
                                            <div class="small mt-1">
                                                <span class="badge <?php echo $is_active ? 'bg-light text-dark' : 'bg-light text-dark border'; ?>">
                                                    <?php echo esc_html('Invoice: ' . (string) $contact['last_order_invoice']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-end">
                                        <?php if (!empty($contact['unread_count'])) : ?>
                                            <span class="badge bg-danger"><?php echo esc_html('Belum dibaca ' . (int) $contact['unread_count']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($contact['last_created_at'])) : ?>
                                            <div class="small mt-1 <?php echo $is_active ? 'text-white-50' : 'text-muted'; ?>">
                                                <?php echo esc_html(mysql2date('d-m-Y H:i', (string) $contact['last_created_at'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <?php if (!$selected_message_contact) : ?>
                    <div class="small text-muted mb-0">Pilih kontak yang ingin diajak chat. Kontak akan tersedia setelah ada transaksi atau tombol pesan dari produk/order dibuka.</div>
                <?php else : ?>
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
                        <div>
                            <h3 class="h6 mb-0"><?php echo esc_html((string) ($selected_message_contact['name'] ?? 'User')); ?></h3>
                            <div class="small text-muted">
                                <?php echo esc_html((string) ($selected_message_contact['role'] ?? '')); ?>
                                <?php if ($selected_message_invoice !== '') : ?>
                                    <span> | </span>
                                    <span><?php echo esc_html('Invoice: ' . $selected_message_invoice); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded p-3 bg-light-subtle mb-3" data-message-thread style="max-height:540px; overflow:auto;">
                        <?php if (empty($message_thread)) : ?>
                            <div class="small text-muted">Belum ada isi percakapan. Kirim pesan pertama dari kotak di bawah.</div>
                        <?php else : ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($message_thread as $row) : ?>
                                    <div class="d-flex <?php echo !empty($row['incoming']) ? 'justify-content-start' : 'justify-content-end'; ?>">
                                        <div class="border rounded px-3 py-2 bg-white" style="max-width:82%;">
                                            <div class="small fw-semibold mb-1">
                                                <?php echo esc_html(!empty($row['incoming']) ? (string) ($row['partner_name'] ?? 'User') : 'Saya'); ?>
                                            </div>
                                            <div><?php echo wp_kses_post(wpautop((string) $row['message'])); ?></div>
                                            <div class="small text-muted mt-2">
                                                <?php echo esc_html(mysql2date('d-m-Y H:i', (string) $row['created_at'])); ?>
                                                <?php if (!empty($row['order_invoice'])) : ?>
                                                    <span> | </span>
                                                    <span><?php echo esc_html('Invoice: ' . (string) $row['order_invoice']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="border rounded p-3">
                        <h4 class="h6 mb-3">Kirim Pesan</h4>
                        <form method="post" class="row g-2">
                            <input type="hidden" name="vmp_action" value="message_send">
                            <input type="hidden" name="recipient_id" value="<?php echo esc_attr((string) $selected_message_to); ?>">
                            <input type="hidden" name="order_id" value="<?php echo esc_attr((string) $selected_message_order); ?>">
                            <?php wp_nonce_field('vmp_message_send', 'vmp_message_nonce'); ?>
                            <?php if ($selected_message_invoice !== '') : ?>
                                <div class="col-12">
                                    <div class="form-text mt-0"><?php echo esc_html('Pesan ini ditautkan ke invoice ' . $selected_message_invoice . '.'); ?></div>
                                </div>
                            <?php endif; ?>
                            <div class="col-12">
                                <textarea name="message" class="form-control" rows="5" placeholder="Tulis pesan..." required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-dark">Kirim Pesan</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
