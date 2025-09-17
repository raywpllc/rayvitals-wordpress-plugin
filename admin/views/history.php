<?php
/**
 * RayVitals Audit History View
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'rayvitals_audits';

// Handle pagination
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($paged - 1) * $per_page;

// Handle search
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$search_clause = '';
if (!empty($search)) {
    $search_clause = $wpdb->prepare(' WHERE url LIKE %s OR email LIKE %s', '%' . $search . '%', '%' . $search . '%');
}

// Get total count
$total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $search_clause");

// Get audits
$audits = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_name $search_clause ORDER BY created_at DESC LIMIT %d OFFSET %d",
    $per_page,
    $offset
));

// Calculate pagination
$total_pages = ceil($total_items / $per_page);
?>

<div class="wrap">
    <h1><?php _e('Audit History', 'rayvitals'); ?></h1>
    
    <!-- Search Form -->
    <form method="get" class="search-form">
        <input type="hidden" name="page" value="rayvitals-history">
        <p class="search-box">
            <label class="screen-reader-text" for="audit-search-input"><?php _e('Search Audits:', 'rayvitals'); ?></label>
            <input type="search" id="audit-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search by URL or email...', 'rayvitals'); ?>">
            <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e('Search', 'rayvitals'); ?>">
        </p>
    </form>
    
    <!-- Stats Summary -->
    <?php if (!$search): ?>
        <div class="rayvitals-stats-summary">
            <div class="stat-item">
                <strong><?php echo esc_html($total_items); ?></strong>
                <span><?php _e('Total Audits', 'rayvitals'); ?></span>
            </div>
            <div class="stat-item">
                <strong><?php echo esc_html($wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'completed'")); ?></strong>
                <span><?php _e('Completed', 'rayvitals'); ?></span>
            </div>
            <div class="stat-item">
                <strong><?php echo esc_html($wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'in_progress'")); ?></strong>
                <span><?php _e('In Progress', 'rayvitals'); ?></span>
            </div>
            <div class="stat-item">
                <strong><?php echo esc_html($wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'failed'")); ?></strong>
                <span><?php _e('Failed', 'rayvitals'); ?></span>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Audits Table -->
    <?php if (empty($audits)): ?>
        <div class="rayvitals-empty-state">
            <?php if ($search): ?>
                <p><?php printf(__('No audits found matching "%s".', 'rayvitals'), esc_html($search)); ?></p>
                <a href="<?php echo admin_url('admin.php?page=rayvitals-history'); ?>" class="button">
                    <?php _e('Clear Search', 'rayvitals'); ?>
                </a>
            <?php else: ?>
                <p><?php _e('No audits found. Start your first audit to see history here.', 'rayvitals'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=rayvitals-new-audit'); ?>" class="button button-primary">
                    <?php _e('Start First Audit', 'rayvitals'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="column-url"><?php _e('Website URL', 'rayvitals'); ?></th>
                    <th class="column-email"><?php _e('Email', 'rayvitals'); ?></th>
                    <th class="column-status"><?php _e('Status', 'rayvitals'); ?></th>
                    <th class="column-scores"><?php _e('Scores', 'rayvitals'); ?></th>
                    <th class="column-date"><?php _e('Date', 'rayvitals'); ?></th>
                    <th class="column-actions"><?php _e('Actions', 'rayvitals'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($audits as $audit): ?>
                    <tr>
                        <td class="column-url">
                            <strong>
                                <a href="<?php echo esc_url($audit->url); ?>" target="_blank" rel="noopener">
                                    <?php echo esc_html($audit->url); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            </strong>
                        </td>
                        <td class="column-email">
                            <?php echo $audit->email ? esc_html($audit->email) : '-'; ?>
                        </td>
                        <td class="column-status">
                            <span class="status-badge status-<?php echo esc_attr($audit->status); ?>">
                                <?php echo esc_html(ucfirst(str_replace('_', ' ', $audit->status))); ?>
                            </span>
                        </td>
                        <td class="column-scores">
                            <?php if ($audit->status === 'completed' && $audit->overall_score !== null): ?>
                                <div class="scores-grid">
                                    <div class="score-item">
                                        <span class="score-label"><?php _e('Overall', 'rayvitals'); ?></span>
                                        <span class="score-value score-<?php echo esc_attr(floor($audit->overall_score / 20)); ?>">
                                            <?php echo number_format($audit->overall_score, 1); ?>
                                        </span>
                                    </div>
                                    <?php if ($audit->security_score !== null): ?>
                                        <div class="score-item">
                                            <span class="score-label"><?php _e('Security', 'rayvitals'); ?></span>
                                            <span class="score-value score-<?php echo esc_attr(floor($audit->security_score / 20)); ?>">
                                                <?php echo number_format($audit->security_score, 1); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($audit->performance_score !== null): ?>
                                        <div class="score-item">
                                            <span class="score-label"><?php _e('Performance', 'rayvitals'); ?></span>
                                            <span class="score-value score-<?php echo esc_attr(floor($audit->performance_score / 20)); ?>">
                                                <?php echo number_format($audit->performance_score, 1); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="column-date">
                            <?php echo esc_html(mysql2date('M j, Y', $audit->created_at)); ?><br>
                            <small><?php echo esc_html(mysql2date('g:i A', $audit->created_at)); ?></small>
                        </td>
                        <td class="column-actions">
                            <?php if ($audit->status === 'completed'): ?>
                                <button class="button button-small" onclick="viewAuditResults('<?php echo esc_js($audit->audit_id); ?>')">
                                    <?php _e('View Results', 'rayvitals'); ?>
                                </button>
                            <?php elseif ($audit->status === 'in_progress'): ?>
                                <button class="button button-small" onclick="checkAuditStatus('<?php echo esc_js($audit->audit_id); ?>')">
                                    <?php _e('Check Status', 'rayvitals'); ?>
                                </button>
                            <?php elseif ($audit->status === 'failed'): ?>
                                <button class="button button-small" onclick="retryAudit('<?php echo esc_js($audit->url); ?>', '<?php echo esc_js($audit->email); ?>')">
                                    <?php _e('Retry', 'rayvitals'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <button class="button button-small button-link-delete" onclick="deleteAudit(<?php echo esc_js($audit->id); ?>)" title="<?php esc_attr_e('Delete audit', 'rayvitals'); ?>">
                                <?php _e('Delete', 'rayvitals'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    $pagination_args = array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $paged
                    );
                    
                    if ($search) {
                        $pagination_args['add_args'] = array('s' => $search);
                    }
                    
                    echo paginate_links($pagination_args);
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Results Modal -->
<div id="results-modal" class="rayvitals-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php _e('Audit Results', 'rayvitals'); ?></h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body" id="modal-results">
            <?php _e('Loading results...', 'rayvitals'); ?>
        </div>
    </div>
</div>

<style>
.rayvitals-stats-summary {
    display: flex;
    gap: 20px;
    margin: 20px 0;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.stat-item {
    text-align: center;
    flex: 1;
}

.stat-item strong {
    display: block;
    font-size: 24px;
    color: #0073aa;
}

.search-form {
    margin-bottom: 20px;
}

.rayvitals-empty-state {
    text-align: center;
    padding: 40px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-completed { background-color: #d4edda; color: #155724; }
.status-failed { background-color: #f8d7da; color: #721c24; }
.status-in_progress { background-color: #fff3cd; color: #856404; }

.scores-grid {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.score-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
}

.score-label {
    color: #666;
}

.score-value {
    display: inline-block;
    padding: 1px 4px;
    border-radius: 2px;
    color: white;
    font-weight: bold;
    min-width: 25px;
    text-align: center;
}

.score-0, .score-1 { background-color: #dc3232; }
.score-2, .score-3 { background-color: #ffb900; }
.score-4 { background-color: #46b450; }

.column-url { width: 25%; }
.column-email { width: 15%; }
.column-status { width: 10%; }
.column-scores { width: 20%; }
.column-date { width: 15%; }
.column-actions { width: 15%; }

.rayvitals-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #888;
    border-radius: 4px;
    width: 90%;
    max-width: 1000px;
    max-height: 80vh;
    overflow: hidden;
}

.modal-header {
    padding: 20px;
    background: #f1f1f1;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
}

.close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: black;
}

.modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}
</style>

<script>
function viewAuditResults(auditId) {
    // Show modal
    document.getElementById('results-modal').style.display = 'block';
    
    // Load results via AJAX
    jQuery.post(ajaxurl, {
        action: 'rayvitals_get_results',
        audit_id: auditId,
        nonce: '<?php echo wp_create_nonce('rayvitals_ajax'); ?>'
    }, function(response) {
        if (response.success) {
            document.getElementById('modal-results').innerHTML = response.data.html;
        } else {
            document.getElementById('modal-results').innerHTML = '<p><?php echo esc_js(__('Failed to load results.', 'rayvitals')); ?></p>';
        }
    }).fail(function() {
        document.getElementById('modal-results').innerHTML = '<p><?php echo esc_js(__('Connection error.', 'rayvitals')); ?></p>';
    });
}

function checkAuditStatus(auditId) {
    // Redirect to new audit page with status check
    window.location.href = '<?php echo admin_url('admin.php?page=rayvitals-new-audit&check_status='); ?>' + auditId;
}

function retryAudit(url, email) {
    // Redirect to new audit page with pre-filled data
    window.location.href = '<?php echo admin_url('admin.php?page=rayvitals-new-audit'); ?>&retry_url=' + encodeURIComponent(url) + '&retry_email=' + encodeURIComponent(email);
}

function deleteAudit(auditId) {
    if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this audit? This action cannot be undone.', 'rayvitals')); ?>')) {
        return;
    }
    
    jQuery.post(ajaxurl, {
        action: 'rayvitals_delete_audit',
        audit_id: auditId,
        nonce: '<?php echo wp_create_nonce('rayvitals_ajax'); ?>'
    }, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('<?php echo esc_js(__('Failed to delete audit.', 'rayvitals')); ?>');
        }
    });
}

function closeModal() {
    document.getElementById('results-modal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('results-modal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>