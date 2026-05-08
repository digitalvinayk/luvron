<?php
/**
 * Plugin Name: Luvron — Homepage Block Pattern
 * Description: Native Gutenberg block pattern (wp:group / wp:cover / wp:columns /
 *              wp:image / wp:heading / wp:buttons) — fully editable in the WordPress
 *              admin block editor. Visual styling hooks via .lv-* classNames from
 *              luvron-frontend.php's chrome CSS.
 * Version: 4.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-homepage-pattern')) return;

add_action('init', function () {
    if (!function_exists('register_block_pattern_category') || !function_exists('register_block_pattern')) return;
    register_block_pattern_category('luvron', ['label' => 'Luvron']);
    register_block_pattern('luvron/homepage', [
        'title'       => 'Luvron Homepage',
        'description' => 'Modern B2B homepage — hero, stats, featured products, categories, why-Luvron, tiers, testimonials, CTA. All editable native Gutenberg blocks.',
        'categories'  => ['luvron'],
        'content'     => luvron_homepage_pattern_content(),
    ]);
    register_block_pattern('luvron/become-dealer', [
        'title'       => 'Luvron — Become a Dealer',
        'description' => 'Dealer landing page (native blocks).',
        'categories'  => ['luvron'],
        'content'     => luvron_become_dealer_pattern_content(),
    ]);
});

function luvron_homepage_pattern_content() {
    $u = trailingslashit(wp_upload_dir()['baseurl']);
    $p = $u . 'luvron-products/';
    $t = $u . 'luvron-testimonials/';

    return <<<HTML
<!-- wp:group {"align":"full","className":"lv-hero","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull lv-hero">

<!-- wp:columns {"verticalAlignment":"center","className":"lv-hero-columns"} -->
<div class="wp-block-columns are-vertically-aligned-center lv-hero-columns">

<!-- wp:column {"verticalAlignment":"center","width":"55%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:55%">

<!-- wp:paragraph {"className":"lv-eyebrow"} -->
<p class="lv-eyebrow">● Manufacturer Direct · B2B Wholesale</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"className":"lv-h1"} -->
<h1 class="wp-block-heading lv-h1">Tricycles built for joy.<br>Dispatched in <em>48 hours.</em></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"lv-lead"} -->
<p class="lv-lead"><strong>India's most carefully packed kids tricycles</strong> — direct from our Loni, Ghaziabad floor to your retail shelf. 44 SKUs across 11 series, GST-compliant invoicing, dealer-tier pricing.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"lv-hero-actions"} -->
<div class="wp-block-buttons lv-hero-actions">

<!-- wp:button {"className":"lv-btn-primary"} -->
<div class="wp-block-button lv-btn-primary"><a class="wp-block-button__link wp-element-button" href="/become-a-dealer/">Become a Dealer →</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline lv-btn-ghost"} -->
<div class="wp-block-button is-style-outline lv-btn-ghost"><a class="wp-block-button__link wp-element-button" href="/shop/">Browse 44 SKUs</a></div>
<!-- /wp:button -->

</div>
<!-- /wp:buttons -->

<!-- wp:paragraph {"className":"lv-trust-row"} -->
<p class="lv-trust-row"><strong>✓</strong> No middlemen &nbsp; <strong>✓</strong> 48-hour dispatch SLA &nbsp; <strong>✓</strong> GSTIN 09GOCPP5350G1ZQ</p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"45%","className":"lv-hero-visual"} -->
<div class="wp-block-column is-vertically-aligned-center lv-hero-visual" style="flex-basis:45%">

<!-- wp:image {"className":"lv-hero-product"} -->
<figure class="wp-block-image lv-hero-product"><img src="{$p}sigma-plus.jpg" alt="SIGMA Plus tricycle — flagship musical model"/></figure>
<!-- /wp:image -->

</div>
<!-- /wp:column -->

</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"lv-stats","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull lv-stats">

<!-- wp:columns {"className":"lv-stats-grid"} -->
<div class="wp-block-columns lv-stats-grid">

<!-- wp:column {"className":"lv-stat"} -->
<div class="wp-block-column lv-stat">
<!-- wp:heading {"level":3,"className":"lv-stat-num"} --><h3 class="wp-block-heading lv-stat-num">44</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lv-stat-label"} --><p class="lv-stat-label">Active SKUs</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column {"className":"lv-stat"} -->
<div class="wp-block-column lv-stat">
<!-- wp:heading {"level":3,"className":"lv-stat-num"} --><h3 class="wp-block-heading lv-stat-num">11</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lv-stat-label"} --><p class="lv-stat-label">Model Series</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column {"className":"lv-stat"} -->
<div class="wp-block-column lv-stat">
<!-- wp:heading {"level":3,"className":"lv-stat-num"} --><h3 class="wp-block-heading lv-stat-num">48hr</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lv-stat-label"} --><p class="lv-stat-label">Dispatch SLA</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column {"className":"lv-stat"} -->
<div class="wp-block-column lv-stat">
<!-- wp:heading {"level":3,"className":"lv-stat-num"} --><h3 class="wp-block-heading lv-stat-num">50+</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lv-stat-label"} --><p class="lv-stat-label">Active Dealers</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column {"className":"lv-stat"} -->
<div class="wp-block-column lv-stat">
<!-- wp:heading {"level":3,"className":"lv-stat-num"} --><h3 class="wp-block-heading lv-stat-num">4yrs</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lv-stat-label"} --><p class="lv-stat-label">In Production</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"lv-section lv-bestsellers","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull lv-section lv-bestsellers">

<!-- wp:paragraph {"align":"center","className":"lv-eyebrow-section"} -->
<p class="has-text-align-center lv-eyebrow-section">BEST SELLERS</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":2,"className":"lv-section-h2"} -->
<h2 class="wp-block-heading has-text-align-center lv-section-h2">Our most-ordered tricycles.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"lv-section-blurb"} -->
<p class="has-text-align-center lv-section-blurb">Top SKUs by carton volume across all dealer accounts. Login to unlock wholesale pricing.</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"className":"lv-prod-grid"} -->
<div class="wp-block-columns lv-prod-grid">
<!-- wp:column {"className":"lv-prod"} -->
<div class="wp-block-column lv-prod">
<!-- wp:image {"className":"lv-prod-img lv-prod-musical"} --><figure class="wp-block-image lv-prod-img lv-prod-musical"><img src="{$p}sigma-plus.jpg" alt="SIGMA Plus"/></figure><!-- /wp:image -->
<!-- wp:paragraph {"className":"lv-prod-series"} --><p class="lv-prod-series">SIGMA SERIES</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"lv-prod-name"} --><h3 class="wp-block-heading lv-prod-name">SIGMA Plus Tricycle</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lv-prod-meta"} --><p class="lv-prod-meta">12 pcs per master carton · 1×1 inner pack</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lv-prod-cta"} --><p class="lv-prod-cta"><a href="https://wa.me/919212389139?text=Hi%20Luvron%2C%20I%20would%20like%20a%20wholesale%20quote%20for%20%2ASIGMA%20Plus%20Tricycle%2A%20%28SKU%3A%20SIGMA-PLUS%29.%20Please%20share%20dealer%20pricing%20and%20dispatch%20lead%20time." target="_blank" rel="noopener">💬 Request Quote on WhatsApp →</a></p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-prod"} -->
<div class="wp-block-column lv-prod">
<!-- wp:image {"className":"lv-prod-img lv-prod-musical"} --><figure class="wp-block-image lv-prod-img lv-prod-musical"><img src="{$p}aura-plus.jpg" alt="AURA Plus"/></figure><!-- /wp:image -->
<!-- wp:paragraph {"className":"lv-prod-series"} --><p class="lv-prod-series">AURA SERIES</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"lv-prod-name"} --><h3 class="wp-block-heading lv-prod-name">AURA Plus Tricycle</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lv-prod-meta"} --><p class="lv-prod-meta">12 pcs per master carton · 1×1 inner pack</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lv-prod-cta"} --><p class="lv-prod-cta"><a href="https://wa.me/919212389139?text=Hi%20Luvron%2C%20I%20would%20like%20a%20wholesale%20quote%20for%20%2AAURA%20Plus%20Tricycle%2A%20%28SKU%3A%20AURA-PLUS%29.%20Please%20share%20dealer%20pricing%20and%20dispatch%20lead%20time." target="_blank" rel="noopener">💬 Request Quote on WhatsApp →</a></p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-prod"} -->
<div class="wp-block-column lv-prod">
<!-- wp:image {"className":"lv-prod-img lv-prod-musical"} --><figure class="wp-block-image lv-prod-img lv-prod-musical"><img src="{$p}eagle-plus.jpg" alt="EAGLE Plus"/></figure><!-- /wp:image -->
<!-- wp:paragraph {"className":"lv-prod-series"} --><p class="lv-prod-series">EAGLE SERIES</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"lv-prod-name"} --><h3 class="wp-block-heading lv-prod-name">EAGLE Plus Tricycle</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lv-prod-meta"} --><p class="lv-prod-meta">12 pcs per master carton · 1×1 inner pack</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lv-prod-cta"} --><p class="lv-prod-cta"><a href="https://wa.me/919212389139?text=Hi%20Luvron%2C%20I%20would%20like%20a%20wholesale%20quote%20for%20%2AEAGLE%20Plus%20Tricycle%2A%20%28SKU%3A%20EAGLE-PLUS%29.%20Please%20share%20dealer%20pricing%20and%20dispatch%20lead%20time." target="_blank" rel="noopener">💬 Request Quote on WhatsApp →</a></p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-prod"} -->
<div class="wp-block-column lv-prod">
<!-- wp:image {"className":"lv-prod-img lv-prod-musical"} --><figure class="wp-block-image lv-prod-img lv-prod-musical"><img src="{$p}alex-plus.jpg" alt="ALEX Plus"/></figure><!-- /wp:image -->
<!-- wp:paragraph {"className":"lv-prod-series"} --><p class="lv-prod-series">ALEX SERIES</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"lv-prod-name"} --><h3 class="wp-block-heading lv-prod-name">ALEX Plus Tricycle</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lv-prod-meta"} --><p class="lv-prod-meta">12 pcs per master carton · 1×1 inner pack</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lv-prod-cta"} --><p class="lv-prod-cta"><a href="https://wa.me/919212389139?text=Hi%20Luvron%2C%20I%20would%20like%20a%20wholesale%20quote%20for%20%2AALEX%20Plus%20Tricycle%2A%20%28SKU%3A%20ALEX-PLUS%29.%20Please%20share%20dealer%20pricing%20and%20dispatch%20lead%20time." target="_blank" rel="noopener">💬 Request Quote on WhatsApp →</a></p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

<!-- wp:columns {"className":"lv-prod-grid"} -->
<div class="wp-block-columns lv-prod-grid">
<!-- wp:column {"className":"lv-prod"} -->
<div class="wp-block-column lv-prod">
<!-- wp:image {"className":"lv-prod-img lv-prod-normal"} --><figure class="wp-block-image lv-prod-img lv-prod-normal"><img src="{$p}rambo-333.jpg" alt="RAMBO 333"/></figure><!-- /wp:image -->
<!-- wp:paragraph {"className":"lv-prod-series"} --><p class="lv-prod-series">RAMBO SERIES</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"lv-prod-name"} --><h3 class="wp-block-heading lv-prod-name">RAMBO 333 Tricycle</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lv-prod-meta"} --><p class="lv-prod-meta">12 pcs per master carton · 3×4 inner pack</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lv-prod-cta"} --><p class="lv-prod-cta"><a href="https://wa.me/919212389139?text=Hi%20Luvron%2C%20I%20would%20like%20a%20wholesale%20quote%20for%20%2ARAMBO%20333%20Tricycle%2A%20%28SKU%3A%20RAMBO-333%29.%20Please%20share%20dealer%20pricing%20and%20dispatch%20lead%20time." target="_blank" rel="noopener">💬 Request Quote on WhatsApp →</a></p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-prod"} -->
<div class="wp-block-column lv-prod">
<!-- wp:image {"className":"lv-prod-img lv-prod-normal"} --><figure class="wp-block-image lv-prod-img lv-prod-normal"><img src="{$p}hulk-pro.jpg" alt="HULK Pro"/></figure><!-- /wp:image -->
<!-- wp:paragraph {"className":"lv-prod-series"} --><p class="lv-prod-series">HULK SERIES</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"lv-prod-name"} --><h3 class="wp-block-heading lv-prod-name">HULK Pro Tricycle</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lv-prod-meta"} --><p class="lv-prod-meta">15 pcs per master carton · 1×1 inner pack</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lv-prod-cta"} --><p class="lv-prod-cta"><a href="https://wa.me/919212389139?text=Hi%20Luvron%2C%20I%20would%20like%20a%20wholesale%20quote%20for%20%2AHULK%20Pro%20Tricycle%2A%20%28SKU%3A%20HULK-PRO%29.%20Please%20share%20dealer%20pricing%20and%20dispatch%20lead%20time." target="_blank" rel="noopener">💬 Request Quote on WhatsApp →</a></p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-prod"} -->
<div class="wp-block-column lv-prod">
<!-- wp:image {"className":"lv-prod-img lv-prod-musical"} --><figure class="wp-block-image lv-prod-img lv-prod-musical"><img src="{$p}charlie-r1-plus.jpg" alt="CHARLIE R1 Plus"/></figure><!-- /wp:image -->
<!-- wp:paragraph {"className":"lv-prod-series"} --><p class="lv-prod-series">CHARLIE SERIES</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"lv-prod-name"} --><h3 class="wp-block-heading lv-prod-name">CHARLIE R1 Plus</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lv-prod-meta"} --><p class="lv-prod-meta">15 pcs per master carton · 1×1 inner pack</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lv-prod-cta"} --><p class="lv-prod-cta"><a href="https://wa.me/919212389139?text=Hi%20Luvron%2C%20I%20would%20like%20a%20wholesale%20quote%20for%20%2ACHARLIE%20R1%20Plus%2A%20%28SKU%3A%20CHARLIE-R1-PLUS%29.%20Please%20share%20dealer%20pricing%20and%20dispatch%20lead%20time." target="_blank" rel="noopener">💬 Request Quote on WhatsApp →</a></p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-prod"} -->
<div class="wp-block-column lv-prod">
<!-- wp:image {"className":"lv-prod-img lv-prod-normal"} --><figure class="wp-block-image lv-prod-img lv-prod-normal"><img src="{$p}emma-r1.jpg" alt="EMMA R1"/></figure><!-- /wp:image -->
<!-- wp:paragraph {"className":"lv-prod-series"} --><p class="lv-prod-series">EMMA SERIES</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"lv-prod-name"} --><h3 class="wp-block-heading lv-prod-name">EMMA R1 Tricycle</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lv-prod-meta"} --><p class="lv-prod-meta">15 pcs per master carton · 1×1 inner pack</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lv-prod-cta"} --><p class="lv-prod-cta"><a href="https://wa.me/919212389139?text=Hi%20Luvron%2C%20I%20would%20like%20a%20wholesale%20quote%20for%20%2AEMMA%20R1%20Tricycle%2A%20%28SKU%3A%20EMMA-R1%29.%20Please%20share%20dealer%20pricing%20and%20dispatch%20lead%20time." target="_blank" rel="noopener">💬 Request Quote on WhatsApp →</a></p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"lv-section lv-bg-cream lv-categories","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull lv-section lv-bg-cream lv-categories">

<!-- wp:paragraph {"align":"center","className":"lv-eyebrow-section"} -->
<p class="has-text-align-center lv-eyebrow-section">OUR COLLECTIONS</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":2,"className":"lv-section-h2"} -->
<h2 class="wp-block-heading has-text-align-center lv-section-h2">Eleven series. Every shelf covered.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"lv-section-blurb"} -->
<p class="has-text-align-center lv-section-blurb">From premium SIGMA Plus to value-tier RAMBO. Every series ships with master cartons and GST-compliant invoicing.</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"className":"lv-cat-grid"} -->
<div class="wp-block-columns lv-cat-grid">
<!-- wp:column {"className":"lv-cat lv-cat-coral"} -->
<div class="wp-block-column lv-cat lv-cat-coral">
<!-- wp:image --><figure class="wp-block-image"><img src="{$p}sigma-plus.jpg" alt="SIGMA"/></figure><!-- /wp:image -->
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading"><a href="/product-category/sigma-series/">SIGMA</a></h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Premium · 4 SKUs</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-cat lv-cat-sky"} -->
<div class="wp-block-column lv-cat lv-cat-sky">
<!-- wp:image --><figure class="wp-block-image"><img src="{$p}aura-plus.jpg" alt="AURA"/></figure><!-- /wp:image -->
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading"><a href="/product-category/aura-series/">AURA</a></h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Mid-tier · 4 SKUs</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-cat lv-cat-mint"} -->
<div class="wp-block-column lv-cat lv-cat-mint">
<!-- wp:image --><figure class="wp-block-image"><img src="{$p}eagle-plus.jpg" alt="EAGLE"/></figure><!-- /wp:image -->
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading"><a href="/product-category/eagle-series/">EAGLE</a></h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Sporty · 4 SKUs</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-cat lv-cat-cream"} -->
<div class="wp-block-column lv-cat lv-cat-cream">
<!-- wp:image --><figure class="wp-block-image"><img src="{$p}alex-plus.jpg" alt="ALEX"/></figure><!-- /wp:image -->
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading"><a href="/product-category/alex-series/">ALEX</a></h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Bestseller · 4 SKUs</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

<!-- wp:columns {"className":"lv-cat-grid"} -->
<div class="wp-block-columns lv-cat-grid">
<!-- wp:column {"className":"lv-cat lv-cat-lavender"} -->
<div class="wp-block-column lv-cat lv-cat-lavender">
<!-- wp:image --><figure class="wp-block-image"><img src="{$p}ecotech-plus.jpg" alt="ECOTECH"/></figure><!-- /wp:image -->
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading"><a href="/product-category/ecotech-series/">ECOTECH</a></h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Eco-friendly · 4 SKUs</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-cat lv-cat-coral"} -->
<div class="wp-block-column lv-cat lv-cat-coral">
<!-- wp:image --><figure class="wp-block-image"><img src="{$p}rambo-333-plus.jpg" alt="RAMBO"/></figure><!-- /wp:image -->
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading"><a href="/product-category/rambo-series/">RAMBO</a></h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Value · 8 SKUs</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-cat lv-cat-sky"} -->
<div class="wp-block-column lv-cat lv-cat-sky">
<!-- wp:image --><figure class="wp-block-image"><img src="{$p}hulk-pro-plus.jpg" alt="HULK"/></figure><!-- /wp:image -->
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading"><a href="/product-category/hulk-series/">HULK</a></h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Sturdy · 4 SKUs</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-cat lv-cat-mint"} -->
<div class="wp-block-column lv-cat lv-cat-mint">
<!-- wp:image --><figure class="wp-block-image"><img src="{$p}charlie-r1-plus.jpg" alt="CHARLIE"/></figure><!-- /wp:image -->
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading"><a href="/product-category/charlie-series/">CHARLIE</a></h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Compact · 4 SKUs</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"lv-section lv-why","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull lv-section lv-why">

<!-- wp:paragraph {"align":"center","className":"lv-eyebrow-section"} -->
<p class="has-text-align-center lv-eyebrow-section">WHY LUVRON</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":2,"className":"lv-section-h2"} -->
<h2 class="wp-block-heading has-text-align-center lv-section-h2">Built for dealers who measure margins.</h2>
<!-- /wp:heading -->

<!-- wp:columns {"className":"lv-feat-grid"} -->
<div class="wp-block-columns lv-feat-grid">
<!-- wp:column {"className":"lv-feat lv-feat-coral"} -->
<div class="wp-block-column lv-feat lv-feat-coral">
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Manufacturer Direct</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>You buy from our workshop, not a distributor's distributor. The price you see is the price we make.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-feat lv-feat-sky"} -->
<div class="wp-block-column lv-feat lv-feat-sky">
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">48-Hour Dispatch</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Confirmed orders leave Loni within 48 working hours. Out-of-stock SKUs flagged at proforma stage.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-feat lv-feat-mint"} -->
<div class="wp-block-column lv-feat lv-feat-mint">
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Carton-Tested Packing</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Inner boxes engineered for stack and transit. Damage rate held below 0.5% on top three corridors.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

<!-- wp:columns {"className":"lv-feat-grid"} -->
<div class="wp-block-columns lv-feat-grid">
<!-- wp:column {"className":"lv-feat lv-feat-cream"} -->
<div class="wp-block-column lv-feat lv-feat-cream">
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">GST-Compliant</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Every B2B tax invoice carries IRN/QR from the IRP. CGST/SGST split for UP, IGST elsewhere.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-feat lv-feat-lavender"} -->
<div class="wp-block-column lv-feat lv-feat-lavender">
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Territory Protection</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Distributors get exclusive PIN-code zones. We don't appoint competing dealers in your area.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-feat lv-feat-support"} -->
<div class="wp-block-column lv-feat lv-feat-support">
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Pan-India Sales Network</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Regional sales managers across India respond to dealer queries within 4 working hours. Order tracking, payments, and dispute resolution under a single point of accountability.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"lv-section lv-bg-canvas lv-catalog","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull lv-section lv-bg-canvas lv-catalog">

<!-- wp:columns {"verticalAlignment":"center","className":"lv-catalog-cols"} -->
<div class="wp-block-columns are-vertically-aligned-center lv-catalog-cols">

<!-- wp:column {"verticalAlignment":"center","width":"55%","className":"lv-catalog-info"} -->
<div class="wp-block-column is-vertically-aligned-center lv-catalog-info" style="flex-basis:55%">
<!-- wp:paragraph {"className":"lv-eyebrow-section"} --><p class="lv-eyebrow-section">2026 PRODUCT CATALOGUE</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":"lv-section-h2"} --><h2 class="wp-block-heading lv-section-h2">Download the full catalogue.</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>44 SKUs across 11 series with full specifications, pack details, master carton sizes, GST rates, HSN codes, and pricing tiers. Updated 01 April 2026.</p><!-- /wp:paragraph -->
<!-- wp:buttons {"className":"lv-catalog-actions"} -->
<div class="wp-block-buttons lv-catalog-actions">
<!-- wp:button {"className":"lv-btn-primary"} --><div class="wp-block-button lv-btn-primary"><a class="wp-block-button__link wp-element-button" href="/wp-content/uploads/luvron-catalogue-feb-26.pdf" download>Download PDF (10 MB) ↓</a></div><!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline lv-btn-ghost"} --><div class="wp-block-button is-style-outline lv-btn-ghost"><a class="wp-block-button__link wp-element-button" href="/shop/">Browse Online Catalogue</a></div><!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"45%","className":"lv-catalog-card"} -->
<div class="wp-block-column is-vertically-aligned-center lv-catalog-card" style="flex-basis:45%">
<!-- wp:group {"className":"lv-catalog-preview","layout":{"type":"constrained"}} -->
<div class="wp-block-group lv-catalog-preview">
<!-- wp:paragraph {"className":"lv-catalog-tag"} --><p class="lv-catalog-tag">PDF · 10 MB · 7 PAGES</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"lv-catalog-num"} --><h3 class="wp-block-heading lv-catalog-num">44 SKUs</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lv-catalog-meta"} --><p class="lv-catalog-meta">Across <strong>11 series</strong> · Master cartons of <strong>12 to 24</strong> · <strong>5%</strong> &amp; <strong>18%</strong> GST</p><!-- /wp:paragraph -->
<!-- wp:columns {"className":"lv-catalog-thumbs"} -->
<div class="wp-block-columns lv-catalog-thumbs">
<!-- wp:column --><div class="wp-block-column"><!-- wp:image --><figure class="wp-block-image"><img src="{$p}sigma-plus.jpg" alt=""/></figure><!-- /wp:image --></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- wp:image --><figure class="wp-block-image"><img src="{$p}aura-plus.jpg" alt=""/></figure><!-- /wp:image --></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- wp:image --><figure class="wp-block-image"><img src="{$p}eagle-plus.jpg" alt=""/></figure><!-- /wp:image --></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- wp:image --><figure class="wp-block-image"><img src="{$p}rambo-333.jpg" alt=""/></figure><!-- /wp:image --></div><!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:column -->

</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"lv-section lv-bg-cream lv-tiers","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull lv-section lv-bg-cream lv-tiers">

<!-- wp:paragraph {"align":"center","className":"lv-eyebrow-section"} -->
<p class="has-text-align-center lv-eyebrow-section">TWO PRICING TIERS</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":2,"className":"lv-section-h2"} -->
<h2 class="wp-block-heading has-text-align-center lv-section-h2">Same chassis. Different soundtrack.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"lv-section-blurb"} -->
<p class="has-text-align-center lv-section-blurb">Every model comes in two tiers — Musical (with horn, lights, music) at 18% GST, or Normal (clean ride, no electronics) at 5% GST.</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"className":"lv-tier-grid"} -->
<div class="wp-block-columns lv-tier-grid">
<!-- wp:column {"className":"lv-tier lv-tier-musical"} -->
<div class="wp-block-column lv-tier lv-tier-musical">
<!-- wp:paragraph {"className":"lv-tier-marker"} --><p class="lv-tier-marker"><span>♪ Musical · 18% GST</span></p><!-- /wp:paragraph -->
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Plus Variants</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lv-tier-price"} --><p class="lv-tier-price"><strong>₹680 – ₹1,760</strong> <span>wholesale, ex-Loni</span></p><!-- /wp:paragraph -->
<!-- wp:list --><ul>
<li><strong>Horn, lights &amp; music</strong> on the handlebar — kids' top decision driver</li>
<li><strong>Premium retail boxed</strong> packaging with brand graphics</li>
<li>Higher margin tier — better shelf attraction</li>
</ul><!-- /wp:list -->
<!-- wp:group {"className":"lv-tier-foot","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group lv-tier-foot">
<!-- wp:paragraph {"className":"lv-tier-hsn"} --><p class="lv-tier-hsn">HSN <code>9503</code> · Toys with mechanical sound</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lv-tier-cta"} --><p class="lv-tier-cta"><a href="/shop/?tier=musical">View 22 Musical SKUs →</a></p><!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-tier lv-tier-normal"} -->
<div class="wp-block-column lv-tier lv-tier-normal">
<!-- wp:paragraph {"className":"lv-tier-marker"} --><p class="lv-tier-marker"><span>⚙ Normal · 5% GST</span></p><!-- /wp:paragraph -->
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Base Variants</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lv-tier-price"} --><p class="lv-tier-price"><strong>₹560 – ₹1,700</strong> <span>wholesale, ex-Loni</span></p><!-- /wp:paragraph -->
<!-- wp:list --><ul>
<li><strong>Same chassis &amp; wheels</strong> as Plus — ride identical</li>
<li><strong>Lower price point</strong> — best for tier-2/3 markets</li>
<li>Lower GST — customer-friendly tax pass-through</li>
</ul><!-- /wp:list -->
<!-- wp:group {"className":"lv-tier-foot","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group lv-tier-foot">
<!-- wp:paragraph {"className":"lv-tier-hsn"} --><p class="lv-tier-hsn">HSN <code>8712</code> · Children's bicycles &amp; tricycles</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lv-tier-cta"} --><p class="lv-tier-cta"><a href="/shop/?tier=normal">View 22 Normal SKUs →</a></p><!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"lv-section lv-testimonials","layout":{"type":"constrained","contentSize":"1280px"}} -->
<div class="wp-block-group alignfull lv-section lv-testimonials">

<!-- wp:paragraph {"align":"center","className":"lv-eyebrow-section"} -->
<p class="has-text-align-center lv-eyebrow-section">VOICE OF OUR DEALERS</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":2,"className":"lv-section-h2"} -->
<h2 class="wp-block-heading has-text-align-center lv-section-h2">Trusted by retailers across India.</h2>
<!-- /wp:heading -->

<!-- wp:columns {"className":"lv-testi-grid"} -->
<div class="wp-block-columns lv-testi-grid">
<!-- wp:column {"className":"lv-testi"} -->
<div class="wp-block-column lv-testi">
<!-- wp:paragraph {"className":"lv-stars"} --><p class="lv-stars">★★★★★</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lv-quote"} --><p class="lv-quote">I have been distributing Luvron tricycles for two seasons now. The packaging holds up perfectly even after long-haul transport, and the price points let me run healthy retail margins across all my outlets.</p><!-- /wp:paragraph -->
<!-- wp:image {"className":"lv-who-img"} --><figure class="wp-block-image lv-who-img"><img src="{$t}aditya-sharma.jpg" alt="Aditya Sharma"/></figure><!-- /wp:image -->
<!-- wp:paragraph {"className":"lv-who-name"} --><p class="lv-who-name"><strong>Aditya Sharma</strong><br><small>Distributor · Lucknow</small></p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-testi"} -->
<div class="wp-block-column lv-testi">
<!-- wp:paragraph {"className":"lv-stars"} --><p class="lv-stars">★★★★★</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lv-quote"} --><p class="lv-quote">What I value most is the dispatch speed. We confirm an order, the cartons leave Loni in 48 hours. That predictability is rare with kids products in India.</p><!-- /wp:paragraph -->
<!-- wp:image {"className":"lv-who-img"} --><figure class="wp-block-image lv-who-img"><img src="{$t}ambuj.jpg" alt="Ambuj Kumar"/></figure><!-- /wp:image -->
<!-- wp:paragraph {"className":"lv-who-name"} --><p class="lv-who-name"><strong>Ambuj Kumar</strong><br><small>Dealer · Patna</small></p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

<!-- wp:columns {"className":"lv-testi-grid"} -->
<div class="wp-block-columns lv-testi-grid">
<!-- wp:column {"className":"lv-testi"} -->
<div class="wp-block-column lv-testi">
<!-- wp:paragraph {"className":"lv-stars"} --><p class="lv-stars">★★★★★</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lv-quote"} --><p class="lv-quote">Luvron Sigma Plus is our bestseller in the premium segment. Customers come back for the second child too — the build quality speaks for itself.</p><!-- /wp:paragraph -->
<!-- wp:image {"className":"lv-who-img"} --><figure class="wp-block-image lv-who-img"><img src="{$t}dinesh-kanojia.jpg" alt="Dinesh Kanojia"/></figure><!-- /wp:image -->
<!-- wp:paragraph {"className":"lv-who-name"} --><p class="lv-who-name"><strong>Dinesh Kanojia</strong><br><small>Retailer · Delhi NCR</small></p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"lv-testi"} -->
<div class="wp-block-column lv-testi">
<!-- wp:paragraph {"className":"lv-stars"} --><p class="lv-stars">★★★★★</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lv-quote"} --><p class="lv-quote">Direct manufacturer pricing, GST-compliant invoices, and a team that picks up the phone. That's all I need from a supplier.</p><!-- /wp:paragraph -->
<!-- wp:image {"className":"lv-who-img"} --><figure class="wp-block-image lv-who-img"><img src="{$t}dinesh.jpg" alt="Dinesh"/></figure><!-- /wp:image -->
<!-- wp:paragraph {"className":"lv-who-name"} --><p class="lv-who-name"><strong>Dinesh</strong><br><small>Dealer · Kanpur</small></p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->

<!-- wp:cover {"customGradient":"linear-gradient(135deg,#ff6b5b 0%,#e84a3f 50%,#c93a30 100%)","minHeight":380,"align":"full","className":"lv-cta-cover"} -->
<div class="wp-block-cover alignfull lv-cta-cover" style="min-height:380px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim has-background-gradient" style="background:linear-gradient(135deg,#ff6b5b 0%,#e84a3f 50%,#c93a30 100%)"></span><div class="wp-block-cover__inner-container">

<!-- wp:heading {"textAlign":"center","level":2,"className":"lv-cta-title","style":{"color":{"text":"#ffffff"}}} -->
<h2 class="wp-block-heading has-text-align-center lv-cta-title has-text-color" style="color:#ffffff">Open a dealer account. Unlock the catalogue.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"lv-cta-blurb","style":{"color":{"text":"#ffffff"}}} -->
<p class="has-text-align-center lv-cta-blurb has-text-color" style="color:#ffffff">Verified GSTIN gets you wholesale pricing within 24 hours. Distributors with PIN-code commitments get exclusive territory rights.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"className":"lv-cta-buttons"} -->
<div class="wp-block-buttons lv-cta-buttons">
<!-- wp:button {"className":"lv-btn-light"} -->
<div class="wp-block-button lv-btn-light"><a class="wp-block-button__link wp-element-button" href="/become-a-dealer/">Apply for Dealer Account →</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"lv-btn-yellow"} -->
<div class="wp-block-button lv-btn-yellow"><a class="wp-block-button__link wp-element-button" href="tel:+919212389139">📞 +91 9212 389 139</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

</div></div>
<!-- /wp:cover -->
HTML;
}

function luvron_become_dealer_pattern_content() {
    return <<<HTML
<!-- wp:cover {"customOverlayColor":"#fff7e6","minHeight":280,"align":"full","className":"lv-hero-slim"} -->
<div class="wp-block-cover alignfull lv-hero-slim" style="min-height:280px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim" style="background-color:#fff7e6"></span><div class="wp-block-cover__inner-container">
<!-- wp:paragraph {"align":"center","className":"lv-breadcrumb"} --><p class="has-text-align-center lv-breadcrumb"><a href="/">Home</a> · Become a Dealer</p><!-- /wp:paragraph -->
<!-- wp:heading {"textAlign":"center","level":1,"className":"lv-hero-slim-h1"} --><h1 class="wp-block-heading has-text-align-center lv-hero-slim-h1">Become a Luvron Dealer.</h1><!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","className":"lv-hero-slim-p"} --><p class="has-text-align-center lv-hero-slim-p">Strong margins · Pan-India dispatch · GST-compliant invoicing · Territory protection.</p><!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->

<!-- wp:group {"align":"full","className":"lv-section","layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group alignfull lv-section">
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Why partner with Luvron</h3><!-- /wp:heading -->
<!-- wp:list --><ul>
<li><strong>Direct manufacturer pricing</strong> — best margins in the category</li>
<li><strong>14 model families</strong> across 44 SKUs</li>
<li><strong>Master-pack ready</strong> — engineered for transit</li>
<li><strong>5% / 18% GST billing</strong> — clean tax structure</li>
<li><strong>48-hour dispatch SLA</strong> on confirmed orders</li>
<li><strong>Direct line to founder</strong> Rajneesh Kumar Pandey</li>
</ul><!-- /wp:list -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Apply now</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Fill out the form on our contact page. We'll review and approve within 24 hours.</p><!-- /wp:paragraph -->
<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"className":"lv-btn-primary"} --><div class="wp-block-button lv-btn-primary"><a class="wp-block-button__link wp-element-button" href="/contact/">Open Dealer Application →</a></div><!-- /wp:button --></div><!-- /wp:buttons -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->
HTML;
}
