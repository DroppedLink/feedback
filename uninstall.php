<?php
/**
 * Uninstall script for User Feedback plugin
 * 
 * This file is executed when the plugin is deleted from WordPress
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;

// Delete options
delete_option('user_feedback_admin_email');
delete_option('user_feedback_default_status');
delete_option('user_feedback_enable_comments');
delete_option('user_feedback_enable_bugs');
delete_option('user_feedback_comment_label');
delete_option('user_feedback_bug_label');
delete_option('user_feedback_submit_button_text');
delete_option('user_feedback_db_version');

// Drop custom tables
$submissions_table = $wpdb->prefix . 'user_feedback_submissions';
$responses_table = $wpdb->prefix . 'user_feedback_canned_responses';

$wpdb->query("DROP TABLE IF EXISTS $submissions_table");
$wpdb->query("DROP TABLE IF EXISTS $responses_table");

// Clear any cached data
wp_cache_flush();

