<?php
/**
 * Plugin Name: Luvron — Schema & NAP
 * Description: Outputs Organization JSON-LD on every page with locked NAP data.
 *              Outputs Product JSON-LD on product pages without leaking pricing to guests.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-schema')) return;

add_action('wp_head', function () {
    $org = [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => 'Luvron',
        'alternateName' => 'Luvron Tricycles',
        'url'      => home_url('/'),
        'logo'     => home_url('/wp-content/uploads/luvron-logo.png'),
        'description' => 'Trusted Indian manufacturer of kids tricycles for B2B dealers and distributors.',
        'address'  => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => 'OFF-D-337, T-04, Indraprastha Colony, Jawli Road, Loni',
            'addressLocality' => 'Ghaziabad',
            'addressRegion'   => 'Uttar Pradesh',
            'postalCode'      => '201102',
            'addressCountry'  => 'IN',
        ],
        'contactPoint' => [
            '@type'       => 'ContactPoint',
            'telephone'   => '+91-9212389139',
            'contactType' => 'sales',
            'areaServed'  => 'IN',
            'availableLanguage' => ['English', 'Hindi'],
        ],
        'founder' => [
            '@type' => 'Person',
            'name'  => 'Rajneesh Kumar Pandey',
        ],
        'sameAs' => [
            'https://www.facebook.com/luvron',
            'https://www.instagram.com/luvron',
        ],
    ];
    echo "\n<script type=\"application/ld+json\">"
       . wp_json_encode($org, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
       . "</script>\n";
}, 5);

add_action('wp_head', function () {
    if (!is_singular('product')) return;
    global $post;
    $product = wc_get_product($post->ID);
    if (!$product) return;

    $data = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Product',
        'name'        => $product->get_name(),
        'sku'         => $product->get_sku(),
        'description' => wp_strip_all_tags($product->get_short_description() ?: $product->get_description()),
        'image'       => wp_get_attachment_url($product->get_image_id()),
        'brand'       => ['@type' => 'Brand', 'name' => 'Luvron'],
        'category'    => 'Kids Tricycles',
        'offers'      => [
            '@type'         => 'Offer',
            'priceCurrency' => 'INR',
            'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url'           => get_permalink($product->get_id()),
            'priceSpecification' => [
                '@type'         => 'PriceSpecification',
                'priceCurrency' => 'INR',
                'description'   => 'B2B wholesale pricing — login required',
            ],
        ],
    ];
    echo "\n<script type=\"application/ld+json\">"
       . wp_json_encode($data, JSON_UNESCAPED_SLASHES)
       . "</script>\n";
});

add_action('wp_footer', function () {
    if (!is_front_page() && !is_page('contact')) return;
    ?>
    <div class="luvron-nap" style="text-align:center;padding:24px 16px;background:#f7f7f7;font-size:14px;line-height:1.6;">
        <strong>Luvron</strong> — A Trusted Brand For Your Child's Fun<br>
        OFF-D-337, T-04, Indraprastha Colony, Jawli Road, Loni, Ghaziabad UP – 201102<br>
        📞 <a href="tel:+919212389139">+91-9212389139</a> ·
        📱 <a href="https://wa.me/919212389139" target="_blank" rel="noopener">WhatsApp</a> ·
        Rajneesh Kumar Pandey
    </div>
    <?php
});
