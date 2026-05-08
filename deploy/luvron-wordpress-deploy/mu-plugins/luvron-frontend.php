<?php
/**
 * Plugin Name: Luvron — Frontend Brand Styling
 * Description: Injects Luvron brand fonts, colors, and component styles into the WordPress
 *              frontend so it visually matches the design preview at digitalvinayk.github.io/luvron.
 *              Overrides Astra/WooCommerce defaults via high-specificity CSS without a custom theme.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-frontend')) return;

// Astra customizer defaults: brand colors, theme settings
add_action('after_setup_theme', function () {
    // Astra primary color (CTAs, links)
    set_theme_mod('theme-color', '#ff6b5b');
    set_theme_mod('link-color',  '#ff6b5b');
    set_theme_mod('link-h-color', '#e84a3f');

    // Custom logo
    $logo_id = attachment_url_to_postid(content_url('uploads/luvron-logo.png'));
    if (!$logo_id && file_exists(ABSPATH . 'wp-content/uploads/luvron-logo.png')) {
        // Sideload as attachment if not present in media library
        $logo_path = ABSPATH . 'wp-content/uploads/luvron-logo.png';
        $filetype  = wp_check_filetype('luvron-logo.png');
        $att_id = wp_insert_attachment([
            'post_mime_type' => $filetype['type'],
            'post_title'     => 'Luvron Logo',
            'post_content'   => '',
            'post_status'    => 'inherit',
        ], $logo_path);
        if ($att_id && !is_wp_error($att_id)) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            wp_update_attachment_metadata($att_id, wp_generate_attachment_metadata($att_id, $logo_path));
            set_theme_mod('custom_logo', $att_id);
        }
    } elseif ($logo_id) {
        set_theme_mod('custom_logo', $logo_id);
    }

    // Astra-specific settings
    set_theme_mod('site-title-color',     '#0f172a');
    set_theme_mod('header-bg-obj-responsive', ['desktop' => ['background-color' => '#ffffff'], 'tablet' => [], 'mobile' => []]);
}, 20);

// Enqueue brand fonts + stylesheet
add_action('wp_enqueue_scripts', function () {
    // Google Fonts: Bricolage Grotesque (display), Plus Jakarta Sans (body), Caveat (handwritten)
    wp_enqueue_style(
        'luvron-fonts',
        'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300..800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Caveat:wght@500;700&display=swap',
        [],
        null
    );

    // Brand stylesheet — the design system from docs/styles.css adapted for WordPress
    wp_register_style('luvron-brand', false, ['luvron-fonts'], '1.0.0');
    wp_enqueue_style('luvron-brand');
    wp_add_inline_style('luvron-brand', luvron_brand_css());
}, 100);

function luvron_brand_css() {
    return <<<CSS
:root {
    --canvas:    #fdfaf6;
    --bg-cream:  #fff7e6;
    --bg-sky:    #eef5ff;
    --bg-mint:   #ecfdf5;
    --bg-peach:  #fff0ec;
    --bg-lavender:#f3eeff;

    --coral:      #ff6b5b;
    --coral-deep: #e84a3f;
    --coral-soft: #ffd9d3;

    --sunshine:   #ffc93c;
    --sky-deep:   #1c7ed6;
    --mint-deep:  #2f9e44;
    --marigold-deep:#b45309;

    --ink:       #0f172a;
    --ink-soft:  #475569;
    --ink-faint: #94a3b8;
    --line:      #e5e7eb;
    --line-soft: #f1f5f9;

    --shadow-1: 0 1px 2px rgba(15,23,42,.04), 0 2px 6px rgba(15,23,42,.04);
    --shadow-2: 0 6px 18px rgba(15,23,42,.06), 0 14px 36px rgba(15,23,42,.04);
    --shadow-3: 0 18px 48px rgba(15,23,42,.10), 0 4px 12px rgba(15,23,42,.06);

    --radius:    12px;
    --radius-lg: 20px;
    --radius-xl: 28px;
}

/* Base */
body, .ast-container, body.woocommerce {
    font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, sans-serif !important;
    color: var(--ink) !important;
    background: #ffffff !important;
    line-height: 1.55 !important;
    -webkit-font-smoothing: antialiased;
}
h1, h2, h3, h4, h5, h6,
.entry-title, .ast-archive-title, .page-title,
.wp-block-heading {
    font-family: "Bricolage Grotesque", "Plus Jakarta Sans", sans-serif !important;
    font-weight: 700 !important;
    letter-spacing: -0.02em !important;
    color: var(--ink) !important;
    line-height: 1.1 !important;
}
h1 { font-size: clamp(36px, 5vw, 56px) !important; }
h2 { font-size: clamp(28px, 3.5vw, 40px) !important; }
h3 { font-size: clamp(22px, 2.5vw, 28px) !important; }

