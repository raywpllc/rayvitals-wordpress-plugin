<?php
/**
 * Results Display Template
 * Used by the [rayvitals_results] shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure we have results data
if (!isset($results) || empty($results)) {
    echo '<p>' . __('No results available.', 'rayvitals') . '</p>';
    return;
}

$overall_score = isset($results->overall_score) ? round($results->overall_score) : 0;
$url = isset($results->url) ? esc_url($results->url) : '';
$ai_summary = isset($results->ai_summary) ? wp_kses_post($results->ai_summary) : '';

// Parse detailed results
$detailed_results = isset($results->results) ? json_decode($results->results, true) : array();
$benchmarking_data = isset($detailed_results['benchmarking']) ? $detailed_results['benchmarking'] : array();
$overall_percentile = isset($benchmarking_data['overall']) ? $benchmarking_data['overall'] : '';

// Get historical comparison data
$comparison_data = RayVitals_Audit::get_scan_comparison_data($results);

// Score classifications
function get_score_class($score) {
    if ($score >= 90) return 'grade-a';
    if ($score >= 80) return 'grade-b';
    if ($score >= 70) return 'grade-c';
    if ($score >= 60) return 'grade-d';
    return 'grade-f';
}

function get_score_grade($score) {
    if ($score >= 90) return 'A';
    if ($score >= 80) return 'B';
    if ($score >= 70) return 'C';
    if ($score >= 60) return 'D';
    return 'F';
}

function get_score_label($score) {
    if ($score >= 90) return __('Excellent', 'rayvitals');
    if ($score >= 80) return __('Good', 'rayvitals');
    if ($score >= 70) return __('Fair', 'rayvitals');
    if ($score >= 60) return __('Needs Work', 'rayvitals');
    return __('Poor', 'rayvitals');
}

// Function to get dynamic description based on score and category
function get_dynamic_description($score, $category_key) {
    $grade = get_score_grade($score);
    
    switch ($category_key) {
        case 'security':
            switch ($grade) {
                case 'A': return __('Excellent security with comprehensive protection measures', 'rayvitals');
                case 'B': return __('Good security with minor improvements recommended', 'rayvitals');
                case 'C': return __('Moderate security with some vulnerabilities to address', 'rayvitals');
                case 'D': return __('Poor security with significant risks that need attention', 'rayvitals');
                case 'F': return __('Critical security vulnerabilities requiring immediate action', 'rayvitals');
            }
            break;
            
        case 'performance':
            switch ($grade) {
                case 'A': return __('Outstanding performance with excellent load times', 'rayvitals');
                case 'B': return __('Good performance with minor optimization opportunities', 'rayvitals');
                case 'C': return __('Average performance with room for improvement', 'rayvitals');
                case 'D': return __('Poor performance causing user experience issues', 'rayvitals');
                case 'F': return __('Very slow performance severely impacting user experience', 'rayvitals');
            }
            break;
            
        case 'seo':
            switch ($grade) {
                case 'A': return __('Excellent SEO optimization for strong search visibility', 'rayvitals');
                case 'B': return __('Good SEO foundation with minor enhancements needed', 'rayvitals');
                case 'C': return __('Basic SEO setup with several improvement opportunities', 'rayvitals');
                case 'D': return __('Poor SEO implementation limiting search visibility', 'rayvitals');
                case 'F': return __('Critical SEO issues severely hurting search rankings', 'rayvitals');
            }
            break;
            
        case 'accessibility':
            switch ($grade) {
                case 'A': return __('Excellent accessibility ensuring inclusive user experience', 'rayvitals');
                case 'B': return __('Good accessibility with minor barriers to address', 'rayvitals');
                case 'C': return __('Basic accessibility with several improvements needed', 'rayvitals');
                case 'D': return __('Poor accessibility creating barriers for many users', 'rayvitals');
                case 'F': return __('Critical accessibility issues preventing inclusive access', 'rayvitals');
            }
            break;
            
        case 'ux':
            switch ($grade) {
                case 'A': return __('Exceptional user experience with intuitive design', 'rayvitals');
                case 'B': return __('Good user experience with minor enhancements possible', 'rayvitals');
                case 'C': return __('Average user experience with room for improvement', 'rayvitals');
                case 'D': return __('Poor user experience causing friction and confusion', 'rayvitals');
                case 'F': return __('Very poor user experience driving users away', 'rayvitals');
            }
            break;
    }
    
    // Fallback
    return __('Analysis complete - see details for specific recommendations', 'rayvitals');
}
?>

<div class="rayvitals-results-container">
    <!-- Header -->
    <div class="results-header">
        <h1 class="results-header-title"><?php _e('Website Audit Results', 'rayvitals'); ?></h1>
        <div class="scan-info-top">
            <?php if ($url): ?>
                <div class="website-url-inline"><?php echo esc_html($url); ?></div>
            <?php endif; ?>
            
            <?php if ($comparison_data['has_previous'] && $comparison_data['time_since_last']): ?>
                <div class="divider">•</div>
                <div class="last-updated-inline">
                    <?php _e('Last scan:', 'rayvitals'); ?> 
                    <?php echo esc_html($comparison_data['time_since_last']) . ' ' . __('ago', 'rayvitals'); ?>
                </div>
            <?php else: ?>
                <div class="divider">•</div>
                <div class="first-scan-inline">
                    <?php _e('First scan for this URL', 'rayvitals'); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($comparison_data['score_improvement']): ?>
                <div class="divider">•</div>
                <div class="improvement-inline">
                    <?php 
                    $improvement = $comparison_data['score_improvement'];
                    $sign = $improvement['improvement'] >= 0 ? '+' : '';
                    $class = $improvement['is_improvement'] ? 'improvement-value' : ($improvement['is_decline'] ? 'decline-value' : 'no-change-value');
                    ?>
                    <span class="<?php echo esc_attr($class); ?>">
                        <?php echo $sign . $improvement['improvement']; ?>
                    </span> 
                    <?php _e('points vs last scan', 'rayvitals'); ?>
                </div>
            <?php endif; ?>
            
            <div class="divider">•</div>
            <div class="issues-inline">
                <span class="issues-value"><?php echo esc_html($comparison_data['issues_count']); ?></span> 
                <?php echo _n('issue found', 'issues found', $comparison_data['issues_count'], 'rayvitals'); ?>
            </div>
        </div>
    </div>

    <!-- Top Section: AI Summary + Overall Grade -->
    <div class="top-section">
        <!-- AI Summary -->
        <?php if ($ai_summary): ?>
            <div class="ai-summary-card">
                <div class="card-header">
                    <div class="card-title-with-icon">
                        <svg class="icon-white" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L2 7L2 17L12 22L22 17L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 22V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M22 7L12 12L2 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 17L12 12L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h3><?php _e('AI Summary', 'rayvitals'); ?></h3>
                    </div>
                    <span class="status-badge updated"><?php _e('Updated', 'rayvitals'); ?></span>
                </div>
                <div class="ai-summary-content">
                    <?php 
                    // Parse structured AI summary sections
                    if ($ai_summary && strpos($ai_summary, 'EXECUTIVE SUMMARY:') !== false) {
                        // Split AI summary into sections
                        $sections = array();
                        $content_parts = preg_split('/\n\n(?=[A-Z][A-Z\s]+:)/', $ai_summary);
                        
                        foreach ($content_parts as $part) {
                            $part = trim($part);
                            if (empty($part)) continue;
                            
                            // Extract section title and content
                            if (preg_match('/^([A-Z][A-Z\s]+):\s*(.+)$/s', $part, $matches)) {
                                $title = trim($matches[1]);
                                $content = trim($matches[2]);
                                $sections[$title] = $content;
                            } else {
                                // If no clear section header, add to a general section
                                $sections['SUMMARY'] = $part;
                            }
                        }
                        
                        // Display only Executive Summary and Key Recommendations
                        $allowed_sections = ['EXECUTIVE SUMMARY', 'KEY RECOMMENDATIONS'];
                        foreach ($sections as $title => $content) {
                            if (!in_array($title, $allowed_sections)) {
                                continue;
                            }
                            
                            $section_id = strtolower(str_replace(' ', '-', $title));
                            ?>
                            <div class="ai-summary-section">
                                <h4 class="ai-section-title"><?php echo esc_html($title); ?></h4>
                                <div class="ai-section-content">
                                    <?php 
                                    // Handle different content types
                                    if (strpos($content, '- ') !== false && strpos($title, 'RECOMMENDATIONS') !== false) {
                                        // Handle bullet points for recommendations
                                        $lines = explode("\n", $content);
                                        echo '<ul class="ai-recommendations-list">';
                                        foreach ($lines as $line) {
                                            $line = trim($line);
                                            if (strpos($line, '- ') === 0) {
                                                echo '<li>' . esc_html(substr($line, 2)) . '</li>';
                                            } else if (!empty($line)) {
                                                echo '<li>' . esc_html($line) . '</li>';
                                            }
                                        }
                                        echo '</ul>';
                                    } else {
                                        // Regular paragraph content
                                        $paragraphs = explode("\n", $content);
                                        foreach ($paragraphs as $paragraph) {
                                            $paragraph = trim($paragraph);
                                            if (!empty($paragraph)) {
                                                echo '<p>' . esc_html($paragraph) . '</p>';
                                            }
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        // Fallback for unstructured content
                        echo '<div class="ai-summary-fallback">' . wp_kses_post($ai_summary) . '</div>';
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Wins Section -->
        <?php if (!empty($detailed_results['quick_wins'])): ?>
        <div class="quick-wins-section">
            <button class="quick-wins-button" onclick="openQuickWinsModal()">
                <svg class="icon-white" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13 2L3 14H12L11 22L21 10H12L13 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span><?php _e('Quick Wins', 'rayvitals'); ?></span>
                <small><?php echo sprintf(__('%d actionable improvements', 'rayvitals'), count($detailed_results['quick_wins'])); ?></small>
            </button>
        </div>
        <?php endif; ?>

        <!-- Overall Grade -->
        <div class="overall-grade-card <?php echo get_score_class($overall_score); ?>">
            <div class="card-header">
                <h3><?php _e('Overall Website Grade', 'rayvitals'); ?></h3>
                <button class="details-link" id="overall-grade-details">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 9h6v6h-6z" stroke="currentColor" stroke-width="2"/>
                        <path d="M3 12V7a4 4 0 014-4h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4v-5z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <?php _e('Details', 'rayvitals'); ?>
                </button>
            </div>
            <div class="grade-display">
                <div class="grade-letter"><?php echo get_score_grade($overall_score); ?></div>
                <div class="grade-info">
                    <div class="score"><?php echo esc_html($overall_score); ?> <span class="out-of"><?php _e('out of 100', 'rayvitals'); ?></span></div>
                    <div class="vs-last-scan"><?php _e('vs last scan', 'rayvitals'); ?></div>
                    <div class="performance-label"><?php echo get_score_label($overall_score); ?></div>
                </div>
            </div>
            <?php if ($overall_percentile): ?>
            <div class="overall-benchmarking">
                <?php echo esc_html($overall_percentile); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>


    <!-- Category Scores -->
    <div class="category-grid">
        <?php
        $categories = array(
            'security' => array(
                'title' => __('Security', 'rayvitals'),
                'score_key' => 'security_score',
                'icon' => 'shield'
            ),
            'performance' => array(
                'title' => __('Speed', 'rayvitals'),
                'score_key' => 'performance_score',
                'icon' => 'zap'
            ),
            'seo' => array(
                'title' => __('SEO', 'rayvitals'),
                'score_key' => 'seo_score',
                'icon' => 'search'
            ),
            'accessibility' => array(
                'title' => __('Accessibility', 'rayvitals'),
                'score_key' => 'accessibility_score',
                'icon' => 'eye'
            ),
            'ux' => array(
                'title' => __('User Experience', 'rayvitals'),
                'score_key' => 'ux_score',
                'icon' => 'users'
            )
        );

        foreach ($categories as $category_key => $category): 
            $category_score = isset($results->{$category['score_key']}) ? round($results->{$category['score_key']}) : 0;
            $category_data = isset($detailed_results[$category_key]) ? $detailed_results[$category_key] : array();
            $benchmarking_data = isset($detailed_results['benchmarking']) ? $detailed_results['benchmarking'] : array();
            $category_percentile = isset($benchmarking_data[$category_key]) ? $benchmarking_data[$category_key] : '';
        ?>
            <div class="metric-card <?php echo get_score_class($category_score); ?>">
                <div class="card-header">
                    <div class="card-title-row">
                        <div class="card-title-with-icon">
                            <?php 
                            $icon_svg = '';
                            switch($category_key) {
                                case 'security':
                                    $icon_svg = '<svg class="icon-white" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 1L21 5V11C21 16.55 17.16 21.74 12 23C6.84 21.74 3 16.55 3 11V5L12 1Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>';
                                    break;
                                case 'performance':
                                    $icon_svg = '<svg class="icon-white" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 2L3 14H12L11 22L21 10H12L13 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                                    break;
                                case 'seo':
                                    $icon_svg = '<svg class="icon-white" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/><path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                                    break;
                                case 'accessibility':
                                    $icon_svg = '<svg class="icon-white" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>';
                                    break;
                                case 'ux':
                                    $icon_svg = '<svg class="icon-white" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><path d="M23 21V19C23 18.1645 22.7155 17.3541 22.2094 16.7001C21.7033 16.046 20.9975 15.5902 20.194 15.4097" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 3.13C16.8039 3.30901 17.5102 3.76509 18.0168 4.41977C18.5234 5.07444 18.8079 5.88592 18.8079 6.72185C18.8079 7.55778 18.5234 8.36925 18.0168 9.02393C17.5102 9.6786 16.8039 10.1347 16 10.3137" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                                    break;
                                default:
                                    $icon_svg = '';
                            }
                            echo $icon_svg;
                            ?>
                            <h3 class="card-title"><?php echo esc_html($category['title']); ?></h3>
                        </div>
                        <button class="details-link" data-category="<?php echo esc_attr($category_key); ?>" data-category-title="<?php echo esc_attr($category['title']); ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 9h6v6h-6z" stroke="currentColor" stroke-width="2"/>
                                <path d="M3 12V7a4 4 0 014-4h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4v-5z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <?php _e('Details', 'rayvitals'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="grade-display">
                    <div class="grade-letter"><?php echo get_score_grade($category_score); ?></div>
                    <div class="grade-details">
                        <div class="score"><?php echo esc_html($category_score); ?></div>
                        <div class="out-of">/ 100</div>
                    </div>
                </div>
                
                <?php if ($category_percentile): ?>
                <div class="percentile-context">
                    <?php echo esc_html($category_percentile); ?>
                </div>
                <?php endif; ?>
                
                <div class="card-description">
                    <?php echo esc_html(get_dynamic_description($category_score, $category_key)); ?>
                </div>
                
                <!-- Hidden data for modal -->
                <script type="application/json" class="category-data-json">
                    <?php echo json_encode(array(
                        'issues' => $category_data['issues'] ?? array(),
                        'recommendations' => $category_data['recommendations'] ?? array()
                    )); ?>
                </script>
            </div>
        <?php endforeach; ?>
    </div>


    <!-- Call to Action -->
    <div class="cta-section">
        <h3><?php _e('Need Professional Help?', 'rayvitals'); ?></h3>
        <p><?php _e('Our team of experts can help you implement these recommendations and improve your website\'s performance, security, and SEO.', 'rayvitals'); ?></p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=rayvitals')); ?>" class="cta-button">
            <?php _e('Get Professional Support', 'rayvitals'); ?>
        </a>
    </div>
</div>

<!-- Details Modal -->
<div class="rayvitals-modal" id="details-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"></h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-section security-education-section" style="display: none;">
                <h4><?php _e('About Security Headers', 'rayvitals'); ?></h4>
                <div class="security-education-content">
                    <p><?php _e('Security headers are HTTP response headers that help protect your website from common attacks and vulnerabilities. They work by instructing browsers how to handle certain security features.', 'rayvitals'); ?></p>
                    <div class="security-headers-guide">
                        <div class="header-explanation">
                            <strong>Content Security Policy (CSP):</strong> <?php _e('Prevents code injection attacks by controlling which resources can be loaded.', 'rayvitals'); ?>
                        </div>
                        <div class="header-explanation">
                            <strong>Cross-Origin Embedder Policy:</strong> <?php _e('Controls how your site can be embedded in other websites.', 'rayvitals'); ?>
                        </div>
                        <div class="header-explanation">
                            <strong>Cross-Origin Opener Policy:</strong> <?php _e('Isolates your browsing context from cross-origin windows.', 'rayvitals'); ?>
                        </div>
                        <div class="header-explanation">
                            <strong>Certificate Transparency:</strong> <?php _e('Helps detect and prevent the use of fraudulent SSL certificates.', 'rayvitals'); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-section issues-section" style="display: none;">
                <h4><?php _e('Issues Found', 'rayvitals'); ?></h4>
                <ul class="issues-list"></ul>
            </div>
            <div class="modal-section recommendations-section" style="display: none;">
                <h4><?php _e('Recommendations', 'rayvitals'); ?></h4>
                <ul class="recommendations-list"></ul>
            </div>
        </div>
    </div>
</div>

<!-- Overall Grade Details Modal -->
<div class="rayvitals-modal" id="overall-grade-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><?php _e('Overall Grade Details', 'rayvitals'); ?></h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-section">
                <p class="grade-explanation">
                    <?php _e('Your overall grade is calculated by averaging all individual metric scores, with security and performance weighted more heavily due to their business impact.', 'rayvitals'); ?>
                </p>
            </div>
            <div class="modal-section grade-scale-section">
                <h4><?php _e('Grade Scale', 'rayvitals'); ?></h4>
                <div class="grade-scale-list">
                    <div class="grade-scale-item">
                        <span class="grade-range">A (90-100)</span>
                        <span class="grade-label excellent"><?php _e('Excellent', 'rayvitals'); ?></span>
                    </div>
                    <div class="grade-scale-item">
                        <span class="grade-range">B (80-89)</span>
                        <span class="grade-label good"><?php _e('Good', 'rayvitals'); ?></span>
                    </div>
                    <div class="grade-scale-item">
                        <span class="grade-range">C (60-79)</span>
                        <span class="grade-label needs-improvement"><?php _e('Needs Improvement', 'rayvitals'); ?></span>
                    </div>
                    <div class="grade-scale-item">
                        <span class="grade-range">D-F (0-59)</span>
                        <span class="grade-label poor"><?php _e('Poor', 'rayvitals'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Wins Modal -->
<div class="rayvitals-modal" id="quick-wins-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><?php _e('Quick Wins - High Impact Improvements', 'rayvitals'); ?></h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="quick-wins-intro">
                <p><?php _e('Focus on these high-impact, low-effort improvements to see immediate results for your website.', 'rayvitals'); ?></p>
            </div>
            <div class="quick-wins-grid" id="quick-wins-list">
                <!-- Quick wins will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
// Add modal and interactive functionality
jQuery(document).ready(function($) {
    // Overall grade details modal
    $('#overall-grade-details').on('click', function() {
        $('#overall-grade-modal').fadeIn();
    });
    
    // Details button click handler
    $('.details-link').on('click', function() {
        var $card = $(this).closest('.metric-card');
        var categoryTitle = $(this).data('category-title');
        var categoryData = JSON.parse($card.find('.category-data-json').text());
        
        // Update modal title
        $('#details-modal .modal-title').text(categoryTitle + ' Details');
        
        // Clear previous content
        $('#details-modal .issues-list').empty();
        $('#details-modal .recommendations-list').empty();
        
        // Populate issues
        if (categoryData.issues && categoryData.issues.length > 0) {
            $('.issues-section').show();
            categoryData.issues.forEach(function(issue) {
                var issueText = '';
                if (typeof issue === 'object') {
                    issueText = issue.message || issue.description || issue.title || 'Issue detected';
                } else {
                    issueText = issue;
                }
                $('#details-modal .issues-list').append('<li>' + escapeHtml(issueText) + '</li>');
            });
        } else {
            $('.issues-section').hide();
        }
        
        // Populate recommendations
        if (categoryData.recommendations && categoryData.recommendations.length > 0) {
            $('.recommendations-section').show();
            categoryData.recommendations.forEach(function(rec) {
                var recText = '';
                if (typeof rec === 'object') {
                    recText = rec.message || rec.description || rec.title || 'Recommendation available';
                } else {
                    recText = rec;
                }
                $('#details-modal .recommendations-list').append('<li>' + escapeHtml(recText) + '</li>');
            });
        } else {
            $('.recommendations-section').hide();
        }
        
        // Show modal
        $('#details-modal').fadeIn();
    });
    
    // Close modal handlers
    $('.modal-close, .modal-overlay').on('click', function() {
        $('.rayvitals-modal').fadeOut();
    });
    
    // Escape key to close modal
    $(document).on('keyup', function(e) {
        if (e.key === "Escape") {
            $('.rayvitals-modal').fadeOut();
        }
    });
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Quick Wins Modal functionality
    window.openQuickWinsModal = function() {
        var quickWinsData = <?php echo json_encode($detailed_results['quick_wins'] ?? []); ?>;
        
        if (quickWinsData.length === 0) {
            alert('<?php _e('No quick wins available for this audit.', 'rayvitals'); ?>');
            return;
        }
        
        // Clear previous content
        $('#quick-wins-list').empty();
        
        // Populate quick wins
        quickWinsData.forEach(function(quickWin) {
            var categoryClass = quickWin.category.toLowerCase().replace(/\s+/g, '');
            
            var cardHtml = `
                <div class="quick-win-card">
                    <div class="quick-win-header">
                        <span class="quick-win-category ${categoryClass}">${escapeHtml(quickWin.category)}</span>
                        <div class="quick-win-meta">
                            <span class="quick-win-time">⏱️ ${escapeHtml(quickWin.time_estimate)}</span>
                            <span class="quick-win-difficulty">${escapeHtml(quickWin.difficulty)}</span>
                        </div>
                    </div>
                    
                    <h4 class="quick-win-title">${escapeHtml(quickWin.action)}</h4>
                    
                    <div class="quick-win-impact">
                        <strong><?php _e('Business Impact:', 'rayvitals'); ?></strong> ${escapeHtml(quickWin.business_impact)}
                    </div>
                    
                    <div class="quick-win-revenue">
                        💰 <strong><?php _e('Revenue Impact:', 'rayvitals'); ?></strong> ${escapeHtml(quickWin.revenue_impact)}
                    </div>
                    
                    <div class="quick-win-implementation">
                        <div class="quick-win-implementation-title"><?php _e('How to Implement:', 'rayvitals'); ?></div>
                        <div class="quick-win-implementation-steps">${escapeHtml(quickWin.implementation)}</div>
                    </div>
                    
                    <div class="quick-win-verification">
                        <div class="quick-win-verification-title"><?php _e('How to Verify:', 'rayvitals'); ?></div>
                        <div class="quick-win-verification-text">${escapeHtml(quickWin.verification)}</div>
                    </div>
                </div>
            `;
            
            $('#quick-wins-list').append(cardHtml);
        });
        
        // Show modal
        $('#quick-wins-modal').fadeIn();
    };
});
</script>