<?php
/**
 * Plugin Name: Luvron — Trust Badges & Stats
 * Description: Renders [luvron_trust_strip] and [luvron_stats] shortcodes — GST badge,
 *              MSME/Udyam badge, dealer count, dispatch SLA, model count.
 *              Configurable via Settings → Luvron Trust.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-trust-badges')) return;

const LUVRON_TRUST_OPT = 'luvron_trust';

add_action('admin_menu', function () {
    add_options_page('Luvron Trust', 'Luvron Trust', 'manage_options',
        'luvron-trust', 'luvron_trust_settings');
});

add_action('admin_init', function () {
    register_setting('luvron_trust_group', LUVRON_TRUST_OPT, [
        'sanitize_callback' => function ($v) {
            return [
                'gstin'      => sanitize_text_field($v['gstin'] ?? '09GOCPP5350G1ZQ'),
                'udyam'      => sanitize_text_field($v['udyam'] ?? 'UDYAM-UP-XX-XXXXXXX'),
                'msme'       => sanitize_text_field($v['msme'] ?? ''),
                'iso'        => sanitize_text_field($v['iso'] ?? ''),
                'founded'    => (int) ($v['founded'] ?? 2018),
                'dealer_count' => (int) ($v['dealer_count'] ?? 50),
                'dispatch_sla' => sanitize_text_field($v['dispatch_sla'] ?? '48 hours'),
                'capacity'   => sanitize_text_field($v['capacity'] ?? '12,000 units / month'),
                'video_url'  => esc_url_raw($v['video_url'] ?? ''),
            ];
        },
    ]);
});

function luvron_trust_settings() {
    $o = get_option(LUVRON_TRUST_OPT, []);
    ?>
    <div class="wrap">
        <h1>Luvron Trust Badges &amp; Stats</h1>
        <p>These appear on the homepage trust strip, footer, and About page.</p>
        <form method="post" action="options.php">
            <?php settings_fields('luvron_trust_group'); ?>
            <table class="form-table">
                <tr><th>GSTIN</th><td><input type="text" class="regular-text" name="<?php echo LUVRON_TRUST_OPT; ?>[gstin]" value="<?php echo esc_attr($o['gstin'] ?? ''); ?>"></td></tr>
                <tr><th>Udyam (MSME) Reg No.</th><td><input type="text" class="regular-text" name="<?php echo LUVRON_TRUST_OPT; ?>[udyam]" value="<?php echo esc_attr($o['udyam'] ?? ''); ?>"></td></tr>
                <tr><th>ISO Certification</th><td><input type="text" class="regular-text" name="<?php echo LUVRON_TRUST_OPT; ?>[iso]" value="<?php echo esc_attr($o['iso'] ?? ''); ?>" placeholder="ISO 9001:2015 (optional)"></td></tr>
                <tr><th>Founded Year</th><td><input type="number" min="1990" max="<?php echo date('Y'); ?>" name="<?php echo LUVRON_TRUST_OPT; ?>[founded]" value="<?php echo (int) ($o['founded'] ?? 2018); ?>"></td></tr>
                <tr><th>Active Dealer Count</th><td><input type="number" min="0" name="<?php echo LUVRON_TRUST_OPT; ?>[dealer_count]" value="<?php echo (int) ($o['dealer_count'] ?? 50); ?>"></td></tr>
                <tr><th>Dispatch SLA</th><td><input type="text" class="regular-text" name="<?php echo LUVRON_TRUST_OPT; ?>[dispatch_sla]" value="<?php echo esc_attr($o['dispatch_sla'] ?? '48 hours'); ?>"></td></tr>
                <tr><th>Manufacturing Capacity</th><td><input type="text" class="regular-text" name="<?php echo LUVRON_TRUST_OPT; ?>[capacity]" value="<?php echo esc_attr($o['capacity'] ?? '12,000 units / month'); ?>"></td></tr>
                <tr><th>Factory Video URL</th><td><input type="url" class="regular-text" name="<?php echo LUVRON_TRUST_OPT; ?>[video_url]" value="<?php echo esc_attr($o['video_url'] ?? ''); ?>" placeholder="YouTube embed URL"></td></tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_shortcode('luvron_stats', function () {
    $o = get_option(LUVRON_TRUST_OPT, []);
    $years = max(1, (int) date('Y') - (int) ($o['founded'] ?? 2018));
    $dealer_count = (int) ($o['dealer_count'] ?? 50);
    $product_count = wp_count_posts('product')->publish ?? 40;

    ob_start(); ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;text-align:center;padding:32px 16px;background:#fafafa;border-radius:8px;">
        <div>
            <div style="font-size:32px;font-weight:800;color:#1a1a1a;"><?php echo $years; ?>+</div>
            <div style="font-size:12px;text-transform:uppercase;color:#666;letter-spacing:1px;">Years Manufacturing</div>
        </div>
        <div>
            <div style="font-size:32px;font-weight:800;color:#1a1a1a;"><?php echo $dealer_count; ?>+</div>
            <div style="font-size:12px;text-transform:uppercase;color:#666;letter-spacing:1px;">Active Dealers</div>
        </div>
        <div>
            <div style="font-size:32px;font-weight:800;color:#1a1a1a;"><?php echo $product_count; ?>+</div>
            <div style="font-size:12px;text-transform:uppercase;color:#666;letter-spacing:1px;">Active SKUs</div>
        </div>
        <div>
            <div style="font-size:32px;font-weight:800;color:#1a1a1a;"><?php echo esc_html($o['dispatch_sla'] ?? '48hr'); ?></div>
            <div style="font-size:12px;text-transform:uppercase;color:#666;letter-spacing:1px;">Dispatch SLA</div>
        </div>
        <div>
            <div style="font-size:32px;font-weight:800;color:#1a1a1a;"><?php echo esc_html($o['capacity'] ?? '12k/mo'); ?></div>
            <div style="font-size:12px;text-transform:uppercase;color:#666;letter-spacing:1px;">Capacity</div>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

add_shortcode('luvron_trust_strip', function () {
    $o = get_option(LUVRON_TRUST_OPT, []);
    ob_start(); ?>
    <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:center;align-items:center;padding:18px 16px;background:#fff8e1;border-bottom:1px solid #f0e0a8;font-size:13px;color:#5c4a17;">
        <span><strong>GSTIN:</strong> <?php echo esc_html($o['gstin'] ?? '09GOCPP5350G1ZQ'); ?></span>
        <span style="opacity:.4;">|</span>
        <span><strong>Udyam:</strong> <?php echo esc_html($o['udyam'] ?? 'UDYAM-UP-XX-XXXXXXX'); ?></span>
        <?php if (!empty($o['iso'])): ?>
            <span style="opacity:.4;">|</span>
            <span><strong><?php echo esc_html($o['iso']); ?></strong></span>
        <?php endif; ?>
        <span style="opacity:.4;">|</span>
        <span>Made in India 🇮🇳</span>
    </div>
    <?php
    return ob_get_clean();
});

add_shortcode('luvron_factory_video', function () {
    $o = get_option(LUVRON_TRUST_OPT, []);
    if (empty($o['video_url'])) {
        return '<div style="background:#1a1a1a;color:#fff;padding:60px 24px;text-align:center;border-radius:8px;">'
             . '<p style="font-size:18px;margin-bottom:8px;">Factory walkthrough video coming soon</p>'
             . '<p style="opacity:.7;font-size:13px;">See how Luvron tricycles are built in our Loni, Ghaziabad facility.</p>'
             . '</div>';
    }
    return '<div class="luvron-video" style="position:relative;padding-bottom:56.25%;height:0;border-radius:8px;overflow:hidden;">'
         . '<iframe src="' . esc_url($o['video_url']) . '" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen></iframe>'
         . '</div>';
});
