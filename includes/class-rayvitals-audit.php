<?php
/**
 * RayVitals Audit Handler
 *
 * Manages audit operations and data
 *
 * @package RayVitals
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RayVitals_Audit {
    
    /**
     * Get audit by ID
     *
     * @param string $audit_id Audit ID
     * @return object|null Audit data or null
     */
    public static function get_by_id($audit_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rayvitals_audits';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE audit_id = %s",
            $audit_id
        ));
    }
    
    /**
     * Get recent audits
     *
     * @param int $limit Number of audits to return
     * @param int $offset Offset for pagination
     * @return array Array of audit objects
     */
    public static function get_recent($limit = 10, $offset = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rayvitals_audits';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }
    
    /**
     * Get audits by URL
     *
     * @param string $url URL to search for
     * @param int $limit Number of audits to return
     * @return array Array of audit objects
     */
    public static function get_by_url($url, $limit = 10) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rayvitals_audits';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE url = %s ORDER BY created_at DESC LIMIT %d",
            $url,
            $limit
        ));
    }
    
    /**
     * Get total audit count
     *
     * @return int Total number of audits
     */
    public static function get_total_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rayvitals_audits';
        
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }
    
    /**
     * Create a new audit record
     *
     * @param array $data Audit data
     * @return int|false Insert ID on success, false on failure
     */
    public static function create_audit($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rayvitals_audits';
        
        // Prepare data with defaults
        $insert_data = array(
            'audit_id' => $data['audit_id'],
            'url' => $data['url'],
            'status' => isset($data['status']) ? $data['status'] : 'pending',
            'created_at' => current_time('mysql')
        );
        
        // Prepare format array
        $formats = array(
            '%s', // audit_id
            '%s', // url
            '%s', // status
            '%s'  // created_at
        );
        
        // Optional fields
        if (isset($data['email'])) {
            $insert_data['email'] = $data['email'];
            $formats[] = '%s'; // email
        }
        
        $result = $wpdb->insert(
            $table_name,
            $insert_data,
            $formats
        );
        
        if ($result === false) {
            error_log('RayVitals: Failed to create audit record. Error: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Delete audit
     *
     * @param string $audit_id Audit ID to delete
     * @return bool True on success, false on failure
     */
    public static function delete($audit_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rayvitals_audits';
        
        // Clear cache if exists
        $audit = self::get_by_id($audit_id);
        if ($audit) {
            delete_transient('rayvitals_audit_' . md5($audit->url));
        }
        
        return $wpdb->delete(
            $table_name,
            array('audit_id' => $audit_id),
            array('%s')
        ) !== false;
    }
    
    /**
     * Get audit statistics
     *
     * @return array Statistics data
     */
    public static function get_statistics() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rayvitals_audits';
        
        // Get average scores
        $averages = $wpdb->get_row(
            "SELECT 
                AVG(overall_score) as avg_overall,
                AVG(security_score) as avg_security,
                AVG(performance_score) as avg_performance,
                AVG(seo_score) as avg_seo,
                AVG(accessibility_score) as avg_accessibility,
                AVG(ux_score) as avg_ux
            FROM $table_name 
            WHERE status = 'completed'"
        );
        
        // Get audit counts by status
        $status_counts = $wpdb->get_results(
            "SELECT status, COUNT(*) as count 
            FROM $table_name 
            GROUP BY status",
            OBJECT_K
        );
        
        // Get audits by day for last 7 days
        $daily_audits = $wpdb->get_results(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
            FROM $table_name 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC"
        );
        
        return array(
            'averages'      => $averages,
            'status_counts' => $status_counts,
            'daily_audits'  => $daily_audits,
            'total_audits'  => self::get_total_count(),
        );
    }
    
    /**
     * Format score for display
     *
     * @param float $score Score value
     * @return string Formatted score with color class
     */
    public static function format_score($score) {
        if ($score === null) {
            return '<span class="score-badge score-unknown">--</span>';
        }
        
        $class = 'score-poor';
        if ($score >= 90) {
            $class = 'score-excellent';
        } elseif ($score >= 70) {
            $class = 'score-good';
        } elseif ($score >= 50) {
            $class = 'score-fair';
        }
        
        return sprintf(
            '<span class="score-badge %s">%d</span>',
            esc_attr($class),
            round($score)
        );
    }
    
    /**
     * Get score color
     *
     * @param float $score Score value
     * @return string Hex color code
     */
    public static function get_score_color($score) {
        if ($score >= 90) {
            return '#0cce6b'; // Green
        } elseif ($score >= 70) {
            return '#ffa400'; // Orange
        } elseif ($score >= 50) {
            return '#ff7e00'; // Dark orange
        } else {
            return '#ff4e42'; // Red
        }
    }
    
    /**
     * Get previous completed scan for the same URL
     *
     * @param string $url URL to search for
     * @param string $current_audit_id Current audit ID to exclude
     * @return object|null Previous audit data or null
     */
    public static function get_previous_scan_for_url($url, $current_audit_id = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rayvitals_audits';
        
        $where_clause = "WHERE url = %s AND status = 'completed'";
        $params = array($url);
        
        // Exclude current audit if provided
        if (!empty($current_audit_id)) {
            $where_clause .= " AND audit_id != %s";
            $params[] = $current_audit_id;
        }
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name $where_clause ORDER BY completed_at DESC LIMIT 1",
            $params
        ));
    }
    
    /**
     * Calculate score improvement compared to previous scan
     *
     * @param string $url URL to compare
     * @param float $current_score Current overall score
     * @param string $current_audit_id Current audit ID to exclude
     * @return array|null Array with improvement data or null
     */
    public static function calculate_score_improvement($url, $current_score, $current_audit_id = '') {
        $previous_scan = self::get_previous_scan_for_url($url, $current_audit_id);
        
        if (!$previous_scan || $previous_scan->overall_score === null) {
            return null;
        }
        
        $improvement = round($current_score - $previous_scan->overall_score);
        
        return array(
            'improvement' => $improvement,
            'previous_score' => round($previous_scan->overall_score),
            'current_score' => round($current_score),
            'is_improvement' => $improvement > 0,
            'is_decline' => $improvement < 0
        );
    }
    
    /**
     * Get time since last scan for the same URL
     *
     * @param string $url URL to check
     * @param string $current_audit_id Current audit ID to exclude
     * @return string|null Human readable time difference or null
     */
    public static function get_time_since_last_scan($url, $current_audit_id = '') {
        $previous_scan = self::get_previous_scan_for_url($url, $current_audit_id);
        
        if (!$previous_scan) {
            return null;
        }
        
        return human_time_diff(strtotime($previous_scan->completed_at), current_time('timestamp'));
    }
    
    /**
     * Count issues from current scan results
     *
     * @param string $results_json JSON string of audit results
     * @return int Total number of issues found
     */
    public static function count_current_issues($results_json) {
        if (empty($results_json)) {
            return 0;
        }
        
        $results = json_decode($results_json, true);
        if (!is_array($results)) {
            return 0;
        }
        
        $total_issues = 0;
        
        // Count issues across all categories
        foreach ($results as $category_data) {
            if (isset($category_data['issues']) && is_array($category_data['issues'])) {
                $total_issues += count($category_data['issues']);
            }
        }
        
        return $total_issues;
    }
    
    /**
     * Get comprehensive scan comparison data
     *
     * @param object $current_audit Current audit object
     * @return array Comparison data for display
     */
    public static function get_scan_comparison_data($current_audit) {
        $comparison_data = array(
            'has_previous' => false,
            'time_since_last' => null,
            'score_improvement' => null,
            'issues_count' => 0
        );
        
        if (!$current_audit) {
            return $comparison_data;
        }
        
        // Get time since last scan
        $time_since = self::get_time_since_last_scan($current_audit->url, $current_audit->audit_id);
        if ($time_since) {
            $comparison_data['has_previous'] = true;
            $comparison_data['time_since_last'] = $time_since;
        }
        
        // Get score improvement
        if ($current_audit->overall_score !== null) {
            $improvement = self::calculate_score_improvement(
                $current_audit->url, 
                $current_audit->overall_score, 
                $current_audit->audit_id
            );
            if ($improvement) {
                $comparison_data['score_improvement'] = $improvement;
            }
        }
        
        // Count current issues
        $comparison_data['issues_count'] = self::count_current_issues($current_audit->results);
        
        return $comparison_data;
    }
}