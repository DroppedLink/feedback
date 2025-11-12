# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Overview

User Feedback & Bug Reports is a WordPress plugin (v2.0.0) that provides a comprehensive feedback management system with a dynamic form builder. Users can create unlimited custom forms organized into hierarchical categories, with support for various field types including dropdowns, text fields, textareas, and file uploads.

## Architecture

### Core Components

**Main Plugin Structure:**
- `user-feedback.php` - Plugin bootstrap, asset registration, and initialization
- `includes/` - Modular PHP functionality, each file handles a specific domain
- `assets/` - Frontend and admin CSS/JS (single files: `style.css`, `script.js`)
- `uninstall.php` - Cleanup on plugin deletion

**Key Modules:**
- `database.php` - All database operations, CRUD functions, and migrations
- `form-builder.php` - Visual form builder interface with category and form management
- `shortcode.php` - Dynamic form rendering via shortcodes (`[userfeedback form="..."]`)
- `ajax-handler.php` - All AJAX endpoints for form submissions and admin operations
- `submissions-page.php` - Admin dashboard for viewing/managing feedback
- `settings.php` - Plugin configuration interface
- `email-handler.php` - Email notifications (new submissions, replies, resolutions)
- `quick-collector.php` - One-click feedback modal in admin bar with auto-collected technical data
- `widget.php` - WordPress widget implementation
- `upload-handler.php` - File upload/attachment management
- `menu-link.php` - Navigation menu integration
- `helpers.php` - Shared utility functions

### Database Schema

**Tables (prefix: `wp_userfeedback_`):**
- `form_categories` - Hierarchical organization of forms
- `custom_forms` - Form configurations with JSON field definitions
- `submissions` - All feedback submissions with status workflow
- `canned_responses` - Reusable response templates

**Key columns in submissions:**
- `form_id` - Links to custom forms (NULL for legacy submissions)
- `form_data` - JSON storage of dynamic field responses
- `metadata` - Technical data (browser, URL, errors) from Quick Collector
- `attachment_id` - WordPress media library ID for screenshots
- `status` - Workflow: new, in_progress, testing, resolved, wont_fix

### Form Builder System (v2.0+)

**Hierarchical Structure:**
```
Categories (e.g., "Agents", "Bug Reports")
  └── Forms (e.g., "Zabbix", "CrowdStrike")
        └── Fields (dropdown, text, textarea, file)
```

**Field Configuration:**
- Stored as JSON in `custom_forms.field_config`
- Field types: `select`, `text`, `textarea`, `file`
- Each field has: type, name, label, required flag, type-specific options
- Frontend renders fields dynamically based on configuration

**Legacy Support:**
- Old shortcodes `[user_feedback type="comment|bug"]` still work
- Legacy submissions display with "(Legacy)" badge
- Gradual migration path without breaking existing forms

## Development Commands

### Testing & Validation

**WordPress Environment:**
This plugin requires a WordPress installation to test. There are no standalone test commands.

**Manual Testing:**
1. Activate plugin: WP Admin → Plugins → Activate "User Feedback & Bug Reports"
2. Test form builder: WP Admin → User Feedback → Form Builder
3. Create test submission: View any page with `[userfeedback form="..."]` shortcode
4. Check admin dashboard: WP Admin → User Feedback → Dashboard

**Database Operations:**
```bash
# Access WordPress database (adjust credentials)
mysql -u root -p wordpress

# Check plugin tables
SHOW TABLES LIKE '%userfeedback%';

# View forms
SELECT * FROM wp_userfeedback_custom_forms;

# View submissions
SELECT * FROM wp_userfeedback_submissions ORDER BY created_at DESC LIMIT 10;
```

### Code Quality

**No automated linting/testing configured.** Follow WordPress Coding Standards manually:
- Sanitize all user input: `sanitize_text_field()`, `sanitize_textarea_field()`
- Escape all output: `esc_html()`, `esc_attr()`, `esc_url()`
- Use nonces for forms: `wp_create_nonce()`, `wp_verify_nonce()`
- Prepared statements for DB queries: `$wpdb->prepare()`
- Check capabilities: `current_user_can('manage_options')`

### File Modification Workflow

