<?php
/**
 * File upload handler for User Feedback plugin
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Handle screenshot upload via AJAX
 */
add_action('wp_ajax_user_feedback_upload_screenshot', 'user_feedback_upload_screenshot_handler');
function user_feedback_upload_screenshot_handler() {
    // Verify nonce
    check_ajax_referer('user_feedback_nonce', 'nonce');
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in to upload files.'));
        return;
    }
    
    // Check if attachments are enabled
    $attachments_enabled = get_option('user_feedback_enable_attachments', '1');
    if ($attachments_enabled !== '1') {
        wp_send_json_error(array('message' => 'File uploads are currently disabled.'));
        return;
    }
    
    // Check if file was uploaded
    if (empty($_FILES['screenshot'])) {
        wp_send_json_error(array('message' => 'No file uploaded.'));
        return;
    }
    
    $file = $_FILES['screenshot'];
    
    // Validate file
    $validation = user_feedback_validate_upload($file);
    if (is_wp_error($validation)) {
        wp_send_json_error(array('message' => $validation->get_error_message()));
        return;
    }
    
    // Upload file using WordPress media library
    $attachment_id = user_feedback_handle_upload($file);
    
    if (is_wp_error($attachment_id)) {
        wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        return;
    }
    
    // Get attachment URL
    $attachment_url = wp_get_attachment_url($attachment_id);
    $attachment_thumb = wp_get_attachment_image_url($attachment_id, 'thumbnail');
    
    wp_send_json_success(array(
        'attachment_id' => $attachment_id,
        'attachment_url' => $attachment_url,
        'thumbnail_url' => $attachment_thumb ? $attachment_thumb : $attachment_url,
        'message' => 'Screenshot uploaded successfully!'
    ));
}

/**
 * Validate uploaded file
 */
function user_feedback_validate_upload($file) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return new WP_Error('upload_error', 'File upload error: ' . $file['error']);
    }
    
    // Get settings
    $max_bytes = user_feedback_get_max_file_size_bytes();
    $allowed_types = user_feedback_get_allowed_file_types();
    if ($file['size'] > $max_bytes) {
        return new WP_Error('file_too_large', sprintf('File size exceeds maximum allowed size of %d MB.', user_feedback_get_max_file_size_mb()));
    }
    
    // Check file type
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types, true)) {
        return new WP_Error('invalid_file_type', sprintf('Invalid file type. Allowed types: %s', implode(',', $allowed_types)));
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed_mimes = user_feedback_get_allowed_mime_types();

    if (!in_array($mime_type, $allowed_mimes, true)) {
        return new WP_Error('invalid_mime_type', 'Invalid file MIME type. Only images are allowed.');
    }
    
    return true;
}

/**
 * Handle file upload using WordPress media library
 */
function user_feedback_handle_upload($file) {
    // WordPress upload handling
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    // Override default upload handler
    $upload_overrides = array(
        'test_form' => false,
        'mimes' => user_feedback_get_upload_mime_overrides(),
    );
    
    // Handle the upload
    $uploaded = wp_handle_upload($file, $upload_overrides);
    
    if (isset($uploaded['error'])) {
        return new WP_Error('upload_failed', $uploaded['error']);
    }
    
    // Prepare attachment data
    $attachment_data = array(
        'post_mime_type' => $uploaded['type'],
        'post_title' => sanitize_file_name(pathinfo($uploaded['file'], PATHINFO_FILENAME)),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    
    // Insert attachment
    $attachment_id = wp_insert_attachment($attachment_data, $uploaded['file']);
    
    if (is_wp_error($attachment_id)) {
        // Clean up uploaded file if attachment creation fails
        @unlink($uploaded['file']);
        return $attachment_id;
    }
    
    // Generate metadata and thumbnails
    $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $uploaded['file']);
    wp_update_attachment_metadata($attachment_id, $attachment_metadata);
    
    // Set attachment author
    wp_update_post(array(
        'ID' => $attachment_id,
        'post_author' => get_current_user_id()
    ));
    
    return $attachment_id;
}

/**
 * Delete attachment when submission is deleted
 */
function user_feedback_delete_attachment($submission_id) {
    $submission = user_feedback_get_submission($submission_id);
    
    if ($submission && !empty($submission->attachment_id)) {
        // Delete the attachment and its file
        wp_delete_attachment($submission->attachment_id, true);
    }
}

/**
 * Get attachment HTML for display
 */
function user_feedback_get_attachment_html($attachment_id, $size = 'medium') {
    if (empty($attachment_id)) {
        return '';
    }
    
    $attachment_url = wp_get_attachment_url($attachment_id);
    $attachment_image = wp_get_attachment_image($attachment_id, $size);
    
    if (!$attachment_url) {
        return '';
    }
    
    $html = '<div class="user-feedback-attachment">';
    if ($attachment_image) {
        $html .= '<a href="' . esc_url($attachment_url) . '" target="_blank" class="user-feedback-attachment-link">';
        $html .= $attachment_image;
        $html .= '</a>';
    } else {
        $html .= '<a href="' . esc_url($attachment_url) . '" target="_blank" class="user-feedback-attachment-link">';
        $html .= '<img src="' . esc_url($attachment_url) . '" alt="Attachment">';
        $html .= '</a>';
    }
    $html .= '<p class="user-feedback-attachment-info">';
    $html .= '<a href="' . esc_url($attachment_url) . '" target="_blank" class="button button-small">View Full Size</a>';
    $html .= '</p>';
    $html .= '</div>';
    
    return $html;
}

