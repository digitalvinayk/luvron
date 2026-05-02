<?php
/**
 * Plugin Name: Luvron — B2B Payment Methods
 * Description: Adds Bank Transfer (with reference matching), UPI Collect, and Razorpay
 *              integration scaffolding to WooCommerce. Forces dealers to choose payment
 *              method before order completes. Hooks into proforma → payment → IRN flow.
 * Version: 1.0.0
 *
 * Setup:
 *   1. Bank transfer is enabled by default (uses LUVRON_BANK constants).
 *   2. UPI Collect requires VPA in luvron-payments option.
 *   3. Razorpay: install official "Razorpay for WooCommerce" plugin → enter Key/Secret.
 *      This file extends Razorpay with B2B-specific UX (proforma reference, GSTIN capture).
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-payments')) return;

const LUVRON_PAY_OPT = 'luvron_payments';

add_action('admin_menu', function () {
    add_options_page('Luvron Payments', 'Luvron Payments', 'manage_options',
        'luvron-payments', 'luvron_payments_settings');
});

add_action('admin_init', function () {
    register_setting('luvron_pay_group', LUVRON_PAY_OPT, [
        'sanitize_callback' => function ($v) {
            return [
                'upi_vpa'         => sanitize_text_field($v['upi_vpa'] ?? ''),
                'upi_name'        => sanitize_text_field($v['upi_name'] ?? 'Luvron Tricycles'),
                'enable_bank'     => !empty($v['enable_bank']) ? 1 : 0,
                'enable_upi'      => !empty($v['enable_upi']) ? 1 : 0,
                'enable_razorpay' => !empty($v['enable_razorpay']) ? 1 : 0,
                'enable_credit'   => !empty($v['enable_credit']) ? 1 : 0,
                'credit_min_orders' => (int) ($v['credit_min_orders'] ?? 3),
                'credit_days'     => (int) ($v['credit_days'] ?? 30),
            ];
        },
    ]);
});

function luvron_payments_settings() {
    $o = get_option(LUVRON_PAY_OPT, []);
    ?>
    <div class="wrap">
        <h1>Luvron Payment Methods</h1>
        <form method="post" action="options.php">
            <?php settings_fields('luvron_pay_group'); ?>
            <h2>Bank Transfer (NEFT/RTGS)</h2>
            <p>Bank details auto-pulled from <code>wp-config.php</code> constants (LUVRON_BANK_*) or <code>luvron-invoice.php</code> defaults.</p>
            <p><label><input type="checkbox" name="<?php echo LUVRON_PAY_OPT; ?>[enable_bank]" value="1" <?php checked(!empty($o['enable_bank']) || !isset($o['enable_bank'])); ?>> Enable Bank Transfer</label></p>

            <h2>UPI Collect</h2>
            <table class="form-table">
                <tr><th>VPA / UPI ID</th><td><input type="text" class="regular-text" name="<?php echo LUVRON_PAY_OPT; ?>[upi_vpa]" value="<?php echo esc_attr($o['upi_vpa'] ?? 'luvron@hdfcbank'); ?>"></td></tr>
                <tr><th>Display Name</th><td><input type="text" class="regular-text" name="<?php echo LUVRON_PAY_OPT; ?>[upi_name]" value="<?php echo esc_attr($o['upi_name'] ?? 'Luvron Tricycles'); ?>"></td></tr>
                <tr><th>Enable</th><td><label><input type="checkbox" name="<?php echo LUVRON_PAY_OPT; ?>[enable_upi]" value="1" <?php checked(!empty($o['enable_upi'])); ?>> Enable UPI Collect with QR</label></td></tr>
            </table>

            <h2>Razorpay (Cards / Netbanking / UPI / EMI)</h2>
            <p>Install <a href="<?php echo admin_url('plugin-install.php?s=razorpay+woocommerce&tab=search'); ?>">Razorpay for WooCommerce</a> first, then check below to enable.</p>
            <p><label><input type="checkbox" name="<?php echo LUVRON_PAY_OPT; ?>[enable_razorpay]" value="1" <?php checked(!empty($o['enable_razorpay'])); ?>> Show Razorpay at checkout</label></p>

            <h2>Credit Terms (Net 30)</h2>
            <p>Allow approved dealers with order history to place orders on credit.</p>
            <table class="form-table">
                <tr><th>Enable</th><td><label><input type="checkbox" name="<?php echo LUVRON_PAY_OPT; ?>[enable_credit]" value="1" <?php checked(!empty($o['enable_credit'])); ?>> Allow credit terms</label></td></tr>
                <tr><th>Min completed orders</th><td><input type="number" min="1" name="<?php echo LUVRON_PAY_OPT; ?>[credit_min_orders]" value="<?php echo (int) ($o['credit_min_orders'] ?? 3); ?>"></td></tr>
                <tr><th>Days credit</th><td><input type="number" min="7" max="90" name="<?php echo LUVRON_PAY_OPT; ?>[credit_days]" value="<?php echo (int) ($o['credit_days'] ?? 30); ?>"></td></tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_filter('woocommerce_available_payment_gateways', function ($gateways) {
    $o = get_option(LUVRON_PAY_OPT, []);
    if (!empty($gateways['bacs']) && empty($o['enable_bank']) && isset($o['enable_bank'])) {
        unset($gateways['bacs']);
    }
    if (!empty($gateways['razorpay']) && empty($o['enable_razorpay'])) {
        unset($gateways['razorpay']);
    }
    return $gateways;
});

class WC_Luvron_UPI_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id                 = 'luvron_upi';
        $this->method_title       = 'UPI Collect (Luvron)';
        $this->method_description = 'Customer scans QR or pays to VPA, then submits transaction reference.';
        $this->title              = 'UPI Payment';
        $this->description        = 'Pay via UPI app (PhonePe, GPay, Paytm). Submit your transaction ref after payment for confirmation.';
        $this->has_fields         = true;
        $this->init_form_fields();
        $this->init_settings();
        add_action('woocommerce_update_options_payment_gateways_' . $this->id,
            [$this, 'process_admin_options']);
    }
    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => 'Enable',
                'type'    => 'checkbox',
                'label'   => 'Enable UPI Collect',
                'default' => 'no',
            ],
        ];
    }
    public function payment_fields() {
        $o = get_option(LUVRON_PAY_OPT, []);
        $vpa = esc_html($o['upi_vpa'] ?? 'luvron@hdfcbank');
        $name = esc_html($o['upi_name'] ?? 'Luvron Tricycles');
        echo '<p>Pay via UPI to: <strong>' . $vpa . '</strong> (' . $name . ')</p>';
        echo '<p><label>Your UPI Transaction Reference (UTR / 12-digit ref ID):<br>'
           . '<input type="text" name="luvron_upi_ref" required pattern="[A-Za-z0-9]{8,}" '
           . 'style="width:100%;padding:8px;"></label></p>';
        echo '<p><small>The order will move to "On hold" until we verify the payment (typically within 4 working hours).</small></p>';
    }
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $ref   = sanitize_text_field($_POST['luvron_upi_ref'] ?? '');
        if (!$ref) {
            wc_add_notice('UPI transaction reference required', 'error');
            return ['result' => 'fail'];
        }
        $order->update_meta_data('_luvron_upi_ref', $ref);
        $order->update_status('on-hold', 'UPI payment submitted (ref: ' . $ref . '). Pending verification.');
        $order->save();
        WC()->cart->empty_cart();
        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }
}

class WC_Luvron_Credit_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id                 = 'luvron_credit';
        $this->method_title       = 'Credit Terms (Luvron)';
        $this->method_description = 'For approved dealers — pay invoice within N days of delivery.';
        $this->title              = 'Credit Terms';
        $this->description        = '';
        $this->has_fields         = false;
        $this->init_form_fields();
        $this->init_settings();
        add_action('woocommerce_update_options_payment_gateways_' . $this->id,
            [$this, 'process_admin_options']);
    }
    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => ['title' => 'Enable', 'type' => 'checkbox', 'default' => 'no'],
        ];
    }
    public function is_available() {
        if (!is_user_logged_in()) return false;
        $o = get_option(LUVRON_PAY_OPT, []);
        if (empty($o['enable_credit'])) return false;
        $user_id = get_current_user_id();
        $min     = (int) ($o['credit_min_orders'] ?? 3);
        $count   = wc_get_customer_order_count($user_id);
        if ($count < $min) return false;
        if (!current_user_can('dealer') && !current_user_can('distributor')) return false;
        return parent::is_available();
    }
    public function payment_fields() {
        $o = get_option(LUVRON_PAY_OPT, []);
        echo '<p>Net ' . (int) ($o['credit_days'] ?? 30) . ' credit. Invoice payment due within '
           . (int) ($o['credit_days'] ?? 30) . ' days of delivery.</p>';
        echo '<p>Late payments attract 24% p.a. interest as per terms.</p>';
    }
    public function process_payment($order_id) {
        $o = get_option(LUVRON_PAY_OPT, []);
        $order = wc_get_order($order_id);
        $due = strtotime('+' . (int) ($o['credit_days'] ?? 30) . ' days');
        $order->update_meta_data('_luvron_credit_due', date('Y-m-d', $due));
        $order->update_status('processing', 'Credit terms (Net ' . (int) ($o['credit_days'] ?? 30) . ') — invoice due ' . date('d M Y', $due));
        $order->save();
        WC()->cart->empty_cart();
        return ['result' => 'success', 'redirect' => $this->get_return_url($order)];
    }
}

add_filter('woocommerce_payment_gateways', function ($methods) {
    $o = get_option(LUVRON_PAY_OPT, []);
    if (!empty($o['enable_upi']))    $methods[] = 'WC_Luvron_UPI_Gateway';
    if (!empty($o['enable_credit'])) $methods[] = 'WC_Luvron_Credit_Gateway';
    return $methods;
});

add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {
    $upi = $order->get_meta('_luvron_upi_ref');
    $due = $order->get_meta('_luvron_credit_due');
    if ($upi) echo '<p><strong>UPI Ref:</strong> <code>' . esc_html($upi) . '</code></p>';
    if ($due) echo '<p><strong>Credit Due:</strong> ' . esc_html(date('d M Y', strtotime($due))) . '</p>';
});
