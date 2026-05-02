<?php
/**
 * Plugin Name: Luvron — WhatsApp Business API (Cloud API)
 * Description: Automated WhatsApp messages via Meta Cloud API on dealer signup, order placed,
 *              order shipped, and order completed. Uses pre-approved message templates.
 * Version: 1.0.0
 *
 * Setup:
 *   1. Create Meta Business app: https://developers.facebook.com/apps
 *   2. Add WhatsApp product → get System User token + Phone Number ID
 *   3. Submit message templates for approval (see TEMPLATES below)
 *   4. WP Admin → Settings → Luvron WhatsApp → enter token, phone-id, templates
 *
 * Templates to register in Meta Business Manager (utility category, English):
 *   - dealer_application_received   ({{1}}=name, {{2}}=expected hours)
 *   - dealer_approved               ({{1}}=name, {{2}}=role label, {{3}}=login url)
 *   - order_received                ({{1}}=name, {{2}}=order#, {{3}}=total, {{4}}=invoice url)
 *   - order_shipped                 ({{1}}=name, {{2}}=order#, {{3}}=tracking note)
 *   - admin_new_order               ({{1}}=business, {{2}}=order#, {{3}}=total, {{4}}=phone)
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-whatsapp-api')) return;

const LUVRON_WA_OPT = 'luvron_wa_settings';
const LUVRON_WA_API = 'https://graph.facebook.com/v22.0/';

add_action('admin_menu', function () {
    add_options_page('Luvron WhatsApp', 'Luvron WhatsApp', 'manage_options',
        'luvron-wa', 'luvron_wa_settings_page');
});

add_action('admin_init', function () {
    register_setting('luvron_wa_group', LUVRON_WA_OPT, [
        'sanitize_callback' => function ($v) {
            return [
                'enabled'        => !empty($v['enabled']) ? 1 : 0,
                'token'          => sanitize_text_field($v['token'] ?? ''),
                'phone_id'       => sanitize_text_field($v['phone_id'] ?? ''),
                'admin_phone'    => sanitize_text_field($v['admin_phone'] ?? '919212389139'),
                'lang'           => sanitize_text_field($v['lang'] ?? 'en'),
                'template_apply' => sanitize_text_field($v['template_apply'] ?? 'dealer_application_received'),
                'template_approve' => sanitize_text_field($v['template_approve'] ?? 'dealer_approved'),
                'template_order' => sanitize_text_field($v['template_order'] ?? 'order_received'),
                'template_shipped' => sanitize_text_field($v['template_shipped'] ?? 'order_shipped'),
                'template_admin' => sanitize_text_field($v['template_admin'] ?? 'admin_new_order'),
            ];
        },
    ]);
});

function luvron_wa_settings_page() {
    $opt = get_option(LUVRON_WA_OPT, []);
    ?>
    <div class="wrap">
        <h1>Luvron WhatsApp Business API</h1>
        <p>Uses Meta Cloud API. Requires a verified WhatsApp Business account and approved message templates.</p>

        <form method="post" action="options.php">
            <?php settings_fields('luvron_wa_group'); ?>
            <table class="form-table">
                <tr><th>Enable</th><td>
                    <label><input type="checkbox" name="<?php echo LUVRON_WA_OPT; ?>[enabled]" value="1" <?php checked(!empty($opt['enabled'])); ?>> Enable WhatsApp notifications</label>
                </td></tr>
                <tr><th>Access Token</th><td>
                    <input type="password" class="regular-text" name="<?php echo LUVRON_WA_OPT; ?>[token]" value="<?php echo esc_attr($opt['token'] ?? ''); ?>" />
                    <p class="description">System User token from Meta Business Settings.</p>
                </td></tr>
                <tr><th>Phone Number ID</th><td>
                    <input type="text" class="regular-text" name="<?php echo LUVRON_WA_OPT; ?>[phone_id]" value="<?php echo esc_attr($opt['phone_id'] ?? ''); ?>" />
                    <p class="description">Numeric ID from WhatsApp → API Setup.</p>
                </td></tr>
                <tr><th>Admin WhatsApp Number</th><td>
                    <input type="text" class="regular-text" name="<?php echo LUVRON_WA_OPT; ?>[admin_phone]" value="<?php echo esc_attr($opt['admin_phone'] ?? '919212389139'); ?>" />
                    <p class="description">Receives admin alerts on new applications/orders. International format, no '+'.</p>
                </td></tr>
                <tr><th>Language Code</th><td>
                    <input type="text" class="small-text" name="<?php echo LUVRON_WA_OPT; ?>[lang]" value="<?php echo esc_attr($opt['lang'] ?? 'en'); ?>" />
                    <p class="description">e.g. en, en_US, hi.</p>
                </td></tr>
                <tr><th colspan="2"><h3>Template Names</h3></th></tr>
                <tr><th>Application Received</th><td><input type="text" class="regular-text" name="<?php echo LUVRON_WA_OPT; ?>[template_apply]" value="<?php echo esc_attr($opt['template_apply'] ?? 'dealer_application_received'); ?>" /></td></tr>
                <tr><th>Application Approved</th><td><input type="text" class="regular-text" name="<?php echo LUVRON_WA_OPT; ?>[template_approve]" value="<?php echo esc_attr($opt['template_approve'] ?? 'dealer_approved'); ?>" /></td></tr>
                <tr><th>Order Received</th><td><input type="text" class="regular-text" name="<?php echo LUVRON_WA_OPT; ?>[template_order]" value="<?php echo esc_attr($opt['template_order'] ?? 'order_received'); ?>" /></td></tr>
                <tr><th>Order Shipped</th><td><input type="text" class="regular-text" name="<?php echo LUVRON_WA_OPT; ?>[template_shipped]" value="<?php echo esc_attr($opt['template_shipped'] ?? 'order_shipped'); ?>" /></td></tr>
                <tr><th>Admin Alert</th><td><input type="text" class="regular-text" name="<?php echo LUVRON_WA_OPT; ?>[template_admin]" value="<?php echo esc_attr($opt['template_admin'] ?? 'admin_new_order'); ?>" /></td></tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <hr>
        <h2>Test send</h2>
        <p>Sends a test text message (requires a 24-hr open window with the recipient, OR a template message).</p>
        <form method="post">
            <?php wp_nonce_field('luvron_wa_test'); ?>
            <input type="text" name="test_phone" placeholder="9198XXXXXXXX" required style="width:240px;">
            <input type="text" name="test_msg" placeholder="Hello from Luvron" value="Test from Luvron WP" style="width:300px;">
            <button class="button" name="luvron_wa_test_send" value="1">Send Test</button>
        </form>
        <?php
        if (isset($_POST['luvron_wa_test_send']) && check_admin_referer('luvron_wa_test')) {
            $phone = preg_replace('/\D/', '', $_POST['test_phone']);
            $msg   = sanitize_text_field($_POST['test_msg']);
            $res   = luvron_wa_send_text($phone, $msg);
            echo '<div class="notice notice-' . (is_wp_error($res) ? 'error' : 'success') . '"><p>'
               . esc_html(is_wp_error($res) ? $res->get_error_message() : 'Sent. Message ID: ' . ($res['messages'][0]['id'] ?? '—'))
               . '</p></div>';
        }
        ?>
    </div>
    <?php
}

function luvron_wa_enabled() {
    $o = get_option(LUVRON_WA_OPT, []);
    return !empty($o['enabled']) && !empty($o['token']) && !empty($o['phone_id']);
}

function luvron_wa_send($payload) {
    $o = get_option(LUVRON_WA_OPT, []);
    if (!luvron_wa_enabled()) {
        return new WP_Error('disabled', 'WhatsApp API not configured');
    }
    $url = LUVRON_WA_API . rawurlencode($o['phone_id']) . '/messages';
    $res = wp_remote_post($url, [
        'timeout' => 15,
        'headers' => [
            'Authorization' => 'Bearer ' . $o['token'],
            'Content-Type'  => 'application/json',
        ],
        'body' => wp_json_encode($payload),
    ]);
    if (is_wp_error($res)) return $res;
    $code = wp_remote_retrieve_response_code($res);
    $body = json_decode(wp_remote_retrieve_body($res), true);
    if ($code >= 200 && $code < 300) return $body;
    $err = $body['error']['message'] ?? "HTTP $code";
    return new WP_Error('wa_fail', $err, $body);
}

function luvron_wa_send_text($to, $text) {
    return luvron_wa_send([
        'messaging_product' => 'whatsapp',
        'to'                => preg_replace('/\D/', '', $to),
        'type'              => 'text',
        'text'              => ['body' => $text, 'preview_url' => true],
    ]);
}

function luvron_wa_send_template($to, $template_name, $params = []) {
    $o = get_option(LUVRON_WA_OPT, []);
    $components = [];
    if (!empty($params)) {
        $components[] = [
            'type'       => 'body',
            'parameters' => array_map(fn($p) => ['type' => 'text', 'text' => (string) $p], $params),
        ];
    }
    return luvron_wa_send([
        'messaging_product' => 'whatsapp',
        'to'                => preg_replace('/\D/', '', $to),
        'type'              => 'template',
        'template'          => [
            'name'     => $template_name,
            'language' => ['code' => $o['lang'] ?? 'en'],
            'components' => $components,
        ],
    ]);
}

function luvron_wa_user_phone($user_id) {
    $phone = get_user_meta($user_id, 'billing_phone', true);
    return $phone ? preg_replace('/\D/', '', $phone) : '';
}

add_action('user_register', function ($user_id) {
    if (!luvron_wa_enabled()) return;
    $user  = get_user_by('id', $user_id);
    $opt   = get_option(LUVRON_WA_OPT);
    $phone = luvron_wa_user_phone($user_id);
    if ($phone && !empty($opt['template_apply'])) {
        luvron_wa_send_template($phone, $opt['template_apply'], [
            $user->display_name, '24',
        ]);
    }
});

add_action('set_user_role', function ($user_id, $new_role, $old_roles) {
    if (!luvron_wa_enabled()) return;
    if (!in_array($new_role, ['dealer', 'distributor'], true)) return;
    if (!in_array('pending_b2b', (array) $old_roles, true)) return;

    $opt   = get_option(LUVRON_WA_OPT);
    $user  = get_user_by('id', $user_id);
    $phone = luvron_wa_user_phone($user_id);
    if ($phone && !empty($opt['template_approve'])) {
        luvron_wa_send_template($phone, $opt['template_approve'], [
            $user->display_name,
            ucfirst($new_role),
            wp_login_url(home_url('/my-account/')),
        ]);
    }
}, 20, 3);

add_action('woocommerce_thankyou', function ($order_id) {
    if (!luvron_wa_enabled() || !$order_id) return;
    $order = wc_get_order($order_id);
    if (!$order) return;
    if ($order->get_meta('_luvron_wa_sent_received')) return;

    $opt = get_option(LUVRON_WA_OPT);

    $cust_phone = preg_replace('/\D/', '', $order->get_billing_phone());
    if ($cust_phone && !empty($opt['template_order'])) {
        luvron_wa_send_template($cust_phone, $opt['template_order'], [
            $order->get_billing_first_name() ?: $order->get_billing_company(),
            (string) $order->get_order_number(),
            number_format($order->get_total(), 2),
            add_query_arg('luvron_invoice', $order_id, home_url('/')),
        ]);
    }
    if (!empty($opt['admin_phone']) && !empty($opt['template_admin'])) {
        luvron_wa_send_template($opt['admin_phone'], $opt['template_admin'], [
            $order->get_billing_company() ?: $order->get_formatted_billing_full_name(),
            (string) $order->get_order_number(),
            number_format($order->get_total(), 2),
            $order->get_billing_phone(),
        ]);
    }
    $order->update_meta_data('_luvron_wa_sent_received', current_time('mysql'));
    $order->save();
}, 20);

add_action('woocommerce_order_status_changed', function ($order_id, $from, $to, $order) {
    if (!luvron_wa_enabled()) return;
    $opt = get_option(LUVRON_WA_OPT);
    if (!in_array($to, ['shipped', 'completed'], true)) return;
    if ($order->get_meta('_luvron_wa_sent_shipped')) return;

    $phone = preg_replace('/\D/', '', $order->get_billing_phone());
    if ($phone && !empty($opt['template_shipped'])) {
        $tracking = $order->get_meta('_tracking_number') ?: 'transporter assigned';
        luvron_wa_send_template($phone, $opt['template_shipped'], [
            $order->get_billing_first_name() ?: $order->get_billing_company(),
            (string) $order->get_order_number(),
            $tracking,
        ]);
        $order->update_meta_data('_luvron_wa_sent_shipped', current_time('mysql'));
        $order->save();
    }
}, 20, 4);
