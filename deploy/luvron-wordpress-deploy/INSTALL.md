# Luvron — WordPress B2B Platform Deployment

This bundle contains everything needed to deploy the Luvron B2B WordPress site on a cPanel host (e.g. luvron.in). The static HTML preview at https://digitalvinayk.github.io/luvron/ is **just a visual mockup**. The real product is the WordPress + WooCommerce installation that this bundle deploys.

---

## What this bundle delivers (mapped to the original 10-section brief)

| § | Brief Section | Where it lives |
|---|---|---|
| 1 | **Architecture overview** | `mu-plugins/` (26 must-use plugins, drop-in) |
| 2 | **Plugin stack** (minimal, scalable) | `import/wp-cli-bootstrap.sh` installs WC, WholesaleX, RankMath, GST plugin, ShortPixel, WPForms, Updraft, Wordfence — no Elementor, no bloat |
| 3 | **Database + pricing logic** | `luvron-pricing.php` (role-based prices, 7% distributor discount), `luvron-roles.php` (dealer/distributor/pending), product meta (`_price_dealer`, `_price_distributor`, `_moq_carton`, `_pack_inner`, `_pack_master`, `_gst_rate`, `_hsn_code`) |
| 4 | **WooCommerce customization** | `luvron-quote.php` (Request Quote for guests), `luvron-moq.php` (carton enforcement), `luvron-bulk-order.php` (single-page bulk grid), `luvron-invoice.php` (custom GST invoice with IRN), `luvron-einvoice.php` (IRN/QR via IRP API), `luvron-proforma.php` (proforma → confirmed → paid → shipped state machine), `luvron-payments.php` (Bank/UPI/Razorpay/Net-30 credit) |
| 5 | **Product structure + CSV** | `import/luvron-products.csv` (76 rows, 44 SKUs across 11 series + variations), or auto-seeded by `luvron-seed.php` on first admin visit (no CSV upload needed) |
| 6 | **Dealer system** | `luvron-roles.php` (registration → pending_b2b → admin approval → dealer/distributor), `luvron-dealer-dashboard.php` (`[luvron_dealer_dashboard]` shortcode), `luvron-gstin-validate.php` (live GSTIN check), `luvron-whatsapp-api.php` (Meta Cloud API templates) |
| 7 | **Homepage wireframe + copy** | `luvron-homepage-pattern.php` (Gutenberg block patterns auto-inserted into Home page by `luvron-seed.php`) |
| 8 | **UX flows** | Guest sees "Request Quote" / Logged-in sees "Order Now" — handled by `luvron-quote.php`. Bulk order grid at `/bulk-order/`. Quick Quote (volume → SKU match) at `/quick-quote/` via `luvron-quick-quote.php`. |
| 9 | **SEO + performance** | `luvron-schema.php` (Organization + Product JSON-LD), Rank Math installed by bootstrap, image lazy-loading native, ShortPixel for compression. GA4/GTM hooks in `luvron-tracking.php` (TODO: add tracking IDs in WP Admin → Settings → Luvron Tracking). |
| 10 | **Deployment** | This file + `import/wp-cli-bootstrap.sh` |

---

## Prerequisites

- cPanel hosting with:
  - PHP ≥ 8.2
  - MySQL 8 / MariaDB ≥ 10.6
  - LiteSpeed (preferred) or Apache
  - WP-CLI installed (most cPanel hosts ship it)
  - SSH access (or terminal in cPanel UI)
- Fresh WordPress installed at `~/public_html/`

---

## Deployment Steps

### Option A — One-shot via SSH (recommended)

```bash
# From your local machine, push the bundle to cPanel
scp -r luvron-wordpress-deploy user@luvron.in:/home/luvron/

# SSH in and run the installer
ssh user@luvron.in
cd ~/luvron-wordpress-deploy
bash install.sh
```

That's it. The installer:
1. Copies `mu-plugins/*.php` → `~/public_html/wp-content/mu-plugins/` (creates the dir if missing)
2. Copies `product-images/*.jpg` → `~/public_html/wp-content/uploads/luvron-products/`
3. Copies `testimonials/*.jpg` → `~/public_html/wp-content/uploads/luvron-testimonials/`
4. Copies `brand-assets/logo.png` → `~/public_html/wp-content/uploads/`
5. Copies `luvron-catalogue.pdf` → `~/public_html/wp-content/uploads/luvron-catalogue-feb-26.pdf`
6. Runs `import/wp-cli-bootstrap.sh` which:
   - Installs WooCommerce, WholesaleX, Rank Math, GST plugin, ShortPixel, WPForms, Updraft, Wordfence
   - Installs Astra theme
   - Configures India base location (UP), INR currency, tax-based-on-shipping
   - Creates GST 5 and GST 18 tax classes
   - Imports state-wise tax rates (UP CGST+SGST, IGST elsewhere)
   - Creates "Tricycles" parent category + 11 series subcategories
   - Creates pages: Request Quote, Become a Dealer, Bulk Order, Dealer Dashboard, Catalogue
   - Enables HPOS (High-Performance Order Storage)

