<?php
/**
 * Plugin Name: Luvron — Dealer Testimonials
 * Description: Registers a "testimonial" custom post type and seeds it with the 4 existing
 *              dealer photos extracted from the cPanel backup. Renders carousel via shortcode.
 * Version: 1.0.0
 *
 * Image expectation: testimonial photos at wp-content/uploads/luvron-testimonials/
 *   - aditya-sharma.jpg
 *   - ambuj.jpg
 *   - dinesh-kanojia.jpg
 *   - dinesh.jpg
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-testimonials')) return;

const LUVRON_TESTIMONIAL_FLAG = 'luvron_testimonials_v1';
const LUVRON_TESTIMONIAL_DIR  = 'luvron-testimonials';

add_action('init', function () {
    register_post_type('luvron_testimonial', [
        'label'         => 'Testimonials',
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'menu_icon'     => 'dashicons-format-quote',
        'supports'      => ['title', 'editor', 'thumbnail'],
        'has_archive'   => false,
    ]);
});

function luvron_seed_testimonials() {
    $upload_dir = wp_upload_dir();
    $img_base   = trailingslashit($upload_dir['basedir']) . LUVRON_TESTIMONIAL_DIR . '/';

    $items = [
        [
            'name'    => 'Aditya Sharma',
            'image'   => 'aditya-sharma.jpg',
            'role'    => 'Distributor, Lucknow',
            'quote'   => 'I have been distributing Luvron tricycles for two seasons now. The packaging holds up perfectly even after long-haul transport, and the price points let me run healthy retail margins across all my outlets.',
        ],
        [
            'name'    => 'Ambuj Kumar',
            'image'   => 'ambuj.jpg',
            'role'    => 'Dealer, Patna',
            'quote'   => 'What I value most is the dispatch speed. We confirm an order, the cartons leave Loni in 48 hours. That predictability is rare with kids products in India.',
        ],
        [
            'name'    => 'Dinesh Kanojia',
            'image'   => 'dinesh-kanojia.jpg',
            'role'    => 'Retailer, Delhi NCR',
            'quote'   => 'Luvron Sigma Plus is our bestseller in the premium segment. Customers come back for the second child too — the build quality speaks for itself.',
        ],
        [
            'name'    => 'Dinesh',
            'image'   => 'dinesh.jpg',
            'role'    => 'Dealer, Kanpur',
            'quote'   => 'Direct manufacturer pricing, GST-compliant invoices, and a team that picks up the phone. That is all I need from a supplier.',
        ],
    ];

    $created = 0;
    foreach ($items as $i) {
        $existing = get_posts([
            'post_type'   => 'luvron_testimonial',
            'title'       => $i['name'],
            'numberposts' => 1,
            'fields'      => 'ids',
        ]);
        if (!empty($existing)) continue;

        $id = wp_insert_post([
            'post_type'    => 'luvron_testimonial',
            'post_status'  => 'publish',
            'post_title'   => $i['name'],
            'post_content' => $i['quote'],
        ]);
        if (!$id) continue;

        update_post_meta($id, '_luvron_testimonial_role', $i['role']);

        $src = $img_base . $i['image'];
        if (file_exists($src)) {
            $filetype = wp_check_filetype($i['image']);
            $att = wp_insert_attachment([
                'post_mime_type' => $filetype['type'],
                'post_title'     => sanitize_file_name(pathinfo($i['image'], PATHINFO_FILENAME)),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ], $src, $id);
            if ($att) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                wp_update_attachment_metadata($att, wp_generate_attachment_metadata($att, $src));
                set_post_thumbnail($id, $att);
            }
        }
        $created++;
    }
    update_option(LUVRON_TESTIMONIAL_FLAG, current_time('mysql'));
    return $created;
}

add_action('admin_init', function () {
    if (get_option(LUVRON_TESTIMONIAL_FLAG)) return;
    if (!class_exists('WooCommerce')) return;
    luvron_seed_testimonials();
});

add_shortcode('luvron_testimonials', function ($atts) {
    $atts = shortcode_atts(['limit' => 4], $atts);
    $items = get_posts([
        'post_type'   => 'luvron_testimonial',
        'numberposts' => (int) $atts['limit'],
        'orderby'     => 'rand',
    ]);
    if (empty($items)) return '';

    ob_start();
    ?>
    <div class="luvron-testimonials" style="padding:48px 16px;background:#fafafa;">
        <h2 style="text-align:center;margin-bottom:8px;">What Our Dealers Say</h2>
        <p style="text-align:center;color:#666;margin-bottom:32px;">Trusted by distributors across India</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;max-width:1180px;margin:0 auto;">
            <?php foreach ($items as $t):
                $img = get_the_post_thumbnail_url($t->ID, 'medium');
                $role = get_post_meta($t->ID, '_luvron_testimonial_role', true);
            ?>
            <div style="background:#fff;border-radius:8px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                <div style="display:flex;align-items:center;gap:14px;margin-bottom:12px;">
                    <?php if ($img): ?>
                        <img src="<?php echo esc_url($img); ?>" alt="" style="width:54px;height:54px;border-radius:50%;object-fit:cover;border:2px solid #ff8c00;">
                    <?php else: ?>
                        <div style="width:54px;height:54px;border-radius:50%;background:#ff8c00;color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;">
                            <?php echo esc_html(strtoupper(substr($t->post_title, 0, 1))); ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <strong style="display:block;"><?php echo esc_html($t->post_title); ?></strong>
                        <small style="color:#666;"><?php echo esc_html($role); ?></small>
                    </div>
                </div>
                <p style="font-size:14px;line-height:1.6;color:#333;font-style:italic;">&ldquo;<?php echo esc_html($t->post_content); ?>&rdquo;</p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
});
