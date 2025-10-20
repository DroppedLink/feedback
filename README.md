# User Feedback & Bug Reports Plugin

A comprehensive WordPress plugin for managing user feedback, comments, and bug reports with email notifications, canned responses, and detailed status tracking.

## Features

- **Dual Submission Types**: Handle both comments/questions and bug reports
- **User Authentication**: Requires users to be logged in to submit feedback
- **Quick Feedback Collector**: One-click feedback button in admin bar with auto-collected technical data
- **Navigation Menu Link**: Add feedback modal trigger to any navigation menu
- **Screenshot Attachments**: Users can attach screenshots to help illustrate bugs and feedback
- **Advanced Data Collection**: Automatically captures page URL, browser info, screen size, console errors, and more
- **Email Notifications**: Automated emails for new submissions, replies, and resolutions
- **Canned Responses**: Pre-configured responses for common scenarios
- **Status Workflow**: Detailed status tracking (New, In Progress, Testing, Resolved, Won't Fix)
- **Context Tracking**: Track bugs by specific pages or features using context IDs
- **Admin Dashboard**: Comprehensive interface for managing all submissions
- **Filtering & Search**: Filter by type, status, and search submissions
- **CSV Export**: Export submissions for external analysis
- **Changelog Display**: Public shortcode to display resolved bugs
- **Responsive Design**: Works seamlessly on desktop and mobile devices
- **Widget Support**: Add feedback forms to sidebars and widget areas

## Installation

1. Upload the `user-feedback` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **User Feedback > Settings** to configure the plugin
4. Add the shortcode to any page or post, or use the widget

## Usage

### Shortcodes

#### Basic Comment/Question Form
```
[user_feedback type="comment"]
```

#### Bug Report Form
```
[user_feedback type="bug"]
```

#### Bug Report with Context ID
```
[user_feedback type="bug" context_id="page-checkout"]
```

#### Display Changelog (Resolved Bugs)
```
[feedback_changelog limit="10"]
```

#### Changelog for Specific Context
```
[feedback_changelog context_id="page-checkout" limit="5"]
```

### Widget

1. Go to **Appearance > Widgets**
2. Find the **User Feedback Form** widget
3. Drag it to your desired widget area
4. Configure the widget settings:
   - Set the title
   - Choose feedback type (comment or bug)
   - Optionally set a context ID

### Quick Feedback Collector

The Quick Feedback Collector adds a one-click feedback button to the WordPress admin bar for all logged-in users.

**To Enable:**
1. Go to **User Feedback > Settings**
2. Scroll to **Quick Feedback Collector** section
3. Check **Enable Quick Feedback Collector**
4. Customize the button label (default: "Quick Feedback")
5. Choose options:
   - **Show technical details to user** - Users can see what data is being collected
   - **Capture JavaScript console errors** - Automatically captures JS errors for bug reports
6. Save settings

**How It Works:**
- A "Quick Feedback" button appears in the WordPress admin bar (top black bar)
- Click it to open a modal with a quick feedback form
- Technical data is automatically collected:
  - Current page URL and title
  - Referrer URL
  - Browser information (user agent)
  - Screen resolution and viewport size
  - Timezone and language
  - Timestamp
  - JavaScript console errors (if enabled)
- Users can choose feedback type (comment or bug) and submit with their message
- All technical data is stored with the submission for admin review

**Technical Data Benefits:**
- Helps reproduce bugs with exact browser/environment info
- Identifies page-specific issues
- Tracks console errors that users may not notice
- Provides context for better support

### Navigation Menu Link

You can add a feedback modal trigger to any WordPress navigation menu, making it easy for visitors to submit feedback from your site's navigation.

**To Enable:**
1. Go to **User Feedback > Settings**
2. Scroll to **Quick Feedback Collector** section
3. Ensure **Enable Navigation Menu Link** is checked
4. Save settings

**To Add to a Menu:**
1. Go to **Appearance > Menus**
2. Scroll to **"Custom Links"** in the left sidebar
3. URL: Enter `#user-feedback-modal`
4. Link Text: Enter `Feedback` (or any text you prefer)
5. Click **"Add to Menu"**
6. Save your menu

**How It Works:**
- The menu link appears in your site navigation wherever you place it
- Clicking the link opens the same feedback modal as the Quick Collector
- All features work identically (attachments, technical data, etc.)
- Only logged-in users can submit feedback
- The link can be styled with custom CSS using class `.user-feedback-menu-trigger`

**Use Cases:**
- Make feedback more visible to site visitors
- Add to header, footer, or sidebar menus
- Create dedicated "Help" or "Support" menu sections
- Complement the admin bar button for better accessibility

For detailed instructions, see [MENU_LINK_GUIDE.md](MENU_LINK_GUIDE.md)

### Screenshot Attachments

Users can attach screenshots to their feedback submissions to help illustrate issues visually.

**To Enable:**
1. Go to **User Feedback > Settings**
2. Scroll to **Screenshot Attachments** section
3. Check **Enable Screenshot Attachments**
4. Set **Maximum File Size** (1-50 MB, default: 5 MB)
5. Configure **Allowed File Types** (default: jpg,jpeg,png,gif,webp)
6. Save settings

**How It Works:**
- File input appears in all feedback forms (shortcode, widget, Quick Collector)
- Users can select an image file from their device
- Image preview shown before submission
- File is uploaded to WordPress media library
- Screenshot appears in admin dashboard with the submission
- Screenshot URL included in all email notifications
- Click to view full-size image
- Automatic cleanup when submission is deleted

**Supported Formats:**
- JPEG/JPG
- PNG
- GIF
- WebP

**Security:**
- File type validation (MIME type checking)
- File size limits enforced
- Only logged-in users can upload
- Integrated with WordPress media library permissions

### Admin Dashboard

Access the admin dashboard at **User Feedback > Dashboard** where you can:

- View all submissions with statistics
- Filter by type, status, or search terms
- Reply to submissions with custom or canned responses
- Update submission status through the workflow
- Add resolution notes when closing bugs
- Export submissions as CSV
- Delete unwanted submissions

### Canned Responses

1. Navigate to **User Feedback > Canned Responses**
2. Create reusable response templates
3. Use them when replying to submissions for faster responses

### Settings

Configure the plugin at **User Feedback > Settings**:

- **Admin Notification Email**: Where new submission notifications are sent
- **Default Status**: Status assigned to new submissions
- **Enable/Disable Types**: Turn on/off comments or bug reports
- **Form Labels**: Customize form field labels and button text

## Submission Workflow

1. **User submits feedback** via shortcode or widget (must be logged in)
2. **Submission stored** in database with "New" status
3. **Admin receives email** notification
4. **Admin reviews** in dashboard
5. **Admin can**:
   - Reply with canned or custom response
   - Update status (In Progress → Testing → Resolved)
   - Add resolution notes
   - Delete if needed
6. **User receives email** when admin replies or resolves the issue

## Status Definitions

- **New**: Just submitted, awaiting review
- **In Progress**: Being actively worked on
- **Testing**: Under testing/verification
- **Resolved**: Issue fixed or question answered
- **Won't Fix**: Decision made not to address

## Context IDs

Context IDs help you track bugs for specific pages, features, or sections of your site:

```
[user_feedback type="bug" context_id="checkout-page"]
[user_feedback type="bug" context_id="login-form"]
[user_feedback type="bug" context_id="mobile-menu"]
```

When a bug is resolved, you can display fixes for specific contexts:

```
[feedback_changelog context_id="checkout-page"]
```

## Email Templates

The plugin automatically sends three types of emails:

1. **New Submission Notification** (to admin)
   - Includes submitter info, subject, message, and link to dashboard

2. **Reply Notification** (to user)
   - Includes admin's response and original submission

3. **Resolved Notification** (to user)
   - Includes resolution notes and previous responses

All emails use WordPress's `wp_mail()` function and work with any SMTP plugin you have configured.

## Database Structure

The plugin creates two custom tables:

### wp_user_feedback_submissions
Stores all feedback submissions with user info, type, status, replies, and timestamps.

### wp_user_feedback_canned_responses
Stores reusable response templates for quick replies.

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Users must be registered and logged in to submit feedback

## Security

The plugin follows WordPress security best practices:

- All user inputs are sanitized
- All outputs are escaped
- Nonce verification on all forms and AJAX requests
- Capability checks for admin functions
- Prepared statements for database queries
- No direct file access allowed

## Customization

### Custom Styling

The plugin enqueues `assets/css/style.css`. You can override styles in your theme:

```css
.user-feedback-form-container {
    /* Your custom styles */
}
```

### Hooks for Developers

The plugin is built with extensibility in mind. Future updates will include:

- Action hooks for before/after submissions
- Filter hooks for modifying email content
- Custom status types
- Integration with external bug tracking systems

## Troubleshooting

### Emails Not Sending

1. Check **Settings > Admin Email** is configured correctly
2. Install an SMTP plugin (e.g., WP Mail SMTP)
3. Check your server's email configuration

### Submissions Not Appearing

1. Ensure user is logged in
2. Check that submission type is enabled in Settings
3. Check browser console for JavaScript errors

### Widget Not Displaying

1. Ensure users are logged in (or they'll see a login message)
2. Check that the submission type is enabled
3. Verify widget settings are configured correctly

## File Structure

```
user-feedback/
├── user-feedback.php           # Main plugin file
├── includes/
│   ├── database.php            # Database operations
│   ├── settings.php            # Settings page
│   ├── submissions-page.php    # Admin dashboard
│   ├── canned-responses.php    # Canned responses CRUD
│   ├── ajax-handler.php        # AJAX handlers
│   ├── shortcode.php           # Shortcode implementation
│   ├── widget.php              # Widget implementation
│   ├── quick-collector.php     # Quick feedback modal
│   ├── menu-link.php           # Navigation menu integration
│   ├── upload-handler.php      # File upload handling
│   └── email-handler.php       # Email notifications
├── assets/
│   ├── css/
│   │   └── style.css           # All plugin styles
│   └── js/
│       └── script.js           # Frontend & admin JavaScript
├── uninstall.php               # Cleanup on deletion
├── README.md                   # This file
├── MENU_LINK_GUIDE.md          # Navigation menu link guide
├── QUICK_START.md              # Quick start guide
└── CHANGELOG.md                # Version history
```

## Support

For support, feature requests, or bug reports, please contact your system administrator.

## Privacy

This plugin stores:
- User IDs (WordPress user accounts)
- Submission content (subject and message)
- Admin replies and notes
- Timestamps

All data is stored in your WordPress database and is subject to your site's privacy policy.

## License

This plugin is provided as-is for use in your WordPress installation.

## Credits

Developed following WordPress plugin development best practices and the Plugin Programmer's Guide.

