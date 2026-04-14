<?php

namespace VelocityMarketplace\Modules\Product;

use VelocityMarketplace\Support\Contract;

class PremiumBadge
{
    public static function is_premium($post_id = 0)
    {
        $post_id = self::resolve_product_id($post_id);
        if ($post_id <= 0) {
            return false;
        }

        return (int) get_post_meta($post_id, 'is_premium', true) === 1;
    }

    public static function render($args = [])
    {
        $args = is_array($args) ? $args : [];

        $post_id = isset($args['post_id']) ? (int) $args['post_id'] : 0;
        $post_id = self::resolve_product_id($post_id);
        if ($post_id <= 0 || !self::is_premium($post_id)) {
            return '';
        }

        $text = array_key_exists('text', $args)
            ? (string) $args['text']
            : __('Premium', 'velocity-marketplace');
        $class = array_key_exists('class', $args)
            ? trim((string) $args['class'])
            : 'badge bg-warning text-dark';
        $tag = array_key_exists('tag', $args)
            ? sanitize_key((string) $args['tag'])
            : 'span';

        if (!in_array($tag, ['span', 'div', 'strong', 'small'], true)) {
            $tag = 'span';
        }

        return sprintf(
            '<%1$s%2$s>%3$s</%1$s>',
            esc_html($tag),
            $class !== '' ? ' class="' . esc_attr($class) . '"' : '',
            esc_html($text)
        );
    }

    private static function resolve_product_id($post_id = 0)
    {
        $post_id = (int) $post_id;
        if ($post_id > 0 && get_post_type($post_id) === Contract::PRODUCT_POST_TYPE) {
            return $post_id;
        }

        $current_id = get_the_ID();
        if ($current_id > 0 && get_post_type($current_id) === Contract::PRODUCT_POST_TYPE) {
            return (int) $current_id;
        }

        return 0;
    }
}
