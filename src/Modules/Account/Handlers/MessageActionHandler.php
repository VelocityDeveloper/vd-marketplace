<?php

namespace VelocityMarketplace\Modules\Account\Handlers;

use VelocityMarketplace\Modules\Message\MessageRepository;

class MessageActionHandler extends BaseActionHandler
{
    public function send()
    {
        $nonce = isset($_POST['vmp_message_nonce']) ? (string) wp_unslash($_POST['vmp_message_nonce']) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'vmp_message_send')) {
            $this->redirect_with(['vmp_error' => 'Form pesan tidak valid.', 'tab' => 'messages']);
        }

        $sender_id = get_current_user_id();
        $recipient_id = isset($_POST['recipient_id']) ? (int) $_POST['recipient_id'] : 0;
        $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        $message = isset($_POST['message']) ? (string) wp_unslash($_POST['message']) : '';

        $repo = new MessageRepository();
        if (!current_user_can('manage_options') && !$repo->can_contact($sender_id, $recipient_id)) {
            $this->redirect_with([
                'vmp_error' => 'Penerima pesan tidak valid untuk akun ini.',
                'tab' => 'messages',
            ]);
        }

        $message_id = $repo->send($sender_id, $recipient_id, $message, $order_id);
        if ($message_id <= 0) {
            $this->redirect_with([
                'vmp_error' => 'Pesan gagal dikirim. Pastikan penerima dan isi pesan valid.',
                'tab' => 'messages',
            ]);
        }

        $params = [
            'vmp_notice' => 'Pesan berhasil dikirim.',
            'tab' => 'messages',
        ];
        if ($recipient_id > 0) {
            $params['message_to'] = $recipient_id;
        }
        if ($order_id > 0) {
            $params['message_order'] = $order_id;
        }

        $this->redirect_with($params);
    }
}
