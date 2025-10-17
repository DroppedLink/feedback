# Changelog

All notable changes to the User Feedback & Bug Reports plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.1] - 2025-10-16

### Added
- **Clipboard Paste Support**: Users can now paste images directly from clipboard
  - Works in all feedback forms (shortcode, widget, Quick Collector)
  - Press Ctrl+V (Windows/Linux) or Cmd+V (Mac) to paste
  - Instant preview of pasted image
  - Success notification when image is pasted
  - Helper text added to forms indicating paste support

### Fixed
- **Upload Bug**: Fixed issue where form would get stuck on "Uploading..." when no file was selected
  - Improved file detection logic to handle missing file inputs
  - Added robust null checks for file input elements
  - Fixed callback execution when attachments are disabled
  - Better error handling for edge cases

### Changed
- File input helper text updated to mention clipboard paste option
- Upload function now checks for pasted files before file input
- Pasted files cleared after successful submission
- Enhanced file validation to prevent stuck states

## [1.2.0] - 2025-10-16

### Added
- **Screenshot Attachments**: Users can now attach screenshots to feedback submissions
  - File upload in both regular forms (shortcode/widget) and Quick Feedback Collector
  - Image preview before submission
  - Drag-and-drop file selection
  - Automatic upload to WordPress media library
  - Support for JPEG, PNG, GIF, and WebP formats
- **Attachment Settings** in settings page:
  - Enable/disable screenshot attachments
  - Configure maximum file size (1-50 MB)
  - Customize allowed file types
- **Attachment Display**: Screenshots shown in admin dashboard with preview
  - Click to view full size
  - Direct link to media file
- **Email Integration**: Screenshot URLs included in all email notifications
  - Admin receives screenshot link with new submission
  - Users see their screenshot in reply emails
- **Automatic Cleanup**: Attachments deleted from media library when submission is deleted

### Changed
- Database version upgraded to 1.2 with automatic migration
- Added `attachment_id` column to store WordPress media library attachment IDs
- Form submissions now handle file uploads before creating submission record
- JavaScript updated to handle file validation and upload progress
- Enhanced CSS for file input styling and preview display

### Technical
- Added `includes/upload-handler.php` for file upload management
- File validation: type checking, size limits, MIME type verification
- Uses WordPress native `wp_handle_upload()` for secure file handling
- Thumbnails automatically generated for uploaded images
- Integration with WordPress media library for attachment management

## [1.1.0] - 2025-10-16

### Added
- **Quick Feedback Collector**: One-click feedback button in WordPress admin bar
  - Accessible from any page for logged-in users
  - Opens modal with quick submission form
  - User can choose feedback type (comment/bug) in the modal
- **Advanced Technical Data Collection**:
  - Automatic capture of page URL, title, and referrer
  - Browser information (user agent, platform, language)
  - Screen resolution and viewport dimensions
  - User timezone and timestamp
  - JavaScript console error tracking (configurable)
- **Quick Collector Settings** in settings page:
  - Enable/disable quick collector
  - Customize button label
  - Show/hide technical details to users
  - Enable/disable console error capture
- **Metadata Display**: Technical details shown in admin dashboard as collapsible section
- **Database Enhancement**: Added `metadata` column to store auto-collected data as JSON

### Changed
- Database version upgraded to 1.1 with automatic migration
- Admin bar now displays feedback collection button when enabled
- Submission form handler now accepts and stores metadata
- Export CSV includes metadata when available

### Technical
- Added `includes/quick-collector.php` for admin bar integration
- Enhanced JavaScript with error capture and data collection
- Added migration function `user_feedback_migrate_to_1_1()`
- Improved CSS for modal and metadata display

## [1.0.0] - 2025-10-16

### Added
- Initial release of User Feedback & Bug Reports plugin
- Dual submission types: Comments/Questions and Bug Reports
- User authentication requirement for submissions
- Admin dashboard with comprehensive submission management
- Email notification system:
  - New submission notifications to admin
  - Reply notifications to users
  - Resolved status notifications to users
- Canned responses system for quick replies
- Detailed status workflow (New, In Progress, Testing, Resolved, Won't Fix)
- Context ID tracking for bug reports
- Shortcode implementation: `[user_feedback]` with type and context_id parameters
- Changelog shortcode: `[feedback_changelog]` for displaying resolved bugs
- WordPress widget for sidebar placement
- Admin filtering and search functionality
- CSV export capability for submissions
- Statistics dashboard showing submission counts by type and status
- Responsive design for mobile and desktop
- Custom database tables for efficient data management
- Comprehensive settings page for configuration
- Complete email template system
- Security features:
  - Nonce verification on all forms
  - Capability checks for admin functions
  - Input sanitization and output escaping
  - Prepared SQL statements
- Uninstall script for clean removal

### Security
- Implemented WordPress security best practices
- All user inputs sanitized
- All outputs escaped
- Database queries use prepared statements
- Admin functions protected with capability checks
- AJAX requests secured with nonces

## [Unreleased]

### Planned Features
- Custom status type creation
- Multiple admin notification recipients
- Email template customization in admin
- Attachment support for submissions
- Submission priority levels
- User notification preferences
- Integration with external bug tracking systems
- REST API endpoints for third-party integrations
- Advanced reporting and analytics
- Submission categories/tags
- Bulk actions for submissions
- Submission templates
- Auto-responses based on keywords
- SLA tracking for response times

