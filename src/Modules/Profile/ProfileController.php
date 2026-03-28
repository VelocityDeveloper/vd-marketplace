<?php

namespace VelocityMarketplace\Modules\Profile;

use VelocityMarketplace\Modules\Account\Account;
use WP_REST_Request;
use WP_REST_Response;

class ProfileController
{
    public function register_routes()
    {
        register_rest_route('velocity-marketplace/v1', '/profile/member', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_member_profile'],
                'permission_callback' => [$this, 'check_logged_in'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'save_member_profile'],
                'permission_callback' => [$this, 'check_rest_nonce'],
            ],
        ]);

        register_rest_route('velocity-marketplace/v1', '/profile/store', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_store_profile'],
                'permission_callback' => [$this, 'check_logged_in'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'save_store_profile'],
                'permission_callback' => [$this, 'check_rest_nonce'],
            ],
        ]);
    }

    public function check_logged_in()
    {
        return is_user_logged_in();
    }

    public function check_rest_nonce(WP_REST_Request $request)
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $nonce = $request->get_header('x_wp_nonce');
        if (!$nonce) {
            $nonce = $request->get_header('x-wp-nonce');
        }

        return is_string($nonce) && wp_verify_nonce($nonce, 'wp_rest');
    }

    public function get_member_profile(WP_REST_Request $request)
    {
        $service = new ProfileService();

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'profile' => $service->get_member_profile(get_current_user_id()),
            ],
        ], 200);
    }

    public function save_member_profile(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }

        $service = new ProfileService();
        $result = $service->save_member_profile(get_current_user_id(), $payload);
        if (is_wp_error($result)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => (string) ($result['message'] ?? 'Profil member berhasil diperbarui.'),
            'data' => [
                'profile' => isset($result['profile']) && is_array($result['profile']) ? $result['profile'] : [],
            ],
        ], 200);
    }

    public function get_store_profile(WP_REST_Request $request)
    {
        if (!Account::can_sell(get_current_user_id())) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Akses profil toko tidak tersedia untuk akun ini.',
            ], 403);
        }

        $service = new ProfileService();

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'profile' => $service->get_store_profile(get_current_user_id()),
            ],
        ], 200);
    }

    public function save_store_profile(WP_REST_Request $request)
    {
        if (!Account::can_sell(get_current_user_id())) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Akses profil toko tidak tersedia untuk akun ini.',
            ], 403);
        }

        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }

        $service = new ProfileService();
        $result = $service->save_store_profile(get_current_user_id(), $payload);
        if (is_wp_error($result)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => (string) ($result['message'] ?? 'Profil toko berhasil diperbarui.'),
            'data' => [
                'profile' => isset($result['profile']) && is_array($result['profile']) ? $result['profile'] : [],
            ],
        ], 200);
    }
}
