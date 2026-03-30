<?php

namespace VelocityMarketplace\Modules\Product;

class ProductQuery
{
    public function normalize_filters($source = [])
    {
        if (!is_array($source)) {
            $source = [];
        }

        return [
            'search' => sanitize_text_field((string) ($source['search'] ?? $source['s'] ?? '')),
            'sort' => sanitize_key((string) ($source['sort'] ?? 'latest')),
            'cat' => (int) ($source['product_cat'] ?? $source['cat'] ?? 0),
            'author' => (int) ($source['author'] ?? 0),
            'label' => sanitize_key((string) ($source['product_label'] ?? $source['label'] ?? '')),
            'store_type' => sanitize_key((string) ($source['store_type'] ?? '')),
            'min_price' => $this->normalize_numeric_filter($source['min_price'] ?? ''),
            'max_price' => $this->normalize_numeric_filter($source['max_price'] ?? ''),
        ];
    }

    public function build_query_args($filters, $overrides = [])
    {
        $filters = $this->normalize_filters(is_array($filters) ? $filters : []);
        $overrides = is_array($overrides) ? $overrides : [];

        $args = [
            'post_type' => 'vmp_product',
            'post_status' => 'publish',
        ];

        if (!empty($overrides['paged'])) {
            $args['paged'] = max(1, (int) $overrides['paged']);
        }

        if (!empty($overrides['posts_per_page'])) {
            $args['posts_per_page'] = max(1, (int) $overrides['posts_per_page']);
        }

        if ($filters['search'] !== '') {
            $args['s'] = $filters['search'];
        }

        if ($filters['cat'] > 0) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'vmp_product_cat',
                    'field' => 'term_id',
                    'terms' => [$filters['cat']],
                ],
            ];
        }

        if ($filters['author'] > 0) {
            $args['author'] = $filters['author'];
        }

        if ($filters['store_type'] !== '') {
            $author_ids = $this->author_ids_for_store_type($filters['store_type']);
            if ($filters['author'] > 0) {
                if (!in_array($filters['author'], $author_ids, true)) {
                    $args['author__in'] = [0];
                }
            } else {
                $args['author__in'] = !empty($author_ids) ? $author_ids : [0];
            }
        }

        $meta_query = [];
        if ($filters['min_price'] !== '') {
            $meta_query[] = [
                'key' => 'price',
                'value' => (float) $filters['min_price'],
                'type' => 'NUMERIC',
                'compare' => '>=',
            ];
        }

        if ($filters['max_price'] !== '') {
            $meta_query[] = [
                'key' => 'price',
                'value' => (float) $filters['max_price'],
                'type' => 'NUMERIC',
                'compare' => '<=',
            ];
        }

        if ($filters['label'] !== '') {
            $meta_query[] = [
                'key' => 'label',
                'value' => $filters['label'],
                'compare' => '=',
            ];
        }

        if (!empty($meta_query)) {
            $args['meta_query'] = array_merge(['relation' => 'AND'], $meta_query);
        }

        if ($filters['sort'] === 'price_asc') {
            $args['meta_key'] = 'price';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'ASC';
        } elseif ($filters['sort'] === 'price_desc') {
            $args['meta_key'] = 'price';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
        } elseif ($filters['sort'] === 'popular') {
            $args['meta_key'] = 'vmp_hits';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
        } elseif ($filters['sort'] === 'name_asc') {
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
        } elseif ($filters['sort'] === 'name_desc') {
            $args['orderby'] = 'title';
            $args['order'] = 'DESC';
        } else {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }

        return $args;
    }

    public function apply_to_query(\WP_Query $query, $filters = [])
    {
        $args = $this->build_query_args($filters);

        foreach ($args as $key => $value) {
            $query->set($key, $value);
        }
    }

    public function label_options()
    {
        return [
            'new' => __('New', 'velocity-marketplace'),
            'limited' => __('Limited', 'velocity-marketplace'),
            'best' => __('Best Seller', 'velocity-marketplace'),
        ];
    }

    private function author_ids_for_store_type($store_type)
    {
        $store_type = sanitize_key((string) $store_type);
        if (!in_array($store_type, ['star_seller', 'regular'], true)) {
            return [];
        }

        $users = get_users([
            'fields' => ['ID'],
            'role__in' => ['vmp_member', 'administrator'],
            'number' => -1,
        ]);

        $author_ids = [];
        foreach ((array) $users as $user) {
            $user_id = isset($user->ID) ? (int) $user->ID : 0;
            if ($user_id <= 0) {
                continue;
            }

            $is_star = !empty(get_user_meta($user_id, 'vmp_is_star_seller', true));
            if ($store_type === 'star_seller' && $is_star) {
                $author_ids[] = $user_id;
            }
            if ($store_type === 'regular' && !$is_star) {
                $author_ids[] = $user_id;
            }
        }

        return array_values(array_unique($author_ids));
    }

    private function normalize_numeric_filter($value)
    {
        if ($value === null || $value === '') {
            return '';
        }

        return (float) $value;
    }
}
