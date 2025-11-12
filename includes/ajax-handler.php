<?php
/**
 * AJAX handlers for User Feedback plugin
 */

if (!defined('WPINC')) {
    die;
}

// Register AJAX actions for submission
add_action('wp_ajax_user_feedback_submit', 'user_feedback_submit_handler');

/**
 * Handle feedback submission
 */
function user_feedback_submit_handler() {
    // Verify nonce
    check_ajax_referer('user_feedback_nonce', 'nonce');
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in to submit feedback.'));
        return;
    }
    
    // Get form_id or legacy type
    $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
    $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
    
    // Sanitize common fields
    $subject = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
    $context_id = isset($_POST['context_id']) ? sanitize_text_field(wp_unslash($_POST['context_id'])) : '';
    $metadata = isset($_POST['metadata']) ? user_feedback_normalize_metadata($_POST['metadata']) : '';
    $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
    $form_data = isset($_POST['form_data']) ? wp_unslash($_POST['form_data']) : '';
    
    // Validate required fields
    if (empty($subject)) {
        wp_send_json_error(array('message' => 'Subject is required.'));
        return;
    }
    
    // For dynamic forms, validate against form configuration
    if ($form_id) {
        $form = userfeedback_get_form($form_id);
        if (!$form) {
            wp_send_json_error(array('message' => 'Invalid form.'));
            return;
        }
        
        if (!$form->is_active) {
            wp_send_json_error(array('message' => 'This form is currently inactive.'));
            return;
        }
    } elseif ($type) {
        // Legacy type-based submission
        if (empty($message)) {
            wp_send_json_error(array('message' => 'Message is required.'));
            return;
        }
        
        if (!in_array($type, array('comment', 'bug'))) {
            wp_send_json_error(array('message' => 'Invalid submission type.'));
            return;
        }
        
        // Check if type is enabled (legacy)
        $type_enabled = get_option('user_feedback_enable_' . $type . 's', '1');
        if ($type_enabled !== '1') {
            wp_send_json_error(array('message' => 'This feedback type is currently disabled.'));
            return;
        }
    } else {
        wp_send_json_error(array('message' => 'Form or type is required.'));
        return;
    }
    
    // Get default status
    $default_status = get_option('userfeedback_default_status', get_option('user_feedback_default_status', 'new'));
    
    // Prepare submission data
    $submission_data = array(
        'user_id' => get_current_user_id(),
        'subject' => $subject,
        'message' => $message,
        'status' => $default_status
    );
    
    // Add form_id if provided
    if ($form_id) {
        $submission_data['form_id'] = $form_id;
    }
    
    // Add type if provided (legacy)
    if ($type) {
        $submission_data['type'] = $type;
    }
    
    // Add context_id if provided
    if ($context_id) {
        $submission_data['context_id'] = $context_id;
    }
    
    // Add metadata if provided
    if (!empty($metadata)) {
        $submission_data['metadata'] = $metadata;
    }
    
    // Add form_data if provided (dynamic field responses)
    if (!empty($form_data)) {
        $submission_data['form_data'] = $form_data;
    }
    
    // Add attachment_id if provided
    if (!empty($attachment_id)) {
        $submission_data['attachment_id'] = $attachment_id;
    }
    
    // Insert submission
    $submission_id = user_feedback_insert_submission($submission_data);
    
    if (!$submission_id) {
        wp_send_json_error(array('message' => 'Failed to save submission. Please try again.'));
        return;
    }
    
    // Send notification to admin
    $submission = user_feedback_get_submission($submission_id);
    user_feedback_send_new_submission_notification($submission);
    
    wp_send_json_success(array(
        'message' => 'Thank you! Your submission has been received.',
        'submission_id' => $submission_id
    ));
}

