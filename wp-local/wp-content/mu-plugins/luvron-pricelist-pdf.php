<?php
/**
 * Plugin Name: Luvron — Role-Aware Price List (PDF/Print)
 * Description: Generates a role-aware price list table at /?luvron_pricelist=1
 *              Print-optimized HTML; dealers save as PDF from browser dialog.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-pricelist-pdf')) return;

add_action('template_redirect', function () {
    if (empty($_GET['luvron_pricelist'])) return;

    if (!is_user_logged_in() || (!current_user_can('dealer') && !current_user_can('distributor'))) {
        wp_die('Login as a dealer to view the price list.', 'Luvron', ['response' => 403]);
    }

    $user = wp_get_current_user();
    $role_label = current_user_can('distributor') ? 'Distributor' : 'Dealer';

    $products = wc_get_products([
        'status'       => 'publish',
        'limit'        => 500,
        'type'         => ['simple', 'variable'],
        'stock_status' => 'instock',
    ]);

    $rows = [];
    foreach ($products as $p) {
        $candidates = $p->is_type('variable') ? array_map('wc_get_product', $p->get_children()) : [$p];
        foreach ($candidates as $c) {
            if (!$c) continue;
            $price = (float) (function_exists('luvron_get_role_price') ? luvron_get_role_price($c) : $c->get_price('edit'));
            $rows[] = [
                'sku'   => $c->get_sku(),
                'name'  => $c->is_type('variation') ? $p->get_name() . ' — ' . wc_get_formatted_variation($c, true) : $c->get_name(),
                'price' => $price,
                'gst'   => (int) get_post_meta($c->get_id(), '_gst_rate', true),
                'inner' => get_post_meta($c->get_id(), '_pack_inner', true) ?: '1x1',
                'pack'  => (int) get_post_meta($c->get_id(), '_pack_master', true) ?: 12,
                'hsn'   => get_post_meta($c->get_id(), '_hsn_code', true) ?: '95030010',
            ];
        }
    }
    usort($rows, fn($a, $b) => strcmp($a['sku'], $b['sku']));

    nocache_headers();
    header('Content-Type: text/html; charset=utf-8');
    ?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8">
<title>Luvron Price List — <?php echo esc_html($role_label); ?> — <?php echo date('M Y'); ?></title>
<style>
@page { size: A4; margin: 12mm; }
*{box-sizing:border-box;margin:0;padding:0;font-family:'Helvetica Neue',Arial,sans-serif}
body{color:#000;font-size:11px;line-height:1.4;background:#fff;padding:20px;max-width:210mm;margin:0 auto}
.head{display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid #000;padding-bottom:14px;margin-bottom:14px}
.head h1{font-size:22px;letter-spacing:1px}
.head .meta{text-align:right;font-size:11px;line-height:1.6}
.tier-badge{display:inline-block;background:#ff8c00;color:#fff;padding:3px 10px;border-radius:3px;font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-left:8px}
table{width:100%;border-collapse:collapse;margin-top:12px;font-size:11px}
th,td{padding:6px 8px;border-bottom:1px solid #eee;text-align:left;vertical-align:middle}
th{background:#1a1a1a;color:#fff;font-size:10px;text-transform:uppercase;letter-spacing:.5px;border:none}
td.num,th.num{text-align:right;font-variant-numeric:tabular-nums}
.bottom{margin-top:20px;padding-top:10px;border-top:1px solid #ddd;font-size:10px;color:#666;line-height:1.6}
.print-bar{padding:14px;text-align:center;background:#fffbe6;margin-bottom:14px;border:1px dashed #999}
.print-bar button{padding:8px 18px;font-size:13px;cursor:pointer;background:#000;color:#fff;border:none;border-radius:4px}
@media print { .print-bar{display:none} body{padding:0} }
</style></head><body>

<div class="print-bar">
    <button onclick="window.print()">Print / Save as PDF</button>
    <span style="margin-left:12px;color:#666;">Confidential — for <?php echo esc_html($user->display_name); ?> only.</span>
</div>

<div class="head">
    <div>
        <h1>LUVRON PRICE LIST <span class="tier-badge"><?php echo esc_html($role_label); ?></span></h1>
        <div style="font-size:11px;color:#666;margin-top:4px;">A Trusted Brand For Your Child's Fun</div>
    </div>
    <div class="meta">
        Issued to: <strong><?php echo esc_html($user->display_name); ?></strong><br>
        Date: <?php echo date('d M Y'); ?><br>
        Valid for 30 days · Subject to change<br>
        GSTIN: 09GOCPP5350G1ZQ
    </div>
</div>

<table>
<thead>
<tr>
    <th>SKU</th><th>Model</th><th>HSN</th><th>Inner / Master</th>
    <th class="num">Unit ₹</th><th class="num">GST</th>
    <th class="num">Per Carton ₹ (excl GST)</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
    <td><code><?php echo esc_html($r['sku']); ?></code></td>
    <td><?php echo esc_html($r['name']); ?></td>
    <td><?php echo esc_html($r['hsn']); ?></td>
    <td><?php echo esc_html($r['inner'] . ' / ' . $r['pack']); ?></td>
    <td class="num">₹ <?php echo number_format($r['price'], 0); ?></td>
    <td class="num"><?php echo $r['gst']; ?>%</td>
    <td class="num">₹ <?php echo number_format($r['price'] * $r['pack'], 0); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="bottom">
<p><strong>Terms:</strong> Prices are ex-Loni Ghaziabad. GST and freight extra. MOQ 1 master carton per SKU. Payment terms NEFT/UPI/Razorpay/credit (approved dealers). E.&O.E.</p>
<p>Luvron Tricycles · OFF-D-337, T-04, Indraprastha Colony, Jawli Road, Loni, Ghaziabad UP – 201102 · +91-9212389139 · orders@luvron.in</p>
</div>

</body></html>
    <?php
    exit;
});

add_shortcode('luvron_pricelist_link', function () {
    if (!is_user_logged_in() || (!current_user_can('dealer') && !current_user_can('distributor'))) {
        return '';
    }
    $url = add_query_arg('luvron_pricelist', '1', home_url('/'));
    return '<a class="button alt" target="_blank" href="' . esc_url($url) . '">Download Price List (PDF)</a>';
});
