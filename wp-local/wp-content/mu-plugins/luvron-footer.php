<?php
/**
 * Plugin Name: Luvron — Branded Footer
 * Description: Replaces Astra's default copyright footer with a 4-column branded footer:
 *              brand block (logo white + NAP + GSTIN stamp + social), Catalogue links,
 *              For Dealers links, Legal links, plus Made-in-India bottom strip.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-footer')) return;

// Wipe Astra's copyright text via filter
add_filter('astra_footer_copyright', '__return_empty_string');
add_filter('option_astra-settings', function ($settings) {
    if (is_array($settings)) {
        $settings['footer-copyright-content'] = '';
        $settings['footer-builder-enabled'] = false;
    }
    return $settings;
});

// Hide Astra's default footer wrappers via CSS
add_action('wp_head', function () {
    echo '<style id="luvron-hide-astra-footer">
        .site-below-footer-wrap,
        .site-above-footer-wrap,
        .site-primary-footer-wrap,
        .ast-builder-grid-row[data-section*="footer"]:not(.luvron-footer-row) {
            display: none !important;
        }
        .site-footer { background: transparent !important; padding: 0 !important; }
    </style>';
}, 100);

// Inject our custom branded footer at the end of <footer>
add_action('astra_footer_content', 'luvron_render_branded_footer', 5);

// Fallback: if Astra hook isn't there, attach to wp_footer
add_action('wp_footer', function () {
    if (!did_action('luvron_footer_rendered')) {
        luvron_render_branded_footer();
    }
}, 5);

function luvron_render_branded_footer() {
    if (did_action('luvron_footer_rendered')) return;
    do_action('luvron_footer_rendered');

    $upload  = trailingslashit(wp_upload_dir()['baseurl']);
    $logo    = $upload . 'luvron-logo.png';
    $year    = date('Y');
    ?>
    <style id="luvron-footer-styles">
        .luvron-footer * { box-sizing: border-box; }
        .luvron-footer {
            background: #0f172a;
            color: rgba(255,255,255,.72);
            padding: 80px 0 24px;
            font-family: "Plus Jakarta Sans", -apple-system, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            position: relative;
            overflow: hidden;
        }
        .luvron-footer::before {
            content: "";
            position: absolute;
            top: -200px;
            left: -100px;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255,107,91,.12), transparent 70%);
            filter: blur(80px);
            pointer-events: none;
        }
        .luvron-footer::after {
            content: "";
            position: absolute;
            bottom: -200px;
            right: -100px;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(46,58,140,.18), transparent 70%);
            filter: blur(80px);
            pointer-events: none;
        }
        .luvron-footer .lvf-wrap {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 32px;
            position: relative;
            z-index: 1;
        }
        @media (max-width: 720px) { .luvron-footer .lvf-wrap { padding: 0 20px; } }

        .luvron-footer .lvf-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 56px;
            margin-bottom: 56px;
        }
        @media (max-width: 980px) {
            .luvron-footer .lvf-grid { grid-template-columns: 1fr 1fr; gap: 40px; }
            .luvron-footer .lvf-brand { grid-column: 1 / -1; }
        }
        @media (max-width: 540px) {
            .luvron-footer .lvf-grid { grid-template-columns: 1fr; gap: 32px; }
        }

        .luvron-footer .lvf-brand img {
            height: 56px;
            width: auto;
            margin-bottom: 24px;
            filter: brightness(0) invert(1);
        }
        .luvron-footer .lvf-nap {
            font-size: 14px;
            line-height: 1.75;
            margin-bottom: 24px;
            max-width: 360px;
        }
        .luvron-footer .lvf-nap strong {
            color: #ffffff;
            font-weight: 600;
        }
        .luvron-footer .lvf-nap a {
            color: rgba(255,255,255,.85);
            border-bottom: 1px dotted rgba(255,255,255,.3);
            text-decoration: none;
            padding-bottom: 1px;
            transition: color .15s, border-color .15s;
        }
        .luvron-footer .lvf-nap a:hover {
            color: #ffc93c;
            border-bottom-color: #ffc93c;
        }

        .luvron-footer .lvf-stamp {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            background: rgba(255,201,60,.08);
            border: 1.5px solid rgba(255,201,60,.5);
            border-radius: 8px;
            color: #ffc93c;
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .luvron-footer .lvf-stamp .lvf-stamp-sep {
            color: rgba(255,201,60,.4);
        }

        .luvron-footer .lvf-social {
            display: flex;
            gap: 10px;
        }
        .luvron-footer .lvf-social a {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all .2s ease;
        }
        .luvron-footer .lvf-social a:hover {
            background: #ff6b5b;
            transform: translateY(-2px);
        }
        .luvron-footer .lvf-social svg {
            width: 16px;
            height: 16px;
            fill: #ffffff;
        }

        .luvron-footer .lvf-col h4 {
            font-family: "Bricolage Grotesque", "Plus Jakarta Sans", sans-serif;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: -0.01em;
            margin: 0 0 18px;
            text-transform: none;
        }
        .luvron-footer .lvf-col ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .luvron-footer .lvf-col li {
            margin-bottom: 11px;
        }
        .luvron-footer .lvf-col a {
            color: rgba(255,255,255,.65);
            text-decoration: none;
            font-size: 14px;
            transition: color .15s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .luvron-footer .lvf-col a:hover {
            color: #ff6b5b;
        }
        .luvron-footer .lvf-col .lvf-badge {
            display: inline-block;
            background: rgba(255,107,91,.15);
            color: #ff8a7d;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 4px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .luvron-footer .lvf-bottom {
            border-top: 1px solid rgba(255,255,255,.1);
            padding-top: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 12.5px;
            color: rgba(255,255,255,.5);
        }
        .luvron-footer .lvf-bottom-left,
        .luvron-footer .lvf-bottom-right {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .luvron-footer .lvf-bottom-sep {
            opacity: 0.4;
        }
        .luvron-footer .lvf-flag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: rgba(255,255,255,.06);
            border-radius: 999px;
            font-weight: 500;
        }
        .luvron-footer .lvf-bottom a {
            color: rgba(255,255,255,.5);
            text-decoration: none;
            transition: color .15s;
        }
        .luvron-footer .lvf-bottom a:hover {
            color: #ffc93c;
        }
    </style>

    <footer class="luvron-footer" role="contentinfo">
        <div class="lvf-wrap">
            <div class="lvf-grid">

                <!-- Brand Block -->
                <div class="lvf-brand">
                    <a href="<?php echo esc_url(home_url('/')); ?>" aria-label="Luvron home">
                        <img src="<?php echo esc_url($logo); ?>" alt="Luvron Tricycles" loading="lazy">
                    </a>
                    <p class="lvf-nap">
                        <strong>Luvron Tricycles</strong><br>
                        OFF-D-337, T-04, Indraprastha Colony,<br>
                        Jawli Road, Loni, Ghaziabad UP – 201102<br><br>
                        <strong>Founder:</strong> Rajneesh Kumar Pandey<br>
                        <strong>Phone:</strong> <a href="tel:+919212389139">+91 9212 389 139</a><br>
                        <strong>Email:</strong> <a href="mailto:orders@luvron.in">orders@luvron.in</a>
                    </p>
                    <div class="lvf-stamp">
                        <span>GSTIN 09GOCPP5350G1ZQ</span>
                        <span class="lvf-stamp-sep">|</span>
                        <span>MADE IN INDIA</span>
                    </div>
                    <div class="lvf-social">
                        <a href="https://wa.me/919212389139" target="_blank" rel="noopener" aria-label="WhatsApp">
                            <svg viewBox="0 0 24 24"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2-.2-.3 0-.5.1-.6.1-.1.3-.4.5-.5.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5-.1-.1-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4s-1 1-1 2.5 1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.4zM12 2C6.5 2 2 6.5 2 12c0 1.8.5 3.5 1.3 5L2 22l5-1.3c1.5.8 3.2 1.3 5 1.3 5.5 0 10-4.5 10-10S17.5 2 12 2z"/></svg>
                        </a>
                        <a href="#" target="_blank" rel="noopener" aria-label="Facebook">
                            <svg viewBox="0 0 24 24"><path d="M22 12a10 10 0 10-11.6 9.9V14.9H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.3v7C18.7 21.1 22 16.9 22 12z"/></svg>
                        </a>
                        <a href="#" target="_blank" rel="noopener" aria-label="Instagram">
                            <svg viewBox="0 0 24 24"><path d="M12 2.2c3.2 0 3.6 0 4.8.1 1.2.1 1.8.2 2.2.4.6.2 1 .5 1.4.9.4.4.7.9.9 1.4.2.5.4 1.1.4 2.2.1 1.2.1 1.6.1 4.8s0 3.6-.1 4.8c-.1 1.2-.2 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.9.7-1.4.9-.5.2-1.1.4-2.2.4-1.2.1-1.6.1-4.8.1s-3.6 0-4.8-.1c-1.2-.1-1.8-.2-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.9-.9-1.4-.2-.5-.4-1.1-.4-2.2-.1-1.2-.1-1.6-.1-4.8s0-3.6.1-4.8c.1-1.2.2-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.9-.7 1.4-.9.5-.2 1.1-.4 2.2-.4 1.2-.1 1.6-.1 4.8-.1zm0 5.5a4.3 4.3 0 100 8.6 4.3 4.3 0 000-8.6zm0 7.1a2.8 2.8 0 110-5.6 2.8 2.8 0 010 5.6zm5.5-7.3a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                        </a>
                        <a href="mailto:orders@luvron.in" aria-label="Email">
                            <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Catalogue -->
                <div class="lvf-col">
                    <h4>Catalogue</h4>
                    <ul>
                        <li><a href="/shop/">All 44 SKUs</a></li>
                        <li><a href="/shop/?tier=musical">Musical (18%)</a></li>
                        <li><a href="/shop/?tier=normal">Normal (5%)</a></li>
                        <li><a href="/product-category/sigma-series/">SIGMA Series</a></li>
                        <li><a href="/product-category/aura-series/">AURA Series</a></li>
                        <li><a href="/product-category/rambo-series/">RAMBO Series</a></li>
                        <li><a href="<?php echo esc_url($upload); ?>luvron-catalogue-feb-26.pdf" target="_blank">Download PDF</a></li>
                    </ul>
                </div>

                <!-- For Dealers -->
                <div class="lvf-col">
                    <h4>For Dealers</h4>
                    <ul>
                        <li><a href="/become-a-dealer/">Become a Dealer</a></li>
                        <li><a href="/wp-login.php">Dealer Login</a></li>
                        <li><a href="/bulk-order/">Bulk Order Grid<span class="lvf-badge">Pro</span></a></li>
                        <li><a href="/dealer-dashboard/">Dealer Dashboard</a></li>
                        <li><a href="/request-quote/">Request a Quote</a></li>
                        <li><a href="/about-luvron/">About Us</a></li>
                        <li><a href="/contact/">Contact</a></li>
                    </ul>
                </div>

                <!-- Legal -->
                <div class="lvf-col">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="/privacy-policy/">Privacy Policy</a></li>
                        <li><a href="/terms-of-service/">Terms of Service</a></li>
                        <li><a href="/refund-policy/">Refund &amp; Returns</a></li>
                        <li><a href="/shipping-policy/">Shipping Policy</a></li>
                    </ul>
                </div>

            </div>

            <div class="lvf-bottom">
                <div class="lvf-bottom-left">
                    <span>© <?php echo $year; ?> Luvron Tricycles · All rights reserved</span>
                    <span class="lvf-bottom-sep">·</span>
                    <span>CIN pending</span>
                </div>
                <div class="lvf-bottom-right">
                    <span class="lvf-flag">🇮🇳 Built in Loni, Ghaziabad since 2022</span>
                    <span class="lvf-bottom-sep">·</span>
                    <a href="https://wa.me/919212389139" target="_blank" rel="noopener">WhatsApp Founder</a>
                </div>
            </div>
        </div>
    </footer>
    <?php
}
