<?php
/**
 * Email notification handler for User Feedback plugin
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Send notification to admin about new submission
 */
function user_feedback_send_new_submission_notification($submission) {
    // Check if new submission emails are enabled
    if (get_option('userfeedback_enable_new_submission_email', '1') !== '1') {
        return;
    }
    
    $admin_email = get_option('userfeedback_admin_email', get_option('user_feedback_admin_email', get_option('admin_email')));
    
    $user = get_userdata($submission->user_id);
    $user_name = $user ? $user->display_name : 'Unknown User';
    $user_email = $user ? $user->user_email : '';
    
    $type_label = ($submission->type === 'bug') ? 'Bug Report' : 'Comment/Question';
    
    // Get customizable subject prefix
    $subject_prefix = get_option('userfeedback_email_subject_prefix', '[' . get_bloginfo('name') . ']');
    $subject = sprintf('%s New %s: %s', $subject_prefix, $type_label, $submission->subject);
    
    $message = "A new {$type_label} has been submitted.\n\n";
    $message .= "Submitted by: {$user_name} ({$user_email})\n";
    $message .= "Subject: {$submission->subject}\n\n";
    $message .= "Message:\n{$submission->message}\n\n";
    
    if (!empty($submission->context_id)) {
        $message .= "Context ID: {$submission->context_id}\n\n";
    }
    
    if (!empty($submission->attachment_id)) {
        $attachment_url = wp_get_attachment_url($submission->attachment_id);
        if ($attachment_url) {
            $message .= "Attached Screenshot: {$attachment_url}\n\n";
        }
    }
    
    $message .= "View and respond: " . admin_url('admin.php?page=user-feedback') . "\n";
    
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    // Set From name if configured
    $from_name = get_option('userfeedback_email_from_name', get_bloginfo('name'));
    if (!empty($from_name)) {
        $headers[] = 'From: ' . $from_name . ' <' . $admin_email . '>';
    }
    
    if (!empty($user_email)) {
        $headers[] = 'Reply-To: ' . $user_email;
    }
    
    wp_mail($admin_email, $subject, $message, $headers);
}

/**
 * Send reply notification to user
 */
function user_feedback_send_reply_notification($submission, $reply) {
    // Check if reply emails are enabled
    if (get_option('userfeedback_enable_reply_email', '1') !== '1') {
        return;
    }
    
    $user = get_userdata($submission->user_id);
    if (!$user) {
        return;
    }
    
    $user_email = $user->user_email;
    $user_name = $user->display_name;
    
    $type_label = ($submission->type === 'bug') ? 'Bug Report' : 'Comment/Question';
    
    // Get customizable subject prefix
    $subject_prefix = get_option('userfeedback_email_subject_prefix', '[' . get_bloginfo('name') . ']');
    $subject = sprintf('%s Re: %s - %s', $subject_prefix, $type_label, $submission->subject);
    
    $message = "Hello {$user_name},\n\n";
    $message .= "Thank you for your {$type_label}. We have reviewed your submission and have a response:\n\n";
    $message .= "---\n{$reply}\n---\n\n";
    $message .= "Your original message:\n";
    $message .= "Subject: {$submission->subject}\n";
    $message .= "Message: {$submission->message}\n";
    
    if (!empty($submission->attachment_id)) {
        $attachment_url = wp_get_attachment_url($submission->attachment_id);
        if ($attachment_url) {
            $message .= "Your Screenshot: {$attachment_url}\n";
        }
    }
    
    $message .= "\nIf you have any additional questions, please feel free to submit another feedback.\n";
    
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    // Set From name if configured
    $admin_email = get_option('userfeedback_admin_email', get_option('user_feedback_admin_email', get_option('admin_email')));
    $from_name = get_option('userfeedback_email_from_name', get_bloginfo('name'));
    if (!empty($from_name) && !empty($admin_email)) {
        $headers[] = 'From: ' . $from_name . ' <' . $admin_email . '>';
    }
    
    if (!empty($admin_email)) {
        $headers[] = 'Reply-To: ' . $admin_email;
    }
    
    wp_mail($user_email, $subject, $message, $headers);
}

/**
 * Send resolved notification to user
 */
function user_feedback_send_resolved_notification($submission, $resolution_notes = '') {
    // Check if resolved emails are enabled
    if (get_option('userfeedback_enable_resolved_email', '1') !== '1') {
        return;
    }
    
    $user = get_userdata($submission->user_id);
    if (!$user) {
        return;
    }
    
    $user_email = $user->user_email;
    $user_name = $user->display_name;
    
    $type_label = ($submission->type === 'bug') ? 'Bug Report' : 'Feedback';
    
    // Get customizable subject prefix
    $subject_prefix = get_option('userfeedback_email_subject_prefix', '[' . get_bloginfo('name') . ']');
    $subject = sprintf('%s %s Resolved: %s', $subject_prefix, $type_label, $submission->subject);
    
    $message = "Hello {$user_name},\n\n";
    
    if ($submission->type === 'bug') {
        $message .= "Great news! The bug you reported has been resolved.\n\n";
    } else {
        $message .= "Your feedback has been addressed and marked as resolved.\n\n";
    }
    
    $message .= "Subject: {$submission->subject}\n\n";
    
    if (!empty($resolution_notes)) {
        $message .= "Resolution Details:\n{$resolution_notes}\n\n";
    }
    
    if (!empty($submission->admin_reply)) {
        $message .= "Previous Response:\n{$submission->admin_reply}\n\n";
    }
    
    if (!empty($submission->attachment_id)) {
        $attachment_url = wp_get_attachment_url($submission->attachment_id);
        if ($attachment_url) {
            $message .= "Your Screenshot: {$attachment_url}\n\n";
        }
    }
    
    $message .= "Thank you for helping us improve!\n";
    
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    // Set From name if configured
    $admin_email = get_option('userfeedback_admin_email', get_option('user_feedback_admin_email', get_option('admin_email')));
    $from_name = get_option('userfeedback_email_from_name', get_bloginfo('name'));
    if (!empty($from_name) && !empty($admin_email)) {
        $headers[] = 'From: ' . $from_name . ' <' . $admin_email . '>';
    }
    
    if (!empty($admin_email)) {
        $headers[] = 'Reply-To: ' . $admin_email;
    }
    
    wp_mail($user_email, $subject, $message, $headers);
}

