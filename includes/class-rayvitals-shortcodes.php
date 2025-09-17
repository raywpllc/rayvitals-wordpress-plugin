<?php
/**
 * RayVitals Shortcodes Handler
 *
 * Manages all public-facing shortcodes for the plugin
 *
 * @package RayVitals
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RayVitals_Shortcodes {
    
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
        $this->init();
    }
    
    /**
     * Initialize shortcodes
     */
    public function init() {
        add_shortcode('rayvitals_audit_form', array($this, 'render_audit_form'));
        add_shortcode('rayvitals_email_capture', array($this, 'render_email_capture'));
        add_shortcode('rayvitals_results', array($this, 'render_results'));
        
        // Enqueue public scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_rayvitals_start_audit', array($this, 'ajax_start_audit'));
        add_action('wp_ajax_nopriv_rayvitals_start_audit', array($this, 'ajax_start_audit'));
        
        add_action('wp_ajax_rayvitals_submit_email', array($this, 'ajax_submit_email'));
        add_action('wp_ajax_nopriv_rayvitals_submit_email', array($this, 'ajax_submit_email'));
        
        add_action('wp_ajax_rayvitals_check_audit_status', array($this, 'ajax_check_audit_status'));
        add_action('wp_ajax_nopriv_rayvitals_check_audit_status', array($this, 'ajax_check_audit_status'));
    }
    
    /**
     * Enqueue public scripts and styles
     */
    public function enqueue_public_scripts() {
        global $post;
        
        // Always load on frontend for now to ensure styles are available
        // TODO: Optimize this later to only load when shortcodes are present
        if (!is_admin()) {
            // Styles
            wp_enqueue_style(
                'rayvitals-public',
                RAYVITALS_PLUGIN_URL . 'public/css/rayvitals-public.css',
                array(),
                RAYVITALS_VERSION
            );
            
            // Scripts
            wp_enqueue_script(
                'rayvitals-public',
                RAYVITALS_PLUGIN_URL . 'public/js/rayvitals-public.js',
                array('jquery'),
                RAYVITALS_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script('rayvitals-public', 'rayvitals_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rayvitals-public-nonce'),
                'scanning_text' => __('Analyzing your website...', 'rayvitals'),
                'error_text' => __('An error occurred. Please try again.', 'rayvitals')
            ));
            
            // Chart.js - load on all pages for now
            wp_enqueue_script(
                'chart-js',
                RAYVITALS_PLUGIN_URL . 'admin/js/lib/chart.min.js',
                array(),
                '3.9.1',
                true
            );
        }
    }
    
    /**
     * Render audit form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_audit_form($atts) {
        $atts = shortcode_atts(array(
            'button_text' => __('Analyze Website', 'rayvitals'),
            'placeholder' => __('Enter your website URL', 'rayvitals')
        ), $atts);
        
        ob_start();
        ?>
        <div class="rayvitals-audit-form-container">
            <form id="rayvitals-audit-form" class="rayvitals-form">
                <div class="rayvitals-form-group">
                    <input 
                        type="url" 
                        name="website_url" 
                        class="rayvitals-input"
                        placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                        required
                    />
                    <button type="submit" class="rayvitals-button rayvitals-button-primary">
                        <span class="button-text">Free Audit</span>
                        <span class="button-spinner" style="display: none;">
                            <span class="spinner"></span>
                        </span>
                    </button>
                    <!-- Honeypot field for bot protection -->
                    <input 
                        type="text" 
                        name="website" 
                        class="rayvitals-honeypot"
                        tabindex="-1"
                        autocomplete="off"
                    />
                </div>
                <div class="rayvitals-form-message" style="display: none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render email capture shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_email_capture($atts) {
        $atts = shortcode_atts(array(
            'audit_id' => isset($_GET['audit_id']) ? sanitize_text_field($_GET['audit_id']) : '',
            'button_text' => __('Get Your Results', 'rayvitals'),
            'title' => __('Your website audit is in progress!', 'rayvitals'),
            'description' => __('Enter your email address to receive the detailed audit results.', 'rayvitals')
        ), $atts);
        
        if (empty($atts['audit_id'])) {
            return '<p>' . __('Invalid audit request. Please start a new audit.', 'rayvitals') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="rayvitals-email-capture-container">
            <div class="rayvitals-email-capture-header">
                <h2><?php echo esc_html($atts['title']); ?></h2>
                <p><?php echo esc_html($atts['description']); ?></p>
            </div>
            
            <div class="rayvitals-audit-progress">
                <div class="progress-bar">
                    <div class="progress-fill" data-audit-id="<?php echo esc_attr($atts['audit_id']); ?>"></div>
                </div>
                <p class="progress-text"><?php _e('Analyzing your website...', 'rayvitals'); ?></p>
            </div>
            
            <form id="rayvitals-email-form" class="rayvitals-form">
                <input type="hidden" name="audit_id" value="<?php echo esc_attr($atts['audit_id']); ?>" />
                <div class="rayvitals-form-group">
                    <input 
                        type="email" 
                        name="email" 
                        class="rayvitals-input"
                        placeholder="<?php esc_attr_e('Enter your email address', 'rayvitals'); ?>"
                        required
                    />
                </div>
                <div class="rayvitals-form-group">
                    <button type="submit" class="rayvitals-button rayvitals-button-primary">
                        <span class="button-text"><?php echo esc_html($atts['button_text']); ?></span>
                        <span class="button-spinner" style="display: none;">
                            <span class="spinner"></span>
                        </span>
                    </button>
                </div>
                <div class="rayvitals-form-message" style="display: none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render results shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_results($atts) {
        $atts = shortcode_atts(array(
            'audit_id' => isset($_GET['audit_id']) ? sanitize_text_field($_GET['audit_id']) : ''
        ), $atts);
        
        if (empty($atts['audit_id'])) {
            return '<p>' . __('No audit results found. Please start a new audit.', 'rayvitals') . '</p>';
        }
        
        // Check if user has access to these results
        $has_access = $this->check_results_access($atts['audit_id']);
        if (!$has_access) {
            $email_url = add_query_arg('audit_id', $atts['audit_id'], home_url('/email-capture/'));
            return '<p>' . sprintf(
                __('Please <a href="%s">provide your email</a> to view the audit results.', 'rayvitals'),
                esc_url($email_url)
            ) . '</p>';
        }
        
        // Check audit status via API first
        $api_status = $this->api->get_audit_status($atts['audit_id']);
        
        if (is_wp_error($api_status)) {
            return '<p>' . __('Error loading audit results: ', 'rayvitals') . $api_status->get_error_message() . '</p>';
        }
        
        // If audit is completed, fetch full results
        if ($api_status['status'] === 'completed') {
            $api_results = $this->api->get_audit_results($atts['audit_id']);
            
            if (is_wp_error($api_results)) {
                return '<p>' . __('Error loading audit results: ', 'rayvitals') . $api_results->get_error_message() . '</p>';
            }
            
            // Get updated results from local database (now populated by API call above)
            $results = RayVitals_Audit::get_by_id($atts['audit_id']);
        } else {
            // Show progress page with auto-polling
            return $this->render_progress_page($atts['audit_id'], $api_status);
        }
        
        ob_start();
        include RAYVITALS_PLUGIN_DIR . 'public/templates/results-display.php';
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for starting audit
     */
    public function ajax_start_audit() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'rayvitals-public-nonce')) {
            wp_die('Security check failed');
        }
        
        // Check honeypot
        if (!empty($_POST['website'])) {
            wp_send_json_error(array('message' => __('Bot detection triggered.', 'rayvitals')));
        }
        
        // Rate limiting check
        if (!$this->check_rate_limit()) {
            wp_send_json_error(array('message' => __('Too many requests. Please try again later.', 'rayvitals')));
        }
        
        // Validate URL
        $url = esc_url_raw($_POST['website_url']);
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(array('message' => __('Please enter a valid URL.', 'rayvitals')));
        }
        
        // Start audit
        $result = $this->api->start_audit($url);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        // Store audit in local database
        $create_result = RayVitals_Audit::create_audit(array(
            'audit_id' => $result['audit_id'],
            'url' => $url,
            'status' => 'pending'
        ));
        
        error_log('RayVitals: Create audit result: ' . ($create_result ? 'SUCCESS' : 'FAILED'));
        
        if ($create_result === false) {
            error_log('RayVitals: Database insert failed, but continuing with success response');
        }
        
        // Return success with audit ID (even if local storage failed)
        $redirect_url = add_query_arg('audit_id', $result['audit_id'], home_url('/email-capture/'));
        error_log('RayVitals: Sending success response with redirect_url: ' . $redirect_url);
        
        wp_send_json_success(array(
            'audit_id' => $result['audit_id'],
            'redirect_url' => $redirect_url
        ));
    }
    
    /**
     * AJAX handler for email submission
     */
    public function ajax_submit_email() {
        error_log('RayVitals: ajax_submit_email started');
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'rayvitals-public-nonce')) {
            error_log('RayVitals: Nonce verification failed');
            wp_die('Security check failed');
        }
        
        error_log('RayVitals: Nonce verified, proceeding with email submission');
        
        $audit_id = sanitize_text_field($_POST['audit_id']);
        $email = sanitize_email($_POST['email']);
        
        error_log('RayVitals: Received audit_id: ' . $audit_id . ', email: ' . $email);
        
        if (!is_email($email)) {
            error_log('RayVitals: Invalid email format');
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'rayvitals')));
        }
        
        // Store email with audit
        global $wpdb;
        $table_name = $wpdb->prefix . 'rayvitals_leads';
        
        // Check if leads table exists, create it if it doesn't
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            error_log('RayVitals: Leads table does not exist, creating it');
            $this->create_leads_table();
        }
        
        error_log('RayVitals: Attempting to insert into leads table: ' . $table_name);
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'audit_id' => $audit_id,
                'email' => $email,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('RayVitals: Failed to insert into leads table. Error: ' . $wpdb->last_error);
            wp_send_json_error(array('message' => __('Failed to save email. Please try again.', 'rayvitals')));
        }
        
        error_log('RayVitals: Successfully inserted email into leads table');
        
        // Check if audits table has email column, add it if missing
        $audits_table = $wpdb->prefix . 'rayvitals_audits';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $audits_table LIKE 'email'");
        
        if (empty($column_exists)) {
            error_log('RayVitals: Email column does not exist in audits table, adding it');
            $wpdb->query("ALTER TABLE $audits_table ADD COLUMN email varchar(255) DEFAULT NULL AFTER url");
            error_log('RayVitals: Email column added to audits table');
        }
        
        // Update audit with email
        $update_result = $wpdb->update(
            $audits_table,
            array('email' => $email),
            array('audit_id' => $audit_id),
            array('%s'),
            array('%s')
        );
        
        error_log('RayVitals: Update audit table result: ' . ($update_result !== false ? 'SUCCESS' : 'FAILED'));
        if ($update_result === false) {
            error_log('RayVitals: Update audit error: ' . $wpdb->last_error);
        }
        
        // Set cookie for access
        setcookie('rayvitals_audit_' . $audit_id, md5($email . $audit_id), time() + DAY_IN_SECONDS, '/');
        error_log('RayVitals: Cookie set for audit access');
        
        $redirect_url = add_query_arg('audit_id', $audit_id, home_url('/results/'));
        error_log('RayVitals: Sending email success response with redirect_url: ' . $redirect_url);
        
        wp_send_json_success(array(
            'redirect_url' => $redirect_url
        ));
    }
    
    /**
     * AJAX handler for checking audit status
     */
    public function ajax_check_audit_status() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'rayvitals-public-nonce')) {
            wp_die('Security check failed');
        }
        
        $audit_id = sanitize_text_field($_POST['audit_id']);
        
        // Get status from API
        $status = $this->api->get_audit_status($audit_id);
        
        if (is_wp_error($status)) {
            wp_send_json_error(array('message' => $status->get_error_message()));
        }
        
        // Calculate progress percentage
        $progress = 0;
        switch ($status['status']) {
            case 'pending':
                $progress = 10;
                break;
            case 'analyzing':
                $progress = 50;
                break;
            case 'completed':
                $progress = 100;
                break;
        }
        
        wp_send_json_success(array(
            'status' => $status['status'],
            'progress' => $progress,
            'message' => $status['message'] ?? ''
        ));
    }
    
    /**
     * Check if user has access to results
     *
     * @param string $audit_id
     * @return bool
     */
    private function check_results_access($audit_id) {
        // Check cookie
        if (isset($_COOKIE['rayvitals_audit_' . $audit_id])) {
            global $wpdb;
            $email = $wpdb->get_var($wpdb->prepare(
                "SELECT email FROM {$wpdb->prefix}rayvitals_audits WHERE audit_id = %s",
                $audit_id
            ));
            
            if ($email && $_COOKIE['rayvitals_audit_' . $audit_id] === md5($email . $audit_id)) {
                return true;
            }
        }
        
        // Check if logged in admin
        if (current_user_can('manage_options')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check rate limit for current IP
     *
     * @return bool
     */
    private function check_rate_limit() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $transient_key = 'rayvitals_rate_' . md5($ip);
        
        $attempts = get_transient($transient_key);
        if ($attempts === false) {
            set_transient($transient_key, 1, HOUR_IN_SECONDS);
            return true;
        }
        
        if ($attempts >= 5) {
            return false;
        }
        
        set_transient($transient_key, $attempts + 1, HOUR_IN_SECONDS);
        return true;
    }
    
    /**
     * Create leads table if it doesn't exist
     */
    private function create_leads_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($leads_sql);
        
        error_log('RayVitals: Leads table creation attempted');
    }
    
    /**
     * Render progress page when audit is still processing
     *
     * @param string $audit_id
     * @param array $api_status
     * @return string
     */
    private function render_progress_page($audit_id, $api_status) {
        $progress = 10; // Default progress
        $status_text = __('Analyzing your website...', 'rayvitals');
        
        // Calculate progress based on status
        switch ($api_status['status']) {
            case 'pending':
                $progress = 10;
                $status_text = __('Starting analysis...', 'rayvitals');
                break;
            case 'processing':
            case 'analyzing':
                $progress = 50;
                $status_text = __('Analyzing your website...', 'rayvitals');
                break;
        }
        
        ob_start();
        ?>
        <div class="rayvitals-results-progress-container">
            <div class="rayvitals-progress-header">
                <h2><?php _e('Your Website Audit is in Progress', 'rayvitals'); ?></h2>
                <p><?php _e('Please wait while we analyze your website. This usually takes 20-30 seconds.', 'rayvitals'); ?></p>
            </div>
            
            <div class="rayvitals-audit-progress">
                <div class="progress-bar">
                    <div class="progress-fill" data-audit-id="<?php echo esc_attr($audit_id); ?>" style="width: <?php echo $progress; ?>%;"></div>
                </div>
                <p class="progress-text"><?php echo esc_html($status_text); ?></p>
            </div>
            
            <div class="rayvitals-progress-info">
                <p><?php _e('We\'re analyzing:', 'rayvitals'); ?></p>
                <ul>
                    <li><?php _e('Security & SSL certificates', 'rayvitals'); ?></li>
                    <li><?php _e('Performance & Core Web Vitals', 'rayvitals'); ?></li>
                    <li><?php _e('SEO optimization', 'rayvitals'); ?></li>
                    <li><?php _e('Accessibility compliance', 'rayvitals'); ?></li>
                    <li><?php _e('User experience factors', 'rayvitals'); ?></li>
                </ul>
            </div>
            
            <script>
            // Auto-refresh the page when audit completes
            (function() {
                var checkComplete = function() {
                    // Use existing progress tracking from rayvitals-public.js
                    jQuery.ajax({
                        url: rayvitals_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'rayvitals_check_audit_status',
                            nonce: rayvitals_ajax.nonce,
                            audit_id: '<?php echo esc_js($audit_id); ?>'
                        },
                        success: function(response) {
                            if (response.success && response.data.status === 'completed') {
                                // Audit completed, refresh the page to show results
                                window.location.reload();
                            } else if (response.success) {
                                // Update progress
                                jQuery('.progress-fill').css('width', response.data.progress + '%');
                                jQuery('.progress-text').text(response.data.message || '<?php echo esc_js($status_text); ?>');
                                
                                // Continue polling
                                setTimeout(checkComplete, 3000);
                            } else {
                                // Error occurred
                                jQuery('.progress-text').text('<?php _e("An error occurred. Please refresh the page.", "rayvitals"); ?>');
                            }
                        },
                        error: function() {
                            // Network error, continue trying
                            setTimeout(checkComplete, 5000);
                        }
                    });
                };
                
                // Start polling after 2 seconds
                setTimeout(checkComplete, 2000);
            })();
            </script>
        </div>
        <?php
        return ob_get_clean();
    }
}