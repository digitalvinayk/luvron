<?php
/**
 * Plugin Name: Luvron — Homepage Block Pattern (Modern)
 * Description: Polished modern enterprise homepage pattern. Hero + stats + category grid +
 *              tier explainer + why-luvron + testimonials + CTA banner. All inline styled
 *              to render correctly even without theme support.
 * Version: 2.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-homepage-pattern')) return;

add_action('init', function () {
    if (!function_exists('register_block_pattern_category') || !function_exists('register_block_pattern')) return;
    register_block_pattern_category('luvron', ['label' => 'Luvron']);
    register_block_pattern('luvron/homepage', [
        'title'       => 'Luvron Homepage',
        'description' => 'Modern enterprise B2B homepage — hero, categories, tiers, why-Luvron, testimonials, CTA.',
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
    $upload = trailingslashit(wp_upload_dir()['baseurl']);
    $img    = $upload . 'luvron-products/';
    $tst    = $upload . 'luvron-testimonials/';

    return <<<HTML
<!-- wp:html -->
<div class="luvron-pattern">

<!-- HERO -->
<section class="lv-hero">
  <div class="lv-wrap lv-hero-grid">
    <div class="lv-hero-text">
      <span class="lv-eyebrow"><span class="lv-pulse"></span>Manufacturer Direct · B2B Wholesale</span>
      <h1 class="lv-display">Tricycles built for <span class="lv-accent">joy</span>.<br>Dispatched in <em class="lv-hand">48 hours.</em></h1>
      <p class="lv-lead"><strong>India's most carefully packed kids tricycles</strong> — direct from our Loni, Ghaziabad floor to your retail shelf. 44 SKUs across 11 series, GST-compliant invoicing, dealer-tier pricing.</p>
      <div class="lv-actions">
        <a href="/become-a-dealer/" class="lv-btn lv-btn-primary">Become a Dealer →</a>
        <a href="/shop/" class="lv-btn lv-btn-ghost">Browse 44 SKUs</a>
      </div>
    </div>
    <div class="lv-hero-visual">
      <div class="lv-chip lv-chip-tl"><span class="lv-chip-ico" style="background:#fff7e6;color:#b45309;">♪</span> Musical · Horn + Lights</div>
      <div class="lv-chip lv-chip-br"><span class="lv-chip-ico" style="background:#ecfdf5;color:#2f9e44;">▣</span> Master Pack · 12</div>
      <img src="{$img}sigma-plus.jpg" alt="SIGMA Plus tricycle" class="lv-hero-img">
    </div>
  </div>
</section>

<!-- STATS BAND -->
<section class="lv-stats-section">
  <div class="lv-wrap">
    <div class="lv-stats">
      <div class="lv-stat"><div class="lv-stat-num">44</div><div class="lv-stat-label">Active SKUs</div></div>
      <div class="lv-stat"><div class="lv-stat-num">11</div><div class="lv-stat-label">Model Series</div></div>
      <div class="lv-stat"><div class="lv-stat-num"><em>48</em>hr</div><div class="lv-stat-label">Dispatch SLA</div></div>
      <div class="lv-stat"><div class="lv-stat-num">50<em>+</em></div><div class="lv-stat-label">Active Dealers</div></div>
    </div>
  </div>
</section>

<!-- CATEGORIES -->
<section class="lv-section lv-bg-canvas">
  <div class="lv-wrap">
    <div class="lv-head">
      <span class="lv-section-label">Our Collections</span>
      <h2 class="lv-display">Pick a series. <span class="lv-accent">We'll handle the rest.</span></h2>
      <p class="lv-blurb">Eleven model families — from premium SIGMA Plus to value-tier RAMBO. Every series ships with master cartons and GST-compliant invoicing.</p>
    </div>
    <div class="lv-cat-grid">
      <a href="/product-category/sigma-series/" class="lv-cat-card lv-c-coral"><div class="lv-cat-img"><img src="{$img}sigma-plus.jpg" alt="SIGMA"></div><div class="lv-cat-info"><h3>SIGMA</h3><p>Premium · 4 SKUs</p></div></a>
      <a href="/product-category/aura-series/" class="lv-cat-card lv-c-sky"><div class="lv-cat-img"><img src="{$img}aura-plus.jpg" alt="AURA"></div><div class="lv-cat-info"><h3>AURA</h3><p>Mid-tier · 4 SKUs</p></div></a>
      <a href="/product-category/eagle-series/" class="lv-cat-card lv-c-mint"><div class="lv-cat-img"><img src="{$img}eagle-plus.jpg" alt="EAGLE"></div><div class="lv-cat-info"><h3>EAGLE</h3><p>Sporty · 4 SKUs</p></div></a>
      <a href="/product-category/alex-series/" class="lv-cat-card lv-c-cream"><div class="lv-cat-img"><img src="{$img}alex-plus.jpg" alt="ALEX"></div><div class="lv-cat-info"><h3>ALEX</h3><p>Bestseller · 4 SKUs</p></div></a>
      <a href="/product-category/ecotech-series/" class="lv-cat-card lv-c-lavender"><div class="lv-cat-img"><img src="{$img}ecotech-plus.jpg" alt="ECOTECH"></div><div class="lv-cat-info"><h3>ECOTECH</h3><p>Eco · 4 SKUs</p></div></a>
      <a href="/product-category/rambo-series/" class="lv-cat-card lv-c-coral"><div class="lv-cat-img"><img src="{$img}rambo-333-plus.jpg" alt="RAMBO"></div><div class="lv-cat-info"><h3>RAMBO</h3><p>Value · 8 SKUs</p></div></a>
      <a href="/product-category/hulk-series/" class="lv-cat-card lv-c-sky"><div class="lv-cat-img"><img src="{$img}hulk-pro-plus.jpg" alt="HULK"></div><div class="lv-cat-info"><h3>HULK</h3><p>Sturdy · 4 SKUs</p></div></a>
      <a href="/product-category/charlie-series/" class="lv-cat-card lv-c-mint"><div class="lv-cat-img"><img src="{$img}charlie-r1-plus.jpg" alt="CHARLIE"></div><div class="lv-cat-info"><h3>CHARLIE</h3><p>Compact · 4 SKUs</p></div></a>
    </div>
  </div>
</section>

<!-- TIERS -->
<section class="lv-section lv-bg-cream">
  <div class="lv-wrap">
    <div class="lv-head">
      <span class="lv-section-label">Two Pricing Tiers</span>
      <h2 class="lv-display">Same chassis. <span class="lv-accent">Different soundtrack.</span></h2>
      <p class="lv-blurb">Every model comes in two tiers — Musical (with horn, lights, music) at 18% GST, or Normal (clean ride, no electronics) at 5% GST.</p>
    </div>
    <div class="lv-tier-grid">
      <div class="lv-tier-card lv-tier-musical">
        <span class="lv-tier-marker"><span class="lv-tier-ico">♪</span> Musical · 18% GST</span>
        <h3 class="lv-display">Plus Variants</h3>
        <p class="lv-tier-price">₹680 – ₹1,760 wholesale</p>
        <ul class="lv-tier-features">
          <li><span class="lv-check">✓</span><span><strong>Horn, lights &amp; music</strong> on the handlebar — kids' top decision driver</span></li>
          <li><span class="lv-check">✓</span><span><strong>Premium retail boxed</strong> packaging with brand graphics</span></li>
          <li><span class="lv-check">✓</span><span>Higher margin tier — better shelf attraction</span></li>
        </ul>
        <p class="lv-hsn">HSN <code>9503</code> · Toys with mechanical sound</p>
      </div>
      <div class="lv-tier-card lv-tier-normal">
        <span class="lv-tier-marker"><span class="lv-tier-ico">⚙</span> Normal · 5% GST</span>
        <h3 class="lv-display">Base Variants</h3>
        <p class="lv-tier-price">₹560 – ₹1,700 wholesale</p>
        <ul class="lv-tier-features">
          <li><span class="lv-check">✓</span><span><strong>Same chassis &amp; wheels</strong> as Plus — ride identical</span></li>
          <li><span class="lv-check">✓</span><span><strong>Lower price point</strong> — best for tier-2/3 markets</span></li>
          <li><span class="lv-check">✓</span><span>Lower GST — customer-friendly tax pass-through</span></li>
        </ul>
        <p class="lv-hsn">HSN <code>8712</code> · Children's bicycles &amp; tricycles</p>
      </div>
    </div>
  </div>
</section>

<!-- WHY LUVRON -->
<section class="lv-section">
  <div class="lv-wrap">
    <div class="lv-head">
      <span class="lv-section-label">Why Luvron</span>
      <h2 class="lv-display">Built for <span class="lv-accent">dealers who measure margins.</span></h2>
      <p class="lv-blurb">Six things we get right, every order. No fine print, no overpromise.</p>
    </div>
    <div class="lv-feat-grid">
      <div class="lv-feat"><span class="lv-feat-ico" style="background:#fff0ec;color:#e84a3f;">🏠</span><h3>Manufacturer Direct</h3><p>You buy from the workshop, not through a distributor's distributor. The price you see is the price we make.</p></div>
      <div class="lv-feat"><span class="lv-feat-ico" style="background:#eef5ff;color:#1c7ed6;">🚚</span><h3>48-Hour Dispatch</h3><p>Confirmed orders leave Loni within 48 working hours. Out-of-stock SKUs flagged at proforma stage.</p></div>
      <div class="lv-feat"><span class="lv-feat-ico" style="background:#ecfdf5;color:#2f9e44;">📦</span><h3>Carton-Tested Packing</h3><p>Inner boxes engineered for stack and transit. Damage rate held below 0.5% on top three corridors.</p></div>
      <div class="lv-feat"><span class="lv-feat-ico" style="background:#fff7e6;color:#b45309;">📋</span><h3>GST-Compliant</h3><p>Every B2B tax invoice carries IRN/QR code from the IRP. CGST/SGST split for UP, IGST elsewhere.</p></div>
      <div class="lv-feat"><span class="lv-feat-ico" style="background:#f3eeff;color:#7048e8;">🛡</span><h3>Territory Protection</h3><p>Distributors get exclusive PIN-code zones. We do not appoint competing dealers in your area.</p></div>
      <div class="lv-feat"><span class="lv-feat-ico" style="background:#fff0ec;color:#e84a3f;">💬</span><h3>Direct Founder Line</h3><p>Rajneesh picks up the phone — WhatsApp +91 9212 389 139. That's not common in this category.</p></div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="lv-section lv-bg-cream">
  <div class="lv-wrap">
    <div class="lv-head">
      <span class="lv-section-label">Voice of Our Dealers</span>
      <h2 class="lv-display">Trusted by <span class="lv-accent">retailers across India.</span></h2>
      <p class="lv-blurb">Quotes from working dealers across Lucknow, Patna, Delhi NCR and Kanpur.</p>
    </div>
    <div class="lv-testi-grid">
      <div class="lv-testi"><div class="lv-stars">★★★★★</div><p class="lv-quote">I have been distributing Luvron tricycles for two seasons now. The packaging holds up perfectly even after long-haul transport, and the price points let me run healthy retail margins across all my outlets.</p><div class="lv-who"><img src="{$tst}aditya-sharma.jpg" alt=""><div><strong>Aditya Sharma</strong><small>Distributor · Lucknow</small></div></div></div>
      <div class="lv-testi"><div class="lv-stars">★★★★★</div><p class="lv-quote">What I value most is the dispatch speed. We confirm an order, the cartons leave Loni in 48 hours. That predictability is rare with kids products in India.</p><div class="lv-who"><img src="{$tst}ambuj.jpg" alt=""><div><strong>Ambuj Kumar</strong><small>Dealer · Patna</small></div></div></div>
      <div class="lv-testi"><div class="lv-stars">★★★★★</div><p class="lv-quote">Luvron Sigma Plus is our bestseller in the premium segment. Customers come back for the second child too — the build quality speaks for itself.</p><div class="lv-who"><img src="{$tst}dinesh-kanojia.jpg" alt=""><div><strong>Dinesh Kanojia</strong><small>Retailer · Delhi NCR</small></div></div></div>
      <div class="lv-testi"><div class="lv-stars">★★★★★</div><p class="lv-quote">Direct manufacturer pricing, GST-compliant invoices, and a team that picks up the phone. That's all I need from a supplier.</p><div class="lv-who"><img src="{$tst}dinesh.jpg" alt=""><div><strong>Dinesh</strong><small>Dealer · Kanpur</small></div></div></div>
    </div>
  </div>
</section>

<!-- CTA BAND -->
<section class="lv-section">
  <div class="lv-wrap">
    <div class="lv-cta-banner">
      <div class="lv-cta-text">
        <h2 class="lv-display">Open a dealer account.<br><span class="lv-accent-yellow">Unlock the catalogue.</span></h2>
        <p>Verified GSTIN gets you wholesale pricing within 24 hours. Distributors with PIN-code commitments get exclusive territory rights and an additional 7% off dealer.</p>
      </div>
      <div class="lv-cta-actions">
        <a href="/become-a-dealer/" class="lv-btn lv-btn-light">Apply for Dealer Account</a>
        <a href="tel:+919212389139" class="lv-btn lv-btn-yellow">📞 +91 9212 389 139</a>
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

<section class="lv-hero-slim">
  <div class="lv-wrap">
    <div class="lv-breadcrumb"><a href="/">Home</a> · <span>Become a Dealer</span></div>
    <h1 class="lv-display">Become a Luvron <span class="lv-accent">Dealer.</span></h1>
    <p class="lv-lead">Strong margins · Pan-India dispatch · GST-compliant invoicing</p>
  </div>
</section>

<section class="lv-section">
  <div class="lv-wrap" style="display:grid;grid-template-columns:1fr 1fr;gap:64px;">
    <div>
      <h3 style="font-family:'Bricolage Grotesque',sans-serif;font-size:28px;margin-bottom:16px;">Why partner with Luvron</h3>
      <ul style="list-style:none;padding:0;font-size:15px;line-height:1.8;color:#475569;">
        <li>✓ <strong>Direct manufacturer pricing</strong> — best margins in the category</li>
        <li>✓ <strong>14 model families</strong> — match every price point</li>
        <li>✓ <strong>Master-pack ready</strong> — engineered for transit</li>
        <li>✓ <strong>5% / 18% GST billing</strong> — clean tax structure</li>
        <li>✓ <strong>48-hour dispatch SLA</strong> on confirmed orders</li>
        <li>✓ <strong>Direct line to founder</strong> Rajneesh Kumar Pandey</li>
      </ul>
    </div>
    <div>
      <h3 style="font-family:'Bricolage Grotesque',sans-serif;font-size:28px;margin-bottom:16px;">Apply now</h3>
      <p style="margin-bottom:20px;color:#475569;">Fill out the form below. We'll review and approve within 24 hours.</p>
      [contact-form-7 id="luvron-dealer-apply"]
      <p><a href="/contact/" class="lv-btn lv-btn-primary">Open Dealer Application Form →</a></p>
    </div>
  </div>
</section>

</div>
<!-- /wp:html -->
HTML;
}
