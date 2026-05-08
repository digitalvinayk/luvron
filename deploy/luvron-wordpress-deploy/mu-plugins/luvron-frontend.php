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
    wp_add_inline_style('luvron-brand', luvron_brand_css());
}, 100);

function luvron_brand_css() {
    return luvron_pattern_css() . luvron_chrome_css();
}

function luvron_pattern_css() {
    return <<<CSS
/* ============================================================
   LUVRON BLOCK PATTERN STYLES — modern enterprise design system
   Used by hero, categories, tiers, why-luvron, testimonials, CTA.
   ============================================================ */

.luvron-pattern { font-family: "Plus Jakarta Sans", sans-serif; color: #0f172a; line-height: 1.55; }
.luvron-pattern *, .luvron-pattern *::before, .luvron-pattern *::after { box-sizing: border-box; }
.luvron-pattern .lv-wrap { max-width: 1280px; margin: 0 auto; padding: 0 32px; }
@media (max-width: 720px) { .luvron-pattern .lv-wrap { padding: 0 20px; } }

.luvron-pattern .lv-display {
    font-family: "Bricolage Grotesque", sans-serif;
    font-weight: 700; line-height: 1.05;
    letter-spacing: -0.025em;
    margin: 0;
}
.luvron-pattern .lv-accent { color: #ff6b5b; position: relative; display: inline-block; }
.luvron-pattern .lv-accent::after {
    content: ""; position: absolute; left: 0; right: 0; bottom: 4px;
    height: 12px; background: #ffc93c; z-index: -1;
    border-radius: 2px; opacity: 0.55;
}
.luvron-pattern .lv-accent-yellow { color: #ffc93c; }
.luvron-pattern .lv-hand {
    font-family: "Caveat", cursive;
    color: #ff6b5b; font-weight: 700;
    font-size: 0.85em; display: inline-block;
    transform: rotate(-2deg);
    font-style: normal;
}

/* HERO */
.luvron-pattern .lv-hero {
    padding: 80px 0 100px; position: relative; overflow: hidden;
    background: #ffffff;
}
.luvron-pattern .lv-hero::before, .luvron-pattern .lv-hero::after {
    content: ""; position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.5;
    pointer-events: none;
}
.luvron-pattern .lv-hero::before {
    top: -100px; left: -50px; width: 400px; height: 400px;
    background: #ffd9d3;
}
.luvron-pattern .lv-hero::after {
    bottom: -100px; right: -50px; width: 500px; height: 500px;
    background: #c7e2fb;
}
.luvron-pattern .lv-hero-grid {
    display: grid; grid-template-columns: 1.1fr 0.9fr;
    gap: 64px; align-items: center;
    position: relative; z-index: 1;
}
@media (max-width: 980px) {
    .luvron-pattern .lv-hero-grid { grid-template-columns: 1fr; gap: 40px; }
    .luvron-pattern .lv-hero { padding: 56px 0 72px; }
}
.luvron-pattern .lv-eyebrow {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 8px 16px; background: #ffd9d3; color: #c93a30;
    border-radius: 999px; font-size: 12.5px; font-weight: 600;
    letter-spacing: 0.02em; margin-bottom: 24px;
}
.luvron-pattern .lv-pulse {
    width: 8px; height: 8px; border-radius: 50%;
    background: #ff6b5b;
    box-shadow: 0 0 0 0 rgba(255,107,91,0.5);
    animation: lv-pulse 2s infinite;
}
@keyframes lv-pulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(255,107,91,0.5); }
    70% { box-shadow: 0 0 0 8px rgba(255,107,91,0); }
}
.luvron-pattern .lv-hero h1.lv-display {
    font-size: clamp(40px, 6.5vw, 78px);
    margin-bottom: 20px;
    color: #0f172a !important;
}
.luvron-pattern .lv-hero h1.lv-display em {
    color: #ff6b5b; font-weight: 700; font-style: normal;
    font-size: 0.92em; display: inline-block;
    transform: rotate(-2deg);
    font-family: "Caveat", cursive;
}
.luvron-pattern .lv-lead {
    font-size: 18px; color: #475569;
    margin-bottom: 32px; max-width: 520px; line-height: 1.6;
}
.luvron-pattern .lv-lead strong { color: #0f172a; font-weight: 600; }
.luvron-pattern .lv-actions { display: flex; gap: 12px; flex-wrap: wrap; }

.luvron-pattern .lv-btn {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 8px; padding: 14px 28px;
    border-radius: 999px; font-weight: 600; font-size: 15px;
    transition: all .2s cubic-bezier(.23,1,.32,1);
    border: 2px solid transparent;
    text-decoration: none;
    cursor: pointer;
    font-family: "Plus Jakarta Sans", sans-serif;
}
.luvron-pattern .lv-btn-primary {
    background: #ff6b5b !important; color: #ffffff !important;
    box-shadow: 0 8px 30px rgba(255,107,91,0.30);
}
.luvron-pattern .lv-btn-primary:hover {
    background: #e84a3f !important; transform: translateY(-2px);
    box-shadow: 0 12px 36px rgba(232,74,63,0.40);
    color: #ffffff !important;
}
.luvron-pattern .lv-btn-ghost {
    background: transparent; color: #0f172a; border-color: #0f172a;
}
.luvron-pattern .lv-btn-ghost:hover { background: #0f172a; color: #ffffff; }
.luvron-pattern .lv-btn-light {
    background: #ffffff !important; color: #0f172a !important;
}
.luvron-pattern .lv-btn-light:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(0,0,0,0.18); }
.luvron-pattern .lv-btn-yellow {
    background: #ffc93c !important; color: #0f172a !important;
}
.luvron-pattern .lv-btn-yellow:hover { background: #e0a91b !important; transform: translateY(-2px); }

.luvron-pattern .lv-hero-visual {
    position: relative; aspect-ratio: 1;
    background: linear-gradient(135deg, #fff0ec 0%, #fff7e6 100%);
    border-radius: 28px; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
}
.luvron-pattern .lv-hero-visual::before {
    content: ""; position: absolute; inset: 0;
    background:
        radial-gradient(circle at 20% 80%, #ffe9a8, transparent 40%),
        radial-gradient(circle at 80% 20%, #ffd9d3, transparent 40%);
    opacity: 0.7;
}
.luvron-pattern .lv-hero-img {
    max-width: 80%; max-height: 80%; object-fit: contain;
    position: relative; z-index: 1;
    filter: drop-shadow(0 30px 40px rgba(15,23,42,0.18));
    animation: lv-float 6s ease-in-out infinite;
}
@keyframes lv-float {
    0%,100% { transform: translateY(0) rotate(-1deg); }
    50% { transform: translateY(-12px) rotate(1deg); }
}
.luvron-pattern .lv-chip {
    position: absolute; background: #ffffff;
    box-shadow: 0 8px 24px rgba(15,23,42,0.10);
    padding: 12px 16px; border-radius: 12px;
    z-index: 2; display: flex; align-items: center; gap: 10px;
    font-size: 13px; font-weight: 600; color: #0f172a;
}
.luvron-pattern .lv-chip-ico {
    width: 36px; height: 36px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 700;
}
.luvron-pattern .lv-chip-tl { top: 20px; left: 20px; transform: rotate(-3deg); }
.luvron-pattern .lv-chip-br { bottom: 20px; right: 20px; transform: rotate(2deg); }

/* STATS */
.luvron-pattern .lv-stats-section { padding: 0 0 80px; background: #ffffff; }
.luvron-pattern .lv-stats {
    display: grid; grid-template-columns: repeat(4, 1fr);
    background: #ffffff; border-radius: 20px;
    border: 1px solid #e5e7eb; overflow: hidden;
}
.luvron-pattern .lv-stat {
    padding: 32px 20px; text-align: center;
    border-right: 1px solid #e5e7eb;
}
.luvron-pattern .lv-stat:last-child { border-right: 0; }
.luvron-pattern .lv-stat-num {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 48px; line-height: 1; font-weight: 700;
    color: #0f172a; margin-bottom: 8px;
    letter-spacing: -0.02em;
}
.luvron-pattern .lv-stat-num em { color: #ff6b5b; font-style: normal; }
.luvron-pattern .lv-stat-label {
    font-size: 12.5px; color: #475569;
    text-transform: uppercase; letter-spacing: 0.06em;
    font-weight: 500;
}
@media (max-width: 720px) {
    .luvron-pattern .lv-stats { grid-template-columns: 1fr 1fr; }
    .luvron-pattern .lv-stat:nth-child(2n) { border-right: 0; }
    .luvron-pattern .lv-stat:nth-child(-n+2) { border-bottom: 1px solid #e5e7eb; }
}

/* SECTION */
.luvron-pattern .lv-section { padding: 96px 0; background: #ffffff; }
.luvron-pattern .lv-bg-canvas { background: #fdfaf6; }
.luvron-pattern .lv-bg-cream { background: #fff7e6; }
.luvron-pattern .lv-head {
    text-align: center; max-width: 720px;
    margin: 0 auto 56px;
}
.luvron-pattern .lv-section-label {
    display: inline-block; color: #ff6b5b;
    font-size: 13px; font-weight: 700;
    letter-spacing: 0.08em; text-transform: uppercase;
    margin-bottom: 12px;
}
.luvron-pattern .lv-head h2.lv-display {
    font-size: clamp(32px, 4.5vw, 48px);
    margin-bottom: 14px;
}
.luvron-pattern .lv-blurb {
    font-size: 17px; color: #475569;
    line-height: 1.55; margin: 0;
}

/* CATEGORY GRID */
.luvron-pattern .lv-cat-grid {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}
.luvron-pattern .lv-cat-card {
    border-radius: 20px; padding: 28px 22px;
    text-align: center; position: relative; overflow: hidden;
    transition: all .25s cubic-bezier(.23,1,.32,1);
    text-decoration: none; color: inherit;
    display: flex; flex-direction: column; aspect-ratio: 1;
    justify-content: space-between;
    cursor: pointer;
}
.luvron-pattern .lv-cat-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 18px 48px rgba(15,23,42,0.10);
}
.luvron-pattern .lv-cat-img {
    flex: 1; display: flex; align-items: center; justify-content: center; padding: 12px;
}
.luvron-pattern .lv-cat-img img {
    max-width: 100%; max-height: 100%; object-fit: contain;
    transition: transform .35s cubic-bezier(.23,1,.32,1);
    filter: drop-shadow(0 8px 16px rgba(15,23,42,0.08));
}
.luvron-pattern .lv-cat-card:hover .lv-cat-img img { transform: scale(1.06) rotate(-2deg); }
.luvron-pattern .lv-cat-card h3 {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 22px; font-weight: 700;
    color: #0f172a !important; margin: 0 0 4px;
    letter-spacing: -0.01em;
}
.luvron-pattern .lv-cat-card p { font-size: 12.5px; color: #475569; margin: 0; font-weight: 500; }
.luvron-pattern .lv-c-coral    { background: #fff0ec; }
.luvron-pattern .lv-c-sky      { background: #eef5ff; }
.luvron-pattern .lv-c-mint     { background: #ecfdf5; }
.luvron-pattern .lv-c-cream    { background: #fff7e6; }
.luvron-pattern .lv-c-lavender { background: #f3eeff; }
@media (max-width: 980px) { .luvron-pattern .lv-cat-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 720px) { .luvron-pattern .lv-cat-grid { grid-template-columns: repeat(2, 1fr); } }

/* TIERS */
.luvron-pattern .lv-tier-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin-top: 32px;
}
.luvron-pattern .lv-tier-card {
    border-radius: 20px; padding: 40px 36px;
    position: relative; overflow: hidden;
}
.luvron-pattern .lv-tier-musical { background: linear-gradient(135deg, #fff7e6, #fff5d3); }
.luvron-pattern .lv-tier-normal  { background: linear-gradient(135deg, #ecfdf5, #d4f4d8); }
.luvron-pattern .lv-tier-card::before {
    content: ""; position: absolute;
    top: -40px; right: -40px; width: 220px; height: 220px;
    border-radius: 50%; opacity: 0.18;
}
.luvron-pattern .lv-tier-musical::before { background: #d4a847; }
.luvron-pattern .lv-tier-normal::before  { background: #7aae8e; }
.luvron-pattern .lv-tier-marker {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 6px 14px; border-radius: 999px;
    font-size: 12.5px; font-weight: 600;
    background: #ffffff; margin-bottom: 18px;
    position: relative; z-index: 1;
}
.luvron-pattern .lv-tier-musical .lv-tier-marker { color: #b45309; }
.luvron-pattern .lv-tier-normal  .lv-tier-marker { color: #2f9e44; }
.luvron-pattern .lv-tier-ico {
    width: 24px; height: 24px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    background: #fff7e6; font-size: 14px;
}
.luvron-pattern .lv-tier-normal .lv-tier-ico { background: #ecfdf5; }
.luvron-pattern .lv-tier-card h3.lv-display {
    font-size: 40px; margin-bottom: 8px;
    color: #0f172a !important; position: relative; z-index: 1;
}
.luvron-pattern .lv-tier-price {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 22px; font-weight: 600; color: #475569;
    margin: 0 0 24px; position: relative; z-index: 1;
}
.luvron-pattern .lv-tier-features {
    list-style: none; padding: 0; margin: 0; position: relative; z-index: 1;
}
.luvron-pattern .lv-tier-features li {
    display: flex; gap: 12px; align-items: flex-start;
    padding: 10px 0; font-size: 14.5px; color: #475569;
    border-top: 1px solid rgba(15,23,42,0.08);
}
.luvron-pattern .lv-tier-features li:first-child { border-top: 0; }
.luvron-pattern .lv-tier-features li strong { color: #0f172a; font-weight: 600; }
.luvron-pattern .lv-check {
    flex-shrink: 0; width: 22px; height: 22px;
    border-radius: 50%; background: #ffffff;
    display: inline-flex; align-items: center; justify-content: center;
    color: #0f172a; font-weight: 700; font-size: 12px;
}
.luvron-pattern .lv-tier-musical .lv-check { color: #b45309; }
.luvron-pattern .lv-tier-normal .lv-check  { color: #2f9e44; }
.luvron-pattern .lv-hsn {
    margin-top: 20px; padding-top: 20px;
    border-top: 1px solid rgba(15,23,42,0.08);
    font-size: 12.5px; color: #475569;
    position: relative; z-index: 1;
}
.luvron-pattern .lv-hsn code {
    font-family: "SFMono-Regular", monospace;
    background: #ffffff; padding: 2px 8px;
    border-radius: 4px; font-size: 11.5px;
}
@media (max-width: 720px) { .luvron-pattern .lv-tier-grid { grid-template-columns: 1fr; } .luvron-pattern .lv-tier-card { padding: 28px 24px; } }

/* WHY LUVRON FEATURES */
.luvron-pattern .lv-feat-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px;
}
.luvron-pattern .lv-feat {
    background: #ffffff; border: 1px solid #e5e7eb;
    border-radius: 20px; padding: 32px 28px;
    transition: all .25s cubic-bezier(.23,1,.32,1);
}
.luvron-pattern .lv-feat:hover {
    transform: translateY(-4px);
    border-color: #ffd9d3;
    box-shadow: 0 18px 48px rgba(15,23,42,0.06);
}
.luvron-pattern .lv-feat-ico {
    width: 56px; height: 56px; border-radius: 12px;
    display: inline-flex; align-items: center; justify-content: center;
    margin-bottom: 20px; font-size: 24px;
}
.luvron-pattern .lv-feat h3 {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 20px; font-weight: 700;
    color: #0f172a !important; margin: 0 0 10px;
    letter-spacing: -0.01em;
}
.luvron-pattern .lv-feat p {
    font-size: 14.5px; color: #475569; line-height: 1.55; margin: 0;
}
@media (max-width: 980px) { .luvron-pattern .lv-feat-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 600px) { .luvron-pattern .lv-feat-grid { grid-template-columns: 1fr; } }

/* TESTIMONIALS */
.luvron-pattern .lv-testi-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 24px;
}
.luvron-pattern .lv-testi {
    background: #ffffff; border: 1px solid #e5e7eb;
    border-radius: 20px; padding: 32px 28px;
    transition: transform .25s cubic-bezier(.23,1,.32,1);
}
.luvron-pattern .lv-testi:hover {
    transform: translateY(-3px);
    box-shadow: 0 18px 48px rgba(15,23,42,0.06);
}
.luvron-pattern .lv-stars {
    color: #f59e0b; font-size: 18px; margin-bottom: 16px;
    letter-spacing: 2px;
}
.luvron-pattern .lv-quote {
    font-size: 16px; line-height: 1.6; color: #0f172a;
    margin: 0 0 22px;
}
.luvron-pattern .lv-who {
    display: flex; align-items: center; gap: 14px;
    padding-top: 18px; border-top: 1px solid #e5e7eb;
}
.luvron-pattern .lv-who img {
    width: 50px; height: 50px; border-radius: 50%;
    object-fit: cover; border: 2px solid #fff0ec;
}
.luvron-pattern .lv-who strong {
    display: block; font-size: 15px; font-weight: 700; color: #0f172a;
}
.luvron-pattern .lv-who small {
    font-size: 12.5px; color: #475569; font-weight: 500;
}
@media (max-width: 720px) { .luvron-pattern .lv-testi-grid { grid-template-columns: 1fr; } }

/* CTA BANNER */
.luvron-pattern .lv-cta-banner {
    border-radius: 28px; padding: 72px 56px;
    background: linear-gradient(135deg, #ff6b5b 0%, #e84a3f 100%);
    color: #ffffff; position: relative; overflow: hidden;
    display: grid; grid-template-columns: 1.4fr 1fr;
    gap: 48px; align-items: center;
}
.luvron-pattern .lv-cta-banner::before {
    content: ""; position: absolute;
    top: -100px; right: -50px; width: 300px; height: 300px;
    border-radius: 50%; background: rgba(255,255,255,0.1);
}
.luvron-pattern .lv-cta-banner::after {
    content: ""; position: absolute;
    bottom: -80px; left: 30%; width: 200px; height: 200px;
    border-radius: 50%; background: rgba(255,201,60,0.2);
}
.luvron-pattern .lv-cta-text { position: relative; z-index: 1; }
.luvron-pattern .lv-cta-banner h2.lv-display {
    font-size: clamp(32px, 4.5vw, 48px);
    color: #ffffff !important;
    margin-bottom: 16px;
}
.luvron-pattern .lv-cta-banner p {
    font-size: 17px; opacity: 0.92;
    max-width: 480px; margin: 0;
}
.luvron-pattern .lv-cta-actions {
    display: flex; flex-direction: column; gap: 12px;
    position: relative; z-index: 1;
}
.luvron-pattern .lv-cta-actions .lv-btn { justify-content: center; }
@media (max-width: 720px) {
    .luvron-pattern .lv-cta-banner { grid-template-columns: 1fr; padding: 48px 28px; gap: 24px; }
}

/* HERO SLIM (subpages) */
.luvron-pattern .lv-hero-slim {
    padding: 64px 0 80px; background: #fff7e6;
    border-bottom: 1px solid #e5e7eb;
}
.luvron-pattern .lv-breadcrumb {
    font-size: 13px; color: #475569; margin-bottom: 18px;
}
.luvron-pattern .lv-breadcrumb a { color: inherit; text-decoration: none; }
.luvron-pattern .lv-breadcrumb a:hover { color: #ff6b5b; }
.luvron-pattern .lv-hero-slim h1.lv-display {
    font-size: clamp(36px, 5vw, 56px);
    margin-bottom: 14px;
}
CSS;
}

function luvron_chrome_css() {
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
