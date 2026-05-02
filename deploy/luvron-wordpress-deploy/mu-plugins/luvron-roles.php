<?php
/**
 * Plugin Name: Luvron — B2B Roles
 * Description: Registers dealer, distributor, and pending_b2b user roles.
 * Author: Luvron
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;
if (function_exists('luvron_disabled') && luvron_disabled('luvron-roles')) return;

register_activation_hook(__FILE__, 'luvron_register_roles');

function luvron_register_roles() {
    add_role('dealer', 'Dealer', [
        'read'                  => true,
        'view_admin_dashboard'  => false,
    ]);
    add_role('distributor', 'Distributor', [
        'read'                  => true,
        'view_admin_dashboard'  => false,
    ]);
    add_role('pending_b2b', 'Pending B2B', [
        'read'                  => true,
        'view_admin_dashboard'  => false,
    ]);
}

add_action('init', function () {
    if (!get_role('dealer'))       luvron_register_roles();
    if (!get_role('distributor'))  luvron_register_roles();
    if (!get_role('pending_b2b'))  luvron_register_roles();
});

add_action('admin_bar_menu', function ($wp_admin_bar) {
    if (!is_admin() && (current_user_can('dealer') || current_user_can('distributor') || current_user_can('pending_b2b'))) {
        $wp_admin_bar->remove_node('wp-logo');
    }
}, 999);

add_filter('show_admin_bar', function ($show) {
    if (current_user_can('dealer') || current_user_can('distributor') || current_user_can('pending_b2b')) {
        return false;
    }
    return $show;
});

add_action('user_register', function ($user_id) {
    $user = new WP_User($user_id);
    if (empty($user->roles) || in_array('subscriber', (array) $user->roles, true)) {
        $user->set_role('pending_b2b');
    }
    $admin_email = get_option('admin_email');
    $subject = 'New B2B Application: ' . $user->display_name;
    $body    = "A new dealer/distributor application has been submitted.\n\n"
             . "Name:  {$user->display_name}\n"
             . "Email: {$user->user_email}\n"
             . "Review: " . admin_url("user-edit.php?user_id={$user_id}");
    wp_mail($admin_email, $subject, $body);
});

add_action('set_user_role', function ($user_id, $new_role, $old_roles) {
    if (in_array($new_role, ['dealer', 'distributor'], true)
        && in_array('pending_b2b', (array) $old_roles, true)) {
        $user = get_user_by('id', $user_id);
        $subject = 'Your Luvron ' . ucfirst($new_role) . ' account is approved';
        $body    = "Hi {$user->display_name},\n\n"
                 . "Your Luvron " . ucfirst($new_role) . " account is approved. "
                 . "You can now log in and view bulk pricing.\n\n"
                 . "Login: " . wp_login_url() . "\n\n"
                 . "Thanks,\nTeam Luvron";
        wp_mail($user->user_email, $subject, $body);
    }
}, 10, 3);
