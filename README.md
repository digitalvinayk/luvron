# Luvron — B2B Ecommerce Platform

WordPress + WooCommerce build for **Luvron Tricycles** — kids tricycle manufacturer in Loni, Ghaziabad UP.

🌐 **Live preview:** https://digitalvinayk.github.io/luvron/

## What's in this repo

```
mu-plugins/        26 must-use plugins — drop into wp-content/mu-plugins/
product-images/    44 product photos mapped to SKUs
testimonials/      4 dealer testimonial photos
import/            CSV product data, GST tax rates, WP-CLI bootstrap script
docs/              Static HTML preview (GitHub Pages)
```

## Architecture

| Layer | Stack |
|---|---|
| CMS | WordPress 6.x (latest) |
| Commerce | WooCommerce 9.x with HPOS enabled |
| Theme | Astra (Free) |
| B2B | WholesaleX (Free tier) + 26 custom mu-plugins |
| Hosting | cPanel + LiteSpeed (Hostinger / BigRock / A2) |
| Database | MariaDB 10.6+ / MySQL 8 |
| PHP | 8.2+ |

## Capabilities

- **Role-based pricing** — dealer / distributor / pending_b2b roles, 7% distributor discount
- **Quote system** — guests can't see prices; "Request Quote" CTA captures lead
- **MOQ enforcement** — orders in master-carton multiples (12 / 15 / 16 / 20 / 24 pieces)
- **Bulk order grid** — single-page table of all 76 SKUs with live subtotals + AJAX batch add
- **Dealer dashboard** — order history, one-click reorder, invoice download
- **Proforma workflow** — cart → proforma → confirmed → paid → IRN → shipped state machine
- **GST e-invoicing** — IRN/QR generation (manual or ClearTax/Cygnet/GSTHero API)
- **Custom GST invoice** — printable PDF with auto CGST/SGST split for UP, IGST elsewhere
- **Payment methods** — Bank transfer, UPI Collect, Razorpay, Net 30 credit terms
- **WhatsApp Business API** — automated messages on apply/approve/order/ship via Meta Cloud API
- **Trust signals** — testimonials, GST/Udyam badges, factory video, dealer count
- **Freight calculator** — state-wise tier matrix, live cart fees
- **Quick Quote** — volume + price band → matched SKUs
- **Comparison tables** — auto-injected on category pages
- **Legal pages** — Privacy Policy, T&C, Refund/Returns, Shipping Policy (India CPA 2019 compliant)
- **Auto-seed** — products, categories, pages, menu, testimonials all created on first admin visit
- **Kill switch** — disable any mu-plugin from wp-admin without filesystem access

## Deploy in one command

```bash
# From this folder, push to your luvron.in cPanel
scp -r mu-plugins product-images testimonials import \
    luvron-catalogue-feb-26-compressed.pdf \
    user@luvron.in:/home/luvron/

# SSH and run bootstrap
ssh user@luvron.in 'cd ~/public_html && bash ~/import/wp-cli-bootstrap.sh'

# Visit https://luvron.in/wp-admin once → seed runs automatically
```

## Setup credentials needed (in `wp-config.php`)

```php
define('LUVRON_BANK_NAME',     'Luvron Tricycles');
define('LUVRON_BANK_BANK',     'HDFC Bank');
define('LUVRON_BANK_ACCOUNT',  'XXXXXXXXXXXX');
define('LUVRON_BANK_IFSC',     'HDFC0000000');
define('LUVRON_BANK_BRANCH',   'Loni, Ghaziabad');
define('LUVRON_BANK_GSTIN',    '09GOCPP5350G1ZQ');

define('LUVRON_SMTP_HOST',     'smtp-relay.brevo.com');
define('LUVRON_SMTP_PORT',     587);
define('LUVRON_SMTP_USER',     'orders@luvron.in');
define('LUVRON_SMTP_PASS',     'your-brevo-smtp-key');
define('LUVRON_SMTP_FROM',     'orders@luvron.in');
define('LUVRON_SMTP_FROMNAME', 'Luvron Tricycles');
```

## Contact

**Luvron Tricycles**
OFF-D-337, T-04, Indraprastha Colony, Jawli Road, Loni, Ghaziabad UP – 201102
Phone: +91-9212389139
Founder: Rajneesh Kumar Pandey
