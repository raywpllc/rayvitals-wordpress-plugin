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
}