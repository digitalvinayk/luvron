<?php
/**
 * Plugin Name: Luvron — Homepage Block Pattern
 * Description: Modern enterprise B2B homepage — hero with floating product chips,
 *              stats band, featured products grid, category cards with real product
 *              imagery, why-Luvron, two-tier explainer, testimonials, CTA banner.
 *              All scoped under .luvron-pattern with inline styles.
 * Version: 3.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-homepage-pattern')) return;

add_action('init', function () {
    if (!function_exists('register_block_pattern_category') || !function_exists('register_block_pattern')) return;
    register_block_pattern_category('luvron', ['label' => 'Luvron']);
    register_block_pattern('luvron/homepage', [
        'title'       => 'Luvron Homepage',
        'description' => 'Hero, stats, featured products, categories, why-Luvron, tiers, testimonials, CTA.',
        'categories'  => ['luvron'],
        'content'     => luvron_homepage_pattern_content(),
    ]);
    register_block_pattern('luvron/become-dealer', [
        'title'       => 'Luvron — Become a Dealer',
        'description' => 'Dealer landing with margin pitch, registration, FAQ.',
        'categories'  => ['luvron'],
        'content'     => luvron_become_dealer_pattern_content(),
    ]);
});

function luvron_homepage_pattern_content() {
    $u = trailingslashit(wp_upload_dir()['baseurl']);
    $p = $u . 'luvron-products/';
    $t = $u . 'luvron-testimonials/';

    return <<<HTML
<!-- wp:html -->
<div class="luvron-pattern">
<style>
.luvron-pattern{font-family:"Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;color:#0f172a;line-height:1.55}
.luvron-pattern *,.luvron-pattern *::before,.luvron-pattern *::after{box-sizing:border-box}
.luvron-pattern img{max-width:100%;display:block}
.luvron-pattern a{text-decoration:none;color:inherit}
.luvron-pattern .lv-wrap{max-width:1280px;margin:0 auto;padding:0 32px}
@media(max-width:720px){.luvron-pattern .lv-wrap{padding:0 20px}}
.luvron-pattern .lv-display{font-family:"Bricolage Grotesque","Plus Jakarta Sans",sans-serif;font-weight:700;line-height:1.05;letter-spacing:-0.025em;margin:0;color:#0f172a}
.luvron-pattern .lv-accent{color:#ff6b5b;position:relative;display:inline-block}
.luvron-pattern .lv-accent::after{content:"";position:absolute;left:0;right:0;bottom:6px;height:14px;background:#ffc93c;z-index:-1;border-radius:2px;opacity:.55}
.luvron-pattern .lv-hand{font-family:"Caveat",cursive;color:#ff6b5b;font-weight:700;font-size:0.95em;display:inline-block;transform:rotate(-2deg);font-style:normal}
.luvron-pattern .lv-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px 28px;border-radius:999px;font-weight:600;font-size:15px;transition:all .25s cubic-bezier(.23,1,.32,1);border:2px solid transparent;font-family:"Plus Jakarta Sans",sans-serif;cursor:pointer}
.luvron-pattern .lv-btn-primary{background:#ff6b5b;color:#fff;box-shadow:0 8px 30px rgba(255,107,91,0.30)}
.luvron-pattern .lv-btn-primary:hover{background:#e84a3f;transform:translateY(-2px);box-shadow:0 14px 36px rgba(232,74,63,0.40);color:#fff}
.luvron-pattern .lv-btn-ghost{background:transparent;color:#0f172a;border-color:#0f172a}
.luvron-pattern .lv-btn-ghost:hover{background:#0f172a;color:#fff}
.luvron-pattern .lv-btn-light{background:#fff;color:#0f172a}
.luvron-pattern .lv-btn-light:hover{transform:translateY(-2px);box-shadow:0 14px 28px rgba(0,0,0,.18)}
.luvron-pattern .lv-btn-yellow{background:#ffc93c;color:#0f172a}
.luvron-pattern .lv-btn-yellow:hover{background:#e0a91b;transform:translateY(-2px)}
.luvron-pattern .lv-section{padding:96px 0;background:#fff;position:relative}
.luvron-pattern .lv-bg-canvas{background:#fdfaf6}
.luvron-pattern .lv-bg-cream{background:#fff7e6}
.luvron-pattern .lv-bg-mesh{background:linear-gradient(180deg,#fff 0%,#fdfaf6 100%);position:relative;overflow:hidden}
.luvron-pattern .lv-bg-mesh::before{content:"";position:absolute;top:-200px;left:-100px;width:500px;height:500px;background:radial-gradient(circle,#ffd9d3,transparent 70%);filter:blur(60px);pointer-events:none}
.luvron-pattern .lv-bg-mesh::after{content:"";position:absolute;bottom:-200px;right:-100px;width:500px;height:500px;background:radial-gradient(circle,#c7e2fb,transparent 70%);filter:blur(60px);pointer-events:none}
.luvron-pattern .lv-head{text-align:center;max-width:720px;margin:0 auto 64px;position:relative;z-index:2}
.luvron-pattern .lv-head .lv-eyebrow{display:inline-block;color:#ff6b5b;font-size:13px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:14px}
.luvron-pattern .lv-head h2.lv-display{font-size:clamp(32px,4.5vw,52px);margin-bottom:16px}
.luvron-pattern .lv-head p{font-size:17px;color:#475569;line-height:1.6;margin:0}

/* HERO */
.luvron-pattern .lv-hero{padding:100px 0 120px;position:relative;overflow:hidden;background:#fff}
.luvron-pattern .lv-hero::before{content:"";position:absolute;top:-150px;left:-100px;width:600px;height:600px;background:radial-gradient(circle,#ffd9d3,transparent 70%);filter:blur(80px);opacity:.55;pointer-events:none}
.luvron-pattern .lv-hero::after{content:"";position:absolute;bottom:-150px;right:-100px;width:600px;height:600px;background:radial-gradient(circle,#c7e2fb,transparent 70%);filter:blur(80px);opacity:.45;pointer-events:none}
.luvron-pattern .lv-hero-inner{display:grid;grid-template-columns:1.1fr 0.9fr;gap:80px;align-items:center;position:relative;z-index:1}
@media(max-width:980px){.luvron-pattern .lv-hero-inner{grid-template-columns:1fr;gap:48px}.luvron-pattern .lv-hero{padding:64px 0 80px}}
.luvron-pattern .lv-hero-eyebrow{display:inline-flex;align-items:center;gap:10px;padding:8px 16px;background:#fff;border:1px solid #ffd9d3;color:#c93a30;border-radius:999px;font-size:12.5px;font-weight:600;letter-spacing:0.02em;margin-bottom:28px;box-shadow:0 4px 12px rgba(232,74,63,.08)}
.luvron-pattern .lv-pulse{width:8px;height:8px;border-radius:50%;background:#ff6b5b;box-shadow:0 0 0 0 rgba(255,107,91,.5);animation:lv-pulse 2s infinite}
@keyframes lv-pulse{0%,100%{box-shadow:0 0 0 0 rgba(255,107,91,.5)}70%{box-shadow:0 0 0 10px rgba(255,107,91,0)}}
.luvron-pattern .lv-hero h1{font-family:"Bricolage Grotesque",sans-serif;font-size:clamp(40px,6.5vw,80px);font-weight:700;line-height:1.02;letter-spacing:-0.025em;margin:0 0 24px;color:#0f172a}
.luvron-pattern .lv-hero h1 br{display:block}
.luvron-pattern .lv-hero p.lv-lead{font-size:19px;color:#475569;line-height:1.6;max-width:520px;margin:0 0 36px}
.luvron-pattern .lv-hero p.lv-lead strong{color:#0f172a;font-weight:600}
.luvron-pattern .lv-hero-actions{display:flex;gap:14px;flex-wrap:wrap;align-items:center}
.luvron-pattern .lv-trust-row{display:flex;gap:24px;margin-top:40px;flex-wrap:wrap;color:#64748b;font-size:13.5px;font-weight:500}
.luvron-pattern .lv-trust-row span{display:inline-flex;align-items:center;gap:6px}
.luvron-pattern .lv-trust-row .lv-tick{color:#2f9e44;font-weight:800}

/* HERO PRODUCT VISUAL */
.luvron-pattern .lv-hero-visual{position:relative;aspect-ratio:1;display:flex;align-items:center;justify-content:center}
.luvron-pattern .lv-hero-stage{position:relative;width:100%;height:100%;background:linear-gradient(135deg,#fff7e6 0%,#fff0ec 50%,#ffd9d3 100%);border-radius:32px;display:flex;align-items:center;justify-content:center;overflow:hidden;box-shadow:0 30px 80px rgba(15,23,42,.10)}
.luvron-pattern .lv-hero-stage::before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 30% 30%,rgba(255,255,255,.5),transparent 60%);pointer-events:none}
.luvron-pattern .lv-hero-stage::after{content:"";position:absolute;width:90%;height:30px;bottom:8%;left:5%;background:radial-gradient(ellipse,rgba(15,23,42,.18),transparent 70%);filter:blur(15px);z-index:0}
.luvron-pattern .lv-hero-product{max-width:78%;max-height:78%;object-fit:contain;position:relative;z-index:1;animation:lv-float 6s ease-in-out infinite;filter:drop-shadow(0 25px 35px rgba(15,23,42,.18))}
@keyframes lv-float{0%,100%{transform:translateY(0) rotate(-1deg)}50%{transform:translateY(-14px) rotate(1deg)}}
.luvron-pattern .lv-chip{position:absolute;background:#fff;box-shadow:0 14px 40px rgba(15,23,42,.12);padding:14px 18px;border-radius:14px;display:flex;align-items:center;gap:12px;font-size:13px;font-weight:600;color:#0f172a;z-index:2;border:1px solid rgba(15,23,42,.04)}
.luvron-pattern .lv-chip-ico{width:36px;height:36px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.luvron-pattern .lv-chip-1{top:8%;left:-8%;transform:rotate(-3deg);animation:lv-chip-in .9s cubic-bezier(.23,1,.32,1) .3s backwards}
.luvron-pattern .lv-chip-2{top:18%;right:-12%;transform:rotate(2deg);animation:lv-chip-in .9s cubic-bezier(.23,1,.32,1) .5s backwards}
.luvron-pattern .lv-chip-3{bottom:18%;left:-10%;transform:rotate(2deg);animation:lv-chip-in .9s cubic-bezier(.23,1,.32,1) .7s backwards}
.luvron-pattern .lv-chip-4{bottom:8%;right:-8%;transform:rotate(-2deg);animation:lv-chip-in .9s cubic-bezier(.23,1,.32,1) .9s backwards}
@keyframes lv-chip-in{from{opacity:0;transform:translate(0,30px) rotate(0)}}
@media(max-width:980px){
  .luvron-pattern .lv-chip-1,.luvron-pattern .lv-chip-2,.luvron-pattern .lv-chip-3,.luvron-pattern .lv-chip-4{position:static;transform:none;display:inline-flex;margin:4px}
  .luvron-pattern .lv-hero-stage{aspect-ratio:1;width:100%;max-width:480px;margin:0 auto}
}

/* STATS BAND */
.luvron-pattern .lv-stats{padding:0;background:#fff}
.luvron-pattern .lv-stats-inner{display:grid;grid-template-columns:repeat(5,1fr);background:#fff;border-radius:24px;border:1px solid #e5e7eb;overflow:hidden;box-shadow:0 8px 24px rgba(15,23,42,.04);margin-top:-60px;position:relative;z-index:3}
.luvron-pattern .lv-stat{padding:36px 24px;text-align:center;border-right:1px solid #e5e7eb}
.luvron-pattern .lv-stat:last-child{border-right:0}
.luvron-pattern .lv-stat-num{font-family:"Bricolage Grotesque",sans-serif;font-size:48px;line-height:1;font-weight:700;color:#0f172a;margin-bottom:8px;letter-spacing:-0.02em}
.luvron-pattern .lv-stat-num em{color:#ff6b5b;font-style:normal}
.luvron-pattern .lv-stat-num .small{font-size:24px;color:#64748b;font-weight:600}
.luvron-pattern .lv-stat-label{font-size:11.5px;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;font-weight:600}
@media(max-width:900px){.luvron-pattern .lv-stats-inner{grid-template-columns:repeat(2,1fr);margin-top:0}.luvron-pattern .lv-stat:nth-child(2n){border-right:0}.luvron-pattern .lv-stat:nth-child(-n+4){border-bottom:1px solid #e5e7eb}.luvron-pattern .lv-stat:nth-child(5){grid-column:span 2;border-bottom:0}.luvron-pattern .lv-stat-num{font-size:36px}}

/* FEATURED PRODUCTS */
.luvron-pattern .lv-featured{padding:120px 0 96px;background:#fff}
.luvron-pattern .lv-prod-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px}
@media(max-width:980px){.luvron-pattern .lv-prod-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:540px){.luvron-pattern .lv-prod-grid{grid-template-columns:1fr}}
.luvron-pattern .lv-prod{background:#fff;border:1px solid #e5e7eb;border-radius:20px;overflow:hidden;transition:all .3s cubic-bezier(.23,1,.32,1);text-decoration:none;display:flex;flex-direction:column;position:relative}
.luvron-pattern .lv-prod:hover{transform:translateY(-8px);border-color:#ffd9d3;box-shadow:0 24px 60px rgba(15,23,42,.10)}
.luvron-pattern .lv-prod-img{aspect-ratio:1;display:flex;align-items:center;justify-content:center;padding:24px;position:relative;overflow:hidden}
.luvron-pattern .lv-prod-img img{max-width:80%;max-height:80%;object-fit:contain;transition:transform .4s cubic-bezier(.23,1,.32,1);filter:drop-shadow(0 14px 24px rgba(15,23,42,.12))}
.luvron-pattern .lv-prod:hover .lv-prod-img img{transform:scale(1.08) rotate(-3deg)}
.luvron-pattern .lv-prod:nth-child(4n+1) .lv-prod-img{background:linear-gradient(135deg,#fff7e6,#fff0ec)}
.luvron-pattern .lv-prod:nth-child(4n+2) .lv-prod-img{background:linear-gradient(135deg,#eef5ff,#d8e8fa)}
.luvron-pattern .lv-prod:nth-child(4n+3) .lv-prod-img{background:linear-gradient(135deg,#ecfdf5,#d4f1d8)}
.luvron-pattern .lv-prod:nth-child(4n) .lv-prod-img{background:linear-gradient(135deg,#f3eeff,#e7dffa)}
.luvron-pattern .lv-prod-badge{position:absolute;top:14px;left:14px;padding:5px 11px;border-radius:999px;font-size:10.5px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;display:inline-flex;align-items:center;gap:5px;backdrop-filter:blur(8px)}
.luvron-pattern .lv-prod-badge.musical{background:rgba(180,83,9,.95);color:#fff}
.luvron-pattern .lv-prod-badge.normal{background:rgba(47,158,68,.95);color:#fff}
.luvron-pattern .lv-prod-info{padding:22px 22px 24px;display:flex;flex-direction:column;flex:1}
.luvron-pattern .lv-prod-series{font-size:11px;font-weight:700;letter-spacing:0.08em;color:#ff6b5b;text-transform:uppercase;margin-bottom:6px}
.luvron-pattern .lv-prod h3{font-family:"Bricolage Grotesque",sans-serif;font-size:18px;font-weight:700;color:#0f172a;line-height:1.2;letter-spacing:-0.01em;margin:0 0 14px}
.luvron-pattern .lv-prod-meta{display:flex;justify-content:space-between;font-size:12px;color:#64748b;padding-top:14px;border-top:1px solid #f1f5f9;margin-top:auto}
.luvron-pattern .lv-prod-meta strong{color:#0f172a;font-weight:600}
.luvron-pattern .lv-prod-cta{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:11px;margin-top:16px;background:#0f172a;color:#fff;border-radius:999px;font-weight:600;font-size:13px;transition:all .2s}
.luvron-pattern .lv-prod:hover .lv-prod-cta{background:#ff6b5b;box-shadow:0 6px 18px rgba(255,107,91,.30)}

/* CATEGORIES */
.luvron-pattern .lv-cat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
@media(max-width:980px){.luvron-pattern .lv-cat-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:720px){.luvron-pattern .lv-cat-grid{grid-template-columns:repeat(2,1fr);gap:14px}}
.luvron-pattern .lv-cat{position:relative;border-radius:20px;overflow:hidden;text-decoration:none;color:inherit;transition:all .3s cubic-bezier(.23,1,.32,1);display:block;aspect-ratio:1;box-shadow:0 4px 16px rgba(15,23,42,.04)}
.luvron-pattern .lv-cat:hover{transform:translateY(-6px);box-shadow:0 20px 50px rgba(15,23,42,.12)}
.luvron-pattern .lv-cat-bg{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:30px}
.luvron-pattern .lv-cat-bg img{max-width:80%;max-height:80%;object-fit:contain;transition:transform .4s cubic-bezier(.23,1,.32,1);filter:drop-shadow(0 12px 22px rgba(15,23,42,.15))}
.luvron-pattern .lv-cat:hover .lv-cat-bg img{transform:scale(1.08) rotate(-3deg)}
.luvron-pattern .lv-cat-info{position:absolute;left:0;right:0;bottom:0;padding:18px 22px;background:linear-gradient(to top,rgba(15,23,42,.85) 0%,rgba(15,23,42,0) 100%);color:#fff;display:flex;justify-content:space-between;align-items:flex-end}
.luvron-pattern .lv-cat-info h3{font-family:"Bricolage Grotesque",sans-serif;font-size:22px;font-weight:700;color:#fff;margin:0 0 2px;letter-spacing:-0.01em}
.luvron-pattern .lv-cat-info p{font-size:12px;color:rgba(255,255,255,.85);margin:0;font-weight:500}
.luvron-pattern .lv-cat-info .lv-cat-arr{width:32px;height:32px;border-radius:50%;background:#fff;color:#0f172a;display:inline-flex;align-items:center;justify-content:center;font-size:14px;transition:all .3s}
.luvron-pattern .lv-cat:hover .lv-cat-info .lv-cat-arr{background:#ff6b5b;color:#fff;transform:rotate(-45deg)}
.luvron-pattern .lv-cat-coral{background:linear-gradient(135deg,#fff0ec 0%,#ffd9d3 100%)}
.luvron-pattern .lv-cat-sky{background:linear-gradient(135deg,#eef5ff 0%,#d8e8fa 100%)}
.luvron-pattern .lv-cat-mint{background:linear-gradient(135deg,#ecfdf5 0%,#d4f1d8 100%)}
.luvron-pattern .lv-cat-cream{background:linear-gradient(135deg,#fff7e6 0%,#ffe9a8 100%)}
.luvron-pattern .lv-cat-lavender{background:linear-gradient(135deg,#f3eeff 0%,#e7dffa 100%)}

/* WHY LUVRON */
.luvron-pattern .lv-feat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
@media(max-width:980px){.luvron-pattern .lv-feat-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.luvron-pattern .lv-feat-grid{grid-template-columns:1fr}}
.luvron-pattern .lv-feat{background:#fff;border:1px solid #e5e7eb;border-radius:20px;padding:36px 32px;transition:all .3s cubic-bezier(.23,1,.32,1)}
.luvron-pattern .lv-feat:hover{transform:translateY(-4px);border-color:#ffd9d3;box-shadow:0 18px 40px rgba(15,23,42,.06)}
.luvron-pattern .lv-feat-ico{width:56px;height:56px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:24px;font-size:24px;font-weight:700}
.luvron-pattern .lv-feat h3{font-family:"Bricolage Grotesque",sans-serif;font-size:21px;font-weight:700;color:#0f172a;margin:0 0 10px;letter-spacing:-0.01em}
.luvron-pattern .lv-feat p{font-size:14.5px;color:#475569;line-height:1.6;margin:0}

/* TIERS */
.luvron-pattern .lv-tier-grid{display:grid;grid-template-columns:1fr 1fr;gap:28px;margin-top:32px}
@media(max-width:720px){.luvron-pattern .lv-tier-grid{grid-template-columns:1fr}}
.luvron-pattern .lv-tier{border-radius:24px;padding:44px 40px;position:relative;overflow:hidden}
.luvron-pattern .lv-tier-musical{background:linear-gradient(135deg,#fff7e6,#fff5d3)}
.luvron-pattern .lv-tier-normal{background:linear-gradient(135deg,#ecfdf5,#d4f4d8)}
.luvron-pattern .lv-tier::before{content:"";position:absolute;top:-60px;right:-60px;width:240px;height:240px;border-radius:50%;opacity:.18}
.luvron-pattern .lv-tier-musical::before{background:#d4a847}
.luvron-pattern .lv-tier-normal::before{background:#7aae8e}
.luvron-pattern .lv-tier-marker{display:inline-flex;align-items:center;gap:10px;padding:7px 16px;border-radius:999px;font-size:12.5px;font-weight:700;background:#fff;margin-bottom:20px;position:relative;z-index:1;letter-spacing:0.02em}
.luvron-pattern .lv-tier-musical .lv-tier-marker{color:#b45309}
.luvron-pattern .lv-tier-normal .lv-tier-marker{color:#2f9e44}
.luvron-pattern .lv-tier h3{font-family:"Bricolage Grotesque",sans-serif;font-size:42px;font-weight:700;color:#0f172a;line-height:1.05;letter-spacing:-0.025em;margin:0 0 10px;position:relative;z-index:1}
.luvron-pattern .lv-tier-price{font-family:"Bricolage Grotesque",sans-serif;font-size:22px;font-weight:600;color:#475569;margin:0 0 24px;position:relative;z-index:1}
.luvron-pattern .lv-tier ul{list-style:none;padding:0;margin:0;position:relative;z-index:1}
.luvron-pattern .lv-tier li{display:flex;gap:14px;align-items:flex-start;padding:14px 0;font-size:14.5px;color:#475569;border-top:1px solid rgba(15,23,42,.08)}
.luvron-pattern .lv-tier li:first-child{border-top:0}
.luvron-pattern .lv-tier li strong{color:#0f172a;font-weight:600}
.luvron-pattern .lv-tier-check{flex-shrink:0;width:22px;height:22px;border-radius:50%;background:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:12px}
.luvron-pattern .lv-tier-musical .lv-tier-check{color:#b45309}
.luvron-pattern .lv-tier-normal .lv-tier-check{color:#2f9e44}
.luvron-pattern .lv-tier-hsn{margin-top:24px;padding-top:20px;border-top:1px solid rgba(15,23,42,.08);font-size:12.5px;color:#475569;position:relative;z-index:1}
.luvron-pattern .lv-tier-hsn code{font-family:"SFMono-Regular",monospace;background:#fff;padding:3px 9px;border-radius:5px;font-size:11.5px;font-weight:600}

/* TESTIMONIALS */
.luvron-pattern .lv-testi-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:24px}
@media(max-width:720px){.luvron-pattern .lv-testi-grid{grid-template-columns:1fr}}
.luvron-pattern .lv-testi{background:#fff;border:1px solid #e5e7eb;border-radius:20px;padding:36px 32px;transition:transform .25s cubic-bezier(.23,1,.32,1);position:relative}
.luvron-pattern .lv-testi:hover{transform:translateY(-3px);box-shadow:0 18px 40px rgba(15,23,42,.06)}
.luvron-pattern .lv-testi::before{content:"\\201C";position:absolute;top:-14px;left:24px;background:#ff6b5b;color:#fff;width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:"Bricolage Grotesque",serif;font-size:48px;line-height:1;box-shadow:0 8px 20px rgba(255,107,91,.30)}
.luvron-pattern .lv-stars{color:#f59e0b;font-size:17px;margin:8px 0 16px;letter-spacing:2px}
.luvron-pattern .lv-quote{font-size:16px;line-height:1.6;color:#0f172a;margin:0 0 26px;font-weight:500}
.luvron-pattern .lv-who{display:flex;align-items:center;gap:14px;padding-top:22px;border-top:1px solid #f1f5f9}
.luvron-pattern .lv-who img{width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid #fff;box-shadow:0 0 0 2px #ffd9d3}
.luvron-pattern .lv-who strong{display:block;font-size:15px;font-weight:700;color:#0f172a;margin-bottom:2px}
.luvron-pattern .lv-who small{font-size:12.5px;color:#64748b;font-weight:500}

/* CTA BANNER */
.luvron-pattern .lv-cta{padding:120px 0;position:relative}
.luvron-pattern .lv-cta-banner{border-radius:32px;padding:80px 64px;background:linear-gradient(135deg,#ff6b5b 0%,#e84a3f 50%,#c93a30 100%);color:#fff;position:relative;overflow:hidden;display:grid;grid-template-columns:1.4fr 1fr;gap:56px;align-items:center;box-shadow:0 30px 80px rgba(232,74,63,.30)}
@media(max-width:760px){.luvron-pattern .lv-cta-banner{grid-template-columns:1fr;padding:48px 32px;gap:24px}}
.luvron-pattern .lv-cta-banner::before{content:"";position:absolute;top:-100px;right:-50px;width:300px;height:300px;border-radius:50%;background:rgba(255,255,255,.12)}
.luvron-pattern .lv-cta-banner::after{content:"";position:absolute;bottom:-80px;left:30%;width:240px;height:240px;border-radius:50%;background:rgba(255,201,60,.25)}
.luvron-pattern .lv-cta-text{position:relative;z-index:1}
.luvron-pattern .lv-cta-banner h2{font-family:"Bricolage Grotesque",sans-serif;font-size:clamp(32px,4.5vw,52px);font-weight:700;color:#fff;line-height:1.05;letter-spacing:-0.025em;margin:0 0 18px}
.luvron-pattern .lv-cta-banner h2 .lv-yellow{color:#ffc93c}
.luvron-pattern .lv-cta-banner p{font-size:17px;opacity:.92;line-height:1.6;max-width:480px;margin:0}
.luvron-pattern .lv-cta-actions{display:flex;flex-direction:column;gap:12px;position:relative;z-index:1}
.luvron-pattern .lv-cta-actions .lv-btn{justify-content:center;padding:16px 28px}
.luvron-pattern .lv-cta-tel{font-size:12.5px;color:rgba(255,255,255,.7);text-align:center;letter-spacing:0.04em;margin-top:4px}
</style>

<!-- HERO -->
<section class="lv-hero">
  <div class="lv-wrap lv-hero-inner">
    <div class="lv-hero-text">
      <span class="lv-hero-eyebrow"><span class="lv-pulse"></span> Manufacturer Direct · B2B Wholesale</span>
      <h1>Tricycles built for <span class="lv-accent">joy</span>.<br>Dispatched in <span class="lv-hand">48 hours.</span></h1>
      <p class="lv-lead"><strong>India's most carefully packed kids tricycles</strong> — direct from our Loni, Ghaziabad floor to your retail shelf. 44 SKUs, 11 series, GST-compliant invoicing, dealer-tier pricing.</p>
      <div class="lv-hero-actions">
        <a href="/become-a-dealer/" class="lv-btn lv-btn-primary">Become a Dealer →</a>
        <a href="/shop/" class="lv-btn lv-btn-ghost">Browse 44 SKUs</a>
      </div>
      <div class="lv-trust-row">
        <span><span class="lv-tick">✓</span> No middlemen</span>
        <span><span class="lv-tick">✓</span> 48-hour dispatch SLA</span>
        <span><span class="lv-tick">✓</span> GSTIN 09GOCPP5350G1ZQ</span>
      </div>
    </div>
    <div class="lv-hero-visual">
      <div class="lv-chip lv-chip-1">
        <span class="lv-chip-ico" style="background:#fff7e6;color:#b45309;">♪</span>
        <span>Musical · 18% GST</span>
      </div>
      <div class="lv-chip lv-chip-2">
        <span class="lv-chip-ico" style="background:#ecfdf5;color:#2f9e44;">▦</span>
        <span>Master Pack · 12</span>
      </div>
      <div class="lv-chip lv-chip-3">
        <span class="lv-chip-ico" style="background:#eef5ff;color:#1c7ed6;">★</span>
        <span>Premium Series</span>
      </div>
      <div class="lv-chip lv-chip-4">
        <span class="lv-chip-ico" style="background:#fff0ec;color:#e84a3f;">⚡</span>
        <span>48hr Dispatch</span>
      </div>
      <div class="lv-hero-stage">
        <img class="lv-hero-product" src="{$p}sigma-plus.jpg" alt="SIGMA Plus tricycle — flagship musical model" loading="eager">
      </div>
    </div>
  </div>
</section>

<!-- STATS -->
<section class="lv-stats">
  <div class="lv-wrap">
    <div class="lv-stats-inner">
      <div class="lv-stat"><div class="lv-stat-num">44</div><div class="lv-stat-label">Active SKUs</div></div>
      <div class="lv-stat"><div class="lv-stat-num">11</div><div class="lv-stat-label">Model Series</div></div>
      <div class="lv-stat"><div class="lv-stat-num"><em>48</em><span class="small">hr</span></div><div class="lv-stat-label">Dispatch SLA</div></div>
      <div class="lv-stat"><div class="lv-stat-num">50<em>+</em></div><div class="lv-stat-label">Active Dealers</div></div>
      <div class="lv-stat"><div class="lv-stat-num">4<span class="small">yrs</span></div><div class="lv-stat-label">In Production</div></div>
    </div>
  </div>
</section>

<!-- FEATURED PRODUCTS -->
<section class="lv-featured">
  <div class="lv-wrap">
    <div class="lv-head">
      <span class="lv-eyebrow">Best Sellers</span>
      <h2 class="lv-display">Our most-ordered <span class="lv-accent">tricycles</span>.</h2>
      <p>Top SKUs by carton volume across all dealer accounts. Login to unlock wholesale pricing.</p>
    </div>
    <div class="lv-prod-grid">
      <a href="/shop/" class="lv-prod">
        <div class="lv-prod-img">
          <span class="lv-prod-badge musical">♪ Musical</span>
          <img src="{$p}sigma-plus.jpg" alt="SIGMA Plus" loading="lazy">
        </div>
        <div class="lv-prod-info">
          <span class="lv-prod-series">SIGMA Series</span>
          <h3>SIGMA Plus Tricycle</h3>
          <div class="lv-prod-meta"><span>Pack <strong>1×1 / 12</strong></span><span><strong>18%</strong> GST</span></div>
          <span class="lv-prod-cta">Login for Price →</span>
        </div>
      </a>
      <a href="/shop/" class="lv-prod">
        <div class="lv-prod-img">
          <span class="lv-prod-badge musical">♪ Musical</span>
          <img src="{$p}aura-plus.jpg" alt="AURA Plus" loading="lazy">
        </div>
        <div class="lv-prod-info">
          <span class="lv-prod-series">AURA Series</span>
          <h3>AURA Plus Tricycle</h3>
          <div class="lv-prod-meta"><span>Pack <strong>1×1 / 12</strong></span><span><strong>18%</strong> GST</span></div>
          <span class="lv-prod-cta">Login for Price →</span>
        </div>
      </a>
      <a href="/shop/" class="lv-prod">
        <div class="lv-prod-img">
          <span class="lv-prod-badge musical">♪ Musical</span>
          <img src="{$p}eagle-plus.jpg" alt="EAGLE Plus" loading="lazy">
        </div>
        <div class="lv-prod-info">
          <span class="lv-prod-series">EAGLE Series</span>
          <h3>EAGLE Plus Tricycle</h3>
          <div class="lv-prod-meta"><span>Pack <strong>1×1 / 12</strong></span><span><strong>18%</strong> GST</span></div>
          <span class="lv-prod-cta">Login for Price →</span>
        </div>
      </a>
      <a href="/shop/" class="lv-prod">
        <div class="lv-prod-img">
          <span class="lv-prod-badge musical">♪ Musical</span>
          <img src="{$p}alex-plus.jpg" alt="ALEX Plus" loading="lazy">
        </div>
        <div class="lv-prod-info">
          <span class="lv-prod-series">ALEX Series</span>
          <h3>ALEX Plus Tricycle</h3>
          <div class="lv-prod-meta"><span>Pack <strong>1×1 / 12</strong></span><span><strong>18%</strong> GST</span></div>
          <span class="lv-prod-cta">Login for Price →</span>
        </div>
      </a>
      <a href="/shop/" class="lv-prod">
        <div class="lv-prod-img">
          <span class="lv-prod-badge normal">⚙ Normal</span>
          <img src="{$p}rambo-333.jpg" alt="RAMBO 333" loading="lazy">
        </div>
        <div class="lv-prod-info">
          <span class="lv-prod-series">RAMBO Series</span>
          <h3>RAMBO 333 Tricycle</h3>
          <div class="lv-prod-meta"><span>Pack <strong>3×4 / 12</strong></span><span><strong>5%</strong> GST</span></div>
          <span class="lv-prod-cta">Login for Price →</span>
        </div>
      </a>
      <a href="/shop/" class="lv-prod">
        <div class="lv-prod-img">
          <span class="lv-prod-badge normal">⚙ Normal</span>
          <img src="{$p}hulk-pro.jpg" alt="HULK Pro" loading="lazy">
        </div>
        <div class="lv-prod-info">
          <span class="lv-prod-series">HULK Series</span>
          <h3>HULK Pro Tricycle</h3>
          <div class="lv-prod-meta"><span>Pack <strong>1×1 / 15</strong></span><span><strong>5%</strong> GST</span></div>
          <span class="lv-prod-cta">Login for Price →</span>
        </div>
      </a>
      <a href="/shop/" class="lv-prod">
        <div class="lv-prod-img">
          <span class="lv-prod-badge musical">♪ Musical</span>
          <img src="{$p}charlie-r1-plus.jpg" alt="CHARLIE R1 Plus" loading="lazy">
        </div>
        <div class="lv-prod-info">
          <span class="lv-prod-series">CHARLIE Series</span>
          <h3>CHARLIE R1 Plus</h3>
          <div class="lv-prod-meta"><span>Pack <strong>1×1 / 15</strong></span><span><strong>18%</strong> GST</span></div>
          <span class="lv-prod-cta">Login for Price →</span>
        </div>
      </a>
      <a href="/shop/" class="lv-prod">
        <div class="lv-prod-img">
          <span class="lv-prod-badge normal">⚙ Normal</span>
          <img src="{$p}emma-r1.jpg" alt="EMMA R1" loading="lazy">
        </div>
        <div class="lv-prod-info">
          <span class="lv-prod-series">EMMA Series</span>
          <h3>EMMA R1 Tricycle</h3>
          <div class="lv-prod-meta"><span>Pack <strong>1×1 / 15</strong></span><span><strong>5%</strong> GST</span></div>
          <span class="lv-prod-cta">Login for Price →</span>
        </div>
      </a>
    </div>
  </div>
</section>

<!-- CATEGORIES -->
<section class="lv-section lv-bg-canvas">
  <div class="lv-wrap">
    <div class="lv-head">
      <span class="lv-eyebrow">Our Collections</span>
      <h2 class="lv-display">Eleven series. <span class="lv-accent">Every shelf covered.</span></h2>
      <p>From premium SIGMA Plus to value-tier RAMBO. Every series ships with master cartons and GST-compliant invoicing.</p>
    </div>
    <div class="lv-cat-grid">
      <a href="/product-category/sigma-series/" class="lv-cat lv-cat-coral"><div class="lv-cat-bg"><img src="{$p}sigma-plus.jpg" alt="SIGMA"></div><div class="lv-cat-info"><div><h3>SIGMA</h3><p>Premium · 4 SKUs</p></div><span class="lv-cat-arr">→</span></div></a>
      <a href="/product-category/aura-series/" class="lv-cat lv-cat-sky"><div class="lv-cat-bg"><img src="{$p}aura-plus.jpg" alt="AURA"></div><div class="lv-cat-info"><div><h3>AURA</h3><p>Mid-tier · 4 SKUs</p></div><span class="lv-cat-arr">→</span></div></a>
      <a href="/product-category/eagle-series/" class="lv-cat lv-cat-mint"><div class="lv-cat-bg"><img src="{$p}eagle-plus.jpg" alt="EAGLE"></div><div class="lv-cat-info"><div><h3>EAGLE</h3><p>Sporty · 4 SKUs</p></div><span class="lv-cat-arr">→</span></div></a>
      <a href="/product-category/alex-series/" class="lv-cat lv-cat-cream"><div class="lv-cat-bg"><img src="{$p}alex-plus.jpg" alt="ALEX"></div><div class="lv-cat-info"><div><h3>ALEX</h3><p>Bestseller · 4 SKUs</p></div><span class="lv-cat-arr">→</span></div></a>
      <a href="/product-category/ecotech-series/" class="lv-cat lv-cat-lavender"><div class="lv-cat-bg"><img src="{$p}ecotech-plus.jpg" alt="ECOTECH"></div><div class="lv-cat-info"><div><h3>ECOTECH</h3><p>Eco · 4 SKUs</p></div><span class="lv-cat-arr">→</span></div></a>
      <a href="/product-category/rambo-series/" class="lv-cat lv-cat-coral"><div class="lv-cat-bg"><img src="{$p}rambo-333-plus.jpg" alt="RAMBO"></div><div class="lv-cat-info"><div><h3>RAMBO</h3><p>Value · 8 SKUs</p></div><span class="lv-cat-arr">→</span></div></a>
      <a href="/product-category/hulk-series/" class="lv-cat lv-cat-sky"><div class="lv-cat-bg"><img src="{$p}hulk-pro-plus.jpg" alt="HULK"></div><div class="lv-cat-info"><div><h3>HULK</h3><p>Sturdy · 4 SKUs</p></div><span class="lv-cat-arr">→</span></div></a>
      <a href="/product-category/charlie-series/" class="lv-cat lv-cat-mint"><div class="lv-cat-bg"><img src="{$p}charlie-r1-plus.jpg" alt="CHARLIE"></div><div class="lv-cat-info"><div><h3>CHARLIE</h3><p>Compact · 4 SKUs</p></div><span class="lv-cat-arr">→</span></div></a>
    </div>
  </div>
</section>

<!-- WHY LUVRON -->
<section class="lv-section">
  <div class="lv-wrap">
    <div class="lv-head">
      <span class="lv-eyebrow">Why Luvron</span>
      <h2 class="lv-display">Built for <span class="lv-accent">dealers who measure margins.</span></h2>
      <p>Six things we get right, every order. No fine print, no overpromise.</p>
    </div>
    <div class="lv-feat-grid">
      <div class="lv-feat"><span class="lv-feat-ico" style="background:#fff0ec;color:#e84a3f;">▲</span><h3>Manufacturer Direct</h3><p>You buy from our workshop, not a distributor's distributor. The price you see is the price we make.</p></div>
      <div class="lv-feat"><span class="lv-feat-ico" style="background:#eef5ff;color:#1c7ed6;">⏱</span><h3>48-Hour Dispatch</h3><p>Confirmed orders leave Loni within 48 working hours. Out-of-stock SKUs flagged at proforma stage.</p></div>
      <div class="lv-feat"><span class="lv-feat-ico" style="background:#ecfdf5;color:#2f9e44;">▦</span><h3>Carton-Tested Packing</h3><p>Inner boxes engineered for stack and transit. Damage rate held below 0.5% on top three corridors.</p></div>
      <div class="lv-feat"><span class="lv-feat-ico" style="background:#fff7e6;color:#b45309;">≡</span><h3>GST-Compliant</h3><p>Every B2B tax invoice carries IRN/QR from the IRP. CGST/SGST split for UP, IGST elsewhere.</p></div>
      <div class="lv-feat"><span class="lv-feat-ico" style="background:#f3eeff;color:#7048e8;">◆</span><h3>Territory Protection</h3><p>Distributors get exclusive PIN-code zones. We don't appoint competing dealers in your area.</p></div>
      <div class="lv-feat"><span class="lv-feat-ico" style="background:#fff0ec;color:#e84a3f;">✆</span><h3>Founder Direct Line</h3><p>Rajneesh Pandey picks up the phone — WhatsApp +91 9212 389 139. Rare in this category.</p></div>
    </div>
  </div>
</section>

<!-- TIERS -->
<section class="lv-section lv-bg-cream">
  <div class="lv-wrap">
    <div class="lv-head">
      <span class="lv-eyebrow">Two Pricing Tiers</span>
      <h2 class="lv-display">Same chassis. <span class="lv-accent">Different soundtrack.</span></h2>
      <p>Every model comes in two tiers — Musical (with horn, lights, music) at 18% GST, or Normal (clean ride, no electronics) at 5% GST.</p>
    </div>
    <div class="lv-tier-grid">
      <div class="lv-tier lv-tier-musical">
        <span class="lv-tier-marker">♪ Musical · 18% GST</span>
        <h3>Plus Variants</h3>
        <p class="lv-tier-price">₹680 – ₹1,760 wholesale</p>
        <ul>
          <li><span class="lv-tier-check">✓</span><span><strong>Horn, lights & music</strong> on the handlebar — kids' top decision driver</span></li>
          <li><span class="lv-tier-check">✓</span><span><strong>Premium retail boxed</strong> packaging with brand graphics</span></li>
          <li><span class="lv-tier-check">✓</span><span>Higher margin tier — better shelf attraction</span></li>
        </ul>
        <p class="lv-tier-hsn">HSN <code>9503</code> · Toys with mechanical sound</p>
      </div>
      <div class="lv-tier lv-tier-normal">
        <span class="lv-tier-marker">⚙ Normal · 5% GST</span>
        <h3>Base Variants</h3>
        <p class="lv-tier-price">₹560 – ₹1,700 wholesale</p>
        <ul>
          <li><span class="lv-tier-check">✓</span><span><strong>Same chassis & wheels</strong> as Plus — ride identical</span></li>
          <li><span class="lv-tier-check">✓</span><span><strong>Lower price point</strong> — best for tier-2/3 markets</span></li>
          <li><span class="lv-tier-check">✓</span><span>Lower GST — customer-friendly tax pass-through</span></li>
        </ul>
        <p class="lv-tier-hsn">HSN <code>8712</code> · Children's bicycles & tricycles</p>
      </div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="lv-section">
  <div class="lv-wrap">
    <div class="lv-head">
      <span class="lv-eyebrow">Voice of Our Dealers</span>
      <h2 class="lv-display">Trusted by <span class="lv-accent">retailers across India.</span></h2>
      <p>Quotes from working dealers across Lucknow, Patna, Delhi NCR and Kanpur.</p>
    </div>
    <div class="lv-testi-grid">
      <div class="lv-testi"><div class="lv-stars">★★★★★</div><p class="lv-quote">I have been distributing Luvron tricycles for two seasons now. The packaging holds up perfectly even after long-haul transport, and the price points let me run healthy retail margins across all my outlets.</p><div class="lv-who"><img src="{$t}aditya-sharma.jpg" alt=""><div><strong>Aditya Sharma</strong><small>Distributor · Lucknow</small></div></div></div>
      <div class="lv-testi"><div class="lv-stars">★★★★★</div><p class="lv-quote">What I value most is the dispatch speed. We confirm an order, the cartons leave Loni in 48 hours. That predictability is rare with kids products in India.</p><div class="lv-who"><img src="{$t}ambuj.jpg" alt=""><div><strong>Ambuj Kumar</strong><small>Dealer · Patna</small></div></div></div>
      <div class="lv-testi"><div class="lv-stars">★★★★★</div><p class="lv-quote">Luvron Sigma Plus is our bestseller in the premium segment. Customers come back for the second child too — the build quality speaks for itself.</p><div class="lv-who"><img src="{$t}dinesh-kanojia.jpg" alt=""><div><strong>Dinesh Kanojia</strong><small>Retailer · Delhi NCR</small></div></div></div>
      <div class="lv-testi"><div class="lv-stars">★★★★★</div><p class="lv-quote">Direct manufacturer pricing, GST-compliant invoices, and a team that picks up the phone. That's all I need from a supplier.</p><div class="lv-who"><img src="{$t}dinesh.jpg" alt=""><div><strong>Dinesh</strong><small>Dealer · Kanpur</small></div></div></div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="lv-cta">
  <div class="lv-wrap">
    <div class="lv-cta-banner">
      <div class="lv-cta-text">
        <h2>Open a dealer account.<br><span class="lv-yellow">Unlock the catalogue.</span></h2>
        <p>Verified GSTIN gets you wholesale pricing within 24 hours. Distributors with PIN-code commitments get exclusive territory rights and an additional 7% off dealer.</p>
      </div>
      <div class="lv-cta-actions">
        <a href="/become-a-dealer/" class="lv-btn lv-btn-light">Apply for Dealer Account →</a>
        <a href="tel:+919212389139" class="lv-btn lv-btn-yellow">📞 +91 9212 389 139</a>
        <span class="lv-cta-tel">OR WHATSAPP THE FOUNDER DIRECTLY</span>
      </div>
    </div>
  </div>
</section>

</div>
<!-- /wp:html -->
HTML;
}

function luvron_become_dealer_pattern_content() {
    return <<<HTML
<!-- wp:html -->
<div class="luvron-pattern">
<style>
.luvron-pattern .lv-hero-slim{padding:80px 0;background:linear-gradient(135deg,#fff7e6,#fff0ec);position:relative;overflow:hidden;border-bottom:1px solid #e5e7eb}
.luvron-pattern .lv-hero-slim::before{content:"";position:absolute;top:-100px;right:-50px;width:400px;height:400px;background:radial-gradient(circle,#ffd9d3,transparent 70%);filter:blur(60px);opacity:.5}
.luvron-pattern .lv-breadcrumb{font-size:13px;color:#475569;margin-bottom:18px;position:relative;z-index:1}
.luvron-pattern .lv-breadcrumb a{color:inherit}
.luvron-pattern .lv-breadcrumb a:hover{color:#ff6b5b}
.luvron-pattern .lv-hero-slim h1{font-family:"Bricolage Grotesque",sans-serif;font-size:clamp(36px,5vw,56px);font-weight:700;line-height:1.05;letter-spacing:-0.025em;margin:0 0 16px;color:#0f172a;position:relative;z-index:1}
.luvron-pattern .lv-hero-slim h1 .lv-accent{color:#ff6b5b}
.luvron-pattern .lv-hero-slim p{font-size:18px;color:#475569;line-height:1.6;max-width:640px;margin:0;position:relative;z-index:1}
.luvron-pattern .lv-dealer-grid{display:grid;grid-template-columns:1fr 1fr;gap:64px;padding:96px 0}
@media(max-width:980px){.luvron-pattern .lv-dealer-grid{grid-template-columns:1fr;gap:32px;padding:64px 0}}
.luvron-pattern .lv-dealer-grid h3{font-family:"Bricolage Grotesque",sans-serif;font-size:28px;font-weight:700;letter-spacing:-0.02em;margin:0 0 18px}
.luvron-pattern .lv-dealer-grid ul{list-style:none;padding:0;margin:0;font-size:15px;line-height:1.8;color:#475569}
.luvron-pattern .lv-dealer-grid li{display:flex;gap:10px;align-items:flex-start;padding:8px 0}
.luvron-pattern .lv-dealer-grid li::before{content:"✓";color:#2f9e44;font-weight:800}
</style>
<section class="lv-hero-slim">
  <div class="lv-wrap">
    <div class="lv-breadcrumb"><a href="/">Home</a> · <span>Become a Dealer</span></div>
    <h1>Become a Luvron <span class="lv-accent">Dealer.</span></h1>
    <p>Strong margins · Pan-India dispatch · GST-compliant invoicing · Territory protection for distributors.</p>
  </div>
</section>
<section class="lv-section">
  <div class="lv-wrap">
    <div class="lv-dealer-grid">
      <div>
        <h3>Why partner with Luvron</h3>
        <ul>
          <li><strong>Direct manufacturer pricing</strong> — best margins in the category</li>
          <li><strong>14 model families</strong> across 44 SKUs — match every price point</li>
          <li><strong>Master-pack ready</strong> — engineered for transit</li>
          <li><strong>5% / 18% GST billing</strong> — clean tax structure</li>
          <li><strong>48-hour dispatch SLA</strong> on confirmed orders</li>
          <li><strong>Direct line to founder</strong> Rajneesh Kumar Pandey</li>
        </ul>
      </div>
      <div>
        <h3>Apply now</h3>
        <p style="margin-bottom:24px;color:#475569;line-height:1.6;">Fill out the form on our contact page. We'll review and approve within 24 hours.</p>
        <p><a href="/contact/" class="lv-btn lv-btn-primary">Open Dealer Application →</a></p>
      </div>
    </div>
  </div>
</section>
</div>
<!-- /wp:html -->
HTML;
}
