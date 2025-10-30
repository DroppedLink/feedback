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
    
    // Sanitize input
    $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
    $subject = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
    $context_id = isset($_POST['context_id']) ? sanitize_text_field(wp_unslash($_POST['context_id'])) : '';
    $metadata = isset($_POST['metadata']) ? user_feedback_normalize_metadata($_POST['metadata']) : '';
    $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
    
    // Validate required fields
    if (empty($subject) || empty($message)) {
        wp_send_json_error(array('message' => 'Subject and message are required.'));
        return;
    }
    
    // Validate type
    if (!in_array($type, array('comment', 'bug'))) {
        wp_send_json_error(array('message' => 'Invalid submission type.'));
        return;
    }
    
    // Check if type is enabled
    $type_enabled = get_option('user_feedback_enable_' . $type . 's', '1');
    if ($type_enabled !== '1') {
        wp_send_json_error(array('message' => 'This feedback type is currently disabled.'));
        return;
    }
    
    // Get default status
    $default_status = get_option('user_feedback_default_status', 'new');
    
    // Prepare submission data
    $submission_data = array(
        'user_id' => get_current_user_id(),
        'type' => $type,
        'context_id' => $context_id,
        'subject' => $subject,
        'message' => $message,
        'status' => $default_status
    );
    
    // Add metadata if provided
    if (!empty($metadata)) {
        $submission_data['metadata'] = $metadata;
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

