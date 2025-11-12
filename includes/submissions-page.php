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
    $filter_category = isset($_GET['filter_category']) ? intval($_GET['filter_category']) : '';
    $filter_form = isset($_GET['filter_form']) ? intval($_GET['filter_form']) : '';
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
    if (!empty($filter_category)) {
        $args['category_id'] = $filter_category;
    }
    if (!empty($filter_form)) {
        $args['form_id'] = $filter_form;
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
            <form method="get" action="" id="submissions-filter-form">
                <input type="hidden" name="page" value="user-feedback">
                
                <?php
                // Get categories and forms for filtering
                $all_categories = userfeedback_get_categories();
                
                // Get forms based on selected category, or all forms if no category selected
                $all_forms = array();
                if ($filter_category) {
                    // Get forms from specific category
                    $all_forms = userfeedback_get_forms(array('category_id' => $filter_category));
                } else {
                    // Get all forms from all categories
                    $all_forms = userfeedback_get_forms(array());
                }
                ?>
                
                <?php if (!empty($all_categories)): ?>
                <select name="filter_category" id="filter-category" data-nonce="<?php echo esc_attr(wp_create_nonce('user_feedback_nonce')); ?>">
                    <option value="">All Categories</option>
                    <?php foreach ($all_categories as $category): ?>
                        <option value="<?php echo esc_attr($category->id); ?>" 
                                <?php selected($filter_category, $category->id); ?>>
                            <?php echo esc_html($category->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="filter_form" id="filter-form">
                    <option value="">All Forms</option>
                    <?php if (!empty($all_forms)): ?>
                        <?php foreach ($all_forms as $form): ?>
                            <option value="<?php echo esc_attr($form->id); ?>" 
                                    <?php selected($filter_form, $form->id); ?>>
                                <?php 
                                // Show category name with form name for clarity when viewing all
                                if (!$filter_category && !empty($form->category_id)) {
                                    $form_category = userfeedback_get_category($form->category_id);
                                    echo esc_html($form_category ? $form_category->name . ' → ' : '') . esc_html($form->name);
                                } else {
                                    echo esc_html($form->name);
                                }
                                ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <?php endif; ?>
                
                <select name="filter_type">
                    <option value="">All Types (Legacy)</option>
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
                        'filter_category' => $filter_category,
                        'filter_form' => $filter_form,
                        'filter_status' => $filter_status
                    ), admin_url('admin.php')),
                    'user_feedback_export'
                );
                ?>
                <a href="<?php echo esc_url($export_url); ?>" class="button button-secondary">Export CSV</a>
            </form>
        </div>
        
        <!-- Bulk Actions -->
        <?php if (!empty($submissions)): ?>
        <div class="user-feedback-bulk-actions">
            <input type="checkbox" id="bulk-select-all">
            <label for="bulk-select-all">Select All</label>
            
            <select id="bulk-action-select">
                <option value="">Select Action</option>
                <option value="new">Mark as New</option>
                <option value="in_progress">Mark as In Progress</option>
                <option value="testing">Mark as Testing</option>
                <option value="resolved">Mark as Resolved</option>
                <option value="wont_fix">Mark as Won't Fix</option>
                <option value="delete">Delete</option>
            </select>
            
            <button type="button" id="apply-bulk-action" class="button" disabled>Bulk Action</button>
        </div>
        <?php endif; ?>
        
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
                    $type_class = $submission->type ? 'type-' . $submission->type : 'type-form';
                    
                    // Get form and category info
                    $form_name = '';
                    $category_name = '';
                    if (!empty($submission->form_id)) {
                        $form = userfeedback_get_form($submission->form_id);
                        if ($form) {
                            $form_name = $form->name;
                            $category = userfeedback_get_category($form->category_id);
                            if ($category) {
                                $category_name = $category->name;
                            }
                        }
                    }
                    ?>
                    <div class="submission-item <?php echo esc_attr($status_class . ' ' . $type_class); ?>" data-id="<?php echo esc_attr($submission->id); ?>">
                        <div class="submission-header" style="cursor: pointer;">
                            <div class="submission-checkbox-wrapper">
                                <input type="checkbox" class="submission-checkbox" value="<?php echo esc_attr($submission->id); ?>" onclick="event.stopPropagation();">
                            </div>
                            <div class="submission-meta">
                                <?php if ($form_name): ?>
                                    <span class="submission-form badge badge-form">
                                        <?php echo esc_html($category_name ? $category_name . ' / ' : ''); ?><?php echo esc_html($form_name); ?>
                                    </span>
                                <?php elseif ($submission->type): ?>
                                    <span class="submission-type badge badge-<?php echo esc_attr($submission->type); ?>">
                                        <?php echo esc_html(ucfirst($submission->type)); ?> (Legacy)
                                    </span>
                                <?php endif; ?>
                                <span class="submission-status badge badge-<?php echo esc_attr($submission->status); ?>">
                                    <?php echo esc_html(str_replace('_', ' ', ucfirst($submission->status))); ?>
                                </span>
                                <?php if (!empty($submission->context_id)): ?>
                                    <span class="submission-context">
                                        Context: <?php echo esc_html($submission->context_id); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="submission-header-right">
                                <span class="submission-date">
                                    <?php echo esc_html(date('M d, Y g:i A', strtotime($submission->created_at))); ?>
                                </span>
                                <span class="submission-toggle">▼</span>
                            </div>
                        </div>
                        
                        <div class="submission-content" style="display: none;">
                            <h3><?php echo esc_html($submission->subject); ?></h3>
                            <p class="submission-user">By: <?php echo esc_html($user_name); ?></p>
                            <div class="submission-message">
                                <?php echo nl2br(esc_html($submission->message)); ?>
                            </div>
                            
                            <?php if (!empty($submission->form_data)): ?>
                                <?php
                                $form_data = json_decode($submission->form_data, true);
                                if ($form_data && is_array($form_data)):
                                ?>
                                    <div class="submission-form-data">
                                        <strong>Form Responses:</strong>
                                        <div class="form-data-content">
                                            <?php foreach ($form_data as $field_name => $field_value): ?>
                                                <p>
                                                    <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $field_name))); ?>:</strong>
                                                    <?php echo esc_html($field_value); ?>
                                                </p>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
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
                        
                        <!-- Quick Reply Box -->
                        <div class="quick-reply-box" style="display: none;">
                            <div class="quick-reply-canned" style="margin-bottom: 10px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Use Canned Response:</label>
                                <select class="quick-reply-canned-selector" style="width: 100%; max-width: 400px;">
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
                            <textarea class="quick-reply-textarea" rows="4" placeholder="Type your reply..."></textarea>
                            <div class="quick-reply-actions">
                                <button type="button" class="button button-primary quick-reply-submit">Send Reply</button>
                                <span class="quick-reply-message"></span>
                            </div>
                        </div>
                        
                        <div class="submission-actions">
                            <button class="button button-small quick-reply-toggle" data-id="<?php echo esc_attr($submission->id); ?>">
                                Quick Reply
                            </button>
                            
                            <button class="button button-primary reply-button" data-id="<?php echo esc_attr($submission->id); ?>">
                                Full Reply
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

