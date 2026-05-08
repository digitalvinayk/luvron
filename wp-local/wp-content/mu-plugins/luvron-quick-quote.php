<?php
/**
 * Plugin Name: Luvron — Quick Quote (Volume → SKU Match)
 * Description: [luvron_quick_quote] shortcode — dealer enters volume + price band; site returns
 *              matching SKUs with cartons-needed math. Eliminates browse step for power buyers.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-quick-quote')) return;

add_shortcode('luvron_quick_quote', function () {
    if (!is_user_logged_in() || !(current_user_can('dealer') || current_user_can('distributor'))) {
        return '<p>Please <a href="' . esc_url(wp_login_url(get_permalink())) . '">login</a> as a dealer to use Quick Quote.</p>';
    }

    ob_start();
    ?>
    <div class="luvron-qq" style="max-width:780px;">
        <h3>Quick Quote</h3>
        <p>Tell us your target volume and price band. We'll match SKUs and pre-fill your bulk order.</p>

        <form id="luvron-qq-form" style="background:#f7f7f7;padding:20px;border-radius:8px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <p style="grid-column:1/-1;margin:0;">
                <label>Target unit price band (₹/piece)
                    <select name="band" style="width:100%;padding:8px;">
                        <option value="0-700">Under ₹700 (entry tier)</option>
                        <option value="700-1100">₹700–1,100 (mid)</option>
                        <option value="1100-1500" selected>₹1,100–1,500 (premium)</option>
                        <option value="1500-2000">₹1,500–2,000 (top)</option>
                        <option value="0-2000">All</option>
                    </select>
                </label>
            </p>
            <p style="margin:0;">
                <label>Volume (pieces)
                    <input type="number" name="qty" min="12" step="1" value="240" style="width:100%;padding:8px;">
                </label>
            </p>
            <p style="margin:0;">
                <label>Preferred GST rate
                    <select name="gst" style="width:100%;padding:8px;">
                        <option value="">Either</option>
                        <option value="5">5% (base models)</option>
                        <option value="18">18% (PLUS models)</option>
                    </select>
                </label>
            </p>
            <p style="grid-column:1/-1;margin:0;">
                <button type="submit" class="button alt" style="width:100%;padding:12px;">Match SKUs</button>
            </p>
        </form>

        <div id="luvron-qq-results" style="margin-top:20px;"></div>
    </div>

    <script>
    (function () {
        const form = document.getElementById('luvron-qq-form');
        const out  = document.getElementById('luvron-qq-results');

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            while (out.firstChild) out.removeChild(out.firstChild);
            out.textContent = 'Searching…';

            const fd = new FormData(form);
            fd.append('action', 'luvron_quick_quote_search');
            fd.append('nonce', '<?php echo esc_js(wp_create_nonce('luvron_qq')); ?>');

            try {
                const res = await fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {
                    method: 'POST', body: fd, credentials: 'same-origin'
                });
                const json = await res.json();
                while (out.firstChild) out.removeChild(out.firstChild);
                if (!json.success || !json.data.matches.length) {
                    out.textContent = 'No matches in that price band. Try a wider range.';
                    return;
                }

                const heading = document.createElement('h4');
                heading.textContent = 'Matched ' + json.data.matches.length + ' SKUs';
                out.appendChild(heading);

                const table = document.createElement('table');
                table.style.cssText = 'width:100%;border-collapse:collapse;';
                const thead = document.createElement('thead');
                const trh = document.createElement('tr');
                ['SKU','Model','Pack','Unit ₹','GST','Cartons needed','Line ₹'].forEach(t => {
                    const th = document.createElement('th');
                    th.textContent = t;
                    th.style.cssText = 'padding:8px;text-align:left;background:#1a1a1a;color:#fff;font-size:11px;text-transform:uppercase;';
                    trh.appendChild(th);
                });
                thead.appendChild(trh);
                table.appendChild(thead);

                const tbody = document.createElement('tbody');
                json.data.matches.forEach(m => {
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid #eee';
                    [m.sku, m.name, m.pack, '₹'+m.price, m.gst+'%', m.cartons, '₹'+m.line.toLocaleString('en-IN')].forEach(v => {
                        const td = document.createElement('td');
                        td.textContent = v;
                        td.style.padding = '8px';
                        tr.appendChild(td);
                    });
                    tbody.appendChild(tr);
                });
                table.appendChild(tbody);
                out.appendChild(table);

                const cta = document.createElement('p');
                cta.style.marginTop = '20px';
                const btn = document.createElement('a');
                btn.href = '<?php echo esc_js(home_url('/bulk-order/')); ?>?prefill=' + encodeURIComponent(JSON.stringify(json.data.matches.map(m=>({pid:m.pid,vid:m.vid,qty:m.qty}))));
                btn.className = 'button alt';
                btn.textContent = 'Open Bulk Order with these SKUs';
                cta.appendChild(btn);
                out.appendChild(cta);
            } catch (err) {
                out.textContent = 'Error — please try again.';
            }
        });
    })();
    </script>
    <?php
    return ob_get_clean();
});

add_action('wp_ajax_luvron_quick_quote_search', function () {
    if (!check_ajax_referer('luvron_qq', 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid token'], 403);
    }
    if (!is_user_logged_in() || !(current_user_can('dealer') || current_user_can('distributor'))) {
        wp_send_json_error(['message' => 'Unauthorised'], 401);
    }

    list($band_min, $band_max) = array_map('floatval', explode('-', $_POST['band'] ?? '0-2000'));
    $qty = (int) ($_POST['qty'] ?? 0);
    $gst_filter = isset($_POST['gst']) && $_POST['gst'] !== '' ? (int) $_POST['gst'] : null;

    if ($qty < 12) wp_send_json_error(['message' => 'Volume too low (min 12 pieces)']);

    $products = wc_get_products([
        'status'       => 'publish',
        'limit'        => 500,
        'type'         => ['simple', 'variable'],
        'stock_status' => 'instock',
    ]);

    $matches = [];
    foreach ($products as $p) {
        $candidates = $p->is_type('variable') ? array_map('wc_get_product', $p->get_children()) : [$p];
        foreach ($candidates as $c) {
            if (!$c) continue;
            $price = (float) (function_exists('luvron_get_role_price') ? luvron_get_role_price($c) : $c->get_price('edit'));
            if ($price < $band_min || $price > $band_max) continue;

            $gst  = (int) get_post_meta($c->get_id(), '_gst_rate', true);
            if ($gst_filter && $gst !== $gst_filter) continue;

            $pack  = (int) get_post_meta($c->get_id(), '_pack_master', true) ?: 12;
            $cartons = (int) ceil($qty / $pack);
            $line  = $cartons * $pack * $price;

            $is_var = $c->is_type('variation');
            $matches[] = [
                'sku'     => $c->get_sku(),
                'pid'     => $is_var ? $p->get_id() : $c->get_id(),
                'vid'     => $is_var ? $c->get_id() : 0,
                'name'    => $is_var ? $p->get_name() : $c->get_name(),
                'pack'    => (get_post_meta($c->get_id(),'_pack_inner',true) ?: '1x1') . ' / ' . $pack,
                'price'   => number_format($price, 0),
                'gst'     => $gst,
                'cartons' => $cartons,
                'qty'     => $cartons * $pack,
                'line'    => $line,
            ];
        }
    }

    usort($matches, fn($a, $b) => $a['line'] <=> $b['line']);
    $matches = array_slice($matches, 0, 20);

    wp_send_json_success(['matches' => $matches]);
});
