<?php
/**
 * Plugin Name: Luvron — Quote Request
 * Description: Replaces "Add to Cart" with "Request Quote" for guests/pending users.
 *              Logs requests to wp_options + emails admin and applicant.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-quote')) return;

add_filter('woocommerce_loop_add_to_cart_link', function ($html, $product) {
    if (is_user_logged_in() && (current_user_can('dealer') || current_user_can('distributor'))) {
        return $html;
    }
    $url = esc_url(home_url('/request-quote/?pid=' . $product->get_id()));
    return sprintf(
        '<a href="%s" class="button luvron-quote-btn" data-product="%d">%s</a>',
        $url, $product->get_id(), esc_html__('Request Quote', 'luvron')
    );
}, 10, 2);

add_action('woocommerce_single_product_summary', function () {
    if (is_user_logged_in() && (current_user_can('dealer') || current_user_can('distributor'))) {
        return;
    }
    global $product;
    if (!$product) return;
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
    $url = esc_url(home_url('/request-quote/?pid=' . $product->get_id()));
    printf(
        '<a href="%s" class="button alt luvron-quote-btn">%s</a>',
        $url, esc_html__('Request Quote', 'luvron')
    );
}, 25);

add_shortcode('luvron_quote_form', function () {
    $pid     = isset($_GET['pid']) ? (int) $_GET['pid'] : 0;
    $product = $pid ? wc_get_product($pid) : null;
    $name    = $product ? $product->get_name() : '';

    if (!empty($_POST['luvron_quote_nonce'])
        && wp_verify_nonce($_POST['luvron_quote_nonce'], 'luvron_quote')) {

        $data = [
            'name'      => sanitize_text_field($_POST['name'] ?? ''),
            'business'  => sanitize_text_field($_POST['business'] ?? ''),
            'gstin'     => sanitize_text_field($_POST['gstin'] ?? ''),
            'phone'     => sanitize_text_field($_POST['phone'] ?? ''),
            'email'     => sanitize_email($_POST['email'] ?? ''),
            'state'     => sanitize_text_field($_POST['state'] ?? ''),
            'product'   => sanitize_text_field($_POST['product'] ?? $name),
            'qty'       => sanitize_text_field($_POST['qty'] ?? ''),
            'notes'     => sanitize_textarea_field($_POST['notes'] ?? ''),
            'submitted' => current_time('mysql'),
        ];

        $log = get_option('luvron_quote_log', []);
        $log[] = $data;
        update_option('luvron_quote_log', array_slice($log, -500));

        $admin_email = get_option('admin_email');
        $subject     = 'Quote request: ' . $data['business'] . ' — ' . $data['product'];
        $body        = "New quote request\n\n";
        foreach ($data as $k => $v) $body .= ucfirst($k) . ": $v\n";
        wp_mail($admin_email, $subject, $body);

        if ($data['email']) {
            wp_mail(
                $data['email'],
                'Luvron — Quote request received',
                "Hi {$data['name']},\n\nWe've received your quote request for {$data['product']}. "
                . "Our team will respond within 24 hours.\n\nThanks,\nTeam Luvron\n+91-9212389139"
            );
        }

        $wa_msg = "Quote request from {$data['business']} ({$data['phone']}): "
                . "{$data['product']} qty {$data['qty']}";
        $wa_url = 'https://wa.me/919212389139?text=' . rawurlencode($wa_msg);

        return '<div class="luvron-quote-success">'
             . '<h3>Thanks — your quote request is in.</h3>'
             . '<p>Our team will respond within 24 hours. For faster service, ping us on WhatsApp:</p>'
             . '<a class="button" target="_blank" href="' . esc_url($wa_url) . '">Send on WhatsApp</a>'
             . '</div>';
    }

    ob_start(); ?>
    <form method="post" class="luvron-quote-form">
        <?php wp_nonce_field('luvron_quote', 'luvron_quote_nonce'); ?>
        <input type="hidden" name="product" value="<?php echo esc_attr($name); ?>">
        <p><label>Your Name *<input required type="text" name="name"></label></p>
        <p><label>Business Name *<input required type="text" name="business"></label></p>
        <p><label>GSTIN<input type="text" name="gstin" pattern="[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}"></label></p>
        <p><label>State *<input required type="text" name="state"></label></p>
        <p><label>Phone *<input required type="tel" name="phone" pattern="[0-9+\- ]{10,15}"></label></p>
        <p><label>Email<input type="email" name="email"></label></p>
        <p><label>Product<input type="text" name="product_name" value="<?php echo esc_attr($name); ?>" readonly></label></p>
        <p><label>Quantity (cartons)<input type="number" name="qty" min="1"></label></p>
        <p><label>Notes<textarea name="notes" rows="3"></textarea></label></p>
        <p><button type="submit" class="button alt">Send Quote Request</button></p>
    </form>
    <?php
    return ob_get_clean();
});
