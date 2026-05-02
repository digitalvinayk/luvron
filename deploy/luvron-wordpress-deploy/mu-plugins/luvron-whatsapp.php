<?php
/**
 * Plugin Name: Luvron — WhatsApp Order Confirm
 * Description: Adds a "Confirm on WhatsApp" button on the thank-you page and a floating WA chat button.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-whatsapp')) return;

const LUVRON_WA_NUMBER = '919212389139';

add_action('woocommerce_thankyou', function ($order_id) {
    if (!$order_id) return;
    $order = wc_get_order($order_id);
    if (!$order) return;

    $items_summary = [];
    foreach ($order->get_items() as $item) {
        $items_summary[] = $item->get_name() . ' × ' . $item->get_quantity();
    }
    $msg = sprintf(
        "Order #%s from %s\nItems: %s\nTotal: ₹%s\nPlease confirm dispatch.",
        $order->get_order_number(),
        $order->get_billing_company() ?: $order->get_formatted_billing_full_name(),
        implode('; ', $items_summary),
        number_format($order->get_total(), 2)
    );
    $url = 'https://wa.me/' . LUVRON_WA_NUMBER . '?text=' . rawurlencode($msg);
    echo '<div class="luvron-wa-confirm" style="margin:20px 0;padding:16px;background:#dcf8c6;border-radius:8px;">';
    echo '<p><strong>Confirm your order on WhatsApp for fastest dispatch:</strong></p>';
    echo '<a class="button" target="_blank" rel="noopener" href="' . esc_url($url) . '">'
       . 'Confirm on WhatsApp</a>';
    echo '</div>';
}, 5);

add_action('wp_footer', function () {
    if (is_admin()) return;
    $msg = 'Hi Luvron, I would like to enquire about your tricycles.';
    $url = 'https://wa.me/' . LUVRON_WA_NUMBER . '?text=' . rawurlencode($msg);
    ?>
    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener"
       class="luvron-wa-float" aria-label="Chat on WhatsApp"
       style="position:fixed;bottom:20px;right:20px;z-index:9999;background:#25D366;color:#fff;width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,0.2);font-size:28px;text-decoration:none;">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="white">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 2C6.486 2 2 6.486 2 12c0 1.776.464 3.515 1.346 5.034L2.05 22l5.062-1.327A9.953 9.953 0 0 0 12 22c5.514 0 10-4.486 10-10S17.514 2 12 2z"/>
        </svg>
    </a>
    <?php
});
