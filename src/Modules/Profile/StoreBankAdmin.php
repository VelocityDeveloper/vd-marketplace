<?php

namespace VelocityMarketplace\Modules\Profile;

class StoreBankAdmin
{
    public function register()
    {
        add_action('show_user_profile', [$this, 'render_fields']);
        add_action('edit_user_profile', [$this, 'render_fields']);
        add_action('personal_options_update', [$this, 'save_fields']);
        add_action('edit_user_profile_update', [$this, 'save_fields']);
    }

    public function render_fields($user)
    {
        if (!$user instanceof \WP_User || !current_user_can('manage_options')) {
            return;
        }

        $is_seller = !empty(get_user_meta((int) $user->ID, '_store_is_seller', true));
        $bank_details = (string) get_user_meta((int) $user->ID, 'vmp_store_bank_details', true);
        ?>
        <h2><?php echo esc_html__('VD Marketplace: Seller', 'velocity-marketplace'); ?></h2>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="store_is_seller"><?php echo esc_html__('Aktif sebagai Seller', 'velocity-marketplace'); ?></label>
                    </th>
                    <td>
                        <?php wp_nonce_field('vmp_admin_seller_status', 'vmp_admin_seller_status_nonce'); ?>
                        <label for="store_is_seller">
                            <input type="checkbox" id="store_is_seller" name="store_is_seller" value="1" <?php checked($is_seller); ?>>
                            <?php echo esc_html__('Izinkan akun ini menjual produk.', 'velocity-marketplace'); ?>
                        </label>
                    </td>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2><?php echo esc_html__('VD Marketplace: Rekening Seller', 'velocity-marketplace'); ?></h2>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="vmp_store_bank_details"><?php echo esc_html__('Rekening Pencairan', 'velocity-marketplace'); ?></label>
                    </th>
                    <td>
                        <textarea id="vmp_store_bank_details" class="large-text" rows="4" readonly><?php echo esc_textarea($bank_details); ?></textarea>
                        <p class="description">
                            <?php echo esc_html__('Data ini diisi seller dari halaman Profil Toko dan hanya ditampilkan untuk admin.', 'velocity-marketplace'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public function save_fields($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0 || !current_user_can('manage_options')) {
            return;
        }

        $nonce = isset($_POST['vmp_admin_seller_status_nonce']) ? (string) wp_unslash($_POST['vmp_admin_seller_status_nonce']) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'vmp_admin_seller_status')) {
            return;
        }

        $is_seller = !empty($_POST['store_is_seller']);
        update_user_meta($user_id, '_store_is_seller', $is_seller ? 1 : 0);
    }
}
