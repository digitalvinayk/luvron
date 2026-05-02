#!/usr/bin/env bash
#
# Luvron WordPress B2B — One-shot cPanel installer
# Run this from inside the bundle folder, AFTER WordPress is installed at ~/public_html/
#
# Usage: bash install.sh
#

set -euo pipefail

# Locate WordPress install
WP_ROOT="${WP_ROOT:-$HOME/public_html}"
if [ ! -f "$WP_ROOT/wp-config.php" ]; then
    echo "ERROR: WordPress not found at $WP_ROOT"
    echo "Set WP_ROOT environment variable, e.g. WP_ROOT=/path/to/wordpress bash install.sh"
    exit 1
fi

WP="${WP:-wp}"
if ! command -v "$WP" >/dev/null 2>&1; then
    echo "ERROR: WP-CLI not found. Install via: curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar"
    exit 1
fi

cd "$WP_ROOT"

echo "==> [1/8] Verifying WordPress install"
$WP --info >/dev/null
$WP core version

echo "==> [2/8] Copying mu-plugins"
mkdir -p wp-content/mu-plugins
cp -v "$OLDPWD"/mu-plugins/*.php wp-content/mu-plugins/

echo "==> [3/8] Copying product images ($(ls "$OLDPWD"/product-images/ | wc -l) files)"
mkdir -p wp-content/uploads/luvron-products
cp -v "$OLDPWD"/product-images/*.jpg wp-content/uploads/luvron-products/ >/dev/null
echo "    done"

echo "==> [4/8] Copying testimonial photos ($(ls "$OLDPWD"/testimonials/ | wc -l) files)"
mkdir -p wp-content/uploads/luvron-testimonials
cp -v "$OLDPWD"/testimonials/*.jpg wp-content/uploads/luvron-testimonials/ >/dev/null
echo "    done"

echo "==> [5/8] Copying brand assets and catalogue PDF"
cp -v "$OLDPWD"/brand-assets/logo.png wp-content/uploads/luvron-logo.png 2>/dev/null || true
cp -v "$OLDPWD"/brand-assets/logo_white.png wp-content/uploads/luvron-logo-white.png 2>/dev/null || true
cp -v "$OLDPWD"/brand-assets/favicon-32x32.png wp-content/uploads/luvron-favicon.png 2>/dev/null || true
cp -v "$OLDPWD"/luvron-catalogue.pdf wp-content/uploads/luvron-catalogue-feb-26.pdf 2>/dev/null || true
echo "    done"

echo "==> [6/8] Running WP-CLI bootstrap"
cp "$OLDPWD"/import/luvron-products.csv .
cp "$OLDPWD"/import/tax-rates-gst-5.csv .
cp "$OLDPWD"/import/tax-rates-gst-18.csv .
bash "$OLDPWD"/import/wp-cli-bootstrap.sh

echo "==> [7/8] Triggering admin_init (so seed plugin runs)"
$WP eval 'do_action("admin_init");' || true

echo "==> [8/8] Verifying"
echo "    Products created: $($WP post list --post_type=product --field=ID 2>/dev/null | wc -l)"
echo "    Pages created:    $($WP post list --post_type=page --field=ID 2>/dev/null | wc -l)"
echo "    Mu-plugins:       $(ls wp-content/mu-plugins/*.php | wc -l)"
echo "    Product images:   $(ls wp-content/uploads/luvron-products/ | wc -l)"

echo
echo "============================================================"
echo "  Installation complete."
echo "============================================================"
echo "  Next steps (in WP Admin):"
echo "  1. Settings → Luvron WhatsApp → enter Meta Cloud API token"
echo "  2. Settings → Luvron E-Invoice → enter IRP API key (if turnover ≥ ₹5 cr)"
echo "  3. Settings → Luvron Trust → real dealer count, Udyam number"
echo "  4. WooCommerce → Settings → Payments → install + configure Razorpay"
echo "  5. Edit wp-config.php → add LUVRON_BANK_*, LUVRON_SMTP_* constants"
echo "  6. Test: register a guest → admin approves → login → see prices"
echo
echo "  Cleanup CSV/SH artifacts at WP root if you don't need them:"
echo "    rm $WP_ROOT/luvron-products.csv $WP_ROOT/tax-rates-gst-*.csv"
echo "============================================================"
