<?php

namespace VelocityMarketplace\Modules\Account\Handlers;

use VelocityMarketplace\Modules\Account\Account;
use VelocityMarketplace\Modules\Profile\ProfileService;

class ProfileActionHandler extends BaseActionHandler
{
    public function save_store_profile()
    {
        if (!Account::can_manage_store_profile()) {
            $this->stay_with(['vmp_error' => 'Fitur ini hanya untuk member marketplace.', 'tab' => 'orders']);
            return;
        }

        $nonce = isset($_POST['vmp_store_profile_nonce']) ? (string) wp_unslash($_POST['vmp_store_profile_nonce']) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'vmp_store_profile')) {
            $this->stay_with(['vmp_error' => 'Nonce profil toko tidak valid.', 'tab' => 'seller_profile']);
            return;
        }

        $service = new ProfileService();
        $result = $service->save_store_profile(get_current_user_id(), wp_unslash($_POST));
        if (is_wp_error($result)) {
            $this->stay_with([
                'vmp_error' => $result->get_error_message(),
                'tab' => 'seller_profile',
            ]);
            return;
        }

        $this->stay_with([
            'vmp_notice' => (string) ($result['message'] ?? 'Profil toko berhasil diperbarui.'),
            'tab' => 'seller_profile',
        ]);
    }

    public function save_customer_profile()
    {
        $nonce = isset($_POST['vmp_customer_profile_nonce']) ? (string) wp_unslash($_POST['vmp_customer_profile_nonce']) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'vmp_customer_profile')) {
            $this->stay_with(['vmp_error' => 'Nonce profil customer tidak valid.', 'tab' => 'account_profile']);
            return;
        }

        $service = new ProfileService();
        $result = $service->save_member_profile(get_current_user_id(), wp_unslash($_POST));
        if (is_wp_error($result)) {
            $this->stay_with([
                'vmp_error' => $result->get_error_message(),
                'tab' => 'account_profile',
            ]);
            return;
        }

        $this->stay_with([
            'vmp_notice' => (string) ($result['message'] ?? 'Profil member berhasil diperbarui.'),
            'tab' => 'account_profile',
        ]);
    }
}
