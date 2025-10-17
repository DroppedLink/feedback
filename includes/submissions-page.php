<?php
/**
 * Admin submissions page for User Feedback plugin
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Submissions dashboard page
 */
function user_feedback_submissions_page() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    // Get filter parameters
    $filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : '';
    $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
    $filter_search = isset($_GET['filter_search']) ? sanitize_text_field($_GET['filter_search']) : '';
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    
    $per_page = 20;
    $offset = ($paged - 1) * $per_page;
    
    // Build query args
    $args = array(
        'limit' => $per_page,
        'offset' => $offset,
        'orderby' => 'created_at',
        'order' => 'DESC'
    );
    
    if (!empty($filter_type)) {
        $args['type'] = $filter_type;
    }
    if (!empty($filter_status)) {
        $args['status'] = $filter_status;
    }
    if (!empty($filter_search)) {
        $args['search'] = $filter_search;
    }
    
    // Get submissions
    $submissions = user_feedback_get_submissions($args);
    $total = user_feedback_get_submissions_count($args);
    $total_pages = ceil($total / $per_page);
    
    // Get statistics
    $stats = array(
        'total' => user_feedback_get_submissions_count(array()),
        'new' => user_feedback_get_submissions_count(array('status' => 'new')),
        'in_progress' => user_feedback_get_submissions_count(array('status' => 'in_progress')),
        'resolved' => user_feedback_get_submissions_count(array('status' => 'resolved')),
        'comments' => user_feedback_get_submissions_count(array('type' => 'comment')),
        'bugs' => user_feedback_get_submissions_count(array('type' => 'bug'))
    );
    ?>
    <div class="wrap">
        <h1>User Feedback Dashboard</h1>
        
        <!-- Statistics -->
        <div class="user-feedback-stats">
            <div class="stat-box">
                <div class="stat-number"><?php echo esc_html($stats['total']); ?></div>
                <div class="stat-label">Total Submissions</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo esc_html($stats['new']); ?></div>
                <div class="stat-label">New</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo esc_html($stats['in_progress']); ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo esc_html($stats['resolved']); ?></div>
                <div class="stat-label">Resolved</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo esc_html($stats['comments']); ?></div>
                <div class="stat-label">Comments</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo esc_html($stats['bugs']); ?></div>
                <div class="stat-label">Bugs</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="user-feedback-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="user-feedback">
                
                <select name="filter_type">
                    <option value="">All Types</option>
                    <option value="comment" <?php selected($filter_type, 'comment'); ?>>Comments</option>
                    <option value="bug" <?php selected($filter_type, 'bug'); ?>>Bugs</option>
                </select>
                
                <select name="filter_status">
                    <option value="">All Statuses</option>
                    <option value="new" <?php selected($filter_status, 'new'); ?>>New</option>
                    <option value="in_progress" <?php selected($filter_status, 'in_progress'); ?>>In Progress</option>
                    <option value="testing" <?php selected($filter_status, 'testing'); ?>>Testing</option>
                    <option value="resolved" <?php selected($filter_status, 'resolved'); ?>>Resolved</option>
                    <option value="wont_fix" <?php selected($filter_status, 'wont_fix'); ?>>Won't Fix</option>
                </select>
                
                <input type="text" 
                       name="filter_search" 
                       placeholder="Search submissions..." 
                       value="<?php echo esc_attr($filter_search); ?>">
                
                <button type="submit" class="button">Filter</button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=user-feedback')); ?>" class="button">Reset</a>
                
                <?php
                $export_url = wp_nonce_url(
                    add_query_arg(array(
                        'action' => 'user_feedback_export',
                        'filter_type' => $filter_type,
                        'filter_status' => $filter_status
                    ), admin_url('admin.php')),
                    'user_feedback_export'
                );
                ?>
                <a href="<?php echo esc_url($export_url); ?>" class="button button-secondary">Export CSV</a>
            </form>
        </div>
        
        <!-- Submissions List -->
        <div class="user-feedback-list">
            <?php if (empty($submissions)): ?>
                <p>No submissions found.</p>
            <?php else: ?>
                <?php foreach ($submissions as $submission): ?>
                    <?php
                    $user = get_userdata($submission->user_id);
                    $user_name = $user ? $user->display_name : 'Unknown User';
                    $status_class = 'status-' . str_replace('_', '-', $submission->status);
                    $type_class = 'type-' . $submission->type;
                    ?>
                    <div class="submission-item <?php echo esc_attr($status_class . ' ' . $type_class); ?>" data-id="<?php echo esc_attr($submission->id); ?>">
                        <div class="submission-header">
                            <div class="submission-meta">
                                <span class="submission-type badge badge-<?php echo esc_attr($submission->type); ?>">
                                    <?php echo esc_html(ucfirst($submission->type)); ?>
                                </span>
                                <span class="submission-status badge badge-<?php echo esc_attr($submission->status); ?>">
                                    <?php echo esc_html(str_replace('_', ' ', ucfirst($submission->status))); ?>
                                </span>
                                <?php if (!empty($submission->context_id)): ?>
                                    <span class="submission-context">
                                        Context: <?php echo esc_html($submission->context_id); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="submission-date">
                                <?php echo esc_html(date('M d, Y g:i A', strtotime($submission->created_at))); ?>
                            </div>
                        </div>
                        
                        <div class="submission-content">
                            <h3><?php echo esc_html($submission->subject); ?></h3>
                            <p class="submission-user">By: <?php echo esc_html($user_name); ?></p>
                            <div class="submission-message">
                                <?php echo nl2br(esc_html($submission->message)); ?>
                            </div>
                            
                            <?php if (!empty($submission->admin_reply)): ?>
                                <div class="submission-reply">
                                    <strong>Your Reply:</strong>
                                    <div><?php echo nl2br(esc_html($submission->admin_reply)); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($submission->resolution_notes)): ?>
                                <div class="submission-resolution">
                                    <strong>Resolution Notes:</strong>
                                    <div><?php echo nl2br(esc_html($submission->resolution_notes)); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($submission->attachment_id)): ?>
                                <div class="submission-attachment-section">
                                    <strong>Attached Screenshot:</strong>
                                    <?php echo user_feedback_get_attachment_html($submission->attachment_id, 'medium'); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($submission->metadata)): ?>
                                <?php
                                $metadata = json_decode($submission->metadata, true);
                                if ($metadata && is_array($metadata)):
                                ?>
                                    <div class="submission-metadata">
                                        <details>
                                            <summary><strong>Technical Details</strong> (Auto-collected)</summary>
                                            <div class="metadata-content">
                                                <?php if (!empty($metadata['pageUrl'])): ?>
                                                    <p><strong>Page URL:</strong> <a href="<?php echo esc_url($metadata['pageUrl']); ?>" target="_blank"><?php echo esc_html($metadata['pageUrl']); ?></a></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($metadata['pageTitle'])): ?>
                                                    <p><strong>Page Title:</strong> <?php echo esc_html($metadata['pageTitle']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($metadata['referrer']) && $metadata['referrer'] !== 'Direct'): ?>
                                                    <p><strong>Referrer:</strong> <?php echo esc_html($metadata['referrer']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($metadata['userAgent'])): ?>
                                                    <p><strong>Browser:</strong> <?php echo esc_html($metadata['userAgent']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($metadata['screenWidth']) && !empty($metadata['screenHeight'])): ?>
                                                    <p><strong>Screen Size:</strong> <?php echo esc_html($metadata['screenWidth']); ?> x <?php echo esc_html($metadata['screenHeight']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($metadata['viewportWidth']) && !empty($metadata['viewportHeight'])): ?>
                                                    <p><strong>Viewport Size:</strong> <?php echo esc_html($metadata['viewportWidth']); ?> x <?php echo esc_html($metadata['viewportHeight']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($metadata['timezone'])): ?>
                                                    <p><strong>Timezone:</strong> <?php echo esc_html($metadata['timezone']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($metadata['language'])): ?>
                                                    <p><strong>Language:</strong> <?php echo esc_html($metadata['language']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($metadata['platform'])): ?>
                                                    <p><strong>Platform:</strong> <?php echo esc_html($metadata['platform']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($metadata['timestamp'])): ?>
                                                    <p><strong>Captured:</strong> <?php echo esc_html($metadata['timestamp']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($metadata['consoleErrors']) && is_array($metadata['consoleErrors'])): ?>
                                                    <p><strong>Console Errors:</strong></p>
                                                    <div class="console-errors-admin">
                                                        <?php foreach ($metadata['consoleErrors'] as $error): ?>
                                                            <div class="console-error-item">
                                                                <?php echo esc_html($error['message']); ?>
                                                                <?php if (!empty($error['source'])): ?>
                                                                    <br><small><?php echo esc_html($error['source']); ?><?php if (!empty($error['line'])): ?>:<?php echo esc_html($error['line']); ?><?php endif; ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </details>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="submission-actions">
                            <button class="button button-primary reply-button" data-id="<?php echo esc_attr($submission->id); ?>">
                                Reply
                            </button>
                            
                            <select class="status-selector" data-id="<?php echo esc_attr($submission->id); ?>">
                                <option value="">Change Status...</option>
                                <option value="new">New</option>
                                <option value="in_progress">In Progress</option>
                                <option value="testing">Testing</option>
                                <option value="resolved">Resolved</option>
                                <option value="wont_fix">Won't Fix</option>
                            </select>
                            
                            <button class="button button-secondary delete-button" data-id="<?php echo esc_attr($submission->id); ?>">
                                Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    $base_url = remove_query_arg('paged');
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%', $base_url),
                        'format' => '',
                        'current' => $paged,
                        'total' => $total_pages,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;'
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Reply Modal -->
    <div id="reply-modal" class="user-feedback-modal" style="display:none;">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Send Reply</h2>
            
            <div class="canned-responses-section">
                <label>Use Canned Response:</label>
                <select id="canned-response-selector">
                    <option value="">-- Select a response --</option>
                    <?php
                    $canned_responses = user_feedback_get_canned_responses();
                    foreach ($canned_responses as $response):
                    ?>
                        <option value="<?php echo esc_attr($response->id); ?>">
                            <?php echo esc_html($response->title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <textarea id="reply-message" rows="8" placeholder="Type your reply..."></textarea>
            
            <div class="modal-actions">
                <button class="button button-primary" id="send-reply-button">Send Reply</button>
                <button class="button" id="cancel-reply-button">Cancel</button>
            </div>
        </div>
    </div>
    
    <!-- Status Update Modal -->
    <div id="status-modal" class="user-feedback-modal" style="display:none;">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Update Status</h2>
            
            <p>Changing status to: <strong id="status-display"></strong></p>
            
            <div id="resolution-notes-section" style="display:none;">
                <label for="resolution-notes">Resolution Notes (optional):</label>
                <textarea id="resolution-notes" rows="6" placeholder="Describe how this was resolved..."></textarea>
                <p class="description">These notes will be sent to the user and displayed in the changelog.</p>
            </div>
            
            <div class="modal-actions">
                <button class="button button-primary" id="update-status-button">Update Status</button>
                <button class="button" id="cancel-status-button">Cancel</button>
            </div>
        </div>
    </div>
    <?php
}