// Register AJAX actions for admin
add_action('wp_ajax_user_feedback_send_reply', 'user_feedback_send_reply_handler');
add_action('wp_ajax_user_feedback_update_status', 'user_feedback_update_status_handler');
add_action('wp_ajax_user_feedback_delete_submission', 'user_feedback_delete_submission_handler');
add_action('wp_ajax_user_feedback_get_canned_response', 'user_feedback_get_canned_response_handler');

// Register AJAX actions for Form Builder
add_action('wp_ajax_userfeedback_save_category', 'userfeedback_save_category_handler');
add_action('wp_ajax_userfeedback_delete_category', 'userfeedback_delete_category_handler');
add_action('wp_ajax_userfeedback_save_form', 'userfeedback_save_form_handler');
add_action('wp_ajax_userfeedback_delete_form', 'userfeedback_delete_form_handler');
add_action('wp_ajax_userfeedback_get_forms_by_category', 'userfeedback_get_forms_by_category_handler');

/**
 * Handle admin reply
 */
function user_feedback_send_reply_handler() {
    // Verify nonce
    check_ajax_referer('user_feedback_nonce', 'nonce');
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
        return;
    }
    
    // Sanitize input
    $submission_id = intval($_POST['submission_id']);
    $reply = sanitize_textarea_field($_POST['reply']);
    
    if (empty($reply)) {
        wp_send_json_error(array('message' => 'Reply cannot be empty.'));
        return;
    }
    
    // Get submission
    $submission = user_feedback_get_submission($submission_id);
    if (!$submission) {
        wp_send_json_error(array('message' => 'Submission not found.'));
        return;
    }
    
    // Update submission with reply
    $result = user_feedback_update_submission($submission_id, array(
        'admin_reply' => $reply
    ));
    
    if ($result === false) {
        wp_send_json_error(array('message' => 'Failed to save reply.'));
        return;
    }
    
    // Send reply email to user
    user_feedback_send_reply_notification($submission, $reply);
    
    wp_send_json_success(array(
        'message' => 'Reply sent successfully!'
    ));
}

/**
 * Handle status update
 */
function user_feedback_update_status_handler() {
    // Verify nonce
    check_ajax_referer('user_feedback_nonce', 'nonce');
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
        return;
    }
    
    // Sanitize input
    $submission_id = intval($_POST['submission_id']);
    $status = sanitize_text_field($_POST['status']);
    $resolution_notes = isset($_POST['resolution_notes']) ? sanitize_textarea_field($_POST['resolution_notes']) : '';
    
    // Validate status
    $valid_statuses = array('new', 'in_progress', 'testing', 'resolved', 'wont_fix');
    if (!in_array($status, $valid_statuses)) {
        wp_send_json_error(array('message' => 'Invalid status.'));
        return;
    }
    
    // Get submission
    $submission = user_feedback_get_submission($submission_id);
    if (!$submission) {
        wp_send_json_error(array('message' => 'Submission not found.'));
        return;
    }
    
    // Update submission
    $update_data = array('status' => $status);
    if (!empty($resolution_notes)) {
        $update_data['resolution_notes'] = $resolution_notes;
    }
    
    $result = user_feedback_update_submission($submission_id, $update_data);
    
    if ($result === false) {
        wp_send_json_error(array('message' => 'Failed to update status.'));
        return;
    }
    
    // Send notification if resolved
    if ($status === 'resolved') {
        user_feedback_send_resolved_notification($submission, $resolution_notes);
    }
    
    wp_send_json_success(array(
        'message' => 'Status updated successfully!'
    ));
}

/**
 * Handle submission deletion
 */
function user_feedback_delete_submission_handler() {
    // Verify nonce
    check_ajax_referer('user_feedback_nonce', 'nonce');
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
        return;
    }
    
    // Sanitize input
    $submission_id = intval($_POST['submission_id']);
    
    // Delete attachment if exists
    user_feedback_delete_attachment($submission_id);
    
    // Delete submission
    $result = user_feedback_delete_submission($submission_id);
    
    if (!$result) {
        wp_send_json_error(array('message' => 'Failed to delete submission.'));
        return;
    }
    
    wp_send_json_success(array(
        'message' => 'Submission deleted successfully!'
    ));
}

