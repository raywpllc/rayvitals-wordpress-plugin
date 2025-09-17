<?php
/**
 * Plugin Name: RayVitals Website Auditor
 * Plugin URI: https://rayvitals.com
 * Description: Comprehensive website audit tool powered by RayVitals API - analyze security, performance, SEO, accessibility, and UX
 * Version: 1.0.0
 * Author: RayWP LLC
 * Author URI: https://raywp.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rayvitals
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RAYVITALS_VERSION', '1.0.0');
define('RAYVITALS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RAYVITALS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RAYVITALS_PLUGIN_FILE', __FILE__);
define('RAYVITALS_API_URL', 'https://rayvitals-backend-xwq86.ondigitalocean.app');
define('RAYVITALS_ADMIN_TOKEN', 'v_admin_erj9C5HfMDA8Tu9d_FH3RD9VENhnS99f3L_9yIo9d9w');

// Activation hook
register_activation_hook(__FILE__, 'rayvitals_activate');
function rayvitals_activate() {
    // Create database tables if needed
    rayvitals_create_tables();
    
    // Set default options
    add_option('rayvitals_api_key', '');
    add_option('rayvitals_enable_caching', true);
    add_option('rayvitals_cache_duration', 3600); // 1 hour
    
    // Schedule cleanup cron
    if (!wp_next_scheduled('rayvitals_cleanup_old_audits')) {
        wp_schedule_event(time(), 'daily', 'rayvitals_cleanup_old_audits');
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'rayvitals_deactivate');
function rayvitals_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook('rayvitals_cleanup_old_audits');
}

// Create database tables
function rayvitals_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Create audits table
    $audits_table = $wpdb->prefix . 'rayvitals_audits';
    $sql = "CREATE TABLE IF NOT EXISTS $audits_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        audit_id varchar(255) NOT NULL,
        url varchar(500) NOT NULL,
        email varchar(255) DEFAULT NULL,
        status varchar(50) NOT NULL,
        overall_score float DEFAULT NULL,
        security_score float DEFAULT NULL,
        performance_score float DEFAULT NULL,
        seo_score float DEFAULT NULL,
        accessibility_score float DEFAULT NULL,
        ux_score float DEFAULT NULL,
        results longtext DEFAULT NULL,
        ai_summary longtext DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        completed_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY audit_id (audit_id),
        KEY url (url),
        KEY email (email),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Create leads table
    $leads_table = $wpdb->prefix . 'rayvitals_leads';
    $leads_sql = "CREATE TABLE IF NOT EXISTS $leads_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        audit_id varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        ip_address varchar(45) DEFAULT NULL,
        user_agent text DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY audit_email (audit_id, email),
        KEY email (email),
        KEY audit_id (audit_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    dbDelta($leads_sql);
}

// Load plugin classes
add_action('plugins_loaded', 'rayvitals_load_plugin');
function rayvitals_load_plugin() {
    // Load text domain
    load_plugin_textdomain('rayvitals', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Include required files
    require_once RAYVITALS_PLUGIN_DIR . 'includes/class-rayvitals-api.php';
    require_once RAYVITALS_PLUGIN_DIR . 'includes/class-rayvitals-admin.php';
    require_once RAYVITALS_PLUGIN_DIR . 'includes/class-rayvitals-audit.php';
    require_once RAYVITALS_PLUGIN_DIR . 'includes/class-rayvitals-ajax.php';
    require_once RAYVITALS_PLUGIN_DIR . 'includes/class-rayvitals-shortcodes.php';
    
    // Initialize admin if in admin area
    if (is_admin()) {
        new RayVitals_Admin();
    }
    
    // Initialize shortcodes for public area
    new RayVitals_Shortcodes();
    
    // Initialize AJAX handlers
    new RayVitals_Ajax();
}

// Add admin menu
add_action('admin_menu', 'rayvitals_admin_menu');
function rayvitals_admin_menu() {
    add_menu_page(
        __('RayVitals', 'rayvitals'),
        __('RayVitals', 'rayvitals'),
        'manage_options',
        'rayvitals',
        array('RayVitals_Admin', 'render_dashboard'),
        'dashicons-chart-area',
        30
    );
    
    add_submenu_page(
        'rayvitals',
        __('New Audit', 'rayvitals'),
        __('New Audit', 'rayvitals'),
        'manage_options',
        'rayvitals-new-audit',
        array('RayVitals_Admin', 'render_new_audit')
    );
    
    add_submenu_page(
        'rayvitals',
        __('Audit History', 'rayvitals'),
        __('Audit History', 'rayvitals'),
        'manage_options',
        'rayvitals-history',
        array('RayVitals_Admin', 'render_history')
    );
    
    add_submenu_page(
        'rayvitals',
        __('Settings', 'rayvitals'),
        __('Settings', 'rayvitals'),
        'manage_options',
        'rayvitals-settings',
        array('RayVitals_Admin', 'render_settings')
    );
}


// Cleanup old audits
add_action('rayvitals_cleanup_old_audits', 'rayvitals_cleanup_old_audits_handler');
function rayvitals_cleanup_old_audits_handler() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rayvitals_audits';
    
    // Delete audits older than 30 days
    $wpdb->query($wpdb->prepare(
        "DELETE FROM $table_name WHERE created_at < %s",
        date('Y-m-d H:i:s', strtotime('-30 days'))
    ));
}