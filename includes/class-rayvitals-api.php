<?php
/**
 * RayVitals API Client
 *
 * Handles all communication with the RayVitals backend API
 *
 * @package RayVitals
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RayVitals_API {
    
    /**
     * API base URL
     *
     * @var string
     */
    private $api_url;
    
    /**
     * API key
     *
     * @var string
     */
    private $api_key;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_url = RAYVITALS_API_URL;
        $this->api_key = get_option('rayvitals_api_key', '');
    }
    
    /**
     * Make API request
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array $data Request data
     * @return array|WP_Error Response data or error
     */
    private function request($endpoint, $method = 'GET', $data = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('API key not configured. Please add your API key in settings.', 'rayvitals'));
        }
        
        $url = trailingslashit($this->api_url) . 'api/v1/' . ltrim($endpoint, '/');
        
        $args = array(
            'method'  => $method,
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
        );
        
        if (!empty($data) && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = wp_json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        if ($response_code >= 400) {
            $error_message = isset($response_data['detail']) ? $response_data['detail'] : 'API request failed';
            return new WP_Error('api_error', $error_message, array('status' => $response_code));
        }
        
        return $response_data;
    }
    
    /**
     * Start a new audit
     *
     * @param string $url URL to audit
     * @return array|WP_Error Audit data or error
     */
    public function start_audit($url) {
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return new WP_Error('invalid_url', __('Please enter a valid URL', 'rayvitals'));
        }
        
        // Check cache first
        $cache_key = 'rayvitals_audit_' . md5($url);
        $cached = get_transient($cache_key);
        
        if ($cached && get_option('rayvitals_enable_caching', true)) {
            return $cached;
        }
        
        // Start audit
        $response = $this->request('audit/start', 'POST', array(
            'url' => $url,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Save to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'rayvitals_audits';
        
        $wpdb->insert(
            $table_name,
            array(
                'audit_id' => $response['audit_id'],
                'url'      => $url,
                'status'   => 'pending',
            ),
            array('%s', '%s', '%s')
        );
        
        return $response;
    }
    
    /**
     * Get audit status
     *
     * @param string $audit_id Audit ID
     * @return array|WP_Error Status data or error
     */
    public function get_audit_status($audit_id) {
        return $this->request('audit/status/' . $audit_id);
    }
    
    /**
     * Get audit results
     *
     * @param string $audit_id Audit ID
     * @return array|WP_Error Results data or error
     */
    public function get_audit_results($audit_id) {
        $response = $this->request('audit/results/' . $audit_id);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Update database with results
        global $wpdb;
        $table_name = $wpdb->prefix . 'rayvitals_audits';
        
        $wpdb->update(
            $table_name,
            array(
                'status'              => 'completed',
                'overall_score'       => $response['overall_score'],
                'security_score'      => $response['security_score'],
                'performance_score'   => $response['performance_score'],
                'seo_score'          => $response['seo_score'],
                'accessibility_score' => $response['accessibility_score'],
                'ux_score'           => $response['ux_score'],
                'results'            => wp_json_encode($response['results']),
                'ai_summary'         => $response['ai_summary'],
                'completed_at'       => current_time('mysql'),
            ),
            array('audit_id' => $audit_id),
            array('%s', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%s'),
            array('%s')
        );
        
        // Cache results
        if (get_option('rayvitals_enable_caching', true)) {
            $cache_duration = get_option('rayvitals_cache_duration', 3600);
            set_transient('rayvitals_audit_' . md5($response['url']), $response, $cache_duration);
        }
        
        return $response;
    }
    
    /**
     * Validate API key
     *
     * @param string $api_key API key to validate
     * @return bool|WP_Error True if valid, error otherwise
     */
    public function validate_api_key($api_key) {
        // Temporarily set the API key
        $old_key = $this->api_key;
        $this->api_key = $api_key;
        
        // Try to validate the key
        $response = $this->request('auth/validate-key', 'POST');
        
        // Restore old key
        $this->api_key = $old_key;
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return true;
    }
    
    /**
     * Get API health status
     *
     * @return array|WP_Error Health data or error
     */
    public function get_health_status() {
        // Health endpoint doesn't require authentication
        $url = trailingslashit($this->api_url) . 'health';
        
        $response = wp_remote_get($url, array(
            'timeout' => 10,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return new WP_Error('api_unhealthy', __('API is not responding', 'rayvitals'));
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }
}