# Luvron — B2B WordPress Ecommerce Platform

**A scalable WordPress + WooCommerce B2B ordering and dealer-acquisition system for Luvron Tricycles**, India-based kids tricycle manufacturer in Loni, Ghaziabad UP.

This repo is the **WordPress build** — not a static site. The static HTML at [/docs](docs/) is just a visual mockup for client review.

---

## ⬇️ Deploy to your cPanel host

```bash
# Download the deploy bundle (11 MB, includes everything)
curl -L -o luvron-wp.tar.gz https://github.com/digitalvinayk/luvron/raw/main/deploy/luvron-wordpress-deploy.tar.gz
tar -xzf luvron-wp.tar.gz
cd luvron-wordpress-deploy

# Push to cPanel host
scp -r . user@luvron.in:/home/luvron/luvron-wordpress-deploy/

# SSH and run installer
ssh user@luvron.in 'cd ~/luvron-wordpress-deploy && bash install.sh'
```

The installer creates everything: products, pages, categories, tax classes, navigation, plugins, theme. **No CSV upload, no manual product entry, no plugin clicking.** Read [`deploy/luvron-wordpress-deploy/INSTALL.md`](deploy/luvron-wordpress-deploy/INSTALL.md) for the full step-by-step.

---

## What this delivers (mapped to the original brief)

| § | Brief Section | Implementation |
|---|---|---|
| **1** | Architecture overview | 26 must-use plugins drop-in to `wp-content/mu-plugins/` — no theme dependency, no plugin to activate, no admin clicks |
| **2** | Plugin stack (minimal, scalable) | WooCommerce + WholesaleX + Astra + Rank Math + GST for WC + ShortPixel + WPForms + UpdraftPlus + Wordfence. **No Elementor**, no page-builder, no bloat. Total active plugin count: 9 + WooCommerce. |
| **3** | Database + pricing logic | Role-based prices in `wp_postmeta` (`_price_dealer`, `_price_distributor`), 7% distributor discount fallback, MOQ in carton multiples, GST per SKU, full HSN |
| **4** | WooCommerce customization | 26 mu-plugins covering: roles, pricing filter, MOQ enforcement, quote requests, bulk order grid, dealer dashboard, GST invoice with IRN, proforma workflow, payments (Bank/UPI/Razorpay/Net-30), e-invoicing, fulfillment, GSTIN validation, freight calculator, WhatsApp Cloud API |
| **5** | Product structure + CSV | All 44 SKUs (28 simple + 16 variable + 32 variations) auto-seeded by `luvron-seed.php` on first admin visit. CSV available as fallback at `import/luvron-products.csv`. Hierarchical taxonomy: Tricycles → 11 series. |
| **6** | Dealer system workflow | Registration → `pending_b2b` role → admin approval → `dealer` or `distributor` role → wholesale pricing unlocked. Email + WhatsApp notifications at every state transition. CRM-ready data in `wp_usermeta`. |
| **7** | Homepage wireframe + copy | Gutenberg block patterns auto-inserted into Home page by seed plugin. Hero, trust strip, 11-category grid, why-Luvron, dealer CTA, catalogue download — all native blocks, no Elementor. |
| **8** | UX flows | Guest → "Request Quote" / Dealer → "Order Now" (filtered at runtime). Bulk order grid at `/bulk-order/`. Quick Quote (volume → SKU match) at `/quick-quote/`. Mobile-responsive throughout. |
| **9** | SEO + performance | Organization + Product JSON-LD on every page. Rank Math sitemap. Native lazy-loading. ShortPixel WebP. HPOS enabled. Core Web Vitals targets: LCP < 2.5s, INP < 200ms, CLS < 0.1. |
| **10** | Deployment | `deploy/luvron-wordpress-deploy.tar.gz` — 11 MB self-contained installer. SCP to cPanel, SSH, run `install.sh`. |

---

## Repo layout

