<?php
/**
 * Plugin Name: Luvron — SKU Comparison Table
 * Description: [luvron_compare series="SIGMA"] renders a side-by-side comparison of all
 *              variants in a series (base, R1, Plus, R1 Plus). Helps dealers pick.
 *              Auto-injected on category pages via filter.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-comparison')) return;

add_shortcode('luvron_compare', function ($atts) {
    $atts = shortcode_atts(['series' => '', 'limit' => 8], $atts);
    if (!$atts['series']) return '<p><em>Specify series, e.g. [luvron_compare series="SIGMA"]</em></p>';

    $term = get_term_by('name', $atts['series'] . ' series', 'product_cat');
    if (!$term) return '';

    $products = wc_get_products([
        'status'   => 'publish',
        'limit'    => (int) $atts['limit'],
        'category' => [$term->slug],
        'orderby'  => 'menu_order title',
        'order'    => 'ASC',
    ]);
    if (empty($products)) return '';

    $is_dealer = is_user_logged_in() && (current_user_can('dealer') || current_user_can('distributor'));

    ob_start();
    ?>
    <div class="luvron-compare">
        <h3 style="margin:24px 0 12px;">Compare <?php echo esc_html($atts['series']); ?> series</h3>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;min-width:600px;">
                <thead>
                    <tr>
                        <th style="padding:10px;background:#1a1a1a;color:#fff;text-align:left;font-size:11px;text-transform:uppercase;">Feature</th>
                        <?php foreach ($products as $p): ?>
                            <th style="padding:10px;background:#1a1a1a;color:#fff;text-align:center;font-size:11px;">
                                <?php
                                $img = wp_get_attachment_image_url($p->get_image_id(), 'thumbnail');
                                if ($img) echo '<img src="' . esc_url($img) . '" alt="" style="width:80px;height:auto;border-radius:4px;background:#fff;padding:4px;margin-bottom:6px;display:block;margin-left:auto;margin-right:auto;">';
                                echo esc_html($p->get_name());
                                ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding:10px;border-bottom:1px solid #eee;font-weight:600;">SKU</td>
                        <?php foreach ($products as $p): ?>
                            <td style="padding:10px;border-bottom:1px solid #eee;text-align:center;font-family:monospace;font-size:11px;"><?php echo esc_html($p->get_sku()); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td style="padding:10px;border-bottom:1px solid #eee;font-weight:600;">Pack (Inner / Master)</td>
                        <?php foreach ($products as $p): ?>
                            <td style="padding:10px;border-bottom:1px solid #eee;text-align:center;">
                                <?php
                                $inner = get_post_meta($p->get_id(), '_pack_inner', true);
                                $pack  = get_post_meta($p->get_id(), '_pack_master', true);
                                if ($p->is_type('variable')) {
                                    $children = $p->get_children();
                                    $packs = [];
                                    foreach ($children as $vid) {
                                        $vinner = get_post_meta($vid, '_pack_inner', true);
                                        $vpack  = get_post_meta($vid, '_pack_master', true);
                                        if ($vinner) $packs[] = $vinner . '/' . $vpack;
                                    }
                                    echo esc_html(implode(' or ', array_unique($packs)) ?: '—');
                                } else {
                                    echo esc_html(($inner ?: '—') . ' / ' . ($pack ?: '—'));
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td style="padding:10px;border-bottom:1px solid #eee;font-weight:600;">GST</td>
                        <?php foreach ($products as $p): ?>
                            <td style="padding:10px;border-bottom:1px solid #eee;text-align:center;">
                                <?php echo (int) get_post_meta($p->get_id(), '_gst_rate', true); ?>%
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td style="padding:10px;border-bottom:1px solid #eee;font-weight:600;">Canopy</td>
                        <?php foreach ($products as $p): ?>
                            <td style="padding:10px;border-bottom:1px solid #eee;text-align:center;">
                                <?php echo (stripos($p->get_name(), 'plus') !== false) ? '✓ Yes' : '<span style="color:#aaa;">—</span>'; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr style="background:#fff8e1;">
                        <td style="padding:14px 10px;border-bottom:1px solid #eee;font-weight:600;">Unit Price</td>
                        <?php foreach ($products as $p): ?>
                            <td style="padding:14px 10px;border-bottom:1px solid #eee;text-align:center;font-weight:700;font-size:16px;">
                                <?php
                                if ($is_dealer) {
                                    $price = function_exists('luvron_get_role_price') ? luvron_get_role_price($p) : $p->get_price();
                                    echo '₹' . esc_html(number_format((float) $price, 0));
                                } else {
                                    echo '<a href="' . esc_url(home_url('/request-quote/?pid=' . $p->get_id())) . '" style="font-size:11px;font-weight:400;">Request Quote</a>';
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td></td>
                        <?php foreach ($products as $p): ?>
                            <td style="padding:10px;text-align:center;">
                                <a class="button" href="<?php echo esc_url(get_permalink($p->get_id())); ?>" style="font-size:12px;padding:6px 12px;">View</a>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

add_action('woocommerce_archive_description', function () {
    if (!is_product_category()) return;
    $term = get_queried_object();
    if (!$term || empty($term->name)) return;
    $series = trim(str_ireplace(' series', '', $term->name));
    if ($series && $series !== $term->name) {
        echo do_shortcode('[luvron_compare series="' . esc_attr($series) . '"]');
    }
}, 20);
