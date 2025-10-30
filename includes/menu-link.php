<?php
/**
 * Navigation Menu Link for User Feedback plugin
 * Allows adding a feedback modal trigger to navigation menus
 */

if (!defined('WPINC')) {
    die;
}


/**
 * Add custom CSS class to body for frontend identification
 */
add_filter('body_class', 'user_feedback_menu_body_class');
function user_feedback_menu_body_class($classes) {
    // Check if quick collector or menu link should be enabled
    if ((user_feedback_is_quick_collector_enabled() && is_user_logged_in()) || user_feedback_is_menu_link_enabled()) {
        $classes[] = 'user-feedback-menu-enabled';
    }
    
    return $classes;
}

/**
 * Add JavaScript to handle menu link clicks
 */
add_action('wp_footer', 'user_feedback_menu_link_script', 999);
add_action('admin_footer', 'user_feedback_menu_link_script', 999);
function user_feedback_menu_link_script() {
    // Only add if user is logged in
    if (!is_user_logged_in()) {
        return;
    }
    
    if (!user_feedback_is_menu_link_enabled()) {
        return;
    }
    
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Handle clicks on menu items with feedback modal trigger
        $(document).on('click', 'a[href="#user-feedback-modal"], .user-feedback-menu-trigger a', function(e) {
            e.preventDefault();
            
            // Check if modal exists (Quick Collector might be disabled)
            if ($('#user-feedback-quick-modal').length > 0) {
                $('#user-feedback-quick-modal').fadeIn();
                
                // Collect and display technical metadata if available
                if (typeof collectTechnicalMetadata !== 'undefined' && 
                    typeof displayTechnicalMetadata !== 'undefined' &&
                    typeof userFeedback !== 'undefined' && 
                    userFeedback.quickCollector && 
                    userFeedback.quickCollector.showDetails) {
                    var metadata = window.collectTechnicalMetadata();
                    var html = window.displayTechnicalMetadata(metadata);
                    $('#ufq-technical-preview').html(html);
                }
                
                // Focus on subject field
                $('#ufq-subject').focus();
            } else {
                // Fallback: Show alert if modal not available
                alert('Feedback modal is not available. Please contact the site administrator.');
            }
        });
    });
    </script>
    <?php
}

/**
 * Output the feedback modal if menu link is enabled (even if Quick Collector is disabled)
 */
add_action('wp_footer', 'user_feedback_menu_modal_fallback', 5);
add_action('admin_footer', 'user_feedback_menu_modal_fallback', 5);
function user_feedback_menu_modal_fallback() {
    // Only output if menu link is enabled but quick collector is disabled
    // (Quick collector already outputs the modal)
    if (user_feedback_is_menu_link_enabled() && !user_feedback_is_quick_collector_enabled()) {
        if (is_user_logged_in()) {
            // Call the quick collector modal function since it's the same modal
            user_feedback_quick_collector_modal();
        }
    }
}

/**
 * Add admin notice to guide users on how to add the menu link
 */
add_action('admin_notices', 'user_feedback_menu_link_notice');
function user_feedback_menu_link_notice() {
    // Only show on nav-menus.php page
    $screen = get_current_screen();
    if ($screen && $screen->id === 'nav-menus') {
        if (user_feedback_is_menu_link_enabled()) {
            ?>
            <div class="notice notice-info" style="padding: 15px;">
                <h3 style="margin-top: 0;">📝 User Feedback Plugin - Add Feedback Link to Menu</h3>
                <p><strong>Follow these simple steps to add a feedback link:</strong></p>
                <ol style="margin-left: 20px; line-height: 1.8;">
                    <li>Scroll down to <strong>"Custom Links"</strong> in the left sidebar</li>
                    <li>In the <strong>URL</strong> field, enter: <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">#user-feedback-modal</code></li>
                    <li>In the <strong>Link Text</strong> field, enter: <strong>Feedback</strong> (or any text you prefer)</li>
                    <li>Click <strong>"Add to Menu"</strong></li>
                    <li>The link will appear in your menu structure on the right</li>
                    <li>Click <strong>"Save Menu"</strong></li>
                </ol>
                <p style="margin-bottom: 0;"><em>Optional: Expand the menu item (click arrow) and add CSS class <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">user-feedback-menu-trigger</code> for custom styling.</em></p>
            </div>
            <?php
        }
    }
}

