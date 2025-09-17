<?php
/**
 * Settings Page View
 *
 * @package RayVitals
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['rayvitals_settings_nonce'], 'rayvitals_settings')) {
    $api_key = sanitize_text_field($_POST['rayvitals_api_key']);
    $enable_caching = isset($_POST['rayvitals_enable_caching']) ? 1 : 0;
    $cache_duration = intval($_POST['rayvitals_cache_duration']);
    
    update_option('rayvitals_api_key', $api_key);
    update_option('rayvitals_enable_caching', $enable_caching);
    update_option('rayvitals_cache_duration', $cache_duration);
    
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'rayvitals') . '</p></div>';
}

// Get current settings
$api_key = get_option('rayvitals_api_key', '');
$enable_caching = get_option('rayvitals_enable_caching', 1);
$cache_duration = get_option('rayvitals_cache_duration', 3600);
?>

<div class="wrap">
    <h1><?php _e('RayVitals Settings', 'rayvitals'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('rayvitals_settings', 'rayvitals_settings_nonce'); ?>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="rayvitals_api_key"><?php _e('API Key', 'rayvitals'); ?></label>
                    </th>
                    <td>
                        <div style="display: flex; gap: 10px; align-items: flex-start;">
                            <input 
                                type="text" 
                                id="rayvitals_api_key" 
                                name="rayvitals_api_key" 
                                value="<?php echo esc_attr($api_key); ?>" 
                                class="regular-text" 
                                placeholder="rv_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                style="flex: 1;"
                            />
                            <button 
                                type="button" 
                                id="generate_api_key" 
                                class="button button-secondary"
                                <?php echo empty($api_key) ? '' : 'disabled'; ?>
                            >
                                <?php _e('Generate New Key', 'rayvitals'); ?>
                            </button>
                        </div>
                        <p class="description">
                            <?php _e('Enter your RayVitals API key or generate a new one automatically.', 'rayvitals'); ?>
                            <?php if (!empty($api_key)): ?>
                                <br><strong><?php _e('Note: You already have an API key configured. Generating a new key will replace the current one.', 'rayvitals'); ?></strong>
                            <?php endif; ?>
                        </p>
                        <div id="api_key_generation_status" style="display: none; margin-top: 10px;"></div>
                        <?php if (empty($api_key)): ?>
                            <p class="description" style="color: #d63638;">
                                <strong><?php _e('⚠️ API key is required for the plugin to function.', 'rayvitals'); ?></strong>
                            </p>
                        <?php else: ?>
                            <p class="description" style="color: #00a32a;">
                                <strong><?php _e('✅ API key configured', 'rayvitals'); ?></strong>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="rayvitals_enable_caching"><?php _e('Enable Caching', 'rayvitals'); ?></label>
                    </th>
                    <td>
                        <input 
                            type="checkbox" 
                            id="rayvitals_enable_caching" 
                            name="rayvitals_enable_caching" 
                            value="1" 
                            <?php checked($enable_caching, 1); ?>
                        />
                        <label for="rayvitals_enable_caching">
                            <?php _e('Cache audit results to improve performance and reduce API calls', 'rayvitals'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="rayvitals_cache_duration"><?php _e('Cache Duration', 'rayvitals'); ?></label>
                    </th>
                    <td>
                        <select id="rayvitals_cache_duration" name="rayvitals_cache_duration">
                            <option value="1800" <?php selected($cache_duration, 1800); ?>><?php _e('30 minutes', 'rayvitals'); ?></option>
                            <option value="3600" <?php selected($cache_duration, 3600); ?>><?php _e('1 hour', 'rayvitals'); ?></option>
                            <option value="7200" <?php selected($cache_duration, 7200); ?>><?php _e('2 hours', 'rayvitals'); ?></option>
                            <option value="21600" <?php selected($cache_duration, 21600); ?>><?php _e('6 hours', 'rayvitals'); ?></option>
                            <option value="86400" <?php selected($cache_duration, 86400); ?>><?php _e('24 hours', 'rayvitals'); ?></option>
                        </select>
                        <p class="description">
                            <?php _e('How long to cache audit results before fetching fresh data.', 'rayvitals'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <hr />
    
    <h2><?php _e('API Configuration', 'rayvitals'); ?></h2>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><?php _e('API URL', 'rayvitals'); ?></th>
                <td>
                    <code><?php echo esc_html(RAYVITALS_API_URL); ?></code>
                    <p class="description"><?php _e('This is automatically configured and should not be changed.', 'rayvitals'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Plugin Version', 'rayvitals'); ?></th>
                <td>
                    <code><?php echo esc_html(RAYVITALS_VERSION); ?></code>
                </td>
            </tr>
        </tbody>
    </table>
    
    <h2><?php _e('Shortcode Usage', 'rayvitals'); ?></h2>
    <p><?php _e('Use these shortcodes to display audit functionality on your site:', 'rayvitals'); ?></p>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Shortcode', 'rayvitals'); ?></th>
                <th><?php _e('Description', 'rayvitals'); ?></th>
                <th><?php _e('Usage', 'rayvitals'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>[rayvitals_audit_form]</code></td>
                <td><?php _e('Display the initial audit form where users enter their website URL', 'rayvitals'); ?></td>
                <td><?php _e('Place on your home page or landing page', 'rayvitals'); ?></td>
            </tr>
            <tr>
                <td><code>[rayvitals_email_capture]</code></td>
                <td><?php _e('Display the email capture form with audit progress', 'rayvitals'); ?></td>
                <td><?php _e('Place on a dedicated email capture page', 'rayvitals'); ?></td>
            </tr>
            <tr>
                <td><code>[rayvitals_results]</code></td>
                <td><?php _e('Display the full audit results with scores and recommendations', 'rayvitals'); ?></td>
                <td><?php _e('Place on a dedicated results page', 'rayvitals'); ?></td>
            </tr>
        </tbody>
    </table>
    
    <h2><?php _e('Recommended Page Setup', 'rayvitals'); ?></h2>
    <ol>
        <li>
            <strong><?php _e('Home Page:', 'rayvitals'); ?></strong> 
            <?php _e('Add', 'rayvitals'); ?> <code>[rayvitals_audit_form]</code> <?php _e('to your home page or create a dedicated landing page', 'rayvitals'); ?>
        </li>
        <li>
            <strong><?php _e('Email Capture Page:', 'rayvitals'); ?></strong> 
            <?php _e('Create a page with slug', 'rayvitals'); ?> <code>/email-capture/</code> <?php _e('and add', 'rayvitals'); ?> <code>[rayvitals_email_capture]</code>
        </li>
        <li>
            <strong><?php _e('Results Page:', 'rayvitals'); ?></strong> 
            <?php _e('Create a page with slug', 'rayvitals'); ?> <code>/results/</code> <?php _e('and add', 'rayvitals'); ?> <code>[rayvitals_results]</code>
        </li>
    </ol>
    
    <?php if (!empty($api_key)): ?>
        <div class="notice notice-info">
            <p><strong><?php _e('🎉 Your plugin is ready to use!', 'rayvitals'); ?></strong></p>
            <p><?php _e('Create the recommended pages above and start capturing leads with website audits.', 'rayvitals'); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.wrap code {
    background: #f1f1f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: Monaco, Consolas, monospace;
}

.wrap .notice {
    margin: 20px 0;
}

.wp-list-table th,
.wp-list-table td {
    padding: 12px;
}

.wrap ol {
    margin-left: 20px;
}

.wrap ol li {
    margin-bottom: 10px;
    line-height: 1.5;
}

#api_key_generation_status.success {
    color: #00a32a;
    background: #f0f6fc;
    padding: 10px;
    border-left: 4px solid #00a32a;
}

#api_key_generation_status.error {
    color: #d63638;
    background: #fcf0f1;
    padding: 10px;
    border-left: 4px solid #d63638;
}

#api_key_generation_status.loading {
    color: #646970;
    background: #f6f7f7;
    padding: 10px;
    border-left: 4px solid #646970;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#generate_api_key').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $status = $('#api_key_generation_status');
        var $apiKeyInput = $('#rayvitals_api_key');
        
        // Confirm action if key already exists
        if ($apiKeyInput.val() && !confirm('<?php _e('This will replace your existing API key. Are you sure?', 'rayvitals'); ?>')) {
            return;
        }
        
        // Set loading state
        $button.prop('disabled', true).text('<?php _e('Generating...', 'rayvitals'); ?>');
        $status.removeClass('success error').addClass('loading')
               .html('<?php _e('🔄 Generating new API key...', 'rayvitals'); ?>')
               .show();
        
        // Make AJAX request to generate key
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rayvitals_generate_api_key',
                nonce: '<?php echo wp_create_nonce('rayvitals_generate_api_key'); ?>'
            },
            timeout: 30000,
            success: function(response) {
                if (response.success) {
                    // Update the input field
                    $apiKeyInput.val(response.data.api_key);
                    
                    // Show success message
                    $status.removeClass('loading error').addClass('success')
                           .html(
                               '✅ <strong><?php _e('API key generated successfully!', 'rayvitals'); ?></strong><br>' +
                               '📋 <strong><?php _e('Key:', 'rayvitals'); ?></strong> <code>' + response.data.api_key + '</code><br>' +
                               '⚠️ <?php _e('Please save your settings to store this key.', 'rayvitals'); ?>'
                           );
                    
                    // Change button text
                    $button.text('<?php _e('Generate Another Key', 'rayvitals'); ?>').prop('disabled', false);
                    
                    // Highlight the save button
                    $('#submit').css('background', '#00a32a').css('border-color', '#00a32a');
                    
                } else {
                    $status.removeClass('loading success').addClass('error')
                           .html('❌ <strong><?php _e('Error:', 'rayvitals'); ?></strong> ' + (response.data ? response.data.message : '<?php _e('Unknown error occurred', 'rayvitals'); ?>'));
                    
                    $button.text('<?php _e('Generate New Key', 'rayvitals'); ?>').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                $status.removeClass('loading success').addClass('error')
                       .html('❌ <strong><?php _e('Network Error:', 'rayvitals'); ?></strong> <?php _e('Failed to connect to RayVitals API. Please try again.', 'rayvitals'); ?>');
                
                $button.text('<?php _e('Generate New Key', 'rayvitals'); ?>').prop('disabled', false);
            }
        });
    });
});
</script>