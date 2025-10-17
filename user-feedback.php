<?php
/**
 * Plugin Name: User Feedback & Bug Reports
 * Description: Manage user feedback, comments, and bug reports with email notifications and detailed status tracking
 * Version: 1.2.1
 * Author: Your Name
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Define constants
define('USER_FEEDBACK_VERSION', '1.2.1');
define('USER_FEEDBACK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('USER_FEEDBACK_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/database.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/settings.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/shortcode.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/ajax-handler.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/email-handler.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/submissions-page.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/canned-responses.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/widget.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/quick-collector.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/upload-handler.php';

// Activation hook
register_activation_hook(__FILE__, 'user_feedback_activate');
function user_feedback_activate() {
    // Create database tables
    user_feedback_create_tables();
    
    // Set default options
    add_option('user_feedback_admin_email', get_option('admin_email'));
    add_option('user_feedback_default_status', 'new');
    add_option('user_feedback_enable_comments', '1');
    add_option('user_feedback_enable_bugs', '1');
    add_option('user_feedback_db_version', '1.0');
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'user_feedback_deactivate');
function user_feedback_deactivate() {
    // Cleanup if needed (keep data for now)
}

// Initialize plugin
add_action('plugins_loaded', 'user_feedback_init');
function user_feedback_init() {
    // Check database version and upgrade if needed
    user_feedback_check_db_version();
}

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', 'user_feedback_enqueue_admin_assets');
function user_feedback_enqueue_admin_assets($hook) {
    // Only load on plugin pages
    if (strpos($hook, 'user-feedback') === false) {
        return;
    }
    
    wp_enqueue_style(
        'user-feedback-admin-style',
        USER_FEEDBACK_PLUGIN_URL . 'assets/css/style.css',
        array(),
        USER_FEEDBACK_VERSION
    );
    
    wp_enqueue_script(
        'user-feedback-admin-script',
        USER_FEEDBACK_PLUGIN_URL . 'assets/js/script.js',
        array('jquery'),
        USER_FEEDBACK_VERSION,
        true
    );
    
    // Pass data to JavaScript
    wp_localize_script('user-feedback-admin-script', 'userFeedback', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('user_feedback_nonce'),
        'isAdmin' => true
    ));
}

// Enqueue frontend scripts and styles
add_action('wp_enqueue_scripts', 'user_feedback_enqueue_frontend_assets');
function user_feedback_enqueue_frontend_assets() {
    wp_enqueue_style(
        'user-feedback-style',
        USER_FEEDBACK_PLUGIN_URL . 'assets/css/style.css',
        array(),
        USER_FEEDBACK_VERSION
    );
    
    wp_enqueue_script(
        'user-feedback-script',
        USER_FEEDBACK_PLUGIN_URL . 'assets/js/script.js',
        array('jquery'),
        USER_FEEDBACK_VERSION,
        true
    );
    
    // Pass data to JavaScript
    wp_localize_script('user-feedback-script', 'userFeedback', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('user_feedback_nonce'),
        'isAdmin' => false,
        'isLoggedIn' => is_user_logged_in()
    ));
}

