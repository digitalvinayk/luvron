<?php
/**
 * Plugin Name: Luvron — GSTIN Validation
 * Description: Validates GSTIN format with checksum AND verifies against the public GSTN
 *              registration database (where API access is available). Caches valid GSTINs
 *              for 30 days to avoid rate limits.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-gstin-validate')) return;

function luvron_gstin_format_valid($gstin) {
    $gstin = strtoupper(trim($gstin));
    if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstin)) {
        return false;
    }
    $checksum_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $factor = 1;
    $sum = 0;
    for ($i = 0; $i < 14; $i++) {
        $code = strpos($checksum_chars, $gstin[$i]);
        if ($code === false) return false;
        $digit = $factor * $code;
        $factor = ($factor === 1) ? 2 : 1;
        $digit = (int) ($digit / 36) + ($digit % 36);
        $sum += $digit;
    }
    $expected = (36 - ($sum % 36)) % 36;
    return $checksum_chars[$expected] === $gstin[14];
}

function luvron_gstin_verify_remote($gstin) {
    $gstin = strtoupper(trim($gstin));
    if (!luvron_gstin_format_valid($gstin)) {
        return new WP_Error('format', 'Invalid GSTIN format or checksum');
    }

    $cache_key = 'luvron_gstin_' . md5($gstin);
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }

    $endpoint = apply_filters('luvron_gstin_verify_endpoint',
        'https://services.gst.gov.in/services/api/search/taxpayerByGstin');

    $res = wp_remote_get($endpoint . '?gstin=' . urlencode($gstin), [
        'timeout' => 10,
        'headers' => ['Accept' => 'application/json'],
    ]);

    if (is_wp_error($res)) {
        return ['valid_format' => true, 'verified' => false, 'reason' => 'network'];
    }

    $code = wp_remote_retrieve_response_code($res);
    $body = json_decode(wp_remote_retrieve_body($res), true);

    if ($code === 200 && !empty($body) && empty($body['error'])) {
        $result = [
            'valid_format' => true,
            'verified'     => true,
            'legal_name'   => $body['lgnm'] ?? $body['tradeNam'] ?? '',
            'state_code'   => substr($gstin, 0, 2),
            'status'       => $body['sts'] ?? 'unknown',
            'pan'          => substr($gstin, 2, 10),
        ];
        set_transient($cache_key, $result, 30 * DAY_IN_SECONDS);
        return $result;
    }

    return ['valid_format' => true, 'verified' => false, 'reason' => 'api_unavailable'];
}

add_action('wp_ajax_nopriv_luvron_validate_gstin', 'luvron_ajax_validate_gstin');
add_action('wp_ajax_luvron_validate_gstin', 'luvron_ajax_validate_gstin');

function luvron_ajax_validate_gstin() {
    $gstin = sanitize_text_field($_REQUEST['gstin'] ?? '');
    if (!$gstin) wp_send_json_error(['message' => 'GSTIN required'], 400);

    if (!luvron_gstin_format_valid($gstin)) {
        wp_send_json_error([
            'message' => 'Invalid GSTIN — check format (15 chars, ends with checksum) or typo',
        ]);
    }

    $result = luvron_gstin_verify_remote($gstin);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }
    wp_send_json_success($result);
}

add_action('wp_footer', function () {
    if (is_admin()) return;
    ?>
    <script>
    document.addEventListener('input', function (e) {
        const t = e.target;
        if (!t.matches('input[name*="gstin" i], input[id*="gstin" i]')) return;
        const v = t.value.trim().toUpperCase();
        if (v.length === 15) {
            t.style.borderColor = '#999';
            const fd = new FormData();
            fd.append('action', 'luvron_validate_gstin');
            fd.append('gstin', v);
            fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {
                method: 'POST', body: fd, credentials: 'same-origin'
            }).then(r => r.json()).then(j => {
                let hint = t.parentElement.querySelector('.luvron-gstin-hint');
                if (!hint) {
                    hint = document.createElement('small');
                    hint.className = 'luvron-gstin-hint';
                    hint.style.cssText = 'display:block;margin-top:4px;font-size:11px;';
                    t.parentElement.appendChild(hint);
                }
                if (j.success) {
                    t.style.borderColor = '#1d6f1d';
                    if (j.data.verified && j.data.legal_name) {
                        hint.style.color = '#1d6f1d';
                        hint.textContent = '✓ ' + j.data.legal_name;
                    } else {
                        hint.style.color = '#ff8c00';
                        hint.textContent = '⚠ Format valid; live verification unavailable';
                    }
                } else {
                    t.style.borderColor = '#c33';
                    hint.style.color = '#c33';
                    hint.textContent = '✗ ' + (j.data && j.data.message || 'Invalid');
                }
            }).catch(() => {});
        }
    });
    </script>
    <?php
});

add_filter('woocommerce_checkout_fields', function ($fields) {
    if (isset($fields['billing']['billing_gstin'])) return $fields;
    $fields['billing']['billing_gstin'] = [
        'label'       => 'GSTIN',
        'placeholder' => '15-character GST Identification Number',
        'required'    => false,
        'class'       => ['form-row-wide'],
        'priority'    => 35,
    ];
    return $fields;
});

add_action('woocommerce_checkout_process', function () {
    if (!empty($_POST['billing_gstin'])) {
        $gstin = sanitize_text_field($_POST['billing_gstin']);
        if (!luvron_gstin_format_valid($gstin)) {
            wc_add_notice('Invalid GSTIN format or checksum. Please re-check.', 'error');
        }
    }
});

add_action('woocommerce_checkout_create_order', function ($order) {
    if (!empty($_POST['billing_gstin'])) {
        $order->update_meta_data('_billing_gstin', sanitize_text_field($_POST['billing_gstin']));
    }
});