a, a:visited { color: var(--ink); transition: color .15s ease; }
a:hover { color: var(--coral); }

/* Header (Astra) — bigger logo, clean look */
.site-header,
.ast-primary-header-bar,
.main-header-bar {
    background: #ffffff !important;
    border-bottom: 1px solid var(--line) !important;
    box-shadow: none !important;
}
.site-logo-img img,
.custom-logo-link img,
.ast-site-identity img {
    max-height: 64px !important;
    width: auto !important;
    height: auto !important;
}
@media (max-width: 720px) {
    .site-logo-img img,
    .custom-logo-link img { max-height: 52px !important; }
}

/* Navigation menu */
.main-navigation a,
.main-header-menu a,
.ast-primary-menu .menu-item > a {
    font-weight: 500 !important;
    font-size: 15px !important;
    color: var(--ink) !important;
    transition: color .15s !important;
}
.main-navigation a:hover,
.ast-primary-menu .menu-item > a:hover,
.main-navigation .current-menu-item > a {
    color: var(--coral) !important;
}

/* Buttons (CTA-style across WP, WC, blocks) */
.wp-block-button__link,
.button, button.button, input[type=submit],
.woocommerce a.button, .woocommerce button.button, .woocommerce input.button,
.woocommerce #respond input#submit, .woocommerce-page button.button {
    background: var(--coral) !important;
    color: #ffffff !important;
    font-family: "Plus Jakarta Sans", sans-serif !important;
    font-weight: 600 !important;
    border-radius: 999px !important;
    border: 0 !important;
    padding: 12px 28px !important;
    box-shadow: 0 4px 16px rgba(232,74,63,.25) !important;
    transition: transform .15s, box-shadow .15s, background .15s !important;
    text-transform: none !important;
    letter-spacing: 0 !important;
}
.wp-block-button__link:hover, .button:hover,
button.button:hover, input[type=submit]:hover,
.woocommerce a.button:hover, .woocommerce button.button:hover,
.woocommerce input.button:hover {
    background: var(--coral-deep) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 24px rgba(232,74,63,.32) !important;
    color: #ffffff !important;
}
.is-style-outline .wp-block-button__link {
    background: transparent !important;
    color: var(--ink) !important;
    border: 2px solid var(--ink) !important;
    box-shadow: none !important;
}
.is-style-outline .wp-block-button__link:hover {
    background: var(--ink) !important;
    color: #ffffff !important;
}

/* Cover blocks (hero) — bigger, better */
.wp-block-cover {
    border-radius: 0 !important;
}
.wp-block-cover__inner-container h1,
.wp-block-cover__inner-container h2 {
    color: #ffffff !important;
}

/* WooCommerce — product grid */
.woocommerce ul.products li.product,
.woocommerce-page ul.products li.product {
    background: #ffffff !important;
    border: 1px solid var(--line) !important;
    border-radius: var(--radius-lg) !important;
    padding: 0 !important;
    overflow: hidden !important;
    transition: transform .25s, box-shadow .25s, border-color .25s !important;
    margin-bottom: 28px !important;
}
.woocommerce ul.products li.product:hover {
    transform: translateY(-6px) !important;
    box-shadow: var(--shadow-3) !important;
    border-color: var(--coral-soft) !important;
}
.woocommerce ul.products li.product a img,
.woocommerce ul.products li.product .wp-post-image {
    background: linear-gradient(135deg, var(--bg-cream) 0%, var(--bg-peach) 100%) !important;
    border-radius: 0 !important;
    margin: 0 !important;
    padding: 24px !important;
    transition: transform .35s ease !important;
}
.woocommerce ul.products li.product:hover a img {
    transform: scale(1.04) rotate(-2deg) !important;
}