**When editing PHP files:**
1. Edit files directly in `wp-content/plugins/user-feedback/`
2. Changes take effect immediately (WordPress loads PHP on each request)
3. For database changes: increment version in `user_feedback_check_db_version()`
4. Clear WordPress object cache if using persistent caching

**When editing CSS/JS:**
1. Edit `assets/css/style.css` or `assets/js/script.js`
2. Increment version in `user-feedback.php` constant `USER_FEEDBACK_VERSION` to bust cache
3. Hard refresh browser (Cmd+Shift+R) to see changes

**Important:** Changes to this plugin may require restarting WordPress containers/services if running in Docker/containerized environment.

## Key Patterns & Conventions

### Naming Convention Evolution

**Version 2.0 introduced new naming:**
- Old: `user_feedback_*` (snake_case with underscore)
- New: `userfeedback_*` (snake_case, no underscore between words)
- Apply new naming to all new functions, tables, and CSS classes
- Keep old naming for backward compatibility functions

### Function Prefixes

- `userfeedback_*` - New v2.0+ functions
- `user_feedback_*` - Legacy functions (maintain for compatibility)

### Security Requirements

**Every AJAX handler must:**
1. Check nonce: `check_ajax_referer('user_feedback_nonce')`
2. Verify capability for admin actions: `if (!current_user_can('manage_options'))`
3. Sanitize input based on expected data type
4. Return JSON response: `wp_send_json_success()` or `wp_send_json_error()`

**Frontend forms must:**
1. Require user login: `is_user_logged_in()`
2. Include nonce field: `wp_nonce_field('user_feedback_submit')`
3. Validate file uploads: Check MIME type, size, extension

### Asset Management

**CSS/JS are registered once, enqueued conditionally:**
- Assets registered in `user_feedback_register_assets()` on `init` hook
- Enqueued selectively based on context:
  - Admin pages: Only on plugin screens or when Quick Collector enabled
  - Frontend: Only when shortcode used or widget active
- Localization done once per request via `user_feedback_localize_script()`

### Database Migrations

**Version-based migrations:**
- Check `userfeedback_db_version` option
- Run migrations in `user_feedback_check_db_version()` on `plugins_loaded`
- Each migration function is idempotent (safe to run multiple times)
- Always use `ALTER TABLE` with existence checks, never DROP

**Migration pattern:**
```php
if (version_compare($current_version, '2.1', '<')) {
    userfeedback_migrate_to_2_1();
    update_option('userfeedback_db_version', '2.1');
}
```

## Common Tasks

### Adding a New Field Type

1. **Update form builder UI** (`form-builder.php`):
   - Add button to "Add Field" section
   - Add field configuration HTML template
   - Add JavaScript handler in `script.js`

2. **Update field rendering** (`shortcode.php`):
   - Add case in `userfeedback_render_form_field()` function
   - Handle new field type HTML generation

3. **Update submission handler** (`ajax-handler.php`):
   - Add sanitization logic in `user_feedback_submit_handler()`
   - Store field data in `form_data` JSON

4. **Update admin display** (`submissions-page.php`):
   - Add formatting for field display in submission view

### Creating a New AJAX Endpoint

1. **Add handler function** (`ajax-handler.php`):
```php
function userfeedback_my_action_handler() {
    check_ajax_referer('user_feedback_nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    $data = isset($_POST['data']) ? sanitize_text_field($_POST['data']) : '';
    
    // Process...
    
    wp_send_json_success(['result' => $result]);
}
add_action('wp_ajax_userfeedback_my_action', 'userfeedback_my_action_handler');
```

2. **Add frontend JavaScript** (`script.js`):
```javascript
jQuery.ajax({
    url: userFeedback.ajaxUrl,
    type: 'POST',
    data: {
        action: 'userfeedback_my_action',
        nonce: userFeedback.nonce,
        data: myData
    },
    success: function(response) {
        // Handle success
    }
});
```

### Modifying Email Templates

**All emails sent via `send_email_notification()` in `email-handler.php`:**
- New submission: Sent to admin email (configured in settings)
- Reply notification: Sent to submitter with admin response
- Resolution notification: Sent to submitter with resolution notes

