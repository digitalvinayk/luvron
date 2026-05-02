<?php
/**
 * Plugin Name: Luvron — Homepage Block Pattern
 * Description: Registers a Gutenberg block pattern that scaffolds the §7 homepage wireframe
 *              (hero, trust strip, categories, why-luvron, dealer CTA, catalogue download).
 *              Insert it from: Pages → Edit homepage → Patterns → Luvron → "Luvron Homepage".
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-homepage-pattern')) return;

add_action('init', function () {
    if (!function_exists('register_block_pattern_category') || !function_exists('register_block_pattern')) return;

    register_block_pattern_category('luvron', ['label' => 'Luvron']);

    register_block_pattern('luvron/homepage', [
        'title'       => 'Luvron Homepage',
        'description' => 'Full B2B homepage: hero, trust strip, categories, why-Luvron, dealer CTA, catalogue download.',
        'categories'  => ['luvron'],
        'content'     => luvron_homepage_pattern_content(),
    ]);

    register_block_pattern('luvron/become-dealer', [
        'title'       => 'Luvron — Become a Dealer Page',
        'description' => 'Dealer landing page with margin pitch, registration CTA, and FAQ.',
        'categories'  => ['luvron'],
        'content'     => luvron_become_dealer_pattern_content(),
    ]);
});

function luvron_homepage_pattern_content() {
    return <<<'HTML'
<!-- wp:cover {"customOverlayColor":"#1a1a1a","dimRatio":40,"minHeight":520,"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"24px","right":"24px"}}}} -->
<div class="wp-block-cover alignfull" style="padding-top:80px;padding-right:24px;padding-bottom:80px;padding-left:24px;min-height:520px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-40 has-background-dim" style="background-color:#1a1a1a"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"48px","lineHeight":"1.1","fontWeight":"800"},"color":{"text":"#ffffff"}}} -->
<h1 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff;font-size:48px;font-weight:800;line-height:1.1">India's Trusted Tricycle Manufacturer</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"20px"},"color":{"text":"#f0f0f0"}}} -->
<p class="has-text-align-center has-text-color" style="color:#f0f0f0;font-size:20px">For Dealers, Distributors and Retailers across India · 40+ models · GST-compliant invoicing</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"32px"}}}} -->
<div class="wp-block-buttons" style="margin-top:32px"><!-- wp:button {"backgroundColor":"vivid-orange","style":{"typography":{"fontSize":"16px","fontWeight":"600"},"spacing":{"padding":{"top":"14px","bottom":"14px","left":"32px","right":"32px"}}}} -->
<div class="wp-block-button has-custom-font-size" style="font-size:16px;font-weight:600"><a class="wp-block-button__link has-vivid-orange-background-color has-background wp-element-button" href="/become-a-dealer/" style="padding-top:14px;padding-right:32px;padding-bottom:14px;padding-left:32px">Become a Dealer</a></div>
<!-- /wp:button -->
<!-- wp:button {"textColor":"white","style":{"border":{"width":"2px","color":"#ffffff"},"typography":{"fontSize":"16px","fontWeight":"600"},"spacing":{"padding":{"top":"12px","bottom":"12px","left":"32px","right":"32px"}}},"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline has-custom-font-size" style="font-size:16px;font-weight:600"><a class="wp-block-button__link has-white-color has-text-color has-border-color wp-element-button" href="/catalogue/" style="border-color:#ffffff;border-width:2px;padding-top:12px;padding-right:32px;padding-bottom:12px;padding-left:32px">View Catalogue</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
</div></div>
<!-- /wp:cover -->

<!-- wp:group {"align":"full","style":{"color":{"background":"#fff8e1"},"spacing":{"padding":{"top":"24px","bottom":"24px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background" style="background-color:#fff8e1;padding-top:24px;padding-bottom:24px">
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide">
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><strong>✓ 14+ Model Families</strong></p><!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><strong>✓ Master-Pack Ready</strong></p><!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><strong>✓ 5% / 18% GST Billing</strong></p><!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><strong>✓ Pan-India Dispatch</strong></p><!-- /wp:paragraph --></div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"64px","bottom":"64px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:64px;padding-bottom:64px">
<!-- wp:heading {"textAlign":"center","level":2,"style":{"typography":{"fontSize":"32px","fontWeight":"700"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:32px;font-weight:700">Explore Our Models</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#666666"}}} -->
<p class="has-text-align-center has-text-color" style="color:#666666">14 series · 40+ SKUs · From premium PLUS to value RAMBO</p>
<!-- /wp:paragraph -->
<!-- wp:columns {"align":"wide","style":{"spacing":{"margin":{"top":"32px"},"blockGap":{"top":"16px","left":"16px"}}}} -->
<div class="wp-block-columns alignwide" style="margin-top:32px">
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"color":{"background":"#f5f5f5"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"16px","right":"16px"}},"border":{"radius":"8px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f5f5f5;padding:24px 16px">
<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"18px"}}} --><h3 class="wp-block-heading has-text-align-center" style="font-size:18px"><a href="/product-category/sigma-series/">SIGMA</a></h3><!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size">Premium with canopy</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"color":{"background":"#f5f5f5"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"16px","right":"16px"}},"border":{"radius":"8px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f5f5f5;padding:24px 16px">
<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"18px"}}} --><h3 class="wp-block-heading has-text-align-center" style="font-size:18px"><a href="/product-category/aura-series/">AURA</a></h3><!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size">Mid-range favourite</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"color":{"background":"#f5f5f5"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"16px","right":"16px"}},"border":{"radius":"8px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f5f5f5;padding:24px 16px">
<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"18px"}}} --><h3 class="wp-block-heading has-text-align-center" style="font-size:18px"><a href="/product-category/eagle-series/">EAGLE</a></h3><!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size">Sporty design</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"color":{"background":"#f5f5f5"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"16px","right":"16px"}},"border":{"radius":"8px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f5f5f5;padding:24px 16px">
<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"18px"}}} --><h3 class="wp-block-heading has-text-align-center" style="font-size:18px"><a href="/product-category/alex-series/">ALEX</a></h3><!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size">Bestselling base</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group --></div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
<!-- wp:columns {"align":"wide","style":{"spacing":{"margin":{"top":"16px"},"blockGap":{"top":"16px","left":"16px"}}}} -->
<div class="wp-block-columns alignwide" style="margin-top:16px">
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"color":{"background":"#f5f5f5"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"16px","right":"16px"}},"border":{"radius":"8px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f5f5f5;padding:24px 16px">
<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"18px"}}} --><h3 class="wp-block-heading has-text-align-center" style="font-size:18px"><a href="/product-category/ecotech-series/">ECOTECH</a></h3><!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size">Eco-friendly build</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"color":{"background":"#f5f5f5"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"16px","right":"16px"}},"border":{"radius":"8px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f5f5f5;padding:24px 16px">
<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"18px"}}} --><h3 class="wp-block-heading has-text-align-center" style="font-size:18px"><a href="/product-category/rambo-series/">RAMBO</a></h3><!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size">Volume value pack</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"color":{"background":"#f5f5f5"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"16px","right":"16px"}},"border":{"radius":"8px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f5f5f5;padding:24px 16px">
<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"18px"}}} --><h3 class="wp-block-heading has-text-align-center" style="font-size:18px"><a href="/product-category/hulk-series/">HULK</a></h3><!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size">Sturdy chassis</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"color":{"background":"#f5f5f5"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"16px","right":"16px"}},"border":{"radius":"8px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;background-color:#f5f5f5;padding:24px 16px">
<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"18px"}}} --><h3 class="wp-block-heading has-text-align-center" style="font-size:18px"><a href="/product-category/charlie-series/">CHARLIE</a></h3><!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size">Compact starter</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group --></div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"color":{"background":"#fafafa"},"spacing":{"padding":{"top":"64px","bottom":"64px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background" style="background-color:#fafafa;padding-top:64px;padding-bottom:64px">
<!-- wp:heading {"textAlign":"center","level":2,"style":{"typography":{"fontSize":"32px"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:32px">Why Luvron</h2>
<!-- /wp:heading -->
<!-- wp:columns {"align":"wide","style":{"spacing":{"margin":{"top":"40px"}}}} -->
<div class="wp-block-columns alignwide" style="margin-top:40px">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"textAlign":"center","level":3} --><h3 class="wp-block-heading has-text-align-center">💰 Bulk Margins</h3><!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Direct from manufacturer pricing — no middleman markup. Tiered rates for distributors and high-volume dealers.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"textAlign":"center","level":3} --><h3 class="wp-block-heading has-text-align-center">🚚 Fast Supply</h3><!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">48-hour dispatch on confirmed orders from our Loni, Ghaziabad facility. Pan-India delivery via leading transporters.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"textAlign":"center","level":3} --><h3 class="wp-block-heading has-text-align-center">📦 Strong Packaging</h3><!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Master cartons engineered for stack and transit. Branded retail boxes for premium SKUs. Damage rate under 0.5%.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:cover {"customOverlayColor":"#ff8c00","dimRatio":0,"align":"full","style":{"spacing":{"padding":{"top":"56px","bottom":"56px","left":"24px","right":"24px"}}}} -->
<div class="wp-block-cover alignfull" style="padding-top:56px;padding-right:24px;padding-bottom:56px;padding-left:24px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim" style="background-color:#ff8c00"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":2,"style":{"color":{"text":"#ffffff"},"typography":{"fontSize":"28px"}}} -->
<h2 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff;font-size:28px">Already a Luvron dealer? Login to order.</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffff"},"typography":{"fontSize":"18px"}}} -->
<p class="has-text-align-center has-text-color" style="color:#ffffff;font-size:18px">New here? Apply for a dealer account in 2 minutes.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"24px"}}}} -->
<div class="wp-block-buttons" style="margin-top:24px">
<!-- wp:button {"backgroundColor":"black","textColor":"white"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-white-color has-black-background-color has-text-color has-background wp-element-button" href="/wp-login.php">Dealer Login</a></div>
<!-- /wp:button -->
<!-- wp:button {"textColor":"white","style":{"border":{"width":"2px","color":"#ffffff"}},"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-text-color has-border-color wp-element-button" href="/become-a-dealer/" style="border-color:#ffffff;border-width:2px">Apply for Dealer Account</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div></div>
<!-- /wp:cover -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"48px","bottom":"48px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:48px;padding-bottom:48px">
<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">Download the 2026 Catalogue</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#666666"}}} -->
<p class="has-text-align-center has-text-color" style="color:#666666">Full product gallery with specs, packaging, and pricing tiers.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"24px"}}}} -->
<div class="wp-block-buttons" style="margin-top:24px">
<!-- wp:button {"backgroundColor":"black","textColor":"white"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-white-color has-black-background-color has-text-color has-background wp-element-button" href="/wp-content/uploads/luvron-catalogue-feb-26.pdf" download>Download Catalogue (PDF)</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
HTML;
}

function luvron_become_dealer_pattern_content() {
    return <<<'HTML'
<!-- wp:cover {"customOverlayColor":"#1a1a1a","dimRatio":50,"align":"full","style":{"spacing":{"padding":{"top":"64px","bottom":"64px","left":"24px","right":"24px"}}}} -->
<div class="wp-block-cover alignfull" style="padding-top:64px;padding-right:24px;padding-bottom:64px;padding-left:24px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-50 has-background-dim" style="background-color:#1a1a1a"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"42px","fontWeight":"800"},"color":{"text":"#ffffff"}}} -->
<h1 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff;font-size:42px;font-weight:800">Become a Luvron Dealer</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"18px"},"color":{"text":"#f0f0f0"}}} -->
<p class="has-text-align-center has-text-color" style="color:#f0f0f0;font-size:18px">Strong margins · Pan-India dispatch · GST-compliant invoicing</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->

<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"48px","bottom":"48px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:48px;padding-bottom:48px">
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide">
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Why partner with Luvron</h3><!-- /wp:heading -->
<!-- wp:list --><ul><li><strong>Direct manufacturer pricing</strong> — best margins in the kids tricycle category.</li><li><strong>14 model families</strong> — match every price point and customer segment.</li><li><strong>Master-pack ready</strong> — engineered for stacking and transit.</li><li><strong>5% / 18% GST billing</strong> — clean tax structure for your books.</li><li><strong>48-hour dispatch SLA</strong> on confirmed orders.</li></ul><!-- /wp:list -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Apply now</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Fill out the form below. We'll review and approve within 24 hours.</p><!-- /wp:paragraph -->
<!-- wp:html -->
<form id="luvron-dealer-apply" method="post" style="background:#f7f7f7;padding:20px;border-radius:8px;">
    <p><label>Business Name *<input required type="text" name="business" style="width:100%;padding:8px;"/></label></p>
    <p><label>Owner Name *<input required type="text" name="owner" style="width:100%;padding:8px;"/></label></p>
    <p><label>GSTIN<input type="text" name="gstin" style="width:100%;padding:8px;"/></label></p>
    <p><label>State *<input required type="text" name="state" style="width:100%;padding:8px;"/></label></p>
    <p><label>City / PIN *<input required type="text" name="city" style="width:100%;padding:8px;"/></label></p>
    <p><label>Phone / WhatsApp *<input required type="tel" name="phone" style="width:100%;padding:8px;"/></label></p>
    <p><label>Email *<input required type="email" name="email" style="width:100%;padding:8px;"/></label></p>
    <p><label>Monthly volume (cartons)
        <select name="volume" style="width:100%;padding:8px;">
            <option>Less than 50</option><option>50–200</option><option>200+</option>
        </select>
    </label></p>
    <p><label>Account requested
        <select name="account" style="width:100%;padding:8px;">
            <option value="dealer">Dealer</option><option value="distributor">Distributor</option>
        </select>
    </label></p>
    <p><button type="submit" style="background:#ff8c00;color:#fff;padding:12px 24px;border:none;border-radius:4px;font-weight:600;">Submit Application</button></p>
</form>
<!-- /wp:html -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"color":{"background":"#fafafa"},"spacing":{"padding":{"top":"48px","bottom":"48px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background" style="background-color:#fafafa;padding-top:48px;padding-bottom:48px">
<!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">Frequently Asked</h2><!-- /wp:heading -->
<!-- wp:details --><details class="wp-block-details"><summary>What is the minimum order quantity?</summary><!-- wp:paragraph --><p>One master carton per SKU. Carton sizes range from 12 to 24 pieces depending on the model.</p><!-- /wp:paragraph --></details><!-- /wp:details -->
<!-- wp:details --><details class="wp-block-details"><summary>Do you offer transport?</summary><!-- wp:paragraph --><p>We dispatch ex-works Loni, Ghaziabad. We can recommend transporters; freight is to dealer's account unless agreed otherwise.</p><!-- /wp:paragraph --></details><!-- /wp:details -->
<!-- wp:details --><details class="wp-block-details"><summary>What's the payment cycle?</summary><!-- wp:paragraph --><p>Advance against proforma for new accounts. Established dealers may negotiate credit terms after the first three orders.</p><!-- /wp:paragraph --></details><!-- /wp:details -->
<!-- wp:details --><details class="wp-block-details"><summary>How long does approval take?</summary><!-- wp:paragraph --><p>Within 24 hours of receiving your complete application during working days.</p><!-- /wp:paragraph --></details><!-- /wp:details -->
</div>
<!-- /wp:group -->
HTML;
}
