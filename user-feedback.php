<?php
/**
 * Plugin Name: User Feedback & Bug Reports
 * Description: Manage user feedback, comments, and bug reports with email notifications and detailed status tracking
 * Version: 2.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Define constants
define('USER_FEEDBACK_VERSION', '2.0.0');
define('USER_FEEDBACK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('USER_FEEDBACK_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/helpers.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/database.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/settings.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/form-builder.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/shortcode.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/ajax-handler.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/email-handler.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/submissions-page.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/canned-responses.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/widget.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/quick-collector.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/upload-handler.php';
require_once USER_FEEDBACK_PLUGIN_DIR . 'includes/menu-link.php';

// Register core assets so we can enqueue them conditionally throughout the plugin
add_action('init', 'user_feedback_register_assets');
function user_feedback_register_assets() {
    wp_register_style(
        'user-feedback-style',
        USER_FEEDBACK_PLUGIN_URL . 'assets/css/style.css',
        array(),
        USER_FEEDBACK_VERSION
    );

    wp_register_script(
        'user-feedback-script',
        USER_FEEDBACK_PLUGIN_URL . 'assets/js/script.js',
        array('jquery'),
        USER_FEEDBACK_VERSION,
        true
    );
}

/**
 * Localize shared script data once per request.
 *
 * @param array $overrides Optional override values for specific contexts.
 */
function user_feedback_localize_script(array $overrides = array()) {
    static $localized = false;

    if ($localized) {
        return;
    }

    $allowed_file_types = user_feedback_get_allowed_file_types();
    $allowed_mime_types = user_feedback_get_allowed_mime_types();

    $defaults = array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('user_feedback_nonce'),
        'isAdmin' => current_user_can('manage_options'),
        'isDashboard' => false,
        'isLoggedIn' => is_user_logged_in(),
        'maxFileSizeMb' => user_feedback_get_max_file_size_mb(),
        'maxFileSizeBytes' => user_feedback_get_max_file_size_bytes(),
        'allowedFileTypes' => $allowed_file_types,
        'allowedMimeTypes' => $allowed_mime_types,
        'quickCollector' => array(
            'enabled' => (user_feedback_is_quick_collector_enabled() && is_user_logged_in()),
            'captureErrors' => get_option('user_feedback_quick_collector_capture_errors', '1') === '1',
            'showDetails' => get_option('user_feedback_quick_collector_show_details', '1') === '1',
            'buttonLabel' => get_option('user_feedback_quick_collector_label', 'Quick Feedback'),
        ),
        'menuLinkEnabled' => user_feedback_is_menu_link_enabled(),
    );

    if (isset($overrides['quickCollector']) && is_array($overrides['quickCollector'])) {
        $defaults['quickCollector'] = wp_parse_args($overrides['quickCollector'], $defaults['quickCollector']);
        unset($overrides['quickCollector']);
    }

    $data = wp_parse_args($overrides, $defaults);

    wp_localize_script('user-feedback-script', 'userFeedback', $data);
    $localized = true;
}

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
    $on_plugin_screen = strpos($hook, 'user-feedback') !== false;
    $requires_global_assets = user_feedback_should_enqueue_global_assets();

    if (!$on_plugin_screen && !$requires_global_assets) {
        return;
    }

    wp_enqueue_style('user-feedback-style');
    wp_enqueue_script('user-feedback-script');

    user_feedback_localize_script(array(
        'isDashboard' => $on_plugin_screen,
    ));
}

// Enqueue frontend scripts and styles
add_action('wp_enqueue_scripts', 'user_feedback_enqueue_frontend_assets');
function user_feedback_enqueue_frontend_assets() {
    wp_enqueue_style('user-feedback-style');
    wp_enqueue_script('user-feedback-script');

    user_feedback_localize_script();
}

