<?php
/**
 * Plugin Name: Luvron — Kill Switch (LOAD FIRST)
 * Description: Provides per-mu-plugin kill switch. Each Luvron plugin checks
 *              `luvron_disabled('plugin-slug')` early and bails if disabled.
 *              Disable via WP-CLI: wp option update luvron_disabled_plugins '["luvron-pricing"]' --format=json
 * Version: 1.0.0
 *
 * Filename starts with "luvron-aa-" to ensure it loads before other luvron-* mu-plugins.
 */

if (!defined('ABSPATH')) exit;

function luvron_disabled($slug) {
    $disabled = get_option('luvron_disabled_plugins', []);
    if (!is_array($disabled)) return false;
    return in_array($slug, $disabled, true);
}

function luvron_kill_all_check() {
    if (defined('LUVRON_KILL_ALL') && LUVRON_KILL_ALL) return true;
    return get_option('luvron_kill_all', '') === '1';
}

if (luvron_kill_all_check()) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>Luvron Kill Switch ACTIVE</strong> — all Luvron plugins are disabled. '
           . 'Run <code>wp option update luvron_kill_all 0</code> to re-enable.</p></div>';
    });
    return;
}

add_action('admin_menu', function () {
    add_management_page('Luvron Kill Switch', 'Luvron Kill Switch', 'manage_options',
        'luvron-killswitch', 'luvron_killswitch_page');
});

function luvron_killswitch_page() {
    if (!current_user_can('manage_options')) return;

    $slugs = ['luvron-roles', 'luvron-pricing', 'luvron-quote', 'luvron-moq',
              'luvron-bulk-order', 'luvron-dealer-dashboard', 'luvron-invoice',
              'luvron-schema', 'luvron-whatsapp', 'luvron-whatsapp-api',
              'luvron-homepage-pattern', 'luvron-seed', 'luvron-payments',
              'luvron-proforma', 'luvron-einvoice', 'luvron-fulfillment',
              'luvron-smtp', 'luvron-gstin-validate', 'luvron-freight',
              'luvron-quick-quote', 'luvron-pricelist-pdf', 'luvron-comparison',
              'luvron-testimonials', 'luvron-trust-badges', 'luvron-legal-pages'];

    if (isset($_POST['luvron_killswitch_save']) && check_admin_referer('luvron_killswitch')) {
        $disabled = isset($_POST['disabled']) && is_array($_POST['disabled']) ? $_POST['disabled'] : [];
        $disabled = array_map('sanitize_text_field', $disabled);
        update_option('luvron_disabled_plugins', $disabled);
        update_option('luvron_kill_all', !empty($_POST['kill_all']) ? '1' : '0');
        echo '<div class="notice notice-success"><p>Saved.</p></div>';
    }

    $disabled = get_option('luvron_disabled_plugins', []);
    $kill_all = get_option('luvron_kill_all', '') === '1';
    ?>
    <div class="wrap">
        <h1>Luvron Kill Switch</h1>
        <p>Disable individual Luvron mu-plugins or all at once. Useful when debugging a faulty release without filesystem access.</p>
        <form method="post">
            <?php wp_nonce_field('luvron_killswitch'); ?>
            <p><label><input type="checkbox" name="kill_all" value="1" <?php checked($kill_all); ?>>
                <strong>Kill ALL Luvron plugins</strong> (instant fallback to vanilla WC)</label></p>
            <h2>Per-plugin disable</h2>
            <table class="widefat striped">
                <thead><tr><th>Plugin</th><th>Disabled?</th></tr></thead>
                <tbody>
                <?php foreach ($slugs as $s): ?>
                <tr>
                    <td><code><?php echo esc_html($s); ?></code></td>
                    <td><label><input type="checkbox" name="disabled[]" value="<?php echo esc_attr($s); ?>"
                        <?php checked(in_array($s, (array) $disabled, true)); ?>></label></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p><button class="button button-primary" name="luvron_killswitch_save" value="1">Save</button></p>
        </form>
    </div>
    <?php
}
