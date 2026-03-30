<?php

namespace VelocityMarketplace\Modules\Product;

use VelocityMarketplace\Modules\Product\ProductData;
use WP_REST_Request;
use WP_REST_Response;

class ProductController
{
    public function register_routes()
    {
        register_rest_route('velocity-marketplace/v1', '/products', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_products'],
                'permission_callback' => '__return_true',
            ],
        ]);

        register_rest_route('velocity-marketplace/v1', '/products/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_product'],
                'permission_callback' => '__return_true',
            ],
        ]);
    }

    public function get_products(WP_REST_Request $request)
    {
        $product_query = new ProductQuery();
        $page = max(1, (int) $request->get_param('page'));
        $per_page = (int) $request->get_param('per_page');
        if ($per_page <= 0) {
            $per_page = 12;
        }
        $per_page = min(36, $per_page);

        $filters = $product_query->normalize_filters($request->get_params());
        $sort = (string) ($filters['sort'] ?? 'latest');
        $args = $product_query->build_query_args($filters, [
            'paged' => $page,
            'posts_per_page' => $per_page,
        ]);

        $query = new \WP_Query($args);
        $items = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $item = ProductData::map_post(get_the_ID());
                if ($item) {
                    $items[] = $item;
                }
            }
            wp_reset_postdata();
        }

        if ($sort === '' || $sort === 'latest') {
            usort($items, static function ($left, $right) {
                $left_premium = !empty($left['is_premium']) ? 1 : 0;
                $right_premium = !empty($right['is_premium']) ? 1 : 0;

                if ($left_premium !== $right_premium) {
                    return $right_premium <=> $left_premium;
                }

                return ((int) ($right['id'] ?? 0)) <=> ((int) ($left['id'] ?? 0));
            });
        }

        return new WP_REST_Response([
            'items' => $items,
            'page' => $page,
            'pages' => (int) $query->max_num_pages,
            'total' => (int) $query->found_posts,
        ], 200);
    }

    public function get_product(WP_REST_Request $request)
    {
        $id = (int) $request->get_param('id');
        $item = ProductData::map_post($id);
        if (!$item) {
            return new WP_REST_Response([
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        $item['content'] = apply_filters('the_content', get_post_field('post_content', $id));
        return new WP_REST_Response($item, 200);
    }
}


