<?php

namespace VelocityMarketplace\Modules\Account\Handlers;

use VelocityMarketplace\Modules\Review\ReviewRepository;

class ReviewActionHandler extends BaseActionHandler
{
    public function submit()
    {
        $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $nonce = isset($_POST['vmp_review_nonce']) ? (string) wp_unslash($_POST['vmp_review_nonce']) : '';
        if ($order_id <= 0 || $product_id <= 0 || $nonce === '' || !wp_verify_nonce($nonce, 'vmp_review_' . $order_id . '_' . $product_id)) {
            $this->redirect_with(['vmp_error' => 'Form ulasan tidak valid.', 'tab' => 'orders']);
        }

        $repo = new ReviewRepository();
        $uploaded_image_ids = $this->handle_review_uploads($product_id);
        if (is_wp_error($uploaded_image_ids)) {
            $this->redirect_with([
                'vmp_error' => $uploaded_image_ids->get_error_message(),
                'tab' => 'orders',
                'invoice' => (string) get_post_meta($order_id, 'vmp_invoice', true),
            ]);
        }
        $review_id = $repo->save([
            'order_id' => $order_id,
            'product_id' => $product_id,
            'user_id' => get_current_user_id(),
            'rating' => isset($_POST['rating']) ? (int) $_POST['rating'] : 0,
            'title' => isset($_POST['review_title']) ? (string) wp_unslash($_POST['review_title']) : '',
            'content' => isset($_POST['review_content']) ? (string) wp_unslash($_POST['review_content']) : '',
            'image_ids' => $uploaded_image_ids,
        ]);

        $invoice = (string) get_post_meta($order_id, 'vmp_invoice', true);
        if ($review_id <= 0) {
            $this->redirect_with([
                'vmp_error' => 'Ulasan gagal disimpan. Pastikan order sudah selesai dan isi ulasan lengkap.',
                'tab' => 'orders',
                'invoice' => $invoice,
            ]);
        }

        $this->redirect_with([
            'vmp_notice' => 'Ulasan berhasil disimpan.',
            'tab' => 'orders',
            'invoice' => $invoice,
        ]);
    }

    private function handle_review_uploads($product_id)
    {
        $max_file_size = 500 * 1024;
        $product_id = (int) $product_id;
        if ($product_id <= 0 || empty($_FILES['review_images']) || empty($_FILES['review_images']['name']) || !is_array($_FILES['review_images']['name'])) {
            return null;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $uploaded_ids = [];
        $files = $_FILES['review_images'];
        $total_files = count((array) $files['name']);
        $has_selected_file = false;

        for ($index = 0; $index < $total_files; $index++) {
            if (count($uploaded_ids) >= 3) {
                break;
            }

            $name = isset($files['name'][$index]) ? (string) $files['name'][$index] : '';
            $tmp_name = isset($files['tmp_name'][$index]) ? (string) $files['tmp_name'][$index] : '';
            $error = isset($files['error'][$index]) ? (int) $files['error'][$index] : UPLOAD_ERR_NO_FILE;
            $size = isset($files['size'][$index]) ? (int) $files['size'][$index] : 0;

            if ($name === '' || $tmp_name === '' || $error !== UPLOAD_ERR_OK) {
                continue;
            }

            if ($size > $max_file_size) {
                return new \WP_Error(
                    'review_image_too_large',
                    'Ukuran tiap foto review maksimal 500 KB.'
                );
            }

            $has_selected_file = true;

            $_FILES['vmp_review_image_single'] = [
                'name' => $name,
                'type' => isset($files['type'][$index]) ? (string) $files['type'][$index] : '',
                'tmp_name' => $tmp_name,
                'error' => $error,
                'size' => $size,
            ];

            $attachment_id = media_handle_upload('vmp_review_image_single', $product_id);
            if (!is_wp_error($attachment_id) && $attachment_id) {
                $uploaded_ids[] = (int) $attachment_id;
            }

            unset($_FILES['vmp_review_image_single']);
        }

        return $has_selected_file ? $uploaded_ids : null;
    }
}
