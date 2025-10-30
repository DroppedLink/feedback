<?php
/**
 * Quick Feedback Collector for User Feedback plugin
 * Adds a button to the WordPress admin bar for quick feedback submission
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Add Quick Feedback button to admin bar
 */
add_action('admin_bar_menu', 'user_feedback_add_admin_bar_button', 999);
function user_feedback_add_admin_bar_button($wp_admin_bar) {
    // Check if quick collector is enabled
    if (!user_feedback_is_quick_collector_enabled()) {
        return;
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return;
    }
    
    // Get button label from settings
    $button_label = get_option('user_feedback_quick_collector_label', 'Quick Feedback');
    
    // Add the admin bar item
    $args = array(
        'id'    => 'user-feedback-quick',
        'title' => '<span class="ab-icon dashicons dashicons-feedback"></span><span class="ab-label">' . esc_html($button_label) . '</span>',
        'href'  => '#',
        'meta'  => array(
            'class' => 'user-feedback-quick-button',
            'onclick' => 'return false;'
        )
    );
    $wp_admin_bar->add_node($args);
}

/**
 * Output quick feedback modal HTML
 */
add_action('wp_footer', 'user_feedback_quick_collector_modal');
add_action('admin_footer', 'user_feedback_quick_collector_modal');
function user_feedback_quick_collector_modal() {
    // Check if quick collector is enabled
    if (!user_feedback_is_quick_collector_enabled()) {
        return;
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return;
    }
    
    $show_technical_details = get_option('user_feedback_quick_collector_show_details', '1');
    $capture_console_errors = get_option('user_feedback_quick_collector_capture_errors', '1');
    $attachments_enabled = get_option('user_feedback_enable_attachments', '1');
    $button_label = get_option('user_feedback_quick_collector_label', 'Quick Feedback');
    
    ?>
    <div id="user-feedback-quick-modal" class="user-feedback-modal user-feedback-quick-modal" style="display:none;">
        <div class="modal-content user-feedback-quick-content">
            <span class="modal-close">&times;</span>
            <h2><?php echo esc_html($button_label); ?></h2>
            
            <form id="user-feedback-quick-form">
                <div class="user-feedback-field">
                    <label for="ufq-type">Type *</label>
                    <select id="ufq-type" name="type" class="user-feedback-input" required>
                        <option value="comment">Comment/Question</option>
                        <option value="bug">Bug Report</option>
                    </select>
                </div>
                
                <div class="user-feedback-field">
                    <label for="ufq-subject">Subject *</label>
                    <input type="text" 
                           id="ufq-subject" 
                           name="subject" 
                           class="user-feedback-input" 
                           placeholder="Brief description" 
                           required>
                </div>
                
                <div class="user-feedback-field">
                    <label for="ufq-message">Message *</label>
                    <textarea id="ufq-message" 
                              name="message" 
                              class="user-feedback-textarea" 
                              rows="5" 
                              placeholder="Describe the issue or feedback in detail..." 
                              required></textarea>
                </div>
                
                <?php if ($attachments_enabled === '1'): ?>
                <div class="user-feedback-field">
                    <label for="ufq-screenshot">Screenshot (optional)</label>
                    <input type="file" 
                           id="ufq-screenshot" 
                           name="screenshot" 
                           class="user-feedback-file-input" 
                           accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                    <p class="description">Attach a screenshot (max <?php echo esc_html(user_feedback_get_max_file_size_mb()); ?> MB) or paste from clipboard</p>
                    <div class="user-feedback-file-preview-quick" style="display:none;">
                        <img src="" alt="Preview" class="user-feedback-preview-image">
                        <button type="button" class="user-feedback-remove-file-quick">Remove</button>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($show_technical_details === '1'): ?>
                <div class="user-feedback-technical-details">
                    <button type="button" class="technical-details-toggle">
                        <span class="dashicons dashicons-arrow-right"></span>
                        Technical Details (Auto-collected)
                    </button>
                    <div class="technical-details-content" style="display:none;">
                        <div id="ufq-technical-preview">
                            <p><em>Technical information will be collected when you submit...</em></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="user-feedback-actions">
                    <button type="submit" class="button button-primary user-feedback-submit">
                        Submit Feedback
                    </button>
                    <button type="button" class="button user-feedback-cancel">
                        Cancel
                    </button>
                </div>
                
                <div class="user-feedback-message"></div>
            </form>
        </div>
    </div>
    
    <script type="text/javascript">
        // Pass settings to JavaScript
        if (typeof userFeedback !== 'undefined') {
            userFeedback.quickCollector = userFeedback.quickCollector || {};
            userFeedback.quickCollector.captureErrors = <?php echo $capture_console_errors === '1' ? 'true' : 'false'; ?>;
            userFeedback.quickCollector.showDetails = <?php echo $show_technical_details === '1' ? 'true' : 'false'; ?>;
        }
    </script>
    <?php
}

/**
 * Render floating feedback trigger for users without admin bar access.
 */
add_action('wp_footer', 'user_feedback_quick_collector_floating_button', 25);
add_action('admin_footer', 'user_feedback_quick_collector_floating_button', 25);
function user_feedback_quick_collector_floating_button() {
    if (!user_feedback_is_quick_collector_enabled() || !is_user_logged_in()) {
        return;
    }

    $button_label = get_option('user_feedback_quick_collector_label', 'Quick Feedback');
    ?>
    <button type="button"
            class="user-feedback-floating-button"
            aria-haspopup="dialog"
            aria-controls="user-feedback-quick-modal">
        <span class="user-feedback-floating-icon dashicons dashicons-feedback" aria-hidden="true"></span>
        <span class="user-feedback-floating-label"><?php echo esc_html($button_label); ?></span>
        <span class="screen-reader-text">Open feedback form</span>
    </button>
    <?php
}

/**
 * Enqueue admin bar styles
 */
add_action('wp_enqueue_scripts', 'user_feedback_quick_collector_styles', 999);
add_action('admin_enqueue_scripts', 'user_feedback_quick_collector_styles', 999);
function user_feedback_quick_collector_styles() {
    if (!user_feedback_is_quick_collector_enabled() || !is_user_logged_in()) {
        return;
    }
    
    // Ensure dashicons icon font is available on the front-end
    wp_enqueue_style('dashicons');

    // Add custom CSS for admin bar button and floating indicator
    wp_add_inline_style('user-feedback-style', '
        #wpadminbar .user-feedback-quick-button .ab-icon:before {
            content: "\f175";
            top: 2px;
        }
        #wpadminbar .user-feedback-quick-button:hover .ab-icon:before {
            color: #00b9eb;
        }
        .user-feedback-floating-button {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 9999;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 999px;
            border: none;
            background: #0073aa;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }
        .user-feedback-floating-button:hover,
        .user-feedback-floating-button:focus {
            background: #008ec2;
            transform: translateY(-2px);
            box-shadow: 0 12px 34px rgba(0, 0, 0, 0.25);
            outline: none;
        }
        .user-feedback-floating-button .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }
        .user-feedback-floating-button .user-feedback-floating-label {
            font-weight: 600;
        }
        .user-feedback-floating-button .screen-reader-text {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    ');
}

