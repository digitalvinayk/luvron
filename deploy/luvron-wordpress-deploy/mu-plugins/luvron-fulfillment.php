<?php
/**
 * Plugin Name: Luvron — Fulfillment & Warehouse Role
 * Description: Adds warehouse_staff role with order-fulfilment-only permissions.
 *              Adds tracking number capture, dispatch action, and a simplified order list view.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-fulfillment')) return;

add_action('init', function () {
    if (!get_role('warehouse_staff')) {
        add_role('warehouse_staff', 'Warehouse Staff', [
            'read'                    => true,
            'edit_shop_orders'        => true,
            'read_shop_order'         => true,
            'manage_woocommerce'      => false,
            'view_woocommerce_reports' => false,
        ]);
    }
});

add_action('add_meta_boxes', function () {
    add_meta_box('luvron_dispatch', 'Dispatch & Tracking', 'luvron_dispatch_metabox',
        ['shop_order', 'woocommerce_page_wc-orders'], 'side', 'default');
});

function luvron_dispatch_metabox($post_or_order) {
    $order = $post_or_order instanceof WP_Post ? wc_get_order($post_or_order->ID) : $post_or_order;
    if (!$order) return;
    wp_nonce_field('luvron_dispatch', 'luvron_dispatch_nonce');
    $transporter = $order->get_meta('_luvron_transporter');
    $lr          = $order->get_meta('_luvron_lr_number');
    $dispatched  = $order->get_meta('_luvron_dispatched_at');
    $cartons     = $order->get_meta('_luvron_cartons_count');
    ?>
    <p><label>Transporter<br>
        <select name="_luvron_transporter" style="width:100%;">
            <option value="">— select —</option>
            <?php foreach (['Delhivery', 'Gati', 'VRL', 'TCI', 'Safexpress', 'BlueDart B2B', 'Buyer Self-Pickup', 'Other'] as $t): ?>
                <option value="<?php echo esc_attr($t); ?>" <?php selected($transporter, $t); ?>><?php echo esc_html($t); ?></option>
            <?php endforeach; ?>
        </select></label></p>
    <p><label>LR / Tracking Number<br>
        <input type="text" name="_luvron_lr_number" value="<?php echo esc_attr($lr); ?>" style="width:100%;font-family:monospace;"></label></p>
    <p><label>Cartons Dispatched<br>
        <input type="number" min="0" name="_luvron_cartons_count" value="<?php echo esc_attr($cartons); ?>" style="width:100%;"></label></p>
    <?php if ($dispatched): ?>
        <p><strong>Dispatched:</strong> <?php echo esc_html($dispatched); ?></p>
    <?php endif; ?>
    <p>
        <button type="button" class="button button-primary" id="luvron-dispatch-now"
                onclick="document.getElementById('luvron_mark_dispatched').value='1';document.getElementById('post').submit();">
            Mark as Dispatched (saves + sends WhatsApp)
        </button>
    </p>
    <input type="hidden" name="luvron_mark_dispatched" id="luvron_mark_dispatched" value="0">
    <?php
}

add_action('woocommerce_process_shop_order_meta', function ($order_id, $post = null) {
    if (empty($_POST['luvron_dispatch_nonce']) || !wp_verify_nonce($_POST['luvron_dispatch_nonce'], 'luvron_dispatch')) return;
    $order = wc_get_order($order_id);
    if (!$order) return;

    $order->update_meta_data('_luvron_transporter',    sanitize_text_field($_POST['_luvron_transporter'] ?? ''));
    $order->update_meta_data('_luvron_lr_number',      sanitize_text_field($_POST['_luvron_lr_number'] ?? ''));
    $order->update_meta_data('_luvron_cartons_count',  (int) ($_POST['_luvron_cartons_count'] ?? 0));

    if (!empty($_POST['luvron_mark_dispatched']) && !$order->get_meta('_luvron_dispatched_at')) {
        $order->update_meta_data('_luvron_dispatched_at', current_time('mysql'));
        $order->update_status('shipped', 'Dispatched via ' . sanitize_text_field($_POST['_luvron_transporter'] ?? '') . ', LR ' . sanitize_text_field($_POST['_luvron_lr_number'] ?? ''));

        $phone = preg_replace('/\D/', '', $order->get_billing_phone());
        if ($phone) {
            $msg = "Order #" . $order->get_order_number() . " dispatched via "
                 . sanitize_text_field($_POST['_luvron_transporter'] ?? '')
                 . " (LR: " . sanitize_text_field($_POST['_luvron_lr_number'] ?? '') . "). Track on dealer dashboard.";
            wp_remote_post('https://wa.me/' . $phone . '?text=' . urlencode($msg), ['blocking' => false]);
        }
    }
    $order->save();
}, 20, 2);

add_filter('wc_order_statuses', function ($statuses) {
    if (!isset($statuses['wc-shipped'])) {
        $new = [];
        foreach ($statuses as $k => $v) {
            $new[$k] = $v;
            if ($k === 'wc-processing') {
                $new['wc-shipped'] = 'Shipped';
            }
        }
        return $new;
    }
    return $statuses;
});

add_action('init', function () {
    register_post_status('wc-shipped', [
        'label'                     => 'Shipped',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>'),
    ]);
});

add_action('admin_menu', function () {
    if (current_user_can('warehouse_staff') && !current_user_can('manage_options')) {
        global $menu;
        foreach ((array) $menu as $key => $item) {
            if (!in_array($item[2], ['edit.php?post_type=shop_order', 'admin.php?page=wc-orders', 'index.php', 'profile.php'], true)) {
                remove_menu_page($item[2]);
            }
        }
    }
}, 999);

add_filter('manage_edit-shop_order_columns', function ($columns) {
    $columns['luvron_lr'] = 'LR / Tracking';
    return $columns;
});

add_action('manage_shop_order_posts_custom_column', function ($column, $post_id) {
    if ($column === 'luvron_lr') {
        $order = wc_get_order($post_id);
        if (!$order) return;
        $lr = $order->get_meta('_luvron_lr_number');
        $tr = $order->get_meta('_luvron_transporter');
        if ($lr) {
            echo '<small>' . esc_html($tr) . '</small><br><code>' . esc_html($lr) . '</code>';
        } else {
            echo '<span style="color:#aaa;">—</span>';
        }
    }
}, 10, 2);