**To customize email content:**
1. Edit `send_email_notification()` function
2. Modify email body HTML (uses inline styles for email client compatibility)
3. Test with different email clients (Gmail, Outlook, Apple Mail)

### Adding Status Workflow States

**Current statuses:** new, in_progress, testing, resolved, wont_fix

**To add new status:**
1. Update status dropdown in `submissions-page.php` (line ~156)
2. Add status handling in `user_feedback_update_submission_status()` (`ajax-handler.php`)
3. Add CSS badge color in `style.css` (`.status-badge.status-{name}`)
4. Update status filter in dashboard

## File Upload Configuration

**Settings location:** User Feedback > Settings > Screenshot Attachments

**Key functions:**
- `user_feedback_get_max_file_size_mb()` - Get configured size limit
- `user_feedback_get_allowed_file_types()` - Get allowed extensions
- `user_feedback_get_allowed_mime_types()` - Get MIME type array
- `user_feedback_handle_file_upload()` - Process upload and return attachment ID

**Upload validation:**
- Validates MIME type using WordPress `wp_check_filetype()`
- Checks file size against setting
- Stores in WordPress media library via `media_handle_upload()`
- Links to submission via `attachment_id` column
- Auto-deletes from media library when submission deleted

## Troubleshooting

### Common Issues

**Forms not appearing:**
- Check if form is set to `is_active = 1`
- Verify shortcode matches form's `shortcode` column exactly
- Ensure user is logged in (plugin requires authentication)
- Check browser console for JavaScript errors

**Submissions not saving:**
- Verify nonce is valid (check AJAX response)
- Check database connection and table existence
- Review PHP error logs for database errors
- Ensure `form_data` JSON is valid

**Emails not sending:**
- WordPress email is notoriously unreliable
- Install SMTP plugin (e.g., WP Mail SMTP, Post SMTP)
- Check `user_feedback_admin_email` option is set
- Review email logs if SMTP plugin has logging

**Database migrations not running:**
- Check `userfeedback_db_version` option value
- Manually trigger: Deactivate and reactivate plugin
- Review `user_feedback_check_db_version()` for version checks
- Check PHP error logs for migration errors

### Debugging Tips

**Enable WordPress debugging** (`wp-config.php`):
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**Check logs:**
- PHP errors: `wp-content/debug.log`
- JavaScript errors: Browser console (F12)
- AJAX responses: Browser Network tab (F12)

**Database queries:**
- Install Query Monitor plugin to see all database queries
- Check for slow queries or errors in submissions table

**Asset loading:**
- Verify script localization: `console.log(userFeedback)` in browser console
- Check asset versions to confirm cache busting working
- Hard refresh to bypass browser cache

## WordPress Integration Points

### Hooks Used

**Actions:**
- `plugins_loaded` - Initialize plugin, check DB version
- `admin_menu` - Register admin pages
- `admin_enqueue_scripts` - Load admin assets
- `wp_enqueue_scripts` - Load frontend assets
- `init` - Register assets, add admin bar items
- `widgets_init` - Register widget
- `wp_ajax_*` - All AJAX handlers

**Filters:**
- None currently implemented (potential future enhancement)

### WordPress APIs Used

- **Options API:** `get_option()`, `update_option()`, `add_option()`
- **Database API:** `$wpdb` with prepared statements
- **Media API:** `media_handle_upload()`, `wp_get_attachment_url()`
- **User API:** `get_userdata()`, `is_user_logged_in()`, `current_user_can()`
- **Email API:** `wp_mail()`
- **Admin API:** `add_menu_page()`, `add_submenu_page()`
- **Widgets API:** `WP_Widget` class extension
- **Shortcode API:** `add_shortcode()`
- **Nonce API:** `wp_create_nonce()`, `wp_verify_nonce()`, `check_ajax_referer()`

## Important Notes

- **User Authentication Required:** All feedback submissions require logged-in WordPress users
- **No REST API:** Plugin uses admin-ajax.php for all AJAX operations
- **Single Responsibility:** Each include file handles one domain (forms, submissions, emails, etc.)
- **JSON Storage:** Dynamic form configurations and responses stored as JSON in longtext columns
- **WordPress Standards:** Follows WordPress Plugin Developer Handbook conventions
- **No Build Process:** No webpack, npm, or compilation required - edit files directly
