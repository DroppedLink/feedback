# Navigation Menu Link Guide

## Overview
You can now add a feedback modal trigger link to your WordPress navigation menus. When visitors click this link, the feedback modal will open, allowing them to submit feedback or report bugs.

## How to Add the Feedback Link to Your Menu

### Step 1: Go to Appearance > Menus
1. Log in to your WordPress admin dashboard
2. Navigate to **Appearance > Menus**
3. Select the menu you want to edit (or create a new one)

### Step 2: Add the Feedback Link Using Custom Links
1. Scroll down to the **"Custom Links"** section in the left sidebar
2. If you don't see it, click **"Screen Options"** at the top right and make sure "Custom Links" is checked
3. In the **URL** field, enter: `#user-feedback-modal`
4. In the **Link Text** field, enter: `Feedback` (or any text you prefer like "Give Feedback", "Report a Bug", "Help")
5. Click **"Add to Menu"**

### Step 3: Customize the Link (Optional)
1. The link will appear in your menu structure on the right
2. You can drag it to reorder it in your menu
3. Click the arrow on the right to expand the menu item settings
4. You can change the **Navigation Label** if needed
5. **Optional for styling:** Add CSS class `user-feedback-menu-trigger` in the CSS Classes field
   - If you don't see CSS Classes, click **"Screen Options"** at the top and enable it
6. Click **"Save Menu"**

### Step 4: Test the Link
1. Visit your website's frontend
2. Look for the new menu item you just added
3. Click it - the feedback modal should open
4. Note: Only logged-in users can submit feedback

## Settings

### Enable/Disable Menu Link Feature
Go to **Feedback > Settings** in your WordPress admin:
- Find the **"Enable Navigation Menu Link"** option under Quick Feedback Collector settings
- Check/uncheck to enable/disable this feature
- This controls whether the menu link can be added and whether it functions on the frontend

## How It Works

### For Logged-in Users
- Clicking the menu link opens the feedback modal
- Users can submit comments, questions, or bug reports
- All standard feedback features are available (attachments, technical details, etc.)

### For Logged-out Users
- The menu link will still appear (controlled by your menu visibility settings)
- But clicking it will do nothing since only logged-in users can submit feedback
- Consider using WordPress menu item visibility plugins if you want to hide the link for logged-out users

## Styling the Menu Link

You can style the feedback menu link with custom CSS. The link has these classes:
- `.user-feedback-menu-trigger` - on the `<li>` element
- The link href is `#user-feedback-modal`

Example CSS to add icon or styling:
```css
.user-feedback-menu-trigger a::before {
    content: "💬 ";
}

.user-feedback-menu-trigger a:hover {
    color: #00b9eb;
}
```

## Comparison: Menu Link vs Admin Bar Button

### Navigation Menu Link (New Feature)
- ✅ Visible to all visitors in your site navigation
- ✅ Can be placed anywhere in your menus
- ✅ Customizable label text
- ✅ Better visibility for public-facing feedback
- ⚠️ Still requires login to submit

### Admin Bar Button (Existing Feature)
- ✅ Quick access for logged-in users
- ✅ Available on every page
- ✅ Technical metadata capture
- ⚠️ Only visible when logged in
- ⚠️ Not visible on frontend to non-admin users (depending on settings)

## Troubleshooting

### The link doesn't appear in my menu
- Make sure you clicked "Add to Menu" after entering the URL and Link Text
- Verify you saved the menu by clicking "Save Menu"
- Clear your site cache if using a caching plugin

### Clicking the link does nothing
- Ensure you're logged in (only logged-in users can access the modal)
- Make sure either "Quick Feedback Collector" or "Navigation Menu Link" is enabled in settings
- Check browser console for JavaScript errors

### The modal doesn't open
- Verify that JavaScript is not being blocked
- Check that your theme properly includes `wp_footer()` in footer.php
- Ensure there are no JavaScript conflicts with other plugins

## Technical Details

The menu link feature:
- Shares the same modal as the Quick Feedback Collector
- Automatically includes all enabled features (attachments, technical details, etc.)
- Works on both frontend and admin pages
- Uses the same AJAX handlers and email notifications
- Integrates seamlessly with your existing feedback workflow

## Support

For issues or questions about this feature, please refer to the main plugin documentation or contact support.

