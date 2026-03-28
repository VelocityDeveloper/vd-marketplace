<?php

namespace VelocityMarketplace\Core;

use WP_REST_Request;
use WP_REST_Response;

class SettingsController
{
    public function register_routes()
    {
        register_rest_route('velocity-marketplace/v1', '/settings', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_settings'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'save_settings'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);
    }

    public function check_permission(WP_REST_Request $request)
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

        $nonce = $request->get_header('x_wp_nonce');
        if (!$nonce) {
            $nonce = $request->get_header('x-wp-nonce');
        }

        return is_string($nonce) && wp_verify_nonce($nonce, 'wp_rest');
    }

    public function get_settings(WP_REST_Request $request)
    {
        $service = new SettingsService();

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'settings' => $service->get_settings_payload(),
            ],
        ], 200);
    }

    public function save_settings(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }

        $service = new SettingsService();
        $settings = $service->save_settings($payload);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Pengaturan berhasil disimpan.',
            'data' => [
                'settings' => $settings,
            ],
        ], 200);
    }
}
