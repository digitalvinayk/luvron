<?php
/**
 * Plugin Name: Luvron — MOQ + Carton Stepping
 * Description: Forces dealers to order in master-carton multiples with a minimum of N cartons.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-moq')) return;

add_filter('woocommerce_quantity_input_args', function ($args, $product) {
    $moq_cartons = (int) get_post_meta($product->get_id(), '_moq_carton', true);
    $master      = (int) get_post_meta($product->get_id(), '_pack_master', true);

    if ($moq_cartons > 0 && $master > 0) {
        $min_pieces = $moq_cartons * $master;
        $args['min_value']   = $min_pieces;
        $args['step']        = $master;
        $args['input_value'] = max((int) ($args['input_value'] ?? 0), $min_pieces);
    }
    return $args;
}, 10, 2);

add_filter('woocommerce_add_to_cart_validation', function ($passed, $product_id, $quantity, $variation_id = 0) {
    $check_id = $variation_id ? $variation_id : $product_id;
    $moq      = (int) get_post_meta($check_id, '_moq_carton', true);
    $master   = (int) get_post_meta($check_id, '_pack_master', true);

    if ($moq > 0 && $master > 0) {
        $min = $moq * $master;
        if ($quantity < $min) {
            wc_add_notice(sprintf(
                'Minimum order is %d cartons (%d pieces). You added %d.',
                $moq, $min, $quantity
            ), 'error');
            return false;
        }
        if ($quantity % $master !== 0) {
            wc_add_notice(sprintf(
                'Order quantity must be in multiples of %d (one master carton).',
                $master
            ), 'error');
            return false;
        }
    }
    return $passed;
}, 10, 4);

add_action('woocommerce_after_add_to_cart_quantity', function () {
    global $product;
    if (!$product) return;
    $master = (int) get_post_meta($product->get_id(), '_pack_master', true);
    if ($master > 0) {
        printf(
            '<small class="luvron-pack-hint">Sold in cartons of %d pieces. Use the qty stepper to add cartons.</small>',
            $master
        );
    }
});
