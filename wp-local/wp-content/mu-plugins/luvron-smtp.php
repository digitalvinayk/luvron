<?php
/**
 * Plugin Name: Luvron — SMTP Configuration
 * Description: Configures wp_mail to use Brevo (or any SMTP provider) so transactional emails
 *              don't land in spam. Reads credentials from wp-config.php constants — never stored
 *              in the database.
 * Version: 1.0.0
 *
 * Add to wp-config.php (above /* That's all *\/ comment):
 *   define('LUVRON_SMTP_HOST',     'smtp-relay.brevo.com');
 *   define('LUVRON_SMTP_PORT',     587);
 *   define('LUVRON_SMTP_USER',     'your-brevo-login@example.com');
 *   define('LUVRON_SMTP_PASS',     'your-smtp-key');
 *   define('LUVRON_SMTP_FROM',     'orders@luvron.in');
 *   define('LUVRON_SMTP_FROMNAME', 'Luvron Tricycles');
 *   define('LUVRON_SMTP_SECURE',   'tls'); // 'tls' or 'ssl'
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-smtp')) return;

if (!defined('LUVRON_SMTP_HOST') || !defined('LUVRON_SMTP_USER') || !defined('LUVRON_SMTP_PASS')) {
    add_action('admin_notices', function () {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-warning"><p><strong>Luvron SMTP not configured.</strong> '
           . 'Add LUVRON_SMTP_* constants to wp-config.php to ensure emails reach inboxes. '
           . '<a href="' . admin_url('admin.php?page=luvron-smtp-help') . '">Setup guide</a></p></div>';
    });
} else {
    add_action('phpmailer_init', function ($phpmailer) {
        $phpmailer->isSMTP();
        $phpmailer->Host       = LUVRON_SMTP_HOST;
        $phpmailer->Port       = defined('LUVRON_SMTP_PORT') ? LUVRON_SMTP_PORT : 587;
        $phpmailer->SMTPSecure = defined('LUVRON_SMTP_SECURE') ? LUVRON_SMTP_SECURE : 'tls';
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Username   = LUVRON_SMTP_USER;
        $phpmailer->Password   = LUVRON_SMTP_PASS;
        if (defined('LUVRON_SMTP_FROM')) {
            $phpmailer->From = LUVRON_SMTP_FROM;
        }
        if (defined('LUVRON_SMTP_FROMNAME')) {
            $phpmailer->FromName = LUVRON_SMTP_FROMNAME;
        }
    });

    add_filter('wp_mail_from', function ($email) {
        return defined('LUVRON_SMTP_FROM') ? LUVRON_SMTP_FROM : $email;
    });
    add_filter('wp_mail_from_name', function ($name) {
        return defined('LUVRON_SMTP_FROMNAME') ? LUVRON_SMTP_FROMNAME : $name;
    });
}

add_action('admin_menu', function () {
    add_management_page('Luvron SMTP', 'Luvron SMTP', 'manage_options',
        'luvron-smtp-help', 'luvron_smtp_help_page');
});

function luvron_smtp_help_page() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
        <h1>Luvron SMTP Setup</h1>
        <p>cPanel default mail has 30-50% spam-folder rate. Configure SMTP relay for reliable email delivery.</p>

        <h2>Recommended providers</h2>
        <table class="widefat striped">
            <thead><tr><th>Provider</th><th>Free tier</th><th>India deliverability</th><th>Setup</th></tr></thead>
            <tbody>
                <tr><td><strong>Brevo</strong> (formerly Sendinblue)</td><td>300/day forever</td><td>Excellent</td><td><a href="https://app.brevo.com/settings/keys/smtp" target="_blank">Get SMTP key</a></td></tr>
                <tr><td><strong>Amazon SES</strong></td><td>$0.10 per 1,000 emails</td><td>Excellent (use ap-south-1)</td><td><a href="https://aws.amazon.com/ses/" target="_blank">AWS console</a></td></tr>
                <tr><td><strong>MS365 Business</strong></td><td>If you have a paid M365 mailbox</td><td>Excellent</td><td>SMTP host smtp.office365.com:587</td></tr>
                <tr><td><strong>Postmark</strong></td><td>100/month free, then $1.25/1k</td><td>Excellent for transactional</td><td><a href="https://postmarkapp.com" target="_blank">Sign up</a></td></tr>
            </tbody>
        </table>

        <h2>Step 1 — Sign up &amp; verify domain</h2>
        <ol>
            <li>Sign up with one of the providers above.</li>
            <li>Add and verify <code>luvron.in</code> by adding the SPF/DKIM TXT records they provide to your domain DNS.</li>
            <li>Once verified, generate an SMTP API key.</li>
        </ol>

        <h2>Step 2 — Add to wp-config.php</h2>
        <p>SSH into your hosting and edit <code>~/public_html/wp-config.php</code>. Add the block below <em>above</em> the line that says <code>/* That's all, stop editing! */</code>:</p>
        <pre style="background:#f7f7f7;padding:16px;border-radius:4px;overflow-x:auto;"><code>define('LUVRON_SMTP_HOST',     'smtp-relay.brevo.com');
define('LUVRON_SMTP_PORT',     587);
define('LUVRON_SMTP_USER',     'your-login@example.com');
define('LUVRON_SMTP_PASS',     'your-smtp-key-here');
define('LUVRON_SMTP_FROM',     'orders@luvron.in');
define('LUVRON_SMTP_FROMNAME', 'Luvron Tricycles');
define('LUVRON_SMTP_SECURE',   'tls');</code></pre>

        <h2>Step 3 — Test</h2>
        <form method="post">
            <?php wp_nonce_field('luvron_smtp_test'); ?>
            <input type="email" name="test_to" value="<?php echo esc_attr(get_option('admin_email')); ?>" placeholder="Test recipient" style="width:300px;padding:8px;">
            <button class="button button-primary" name="luvron_smtp_test" value="1">Send Test Email</button>
        </form>
        <?php
        if (isset($_POST['luvron_smtp_test']) && check_admin_referer('luvron_smtp_test')) {
            $to = sanitize_email($_POST['test_to']);
            $r = wp_mail($to, 'Luvron SMTP test ' . date('H:i:s'),
                         "If you received this, your SMTP is working.\n\nSent from " . home_url());
            echo '<div class="notice notice-' . ($r ? 'success' : 'error') . '"><p>'
               . ($r ? "Sent to $to. Check inbox AND spam folder." : "Failed. Check error_log and credentials.")
               . '</p></div>';
        }
        ?>

        <h2>DNS records for deliverability</h2>
        <p>Add these to your luvron.in DNS (in addition to provider SPF/DKIM):</p>
        <pre style="background:#f7f7f7;padding:16px;border-radius:4px;">TXT @  "v=spf1 include:spf.brevo.com ~all"
TXT _dmarc  "v=DMARC1; p=none; rua=mailto:dmarc@luvron.in"</pre>
    </div>
    <?php
}
