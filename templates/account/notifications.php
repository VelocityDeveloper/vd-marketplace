    <div class="card border-0 shadow-sm"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="h6 mb-0"><?php echo esc_html__('Notifikasi', 'velocity-marketplace'); ?></h3>
            <form method="post"><input type="hidden" name="vmp_action" value="notification_mark_all"><?php wp_nonce_field('vmp_notification_mark_all', 'vmp_notification_nonce'); ?><button type="submit" class="btn btn-sm btn-outline-dark"><?php echo esc_html__('Tandai Semua Sudah Dibaca', 'velocity-marketplace'); ?></button></form>
        </div>
        <?php if (empty($notifications)) : ?>
            <div class="small text-muted"><?php echo esc_html__('Belum ada notifikasi.', 'velocity-marketplace'); ?></div>
        <?php else : ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $row) : ?>
                    <div class="list-group-item px-0 <?php echo empty($row['is_read']) ? 'bg-light' : ''; ?>">
                        <div class="d-flex justify-content-between gap-2 flex-wrap">
                            <div>
                                <div class="fw-semibold"><?php echo esc_html($row['title']); ?></div>
                                <div class="small text-muted"><?php echo esc_html($row['message']); ?></div>
                                <div class="small text-muted"><?php echo esc_html(mysql2date('d-m-Y H:i', (string) $row['created_at'])); ?></div>
                                <?php if (!empty($row['url'])) : ?><a class="small" href="<?php echo esc_url($row['url']); ?>"><?php echo esc_html__('Lihat Detail', 'velocity-marketplace'); ?></a><?php endif; ?>
                            </div>
                            <div class="d-flex gap-1 align-items-start">
                                <?php if (empty($row['is_read'])) : ?>
                                    <form method="post"><input type="hidden" name="vmp_action" value="notification_mark_read"><input type="hidden" name="notification_id" value="<?php echo esc_attr($row['id']); ?>"><?php wp_nonce_field('vmp_notification_action_' . $row['id'], 'vmp_notification_nonce'); ?><button class="btn btn-sm btn-outline-success" type="submit"><?php echo esc_html__('Tandai Dibaca', 'velocity-marketplace'); ?></button></form>
                                <?php endif; ?>
                                <form method="post"><input type="hidden" name="vmp_action" value="notification_delete"><input type="hidden" name="notification_id" value="<?php echo esc_attr($row['id']); ?>"><?php wp_nonce_field('vmp_notification_action_' . $row['id'], 'vmp_notification_nonce'); ?><button class="btn btn-sm btn-outline-danger" type="submit"><?php echo esc_html__('Hapus', 'velocity-marketplace'); ?></button></form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div></div>
