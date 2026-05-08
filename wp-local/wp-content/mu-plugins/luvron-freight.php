<?php
/**
 * Plugin Name: Luvron — Freight Calculator
 * Description: State-wise flat-rate freight estimator. Adds freight estimate to cart, checkout,
 *              proforma, and bulk-order grid. Admin can override per order on the proforma stage.
 * Version: 1.0.0
 *
 * Rates are ex-Loni (Ghaziabad UP) base; per-carton, surface transport.
 * Override the matrix via filter `luvron_freight_matrix` if needed.
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-freight')) return;

function luvron_freight_matrix() {
    return apply_filters('luvron_freight_matrix', [
        // state_code => [tier1_rate (1-5 cartons), tier2_rate (6-20), tier3_rate (21+)] ₹/carton
        'UP' => [120, 90, 70],   'DL' => [180, 140, 110],
        'HR' => [200, 160, 130], 'PB' => [320, 260, 220],
        'RJ' => [380, 320, 270], 'UT' => [350, 290, 240],
        'HP' => [420, 360, 310], 'JK' => [620, 540, 480],
        'LA' => [820, 720, 640], 'MP' => [440, 380, 330],
        'CT' => [520, 440, 380], 'GJ' => [560, 480, 420],
        'MH' => [640, 540, 460], 'GA' => [780, 660, 580],
        'KA' => [820, 700, 620], 'KL' => [920, 800, 720],
        'TN' => [880, 760, 680], 'AP' => [840, 720, 640],
        'TS' => [820, 700, 620], 'OR' => [620, 540, 480],
        'WB' => [580, 500, 440], 'JH' => [560, 480, 420],
        'BR' => [480, 410, 360], 'AS' => [820, 720, 640],
        'AR' => [1100,980, 880], 'MN' => [1100,980, 880],
        'ML' => [980, 880, 780], 'MZ' => [1100,980, 880],
        'NL' => [1100,980, 880], 'TR' => [1100,980, 880],
        'SK' => [880, 780, 700], 'AN' => [1800,1600,1400],
        'CH' => [200, 160, 130], 'DN' => [580, 500, 440],
        'LD' => [1800,1600,1400],'PY' => [880, 760, 680],
    ]);
}

function luvron_freight_estimate($state_code, $cartons) {
    $matrix = luvron_freight_matrix();
    $state_code = strtoupper(trim($state_code));
    if (!isset($matrix[$state_code])) {
        return ['per_carton' => 600, 'total' => 600 * $cartons, 'note' => 'Default rate — confirm at proforma'];
    }
    list($t1, $t2, $t3) = $matrix[$state_code];
    if     ($cartons <= 5)   $rate = $t1;
    elseif ($cartons <= 20)  $rate = $t2;
    else                     $rate = $t3;
    return [
        'per_carton' => $rate,
        'total'      => $rate * $cartons,
        'cartons'    => $cartons,
        'state'      => $state_code,
        'note'       => 'Indicative surface freight ex-Loni Ghaziabad. Actual confirmed at proforma.',
    ];
}

add_action('woocommerce_cart_calculate_fees', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    $state = WC()->customer ? WC()->customer->get_shipping_state() : '';
    if (!$state) return;

    $cartons = 0;
    foreach ($cart->get_cart() as $item) {
        $product_id = !empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
        $pack_master = (int) get_post_meta($product_id, '_pack_master', true);
        if ($pack_master > 0) {
            $cartons += (int) ceil($item['quantity'] / $pack_master);
        }
    }
    if ($cartons <= 0) return;

    $est = luvron_freight_estimate($state, $cartons);
    $cart->add_fee('Freight estimate (' . $cartons . ' carton' . ($cartons > 1 ? 's' : '') . ')', $est['total'], false);
});

add_shortcode('luvron_freight_calculator', function () {
    $matrix = luvron_freight_matrix();
    ob_start();
    ?>
    <div class="luvron-freight-calc" style="background:#f7f7f7;padding:20px;border-radius:8px;max-width:600px;">
        <h3 style="margin-top:0;">Freight Calculator</h3>
        <p style="font-size:13px;color:#666;">Indicative surface freight ex-Loni Ghaziabad UP. Final freight confirmed at proforma stage.</p>
        <p>
            <label>Destination state<br>
                <select id="luvron-fc-state" style="width:100%;padding:8px;">
                    <option value="">— select —</option>
                    <?php foreach ($matrix as $code => $rates): ?>
                        <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($code); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </p>
        <p>
            <label>Cartons<br>
                <input type="number" id="luvron-fc-cartons" min="1" value="5" style="width:100%;padding:8px;">
            </label>
        </p>
        <button type="button" class="button alt" id="luvron-fc-btn">Estimate</button>
        <div id="luvron-fc-result" style="margin-top:16px;font-size:15px;"></div>
        <script>
        (function(){
            const matrix = <?php echo wp_json_encode($matrix); ?>;
            const btn   = document.getElementById('luvron-fc-btn');
            const out   = document.getElementById('luvron-fc-result');
            const stEl  = document.getElementById('luvron-fc-state');
            const ctEl  = document.getElementById('luvron-fc-cartons');
            btn.addEventListener('click', function(){
                while (out.firstChild) out.removeChild(out.firstChild);
                const s = stEl.value;
                const c = parseInt(ctEl.value, 10) || 0;
                if (!s || c < 1) {
                    out.textContent = 'Select state and cartons.';
                    return;
                }
                const r = matrix[s];
                const rate = c <= 5 ? r[0] : c <= 20 ? r[1] : r[2];
                const total = rate * c;
                const strong = document.createElement('strong');
                strong.textContent = '₹' + rate.toLocaleString('en-IN') + '/carton × ' + c + ' = ₹' + total.toLocaleString('en-IN');
                out.appendChild(strong);
                out.appendChild(document.createElement('br'));
                const note = document.createElement('small');
                note.textContent = 'Indicative; actual confirmed at proforma';
                out.appendChild(note);
            });
        })();
        </script>
    </div>
    <?php
    return ob_get_clean();
});

add_action('woocommerce_admin_order_data_after_shipping_address', function ($order) {
    $state = $order->get_shipping_state() ?: $order->get_billing_state();
    if (!$state) return;
    $cartons = 0;
    foreach ($order->get_items() as $item) {
        $vid = $item->get_variation_id();
        $pid = $item->get_product_id();
        $check = $vid ?: $pid;
        $pack = (int) get_post_meta($check, '_pack_master', true);
        if ($pack > 0) $cartons += (int) ceil($item->get_quantity() / $pack);
    }
    if ($cartons > 0) {
        $est = luvron_freight_estimate($state, $cartons);
        echo '<p><strong>Freight estimate:</strong> ' . esc_html($cartons) . ' cartons × ₹'
           . esc_html(number_format($est['per_carton'])) . ' = <strong>₹'
           . esc_html(number_format($est['total'])) . '</strong></p>';
    }
});
