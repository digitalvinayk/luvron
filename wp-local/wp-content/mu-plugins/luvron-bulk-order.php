<?php
/**
 * Plugin Name: Luvron — Bulk Order Grid
 * Description: [luvron_bulk_order] shortcode — single-page table of all SKUs with qty inputs
 *              and AJAX batch add-to-cart. Dealer/distributor only.
 * Version: 1.0.0
 *
 * Usage:
 *   [luvron_bulk_order]                     — all in-stock products
 *   [luvron_bulk_order category="sigma"]    — filter by category slug(s), comma-separated
 *   [luvron_bulk_order group="1"]           — group rows under a category header
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-bulk-order')) return;

function luvron_bulk_row_data($variation_or_product, $parent = null) {
    $p = $variation_or_product;
    $is_variation = $parent !== null;

    $pid    = $is_variation ? $parent->get_id() : $p->get_id();
    $vid    = $is_variation ? $p->get_id() : 0;
    $sku    = $p->get_sku() ?: ($is_variation ? $parent->get_sku() . '-VAR' : '');
    $name   = $is_variation ? $parent->get_name() . ' — ' . wc_get_formatted_variation($p, true) : $p->get_name();

    $price  = (float) (function_exists('luvron_get_role_price') ? luvron_get_role_price($p) : $p->get_price('edit'));
    $pack   = (int) get_post_meta($p->get_id(), '_pack_master', true);
    if (!$pack && $is_variation) {
        $pack = (int) get_post_meta($parent->get_id(), '_pack_master', true);
    }
    $pack   = $pack ?: 1;
    $inner  = get_post_meta($p->get_id(), '_pack_inner', true) ?: '1x1';
    $gst    = (int) get_post_meta($p->get_id(), '_gst_rate', true);
    if (!$gst && $is_variation) {
        $gst = (int) get_post_meta($parent->get_id(), '_gst_rate', true);
    }

    $cats = wp_get_post_terms($pid, 'product_cat', ['fields' => 'names']);
    $category = $cats ? $cats[0] : 'Tricycles';

    return compact('pid', 'vid', 'sku', 'name', 'price', 'pack', 'inner', 'gst', 'category');
}

add_shortcode('luvron_bulk_order', function ($atts) {
    if (!is_user_logged_in() || !(current_user_can('dealer') || current_user_can('distributor'))) {
        return '<div class="luvron-bulk-locked">'
             . '<p>The bulk order grid is available to approved dealers and distributors.</p>'
             . '<a class="button" href="' . esc_url(wp_login_url(get_permalink())) . '">Login</a> '
             . '<a class="button alt" href="' . esc_url(home_url('/become-a-dealer/')) . '">Become a Dealer</a>'
             . '</div>';
    }

    $atts = shortcode_atts(['category' => '', 'limit' => 500, 'group' => '1'], $atts);

    $args = [
        'status'       => 'publish',
        'limit'        => (int) $atts['limit'],
        'type'         => ['simple', 'variable'],
        'stock_status' => 'instock',
        'orderby'      => 'menu_order title',
        'order'        => 'ASC',
    ];
    if (!empty($atts['category'])) {
        $args['category'] = array_map('trim', explode(',', $atts['category']));
    }

    $products = wc_get_products($args);
    $rows = [];
    foreach ($products as $p) {
        if ($p->is_type('variable')) {
            foreach ($p->get_children() as $vid) {
                $v = wc_get_product($vid);
                if ($v && $v->is_in_stock()) {
                    $rows[] = luvron_bulk_row_data($v, $p);
                }
            }
        } else {
            $rows[] = luvron_bulk_row_data($p);
        }
    }

    if (empty($rows)) {
        return '<p>No products available.</p>';
    }

    if ($atts['group']) {
        usort($rows, fn($a, $b) => strcmp($a['category'], $b['category']) ?: strcmp($a['name'], $b['name']));
    }

    $ajax_url = admin_url('admin-ajax.php');
    $nonce    = wp_create_nonce('luvron_bulk_add');
    $cart_url = wc_get_cart_url();

    ob_start(); ?>
    <div class="luvron-bulk-wrap">
        <div class="luvron-bulk-toolbar">
            <input type="search" id="luvron-bulk-search" placeholder="Search SKU, model, pack…" />
            <span class="luvron-bulk-stats">
                <span id="luvron-row-count"><?php echo count($rows); ?></span> SKUs
            </span>
        </div>

        <form id="luvron-bulk-form" class="luvron-bulk-grid" method="post">
            <table class="luvron-bulk-table">
                <thead>
                    <tr>
                        <th class="col-sku">SKU</th>
                        <th class="col-name">Model</th>
                        <th class="col-pack">Pack</th>
                        <th class="col-price">Unit ₹</th>
                        <th class="col-gst">GST</th>
                        <th class="col-qty">Qty (pcs)</th>
                        <th class="col-cartons">Cartons</th>
                        <th class="col-line">Line ₹</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $current_cat = '';
                foreach ($rows as $r):
                    if ($atts['group'] && $r['category'] !== $current_cat):
                        $current_cat = $r['category']; ?>
                        <tr class="luvron-bulk-grouphead">
                            <td colspan="8"><?php echo esc_html($current_cat); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="luvron-bulk-row"
                        data-pid="<?php echo (int) $r['pid']; ?>"
                        data-vid="<?php echo (int) $r['vid']; ?>"
                        data-price="<?php echo esc_attr($r['price']); ?>"
                        data-pack="<?php echo (int) $r['pack']; ?>"
                        data-gst="<?php echo (int) $r['gst']; ?>"
                        data-search="<?php echo esc_attr(strtolower($r['sku'] . ' ' . $r['name'] . ' ' . $r['inner'])); ?>">
                        <td class="col-sku"><code><?php echo esc_html($r['sku']); ?></code></td>
                        <td class="col-name"><?php echo esc_html($r['name']); ?></td>
                        <td class="col-pack"><?php echo esc_html($r['inner'] . ' / ' . $r['pack']); ?></td>
                        <td class="col-price">₹<?php echo number_format($r['price'], 0); ?></td>
                        <td class="col-gst"><?php echo (int) $r['gst']; ?>%</td>
                        <td class="col-qty">
                            <input type="number"
                                   class="luvron-qty"
                                   min="0"
                                   step="<?php echo (int) $r['pack']; ?>"
                                   value="0"
                                   placeholder="0"
                                   inputmode="numeric" />
                        </td>
                        <td class="col-cartons"><span class="luvron-cartons">0</span></td>
                        <td class="col-line">₹<span class="luvron-line">0</span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="luvron-bulk-summary">
                        <td colspan="5"></td>
                        <td colspan="2">
                            <strong>Subtotal</strong> (excl. GST):<br>
                            <strong>GST</strong>:<br>
                            <strong>Total</strong>:
                        </td>
                        <td>
                            ₹<span id="luvron-subtotal">0</span><br>
                            ₹<span id="luvron-tax">0</span><br>
                            <strong>₹<span id="luvron-grand">0</span></strong>
                        </td>
                    </tr>
                    <tr class="luvron-bulk-cta">
                        <td colspan="8">
                            <button type="submit" class="button alt luvron-bulk-submit" disabled>
                                Add selected to cart
                            </button>
                            <a href="<?php echo esc_url($cart_url); ?>" class="button">View cart</a>
                            <span class="luvron-bulk-status" aria-live="polite"></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </form>
    </div>

    <style>
        .luvron-bulk-wrap{font-size:14px}
        .luvron-bulk-toolbar{display:flex;gap:12px;align-items:center;margin-bottom:12px;position:sticky;top:0;background:#fff;padding:8px 0;z-index:5}
        .luvron-bulk-toolbar input[type=search]{flex:1;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:14px}
        .luvron-bulk-stats{color:#666;font-size:13px}
        .luvron-bulk-table{width:100%;border-collapse:collapse}
        .luvron-bulk-table th,.luvron-bulk-table td{padding:8px 10px;border-bottom:1px solid #eee;text-align:left;vertical-align:middle}
        .luvron-bulk-table th{background:#f7f7f7;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.04em;position:sticky;top:46px;z-index:4}
        .luvron-bulk-grouphead td{background:#fafafa;font-weight:700;color:#333;padding:12px 10px;border-top:2px solid #ddd}
        .luvron-bulk-row.luvron-active{background:#fffbe6}
        .luvron-bulk-row code{font-size:12px;color:#555}
        .luvron-qty{width:90px;padding:6px 8px;border:1px solid #ccc;border-radius:4px;text-align:right;font-variant-numeric:tabular-nums}
        .col-price,.col-line,.col-cartons,.col-gst{text-align:right;font-variant-numeric:tabular-nums}
        .luvron-bulk-summary td{padding:14px 10px;background:#f7f7f7;border-top:2px solid #ddd;text-align:right;font-size:15px}
        .luvron-bulk-cta td{padding:16px 10px;background:#fafafa}
        .luvron-bulk-cta .button{margin-right:8px}
        .luvron-bulk-status{margin-left:12px;color:#2c7d2c;font-weight:600}
        .luvron-bulk-status.error{color:#c33}
        .luvron-bulk-locked{padding:24px;background:#f7f7f7;border-radius:8px;text-align:center}
        .luvron-bulk-locked .button{margin:8px}
        @media (max-width:900px){
            .luvron-bulk-table thead{display:none}
            .luvron-bulk-table tr{display:grid;grid-template-columns:1fr 1fr;gap:4px;padding:10px;border-bottom:2px solid #eee}
            .luvron-bulk-table td{border:none;padding:4px}
            .luvron-bulk-grouphead{grid-template-columns:1fr !important}
            .luvron-bulk-summary,.luvron-bulk-cta{grid-template-columns:1fr !important}
        }
    </style>

    <script>
    (function () {
        const form     = document.getElementById('luvron-bulk-form');
        const search   = document.getElementById('luvron-bulk-search');
        const submit   = form.querySelector('.luvron-bulk-submit');
        const status   = form.querySelector('.luvron-bulk-status');
        const elSub    = document.getElementById('luvron-subtotal');
        const elTax    = document.getElementById('luvron-tax');
        const elGrand  = document.getElementById('luvron-grand');
        const elCount  = document.getElementById('luvron-row-count');
        const fmt      = (n) => Math.round(n).toLocaleString('en-IN');

        function recalc() {
            let sub = 0, tax = 0, anyQty = false;
            form.querySelectorAll('.luvron-bulk-row').forEach(row => {
                const qty   = parseInt(row.querySelector('.luvron-qty').value || '0', 10);
                const price = parseFloat(row.dataset.price || '0');
                const pack  = parseInt(row.dataset.pack || '1', 10);
                const gst   = parseFloat(row.dataset.gst || '0') / 100;

                const cartons = pack > 0 ? Math.floor(qty / pack) : 0;
                const line    = qty * price;
                const lineTax = line * gst;

                row.querySelector('.luvron-cartons').textContent = cartons;
                row.querySelector('.luvron-line').textContent = fmt(line);
                row.classList.toggle('luvron-active', qty > 0);

                if (qty > 0) { sub += line; tax += lineTax; anyQty = true; }
            });
            elSub.textContent  = fmt(sub);
            elTax.textContent  = fmt(tax);
            elGrand.textContent = fmt(sub + tax);
            submit.disabled = !anyQty;
        }

        form.addEventListener('input', e => {
            if (e.target.classList.contains('luvron-qty')) recalc();
        });

        search.addEventListener('input', () => {
            const q = search.value.trim().toLowerCase();
            let visible = 0;
            form.querySelectorAll('.luvron-bulk-row').forEach(row => {
                const match = !q || row.dataset.search.includes(q);
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            elCount.textContent = visible;
            form.querySelectorAll('.luvron-bulk-grouphead').forEach(g => {
                let next = g.nextElementSibling, hasVisible = false;
                while (next && !next.classList.contains('luvron-bulk-grouphead')) {
                    if (next.style.display !== 'none') { hasVisible = true; break; }
                    next = next.nextElementSibling;
                }
                g.style.display = hasVisible ? '' : 'none';
            });
        });

        form.addEventListener('submit', async e => {
            e.preventDefault();
            const items = [];
            form.querySelectorAll('.luvron-bulk-row').forEach(row => {
                const qty = parseInt(row.querySelector('.luvron-qty').value || '0', 10);
                if (qty > 0) {
                    items.push({
                        pid: parseInt(row.dataset.pid, 10),
                        vid: parseInt(row.dataset.vid, 10),
                        qty: qty
                    });
                }
            });
            if (!items.length) return;

            submit.disabled = true;
            status.className = 'luvron-bulk-status';
            status.textContent = 'Adding ' + items.length + ' items…';

            try {
                const fd = new FormData();
                fd.append('action', 'luvron_bulk_add');
                fd.append('nonce', '<?php echo esc_js($nonce); ?>');
                fd.append('items', JSON.stringify(items));
                const res = await fetch('<?php echo esc_js($ajax_url); ?>', {
                    method: 'POST', body: fd, credentials: 'same-origin'
                });
                const json = await res.json();
                if (json.success) {
                    status.textContent = 'Added ' + json.data.added + ' items. Redirecting to cart…';
                    setTimeout(() => window.location = json.data.cart_url, 600);
                } else {
                    status.className = 'luvron-bulk-status error';
                    status.textContent = (json.data && json.data.message) || 'Failed to add items.';
                    submit.disabled = false;
                }
            } catch (err) {
                status.className = 'luvron-bulk-status error';
                status.textContent = 'Network error. Try again.';
                submit.disabled = false;
            }
        });

        recalc();
    })();
    </script>
    <?php
    return ob_get_clean();
});

add_action('wp_ajax_luvron_bulk_add', function () {
    if (!check_ajax_referer('luvron_bulk_add', 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid security token'], 403);
    }
    if (!is_user_logged_in() || !(current_user_can('dealer') || current_user_can('distributor'))) {
        wp_send_json_error(['message' => 'Login as dealer/distributor required'], 401);
    }

    $raw   = isset($_POST['items']) ? wp_unslash($_POST['items']) : '[]';
    $items = json_decode($raw, true);
    if (!is_array($items) || empty($items)) {
        wp_send_json_error(['message' => 'No items submitted']);
    }

    if (!WC()->cart) wc_load_cart();

    $added  = 0;
    $errors = [];
    foreach ($items as $i) {
        $pid = isset($i['pid']) ? (int) $i['pid'] : 0;
        $vid = isset($i['vid']) ? (int) $i['vid'] : 0;
        $qty = isset($i['qty']) ? (int) $i['qty'] : 0;
        if ($pid <= 0 || $qty <= 0) continue;

        $variation = [];
        if ($vid > 0) {
            $v = wc_get_product($vid);
            if ($v && $v->is_type('variation')) {
                $variation = $v->get_variation_attributes();
            }
        }

        $key = WC()->cart->add_to_cart($pid, $qty, $vid, $variation);
        if ($key) {
            $added++;
        } else {
            $errors[] = $vid ?: $pid;
        }
    }

    if ($added > 0) {
        WC()->cart->calculate_totals();
        wp_send_json_success([
            'added'      => $added,
            'errors'     => $errors,
            'cart_url'   => wc_get_cart_url(),
            'cart_count' => WC()->cart->get_cart_contents_count(),
        ]);
    }

    $msg = 'Could not add items.';
    if (function_exists('wc_get_notices')) {
        $notices = wc_get_notices('error');
        if (!empty($notices)) {
            $msg = wp_strip_all_tags($notices[0]['notice'] ?? $msg);
            wc_clear_notices();
        }
    }
    wp_send_json_error(['message' => $msg, 'errors' => $errors]);
});
