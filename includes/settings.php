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
    
    // Submenu - Submissions (rename main menu link)
    add_submenu_page(
        'user-feedback',
        'Submissions',
        'Submissions',
        'manage_options',
        'user-feedback',
        'user_feedback_submissions_page'
    );
    
    // Note: Form Builder is added via form-builder.php with priority 20
    
    // Submenu - Responses (order: 3rd)
    add_submenu_page(
        'user-feedback',
        'Canned Responses',
        'Responses',
        'manage_options',
        'user-feedback-responses',
        'user_feedback_canned_responses_page'
    );
    
    // Submenu - Settings (order: 4th - last)
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
        
        update_option('userfeedback_admin_email', sanitize_email($_POST['admin_email']));
        update_option('userfeedback_default_status', sanitize_text_field($_POST['default_status']));
        
        // Quick Collector settings
        update_option('user_feedback_quick_collector_enabled', isset($_POST['quick_collector_enabled']) ? '1' : '0');
        update_option('user_feedback_quick_collector_label', sanitize_text_field($_POST['quick_collector_label']));
        update_option('user_feedback_quick_collector_show_details', isset($_POST['quick_collector_show_details']) ? '1' : '0');
        update_option('user_feedback_quick_collector_capture_errors', isset($_POST['quick_collector_capture_errors']) ? '1' : '0');
        update_option('user_feedback_menu_link_enabled', isset($_POST['menu_link_enabled']) ? '1' : '0');
        
        // Attachment settings
        update_option('user_feedback_enable_attachments', isset($_POST['enable_attachments']) ? '1' : '0');
        update_option('user_feedback_max_file_size', intval($_POST['max_file_size']));
        update_option('user_feedback_allowed_file_types', sanitize_text_field($_POST['allowed_file_types']));
        
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }
    
    // Get current settings
    $admin_email = get_option('userfeedback_admin_email', get_option('user_feedback_admin_email', get_option('admin_email')));
    $default_status = get_option('userfeedback_default_status', get_option('user_feedback_default_status', 'new'));
    
    // Quick Collector settings
    $quick_collector_enabled = get_option('user_feedback_quick_collector_enabled', '0');
    $quick_collector_label = get_option('user_feedback_quick_collector_label', 'Quick Feedback');
    $quick_collector_show_details = get_option('user_feedback_quick_collector_show_details', '1');
    $quick_collector_capture_errors = get_option('user_feedback_quick_collector_capture_errors', '1');
    $menu_link_enabled = get_option('user_feedback_menu_link_enabled', '1');
    
    // Attachment settings
    $enable_attachments = get_option('user_feedback_enable_attachments', '1');
    $max_file_size = get_option('user_feedback_max_file_size', 5);
    $allowed_file_types = get_option('user_feedback_allowed_file_types', 'jpg,jpeg,png,gif,webp');
    ?>
    <div class="wrap">
        <h1>User Feedback Settings</h1>
        
        <div class="userfeedback-notice userfeedback-notice-info" style="background: #e7f3ff; border-left: 4px solid #0073aa; padding: 12px; margin: 20px 0;">
            <p style="margin: 0;">
                <strong>Custom Form Builder!</strong> 
                Create custom forms with dynamic fields instead of using hardcoded types. 
                <a href="<?php echo esc_url(admin_url('admin.php?page=user-feedback-form-builder')); ?>" class="button button-primary" style="margin-left: 10px;">
                    Go to Forms
                </a>
            </p>
        </div>
        
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
                            Display a floating quick feedback button for logged-in users (also available in the admin bar)
                        </label>
                        <p class="description">When enabled, logged-in users will see a pill-shaped feedback button that floats on every page, even if the admin bar is hidden. The admin bar shortcut remains available for users who can see it.</p>
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
                        <p class="description">Text displayed on both the floating button and the admin bar shortcut.</p>
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
                
                <tr>
                    <th scope="row">Enable Navigation Menu Link</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="menu_link_enabled" 
                                   value="1" 
                                   <?php checked($menu_link_enabled, '1'); ?>>
                            Allow adding feedback modal links to navigation menus
                        </label>
                        <p class="description">
                            When enabled, you can add a feedback link to any menu. 
                            <a href="<?php echo esc_url(admin_url('nav-menus.php')); ?>" class="button button-small" style="vertical-align: baseline;">Go to Menus</a>
                        </p>
                        
                        <?php if ($menu_link_enabled === '1'): ?>
                        <div style="background: #f0f6fc; border-left: 4px solid #0073aa; padding: 12px; margin-top: 10px;">
                            <p style="margin: 0 0 10px 0;"><strong>📝 How to add feedback link to your menu:</strong></p>
                            <ol style="margin: 0 0 0 20px; line-height: 1.8;">
                                <li>Go to <strong>Appearance &gt; Menus</strong></li>
                                <li>Find <strong>"Custom Links"</strong> section in the left sidebar</li>
                                <li>URL: <code style="background: #fff; padding: 2px 6px; border: 1px solid #ddd;">#user-feedback-modal</code></li>
                                <li>Link Text: <strong>Feedback</strong> (or any text you want)</li>
                                <li>Click "Add to Menu" and save</li>
                            </ol>
                        </div>
                        <?php endif; ?>
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

