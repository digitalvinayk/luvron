<?php
/**
 * Plugin Name: Luvron — Order Received + GST Invoice
 * Description: Custom thank-you page with bank-transfer details + printable GST-compliant invoice
 *              accessible at /?luvron_invoice=ORDER_ID (browser print → Save as PDF).
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-invoice')) return;

function luvron_bank_details() {
    return [
        'beneficiary' => defined('LUVRON_BANK_NAME')        ? LUVRON_BANK_NAME       : 'Luvron Tricycles',
        'bank'        => defined('LUVRON_BANK_BANK')        ? LUVRON_BANK_BANK       : 'HDFC Bank',
        'account_no'  => defined('LUVRON_BANK_ACCOUNT')     ? LUVRON_BANK_ACCOUNT    : 'XXXXXXXXXXXX',
        'ifsc'        => defined('LUVRON_BANK_IFSC')        ? LUVRON_BANK_IFSC       : 'HDFC0000000',
        'branch'      => defined('LUVRON_BANK_BRANCH')      ? LUVRON_BANK_BRANCH     : 'Loni, Ghaziabad',
        'gstin'       => defined('LUVRON_BANK_GSTIN')       ? LUVRON_BANK_GSTIN      : '09XXXXXXXXXXX1Z5',
    ];
}

function luvron_seller_details() {
    $trust = get_option('luvron_trust', []);
    return [
        'name'       => 'Luvron Tricycles',
        'address'    => 'OFF-D-337, T-04, Indraprastha Colony, Jawli Road, Loni, Ghaziabad UP – 201102',
        'phone'      => '+91-9212389139',
        'email'      => 'orders@luvron.in',
        'gstin'      => $trust['gstin'] ?? '09XXXXXXXXXXX1Z5',
        'state'      => 'Uttar Pradesh',
        'state_code' => '09',
    ];
}

if (!defined('LUVRON_BANK')) define('LUVRON_BANK', 'see luvron_bank_details()');
if (!defined('LUVRON_SELLER')) define('LUVRON_SELLER', 'see luvron_seller_details()');

add_action('woocommerce_thankyou', function ($order_id) {
    if (!$order_id) return;
    $order = wc_get_order($order_id);
    if (!$order) return;

    $invoice_url = add_query_arg('luvron_invoice', $order_id, home_url('/'));
    ?>
    <div class="luvron-payment-instructions" style="margin:24px 0;padding:24px;background:#f7f7f7;border-radius:8px;border-left:4px solid #ff8c00;">
        <h2 style="margin-top:0;">Bank Transfer Details</h2>
        <p>Please transfer <strong>₹<?php echo number_format($order->get_total(), 2); ?></strong> to:</p>
        <table style="width:auto;font-size:15px;line-height:1.8;">
            <tr><td><strong>Beneficiary:</strong></td><td><?php echo esc_html(luvron_bank_details()['beneficiary']); ?></td></tr>
            <tr><td><strong>Bank:</strong></td><td><?php echo esc_html(luvron_bank_details()['bank']); ?></td></tr>
            <tr><td><strong>Account No:</strong></td><td><code><?php echo esc_html(luvron_bank_details()['account_no']); ?></code></td></tr>
            <tr><td><strong>IFSC:</strong></td><td><code><?php echo esc_html(luvron_bank_details()['ifsc']); ?></code></td></tr>
            <tr><td><strong>Branch:</strong></td><td><?php echo esc_html(luvron_bank_details()['branch']); ?></td></tr>
            <tr><td><strong>GSTIN:</strong></td><td><code><?php echo esc_html(luvron_bank_details()['gstin']); ?></code></td></tr>
            <tr><td><strong>Reference:</strong></td><td><code>ORDER-<?php echo esc_html($order->get_order_number()); ?></code></td></tr>
        </table>
        <p style="margin-top:16px;"><strong>Important:</strong> Use the order reference in your transfer remark so we can match the payment.</p>
        <p>
            <a href="<?php echo esc_url($invoice_url); ?>" target="_blank" class="button alt"
               style="background:#1a1a1a;color:#fff;">Download GST Invoice (PDF)</a>
        </p>
    </div>
    <?php
}, 6);

add_action('template_redirect', function () {
    if (empty($_GET['luvron_invoice'])) return;

    $order_id = (int) $_GET['luvron_invoice'];
    $order    = wc_get_order($order_id);
    if (!$order) {
        wp_die('Invoice not found.', 'Luvron', ['response' => 404]);
    }

    if (!current_user_can('manage_woocommerce')) {
        if (!is_user_logged_in() || get_current_user_id() != $order->get_customer_id()) {
            wp_die('You are not allowed to view this invoice.', 'Luvron', ['response' => 403]);
        }
    }

    luvron_render_invoice($order);
    exit;
});

function luvron_render_invoice($order) {
    $doc_type      = apply_filters('luvron_invoice_doc_type', isset($_GET['doc']) && $_GET['doc'] === 'proforma' ? 'proforma' : 'tax');
    $is_proforma   = ($doc_type === 'proforma');
    $billing_state = $order->get_billing_state();
    $is_intrastate = ($billing_state === 'UP');
    $prefix        = $is_proforma ? 'PRO' : 'LUV';
    $invoice_no    = $prefix . '/' . date('Y') . '/' . str_pad($order->get_id(), 5, '0', STR_PAD_LEFT);
    $invoice_date  = $order->get_date_created() ? $order->get_date_created()->format('d M Y') : date('d M Y');
    $billing_gstin = $order->get_meta('_billing_gstin') ?: '—';
    $irn           = $order->get_meta('_luvron_irn');
    $irn_qr        = $order->get_meta('_luvron_irn_qr');
    $irn_ack       = $order->get_meta('_luvron_irn_ack');
    $irn_dt        = $order->get_meta('_luvron_irn_dt');

    $items = [];
    $sub   = 0;
    $tax_total = 0;

    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if (!$product) continue;

        $qty       = $item->get_quantity();
        $line_sub  = (float) $item->get_subtotal();
        $line_tax  = (float) $item->get_total_tax();
        $hsn       = get_post_meta($product->get_id(), '_hsn_code', true) ?: '95030010';
        $gst_rate  = (float) (get_post_meta($product->get_id(), '_gst_rate', true) ?: 18);
        $unit      = $qty > 0 ? $line_sub / $qty : 0;

        $items[] = [
            'name'     => $item->get_name(),
            'sku'      => $product->get_sku(),
            'hsn'      => $hsn,
            'qty'      => $qty,
            'unit'     => $unit,
            'sub'      => $line_sub,
            'gst_rate' => $gst_rate,
            'tax'      => $line_tax,
            'total'    => $line_sub + $line_tax,
        ];
        $sub       += $line_sub;
        $tax_total += $line_tax;
    }

    $cgst = $is_intrastate ? $tax_total / 2 : 0;
    $sgst = $is_intrastate ? $tax_total / 2 : 0;
    $igst = $is_intrastate ? 0 : $tax_total;

    $grand     = $order->get_total();
    $words     = luvron_number_to_words((int) round($grand));

    nocache_headers();
    header('Content-Type: text/html; charset=utf-8');
    ?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8">
<title>Tax Invoice <?php echo esc_html($invoice_no); ?> — Luvron</title>
<style>
    @page { size: A4; margin: 12mm; }
    *{box-sizing:border-box;margin:0;padding:0;font-family:'Helvetica Neue',Arial,sans-serif}
    body{color:#000;font-size:12px;line-height:1.4;background:#fff;padding:20px;max-width:210mm;margin:0 auto}
    .invoice{border:2px solid #000}
    .head{display:flex;justify-content:space-between;padding:14px;border-bottom:1px solid #000}
    .head h1{font-size:22px;letter-spacing:2px}
    .head .seller{text-align:right;font-size:11px;line-height:1.6}
    .meta{display:grid;grid-template-columns:1fr 1fr;border-bottom:1px solid #000}
    .meta>div{padding:10px 14px}
    .meta>div+div{border-left:1px solid #000}
    .meta h3{font-size:11px;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;color:#666}
    table{width:100%;border-collapse:collapse}
    th,td{padding:8px 10px;border-bottom:1px solid #ddd;text-align:left;vertical-align:top}
    th{background:#f0f0f0;font-size:11px;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #000}
    td.num,th.num{text-align:right;font-variant-numeric:tabular-nums}
    .totals{display:grid;grid-template-columns:1fr 1fr;border-top:2px solid #000}
    .totals>div{padding:14px}
    .totals>div+div{border-left:1px solid #000}
    .totals .grand{font-size:16px;font-weight:700;padding-top:8px;margin-top:8px;border-top:1px solid #000}
    .totals table{font-size:12px}
    .totals table td{border:none;padding:3px 0}
    .totals table td.num{text-align:right}
    .footer{padding:14px;font-size:11px;border-top:1px solid #000;display:flex;justify-content:space-between}
    .sign{margin-top:50px;border-top:1px solid #000;padding-top:6px;text-align:center;width:200px}
    .print-bar{padding:16px;text-align:center;background:#fffbe6;margin-bottom:16px;border:1px dashed #999}
    .print-bar button{padding:8px 20px;font-size:14px;cursor:pointer;background:#000;color:#fff;border:none;border-radius:4px}
    @media print { .print-bar{display:none} body{padding:0} }
</style>
</head><body>

<div class="print-bar">
    <button onclick="window.print()">Print / Save as PDF</button>
    <span style="margin-left:12px;color:#666;">Use your browser's print dialog to save this invoice as PDF.</span>
</div>

<div class="invoice">
    <?php if ($is_proforma): ?>
        <div style="background:#fff8e1;color:#5c4a17;padding:10px;text-align:center;font-weight:700;letter-spacing:2px;border-bottom:2px solid #000;">
            PROFORMA INVOICE — NOT VALID FOR INPUT TAX CREDIT
        </div>
    <?php endif; ?>
    <?php if ($irn && !$is_proforma): ?>
        <div style="background:#dcf8c6;padding:10px 14px;border-bottom:1px solid #000;font-size:10px;display:flex;justify-content:space-between;align-items:center;gap:14px;">
            <div>
                <strong>IRN:</strong> <code style="font-family:monospace;font-size:9px;word-break:break-all;"><?php echo esc_html($irn); ?></code><br>
                <strong>Ack No:</strong> <?php echo esc_html($irn_ack); ?> · <strong>Ack Date:</strong> <?php echo esc_html($irn_dt); ?>
            </div>
            <?php if ($irn_qr): ?>
            <div style="text-align:right;">
                <small style="display:block;">Signed QR Code</small>
                <img src="<?php echo esc_attr(strpos($irn_qr,'data:')===0 ? $irn_qr : 'data:image/png;base64,' . $irn_qr); ?>" alt="IRN QR" style="width:72px;height:72px;background:#fff;padding:4px;">
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="head">
        <div>
            <h1><?php echo $is_proforma ? 'PROFORMA INVOICE' : 'TAX INVOICE'; ?></h1>
            <div style="margin-top:4px;font-size:11px;color:#666;"><?php echo $is_proforma ? 'For confirmation only — final tax invoice on payment' : 'Original for Recipient'; ?></div>
        </div>
        <div class="seller">
            <strong style="font-size:14px;"><?php echo esc_html(luvron_seller_details()['name']); ?></strong><br>
            <?php echo nl2br(esc_html(luvron_seller_details()['address'])); ?><br>
            Phone: <?php echo esc_html(luvron_seller_details()['phone']); ?><br>
            GSTIN: <?php echo esc_html(luvron_seller_details()['gstin']); ?><br>
            State: <?php echo esc_html(luvron_seller_details()['state']); ?> (<?php echo esc_html(luvron_seller_details()['state_code']); ?>)
        </div>
    </div>

    <div class="meta">
        <div>
            <h3>Invoice Details</h3>
            <strong>Invoice No:</strong> <?php echo esc_html($invoice_no); ?><br>
            <strong>Date:</strong> <?php echo esc_html($invoice_date); ?><br>
            <strong>Order Ref:</strong> #<?php echo esc_html($order->get_order_number()); ?><br>
            <strong>Place of Supply:</strong> <?php echo esc_html($billing_state); ?>
        </div>
        <div>
            <h3>Bill To</h3>
            <strong><?php echo esc_html($order->get_billing_company() ?: $order->get_formatted_billing_full_name()); ?></strong><br>
            <?php echo wp_kses_post($order->get_formatted_billing_address()); ?><br>
            GSTIN: <?php echo esc_html($billing_gstin); ?><br>
            Phone: <?php echo esc_html($order->get_billing_phone()); ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Description</th>
                <th>HSN</th>
                <th class="num">Qty</th>
                <th class="num">Unit ₹</th>
                <th class="num">Taxable ₹</th>
                <th class="num">GST %</th>
                <th class="num">Tax ₹</th>
                <th class="num">Total ₹</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i => $row): ?>
            <tr>
                <td><?php echo $i + 1; ?></td>
                <td>
                    <strong><?php echo esc_html($row['name']); ?></strong><br>
                    <small style="color:#666;">SKU: <?php echo esc_html($row['sku']); ?></small>
                </td>
                <td><?php echo esc_html($row['hsn']); ?></td>
                <td class="num"><?php echo (int) $row['qty']; ?></td>
                <td class="num"><?php echo number_format($row['unit'], 2); ?></td>
                <td class="num"><?php echo number_format($row['sub'], 2); ?></td>
                <td class="num"><?php echo number_format($row['gst_rate'], 0); ?>%</td>
                <td class="num"><?php echo number_format($row['tax'], 2); ?></td>
                <td class="num"><?php echo number_format($row['total'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div>
            <h3 style="font-size:11px;text-transform:uppercase;color:#666;margin-bottom:8px;">Amount in Words</h3>
            <strong><?php echo esc_html($words); ?> Rupees Only</strong>

            <h3 style="font-size:11px;text-transform:uppercase;color:#666;margin:16px 0 8px;">Bank Details</h3>
            <?php echo esc_html(luvron_bank_details()['beneficiary']); ?><br>
            <?php echo esc_html(luvron_bank_details()['bank']); ?> · <?php echo esc_html(luvron_bank_details()['branch']); ?><br>
            A/C: <code><?php echo esc_html(luvron_bank_details()['account_no']); ?></code><br>
            IFSC: <code><?php echo esc_html(luvron_bank_details()['ifsc']); ?></code>
        </div>
        <div>
            <table>
                <tr><td>Subtotal (Taxable)</td><td class="num">₹ <?php echo number_format($sub, 2); ?></td></tr>
                <?php if ($is_intrastate): ?>
                    <tr><td>CGST</td><td class="num">₹ <?php echo number_format($cgst, 2); ?></td></tr>
                    <tr><td>SGST</td><td class="num">₹ <?php echo number_format($sgst, 2); ?></td></tr>
                <?php else: ?>
                    <tr><td>IGST</td><td class="num">₹ <?php echo number_format($igst, 2); ?></td></tr>
                <?php endif; ?>
                <?php if ($order->get_shipping_total() > 0): ?>
                    <tr><td>Shipping</td><td class="num">₹ <?php echo number_format($order->get_shipping_total(), 2); ?></td></tr>
                <?php endif; ?>
                <tr class="grand"><td>Grand Total</td><td class="num">₹ <?php echo number_format($grand, 2); ?></td></tr>
            </table>
        </div>
    </div>

    <div class="footer">
        <div style="font-size:10px;color:#666;line-height:1.5;max-width:60%;">
            <strong>Terms:</strong> Goods once sold will not be taken back. Interest @ 24% p.a. charged on delayed payments.
            Subject to Ghaziabad jurisdiction. E. & O. E.
        </div>
        <div class="sign">
            For <?php echo esc_html(luvron_seller_details()['name']); ?><br>
            <small>Authorised Signatory</small>
        </div>
    </div>
</div>

<script>setTimeout(function(){ if(window.location.search.indexOf('autoprint')>-1) window.print(); }, 300);</script>
</body></html>
    <?php
}

function luvron_number_to_words($num) {
    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
             'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    $convert_two = function ($n) use ($ones, $tens) {
        if ($n < 20) return $ones[$n];
        $t = (int) ($n / 10);
        $o = $n % 10;
        return trim($tens[$t] . ' ' . $ones[$o]);
    };

    if ($num == 0) return 'Zero';

    $crore = (int) ($num / 10000000);
    $num   = $num % 10000000;
    $lakh  = (int) ($num / 100000);
    $num   = $num % 100000;
    $thou  = (int) ($num / 1000);
    $num   = $num % 1000;
    $hun   = (int) ($num / 100);
    $rest  = $num % 100;

    $out = '';
    if ($crore) $out .= $convert_two($crore) . ' Crore ';
    if ($lakh)  $out .= $convert_two($lakh) . ' Lakh ';
    if ($thou)  $out .= $convert_two($thou) . ' Thousand ';
    if ($hun)   $out .= $ones[$hun] . ' Hundred ';
    if ($rest)  $out .= ($out ? 'and ' : '') . $convert_two($rest);

    return trim($out);
}

add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {
    $invoice_url = add_query_arg('luvron_invoice', $order->get_id(), home_url('/'));
    echo '<p><a class="button" target="_blank" href="' . esc_url($invoice_url) . '">View Luvron GST Invoice</a></p>';
});

add_action('woocommerce_email_order_meta', function ($order, $sent_to_admin, $plain_text) {
    if ($plain_text) return;
    $invoice_url = add_query_arg('luvron_invoice', $order->get_id(), home_url('/'));
    echo '<p style="margin:24px 0;"><a href="' . esc_url($invoice_url) . '" '
       . 'style="background:#000;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block;">'
       . 'Download GST Invoice</a></p>';
}, 10, 3);
