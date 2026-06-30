<?php

namespace VelocityMarketplace\Support;

class Contract
{
    const PRODUCT_POST_TYPE = 'store_product';
    const ORDER_POST_TYPE = 'store_order';
    const COUPON_POST_TYPE = 'store_coupon';
    const PRODUCT_TAXONOMY = 'store_product_cat';
    const BRAND_TAXONOMY = 'brand';

    public static function product_post_types()
    {
        return [self::PRODUCT_POST_TYPE];
    }

    public static function order_post_types()
    {
        return [self::ORDER_POST_TYPE];
    }

    public static function coupon_post_types()
    {
        return [self::COUPON_POST_TYPE];
    }

    public static function product_taxonomies()
    {
        return [self::PRODUCT_TAXONOMY, self::BRAND_TAXONOMY];
    }

    public static function brand_taxonomies()
    {
        return [self::BRAND_TAXONOMY];
    }

    public static function is_product_post_type($post_type)
    {
        return in_array((string) $post_type, self::product_post_types(), true);
    }

    public static function is_order_post_type($post_type)
    {
        return in_array((string) $post_type, self::order_post_types(), true);
    }

    public static function is_coupon_post_type($post_type)
    {
        return in_array((string) $post_type, self::coupon_post_types(), true);
    }

    public static function is_product($post_id)
    {
        return self::is_product_post_type(get_post_type((int) $post_id));
    }

    public static function is_order($post_id)
    {
        return self::is_order_post_type(get_post_type((int) $post_id));
    }
}