```
luvron/
├── mu-plugins/                   ← THE WORDPRESS B2B PRODUCT (26 PHP files)
│   ├── 00-luvron-killswitch.php  (loaded first — disable any plugin from wp-admin)
│   ├── luvron-roles.php          (dealer/distributor/pending_b2b)
│   ├── luvron-pricing.php        (role-based price filter, 7% distributor)
│   ├── luvron-moq.php            (carton multiples enforcement)
│   ├── luvron-quote.php          (Request Quote for guests)
│   ├── luvron-bulk-order.php     ([luvron_bulk_order] grid shortcode)
│   ├── luvron-dealer-dashboard.php  ([luvron_dealer_dashboard] shortcode)
│   ├── luvron-invoice.php        (custom GST invoice + bank transfer page)
│   ├── luvron-einvoice.php       (IRN/QR generation via IRP API)
│   ├── luvron-proforma.php       (proforma → confirmed → paid → shipped state machine)
│   ├── luvron-payments.php       (Bank/UPI/Razorpay/Net-30 credit gateways)
│   ├── luvron-fulfillment.php    (warehouse_staff role + tracking)
│   ├── luvron-gstin-validate.php (live GSTIN format + API verification)
│   ├── luvron-freight.php        (state-wise freight calculator)
│   ├── luvron-quick-quote.php    ([luvron_quick_quote] volume → SKU)
│   ├── luvron-pricelist-pdf.php  (role-aware printable price list)
│   ├── luvron-comparison.php     ([luvron_compare series="SIGMA"] table)
│   ├── luvron-testimonials.php   (CPT + 4 dealer testimonials seeded)
│   ├── luvron-trust-badges.php   (GSTIN/Udyam/MSME stat shortcodes)
│   ├── luvron-legal-pages.php    (Privacy/T&C/Refund/Shipping auto-create)
│   ├── luvron-schema.php         (Organization + Product JSON-LD)
│   ├── luvron-whatsapp.php       (floating WA button)
│   ├── luvron-whatsapp-api.php   (Meta Cloud API + 5 templated events)
│   ├── luvron-smtp.php           (Brevo/SES/MS365 SMTP relay setup)
│   ├── luvron-homepage-pattern.php  (Gutenberg homepage block patterns)
│   └── luvron-seed.php           (auto-creates products + pages + menu)
│
├── product-images/               ← 44 SKU photos (extracted from cPanel backup)
├── testimonials/                 ← 4 dealer testimonial photos
├── brand-assets/                 ← Logo, favicon, white logo
├── luvron-catalogue-feb-26-compressed.pdf  ← 9.8 MB compressed catalogue
│
├── import/                       ← CSV fallback + bootstrap script
│   ├── luvron-products.csv       (76 rows, all SKUs — only used if seed plugin disabled)
│   ├── tax-rates-gst-5.csv       (state-wise IGST + UP CGST/SGST)
│   ├── tax-rates-gst-18.csv
│   ├── sku-to-image-map.csv      (SKU → image filename reference)
│   └── wp-cli-bootstrap.sh       (plugin install + WC config + tax setup)
│
├── deploy/
│   ├── luvron-wordpress-deploy.tar.gz   ← 11 MB SELF-CONTAINED INSTALLER
│   └── luvron-wordpress-deploy/         ← Same, unpacked
│
└── docs/                         ← STATIC HTML PREVIEW (visual mockup only)
    └── ... (5 pages, GitHub Pages at https://digitalvinayk.github.io/luvron/)
```

---

## Setup credentials needed (in `wp-config.php` after install)

```php
// Bank transfer details — never stored in DB or filesystem code
define('LUVRON_BANK_NAME',     'Luvron Tricycles');
define('LUVRON_BANK_BANK',     'HDFC Bank');
define('LUVRON_BANK_ACCOUNT',  'XXXXXXXXXXXX');     // ← real account number
define('LUVRON_BANK_IFSC',     'HDFC0000000');      // ← real IFSC
define('LUVRON_BANK_BRANCH',   'Loni, Ghaziabad');
define('LUVRON_BANK_GSTIN',    '09GOCPP5350G1ZQ');

// SMTP — for transactional email reliability (Brevo recommended)
define('LUVRON_SMTP_HOST',     'smtp-relay.brevo.com');
define('LUVRON_SMTP_PORT',     587);
define('LUVRON_SMTP_USER',     'orders@luvron.in');
define('LUVRON_SMTP_PASS',     'your-brevo-smtp-key');
define('LUVRON_SMTP_FROM',     'orders@luvron.in');
define('LUVRON_SMTP_FROMNAME', 'Luvron Tricycles');
define('LUVRON_SMTP_SECURE',   'tls');
```

Then in WP Admin:
- **Settings → Luvron WhatsApp** → Meta Cloud API token + phone ID + 5 approved templates
- **Settings → Luvron E-Invoice** → ClearTax/Cygnet/GSTHero IRP API (mandatory if turnover ≥ ₹5 cr)
- **Settings → Luvron Trust** → real dealer count, Udyam registration, factory video URL
- **WooCommerce → Settings → Payments** → install Razorpay official plugin → Key/Secret

---

## Visual mockup (for client design review only)

A static HTML preview lives at https://digitalvinayk.github.io/luvron/ for sharing visual direction with the client before WordPress goes live. **It is not the product.** The product is the WordPress site this repo deploys.

---

## Contact

**Luvron Tricycles**
OFF-D-337, T-04, Indraprastha Colony, Jawli Road, Loni, Ghaziabad UP – 201102
Phone: +91-9212389139
Email: orders@luvron.in
Founder: Rajneesh Kumar Pandey
GSTIN: 09GOCPP5350G1ZQ
Founded: 2022
