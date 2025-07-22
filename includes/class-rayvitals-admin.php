<?php
/**
 * RayVitals Admin Interface
 *
 * Handles all admin functionality and UI
 *
 * @package RayVitals
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RayVitals_Admin {
    
    /**
     * API client instance
     *
     * @var RayVitals_API
     */
    private $api;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api = new RayVitals_API();
        
        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_filter('admin_body_class', array($this, 'admin_body_class'));
    }
    
    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'rayvitals') === false) {
            return;
        }
        
        // Styles
        wp_enqueue_style(
            'rayvitals-admin',
            RAYVITALS_PLUGIN_URL . 'admin/css/rayvitals-admin.css',
            array(),
            RAYVITALS_VERSION
        );
        
        // Scripts
        wp_enqueue_script(
            'rayvitals-admin',
            RAYVITALS_PLUGIN_URL . 'admin/js/rayvitals-admin.js',
            array('jquery', 'wp-util'),
            RAYVITALS_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('rayvitals-admin', 'rayvitals', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('rayvitals_ajax'),
            'strings'  => array(
                'starting_audit'    => __('Starting audit...', 'rayvitals'),
                'checking_status'   => __('Checking status...', 'rayvitals'),
                'audit_complete'    => __('Audit complete!', 'rayvitals'),
                'audit_failed'      => __('Audit failed', 'rayvitals'),
                'please_wait'       => __('Please wait...', 'rayvitals'),
                'error_occurred'    => __('An error occurred', 'rayvitals'),
                'confirm_delete'    => __('Are you sure you want to delete this audit?', 'rayvitals'),
            ),
        ));
        
        // Chart.js for displaying scores
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            array(),
            '4.4.0',
            true
        );
    }
    
    /**
     * Show admin notices
     */
    public function admin_notices() {
        // Check if API key is configured
        if (empty(get_option('rayvitals_api_key'))) {
            $screen = get_current_screen();
            if ($screen && strpos($screen->id, 'rayvitals') !== false && $screen->id !== 'rayvitals_page_rayvitals-settings') {
                echo '<div class="notice notice-warning">';
                echo '<p>' . sprintf(
                    __('RayVitals API key not configured. Please <a href="%s">add your API key</a> to start auditing websites.', 'rayvitals'),
                    admin_url('admin.php?page=rayvitals-settings')
                ) . '</p>';
                echo '</div>';
            }
        }
        
        // Check API health
        $health_transient = get_transient('rayvitals_api_health');
        if ($health_transient === false) {
            $health = $this->api->get_health_status();
            set_transient('rayvitals_api_health', $health, 300); // Cache for 5 minutes
            
            if (is_wp_error($health)) {
                $screen = get_current_screen();
                if ($screen && strpos($screen->id, 'rayvitals') !== false) {
                    echo '<div class="notice notice-error">';
                    echo '<p>' . __('RayVitals API is currently unavailable. Please try again later.', 'rayvitals') . '</p>';
                    echo '</div>';
                }
            }
        }
    }
    
    /**
     * Add custom admin body classes
     *
     * @param string $classes Current body classes
     * @return string Modified body classes
     */
    public function admin_body_class($classes) {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'rayvitals') !== false) {
            $classes .= ' rayvitals-admin';
        }
        return $classes;
    }
    
    /**
     * Render dashboard page
     */
    public static function render_dashboard() {
        include RAYVITALS_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Render new audit page
     */
    public static function render_new_audit() {
        include RAYVITALS_PLUGIN_DIR . 'admin/views/new-audit.php';
    }
    
    /**
     * Render history page
     */
    public static function render_history() {
        include RAYVITALS_PLUGIN_DIR . 'admin/views/history.php';
    }
    
    /**
     * Render settings page
     */
    public static function render_settings() {
        // Handle form submission
        if (isset($_POST['rayvitals_save_settings']) && check_admin_referer('rayvitals_settings')) {
            $api_key = sanitize_text_field($_POST['rayvitals_api_key']);
            
            // Validate API key if provided
            if (!empty($api_key)) {
                $api = new RayVitals_API();
                $validation = $api->validate_api_key($api_key);
                
                if (is_wp_error($validation)) {
                    add_settings_error(
                        'rayvitals_settings',
                        'invalid_api_key',
                        __('Invalid API key. Please check your key and try again.', 'rayvitals')
                    );
                } else {
                    update_option('rayvitals_api_key', $api_key);
                    add_settings_error(
                        'rayvitals_settings',
                        'settings_saved',
                        __('Settings saved successfully.', 'rayvitals'),
                        'success'
                    );
                }
            } else {
                update_option('rayvitals_api_key', '');
            }
            
            // Save other settings
            update_option('rayvitals_enable_caching', !empty($_POST['rayvitals_enable_caching']));
            update_option('rayvitals_cache_duration', absint($_POST['rayvitals_cache_duration']));
        }
        
        include RAYVITALS_PLUGIN_DIR . 'admin/views/settings.php';
    }
}