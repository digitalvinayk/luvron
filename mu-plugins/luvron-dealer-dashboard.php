<?php
/**
 * Plugin Name: Luvron — Dealer Dashboard
 * Description: [luvron_dealer_dashboard] shortcode — order history, one-click reorder, invoice download.
 *              Drop into a "My Account" or "Dealer Dashboard" page.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-dealer-dashboard')) return;

add_shortcode('luvron_dealer_dashboard', function () {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . esc_url(wp_login_url(get_permalink())) . '">login</a> to view your dashboard.</p>';
    }

    if (current_user_can('pending_b2b')) {
        return '<div class="luvron-pending"><h3>Application under review</h3>'
             . '<p>Your dealer account is pending admin approval. You\'ll receive an email once approved.</p></div>';
    }

    if (!current_user_can('dealer') && !current_user_can('distributor')) {
        return '<p>This dashboard is for approved dealers and distributors.</p>';
    }

    $user_id = get_current_user_id();
    $user    = wp_get_current_user();

    if (!empty($_GET['luvron_reorder']) && check_admin_referer('luvron_reorder_' . $user_id, 'nonce')) {
        $oid = (int) $_GET['luvron_reorder'];
        $msg = luvron_reorder($oid, $user_id);
        if (!is_wp_error($msg)) {
            wp_safe_redirect(wc_get_cart_url());
            exit;
        }
    }

    $orders = wc_get_orders([
        'customer_id' => $user_id,
        'limit'       => 25,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'status'      => ['wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending'],
    ]);

    $stat_total = 0;
    $stat_count = count($orders);
    foreach ($orders as $o) $stat_total += $o->get_total();

    $role_label = current_user_can('distributor') ? 'Distributor' : 'Dealer';

    ob_start(); ?>
    <div class="luvron-dashboard">
        <div class="luvron-dash-head">
            <div>
                <h2>Welcome, <?php echo esc_html($user->display_name); ?></h2>
                <p class="luvron-dash-meta">
                    <span class="luvron-pill"><?php echo esc_html($role_label); ?></span>
                    <span><?php echo esc_html($user->user_email); ?></span>
                </p>
            </div>
            <div class="luvron-dash-actions">
                <a href="<?php echo esc_url(home_url('/bulk-order/')); ?>" class="button alt">Open Bulk Order Grid</a>
                <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" class="button">Logout</a>
            </div>
        </div>

        <div class="luvron-dash-stats">
            <div class="stat"><span class="num"><?php echo (int) $stat_count; ?></span><span class="label">Recent Orders</span></div>
            <div class="stat"><span class="num">₹<?php echo number_format($stat_total, 0); ?></span><span class="label">Lifetime (last 25)</span></div>
            <div class="stat"><span class="num"><?php echo esc_html($role_label); ?></span><span class="label">Pricing Tier</span></div>
        </div>

        <h3 style="margin-top:32px;">Recent Orders</h3>
        <?php if (empty($orders)): ?>
            <div class="luvron-empty">
                <p>No orders yet. Head to the <a href="<?php echo esc_url(home_url('/bulk-order/')); ?>">bulk order grid</a> to place your first order.</p>
            </div>
        <?php else: ?>
            <table class="luvron-dash-orders">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th class="num">Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order):
                    $reorder_url = wp_nonce_url(
                        add_query_arg('luvron_reorder', $order->get_id(), get_permalink()),
                        'luvron_reorder_' . $user_id, 'nonce'
                    );
                    $invoice_url = add_query_arg('luvron_invoice', $order->get_id(), home_url('/'));
                    $item_count  = $order->get_item_count();
                ?>
                    <tr>
                        <td><a href="<?php echo esc_url($order->get_view_order_url()); ?>"><strong>#<?php echo esc_html($order->get_order_number()); ?></strong></a></td>
                        <td><?php echo esc_html($order->get_date_created()->format('d M Y')); ?></td>
                        <td><?php echo (int) $item_count; ?> pcs across <?php echo count($order->get_items()); ?> SKUs</td>
                        <td class="num">₹<?php echo number_format($order->get_total(), 2); ?></td>
                        <td><span class="luvron-status status-<?php echo esc_attr($order->get_status()); ?>"><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></span></td>
                        <td class="actions">
                            <a href="<?php echo esc_url($reorder_url); ?>" class="button luvron-btn-sm">Reorder</a>
                            <a href="<?php echo esc_url($invoice_url); ?>" target="_blank" class="button luvron-btn-sm">Invoice</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h3 style="margin-top:40px;">Account Details</h3>
        <div class="luvron-dash-account">
            <p><strong>Display Name:</strong> <?php echo esc_html($user->display_name); ?></p>
            <p><strong>Email:</strong> <?php echo esc_html($user->user_email); ?></p>
            <p><strong>Business Name:</strong> <?php echo esc_html(get_user_meta($user_id, 'billing_company', true) ?: '—'); ?></p>
            <p><strong>GSTIN:</strong> <?php echo esc_html(get_user_meta($user_id, '_billing_gstin', true) ?: '—'); ?></p>
            <p><strong>Phone:</strong> <?php echo esc_html(get_user_meta($user_id, 'billing_phone', true) ?: '—'); ?></p>
            <p><a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>" class="button">Edit account</a> <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-address')); ?>" class="button">Manage addresses</a></p>
        </div>
    </div>

    <style>
        .luvron-dashboard{font-size:14px}
        .luvron-dash-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;padding:24px;background:#f7f7f7;border-radius:8px;margin-bottom:20px}
        .luvron-dash-head h2{margin:0 0 6px}
        .luvron-dash-meta{margin:0;color:#666;font-size:13px;display:flex;gap:12px;align-items:center}
        .luvron-pill{display:inline-block;background:#ff8c00;color:#fff;padding:3px 10px;border-radius:99px;font-size:12px;font-weight:600;letter-spacing:.5px;text-transform:uppercase}
        .luvron-dash-actions .button{margin-left:6px}
        .luvron-dash-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px}
        .luvron-dash-stats .stat{background:#fff;border:1px solid #eee;padding:18px;border-radius:8px;text-align:center}
        .luvron-dash-stats .num{display:block;font-size:24px;font-weight:700;color:#1a1a1a;margin-bottom:4px}
        .luvron-dash-stats .label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#666}
        .luvron-dash-orders{width:100%;border-collapse:collapse;background:#fff;border:1px solid #eee;border-radius:8px;overflow:hidden}
        .luvron-dash-orders th{background:#f0f0f0;padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px}
        .luvron-dash-orders td{padding:14px;border-bottom:1px solid #f0f0f0;vertical-align:middle}
        .luvron-dash-orders tr:last-child td{border-bottom:none}
        .luvron-dash-orders td.num,.luvron-dash-orders th.num{text-align:right;font-variant-numeric:tabular-nums}
        .luvron-dash-orders td.actions{white-space:nowrap}
        .luvron-btn-sm{padding:5px 12px !important;font-size:12px !important;margin-right:4px}
        .luvron-status{display:inline-block;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;text-transform:uppercase}
        .luvron-status.status-completed{background:#dcf8c6;color:#1d6f1d}
        .luvron-status.status-processing{background:#cfe2ff;color:#0a3d75}
        .luvron-status.status-on-hold{background:#fff3cd;color:#856404}
        .luvron-status.status-pending{background:#f0f0f0;color:#666}
        .luvron-empty{padding:40px;text-align:center;background:#fafafa;border-radius:8px;color:#666}
        .luvron-pending{padding:40px;text-align:center;background:#fff3cd;border-radius:8px}
        .luvron-dash-account{background:#fff;border:1px solid #eee;padding:20px;border-radius:8px;line-height:1.8}
        .luvron-dash-account p{margin:0 0 6px}
        @media (max-width:700px){
            .luvron-dash-orders thead{display:none}
            .luvron-dash-orders tr{display:grid;grid-template-columns:1fr 1fr;padding:14px;border-bottom:1px solid #f0f0f0}
            .luvron-dash-orders td{padding:4px;border:none}
            .luvron-dash-orders td.actions{grid-column:1/-1;margin-top:8px}
        }
    </style>
    <?php
    return ob_get_clean();
});

function luvron_reorder($order_id, $user_id) {
    $order = wc_get_order($order_id);
    if (!$order) return new WP_Error('not_found', 'Order not found');
    if ((int) $order->get_customer_id() !== (int) $user_id) {
        return new WP_Error('forbidden', 'Not your order');
    }
    if (!WC()->cart) wc_load_cart();

    foreach ($order->get_items() as $item) {
        $pid = $item->get_product_id();
        $vid = $item->get_variation_id();
        $qty = $item->get_quantity();
        if ($pid && $qty > 0) {
            $variation = [];
            if ($vid > 0) {
                $v = wc_get_product($vid);
                if ($v && $v->is_type('variation')) {
                    $variation = $v->get_variation_attributes();
                }
            }
            WC()->cart->add_to_cart($pid, $qty, $vid, $variation);
        }
    }
    return true;
}

add_filter('woocommerce_account_menu_items', function ($items) {
    if (!current_user_can('dealer') && !current_user_can('distributor')) return $items;
    $new = ['luvron-dashboard' => 'Dealer Dashboard'];
    return array_merge($new, $items);
});

add_action('init', function () {
    add_rewrite_endpoint('luvron-dashboard', EP_ROOT | EP_PAGES);
});

add_action('woocommerce_account_luvron-dashboard_endpoint', function () {
    echo do_shortcode('[luvron_dealer_dashboard]');
});

add_action('woocommerce_register_form_tag', function () {
    echo ' enctype="multipart/form-data"';
});

add_action('woocommerce_register_form', function () {
    ?>
    <p class="form-row form-row-wide">
        <label for="reg_billing_company">Business Name <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_company" id="reg_billing_company"
               value="<?php echo esc_attr(wp_unslash($_POST['billing_company'] ?? '')); ?>" required>
    </p>
    <p class="form-row form-row-wide">
        <label for="reg_billing_gstin">GSTIN</label>
        <input type="text" class="input-text" name="billing_gstin" id="reg_billing_gstin"
               pattern="[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}"
               value="<?php echo esc_attr(wp_unslash($_POST['billing_gstin'] ?? '')); ?>">
    </p>
    <p class="form-row form-row-wide">
        <label for="reg_billing_phone">Phone / WhatsApp <span class="required">*</span></label>
        <input type="tel" class="input-text" name="billing_phone" id="reg_billing_phone"
               value="<?php echo esc_attr(wp_unslash($_POST['billing_phone'] ?? '')); ?>" required>
    </p>
    <p class="form-row form-row-wide">
        <label for="reg_billing_state">State <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_state" id="reg_billing_state"
               value="<?php echo esc_attr(wp_unslash($_POST['billing_state'] ?? '')); ?>" required>
    </p>
    <p class="form-row form-row-wide">
        <label for="reg_volume">Monthly volume (cartons)</label>
        <select name="luvron_volume" id="reg_volume" class="input-text">
            <option value="lt50">Less than 50</option>
            <option value="50-200">50–200</option>
            <option value="200+">200+</option>
        </select>
    </p>
    <?php
});

add_action('woocommerce_created_customer', function ($user_id) {
    foreach (['billing_company', 'billing_gstin', 'billing_phone', 'billing_state'] as $f) {
        if (!empty($_POST[$f])) {
            update_user_meta($user_id, $f, sanitize_text_field(wp_unslash($_POST[$f])));
            if ($f === 'billing_gstin') {
                update_user_meta($user_id, '_billing_gstin', sanitize_text_field(wp_unslash($_POST[$f])));
            }
        }
    }
    if (!empty($_POST['luvron_volume'])) {
        update_user_meta($user_id, 'luvron_volume', sanitize_text_field(wp_unslash($_POST['luvron_volume'])));
    }
    $u = new WP_User($user_id);
    $u->set_role('pending_b2b');
});
