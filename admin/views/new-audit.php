<?php
/**
 * RayVitals New Audit View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('New Website Audit', 'rayvitals'); ?></h1>
    
    <?php if (empty(get_option('rayvitals_api_key'))): ?>
        <div class="notice notice-error">
            <p><?php printf(
                __('RayVitals API key not configured. Please <a href="%s">add your API key</a> to start auditing websites.', 'rayvitals'),
                admin_url('admin.php?page=rayvitals-settings')
            ); ?></p>
        </div>
    <?php else: ?>
        
        <div class="rayvitals-audit-form">
            <form id="rayvitals-audit-form">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="audit_url"><?php _e('Website URL', 'rayvitals'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input type="url" id="audit_url" name="audit_url" class="regular-text" 
                                       placeholder="https://example.com" required>
                                <p class="description">
                                    <?php _e('Enter the full URL of the website you want to audit (including https://).', 'rayvitals'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="audit_email"><?php _e('Email Address', 'rayvitals'); ?></label>
                            </th>
                            <td>
                                <input type="email" id="audit_email" name="audit_email" class="regular-text" 
                                       value="<?php echo esc_attr(get_option('admin_email')); ?>">
                                <p class="description">
                                    <?php _e('Email address to receive audit notifications (optional).', 'rayvitals'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Audit Categories', 'rayvitals'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="categories[]" value="security" checked disabled>
                                        <?php _e('Security Analysis', 'rayvitals'); ?>
                                        <span class="description"><?php _e('(SSL, headers, vulnerabilities)', 'rayvitals'); ?></span>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="categories[]" value="performance" checked disabled>
                                        <?php _e('Performance Testing', 'rayvitals'); ?>
                                        <span class="description"><?php _e('(Load times, Core Web Vitals)', 'rayvitals'); ?></span>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="categories[]" value="seo" checked disabled>
                                        <?php _e('SEO Analysis', 'rayvitals'); ?>
                                        <span class="description"><?php _e('(Meta tags, structure, content)', 'rayvitals'); ?></span>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="categories[]" value="accessibility" checked disabled>
                                        <?php _e('Accessibility Check', 'rayvitals'); ?>
                                        <span class="description"><?php _e('(WCAG compliance, screen readers)', 'rayvitals'); ?></span>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="categories[]" value="ux" checked disabled>
                                        <?php _e('User Experience', 'rayvitals'); ?>
                                        <span class="description"><?php _e('(Mobile responsiveness, usability)', 'rayvitals'); ?></span>
                                    </label>
                                </fieldset>
                                <p class="description">
                                    <?php _e('All categories are included in every audit for comprehensive analysis.', 'rayvitals'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <?php wp_nonce_field('rayvitals_start_audit', 'rayvitals_nonce'); ?>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large" id="start-audit-btn">
                        <?php _e('Start Audit', 'rayvitals'); ?>
                    </button>
                </p>
            </form>
        </div>
        
        <!-- Progress Section (initially hidden) -->
        <div id="audit-progress" class="rayvitals-progress" style="display: none;">
            <h2><?php _e('Audit in Progress', 'rayvitals'); ?></h2>
            
            <div class="progress-bar">
                <div class="progress-fill" id="progress-fill"></div>
            </div>
            
            <div class="progress-status" id="progress-status">
                <?php _e('Initializing audit...', 'rayvitals'); ?>
            </div>
            
            <div class="progress-details" id="progress-details">
                <div class="audit-steps">
                    <div class="step" id="step-security">
                        <span class="step-icon">⏳</span>
                        <?php _e('Security Analysis', 'rayvitals'); ?>
                    </div>
                    <div class="step" id="step-performance">
                        <span class="step-icon">⏳</span>
                        <?php _e('Performance Testing', 'rayvitals'); ?>
                    </div>
                    <div class="step" id="step-seo">
                        <span class="step-icon">⏳</span>
                        <?php _e('SEO Analysis', 'rayvitals'); ?>
                    </div>
                    <div class="step" id="step-accessibility">
                        <span class="step-icon">⏳</span>
                        <?php _e('Accessibility Check', 'rayvitals'); ?>
                    </div>
                    <div class="step" id="step-ux">
                        <span class="step-icon">⏳</span>
                        <?php _e('User Experience', 'rayvitals'); ?>
                    </div>
                    <div class="step" id="step-ai">
                        <span class="step-icon">⏳</span>
                        <?php _e('AI Analysis', 'rayvitals'); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Results Section (initially hidden) -->
        <div id="audit-results" class="rayvitals-results" style="display: none;">
            <h2><?php _e('Audit Complete!', 'rayvitals'); ?></h2>
            <div id="results-content"></div>
        </div>
        
    <?php endif; ?>
</div>

<style>
.rayvitals-audit-form {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.required {
    color: #dc3232;
}

.rayvitals-progress {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background-color: #f1f1f1;
    border-radius: 10px;
    overflow: hidden;
    margin: 15px 0;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0073aa, #00a0d2);
    width: 0%;
    transition: width 0.5s ease;
}

.progress-status {
    text-align: center;
    font-weight: bold;
    margin: 15px 0;
    color: #0073aa;
}

.audit-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.step {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
    background: #f9f9f9;
}

.step.active {
    background: #e7f5ff;
    border-color: #0073aa;
}

.step.completed {
    background: #e8f5e8;
    border-color: #46b450;
}

.step-icon {
    display: block;
    font-size: 20px;
    margin-bottom: 5px;
}

.step.active .step-icon::before {
    content: "🔄";
}

.step.completed .step-icon::before {
    content: "✅";
}

.rayvitals-results {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    let currentAuditId = null;
    let progressInterval = null;
    
    // Handle form submission
    $('#rayvitals-audit-form').on('submit', function(e) {
        e.preventDefault();
        
        const url = $('#audit_url').val();
        const email = $('#audit_email').val();
        
        if (!url) {
            alert('<?php echo esc_js(__('Please enter a website URL.', 'rayvitals')); ?>');
            return;
        }
        
        startAudit(url, email);
    });
    
    function startAudit(url, email) {
        // Hide form and show progress
        $('.rayvitals-audit-form').slideUp();
        $('#audit-progress').slideDown();
        
        // Disable form
        $('#start-audit-btn').prop('disabled', true);
        
        // Start audit via AJAX
        $.post(ajaxurl, {
            action: 'rayvitals_start_audit',
            nonce: $('#rayvitals_nonce').val(),
            url: url,
            email: email
        }, function(response) {
            if (response.success) {
                currentAuditId = response.data.audit_id;
                updateProgress(0, '<?php echo esc_js(__('Audit started successfully', 'rayvitals')); ?>');
                
                // Start polling for progress
                progressInterval = setInterval(checkProgress, 3000);
            } else {
                alert('<?php echo esc_js(__('Failed to start audit: ', 'rayvitals')); ?>' + response.data.message);
                resetForm();
            }
        }).fail(function() {
            alert('<?php echo esc_js(__('Connection error. Please try again.', 'rayvitals')); ?>');
            resetForm();
        });
    }
    
    function checkProgress() {
        if (!currentAuditId) return;
        
        $.post(ajaxurl, {
            action: 'rayvitals_check_status',
            nonce: $('#rayvitals_nonce').val(),
            audit_id: currentAuditId
        }, function(response) {
            if (response.success) {
                const data = response.data;
                
                if (data.status === 'completed') {
                    clearInterval(progressInterval);
                    showResults(data);
                } else if (data.status === 'failed') {
                    clearInterval(progressInterval);
                    alert('<?php echo esc_js(__('Audit failed. Please try again.', 'rayvitals')); ?>');
                    resetForm();
                } else {
                    // Update progress
                    updateProgressFromStatus(data);
                }
            }
        });
    }
    
    function updateProgress(percent, message) {
        $('#progress-fill').css('width', percent + '%');
        $('#progress-status').text(message);
    }
    
    function updateProgressFromStatus(data) {
        // Update progress based on completed steps
        let completedSteps = 0;
        const totalSteps = 6;
        
        if (data.progress) {
            if (data.progress.security_completed) {
                $('#step-security').addClass('completed').removeClass('active');
                completedSteps++;
            } else if (data.progress.security_started) {
                $('#step-security').addClass('active');
            }
            
            if (data.progress.performance_completed) {
                $('#step-performance').addClass('completed').removeClass('active');
                completedSteps++;
            } else if (data.progress.performance_started) {
                $('#step-performance').addClass('active');
            }
            
            // Continue for other steps...
            const percent = Math.round((completedSteps / totalSteps) * 100);
            updateProgress(percent, data.status_message || '<?php echo esc_js(__('Processing...', 'rayvitals')); ?>');
        }
    }
    
    function showResults(data) {
        $('#audit-progress').slideUp();
        $('#audit-results').slideDown();
        
        // Display results (you can expand this)
        $('#results-content').html('<p><?php echo esc_js(__('Audit completed successfully! Check the dashboard for detailed results.', 'rayvitals')); ?></p>');
        
        // Reset form after a delay
        setTimeout(resetForm, 3000);
    }
    
    function resetForm() {
        $('#start-audit-btn').prop('disabled', false);
        $('.rayvitals-audit-form').slideDown();
        $('#audit-progress, #audit-results').slideUp();
        $('.step').removeClass('active completed');
        currentAuditId = null;
        if (progressInterval) {
            clearInterval(progressInterval);
        }
    }
});
</script>