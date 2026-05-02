<?php
/**
 * Plugin Name: Luvron — Auto-Seed (Products + Pages + Categories)
 * Description: One-shot seed: creates all 44 SKUs (simple + variable), categories, tax classes,
 *              and the full page set (Home, Bulk Order, Dealer Dashboard, Request Quote,
 *              Become a Dealer, Catalogue) directly in WordPress. No CSV import needed.
 *              Idempotent — won't duplicate on re-run. Admin → Tools → Luvron Seed to re-run manually.
 * Version: 1.0.0
 *
 * Image expectation: place product JPGs at wp-content/uploads/luvron-products/<sku-slug>.jpg
 * Bootstrap script copies them automatically.
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-seed')) return;

const LUVRON_SEED_FLAG = 'luvron_seed_v2_done';
const LUVRON_IMAGE_DIR = 'luvron-products';

function luvron_product_data() {
    return [
        // ─── SINGLE-PACK SIMPLE PRODUCTS (1×1 inner, master 12) ─────────────────
        ['SIGMA-PLUS','SIGMA Plus Tricycle','SIGMA',1760,1637,18,'1x1',12,'95030010','sigma-plus.jpg','Premium SIGMA series tricycle with canopy and parental push handle.'],
        ['SIGMA-BASE','SIGMA Tricycle','SIGMA',1700,1581,5,'1x1',12,'87120010','sigma.jpg','SIGMA series base model tricycle. Master carton of 12.'],
        ['SIGMA-R1-PLUS','SIGMA R1 Plus Tricycle','SIGMA',1660,1544,18,'1x1',12,'95030010','sigma-r1-plus.jpg','SIGMA R1 Plus variant with canopy.'],
        ['SIGMA-R1','SIGMA R1 Tricycle','SIGMA',1600,1488,5,'1x1',12,'87120010','sigma-r1.jpg','SIGMA R1 base variant tricycle.'],
        ['AURA-PLUS','AURA Plus Tricycle','AURA',1670,1553,18,'1x1',12,'95030010','aura-plus.jpg','AURA Plus tricycle with canopy.'],
        ['AURA-BASE','AURA Tricycle','AURA',1610,1497,5,'1x1',12,'87120010','aura.jpg','AURA base tricycle.'],
        ['AURA-R1-PLUS','AURA R1 Plus Tricycle','AURA',1570,1460,18,'1x1',12,'95030010','aura-r1-plus.jpg','AURA R1 Plus with canopy.'],
        ['AURA-R1','AURA R1 Tricycle','AURA',1510,1404,5,'1x1',12,'87120010','aura-r1.jpg','AURA R1 base tricycle.'],
        ['EAGLE-PLUS','EAGLE Plus Tricycle','EAGLE',1535,1428,18,'1x1',12,'95030010','eagle-plus.jpg','EAGLE Plus with canopy.'],
        ['EAGLE-BASE','EAGLE Tricycle','EAGLE',1475,1372,5,'1x1',12,'87120010','eagle.jpg','EAGLE base tricycle.'],
        ['EAGLE-R1-PLUS','EAGLE R1 Plus Tricycle','EAGLE',1435,1335,18,'1x1',12,'95030010','eagle-r1-plus.jpg','EAGLE R1 Plus with canopy.'],
        ['EAGLE-R1','EAGLE R1 Tricycle','EAGLE',1375,1279,5,'1x1',12,'87120010','eagle-r1.jpg','EAGLE R1 base tricycle.'],
        ['ALEX-PLUS','ALEX Plus Tricycle','ALEX',1595,1483,18,'1x1',12,'95030010','alex-plus.jpg','ALEX Plus with canopy.'],
        ['ALEX-BASE','ALEX Tricycle','ALEX',1535,1428,5,'1x1',12,'87120010','alex.jpg','ALEX base tricycle.'],
        ['ALEX-R1-PLUS','ALEX R1 Plus Tricycle','ALEX',1495,1390,18,'1x1',12,'95030010','alex-r1-plus.jpg','ALEX R1 Plus with canopy.'],
        ['ALEX-R1','ALEX R1 Tricycle','ALEX',1435,1335,5,'1x1',12,'87120010','alex-r1.jpg','ALEX R1 base tricycle.'],
        ['ECOTECH-PLUS','ECOTECH Plus Tricycle','ECOTECH',1430,1330,18,'1x1',12,'95030010','ecotech-plus.jpg','ECOTECH Plus with canopy.'],
        ['ECOTECH-BASE','ECOTECH Tricycle','ECOTECH',1370,1274,5,'1x1',12,'87120010','ecotech.jpg','ECOTECH base tricycle.'],
        ['ECOTECH-R1-PLUS','ECOTECH R1 Plus Tricycle','ECOTECH',1320,1228,18,'1x1',12,'95030010','ecotech-r1-plus.jpg','ECOTECH R1 Plus with canopy.'],
        ['ECOTECH-R1','ECOTECH R1 Tricycle','ECOTECH',1270,1181,5,'1x1',12,'87120010','ecotech-r1.jpg','ECOTECH R1 base tricycle.'],
        ['RAMBO-333-PLUS','RAMBO 333 Plus Tricycle','RAMBO',1240,1153,18,'3x4',12,'95030010','rambo-333-plus.jpg','RAMBO 333 Plus tricycle.'],
        ['RAMBO-333','RAMBO 333 Tricycle','RAMBO',1180,1097,5,'3x4',12,'87120010','rambo-333.jpg','RAMBO 333 base tricycle.'],
        ['RAMBO-333-R1-PLUS','RAMBO 333 R1 Plus Tricycle','RAMBO',1160,1079,18,'3x4',12,'95030010','rambo-333-r1-plus.jpg','RAMBO 333 R1 Plus tricycle.'],
        ['RAMBO-333-R1','RAMBO 333 R1 Tricycle','RAMBO',1100,1023,5,'3x4',12,'87120010','rambo-333-r1.jpg','RAMBO 333 R1 base tricycle.'],
        ['RAMBO-111-PLUS','RAMBO 111 Plus Tricycle','RAMBO',680,632,18,'6x4',24,'95030010','rambo-111-plus.jpg','RAMBO 111 Plus tricycle.'],
        ['RAMBO-111','RAMBO 111 Tricycle','RAMBO',620,577,5,'6x4',24,'87120010','rambo-111.jpg','RAMBO 111 base tricycle.'],
        ['RAMBO-100-PLUS','RAMBO 100 Plus Tricycle','RAMBO',620,577,18,'6x4',24,'95030010','rambo-100-plus.jpg','RAMBO 100 Plus tricycle.'],
        ['RAMBO-100','RAMBO 100 Tricycle','RAMBO',560,521,5,'6x4',24,'87120010','rambo-100.jpg','RAMBO 100 base tricycle.'],
    ];
}

function luvron_variable_product_data() {
    // [parent_sku, name, series, gst, hsn, image, [variations...]]
    // Each variation: [child_sku, label, dealer, distributor, pack_inner, pack_master]
    return [
        ['LEON-R1-GREY-PLUS','LEON R1 Grey Plus Tricycle','LEON',18,'95030010','leon-r1-grey-plus.jpg',[
            ['LEON-R1-GREY-PLUS-IB','Inner Box',1200,1116,'1x1',15],
            ['LEON-R1-GREY-PLUS-MC','Master Carton',1150,1070,'3x4',12],
        ]],
        ['LEON-R1-GREY','LEON R1 Grey Tricycle','LEON',5,'87120010','leon-r1-grey.jpg',[
            ['LEON-R1-GREY-IB','Inner Box',1140,1060,'1x1',15],
            ['LEON-R1-GREY-MC','Master Carton',1090,1014,'3x4',12],
        ]],
        ['LION-R1-PLUS','LION R1 Plus Tricycle','LION',18,'95030010','lion-r1-plus.jpg',[
            ['LION-R1-PLUS-IB','Inner Box',1140,1060,'1x1',15],
            ['LION-R1-PLUS-MC','Master Carton',1080,1004,'3x4',12],
        ]],
        ['LION-R1','LION R1 Tricycle','LION',5,'87120010','lion-r1.jpg',[
            ['LION-R1-IB','Inner Box',1070,995,'1x1',15],
            ['LION-R1-MC','Master Carton',1020,949,'3x4',12],
        ]],
        ['CHARLIE-R1-PLUS','CHARLIE R1 Plus Tricycle','CHARLIE',18,'95030010','charlie-r1-plus.jpg',[
            ['CHARLIE-R1-PLUS-IB','Inner Box',1020,949,'1x1',15],
            ['CHARLIE-R1-PLUS-MC','Master Carton',970,902,'4x4',16],
        ]],
        ['CHARLIE-R1','CHARLIE R1 Tricycle','CHARLIE',5,'87120010','charlie-r1.jpg',[
            ['CHARLIE-R1-IB','Inner Box',970,902,'1x1',15],
            ['CHARLIE-R1-MC','Master Carton',920,856,'4x4',16],
        ]],
        ['EMMA-R1-PLUS','EMMA R1 Plus Tricycle','EMMA',18,'95030010','emma-r1-plus.jpg',[
            ['EMMA-R1-PLUS-IB','Inner Box',1100,1023,'1x1',15],
            ['EMMA-R1-PLUS-MC','Master Carton',1050,977,'4x4',16],
        ]],
        ['EMMA-R1','EMMA R1 Tricycle','EMMA',5,'87120010','emma-r1.jpg',[
            ['EMMA-R1-IB','Inner Box',1050,977,'1x1',15],
            ['EMMA-R1-MC','Master Carton',1000,930,'4x4',16],
        ]],
        ['CHARLIE-PLUS','CHARLIE Plus Tricycle','CHARLIE',18,'95030010','charlie-plus.jpg',[
            ['CHARLIE-PLUS-IB','Inner Box',820,763,'1x1',15],
            ['CHARLIE-PLUS-MC','Master Carton',770,716,'5x4',20],
        ]],
        ['CHARLIE-BASE','CHARLIE Tricycle','CHARLIE',5,'87120010','charlie.jpg',[
            ['CHARLIE-BASE-IB','Inner Box',770,716,'1x1',15],
            ['CHARLIE-BASE-MC','Master Carton',720,670,'5x4',20],
        ]],
        ['HULK-PRO-PLUS','HULK Pro Plus Tricycle','HULK',18,'95030010','hulk-pro-plus.jpg',[
            ['HULK-PRO-PLUS-IB','Inner Box',870,809,'1x1',15],
            ['HULK-PRO-PLUS-MC','Master Carton',820,763,'4x4',16],
        ]],
        ['HULK-PRO','HULK Pro Tricycle','HULK',5,'87120010','hulk-pro.jpg',[
            ['HULK-PRO-IB','Inner Box',820,763,'1x1',15],
            ['HULK-PRO-MC','Master Carton',770,716,'4x4',16],
        ]],
        ['HULK-PLUS','HULK Plus Tricycle','HULK',18,'95030010','hulk-plus.jpg',[
            ['HULK-PLUS-IB','Inner Box',850,791,'1x1',15],
            ['HULK-PLUS-MC','Master Carton',800,744,'4x4',16],
        ]],
        ['HULK-BASE','HULK Tricycle','HULK',5,'87120010','hulk.jpg',[
            ['HULK-BASE-IB','Inner Box',800,744,'1x1',15],
            ['HULK-BASE-MC','Master Carton',750,698,'4x4',16],
        ]],
        ['EMMA-PLUS','EMMA Plus Tricycle','EMMA',18,'95030010','emma-plus.jpg',[
            ['EMMA-PLUS-IB','Inner Box',880,818,'1x1',15],
            ['EMMA-PLUS-MC','Master Carton',830,772,'5x4',20],
        ]],
        ['EMMA-BASE','EMMA Tricycle','EMMA',5,'87120010','emma.jpg',[
            ['EMMA-BASE-IB','Inner Box',830,772,'1x1',15],
            ['EMMA-BASE-MC','Master Carton',780,725,'5x4',20],
        ]],
    ];
}

function luvron_get_or_create_term($name, $taxonomy, $parent = 0) {
    $term = get_term_by('name', $name, $taxonomy);
    if ($term) return $term->term_id;
    $created = wp_insert_term($name, $taxonomy, ['parent' => $parent]);
    return is_wp_error($created) ? 0 : $created['term_id'];
}

function luvron_attach_image_to_product($product_id, $filename) {
    $upload_dir = wp_upload_dir();
    $src_path   = trailingslashit($upload_dir['basedir']) . LUVRON_IMAGE_DIR . '/' . $filename;
    if (!file_exists($src_path)) return 0;

    // Reuse if already in media library
    $existing = get_posts([
        'post_type'  => 'attachment',
        'meta_key'   => '_luvron_seed_filename',
        'meta_value' => $filename,
        'numberposts' => 1,
        'fields'     => 'ids',
    ]);
    if (!empty($existing)) {
        set_post_thumbnail($product_id, $existing[0]);
        return $existing[0];
    }

    $filetype = wp_check_filetype($filename);
    $attach_id = wp_insert_attachment([
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ], $src_path, $product_id);
    if (!$attach_id) return 0;

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $meta = wp_generate_attachment_metadata($attach_id, $src_path);
    wp_update_attachment_metadata($attach_id, $meta);
    update_post_meta($attach_id, '_luvron_seed_filename', $filename);
    set_post_thumbnail($product_id, $attach_id);
    return $attach_id;
}

function luvron_set_simple_meta($product, $args) {
    list($sku, $name, $series, $dealer, $distrib, $gst, $inner, $master, $hsn, $image, $shortdesc) = $args;
    $product->set_sku($sku);
    $product->set_name($name);
    $product->set_short_description($shortdesc);
    $product->set_description("Luvron $name. Inner pack $inner, master carton of $master. GST $gst%.");
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_regular_price((string) $dealer);
    $product->set_price((string) $dealer);
    // Stock management deferred to admin / Tally integration. Default: don't track per-SKU stock,
    // mark all SKUs as in-stock until proven otherwise.
    $product->set_manage_stock(false);
    $product->set_stock_status('instock');
    $product->set_sold_individually(false);
    $product->set_tax_status('taxable');
    $product->set_tax_class($gst == 5 ? 'gst-5' : 'gst-18');

    $cat_parent = luvron_get_or_create_term('Tricycles', 'product_cat');
    $cat_child  = luvron_get_or_create_term("$series series", 'product_cat', $cat_parent);
    $product->set_category_ids(array_filter([$cat_parent, $cat_child]));

    $id = $product->save();

    update_post_meta($id, '_price_dealer', $dealer);
    update_post_meta($id, '_price_distributor', $distrib);
    update_post_meta($id, '_moq_carton', 1);
    update_post_meta($id, '_pack_inner', $inner);
    update_post_meta($id, '_pack_master', $master);
    update_post_meta($id, '_gst_rate', $gst);
    update_post_meta($id, '_hsn_code', $hsn);

    luvron_attach_image_to_product($id, $image);
    return $id;
}

function luvron_seed_simple_products() {
    $created = 0;
    foreach (luvron_product_data() as $row) {
        $sku = $row[0];
        if (wc_get_product_id_by_sku($sku)) continue;
        $product = new WC_Product_Simple();
        luvron_set_simple_meta($product, $row);
        $created++;
    }
    return $created;
}

function luvron_seed_variable_products() {
    $created = 0;
    foreach (luvron_variable_product_data() as $row) {
        list($parent_sku, $name, $series, $gst, $hsn, $image, $variations) = $row;
        if (wc_get_product_id_by_sku($parent_sku)) continue;

        $attr = new WC_Product_Attribute();
        $attr->set_name('Pack type');
        $attr->set_options(array_map(fn($v) => $v[1], $variations));
        $attr->set_visible(true);
        $attr->set_variation(true);

        $parent = new WC_Product_Variable();
        $parent->set_sku($parent_sku);
        $parent->set_name($name);
        $parent->set_status('publish');
        $parent->set_catalog_visibility('visible');
        $parent->set_short_description("Luvron $name. Choose Inner Box or Master Carton pack.");
        $parent->set_description("Luvron $name — kids tricycle. Available in two pack configurations to suit your transit and storage needs.");
        $parent->set_tax_status('taxable');
        $parent->set_tax_class($gst == 5 ? 'gst-5' : 'gst-18');
        $parent->set_attributes([$attr]);

        $cat_parent = luvron_get_or_create_term('Tricycles', 'product_cat');
        $cat_child  = luvron_get_or_create_term("$series series", 'product_cat', $cat_parent);
        $parent->set_category_ids(array_filter([$cat_parent, $cat_child]));

        $parent_id = $parent->save();
        update_post_meta($parent_id, '_gst_rate', $gst);
        update_post_meta($parent_id, '_hsn_code', $hsn);
        update_post_meta($parent_id, '_pack_master', $variations[0][5]);
        luvron_attach_image_to_product($parent_id, $image);

        foreach ($variations as $v) {
            list($child_sku, $label, $dealer, $distrib, $inner, $master) = $v;
            $variation = new WC_Product_Variation();
            $variation->set_parent_id($parent_id);
            $variation->set_sku($child_sku);
            $variation->set_attributes(['pack-type' => $label]);
            $variation->set_regular_price((string) $dealer);
            $variation->set_price((string) $dealer);
            $variation->set_status('publish');
            $variation->set_stock_status('instock');
            $variation->set_tax_class($gst == 5 ? 'gst-5' : 'gst-18');
            $vid = $variation->save();

            update_post_meta($vid, '_price_dealer', $dealer);
            update_post_meta($vid, '_price_distributor', $distrib);
            update_post_meta($vid, '_moq_carton', 1);
            update_post_meta($vid, '_pack_inner', $inner);
            update_post_meta($vid, '_pack_master', $master);
            update_post_meta($vid, '_gst_rate', $gst);
            update_post_meta($vid, '_hsn_code', $hsn);
        }

        WC_Product_Variable::sync($parent_id);
        $created++;
    }
    return $created;
}

function luvron_seed_pages() {
    $pages = [
        'home' => [
            'title'   => 'Home',
            'slug'    => 'home',
            'content' => function_exists('luvron_homepage_pattern_content') ? luvron_homepage_pattern_content() : '<p>Homepage</p>',
        ],
        'request-quote' => [
            'title'   => 'Request Quote',
            'content' => '<p>Tell us what you need and we\'ll respond within 24 hours.</p>[luvron_quote_form]',
        ],
        'become-a-dealer' => [
            'title'   => 'Become a Dealer',
            'content' => function_exists('luvron_become_dealer_pattern_content') ? luvron_become_dealer_pattern_content() : '<p>Apply for a dealer account. Approval within 24 hours.</p>',
        ],
        'bulk-order' => [
            'title'   => 'Bulk Order',
            'content' => '[luvron_bulk_order]',
        ],
        'dealer-dashboard' => [
            'title'   => 'Dealer Dashboard',
            'content' => '[luvron_dealer_dashboard]',
        ],
        'catalogue' => [
            'title'   => 'Catalogue',
            'content' => "<!-- wp:paragraph -->\n<p>Download our 2026 catalogue (PDF).</p>\n<!-- /wp:paragraph -->\n<!-- wp:buttons -->\n<div class=\"wp-block-buttons\"><!-- wp:button --><div class=\"wp-block-button\"><a class=\"wp-block-button__link wp-element-button\" href=\"/wp-content/uploads/luvron-catalogue-feb-26.pdf\" download>Download Catalogue (PDF)</a></div><!-- /wp:button --></div>\n<!-- /wp:buttons -->",
        ],
        'about' => [
            'title'   => 'About Luvron',
            'content' => "<!-- wp:heading --><h2>About Luvron</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>Luvron is a trusted Indian manufacturer of kids tricycles, supplying dealers and distributors across India from our facility in Loni, Ghaziabad UP. We make 14+ model families across 40+ SKUs, all GST-compliant and ready for bulk dispatch.</p><!-- /wp:paragraph -->\n<!-- wp:paragraph --><p><strong>Founded by:</strong> Rajneesh Kumar Pandey<br><strong>Location:</strong> OFF-D-337, T-04, Indraprastha Colony, Jawli Road, Loni, Ghaziabad UP – 201102<br><strong>Phone:</strong> +91-9212389139</p><!-- /wp:paragraph -->",
        ],
        'contact' => [
            'title'   => 'Contact',
            'content' => "<!-- wp:heading --><h2>Contact Luvron</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p><strong>Sales:</strong> +91-9212389139<br><strong>WhatsApp:</strong> <a href=\"https://wa.me/919212389139\">Chat with us</a><br><strong>Email:</strong> orders@luvron.in</p><!-- /wp:paragraph -->\n<!-- wp:paragraph --><p><strong>Address:</strong><br>OFF-D-337, T-04, Indraprastha Colony,<br>Jawli Road, Loni, Ghaziabad UP – 201102</p><!-- /wp:paragraph -->",
        ],
    ];

    $created = 0;
    foreach ($pages as $key => $data) {
        $slug = $data['slug'] ?? sanitize_title($data['title']);
        $existing = get_page_by_path($slug);
        if ($existing) continue;

        $post_id = wp_insert_post([
            'post_title'   => $data['title'],
            'post_name'    => $slug,
            'post_content' => $data['content'],
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
        if ($post_id) $created++;

        if ($key === 'home' && $post_id) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $post_id);
        }
    }
    return $created;
}

function luvron_seed_menu() {
    $menu_name = 'Luvron Primary';
    if (wp_get_nav_menu_object($menu_name)) return 0;

    $menu_id = wp_create_nav_menu($menu_name);
    $items = [
        ['Home',          home_url('/')],
        ['Products',      home_url('/shop/')],
        ['Bulk Order',    home_url('/bulk-order/')],
        ['Become a Dealer', home_url('/become-a-dealer/')],
        ['Catalogue',     home_url('/catalogue/')],
        ['About',         home_url('/about-luvron/')],
        ['Contact',       home_url('/contact/')],
    ];
    foreach ($items as $i => $row) {
        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title'  => $row[0],
            'menu-item-url'    => $row[1],
            'menu-item-status' => 'publish',
            'menu-item-position' => $i + 1,
        ]);
    }

    $locations = get_theme_mod('nav_menu_locations', []);
    foreach (['primary', 'main', 'top', 'header'] as $loc) {
        $locations[$loc] = $menu_id;
    }
    set_theme_mod('nav_menu_locations', $locations);
    return 1;
}

function luvron_seed_tax_classes() {
    $classes = WC_Tax::get_tax_classes();
    if (!in_array('GST 5', $classes, true))  WC_Tax::create_tax_class('GST 5');
    if (!in_array('GST 18', $classes, true)) WC_Tax::create_tax_class('GST 18');
}

function luvron_run_seed() {
    if (!class_exists('WooCommerce')) return ['error' => 'WooCommerce not active'];

    luvron_seed_tax_classes();
    $simple_n   = luvron_seed_simple_products();
    $variable_n = luvron_seed_variable_products();
    $page_n     = luvron_seed_pages();
    $menu_n     = luvron_seed_menu();
    $legal_n    = function_exists('luvron_seed_legal_pages') ? luvron_seed_legal_pages() : 0;
    $testim_n   = function_exists('luvron_seed_testimonials') ? luvron_seed_testimonials() : 0;

    update_option(LUVRON_SEED_FLAG, current_time('mysql'));
    return [
        'simple_products'   => $simple_n,
        'variable_products' => $variable_n,
        'pages'             => $page_n,
        'legal_pages'       => $legal_n,
        'testimonials'      => $testim_n,
        'menu_created'      => $menu_n,
    ];
}

add_action('admin_init', function () {
    if (get_option(LUVRON_SEED_FLAG)) return;
    if (!class_exists('WooCommerce')) return;
    if (defined('DOING_CRON') && DOING_CRON) return;
    luvron_run_seed();
});

add_action('admin_menu', function () {
    add_management_page('Luvron Seed', 'Luvron Seed', 'manage_options',
        'luvron-seed', 'luvron_seed_admin_page');
});

function luvron_seed_admin_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['luvron_run_seed']) && check_admin_referer('luvron_seed')) {
        $result = luvron_run_seed();
        echo '<div class="notice notice-success"><p>Seed complete: '
           . esc_html(wp_json_encode($result)) . '</p></div>';
    }
    if (isset($_POST['luvron_reset_seed']) && check_admin_referer('luvron_seed')) {
        delete_option(LUVRON_SEED_FLAG);
        echo '<div class="notice notice-warning"><p>Seed flag cleared. Re-run to re-seed.</p></div>';
    }

    $done = get_option(LUVRON_SEED_FLAG);
    $product_count = wp_count_posts('product')->publish ?? 0;
    $page_count = wp_count_posts('page')->publish ?? 0;
    ?>
    <div class="wrap">
        <h1>Luvron Auto-Seed</h1>
        <p>One-shot seed for products, categories, pages, menu, tax classes.</p>

        <table class="widefat striped" style="max-width:600px;">
            <tr><th>Last seeded</th><td><?php echo esc_html($done ?: 'Never'); ?></td></tr>
            <tr><th>Published products</th><td><?php echo (int) $product_count; ?></td></tr>
            <tr><th>Published pages</th><td><?php echo (int) $page_count; ?></td></tr>
        </table>

        <form method="post" style="margin-top:20px;">
            <?php wp_nonce_field('luvron_seed'); ?>
            <button class="button button-primary" name="luvron_run_seed" value="1">Run Seed Now</button>
            <button class="button" name="luvron_reset_seed" value="1"
                    onclick="return confirm('Clear the seed flag? Existing products/pages remain; next run will only add missing items.');">
                Reset Seed Flag
            </button>
        </form>

        <h2 style="margin-top:30px;">What this creates</h2>
        <ul style="list-style:disc;margin-left:24px;">
            <li>Tax classes: <code>GST 5</code>, <code>GST 18</code></li>
            <li>Categories: Tricycles + 11 series sub-categories</li>
            <li>28 simple products + 16 variable products (32 variations)</li>
            <li>Pages: Home, Request Quote, Become a Dealer, Bulk Order, Dealer Dashboard, Catalogue, About, Contact</li>
            <li>Primary navigation menu</li>
        </ul>
        <p><strong>Idempotent:</strong> existing SKUs and pages are preserved; only missing items are created.</p>
    </div>
    <?php
}
