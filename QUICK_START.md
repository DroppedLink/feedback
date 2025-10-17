# User Feedback Plugin - Quick Start Guide

## Installation Complete! ✓

Your User Feedback & Bug Reports plugin has been successfully created and is ready to use.

## Location

**Plugin Directory**: `/plugins/user-feedback/`  
**Distribution Package**: `/dist/user-feedback.zip`

## Activation Steps

1. **If developing locally**: The plugin is already in place at `plugins/user-feedback/`
2. **If installing on a live site**: Upload `dist/user-feedback.zip` via WordPress admin
3. Go to **Plugins** in WordPress admin
4. Find **User Feedback & Bug Reports**
5. Click **Activate**

## Initial Setup (5 minutes)

### Step 1: Configure Settings
1. Navigate to **User Feedback > Settings** in WordPress admin
2. Set your **Admin Notification Email** (where you'll receive new submission alerts)
3. Choose your **Default Status** for new submissions (recommend: "New")
4. Ensure both **Comments/Questions** and **Bug Reports** are enabled
5. Customize form labels if desired
6. Click **Save Settings**

### Step 2: Create Canned Responses (Optional but Recommended)
1. Go to **User Feedback > Canned Responses**
2. Add some common responses, for example:
   - **Title**: "Thank you for reporting"  
     **Content**: "Thank you for reporting this issue. We're looking into it and will update you soon."
   - **Title**: "Already fixed"  
     **Content**: "This issue has been resolved in our latest update. Please clear your cache and try again."
   - **Title**: "Need more info"  
     **Content**: "Thank you for your feedback. Could you please provide more details about when this occurs?"

### Step 3: Add Feedback Form to Your Site

#### Option A: Using Shortcode (Recommended)
Add to any page or post:

**For Comments/Questions:**
```
[user_feedback type="comment"]
```

**For Bug Reports:**
```
[user_feedback type="bug"]
```

**For Bug Reports on Specific Pages:**
```
[user_feedback type="bug" context_id="checkout-page"]
```

#### Option B: Using Widget
1. Go to **Appearance > Widgets**
2. Find **User Feedback Form** widget
3. Drag it to your desired sidebar
4. Configure the settings (title, type, context_id)
5. Save

## Usage Examples

### Example 1: Contact/Feedback Page
Create a page called "Feedback" and add:
```
[user_feedback type="comment"]
```

### Example 2: Bug Report Button
Add to a page with a specific feature:
```
<h3>Found a bug? Let us know!</h3>
[user_feedback type="bug" context_id="payment-system"]
```

### Example 3: Changelog Page
Show recently fixed bugs:
```
<h2>Recent Improvements</h2>
[feedback_changelog limit="15"]
```

### Example 4: Page-Specific Fixes
Show fixes for a specific feature:
```
[feedback_changelog context_id="checkout-page" limit="10"]
```

## Admin Workflow

### When a New Submission Arrives:
1. You'll receive an **email notification**
2. Go to **User Feedback > Dashboard**
3. You'll see the new submission with a **"New"** badge
4. Review the submission details

### To Reply:
1. Click the **"Reply"** button on the submission
2. Either:
   - Select a canned response from the dropdown, OR
   - Type a custom reply
3. Click **"Send Reply"**
4. User receives an email with your response

### To Update Status:
1. Click the **status dropdown** on any submission
2. Select new status (In Progress, Testing, Resolved, Won't Fix)
3. If selecting **"Resolved"**:
   - Add resolution notes (these are sent to the user and shown in changelog)
4. Click **"Update Status"**
5. User receives a notification if marked as resolved

### To Export Data:
1. Apply any filters you want (type, status, search)
2. Click **"Export CSV"** button
3. CSV file downloads with filtered submissions

## Key Features You Asked For

✅ **Users can submit via shortcode or widget** - Implemented with `[user_feedback]` shortcode and widget  
✅ **Requires WordPress login** - Anonymous submissions blocked  
✅ **Quick feedback from admin bar** - One-click button with auto-collected technical data ⭐ NEW  
✅ **Auto-collects browser/page info** - URL, browser, screen size, errors, etc. ⭐ NEW  
✅ **Email link for submissions** - Users receive direct links when you reply  
✅ **Admin page to view/manage** - Full dashboard at User Feedback > Dashboard  
✅ **Canned and custom responses** - Built-in canned response system  
✅ **Bug tracking by page/feature** - Context ID system implemented  
✅ **Reply via email** - Automatic email notifications for replies and resolutions  
✅ **Resolution tracking** - Resolution notes saved and displayed  
✅ **Public changelog** - `[feedback_changelog]` shortcode for displaying fixes  

## Testing the Plugin

### Test as a User:
1. Log in as a regular WordPress user (not admin)
2. Go to a page with the feedback form
3. Submit a test comment or bug report
4. Check if you receive a success message

### Test as Admin:
1. Check your email for the notification
2. Go to **User Feedback > Dashboard**
3. Find the test submission
4. Click **Reply** and send a test response
5. Change status to **Resolved** with some notes
6. Check that the test user receives the emails

### Test the Changelog:
1. Create a page with `[feedback_changelog]`
2. Ensure resolved bugs appear there

### Test the Quick Feedback Collector:
1. Go to **User Feedback > Settings**
2. Enable **Quick Feedback Collector**
3. Save settings
4. Look at the top WordPress admin bar - you should see a "Quick Feedback" button
5. Click it - a modal should appear
6. Fill in the form and submit
7. Check the admin dashboard - your submission should include "Technical Details" section
8. Expand it to see all the auto-collected data (URL, browser, screen size, etc.)

## Troubleshooting

### "You must be logged in to submit feedback"
- This is expected behavior. Users MUST be logged in to submit
- Make sure you have a login system on your site
- Consider adding a link to your registration page

### Emails not arriving?
- Go to **Settings > General** and verify site email
- Consider installing **WP Mail SMTP** plugin for better email delivery
- Check spam folders

### Widget not showing?
- Make sure users are logged in (they'll see a login message if not)
- Check that the submission type is enabled in settings
- Verify widget is added to an active sidebar

## Next Steps

1. ✅ **Test the plugin** with a few submissions
2. ✅ **Create canned responses** for common scenarios
3. ✅ **Add the shortcode** to your key pages
4. ✅ **Customize the settings** to match your workflow
5. 🔜 **Announce to your users** that they can now submit feedback

## Support & Customization

All files follow the **Plugin Programmer's Guide** standards:

- **Security**: All inputs sanitized, outputs escaped, nonces verified
- **Performance**: Database indexed, assets load conditionally
- **Extensibility**: Hooks ready for future enhancements
- **Documentation**: Comprehensive inline comments

### File Structure
```
plugins/user-feedback/
├── user-feedback.php           # Main plugin file
├── includes/                   # All PHP functionality
│   ├── database.php
│   ├── settings.php
│   ├── submissions-page.php
│   ├── canned-responses.php
│   ├── ajax-handler.php
│   ├── shortcode.php
│   ├── widget.php
│   └── email-handler.php
├── assets/
│   ├── css/style.css          # All styling
│   └── js/script.js           # All JavaScript
├── uninstall.php              # Cleanup script
├── README.md                  # Full documentation
├── CHANGELOG.md               # Version history
└── QUICK_START.md             # This file
```

## You're All Set! 🚀

Your plugin is production-ready and follows WordPress best practices. Users can now submit feedback and bug reports, and you can manage them all from a single dashboard with email notifications and canned responses.

**Need help?** Review the full README.md for detailed documentation of all features.

