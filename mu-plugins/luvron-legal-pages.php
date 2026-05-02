<?php
/**
 * Plugin Name: Luvron — Legal Pages (Privacy, Terms, Refund, Shipping)
 * Description: Auto-creates India-compliant legal pages and links them in the footer.
 *              Required by Consumer Protection Act 2019, Information Technology Act 2000,
 *              and ASCI guidelines for ecommerce.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-legal-pages')) return;

const LUVRON_LEGAL_FLAG = 'luvron_legal_pages_v1';

function luvron_legal_content() {
    $name    = 'Luvron Tricycles';
    $email   = 'orders@luvron.in';
    $phone   = '+91-9212389139';
    $addr    = 'OFF-D-337, T-04, Indraprastha Colony, Jawli Road, Loni, Ghaziabad UP – 201102';
    $founder = 'Rajneesh Kumar Pandey';

    return [
        'privacy-policy' => [
            'title'   => 'Privacy Policy',
            'content' => "<!-- wp:heading --><h2>Privacy Policy</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Last updated: " . date('d M Y') . "</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>$name (\"we\", \"us\", \"our\") respects your privacy. This policy describes how we collect, use, store, and protect personal and business information when you use luvron.in.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>1. Information We Collect</h3><!-- /wp:heading -->
<!-- wp:list --><ul>
<li><strong>Account data:</strong> Business name, GSTIN, owner name, email, phone, billing/shipping address.</li>
<li><strong>Transaction data:</strong> Order details, invoice records, payment references, IRN/QR codes.</li>
<li><strong>Technical data:</strong> IP address, browser, device, pages visited (via cookies and analytics).</li>
<li><strong>Communications:</strong> Quote requests, support messages, WhatsApp chats with us.</li>
</ul><!-- /wp:list -->
<!-- wp:heading {\"level\":3} --><h3>2. How We Use It</h3><!-- /wp:heading -->
<!-- wp:list --><ul>
<li>Process orders, generate GST-compliant tax invoices, and arrange dispatch.</li>
<li>Verify dealer/distributor eligibility before granting B2B pricing access.</li>
<li>Send transactional emails, SMS, and WhatsApp messages about your orders.</li>
<li>Comply with Indian tax law (GST, e-invoicing, TDS) and statutory record-keeping requirements.</li>
<li>Improve our website and prevent fraud.</li>
</ul><!-- /wp:list -->
<!-- wp:heading {\"level\":3} --><h3>3. Sharing</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>We share your information only with: payment gateway providers (Razorpay/Cashfree), logistics partners for dispatch, our chartered accountant for tax compliance, the GST IRP for e-invoice generation, and law enforcement when legally required. We do not sell your data.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>4. Cookies</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>We use cookies for: session management, cart persistence, analytics (Google Analytics), and remembering your preferences. You can disable cookies in your browser; some site features will not work without them.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>5. Data Retention</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Order and tax records are retained for 8 years as required by GST law. Account data is retained while your account is active and deleted within 90 days of closure (subject to legal retention requirements).</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>6. Your Rights</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>You may request access, correction, or deletion of your personal data by emailing $email. We respond within 30 days.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>7. Security</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>We use HTTPS, password hashing, and access controls. No system is 100% secure; report suspected breaches to $email immediately.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>8. Contact</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>$name<br>$addr<br>Phone: $phone<br>Email: $email</p><!-- /wp:paragraph -->",
        ],
        'terms-of-service' => [
            'title'   => 'Terms of Service',
            'content' => "<!-- wp:heading --><h2>Terms of Service</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Last updated: " . date('d M Y') . "</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>By accessing luvron.in or placing an order, you agree to these terms.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>1. Eligibility</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>This site is exclusively for B2B (business-to-business) transactions. Buyers must be authorised representatives of registered businesses (proprietorship, partnership, LLP, company) with valid GSTIN where applicable. Retail consumers should not register or place orders here.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>2. Pricing &amp; Quotation</h3><!-- /wp:heading -->
<!-- wp:list --><ul>
<li>Prices shown to logged-in dealers/distributors are exclusive of GST and freight unless explicitly stated.</li>
<li>Prices may change without notice. The price prevailing at the time of proforma confirmation is binding.</li>
<li>Minimum order quantity is one master carton per SKU. We may decline orders below MOQ.</li>
</ul><!-- /wp:list -->
<!-- wp:heading {\"level\":3} --><h3>3. Order Process</h3><!-- /wp:heading -->
<!-- wp:list --><ul>
<li>Orders pass through these states: Cart → Proforma (optional) → Confirmed → Paid → Dispatched → Delivered.</li>
<li>An order is binding only after we issue a GST tax invoice with IRN.</li>
<li>We reserve the right to cancel or partially fulfil orders due to stock shortage, regional restrictions, or suspected fraud.</li>
</ul><!-- /wp:list -->
<!-- wp:heading {\"level\":3} --><h3>4. Payment</h3><!-- /wp:heading -->
<!-- wp:list --><ul>
<li>Accepted methods: NEFT/RTGS, UPI, Razorpay (cards/netbanking/wallets), and credit terms for approved dealers.</li>
<li>For credit terms, payment is due within the agreed days from invoice date. Late payment attracts interest at 24% p.a.</li>
<li>All bank charges are to the buyer's account.</li>
</ul><!-- /wp:list -->
<!-- wp:heading {\"level\":3} --><h3>5. Dispatch &amp; Title</h3><!-- /wp:heading -->
<!-- wp:list --><ul>
<li>We dispatch ex-works from our Loni, Ghaziabad UP facility within 48 hours of confirmed payment, subject to stock availability.</li>
<li>Title and risk pass to the buyer on dispatch from our facility (Ex Works as per Incoterms 2020).</li>
<li>Freight is to the buyer's account unless agreed in writing.</li>
</ul><!-- /wp:list -->
<!-- wp:heading {\"level\":3} --><h3>6. Inspection &amp; Claims</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>The buyer must inspect goods on receipt and report transit damage or shortage in writing within 48 hours of delivery, with photographs and the transporter's POD. Claims raised after 48 hours will not be accepted.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>7. Warranty</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>We warrant goods against manufacturing defects for 6 months from invoice date. Warranty excludes wear-and-tear, misuse, accidental damage, and unauthorised modifications. Replacement is at our sole discretion against return of the defective unit.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>8. Limitation of Liability</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Our total liability for any order is limited to the invoice value of that order. We are not liable for consequential, indirect, or punitive damages including loss of profit or goodwill.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>9. Force Majeure</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>We are not liable for delay or failure caused by events beyond our reasonable control: natural disasters, strikes, pandemic restrictions, government action, war, transport disruption, or supply-chain failures.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>10. Intellectual Property</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>All content, images, designs, and product names on luvron.in are owned by $name. Use without written permission is prohibited.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>11. Jurisdiction</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>These terms are governed by the laws of India. Disputes are subject to the exclusive jurisdiction of courts in Ghaziabad, Uttar Pradesh.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>12. Changes</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>We may revise these terms; the revised version applies to orders placed after the revision date.</p><!-- /wp:paragraph -->",
        ],
        'refund-policy' => [
            'title'   => 'Refund &amp; Returns Policy',
            'content' => "<!-- wp:heading --><h2>Refund &amp; Returns Policy</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Last updated: " . date('d M Y') . "</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>1. B2B Sale</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>This is a B2B sale. Goods once sold and dispatched are not returnable except for the cases listed below.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>2. Damage in Transit</h3><!-- /wp:heading -->
<!-- wp:list --><ul>
<li>Report within <strong>48 hours of delivery</strong> with photographs of damaged carton and product, plus the transporter's POD.</li>
<li>Email orders@luvron.in with order number, photos, and quantity affected.</li>
<li>We will replace the damaged units in the next dispatch or issue a credit note (buyer's choice).</li>
</ul><!-- /wp:list -->
<!-- wp:heading {\"level\":3} --><h3>3. Manufacturing Defect</h3><!-- /wp:heading -->
<!-- wp:list --><ul>
<li>Reported within 6 months of invoice date.</li>
<li>Defective unit must be returned to our facility for inspection (freight to buyer's account).</li>
<li>If defect is confirmed, we replace the unit free of cost; freight back to buyer is to our account.</li>
</ul><!-- /wp:list -->
<!-- wp:heading {\"level\":3} --><h3>4. Wrong SKU Dispatched</h3><!-- /wp:heading -->
<!-- wp:list --><ul>
<li>Report within 48 hours of delivery.</li>
<li>We arrange reverse pickup at our cost and dispatch the correct SKU within 7 working days.</li>
</ul><!-- /wp:list -->
<!-- wp:heading {\"level\":3} --><h3>5. Refund Mode &amp; Timeline</h3><!-- /wp:heading -->
<!-- wp:list --><ul>
<li>Approved refunds are credited via the original payment method within 7-10 working days of approval.</li>
<li>Bank transfer / UPI refunds: 3-5 working days.</li>
<li>Razorpay refunds: 5-7 working days (subject to issuing bank).</li>
<li>For credit-term orders, the credit note is set off against the next invoice.</li>
</ul><!-- /wp:list -->
<!-- wp:heading {\"level\":3} --><h3>6. Non-Returnable Cases</h3><!-- /wp:heading -->
<!-- wp:list --><ul>
<li>Buyer's change of mind after dispatch.</li>
<li>Damage caused by mishandling at buyer's premises after delivery.</li>
<li>Product modifications, repainting, or part substitution by the buyer.</li>
<li>Sales returns after 6 months from invoice date.</li>
</ul><!-- /wp:list -->
<!-- wp:heading {\"level\":3} --><h3>7. GST Treatment</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Approved returns are processed by issuing a GST credit note (with IRN where applicable). Buyer must reverse the input tax credit claimed in the period the credit note is issued.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>8. Contact</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>For all return/refund queries: $email or $phone.</p><!-- /wp:paragraph -->",
        ],
        'shipping-policy' => [
            'title'   => 'Shipping Policy',
            'content' => "<!-- wp:heading --><h2>Shipping Policy</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Last updated: " . date('d M Y') . "</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>1. Origin</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>All orders dispatch ex-works from our manufacturing facility:<br>OFF-D-337, T-04, Indraprastha Colony, Jawli Road, Loni, Ghaziabad UP – 201102.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>2. Lead Time</h3><!-- /wp:heading -->
<!-- wp:list --><ul>
<li>Stock orders: dispatch within 48 hours of confirmed payment.</li>
<li>Mixed-SKU orders: 3-4 working days.</li>
<li>Out-of-stock SKUs: 7-15 working days (we'll inform you at proforma stage).</li>
</ul><!-- /wp:list -->
<!-- wp:heading {\"level\":3} --><h3>3. Transport &amp; Freight</h3><!-- /wp:heading -->
<!-- wp:list --><ul>
<li>We dispatch via leading B2B transporters (Delhivery, Gati, VRL, TCI, Safexpress) by surface or rail depending on destination.</li>
<li>Freight is to the buyer's account unless agreed in writing on the proforma.</li>
<li>Buyer may nominate their own transporter; we hand over goods at our gate with LR/POD.</li>
</ul><!-- /wp:list -->
<!-- wp:heading {\"level\":3} --><h3>4. Indicative Transit Times</h3><!-- /wp:heading -->
<!-- wp:list --><ul>
<li>Delhi NCR / UP / Haryana / Punjab: 1-3 days</li>
<li>Rajasthan / MP / Gujarat: 3-5 days</li>
<li>Maharashtra / Karnataka / Telangana: 5-7 days</li>
<li>Tamil Nadu / Kerala / North-East: 7-10 days</li>
<li>J&amp;K / Ladakh / Andaman: 10-15 days</li>
</ul><!-- /wp:list -->
<!-- wp:heading {\"level\":3} --><h3>5. Title &amp; Risk</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Per Incoterms 2020 \"Ex Works (EXW) Loni\", title and risk pass to the buyer when goods are loaded onto the transporter at our gate. Insurance is the buyer's responsibility.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>6. Tracking</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>LR (Lorry Receipt) number is shared via WhatsApp and the dealer dashboard once goods leave our facility. For surface transport, real-time tracking depends on the transporter's tech capability.</p><!-- /wp:paragraph -->
<!-- wp:heading {\"level\":3} --><h3>7. Restricted Destinations</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>We currently dispatch only within India. International orders require a separate export agreement.</p><!-- /wp:paragraph -->",
        ],
    ];
}

function luvron_seed_legal_pages() {
    $created = 0;
    foreach (luvron_legal_content() as $slug => $data) {
        if (get_page_by_path($slug)) continue;
        $id = wp_insert_post([
            'post_title'   => $data['title'],
            'post_name'    => $slug,
            'post_content' => $data['content'],
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
        if ($id) $created++;
    }
    update_option(LUVRON_LEGAL_FLAG, current_time('mysql'));

    update_option('wp_page_for_privacy_policy', get_page_by_path('privacy-policy')->ID ?? 0);

    return $created;
}

add_action('admin_init', function () {
    if (get_option(LUVRON_LEGAL_FLAG)) return;
    if (!class_exists('WooCommerce')) return;
    luvron_seed_legal_pages();
});

add_action('wp_footer', function () {
    if (is_admin()) return;
    $links = [
        ['/privacy-policy/', 'Privacy Policy'],
        ['/terms-of-service/', 'Terms of Service'],
        ['/refund-policy/', 'Refund &amp; Returns'],
        ['/shipping-policy/', 'Shipping Policy'],
        ['/contact/', 'Contact'],
    ];
    echo '<div class="luvron-legal-footer" style="text-align:center;padding:16px;background:#1a1a1a;color:#999;font-size:12px;">';
    foreach ($links as $i => $l) {
        if ($i > 0) echo ' &middot; ';
        echo '<a href="' . esc_url($l[0]) . '" style="color:#ccc;text-decoration:none;">' . $l[1] . '</a>';
    }
    echo '<br><span style="color:#666;">© ' . date('Y') . ' Luvron Tricycles · GSTIN 09GOCPP5350G1ZQ · Made in India</span>';
    echo '</div>';
});
