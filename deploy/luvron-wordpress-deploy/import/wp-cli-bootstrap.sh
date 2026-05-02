#!/usr/bin/env bash
#
# Luvron — Full WP-CLI bootstrap (idempotent)
# Run from the cPanel terminal/SSH inside your WordPress install root.
# Requires: WP-CLI (`wp --info` should work).
#
# Usage:
#   bash wp-cli-bootstrap.sh
#
set -euo pipefail
WP=${WP:-wp}

echo "==> [1/9] Verifying WP-CLI"
$WP --info >/dev/null

echo "==> [2/9] Installing required plugins"
$WP plugin install woocommerce wholesalex rank-math-seo gst-for-woocommerce \
    shortpixel-image-optimiser wpforms-lite updraftplus wordfence \
    click-to-chat-for-whatsapp --activate || true

echo "==> [3/9] Activating Astra theme (auto-installs)"
$WP theme install astra --activate || true

echo "==> [4/9] WooCommerce core settings"
$WP option update woocommerce_default_country IN:UP
$WP option update woocommerce_currency INR
$WP option update woocommerce_currency_pos left
$WP option update woocommerce_price_thousand_sep ","
$WP option update woocommerce_price_decimal_sep "."
$WP option update woocommerce_price_num_decimals 2
$WP option update woocommerce_calc_taxes yes
$WP option update woocommerce_prices_include_tax no
$WP option update woocommerce_tax_based_on shipping
$WP option update woocommerce_custom_orders_table_enabled yes
$WP option update woocommerce_feature_custom_order_tables_enabled yes

echo "==> [5/9] Tax classes (GST 5 / GST 18)"
EXISTING_CLASSES=$($WP option get woocommerce_tax_classes 2>/dev/null || echo '')
if ! echo "$EXISTING_CLASSES" | grep -q "GST 5"; then
    $WP option update woocommerce_tax_classes "$(printf 'GST 5\nGST 18')"
fi

echo "    Importing state-wise GST rates"
$WP db query "DELETE FROM \`$($WP db prefix)woocommerce_tax_rate_locations\`;
              DELETE FROM \`$($WP db prefix)woocommerce_tax_rates\`
              WHERE tax_rate_class IN ('gst-5','gst-18');" 2>/dev/null || true

import_rates() {
    local class_slug=$1
    local file=$2
    while IFS=, read -r country state postcode city rate name priority compound shipping cls; do
        [[ "$country" == "Country code" ]] && continue
        $WP wc tax_rate create \
            --country="$country" --state="$state" \
            --postcode="$postcode" --city="$city" \
            --rate="$rate" --name="$name" \
            --priority="$priority" --compound="$compound" \
            --shipping="$shipping" --class="$class_slug" \
            --user=1 >/dev/null 2>&1 || true
    done < "$file"
}
import_rates "gst-5"  tax-rates-gst-5.csv
import_rates "gst-18" tax-rates-gst-18.csv

echo "==> [6/9] Product categories"
PARENT=$($WP wc product_cat list --search="Tricycles" --field=id --user=1 2>/dev/null | head -1 || true)
if [[ -z "$PARENT" ]]; then
    $WP wc product_cat create --name="Tricycles" --user=1
    PARENT=$($WP wc product_cat list --search="Tricycles" --field=id --user=1 | head -1)
fi
for series in SIGMA AURA EAGLE ALEX ECOTECH RAMBO LEON LION CHARLIE EMMA HULK; do
    EXISTS=$($WP wc product_cat list --search="${series} series" --field=id --user=1 2>/dev/null | head -1 || true)
    [[ -z "$EXISTS" ]] && $WP wc product_cat create --name="${series} series" --parent="$PARENT" --user=1 || true
done

echo "==> [7/9] Pages (idempotent)"
ensure_page() {
    local title=$1; local content=$2
    local exists=$($WP post list --post_type=page --title="$title" --field=ID 2>/dev/null | head -1 || true)
    if [[ -z "$exists" ]]; then
        $WP post create --post_type=page --post_status=publish \
            --post_title="$title" --post_content="$content" --user=1
    fi
}
ensure_page "Request Quote"      "[luvron_quote_form]"
ensure_page "Become a Dealer"    "<p>Apply for a dealer or distributor account. Approval typically within 24 hours.</p>"
ensure_page "Bulk Order"         "[luvron_bulk_order]"
ensure_page "Dealer Dashboard"   "[luvron_dealer_dashboard]"
ensure_page "Catalogue"          "<p>Download our 2026 catalogue (PDF, ~10 MB).</p><p><a class='button alt' href='/wp-content/uploads/luvron-catalogue-feb-26.pdf'>Download Catalogue</a></p>"

echo "==> [8/9] Placing images & assets to uploads dir"
mkdir -p wp-content/uploads/luvron-products wp-content/uploads/luvron-testimonials
for src_base in ".." "."; do
    [[ -d "$src_base/product-images" ]] && cp $src_base/product-images/*.jpg wp-content/uploads/luvron-products/ 2>/dev/null || true
    [[ -d "$src_base/testimonials" ]] && cp $src_base/testimonials/*.jpg wp-content/uploads/luvron-testimonials/ 2>/dev/null || true
    [[ -f "$src_base/luvron-catalogue-feb-26-compressed.pdf" ]] && cp "$src_base/luvron-catalogue-feb-26-compressed.pdf" wp-content/uploads/luvron-catalogue-feb-26.pdf 2>/dev/null || true
done
echo "    Product images: wp-content/uploads/luvron-products/ ($(ls wp-content/uploads/luvron-products/ 2>/dev/null | wc -l) files)"
echo "    Testimonial images: wp-content/uploads/luvron-testimonials/ ($(ls wp-content/uploads/luvron-testimonials/ 2>/dev/null | wc -l) files)"
echo "    Catalogue PDF: wp-content/uploads/luvron-catalogue-feb-26.pdf"

echo "==> [9/9] Auto-seed via mu-plugin"
echo "    luvron-seed.php runs automatically on next admin page-load and creates:"
echo "      • 28 simple products + 16 variable products (32 variations)"
echo "      • Categories: Tricycles + 11 series subcategories"
echo "      • Pages: Home, Request Quote, Become a Dealer, Bulk Order,"
echo "              Dealer Dashboard, Catalogue, About, Contact"
echo "      • Primary navigation menu"
echo "    No CSV upload required. Visit Admin → Tools → Luvron Seed to verify."
echo "    The CSV files in this folder remain available as fallback/backup."

$WP rewrite flush --hard >/dev/null

echo
echo "============================================================"
echo "Bootstrap complete."
echo "============================================================"
echo "Manual follow-up:"
echo "  1. Drop mu-plugins/*.php into /wp-content/mu-plugins/ (create dir if missing)"
echo "  2. Visit /wp-admin once → seed runs automatically"
echo "     (or Tools → Luvron Seed → Run Seed Now)"
echo "  3. WP Admin → Settings → Luvron WhatsApp → enter Meta token (optional)"
echo "  4. WP Admin → WholesaleX → confirm dealer/distributor role mapping"
echo "  5. Edit luvron-invoice.php → replace bank constants with real account"
echo "  6. Edit luvron-schema.php → add real social URLs"
echo "  7. Test: register a guest → admin approves → login → confirm prices appear"
echo "============================================================"
