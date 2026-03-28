<?php

namespace VelocityMarketplace\Modules\Account\Handlers;

use VelocityMarketplace\Modules\Notification\NotificationRepository;

class NotificationActionHandler extends BaseActionHandler
{
    public function mark_read()
    {
        $nonce = isset($_POST['vmp_notification_nonce']) ? (string) wp_unslash($_POST['vmp_notification_nonce']) : '';
        $id = isset($_POST['notification_id']) ? sanitize_text_field((string) wp_unslash($_POST['notification_id'])) : '';
        if ($id === '' || $nonce === '' || !wp_verify_nonce($nonce, 'vmp_notification_action_' . $id)) {
            $this->redirect_with(['vmp_error' => 'Aksi notifikasi tidak valid.', 'tab' => 'notifications']);
        }

        $repo = new NotificationRepository();
        $repo->mark_read($id);

        $this->redirect_with([
            'vmp_notice' => 'Notifikasi ditandai sudah dibaca.',
            'tab' => 'notifications',
        ]);
    }

    public function mark_all()
    {
        $nonce = isset($_POST['vmp_notification_nonce']) ? (string) wp_unslash($_POST['vmp_notification_nonce']) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'vmp_notification_mark_all')) {
            $this->redirect_with(['vmp_error' => 'Aksi notifikasi tidak valid.', 'tab' => 'notifications']);
        }

        $repo = new NotificationRepository();
        $repo->mark_all_read();

        $this->redirect_with([
            'vmp_notice' => 'Semua notifikasi ditandai sudah dibaca.',
            'tab' => 'notifications',
        ]);
    }

    public function delete()
    {
        $nonce = isset($_POST['vmp_notification_nonce']) ? (string) wp_unslash($_POST['vmp_notification_nonce']) : '';
        $id = isset($_POST['notification_id']) ? sanitize_text_field((string) wp_unslash($_POST['notification_id'])) : '';
        if ($id === '' || $nonce === '' || !wp_verify_nonce($nonce, 'vmp_notification_action_' . $id)) {
            $this->redirect_with(['vmp_error' => 'Aksi notifikasi tidak valid.', 'tab' => 'notifications']);
        }

        $repo = new NotificationRepository();
        $repo->delete($id);

        $this->redirect_with([
            'vmp_notice' => 'Notifikasi dihapus.',
            'tab' => 'notifications',
        ]);
    }
}
