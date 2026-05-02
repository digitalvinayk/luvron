<?php
/**
 * Plugin Name: Luvron — Proforma Invoice Workflow
 * Description: Inserts proforma invoice step into the B2B order flow.
 *              Dealer adds to cart → "Generate Proforma" → admin confirms freight & terms →
 *              dealer pays → IRN-stamped tax invoice issued.
 *              Adds custom order statuses: wc-proforma, wc-confirmed, wc-paid-pending-irn.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-proforma')) return;

add_action('init', function () {
    register_post_status('wc-proforma', [
        'label'                     => 'Proforma',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('Proforma <span class="count">(%s)</span>',
                                                'Proforma <span class="count">(%s)</span>'),
    ]);
    register_post_status('wc-confirmed', [
        'label'                     => 'Confirmed',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('Confirmed <span class="count">(%s)</span>',
                                                'Confirmed <span class="count">(%s)</span>'),
    ]);
    register_post_status('wc-paid-pending-irn', [
        'label'                     => 'Paid (Pending IRN)',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('Paid Pending IRN <span class="count">(%s)</span>',
                                                'Paid Pending IRN <span class="count">(%s)</span>'),
    ]);
});

add_filter('wc_order_statuses', function ($statuses) {
    $new = [];
    foreach ($statuses as $key => $label) {
        if ($key === 'wc-pending') {
            $new['wc-proforma']  = 'Proforma';
            $new['wc-confirmed'] = 'Confirmed';
        }
        if ($key === 'wc-on-hold') {
            $new['wc-paid-pending-irn'] = 'Paid (Pending IRN)';
        }
        $new[$key] = $label;
    }
    return $new;
});

add_action('woocommerce_review_order_before_submit', function () {
    if (!is_user_logged_in() || (!current_user_can('dealer') && !current_user_can('distributor'))) return;
    ?>
    <p style="background:#fff8e1;padding:12px;border-left:4px solid #ff8c00;border-radius:4px;margin:12px 0;">
        <label>
            <input type="checkbox" name="luvron_request_proforma" id="luvron_request_proforma" value="1">
            <strong>Request Proforma Invoice First</strong> (recommended — lock pricing &amp; freight before payment)
        </label>
    </p>
    <?php
});

add_action('woocommerce_checkout_create_order', function ($order) {
    if (!empty($_POST['luvron_request_proforma'])) {
        $order->update_meta_data('_luvron_proforma_requested', 1);
    }
}, 10, 1);

add_filter('woocommerce_payment_complete_order_status', function ($status, $order_id, $order) {
    if ($order->get_meta('_luvron_proforma_requested')) {
        return 'proforma';
    }
    return $status;
}, 10, 3);

add_action('woocommerce_thankyou', function ($order_id) {
    $order = wc_get_order($order_id);
    if (!$order || !$order->get_meta('_luvron_proforma_requested')) return;

    $proforma_url = add_query_arg(['luvron_invoice' => $order_id, 'doc' => 'proforma'], home_url('/'));
    $order->update_status('proforma', 'Proforma requested by dealer');
    ?>
    <div style="background:#fff8e1;padding:24px;margin:24px 0;border-radius:8px;border-left:4px solid #ff8c00;">
        <h2 style="margin-top:0;">Proforma Invoice Generated</h2>
        <p>Your proforma invoice <strong>#<?php echo esc_html($order->get_order_number()); ?></strong> is ready. Our team will:</p>
        <ol>
            <li>Confirm freight and dispatch timeline within 4 hours</li>
            <li>Email you a signed proforma with bank/UPI/Razorpay payment link</li>
            <li>Issue the GST tax invoice (with IRN/QR) once payment is received</li>
        </ol>
        <p>
            <a class="button alt" target="_blank" href="<?php echo esc_url($proforma_url); ?>">Download Proforma (PDF)</a>
            <a class="button" href="<?php echo esc_url(home_url('/dealer-dashboard/')); ?>">View on Dashboard</a>
        </p>
    </div>
    <?php
}, 7);

add_action('woocommerce_order_status_confirmed', function ($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    $order->add_order_note('Proforma confirmed — payment instructions sent to dealer');
    $email = $order->get_billing_email();
    if ($email) {
        $url = add_query_arg('luvron_invoice', $order_id, home_url('/')) . '&doc=proforma';
        wp_mail($email, 'Proforma confirmed — Order ' . $order->get_order_number(),
            "Hi,\n\nYour proforma is confirmed. Please proceed with payment.\n\n"
            . "Proforma PDF: $url\n\n"
            . "Bank/UPI/Razorpay options on the proforma page.\n\nLuvron Tricycles");
    }
});

add_action('woocommerce_order_actions', function ($actions) {
    $actions['luvron_confirm_proforma'] = 'Confirm Proforma → Send Payment Link';
    $actions['luvron_mark_paid_pending_irn'] = 'Mark Paid (Generate IRN next)';
    return $actions;
});

add_action('woocommerce_order_action_luvron_confirm_proforma', function ($order) {
    $order->update_status('confirmed', 'Confirmed by admin via order action');
});

add_action('woocommerce_order_action_luvron_mark_paid_pending_irn', function ($order) {
    $order->update_status('paid-pending-irn', 'Marked paid; IRN pending');
});

add_filter('luvron_invoice_doc_type', function ($type) {
    return isset($_GET['doc']) && $_GET['doc'] === 'proforma' ? 'proforma' : 'tax';
});
