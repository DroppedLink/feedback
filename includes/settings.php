<?php
/**
 * Settings page for User Feedback plugin
 */

if (!defined('WPINC')) {
    die;
}

// Add admin menu
add_action('admin_menu', 'user_feedback_add_admin_menu');
function user_feedback_add_admin_menu() {
    // Main menu item
    add_menu_page(
        'User Feedback',
        'User Feedback',
        'manage_options',
        'user-feedback',
        'user_feedback_submissions_page',
        'dashicons-feedback',
        30
    );
    
    // Submenu - Dashboard (rename main menu link)
    add_submenu_page(
        'user-feedback',
        'Submissions Dashboard',
        'Dashboard',
        'manage_options',
        'user-feedback',
        'user_feedback_submissions_page'
    );
    
    // Submenu - Canned Responses
    add_submenu_page(
        'user-feedback',
        'Canned Responses',
        'Canned Responses',
        'manage_options',
        'user-feedback-responses',
        'user_feedback_canned_responses_page'
    );
    
    // Submenu - Settings
    add_submenu_page(
        'user-feedback',
        'Settings',
        'Settings',
        'manage_options',
        'user-feedback-settings',
        'user_feedback_settings_page'
    );
}

/**
 * Settings page content
 */
function user_feedback_settings_page() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    // Handle form submission
    if (isset($_POST['user_feedback_settings_submit'])) {
        check_admin_referer('user_feedback_settings_nonce');
        
        update_option('user_feedback_admin_email', sanitize_email($_POST['admin_email']));
        update_option('user_feedback_default_status', sanitize_text_field($_POST['default_status']));
        update_option('user_feedback_enable_comments', isset($_POST['enable_comments']) ? '1' : '0');
        update_option('user_feedback_enable_bugs', isset($_POST['enable_bugs']) ? '1' : '0');
        update_option('user_feedback_comment_label', sanitize_text_field($_POST['comment_label']));
        update_option('user_feedback_bug_label', sanitize_text_field($_POST['bug_label']));
        update_option('user_feedback_submit_button_text', sanitize_text_field($_POST['submit_button_text']));
        
        // Quick Collector settings
        update_option('user_feedback_quick_collector_enabled', isset($_POST['quick_collector_enabled']) ? '1' : '0');
        update_option('user_feedback_quick_collector_label', sanitize_text_field($_POST['quick_collector_label']));
        update_option('user_feedback_quick_collector_show_details', isset($_POST['quick_collector_show_details']) ? '1' : '0');
        update_option('user_feedback_quick_collector_capture_errors', isset($_POST['quick_collector_capture_errors']) ? '1' : '0');
        
        // Attachment settings
        update_option('user_feedback_enable_attachments', isset($_POST['enable_attachments']) ? '1' : '0');
        update_option('user_feedback_max_file_size', intval($_POST['max_file_size']));
        update_option('user_feedback_allowed_file_types', sanitize_text_field($_POST['allowed_file_types']));
        
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }
    
    // Get current settings
    $admin_email = get_option('user_feedback_admin_email', get_option('admin_email'));
    $default_status = get_option('user_feedback_default_status', 'new');
    $enable_comments = get_option('user_feedback_enable_comments', '1');
    $enable_bugs = get_option('user_feedback_enable_bugs', '1');
    $comment_label = get_option('user_feedback_comment_label', 'Comment/Question');
    $bug_label = get_option('user_feedback_bug_label', 'Bug Report');
    $submit_button_text = get_option('user_feedback_submit_button_text', 'Submit');
    
    // Quick Collector settings
    $quick_collector_enabled = get_option('user_feedback_quick_collector_enabled', '0');
    $quick_collector_label = get_option('user_feedback_quick_collector_label', 'Quick Feedback');
    $quick_collector_show_details = get_option('user_feedback_quick_collector_show_details', '1');
    $quick_collector_capture_errors = get_option('user_feedback_quick_collector_capture_errors', '1');
    
    // Attachment settings
    $enable_attachments = get_option('user_feedback_enable_attachments', '1');
    $max_file_size = get_option('user_feedback_max_file_size', 5);
    $allowed_file_types = get_option('user_feedback_allowed_file_types', 'jpg,jpeg,png,gif,webp');
    ?>
    <div class="wrap">
        <h1>User Feedback Settings</h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('user_feedback_settings_nonce'); ?>
            
            <h2>General Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="admin_email">Admin Notification Email</label>
                    </th>
                    <td>
                        <input type="email" 
                               id="admin_email" 
                               name="admin_email" 
                               value="<?php echo esc_attr($admin_email); ?>" 
                               class="regular-text">
                        <p class="description">Email address to receive notifications about new submissions</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="default_status">Default Status for New Submissions</label>
                    </th>
                    <td>
                        <select id="default_status" name="default_status">
                            <option value="new" <?php selected($default_status, 'new'); ?>>New</option>
                            <option value="in_progress" <?php selected($default_status, 'in_progress'); ?>>In Progress</option>
                        </select>
                        <p class="description">Status assigned to newly submitted items</p>
                    </td>
                </tr>
            </table>
            
            <h2>Submission Types</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Enable Submission Types</th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" 
                                       name="enable_comments" 
                                       value="1" 
                                       <?php checked($enable_comments, '1'); ?>>
                                Comments/Questions
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" 
                                       name="enable_bugs" 
                                       value="1" 
                                       <?php checked($enable_bugs, '1'); ?>>
                                Bug Reports
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
            
            <h2>Form Labels</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="comment_label">Comment/Question Label</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="comment_label" 
                               name="comment_label" 
                               value="<?php echo esc_attr($comment_label); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="bug_label">Bug Report Label</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="bug_label" 
                               name="bug_label" 
                               value="<?php echo esc_attr($bug_label); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="submit_button_text">Submit Button Text</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="submit_button_text" 
                               name="submit_button_text" 
                               value="<?php echo esc_attr($submit_button_text); ?>" 
                               class="regular-text">
                    </td>
                </tr>
            </table>
            
            <h2>Quick Feedback Collector</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Enable Quick Feedback Collector</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="quick_collector_enabled" 
                                   value="1" 
                                   <?php checked($quick_collector_enabled, '1'); ?>>
                            Add a quick feedback button to the WordPress admin bar
                        </label>
                        <p class="description">When enabled, logged-in users will see a feedback button in the top admin bar that automatically collects page context.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="quick_collector_label">Button Label</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="quick_collector_label" 
                               name="quick_collector_label" 
                               value="<?php echo esc_attr($quick_collector_label); ?>" 
                               class="regular-text">
                        <p class="description">Text displayed on the admin bar button</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Quick Collector Options</th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" 
                                       name="quick_collector_show_details" 
                                       value="1" 
                                       <?php checked($quick_collector_show_details, '1'); ?>>
                                Show technical details to user before submission
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" 
                                       name="quick_collector_capture_errors" 
                                       value="1" 
                                       <?php checked($quick_collector_capture_errors, '1'); ?>>
                                Capture JavaScript console errors
                            </label>
                        </fieldset>
                        <p class="description">Technical details include: page URL, browser info, screen size, timezone, and console errors</p>
                    </td>
                </tr>
            </table>
            
            <h2>Screenshot Attachments</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Enable Screenshot Attachments</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="enable_attachments" 
                                   value="1" 
                                   <?php checked($enable_attachments, '1'); ?>>
                            Allow users to attach screenshots to their feedback
                        </label>
                        <p class="description">Users can upload images to help illustrate bugs or feedback</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="max_file_size">Maximum File Size (MB)</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="max_file_size" 
                               name="max_file_size" 
                               value="<?php echo esc_attr($max_file_size); ?>" 
                               min="1" 
                               max="50" 
                               step="1"
                               class="small-text">
                        <p class="description">Maximum file size in megabytes (1-50 MB)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="allowed_file_types">Allowed File Types</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="allowed_file_types" 
                               name="allowed_file_types" 
                               value="<?php echo esc_attr($allowed_file_types); ?>" 
                               class="regular-text">
                        <p class="description">Comma-separated list of allowed extensions (e.g., jpg,jpeg,png,gif,webp)</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" 
                       name="user_feedback_settings_submit" 
                       class="button button-primary" 
                       value="Save Settings">
            </p>
        </form>
    </div>
    <?php
}

