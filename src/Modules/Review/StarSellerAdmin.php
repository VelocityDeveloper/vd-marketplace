<?php

namespace VelocityMarketplace\Modules\Review;

class StarSellerAdmin
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

        $summary = (new StarSellerService())->summary((int) $user->ID);
        $override = (string) get_user_meta((int) $user->ID, 'vmp_star_seller_override', true);
        if ($override === '') {
            $override = 'auto';
        }
        $note = (string) get_user_meta((int) $user->ID, 'vmp_star_seller_override_note', true);
        ?>
        <h2>Velocity Marketplace: Star Seller</h2>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">Status Otomatis</th>
                    <td>
                        <strong><?php echo esc_html(!empty($summary['auto_star_seller']) ? 'Lolos' : 'Belum Lolos'); ?></strong>
                        <p class="description">
                            Rating <?php echo esc_html(number_format((float) ($summary['rating_average'] ?? 0), 1, ',', '.')); ?>/5,
                            ulasan <?php echo esc_html((string) (int) ($summary['rating_count'] ?? 0)); ?>,
                            order selesai <?php echo esc_html((string) (int) ($summary['completed_orders'] ?? 0)); ?>,
                            cancel rate <?php echo esc_html(number_format((float) ($summary['cancel_rate'] ?? 0), 2, ',', '.')); ?>%
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="vmp_star_seller_override">Override Manual</label></th>
                    <td>
                        <select name="vmp_star_seller_override" id="vmp_star_seller_override">
                            <option value="auto" <?php selected($override, 'auto'); ?>>Ikuti Sistem Otomatis</option>
                            <option value="force_on" <?php selected($override, 'force_on'); ?>>Paksa Aktif</option>
                            <option value="force_off" <?php selected($override, 'force_off'); ?>>Paksa Nonaktif</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="vmp_star_seller_override_note">Catatan Admin</label></th>
                    <td>
                        <textarea name="vmp_star_seller_override_note" id="vmp_star_seller_override_note" class="large-text" rows="3"><?php echo esc_textarea($note); ?></textarea>
                        <p class="description">Catatan internal untuk override star seller.</p>
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

        $override = isset($_POST['vmp_star_seller_override']) ? sanitize_key((string) wp_unslash($_POST['vmp_star_seller_override'])) : 'auto';
        if (!in_array($override, ['auto', 'force_on', 'force_off'], true)) {
            $override = 'auto';
        }

        $note = isset($_POST['vmp_star_seller_override_note']) ? sanitize_textarea_field((string) wp_unslash($_POST['vmp_star_seller_override_note'])) : '';

        update_user_meta($user_id, 'vmp_star_seller_override', $override);
        update_user_meta($user_id, 'vmp_star_seller_override_note', $note);

        (new StarSellerService())->recalculate($user_id);
    }
}
