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

    // Hide site title text — only show logo
    update_option('blogname', 'Luvron');           // shorter title where it appears
    set_theme_mod('display-site-title',     false);
    set_theme_mod('display-site-tagline',   false);
    set_theme_mod('site-title-color',       '#0f172a');

    // Hide Astra page title bar on all pages (we use block patterns)
    set_theme_mod('ast-banner-title-area',  ['default' => 'disable']);
    set_theme_mod('ast-dynamic-single-page-title-layout', 'layout-1');
    set_theme_mod('ast-dynamic-single-page-title', false);
    set_theme_mod('ast-single-post-title',          ['default' => 0]);
    set_theme_mod('ast-dynamic-single-page-title-display', false);
    set_theme_mod('breadcrumb-position',     'none');

    // Container = full width so block patterns can reach edge-to-edge
    set_theme_mod('site-content-layout',    'plain-container');
    set_theme_mod('single-page-content-layout', 'plain-container');
    set_theme_mod('site-sidebar-layout',    'no-sidebar');
    set_theme_mod('single-page-sidebar-layout', 'no-sidebar');
    set_theme_mod('site-content-width',     1280);
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
    wp_add_inline_style('luvron-brand', luvron_brand_css() . luvron_blocks_css());
}, 100);

function luvron_blocks_css() {
    return <<<CSS

/* ============================================================
   LUVRON NATIVE BLOCK PATTERN STYLES
   Hooks into native Gutenberg blocks via .lv-* className attrs.
   The home page uses wp:group / wp:cover / wp:columns / wp:image
   / wp:heading / wp:paragraph / wp:buttons — all editable in admin.
   ============================================================ */

/* HERO */
.lv-hero {
    padding: 100px 0 120px !important;
    position: relative;
    overflow: hidden;
    background: #ffffff !important;
}
.lv-hero::before {
    content: ""; position: absolute; top: -150px; left: -100px;
    width: 600px; height: 600px;
    background: radial-gradient(circle, #ffd9d3, transparent 70%);
    filter: blur(80px); opacity: .55; pointer-events: none;
}
.lv-hero::after {
    content: ""; position: absolute; bottom: -150px; right: -100px;
    width: 600px; height: 600px;
    background: radial-gradient(circle, #c7e2fb, transparent 70%);
    filter: blur(80px); opacity: .45; pointer-events: none;
}
.lv-hero .lv-hero-columns { position: relative; z-index: 1; gap: 60px; }
.lv-hero .lv-eyebrow {
    display: inline-block;
    padding: 8px 16px;
    background: #ffffff;
    border: 1px solid #ffd9d3;
    color: #c93a30;
    border-radius: 999px;
    font-size: 12.5px !important;
    font-weight: 600 !important;
    letter-spacing: 0.02em;
    margin-bottom: 24px !important;
    box-shadow: 0 4px 12px rgba(232,74,63,.08);
    text-transform: none !important;
    line-height: 1 !important;
}
.lv-hero h1.lv-h1 {
    font-family: "Bricolage Grotesque", sans-serif !important;
    font-size: clamp(40px, 6.5vw, 76px) !important;
    font-weight: 700 !important;
    line-height: 1.04 !important;
    letter-spacing: -0.025em !important;
    margin: 0 0 24px !important;
    color: #0f172a !important;
}
.lv-hero h1.lv-h1 em {
    color: #ff6b5b;
    font-style: normal;
    font-family: "Caveat", cursive;
    font-weight: 700;
    font-size: 0.92em;
    display: inline-block;
    transform: rotate(-2deg);
}
.lv-hero p.lv-lead {
    font-size: 18px !important;
    color: #475569 !important;
    line-height: 1.6 !important;
    max-width: 520px;
    margin: 0 0 32px !important;
}
.lv-hero p.lv-lead strong { color: #0f172a; font-weight: 600; }
.lv-hero .lv-hero-actions { gap: 12px !important; margin-bottom: 32px !important; }
.lv-hero .lv-trust-row {
    color: #64748b !important;
    font-size: 13.5px !important;
    line-height: 1.7 !important;
    font-weight: 500 !important;
    margin: 0 !important;
}
.lv-hero .lv-trust-row strong { color: #2f9e44; font-weight: 800; }
.lv-hero-visual figure.lv-hero-product {
    position: relative;
    aspect-ratio: 1;
    background: linear-gradient(135deg, #fff7e6 0%, #fff0ec 50%, #ffd9d3 100%);
    border-radius: 32px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 30px 80px rgba(15,23,42,.10);
    margin: 0 !important;
    padding: 8%;
}
.lv-hero-visual figure.lv-hero-product img {
    max-width: 80% !important;
    max-height: 80% !important;
    width: auto !important;
    height: auto !important;
    object-fit: contain;
    animation: lv-float 6s ease-in-out infinite;
    filter: drop-shadow(0 25px 35px rgba(15,23,42,.18));
}
@keyframes lv-float {
    0%,100% { transform: translateY(0) rotate(-1deg); }
    50% { transform: translateY(-14px) rotate(1deg); }
}

/* STATS */
.lv-stats {
    padding: 0 !important;
    background: #ffffff !important;
    margin-top: -60px !important;
    position: relative;
    z-index: 3;
}
.lv-stats-grid {
    background: #ffffff;
    border-radius: 24px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 8px 24px rgba(15,23,42,.04);
    margin: 0 !important;
    padding: 0 !important;
    gap: 0 !important;
    overflow: hidden;
}
.lv-stats-grid .lv-stat {
    padding: 36px 24px !important;
    text-align: center;
    border-right: 1px solid #e5e7eb;
    margin: 0 !important;
}
.lv-stats-grid .lv-stat:last-child { border-right: 0; }
.lv-stat-num {
    font-family: "Bricolage Grotesque", sans-serif !important;
    font-size: 48px !important;
    line-height: 1 !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    margin: 0 0 8px !important;
    letter-spacing: -0.02em !important;
}
.lv-stat-label {
    font-size: 11.5px !important;
    color: #64748b !important;
    text-transform: uppercase !important;
    letter-spacing: 0.08em;
    font-weight: 600 !important;
    margin: 0 !important;
}

/* SECTION HEAD */
.lv-section {
    padding: 96px 0 !important;
    background: #ffffff;
}
.lv-bg-cream { background: #fff7e6 !important; }
.lv-section .lv-eyebrow-section {
    color: #ff6b5b !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    letter-spacing: 0.08em;
    text-transform: uppercase !important;
    margin: 0 0 14px !important;
}
.lv-section .lv-section-h2 {
    font-family: "Bricolage Grotesque", sans-serif !important;
    font-size: clamp(32px, 4.5vw, 52px) !important;
    font-weight: 700 !important;
    line-height: 1.05 !important;
    letter-spacing: -0.025em !important;
    margin: 0 auto 16px !important;
    max-width: 720px;
    color: #0f172a !important;
}
.lv-section .lv-section-blurb {
    font-size: 17px !important;
    color: #475569 !important;
    line-height: 1.55 !important;
    margin: 0 auto 56px !important;
    max-width: 720px;
}

/* PRODUCT GRID (Best Sellers) */
.lv-prod-grid {
    margin: 0 0 24px !important;
    gap: 24px !important;
}
.lv-prod-grid .lv-prod {
    background: #ffffff !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 20px !important;
    overflow: hidden !important;
    transition: all .3s cubic-bezier(.23,1,.32,1) !important;
    padding: 0 !important;
    margin: 0 !important;
    flex-basis: auto !important;
}
.lv-prod-grid .lv-prod:hover {
    transform: translateY(-8px);
    border-color: #ffd9d3 !important;
    box-shadow: 0 24px 60px rgba(15,23,42,.10);
}
.lv-prod figure.lv-prod-img {
    aspect-ratio: 1;
    margin: 0 !important;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    overflow: hidden;
    position: relative;
}
.lv-prod figure.lv-prod-img img {
    max-width: 80% !important;
    max-height: 80% !important;
    width: auto !important;
    height: auto !important;
    object-fit: contain;
    transition: transform .4s cubic-bezier(.23,1,.32,1);
    filter: drop-shadow(0 14px 24px rgba(15,23,42,.12));
}
.lv-prod:hover figure.lv-prod-img img { transform: scale(1.08) rotate(-3deg); }
.lv-prod figure.lv-prod-img.lv-prod-musical { background: linear-gradient(135deg, #fff7e6, #fff0ec); }
.lv-prod figure.lv-prod-img.lv-prod-normal { background: linear-gradient(135deg, #ecfdf5, #d4f1d8); }
.lv-prod figure.lv-prod-img.lv-prod-musical::before {
    content: "♪ MUSICAL";
    position: absolute;
    top: 14px; left: 14px;
    background: rgba(180,83,9,.95);
    color: #fff;
    padding: 5px 11px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.04em;
    z-index: 2;
}
.lv-prod figure.lv-prod-img.lv-prod-normal::before {
    content: "⚙ NORMAL";
    position: absolute;
    top: 14px; left: 14px;
    background: rgba(47,158,68,.95);
    color: #fff;
    padding: 5px 11px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.04em;
    z-index: 2;
}
.lv-prod p.lv-prod-series {
    font-size: 11px !important;
    font-weight: 700 !important;
    letter-spacing: 0.08em;
    color: #ff6b5b !important;
    text-transform: uppercase !important;
    margin: 18px 22px 4px !important;
}
.lv-prod h3.lv-prod-name {
    font-family: "Bricolage Grotesque", sans-serif !important;
    font-size: 18px !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    line-height: 1.2 !important;
    letter-spacing: -0.01em !important;
    margin: 0 22px 10px !important;
}
.lv-prod p.lv-prod-meta {
    font-size: 13px !important;
    color: #64748b !important;
    margin: 0 22px 22px !important;
    padding-top: 14px !important;
    border-top: 1px solid #f1f5f9;
}
.lv-prod p.lv-prod-meta a {
    color: #ff6b5b !important;
    font-weight: 600 !important;
    text-decoration: none !important;
}

/* CATEGORY GRID */
.lv-cat-grid {
    margin: 0 0 20px !important;
    gap: 20px !important;
}
.lv-cat-grid .lv-cat {
    border-radius: 20px !important;
    overflow: hidden !important;
    padding: 28px !important;
    margin: 0 !important;
    text-align: center;
    transition: all .3s cubic-bezier(.23,1,.32,1) !important;
    aspect-ratio: 1;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
    box-shadow: 0 4px 16px rgba(15,23,42,.04);
    flex-basis: auto !important;
}
.lv-cat-grid .lv-cat:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 50px rgba(15,23,42,.12);
}
.lv-cat-grid .lv-cat figure.wp-block-image {
    flex: 1;
    margin: 0 0 16px !important;
    display: flex !important;
    align-items: center;
    justify-content: center;
    padding: 8px;
}
.lv-cat-grid .lv-cat figure.wp-block-image img {
    max-width: 100% !important;
    max-height: 100% !important;
    width: auto !important;
    height: auto !important;
    object-fit: contain;
    transition: transform .4s cubic-bezier(.23,1,.32,1);
    filter: drop-shadow(0 12px 22px rgba(15,23,42,.15));
}
.lv-cat-grid .lv-cat:hover figure.wp-block-image img { transform: scale(1.08) rotate(-3deg); }
.lv-cat-grid .lv-cat h3 {
    font-family: "Bricolage Grotesque", sans-serif !important;
    font-size: 22px !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    margin: 0 0 4px !important;
    letter-spacing: -0.01em !important;
}
.lv-cat-grid .lv-cat h3 a { color: inherit !important; text-decoration: none !important; }
.lv-cat-grid .lv-cat p {
    font-size: 12.5px !important;
    color: #475569 !important;
    margin: 0 !important;
    font-weight: 500 !important;
}
.lv-cat-coral { background: linear-gradient(135deg, #fff0ec 0%, #ffd9d3 100%); }
.lv-cat-sky { background: linear-gradient(135deg, #eef5ff 0%, #d8e8fa 100%); }
.lv-cat-mint { background: linear-gradient(135deg, #ecfdf5 0%, #d4f1d8 100%); }
.lv-cat-cream { background: linear-gradient(135deg, #fff7e6 0%, #ffe9a8 100%); }
.lv-cat-lavender { background: linear-gradient(135deg, #f3eeff 0%, #e7dffa 100%); }

/* WHY LUVRON FEATURES */
.lv-feat-grid {
    margin: 0 0 24px !important;
    gap: 24px !important;
}
.lv-feat-grid .lv-feat {
    background: #ffffff !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 20px !important;
    padding: 36px 32px !important;
    margin: 0 !important;
    transition: all .3s cubic-bezier(.23,1,.32,1) !important;
    flex-basis: auto !important;
    position: relative;
}
.lv-feat-grid .lv-feat::before {
    content: "";
    width: 56px; height: 56px;
    border-radius: 14px;
    display: block;
    margin-bottom: 22px;
    background-color: #fff0ec;
    background-position: center;
    background-repeat: no-repeat;
    background-size: 28px 28px;
}
/* Manufacturer Direct — factory icon, coral */
.lv-feat-coral::before {
    background-color: #fff0ec !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23e84a3f' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'/%3E%3Cpath d='M9 22V12h6v10'/%3E%3C/svg%3E");
}
/* 48-Hour Dispatch — truck icon, sky */
.lv-feat-sky::before {
    background-color: #eef5ff !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231c7ed6' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='1' y='3' width='15' height='13' rx='1'/%3E%3Cpolygon points='16 8 20 8 23 11 23 16 16 16 16 8'/%3E%3Ccircle cx='5.5' cy='18.5' r='2.5'/%3E%3Ccircle cx='18.5' cy='18.5' r='2.5'/%3E%3C/svg%3E");
}
/* Carton-Tested — package box icon, mint */
.lv-feat-mint::before {
    background-color: #ecfdf5 !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232f9e44' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z'/%3E%3Cpolyline points='3.27 6.96 12 12.01 20.73 6.96'/%3E%3Cline x1='12' y1='22.08' x2='12' y2='12'/%3E%3C/svg%3E");
}
/* GST-Compliant — document icon, cream */
.lv-feat-cream::before {
    background-color: #fff7e6 !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23b45309' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z'/%3E%3Cpolyline points='14 2 14 8 20 8'/%3E%3Cline x1='16' y1='13' x2='8' y2='13'/%3E%3Cline x1='16' y1='17' x2='8' y2='17'/%3E%3C/svg%3E");
}
/* Territory Protection — shield with check, lavender */
.lv-feat-lavender::before {
    background-color: #f3eeff !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%237048e8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z'/%3E%3Cpolyline points='9 12 11 14 15 10'/%3E%3C/svg%3E");
}
/* Pan-India Sales Network — headset icon, coral */
.lv-feat-support::before {
    background-color: #fff0ec !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23e84a3f' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 18v-6a9 9 0 0 1 18 0v6'/%3E%3Cpath d='M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z'/%3E%3C/svg%3E");
}

/* CATALOGUE DOWNLOAD SECTION */
.lv-bg-canvas { background: #fdfaf6 !important; }
.lv-catalog .lv-catalog-cols { gap: 56px !important; align-items: center; }
.lv-catalog .lv-catalog-info { padding: 0 !important; }
.lv-catalog .lv-catalog-info p { font-size: 17px !important; color: #475569 !important; line-height: 1.6 !important; margin: 0 0 28px !important; max-width: 480px; }
.lv-catalog .lv-catalog-info .lv-eyebrow-section { margin: 0 0 14px !important; text-align: left !important; }
.lv-catalog .lv-catalog-info h2.lv-section-h2 { text-align: left !important; margin: 0 0 16px !important; max-width: none !important; }
.lv-catalog-actions { gap: 12px !important; margin: 0 !important; justify-content: flex-start !important; }
.lv-catalog-card .lv-catalog-preview {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 24px;
    padding: 36px 36px 32px !important;
    box-shadow: 0 14px 40px rgba(15,23,42,.08);
    position: relative;
    overflow: hidden;
}
.lv-catalog-card .lv-catalog-preview::before {
    content: "PDF";
    position: absolute;
    top: 24px; right: 24px;
    background: #ff6b5b;
    color: #ffffff;
    padding: 5px 11px;
    border-radius: 6px;
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    box-shadow: 0 6px 16px rgba(255,107,91,.30);
}
.lv-catalog-card .lv-catalog-tag {
    font-size: 11.5px !important;
    font-weight: 700 !important;
    letter-spacing: 0.10em;
    color: #94a3b8 !important;
    text-transform: uppercase !important;
    margin: 0 0 14px !important;
}
.lv-catalog-card .lv-catalog-num {
    font-family: "Bricolage Grotesque", sans-serif !important;
    font-size: 56px !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    line-height: 1 !important;
    letter-spacing: -0.025em !important;
    margin: 0 0 14px !important;
}
.lv-catalog-card .lv-catalog-meta {
    font-size: 14px !important;
    color: #475569 !important;
    margin: 0 0 24px !important;
    line-height: 1.6 !important;
}
.lv-catalog-card .lv-catalog-meta strong { color: #0f172a; font-weight: 600; }
.lv-catalog-thumbs { gap: 10px !important; margin: 0 !important; }
.lv-catalog-thumbs .wp-block-column { padding: 0 !important; }
.lv-catalog-thumbs figure.wp-block-image {
    margin: 0 !important;
    aspect-ratio: 1;
    background: linear-gradient(135deg, #fff7e6, #fff0ec);
    border-radius: 12px;
    overflow: hidden;
    display: flex !important;
    align-items: center;
    justify-content: center;
    padding: 8px;
}
.lv-catalog-thumbs figure.wp-block-image img {
    max-width: 80% !important;
    max-height: 80% !important;
    width: auto !important;
    height: auto !important;
    object-fit: contain;
    filter: drop-shadow(0 6px 10px rgba(15,23,42,.10));
}
.lv-catalog-thumbs .wp-block-column:nth-child(2) figure { background: linear-gradient(135deg, #eef5ff, #d8e8fa); }
.lv-catalog-thumbs .wp-block-column:nth-child(3) figure { background: linear-gradient(135deg, #ecfdf5, #d4f1d8); }
.lv-catalog-thumbs .wp-block-column:nth-child(4) figure { background: linear-gradient(135deg, #f3eeff, #e7dffa); }
@media (max-width: 980px) {
    .lv-catalog .lv-catalog-info, .lv-catalog .lv-catalog-card { flex-basis: 100% !important; }
    .lv-catalog .lv-catalog-info h2.lv-section-h2 { text-align: center !important; }
    .lv-catalog .lv-catalog-info .lv-eyebrow-section { text-align: center !important; }
    .lv-catalog-actions { justify-content: center !important; }
}
.lv-feat-grid .lv-feat:hover {
    transform: translateY(-4px);
    border-color: #ffd9d3 !important;
    box-shadow: 0 18px 40px rgba(15,23,42,.06);
}
.lv-feat-grid .lv-feat h3 {
    font-family: "Bricolage Grotesque", sans-serif !important;
    font-size: 21px !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    margin: 0 0 10px !important;
    letter-spacing: -0.01em !important;
}
.lv-feat-grid .lv-feat p {
    font-size: 14.5px !important;
    color: #475569 !important;
    line-height: 1.6 !important;
    margin: 0 !important;
}

/* TIERS — redesigned with featured product image instead of empty decorative circle */
.lv-tier-grid {
    margin: 32px 0 0 !important;
    gap: 28px !important;
}
.lv-tier-grid .lv-tier {
    border-radius: 24px !important;
    padding: 36px 36px 28px !important;
    margin: 0 !important;
    position: relative;
    overflow: hidden;
    flex-basis: auto !important;
    display: flex !important;
    flex-direction: column !important;
}
.lv-tier-musical {
    background:
        radial-gradient(circle at 100% 0%, rgba(212,168,71,0.18), transparent 50%),
        linear-gradient(135deg, #fff7e6, #fff5d3) !important;
}
.lv-tier-normal {
    background:
        radial-gradient(circle at 100% 0%, rgba(122,174,142,0.20), transparent 50%),
        linear-gradient(135deg, #ecfdf5, #d4f4d8) !important;
}
/* Large translucent symbol decoration in top-right (CSS-only, no images, no broken renders) */
.lv-tier::after {
    content: "";
    position: absolute;
    top: -50px; right: -50px;
    width: 280px; height: 280px;
    border-radius: 50%;
    pointer-events: none;
    z-index: 0;
    background-position: center;
    background-repeat: no-repeat;
    background-size: 140px 140px;
    opacity: 0.85;
    transition: transform .4s cubic-bezier(.23,1,.32,1);
}
.lv-tier:hover::after {
    transform: scale(1.05) rotate(-5deg);
}
.lv-tier-musical::after {
    background-color: rgba(212, 168, 71, 0.16);
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23b45309' fill-opacity='0.55'%3E%3Cpath d='M9 17a3 3 0 1 1 0 6 3 3 0 0 1 0-6zm12-2a3 3 0 1 1 0 6 3 3 0 0 1 0-6zM12 2v15a3 3 0 1 1-2-2.83V5h13v10a3 3 0 1 1-2-2.83V4H12V2z'/%3E%3C/svg%3E");
}
.lv-tier-normal::after {
    background-color: rgba(122, 174, 142, 0.18);
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232f9e44' fill-opacity='0.55'%3E%3Cpath d='M19.43 12.98c.04-.32.07-.64.07-.98 0-.34-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98 0 .33.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z'/%3E%3C/svg%3E");
}

/* Marker pill — wrap span trick: <p> is block (doesn't constrain width)
   but the inner <span> with display:inline-flex IS the actual pill */
.lv-tier .lv-tier-marker {
    display: block !important;
    width: 100% !important;
    margin: 0 0 22px !important;
    position: relative;
    z-index: 2;
    line-height: 1 !important;
    background: transparent !important;
    padding: 0 !important;
    box-shadow: none !important;
}
.lv-tier .lv-tier-marker > span {
    display: inline-flex !important;
    align-items: center;
    padding: 7px 14px !important;
    border-radius: 999px;
    font-size: 12px !important;
    font-weight: 700 !important;
    background: #ffffff !important;
    line-height: 1.2 !important;
    box-shadow: 0 4px 12px rgba(15,23,42,.06);
    letter-spacing: 0.02em;
    width: auto !important;
}
.lv-tier-musical .lv-tier-marker > span { color: #b45309 !important; }
.lv-tier-normal .lv-tier-marker > span { color: #2f9e44 !important; }
.lv-tier h3 {
    font-family: "Bricolage Grotesque", sans-serif !important;
    font-size: 38px !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    line-height: 1.05 !important;
    letter-spacing: -0.025em !important;
    margin: 0 0 6px !important;
    position: relative;
    z-index: 2;
    max-width: 100%;
}
.lv-tier .lv-tier-price {
    font-family: "Plus Jakarta Sans", sans-serif !important;
    font-size: 15px !important;
    color: #475569 !important;
    margin: 0 0 22px !important;
    position: relative;
    z-index: 2;
    line-height: 1.3 !important;
    max-width: 100%;
}
.lv-tier .lv-tier-price strong {
    font-family: "Bricolage Grotesque", sans-serif !important;
    font-size: 28px !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    letter-spacing: -0.02em;
    display: block;
    margin-bottom: 2px;
}
.lv-tier .lv-tier-price span {
    font-size: 12.5px !important;
    color: #64748b !important;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 600;
}
.lv-tier ul {
    margin: 0 !important;
    padding: 0 !important;
    list-style: none !important;
    position: relative;
    z-index: 2;
}
.lv-tier ul li {
    padding: 12px 0 12px 30px !important;
    font-size: 14px !important;
    color: #475569 !important;
    border-top: 1px solid rgba(15,23,42,.08);
    position: relative;
    line-height: 1.55;
}
.lv-tier ul li:first-child { border-top: 0; padding-top: 4px !important; }
.lv-tier ul li::before {
    content: "✓";
    position: absolute;
    left: 0; top: 12px;
    width: 22px; height: 22px;
    border-radius: 50%;
    background: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 11px;
    box-shadow: 0 2px 6px rgba(15,23,42,.06);
}
.lv-tier-musical ul li::before { color: #b45309; }
.lv-tier-normal ul li::before { color: #2f9e44; }
.lv-tier-musical ul li:first-child::before { top: 4px; }
.lv-tier-normal ul li:first-child::before { top: 4px; }
.lv-tier ul li strong { color: #0f172a !important; font-weight: 600 !important; }

/* Footer row: HSN tag pill (left) + CTA arrow (right) */
.lv-tier .lv-tier-foot {
    margin-top: 24px !important;
    padding-top: 18px !important;
    border-top: 1px solid rgba(15,23,42,.10);
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    gap: 16px;
    flex-wrap: wrap;
    position: relative;
    z-index: 2;
}
.lv-tier .lv-tier-hsn {
    margin: 0 !important;
    font-size: 12px !important;
    color: #475569 !important;
    line-height: 1.4 !important;
}
.lv-tier .lv-tier-hsn code {
    font-family: "SFMono-Regular", monospace !important;
    background: #ffffff;
    padding: 3px 9px;
    border-radius: 5px;
    font-size: 11px !important;
    font-weight: 700;
    color: #0f172a;
    margin-right: 4px;
    box-shadow: 0 1px 3px rgba(15,23,42,.06);
}
.lv-tier .lv-tier-cta {
    margin: 0 !important;
    font-size: 13.5px !important;
    font-weight: 700 !important;
}
.lv-tier .lv-tier-cta a {
    text-decoration: none !important;
    transition: gap .2s, color .2s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.lv-tier-musical .lv-tier-cta a {
    color: #b45309 !important;
}
.lv-tier-musical .lv-tier-cta a:hover {
    color: #92400e !important;
    gap: 8px;
}
.lv-tier-normal .lv-tier-cta a {
    color: #2f9e44 !important;
}
.lv-tier-normal .lv-tier-cta a:hover {
    color: #166534 !important;
    gap: 8px;
}
@media (max-width: 720px) {
    .lv-tier figure.lv-tier-image {
        width: 130px !important;
        height: 130px !important;
        top: -5px !important;
        right: -10px !important;
    }
    .lv-tier h3 { font-size: 32px !important; max-width: 65% !important; }
    .lv-tier .lv-tier-price { max-width: 65% !important; }
    .lv-tier .lv-tier-price strong { font-size: 24px !important; }
}

/* TESTIMONIALS */
.lv-testi-grid {
    margin: 0 0 24px !important;
    gap: 24px !important;
}
.lv-testi-grid .lv-testi {
    background: #ffffff !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 20px !important;
    padding: 36px 32px !important;
    margin: 0 !important;
    flex-basis: auto !important;
    transition: transform .25s cubic-bezier(.23,1,.32,1) !important;
    position: relative;
}
.lv-testi-grid .lv-testi:hover {
    transform: translateY(-3px);
    box-shadow: 0 18px 40px rgba(15,23,42,.06);
}
.lv-testi-grid .lv-testi::before {
    content: "\"";
    position: absolute;
    top: -14px; left: 24px;
    background: #ff6b5b;
    color: #fff;
    width: 48px; height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: "Bricolage Grotesque", serif;
    font-size: 36px;
    line-height: 0.9;
    box-shadow: 0 8px 20px rgba(255,107,91,.30);
}
.lv-testi p.lv-stars {
    color: #f59e0b !important;
    font-size: 17px !important;
    margin: 8px 0 16px !important;
    letter-spacing: 2px;
}
.lv-testi p.lv-quote {
    font-size: 16px !important;
    line-height: 1.6 !important;
    color: #0f172a !important;
    margin: 0 0 26px !important;
    font-weight: 500;
}
.lv-testi figure.lv-who-img {
    margin: 22px 0 12px !important;
    padding-top: 22px;
    border-top: 1px solid #f1f5f9;
}
.lv-testi figure.lv-who-img img {
    width: 52px !important;
    height: 52px !important;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #ffd9d3;
}
.lv-testi p.lv-who-name {
    font-size: 13px !important;
    color: #64748b !important;
    margin: 0 !important;
    line-height: 1.4 !important;
}
.lv-testi p.lv-who-name strong {
    display: block;
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 2px;
}

/* CTA COVER */
.lv-cta-cover {
    border-radius: 0 !important;
    padding: 80px 32px !important;
}
.lv-cta-cover .lv-cta-title {
    font-family: "Bricolage Grotesque", sans-serif !important;
    font-size: clamp(32px, 4.5vw, 52px) !important;
    font-weight: 700 !important;
    line-height: 1.05 !important;
    letter-spacing: -0.025em !important;
    margin: 0 auto 18px !important;
    max-width: 760px;
}
.lv-cta-cover .lv-cta-blurb {
    font-size: 17px !important;
    line-height: 1.6 !important;
    margin: 0 auto 32px !important;
    max-width: 580px;
    opacity: 0.92;
}
.lv-cta-cover .lv-cta-buttons { gap: 14px !important; }
.lv-cta-cover .lv-btn-light .wp-block-button__link {
    background: #ffffff !important;
    color: #0f172a !important;
}
.lv-cta-cover .lv-btn-yellow .wp-block-button__link {
    background: #ffc93c !important;
    color: #0f172a !important;
}

/* HERO SLIM (subpages) */
.lv-hero-slim { padding: 80px 0 !important; }
.lv-hero-slim .lv-breadcrumb {
    font-size: 13px !important;
    color: #475569 !important;
    margin: 0 0 18px !important;
}
.lv-hero-slim .lv-breadcrumb a { color: inherit; }
.lv-hero-slim h1.lv-hero-slim-h1 {
    font-family: "Bricolage Grotesque", sans-serif !important;
    font-size: clamp(36px, 5vw, 56px) !important;
    font-weight: 700 !important;
    line-height: 1.05 !important;
    letter-spacing: -0.025em !important;
    margin: 0 0 16px !important;
    color: #0f172a !important;
}
.lv-hero-slim p.lv-hero-slim-p {
    font-size: 18px !important;
    color: #475569 !important;
    line-height: 1.6 !important;
    margin: 0 !important;
}

/* MOBILE */
@media (max-width: 980px) {
    .lv-hero { padding: 60px 0 80px !important; }
    .lv-stats { margin-top: 0 !important; }
    .lv-stats-grid { flex-wrap: wrap !important; }
    .lv-stats-grid .lv-stat { flex-basis: 50% !important; border-right: 1px solid #e5e7eb !important; border-bottom: 1px solid #e5e7eb !important; }
    .lv-stats-grid .lv-stat:nth-child(2n) { border-right: 0 !important; }
    .lv-stats-grid .lv-stat:last-child { flex-basis: 100% !important; border-bottom: 0 !important; }
    .lv-section { padding: 64px 0 !important; }
}
@media (max-width: 720px) {
    .lv-cat-grid, .lv-prod-grid, .lv-feat-grid, .lv-tier-grid, .lv-testi-grid {
        flex-direction: column !important;
    }
    .lv-cat-grid > *, .lv-prod-grid > *, .lv-feat-grid > *, .lv-tier-grid > *, .lv-testi-grid > * {
        flex-basis: 100% !important;
    }
}
CSS;
}

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

/* Hide Astra default page title bar / page title text on ALL pages
   (we use block patterns for headers; redundant page titles look broken) */
.ast-single-post-banner,
.ast-archive-title,
.ast-single-banner-area,
.ast-banner-image-bg-disable,
body.page .entry-header,
body.page article .entry-title,
body.home .entry-header,
body.home article .entry-title,
.ast-page-builder-template .entry-header,
header.entry-header,
.ast-no-thumbnail .entry-header {
    display: none !important;
}

/* Hide site-title text — only the logo image should appear */
.ast-site-identity .ast-site-title-wrap,
.ast-site-title-wrap,
.site-title,
.site-description,
.site-header .site-title,
.site-header .site-description {
    display: none !important;
}

/* Constrain the logo strictly */
.custom-logo,
.custom-logo-link img,
.site-logo-img img {
    max-height: 56px !important;
    height: auto !important;
    width: auto !important;
}
@media (max-width: 720px) {
    .custom-logo,
    .custom-logo-link img,
    .site-logo-img img { max-height: 44px !important; }
}

/* Astra page wrapper should not add inner padding when Home page is full-width */
body.home #primary,
body.home #content,
body.home .site-content > .ast-container {
    padding: 0 !important;
    max-width: none !important;
    margin: 0 !important;
}
body.home article.page {
    padding: 0 !important;
    margin: 0 !important;
    border: 0 !important;
}
body.home .entry-content { margin: 0 !important; padding: 0 !important; }
body.home .entry-content > * { margin-top: 0 !important; margin-bottom: 0 !important; }

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

// Set up primary menu with curated Luvron items. Recreate cleanly on each load
// so we own this menu (vs Astra's auto-pages fallback that lists every page alphabetically).
add_action('after_switch_theme', 'luvron_create_primary_menu');
add_action('init', function () {
    if (get_option('luvron_menu_v2_done')) return;
    luvron_create_primary_menu();
    update_option('luvron_menu_v2_done', current_time('mysql'));
}, 30);

function luvron_create_primary_menu() {
    // Delete any existing menu to start clean
    $existing = wp_get_nav_menu_object('Luvron Primary');
    if ($existing) wp_delete_nav_menu($existing->term_id);

    $menu_id = wp_create_nav_menu('Luvron Primary');
    if (is_wp_error($menu_id)) return;

    $items = [
        ['Home',            home_url('/')],
        ['Catalogue',       home_url('/shop/')],
        ['Bulk Order',      home_url('/bulk-order/')],
        ['Become a Dealer', home_url('/become-a-dealer/')],
        ['About',           home_url('/about-luvron/')],
        ['Contact',         home_url('/contact/')],
    ];
    foreach ($items as $i => $row) {
        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title'    => $row[0],
            'menu-item-url'      => $row[1],
            'menu-item-status'   => 'publish',
            'menu-item-type'     => 'custom',
            'menu-item-position' => $i + 1,
        ]);
    }

    // Assign to primary location AND all other Astra menu locations
    $locations = get_theme_mod('nav_menu_locations', []);
    foreach (['primary', 'main', 'top', 'header', 'mobile_menu', 'menu-1'] as $loc) {
        $locations[$loc] = $menu_id;
    }
    set_theme_mod('nav_menu_locations', $locations);
}

// Filter wp_nav_menu fallback to never show "all pages alphabetically"
add_filter('wp_nav_menu_args', function ($args) {
    // If theme falls back to wp_page_menu, force-hide the wrong pages
    if (empty($args['theme_location']) || empty(get_theme_mod('nav_menu_locations')[$args['theme_location']] ?? null)) {
        $exclude_slugs = ['cart', 'checkout', 'my-account', 'sample-page', 'privacy-policy',
                          'terms-of-service', 'refund-policy', 'shipping-policy', 'dealer-dashboard',
                          'request-quote', 'catalogue'];
        $exclude_ids = [];
        foreach ($exclude_slugs as $slug) {
            $p = get_page_by_path($slug);
            if ($p) $exclude_ids[] = $p->ID;
        }
        $args['exclude'] = implode(',', $exclude_ids);
    }
    return $args;
});

// Add subtle body class for our custom styling targets
add_filter('body_class', function ($classes) {
    $classes[] = 'luvron-styled';
    return $classes;
});
