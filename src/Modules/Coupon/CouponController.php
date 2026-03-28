<?php

namespace VelocityMarketplace\Modules\Coupon;

use VelocityMarketplace\Modules\Cart\CartRepository;
use WP_REST_Request;
use WP_REST_Response;

class CouponController
{
    public function register_routes()
    {
        register_rest_route('velocity-marketplace/v1', '/coupon/preview', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'preview'],
                'permission_callback' => '__return_true',
            ],
        ]);
    }

    public function preview(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = [];
        }

        $code = sanitize_text_field((string) ($payload['code'] ?? ''));
        if ($code === '') {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Kode kupon wajib diisi.',
            ], 400);
        }

        $cart = (new CartRepository())->get_cart_data();
        $subtotal = isset($cart['total']) ? (float) $cart['total'] : 0;
        $shipping_total = isset($payload['shipping_total']) ? (float) $payload['shipping_total'] : 0;
        $coupon = (new CouponService())->preview($code, $subtotal, $shipping_total);
        if (is_wp_error($coupon)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $coupon->get_error_message(),
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'id' => (int) $coupon['id'],
                'code' => (string) $coupon['code'],
                'scope' => (string) ($coupon['scope'] ?? 'product'),
                'type' => (string) $coupon['type'],
                'discount' => (float) $coupon['discount'],
                'product_discount' => (float) ($coupon['product_discount'] ?? 0),
                'shipping_discount' => (float) ($coupon['shipping_discount'] ?? 0),
                'min_purchase' => (float) $coupon['min_purchase'],
            ],
        ], 200);
    }
}
