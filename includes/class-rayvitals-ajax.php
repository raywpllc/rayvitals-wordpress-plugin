<?php
/**
 * RayVitals AJAX Handler
 *
 * Handles all AJAX requests
 *
 * @package RayVitals
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RayVitals_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        // AJAX actions
        add_action('wp_ajax_rayvitals_start_audit', array($this, 'start_audit'));
        add_action('wp_ajax_rayvitals_check_status', array($this, 'check_status'));
        add_action('wp_ajax_rayvitals_get_results', array($this, 'get_results'));
        add_action('wp_ajax_rayvitals_delete_audit', array($this, 'delete_audit'));
        add_action('wp_ajax_rayvitals_get_statistics', array($this, 'get_statistics'));
    }
    
    /**
     * Verify AJAX nonce
     */
    private function verify_nonce() {
        if (!check_ajax_referer('rayvitals_ajax', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'rayvitals'));
        }
    }
    
    /**
     * Start a new audit
     */
    public function start_audit() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'rayvitals'));
        }
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        if (empty($url)) {
            wp_send_json_error(__('Please provide a URL', 'rayvitals'));
        }
        
        $api = new RayVitals_API();
        $response = $api->start_audit($url);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * Check audit status
     */
    public function check_status() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'rayvitals'));
        }
        
        $audit_id = isset($_POST['audit_id']) ? sanitize_text_field($_POST['audit_id']) : '';
        
        if (empty($audit_id)) {
            wp_send_json_error(__('Invalid audit ID', 'rayvitals'));
        }
        
        $api = new RayVitals_API();
        $response = $api->get_audit_status($audit_id);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        // If completed, fetch full results
        if ($response['status'] === 'completed') {
            $results = $api->get_audit_results($audit_id);
            if (!is_wp_error($results)) {
                $response = $results;
            }
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * Get audit results
     */
    public function get_results() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'rayvitals'));
        }
        
        $audit_id = isset($_POST['audit_id']) ? sanitize_text_field($_POST['audit_id']) : '';
        
        if (empty($audit_id)) {
            wp_send_json_error(__('Invalid audit ID', 'rayvitals'));
        }
        
        // First check local database
        $audit = RayVitals_Audit::get_by_id($audit_id);
        
        if ($audit && $audit->status === 'completed' && !empty($audit->results)) {
            $results = json_decode($audit->results, true);
            $response = array(
                'id'                  => $audit->audit_id,
                'url'                 => $audit->url,
                'status'              => $audit->status,
                'overall_score'       => $audit->overall_score,
                'security_score'      => $audit->security_score,
                'performance_score'   => $audit->performance_score,
                'seo_score'          => $audit->seo_score,
                'accessibility_score' => $audit->accessibility_score,
                'ux_score'           => $audit->ux_score,
                'results'            => $results,
                'ai_summary'         => $audit->ai_summary,
            );
            wp_send_json_success($response);
        }
        
        // Fetch from API
        $api = new RayVitals_API();
        $response = $api->get_audit_results($audit_id);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * Delete an audit
     */
    public function delete_audit() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'rayvitals'));
        }
        
        $audit_id = isset($_POST['audit_id']) ? sanitize_text_field($_POST['audit_id']) : '';
        
        if (empty($audit_id)) {
            wp_send_json_error(__('Invalid audit ID', 'rayvitals'));
        }
        
        if (RayVitals_Audit::delete($audit_id)) {
            wp_send_json_success(__('Audit deleted successfully', 'rayvitals'));
        } else {
            wp_send_json_error(__('Failed to delete audit', 'rayvitals'));
        }
    }
    
    /**
     * Get statistics
     */
    public function get_statistics() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'rayvitals'));
        }
        
        $stats = RayVitals_Audit::get_statistics();
        wp_send_json_success($stats);
    }
}