/**
 * Handle getting canned response content
 */
function user_feedback_get_canned_response_handler() {
    // Verify nonce
    check_ajax_referer('user_feedback_nonce', 'nonce');
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
        return;
    }
    
    // Sanitize input
    $response_id = intval($_POST['response_id']);
    
    // Get canned response
    $response = user_feedback_get_canned_response($response_id);
    
    if (!$response) {
        wp_send_json_error(array('message' => 'Canned response not found.'));
        return;
    }
    
    wp_send_json_success(array(
        'content' => $response->content
    ));
}

/**
 * Export submissions as CSV
 */
add_action('admin_init', 'user_feedback_handle_export');
function user_feedback_handle_export() {
    if (!isset($_GET['action']) || $_GET['action'] !== 'user_feedback_export') {
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    // Verify nonce
    check_admin_referer('user_feedback_export');
    
    // Get submissions
    $args = array(
        'limit' => 10000,
        'offset' => 0
    );
    
    // Apply filters if set
    if (!empty($_GET['filter_type'])) {
        $args['type'] = sanitize_text_field($_GET['filter_type']);
    }
    if (!empty($_GET['filter_status'])) {
        $args['status'] = sanitize_text_field($_GET['filter_status']);
    }
    
    $submissions = user_feedback_get_submissions($args);
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=user-feedback-export-' . date('Y-m-d') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, array('ID', 'User', 'Type', 'Context ID', 'Subject', 'Message', 'Status', 'Admin Reply', 'Resolution Notes', 'Created At', 'Resolved At'));
    
    // Add data rows
    foreach ($submissions as $submission) {
        $user = get_userdata($submission->user_id);
        $username = $user ? $user->user_login : 'Unknown';
        
        fputcsv($output, array(
            $submission->id,
            $username,
            $submission->type,
            $submission->context_id,
            $submission->subject,
            $submission->message,
            $submission->status,
            $submission->admin_reply,
            $submission->resolution_notes,
            $submission->created_at,
            $submission->resolved_at
        ));
    }
    
    fclose($output);
    exit;
}

// ============================================================================
// FORM BUILDER AJAX HANDLERS
// ============================================================================

/**
 * Save category (create or update)
 */
function userfeedback_save_category_handler() {
    // Verify nonce
    check_ajax_referer('user_feedback_nonce', 'nonce');
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
        return;
    }
    
    // Get data
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $slug = isset($_POST['slug']) ? sanitize_title($_POST['slug']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    
    // Validate
    if (empty($name)) {
        wp_send_json_error(array('message' => 'Category name is required.'));
        return;
    }
    
    if (empty($slug)) {
        $slug = sanitize_title($name);
    }
    
    // Check for duplicate slug
    $existing = userfeedback_get_category_by_slug($slug);
    if ($existing && (!$category_id || $existing->id != $category_id)) {
        wp_send_json_error(array('message' => 'A category with this slug already exists.'));
        return;
    }
    
    $data = array(
        'name' => $name,
        'slug' => $slug,
        'description' => $description
    );
    
    if ($category_id) {
        // Update existing category
        $result = userfeedback_update_category($category_id, $data);
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Category updated successfully!',
                'category_id' => $category_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to update category.'));
        }
    } else {
        // Create new category
        $new_id = userfeedback_insert_category($data);
        if ($new_id) {
            wp_send_json_success(array(
                'message' => 'Category created successfully!',
                'category_id' => $new_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to create category.'));
        }
    }
}

/**
 * Delete category
 */
function userfeedback_delete_category_handler() {
    // Verify nonce
    check_ajax_referer('user_feedback_nonce', 'nonce');
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
        return;
    }
    
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    
    if (!$category_id) {
        wp_send_json_error(array('message' => 'Invalid category ID.'));
        return;
    }
    
    // Check if category has forms
    $forms = userfeedback_get_forms(array('category_id' => $category_id));
    if (!empty($forms)) {
        wp_send_json_error(array('message' => 'Cannot delete category with forms. Delete the forms first.'));
        return;
    }
    
    $result = userfeedback_delete_category($category_id);
    
    if ($result) {
        wp_send_json_success(array('message' => 'Category deleted successfully!'));
    } else {
        wp_send_json_error(array('message' => 'Failed to delete category.'));
    }
}

/**
 * Save form (create or update)
 */
function userfeedback_save_form_handler() {
    // Verify nonce
    check_ajax_referer('user_feedback_nonce', 'nonce');
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
        return;
    }
    
    // Get data
    $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $slug = isset($_POST['slug']) ? sanitize_title($_POST['slug']) : '';
    $shortcode = isset($_POST['shortcode']) ? sanitize_title($_POST['shortcode']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    $is_active = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true;
    $field_config = isset($_POST['field_config']) ? wp_unslash($_POST['field_config']) : '';
    
    // Validate
    if (empty($name)) {
        wp_send_json_error(array('message' => 'Form name is required.'));
        return;
    }
    
    if (empty($category_id)) {
        wp_send_json_error(array('message' => 'Category is required.'));
        return;
    }
    
    if (empty($shortcode)) {
        $shortcode = sanitize_title($name);
    }
    
    if (empty($slug)) {
        $slug = sanitize_title($name);
    }
    
    // Check for duplicate shortcode
    $existing = userfeedback_get_form_by_shortcode($shortcode);
    if ($existing && (!$form_id || $existing->id != $form_id)) {
        wp_send_json_error(array('message' => 'A form with this shortcode already exists.'));
        return;
    }
    
    // Validate field config JSON
    if (!empty($field_config)) {
        $decoded = json_decode($field_config, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array('message' => 'Invalid field configuration JSON.'));
            return;
        }
    }
    
    $data = array(
        'category_id' => $category_id,
        'name' => $name,
        'slug' => $slug,
        'shortcode' => $shortcode,
        'description' => $description,
        'is_active' => $is_active,
        'field_config' => $field_config
    );
    
    if ($form_id) {
        // Update existing form
        $result = userfeedback_update_form($form_id, $data);
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Form updated successfully!',
                'form_id' => $form_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to update form.'));
        }
    } else {
        // Create new form
        $new_id = userfeedback_insert_form($data);
        if ($new_id) {
            wp_send_json_success(array(
                'message' => 'Form created successfully!',
                'form_id' => $new_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to create form.'));
        }
    }
}

/**
 * Delete form
 */
function userfeedback_delete_form_handler() {
    // Verify nonce
    check_ajax_referer('user_feedback_nonce', 'nonce');
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
        return;
    }
    
    $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
    
    if (!$form_id) {
        wp_send_json_error(array('message' => 'Invalid form ID.'));
        return;
    }
    
    // Check if form has submissions
    $submissions = user_feedback_get_submissions(array('form_id' => $form_id, 'limit' => 1));
    if (!empty($submissions)) {
        wp_send_json_error(array('message' => 'Cannot delete form with submissions. Archive it instead by setting it to inactive.'));
        return;
    }
    
    $result = userfeedback_delete_form($form_id);
    
    if ($result) {
        wp_send_json_success(array('message' => 'Form deleted successfully!'));
    } else {
        wp_send_json_error(array('message' => 'Failed to delete form.'));
    }
}

/**
 * Get forms by category (for filtering)
 */
function userfeedback_get_forms_by_category_handler() {
    // Verify nonce
    check_ajax_referer('user_feedback_nonce', 'nonce');
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
        return;
    }
    
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    
    if (!$category_id) {
        wp_send_json_success(array('forms' => array()));
        return;
    }
    
    $forms = userfeedback_get_forms(array('category_id' => $category_id));
    
    wp_send_json_success(array('forms' => $forms));
}

