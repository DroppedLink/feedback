<?php
/**
 * Shortcode implementation for User Feedback plugin
 */

if (!defined('WPINC')) {
    die;
}

// Register shortcode
add_shortcode('user_feedback', 'user_feedback_shortcode_handler');

/**
 * Handle user_feedback shortcode
 */
function user_feedback_shortcode_handler($atts) {
    $atts = shortcode_atts(array(
        'type' => 'comment',
        'context_id' => ''
    ), $atts);
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div class="user-feedback-notice">You must be <a href="' . esc_url(wp_login_url(get_permalink())) . '">logged in</a> to submit feedback.</div>';
    }
    
    // Validate type
    $type = in_array($atts['type'], array('comment', 'bug')) ? $atts['type'] : 'comment';
    $context_id = sanitize_text_field($atts['context_id']);
    
    // Check if type is enabled
    $type_enabled = get_option('user_feedback_enable_' . $type . 's', '1');
    if ($type_enabled !== '1') {
        return '<div class="user-feedback-notice">This feedback type is currently disabled.</div>';
    }
    
    // Get labels
    $comment_label = get_option('user_feedback_comment_label', 'Comment/Question');
    $bug_label = get_option('user_feedback_bug_label', 'Bug Report');
    $submit_button_text = get_option('user_feedback_submit_button_text', 'Submit');
    
    // Check if attachments are enabled
    $attachments_enabled = get_option('user_feedback_enable_attachments', '1');
    
    $type_label = ($type === 'bug') ? $bug_label : $comment_label;
    
    ob_start();
    ?>
    <div class="user-feedback-form-container" data-type="<?php echo esc_attr($type); ?>" data-context-id="<?php echo esc_attr($context_id); ?>">
        <h3><?php echo esc_html($type_label); ?></h3>
        
        <form class="user-feedback-form">
            <div class="user-feedback-field">
                <label for="uf-subject">Subject *</label>
                <input type="text" 
                       id="uf-subject" 
                       name="subject" 
                       class="user-feedback-input" 
                       required>
            </div>
            
            <div class="user-feedback-field">
                <label for="uf-message">Message *</label>
                <textarea id="uf-message" 
                          name="message" 
                          class="user-feedback-textarea" 
                          rows="6" 
                          required></textarea>
            </div>
            
            <?php if ($attachments_enabled === '1'): ?>
            <div class="user-feedback-field">
                <label for="uf-screenshot">Screenshot (optional)</label>
                <input type="file" 
                       id="uf-screenshot" 
                       name="screenshot" 
                       class="user-feedback-file-input" 
                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                <p class="description">Attach a screenshot (max <?php echo esc_html(get_option('user_feedback_max_file_size', 5)); ?> MB) or paste from clipboard (Ctrl+V/Cmd+V)</p>
                <div class="user-feedback-file-preview" style="display:none;">
                    <img src="" alt="Preview" class="user-feedback-preview-image">
                    <button type="button" class="user-feedback-remove-file">Remove</button>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="user-feedback-actions">
                <button type="submit" class="user-feedback-submit">
                    <?php echo esc_html($submit_button_text); ?>
                </button>
            </div>
            
            <div class="user-feedback-message"></div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Register changelog shortcode (for future enhancement)
 */
add_shortcode('feedback_changelog', 'user_feedback_changelog_shortcode');

function user_feedback_changelog_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 10,
        'context_id' => ''
    ), $atts);
    
    $args = array(
        'type' => 'bug',
        'status' => 'resolved',
        'limit' => intval($atts['limit']),
        'orderby' => 'resolved_at',
        'order' => 'DESC'
    );
    
    if (!empty($atts['context_id'])) {
        $args['context_id'] = sanitize_text_field($atts['context_id']);
    }
    
    $submissions = user_feedback_get_submissions($args);
    
    if (empty($submissions)) {
        return '<div class="user-feedback-changelog">No resolved bugs to display.</div>';
    }
    
    ob_start();
    ?>
    <div class="user-feedback-changelog">
        <h3>Recent Bug Fixes</h3>
        <div class="changelog-list">
            <?php foreach ($submissions as $submission): ?>
                <div class="changelog-item">
                    <div class="changelog-date">
                        <?php echo esc_html(date('M d, Y', strtotime($submission->resolved_at))); ?>
                    </div>
                    <div class="changelog-title">
                        <strong><?php echo esc_html($submission->subject); ?></strong>
                    </div>
                    <?php if (!empty($submission->resolution_notes)): ?>
                        <div class="changelog-notes">
                            <?php echo esc_html($submission->resolution_notes); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

