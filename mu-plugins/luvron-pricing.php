<?php
/**
 * Plugin Name: Luvron — Role-based Pricing
 * Description: Hides prices from guests; shows dealer/distributor prices to authenticated B2B users.
 *              Distributor falls back to (dealer × 0.93) if _price_distributor is empty.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-pricing')) return;

const LUVRON_DISTRIBUTOR_DISCOUNT = 0.07;

function luvron_get_role_price($product) {
    $product_id = $product->get_id();
    $dealer     = (float) get_post_meta($product_id, '_price_dealer', true);

    if ($dealer <= 0) {
        $dealer = (float) $product->get_regular_price('edit');
    }

    if (current_user_can('distributor')) {
        $distributor = (float) get_post_meta($product_id, '_price_distributor', true);
        if ($distributor <= 0 && $dealer > 0) {
            $distributor = round($dealer * (1 - LUVRON_DISTRIBUTOR_DISCOUNT));
        }
        return $distributor > 0 ? $distributor : $dealer;
    }

    if (current_user_can('dealer')) {
        return $dealer;
    }

    return null;
}

add_filter('woocommerce_product_get_price',           'luvron_filter_price', 10, 2);
add_filter('woocommerce_product_get_regular_price',   'luvron_filter_price', 10, 2);
add_filter('woocommerce_product_variation_get_price', 'luvron_filter_price', 10, 2);
add_filter('woocommerce_product_variation_get_regular_price', 'luvron_filter_price', 10, 2);

function luvron_filter_price($price, $product) {
    if (!is_user_logged_in() || current_user_can('pending_b2b')) {
        return '';
    }
    $role_price = luvron_get_role_price($product);
    return $role_price !== null ? $role_price : $price;
}

add_filter('woocommerce_get_price_html', function ($html, $product) {
    if (!is_user_logged_in() || current_user_can('pending_b2b')) {
        $url = home_url('/request-quote/?pid=' . $product->get_id());
        return '<a class="luvron-quote-cta button" href="' . esc_url($url) . '">'
             . esc_html__('Login or Request Quote', 'luvron') . '</a>';
    }
    return $html;
}, 10, 2);

add_filter('woocommerce_variation_prices', function ($prices, $product) {
    if (!is_user_logged_in() || current_user_can('pending_b2b')) return $prices;

    foreach (['price', 'regular_price', 'sale_price'] as $key) {
        if (!isset($prices[$key])) continue;
        foreach ($prices[$key] as $variation_id => $val) {
            $variation = wc_get_product($variation_id);
            if (!$variation) continue;
            $role = luvron_get_role_price($variation);
            if ($role !== null) {
                $prices[$key][$variation_id] = (string) $role;
            }
        }
    }
    return $prices;
}, 10, 2);

add_filter('woocommerce_get_variation_prices_hash', function ($hash) {
    $user = wp_get_current_user();
    $hash['luvron_role'] = $user->ID ? implode(',', (array) $user->roles) : 'guest';
    return $hash;
});
