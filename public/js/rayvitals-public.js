/**
 * RayVitals Public JavaScript
 * Handles frontend interactions for shortcodes
 */

(function($) {
    'use strict';

    var RayVitalsPublic = {
        
        init: function() {
            this.bindEvents();
            this.initProgressTracking();
        },
        
        bindEvents: function() {
            // Audit form submission
            $(document).on('submit', '#rayvitals-audit-form', this.handleAuditSubmission);
            
            // Email form submission
            $(document).on('submit', '#rayvitals-email-form', this.handleEmailSubmission);
            
            // Results page initialization
            if ($('.rayvitals-results-container').length) {
                this.initResultsDisplay();
            }
        },
        
        handleAuditSubmission: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var $message = $form.find('.rayvitals-form-message');
            var url = $form.find('input[name="website_url"]').val();
            
            // Basic validation
            if (!url || !RayVitalsPublic.isValidUrl(url)) {
                RayVitalsPublic.showMessage($message, 'Please enter a valid website URL.', 'error');
                return;
            }
            
            // Set loading state
            RayVitalsPublic.setButtonLoading($button, true);
            $message.hide();
            
            // Submit via AJAX
            $.ajax({
                url: rayvitals_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rayvitals_start_audit',
                    nonce: rayvitals_ajax.nonce,
                    website_url: url
                },
                success: function(response) {
                    if (response.success) {
                        // Redirect to email capture page
                        window.location.href = response.data.redirect_url;
                    } else {
                        RayVitalsPublic.showMessage($message, response.data.message, 'error');
                        RayVitalsPublic.setButtonLoading($button, false);
                    }
                },
                error: function() {
                    RayVitalsPublic.showMessage($message, rayvitals_ajax.error_text, 'error');
                    RayVitalsPublic.setButtonLoading($button, false);
                }
            });
        },
        
        handleEmailSubmission: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var $message = $form.find('.rayvitals-form-message');
            var email = $form.find('input[name="email"]').val();
            var auditId = $form.find('input[name="audit_id"]').val();
            
            // Basic validation
            if (!email || !RayVitalsPublic.isValidEmail(email)) {
                RayVitalsPublic.showMessage($message, 'Please enter a valid email address.', 'error');
                return;
            }
            
            // Set loading state
            RayVitalsPublic.setButtonLoading($button, true);
            $message.hide();
            
            // Submit via AJAX
            $.ajax({
                url: rayvitals_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rayvitals_submit_email',
                    nonce: rayvitals_ajax.nonce,
                    email: email,
                    audit_id: auditId
                },
                success: function(response) {
                    if (response.success) {
                        // Redirect to results page
                        window.location.href = response.data.redirect_url;
                    } else {
                        RayVitalsPublic.showMessage($message, response.data.message, 'error');
                        RayVitalsPublic.setButtonLoading($button, false);
                    }
                },
                error: function() {
                    RayVitalsPublic.showMessage($message, rayvitals_ajax.error_text, 'error');
                    RayVitalsPublic.setButtonLoading($button, false);
                }
            });
        },
        
        initProgressTracking: function() {
            var $progressFill = $('.progress-fill[data-audit-id]');
            if ($progressFill.length) {
                var auditId = $progressFill.data('audit-id');
                this.trackAuditProgress(auditId);
            }
        },
        
        trackAuditProgress: function(auditId) {
            var self = this;
            var $progressFill = $('.progress-fill[data-audit-id="' + auditId + '"]');
            var $progressText = $('.progress-text');
            
            var checkStatus = function() {
                $.ajax({
                    url: rayvitals_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'rayvitals_check_audit_status',
                        nonce: rayvitals_ajax.nonce,
                        audit_id: auditId
                    },
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            
                            // Update progress bar
                            $progressFill.css('width', data.progress + '%');
                            
                            // Update status text
                            var statusText = rayvitals_ajax.scanning_text;
                            if (data.status === 'completed') {
                                statusText = 'Analysis complete! Please check your email.';
                            } else if (data.message) {
                                statusText = data.message;
                            }
                            $progressText.text(statusText);
                            
                            // Continue polling if not complete
                            if (data.status !== 'completed' && data.status !== 'failed') {
                                setTimeout(checkStatus, 3000); // Check every 3 seconds
                            }
                        } else {
                            // Error occurred, stop polling
                            $progressText.text('An error occurred during analysis.');
                        }
                    },
                    error: function() {
                        // Network error, stop polling
                        $progressText.text('Connection error. Please refresh the page.');
                    }
                });
            };
            
            // Start polling
            checkStatus();
        },
        
        initResultsDisplay: function() {
            // Initialize any charts or interactive elements
            this.initScoreCharts();
            this.addSmoothScrolling();
        },
        
        initScoreCharts: function() {
            // If Chart.js is available and we have score data
            if (typeof Chart !== 'undefined') {
                this.createScoreCharts();
            }
        },
        
        createScoreCharts: function() {
            // This would create interactive charts for the results
            // Implementation would depend on the specific chart requirements
            $('.category-card').each(function() {
                var $card = $(this);
                var score = parseInt($card.find('.category-score').text());
                
                // Add visual enhancements based on score
                if (score >= 80) {
                    $card.addClass('score-excellent');
                } else if (score >= 60) {
                    $card.addClass('score-good');
                } else {
                    $card.addClass('score-poor');
                }
            });
        },
        
        addSmoothScrolling: function() {
            // Add smooth scrolling to anchor links
            $('a[href^="#"]').on('click', function(e) {
                e.preventDefault();
                var target = $($(this).attr('href'));
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 20
                    }, 500);
                }
            });
        },
        
        // Utility functions
        isValidUrl: function(string) {
            try {
                var url = new URL(string);
                return url.protocol === 'http:' || url.protocol === 'https:';
            } catch (_) {
                return false;
            }
        },
        
        isValidEmail: function(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },
        
        setButtonLoading: function($button, loading) {
            if (loading) {
                $button.addClass('loading').prop('disabled', true);
                $button.find('.button-text').hide();
                $button.find('.button-spinner').show();
            } else {
                $button.removeClass('loading').prop('disabled', false);
                $button.find('.button-text').show();
                $button.find('.button-spinner').hide();
            }
        },
        
        showMessage: function($messageElement, message, type) {
            $messageElement
                .removeClass('success error')
                .addClass(type)
                .text(message)
                .fadeIn();
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(function() {
                    $messageElement.fadeOut();
                }, 5000);
            }
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        RayVitalsPublic.init();
    });
    
    // Expose to global scope if needed
    window.RayVitalsPublic = RayVitalsPublic;
    
})(jQuery);