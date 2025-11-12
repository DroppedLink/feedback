<?php
/**
 * Shortcode implementation for User Feedback plugin
 */

if (!defined('WPINC')) {
    die;
}

// Register dynamic form shortcode
add_shortcode('userfeedback', 'userfeedback_shortcode_handler');

/**
 * Handle userfeedback shortcode for dynamic forms
 */
function userfeedback_shortcode_handler($atts) {
    $atts = shortcode_atts(array(
        'form' => '',
        'context_id' => ''
    ), $atts);
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div class="userfeedback-notice">You must be <a href="' . esc_url(wp_login_url(get_permalink())) . '">logged in</a> to submit feedback.</div>';
    }
    
    // Get form by shortcode
    $form_shortcode = sanitize_text_field($atts['form']);
    if (empty($form_shortcode)) {
        return '<div class="userfeedback-notice userfeedback-notice-error">Error: Form shortcode is required. Use: [userfeedback form="your-form"]</div>';
    }
    
    $form = userfeedback_get_form_by_shortcode($form_shortcode);
    
    if (!$form) {
        return '<div class="userfeedback-notice userfeedback-notice-error">Error: Form not found or inactive.</div>';
    }
    
    // Parse field configuration
    $field_config = json_decode($form->field_config, true);
    if (empty($field_config) || !isset($field_config['fields']) || empty($field_config['fields'])) {
        return '<div class="userfeedback-notice userfeedback-notice-warning">This form has no fields configured yet.</div>';
    }
    
    $context_id = sanitize_text_field($atts['context_id']);
    $attachments_enabled = get_option('user_feedback_enable_attachments', '1');
    
    ob_start();
    ?>
    <div class="userfeedback-form-container" 
         data-form-id="<?php echo esc_attr($form->id); ?>" 
         data-context-id="<?php echo esc_attr($context_id); ?>">
        <h3><?php echo esc_html($form->name); ?></h3>
        <?php if (!empty($form->description)): ?>
            <p class="userfeedback-form-description"><?php echo esc_html($form->description); ?></p>
        <?php endif; ?>
        
        <form class="userfeedback-dynamic-form">
            <?php foreach ($field_config['fields'] as $index => $field): ?>
                <?php echo userfeedback_render_form_field($field, $index); ?>
            <?php endforeach; ?>
            
            <div class="userfeedback-form-actions">
                <button type="submit" class="userfeedback-submit-btn">
                    Submit Feedback
                </button>
            </div>
            
            <div class="userfeedback-form-message"></div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render a single form field based on configuration
 */
function userfeedback_render_form_field($field, $index) {
    if (empty($field['type'])) {
        return '';
    }
    
    $field_type = $field['type'];
    $field_name = isset($field['name']) ? sanitize_key($field['name']) : 'field_' . $index;
    $field_label = isset($field['label']) ? esc_html($field['label']) : 'Field ' . ($index + 1);
    $required = isset($field['required']) && $field['required'];
    $required_attr = $required ? 'required' : '';
    $required_mark = $required ? ' *' : '';
    
    ob_start();
    
    echo '<div class="userfeedback-form-field">';
    
    switch ($field_type) {
        case 'select':
            echo '<label for="uff-' . esc_attr($field_name) . '">' . $field_label . $required_mark . '</label>';
            echo '<select id="uff-' . esc_attr($field_name) . '" 
                         name="' . esc_attr($field_name) . '" 
                         class="userfeedback-select" 
                         data-field-name="' . esc_attr($field_name) . '"
                         ' . $required_attr . '>';
            echo '<option value="">-- Select --</option>';
            
            if (!empty($field['options']) && is_array($field['options'])) {
                foreach ($field['options'] as $option) {
                    $option_value = esc_attr($option);
                    echo '<option value="' . $option_value . '">' . esc_html($option) . '</option>';
                }
            }
            
            echo '</select>';
            break;
            
        case 'text':
            echo '<label for="uff-' . esc_attr($field_name) . '">' . $field_label . $required_mark . '</label>';
            echo '<input type="text" 
                         id="uff-' . esc_attr($field_name) . '" 
                         name="' . esc_attr($field_name) . '" 
                         class="userfeedback-input" 
                         data-field-name="' . esc_attr($field_name) . '"
                         ' . $required_attr . '>';
            
            if (!empty($field['placeholder'])) {
                echo '<p class="description">' . esc_html($field['placeholder']) . '</p>';
            }
            break;
            
        case 'textarea':
            $rows = isset($field['rows']) ? intval($field['rows']) : 6;
            echo '<label for="uff-' . esc_attr($field_name) . '">' . $field_label . $required_mark . '</label>';
            echo '<textarea id="uff-' . esc_attr($field_name) . '" 
                            name="' . esc_attr($field_name) . '" 
                            class="userfeedback-textarea" 
                            data-field-name="' . esc_attr($field_name) . '"
                            rows="' . esc_attr($rows) . '" 
                            ' . $required_attr . '></textarea>';
            
            if (!empty($field['placeholder'])) {
                echo '<p class="description">' . esc_html($field['placeholder']) . '</p>';
            }
            break;
            
        case 'file':
            $attachments_enabled = get_option('user_feedback_enable_attachments', '1');
            if ($attachments_enabled === '1') {
                echo '<label for="uff-' . esc_attr($field_name) . '">' . $field_label . $required_mark . '</label>';
                echo '<input type="file" 
                             id="uff-' . esc_attr($field_name) . '" 
                             name="' . esc_attr($field_name) . '" 
                             class="userfeedback-file-input" 
                             data-field-name="' . esc_attr($field_name) . '"
                             accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                             ' . $required_attr . '>';
                echo '<p class="description">Attach a file (max ' . esc_html(get_option('user_feedback_max_file_size', 5)) . ' MB) or paste from clipboard (Ctrl+V/Cmd+V)</p>';
                echo '<div class="userfeedback-file-preview" style="display:none;">';
                echo '    <img src="" alt="Preview" class="userfeedback-preview-image">';
                echo '    <button type="button" class="userfeedback-remove-file">Remove</button>';
                echo '</div>';
            }
            break;
    }
    
    echo '</div>';
    
    return ob_get_clean();
}

// Keep legacy shortcode for backward compatibility
add_shortcode('user_feedback', 'user_feedback_legacy_shortcode_handler');

/**
 * Legacy shortcode handler (for backward compatibility)
 */
function user_feedback_legacy_shortcode_handler($atts) {
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
 * Register changelog shortcode
 */
add_shortcode('feedback_changelog', 'user_feedback_changelog_shortcode');

function user_feedback_changelog_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 10,
        'context_id' => '',
        'form' => '',
        'category' => ''
    ), $atts);
    
    $args = array(
        'type' => 'bug',
        'status' => 'resolved',
        'limit' => intval($atts['limit']),
        'orderby' => 'resolved_at',
        'order' => 'DESC'
    );
    
    // Filter by form if provided
    if (!empty($atts['form'])) {
        $form = userfeedback_get_form_by_shortcode(sanitize_text_field($atts['form']));
        if ($form) {
            $args['form_id'] = $form->id;
        }
    }
    
    // Filter by category if provided
    if (!empty($atts['category'])) {
        $category = userfeedback_get_category_by_slug(sanitize_text_field($atts['category']));
        if ($category) {
            $args['category_id'] = $category->id;
        }
    }
    
    // Legacy context_id filter
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
