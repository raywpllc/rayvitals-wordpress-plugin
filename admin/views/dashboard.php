<?php
/**
 * RayVitals Dashboard View
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'rayvitals_audits';

// Get recent audits
$recent_audits = $wpdb->get_results(
    "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 10"
);

// Get stats
$total_audits = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$completed_audits = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'completed'");
$avg_score = $wpdb->get_var("SELECT AVG(overall_score) FROM $table_name WHERE overall_score IS NOT NULL");
?>

<div class="wrap">
    <h1><?php _e('RayVitals Dashboard', 'rayvitals'); ?></h1>
    
    <?php if (empty(get_option('rayvitals_api_key'))): ?>
        <div class="notice notice-warning">
            <p><?php printf(
                __('RayVitals API key not configured. Please <a href="%s">add your API key</a> to start auditing websites.', 'rayvitals'),
                admin_url('admin.php?page=rayvitals-settings')
            ); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="rayvitals-stats-grid">
        <div class="rayvitals-stat-card">
            <h3><?php _e('Total Audits', 'rayvitals'); ?></h3>
            <div class="stat-value"><?php echo esc_html($total_audits); ?></div>
        </div>
        
        <div class="rayvitals-stat-card">
            <h3><?php _e('Completed Audits', 'rayvitals'); ?></h3>
            <div class="stat-value"><?php echo esc_html($completed_audits); ?></div>
        </div>
        
        <div class="rayvitals-stat-card">
            <h3><?php _e('Average Score', 'rayvitals'); ?></h3>
            <div class="stat-value"><?php echo $avg_score ? number_format($avg_score, 1) : '-'; ?></div>
        </div>
        
        <div class="rayvitals-stat-card">
            <h3><?php _e('Quick Action', 'rayvitals'); ?></h3>
            <a href="<?php echo admin_url('admin.php?page=rayvitals-new-audit'); ?>" class="button button-primary">
                <?php _e('New Audit', 'rayvitals'); ?>
            </a>
        </div>
    </div>
    
    <!-- Recent Audits -->
    <h2><?php _e('Recent Audits', 'rayvitals'); ?></h2>
    
    <?php if (empty($recent_audits)): ?>
        <div class="rayvitals-empty-state">
            <p><?php _e('No audits found. Start your first audit to see results here.', 'rayvitals'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=rayvitals-new-audit'); ?>" class="button button-primary">
                <?php _e('Start First Audit', 'rayvitals'); ?>
            </a>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('URL', 'rayvitals'); ?></th>
                    <th><?php _e('Status', 'rayvitals'); ?></th>
                    <th><?php _e('Overall Score', 'rayvitals'); ?></th>
                    <th><?php _e('Date', 'rayvitals'); ?></th>
                    <th><?php _e('Actions', 'rayvitals'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_audits as $audit): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($audit->url); ?></strong>
                        </td>
                        <td>
                            <span class="status-<?php echo esc_attr($audit->status); ?>">
                                <?php echo esc_html(ucfirst($audit->status)); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($audit->overall_score !== null): ?>
                                <span class="score-badge score-<?php echo esc_attr(floor($audit->overall_score / 20)); ?>">
                                    <?php echo number_format($audit->overall_score, 1); ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(mysql2date('M j, Y g:i A', $audit->created_at)); ?></td>
                        <td>
                            <?php if ($audit->status === 'completed'): ?>
                                <a href="#" class="button button-small" onclick="viewAuditResults('<?php echo esc_js($audit->audit_id); ?>')">
                                    <?php _e('View Results', 'rayvitals'); ?>
                                </a>
                            <?php elseif ($audit->status === 'in_progress'): ?>
                                <a href="#" class="button button-small" onclick="checkAuditStatus('<?php echo esc_js($audit->audit_id); ?>')">
                                    <?php _e('Check Status', 'rayvitals'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p>
            <a href="<?php echo admin_url('admin.php?page=rayvitals-history'); ?>" class="button">
                <?php _e('View All Audits', 'rayvitals'); ?>
            </a>
        </p>
    <?php endif; ?>
</div>

<style>
.rayvitals-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.rayvitals-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.rayvitals-stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #0073aa;
}

.rayvitals-empty-state {
    text-align: center;
    padding: 40px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.status-completed { color: #46b450; }
.status-failed { color: #dc3232; }
.status-in_progress { color: #ffb900; }

.score-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    color: white;
    font-weight: bold;
}

.score-0, .score-1 { background-color: #dc3232; }
.score-2, .score-3 { background-color: #ffb900; }
.score-4 { background-color: #46b450; }
</style>