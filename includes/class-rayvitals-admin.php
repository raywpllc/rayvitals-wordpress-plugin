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
        
        // AJAX handlers for API key generation
        add_action('wp_ajax_rayvitals_generate_api_key', array($this, 'ajax_generate_api_key'));
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
        include RAYVITALS_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * AJAX handler for generating API keys
     */
    public function ajax_generate_api_key() {
        error_log('RayVitals WP: Starting API key generation');
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'rayvitals_generate_api_key')) {
            error_log('RayVitals WP: Nonce verification failed');
            wp_send_json_error(array('message' => __('Security check failed', 'rayvitals')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            error_log('RayVitals WP: User lacks manage_options capability');
            wp_send_json_error(array('message' => __('Insufficient permissions', 'rayvitals')));
        }
        
        try {
            // Call admin endpoint to generate API key
            $admin_token = defined('RAYVITALS_ADMIN_TOKEN') ? RAYVITALS_ADMIN_TOKEN : 'your-secret-admin-token-here';
            $api_url = RAYVITALS_API_URL . '/api/admin/api-keys';
            
            error_log('RayVitals WP: Admin token defined: ' . (defined('RAYVITALS_ADMIN_TOKEN') ? 'YES' : 'NO'));
            error_log('RayVitals WP: Admin token length: ' . strlen($admin_token));
            error_log('RayVitals WP: API URL: ' . $api_url);
            
            $request_body = json_encode(array(
                'key_name' => 'WordPress Plugin - ' . get_bloginfo('name'),
                'rate_limit' => 120,
                'monthly_limit' => 10000
            ));
            
            error_log('RayVitals WP: Request body: ' . $request_body);
            
            $response = wp_remote_post($api_url, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $admin_token,
                    'Content-Type' => 'application/json'
                ),
                'body' => $request_body,
                'timeout' => 30
            ));
            
            error_log('RayVitals WP: Raw response: ' . print_r($response, true));
            
            if (is_wp_error($response)) {
                error_log('RayVitals WP: WP Error: ' . $response->get_error_message());
                wp_send_json_error(array('message' => __('Failed to connect to RayVitals API: ', 'rayvitals') . $response->get_error_message()));
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            error_log('RayVitals WP: Response code: ' . $response_code);
            error_log('RayVitals WP: Response body: ' . $response_body);
            
            if ($response_code !== 200) {
                error_log('RayVitals WP: Non-200 response code: ' . $response_code);
                $error_data = json_decode($response_body, true);
                error_log('RayVitals WP: Error data: ' . print_r($error_data, true));
                $error_message = isset($error_data['detail']) ? $error_data['detail'] : __('Unknown API error', 'rayvitals');
                wp_send_json_error(array('message' => $error_message));
            }
            
            $api_key_data = json_decode($response_body, true);
            error_log('RayVitals WP: Decoded API key data: ' . print_r($api_key_data, true));
            
            if (!isset($api_key_data['api_key'])) {
                error_log('RayVitals WP: No api_key in response');
                wp_send_json_error(array('message' => __('Invalid API response', 'rayvitals')));
            }
            
            error_log('RayVitals WP: Successfully generated API key');
            
            // Return the generated API key
            wp_send_json_success(array(
                'api_key' => $api_key_data['api_key'],
                'key_name' => $api_key_data['key_name'],
                'rate_limit' => $api_key_data['rate_limit'],
                'monthly_limit' => $api_key_data['monthly_limit']
            ));
            
        } catch (Exception $e) {
            error_log('RayVitals WP: Exception caught: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Error generating API key: ', 'rayvitals') . $e->getMessage()));
        }
    }
}