After the script finishes, the **first time you visit `/wp-admin`**, the auto-seed plugin (`luvron-seed.php`) automatically:
- Creates all 44 simple products + 16 variable products + 32 variations
- Attaches product images to each SKU
- Creates Home, About, Contact, Privacy Policy, Terms, Refund Policy, Shipping Policy pages
- Inserts the homepage block pattern
- Creates the primary navigation menu
- Creates 4 dealer testimonials with photos

### Option B — Manual upload via cPanel File Manager

1. cPanel → File Manager → navigate to `~/public_html/wp-content/`
2. Create folder `mu-plugins/` if it doesn't exist
3. Upload all `mu-plugins/*.php` files into it
4. Navigate to `wp-content/uploads/`, create `luvron-products/` and upload `product-images/*.jpg`
5. Create `luvron-testimonials/` and upload `testimonials/*.jpg`
6. Upload `luvron-catalogue.pdf` to `wp-content/uploads/`
7. WP Admin → Plugins → Add New → install: WooCommerce, WholesaleX, Rank Math SEO, GST for WooCommerce, ShortPixel, WPForms Lite, UpdraftPlus, Wordfence
8. WP Admin → Appearance → Themes → install Astra
9. Visit any wp-admin page — the seed plugin runs automatically and creates everything

---

## Post-deployment configuration (one-time, in WP Admin)

| Setting | Where | What to enter |
|---|---|---|
| **Bank details** | `wp-config.php` | Add `LUVRON_BANK_ACCOUNT`, `LUVRON_BANK_IFSC` constants (see SECURITY.md) |
| **SMTP relay** | `wp-config.php` | Add `LUVRON_SMTP_HOST`, etc. for Brevo / SES / MS365 (Settings → Tools → Luvron SMTP for guide) |
| **WhatsApp Cloud API** | Settings → Luvron WhatsApp | Meta token + phone number ID + 5 approved templates |
| **GST e-invoicing** | Settings → Luvron E-Invoice | ClearTax/Cygnet/GSTHero IRP API credentials (mandatory if turnover ≥ ₹5 cr) |
| **Razorpay** | WooCommerce → Settings → Payments | Install Razorpay official plugin → Key/Secret |
| **Trust badges** | Settings → Luvron Trust | Real dealer count, Udyam number, factory video URL |
| **Tax rates** | WooCommerce → Settings → Tax | Already imported by bootstrap; verify CGST/SGST split for UP and IGST for other states |
| **GA4 / GTM** | Rank Math → Analytics | Connect Google account |

---

## What's pre-built and ready (no further work)

- ✅ All 44 SKUs with real prices, GST rates, HSN codes
- ✅ Role-based pricing engine (dealer / distributor / pending)
- ✅ MOQ enforcement (carton multiples)
- ✅ Bulk order grid for fast B2B ordering
- ✅ Dealer dashboard with order history + reorder
- ✅ Custom GST invoice PDF with IRN/QR
- ✅ Proforma → tax invoice workflow
- ✅ State-wise CGST/SGST/IGST automation
- ✅ Privacy / T&C / Refund / Shipping policy pages
- ✅ Homepage block pattern with hero, categories, why-Luvron, testimonials, CTA
- ✅ Schema.org JSON-LD on every page
- ✅ WhatsApp float button + thank-you confirm
- ✅ 4 dealer testimonials with real photos
- ✅ 11 model series taxonomy with subcategories

---

## Visual design preview

The static HTML preview at https://digitalvinayk.github.io/luvron/ is provided **only as a visual mockup** to share with the client before WordPress goes live. Once this bundle is deployed to luvron.in, the WordPress site itself becomes the canonical preview — login as a dealer to see role-based pricing, real cart, real checkout.

---

## Support

For deployment issues, contact the developer. For business / sales / dealer queries, contact:

**Luvron Tricycles**
OFF-D-337, T-04, Indraprastha Colony, Jawli Road, Loni, Ghaziabad UP – 201102
Phone: +91-9212389139
Email: orders@luvron.in
GSTIN: 09GOCPP5350G1ZQ