/* Rotating pastel backgrounds for product images */
.woocommerce ul.products li.product:nth-child(5n+1) a img { background: linear-gradient(135deg, var(--bg-cream), var(--bg-peach)) !important; }
.woocommerce ul.products li.product:nth-child(5n+2) a img { background: linear-gradient(135deg, var(--bg-sky), #d8e8fa) !important; }
.woocommerce ul.products li.product:nth-child(5n+3) a img { background: linear-gradient(135deg, var(--bg-mint), #d4f1d8) !important; }
.woocommerce ul.products li.product:nth-child(5n+4) a img { background: linear-gradient(135deg, var(--bg-lavender), #e7dffa) !important; }
.woocommerce ul.products li.product:nth-child(5n)   a img { background: linear-gradient(135deg, var(--bg-peach), #ffd9d3) !important; }

.woocommerce ul.products li.product .woocommerce-loop-product__title,
.woocommerce ul.products li.product h2 {
    font-family: "Bricolage Grotesque", sans-serif !important;
    font-size: 18px !important;
    font-weight: 700 !important;
    color: var(--ink) !important;
    padding: 16px 20px 6px !important;
    margin: 0 !important;
    letter-spacing: -0.01em !important;
}
.woocommerce ul.products li.product .price {
    color: var(--ink) !important;
    font-family: "Bricolage Grotesque", sans-serif !important;
    font-weight: 700 !important;
    font-size: 18px !important;
    padding: 0 20px 12px !important;
    display: block !important;
}
.woocommerce ul.products li.product .price del { color: var(--ink-faint) !important; opacity: 0.6; }
.woocommerce ul.products li.product .luvron-quote-btn,
.woocommerce ul.products li.product .button.add_to_cart_button {
    margin: 0 20px 20px !important;
    width: calc(100% - 40px) !important;
    text-align: center !important;
    justify-content: center !important;
    display: block !important;
}

/* Single product page */
.woocommerce div.product .product_title {
    font-family: "Bricolage Grotesque", sans-serif !important;
    font-size: clamp(28px, 4vw, 48px) !important;
    font-weight: 700 !important;
    letter-spacing: -0.02em !important;
    margin-bottom: 16px !important;
}
.woocommerce div.product p.price,
.woocommerce div.product span.price {
    color: var(--coral-deep) !important;
    font-family: "Bricolage Grotesque", sans-serif !important;
    font-weight: 700 !important;
    font-size: 28px !important;
}
.woocommerce div.product .quantity .qty {
    border: 1.5px solid var(--line) !important;
    border-radius: 8px !important;
    padding: 10px 14px !important;
}

/* Tabs */
.woocommerce div.product .woocommerce-tabs ul.tabs {
    border-bottom: 1px solid var(--line) !important;
    padding: 0 !important;
}
.woocommerce div.product .woocommerce-tabs ul.tabs li {
    background: transparent !important;
    border: 0 !important;
    border-radius: 0 !important;
    margin: 0 24px 0 0 !important;
    padding: 0 !important;
}
.woocommerce div.product .woocommerce-tabs ul.tabs li a {
    color: var(--ink-soft) !important;
    font-weight: 600 !important;
    padding: 14px 0 !important;
    display: inline-block !important;
    border-bottom: 2px solid transparent !important;
}
.woocommerce div.product .woocommerce-tabs ul.tabs li.active a {
    color: var(--coral) !important;
    border-bottom-color: var(--coral) !important;
}

/* Cart, checkout */
.woocommerce-cart table.cart,
.woocommerce-checkout-review-order-table {
    border: 1px solid var(--line) !important;
    border-radius: var(--radius-lg) !important;
    overflow: hidden !important;
}
.woocommerce table.shop_table {
    border-collapse: collapse !important;
    border-radius: var(--radius-lg) !important;
}
.woocommerce table.shop_table th {
    background: var(--bg-cream) !important;
    color: var(--ink) !important;
    font-family: "Bricolage Grotesque", sans-serif !important;
    font-weight: 700 !important;
}

/* Forms */
input[type=text], input[type=email], input[type=tel], input[type=url],
input[type=number], input[type=password], input[type=search],
textarea, select,
.woocommerce form .form-row input.input-text,
.woocommerce form .form-row textarea {
    border: 1.5px solid var(--line) !important;
    border-radius: 8px !important;
    padding: 12px 14px !important;
    font: inherit !important;
    background: #ffffff !important;
    transition: border-color .15s, box-shadow .15s !important;
}
input:focus, textarea:focus, select:focus {
    outline: 0 !important;
    border-color: var(--coral) !important;
    box-shadow: 0 0 0 4px var(--coral-soft) !important;
}

/* Quote button override (from luvron-quote.php) */
.luvron-quote-btn {
    background: var(--coral) !important;
    color: #ffffff !important;
    border-radius: 999px !important;
    padding: 12px 24px !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    box-shadow: 0 4px 16px rgba(232,74,63,.25) !important;
}
.luvron-quote-btn:hover {
    background: var(--coral-deep) !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(232,74,63,.32) !important;
    color: #ffffff !important;
}

/* Footer */
.site-footer,
.ast-footer-overlay,
.footer-adv,
[data-section="section-footer-builder"] {
    background: var(--ink) !important;
    color: rgba(255,255,255,0.7) !important;
}
.site-footer a, .ast-footer-overlay a, .footer-adv a {
    color: rgba(255,255,255,0.85) !important;
}
.site-footer a:hover { color: var(--coral) !important; }

/* Bulk order grid (from luvron-bulk-order.php) */
.luvron-bulk-table {
    border-radius: var(--radius-lg) !important;
    overflow: hidden !important;
    border: 1px solid var(--line) !important;
}
.luvron-bulk-table thead {
    background: var(--ink) !important;
}
.luvron-bulk-table thead th {
    background: var(--ink) !important;
    color: #ffffff !important;
    font-family: "Plus Jakarta Sans", sans-serif !important;
}

/* Make the page area max width consistent */
.ast-container,
.site-content > .ast-container,
.entry-content > .alignwide {
    max-width: 1280px !important;
}
.entry-content > .alignfull {
    max-width: none !important;
}

/* Hide Astra page title bar on Home */
body.home .ast-archive-title,
body.home .ast-single-post-banner,
body.page-id-130 .entry-header,
body.page-id-130 .ast-single-post-banner {
    display: none !important;
}

/* Section-style block patterns get full breathing room */
.entry-content {
    margin-top: 0 !important;
}
body.home .entry-content > * {
    margin-top: 0 !important;
    margin-bottom: 0 !important;
}
CSS;
}

// Set a primary menu with Luvron pages if no primary menu exists
add_action('init', function () {
    $locations = get_theme_mod('nav_menu_locations', []);
    if (!empty($locations['primary'])) return;

    $menu_id = wp_create_nav_menu('Luvron Primary');
    if (is_wp_error($menu_id)) return;

    $items = [
        ['Home',         home_url('/')],
        ['Catalogue',    home_url('/catalogue/')],
        ['Bulk Order',   home_url('/bulk-order/')],
        ['Become a Dealer', home_url('/become-a-dealer/')],
        ['About',        home_url('/about-luvron/')],
        ['Contact',      home_url('/contact/')],
    ];
    foreach ($items as $i => $row) {
        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title'    => $row[0],
            'menu-item-url'      => $row[1],
            'menu-item-status'   => 'publish',
            'menu-item-position' => $i + 1,
        ]);
    }
    $locations['primary'] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
}, 30);

// Add subtle body class for our custom styling targets
add_filter('body_class', function ($classes) {
    $classes[] = 'luvron-styled';
    return $classes;
});
