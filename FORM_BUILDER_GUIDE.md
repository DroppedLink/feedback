# Form Builder System - Implementation Guide

## Overview

The User Feedback plugin has been completely upgraded to version 2.0.0 with a powerful new Form Builder system. You can now create unlimited custom feedback forms with dynamic fields, organized into hierarchical categories.

## What's New

### Dynamic Form Builder
- **Create Custom Forms**: Build forms with any combination of fields you need
- **Hierarchical Organization**: Group forms into categories (e.g., "Agents" category containing Zabbix, CrowdStrike, etc.)
- **Field Types**: Dropdown/Select, Text Input, Text Area, File Upload
- **Unique Shortcodes**: Each form gets its own shortcode for easy embedding

### Database Changes
- **New Tables**: 
  - `userfeedback_form_categories` - Stores categories
  - `userfeedback_custom_forms` - Stores form configurations
- **Updated Tables**:
  - `userfeedback_submissions` - Now supports `form_id` and `form_data` columns
- **Automatic Migration**: Existing data preserved with legacy support

## How to Use the Form Builder

### 1. Access the Form Builder

Navigate to **User Feedback > Form Builder** in your WordPress admin menu.

### 2. Create Categories

Categories help you organize forms into logical groups.

**Steps:**
1. Go to the **Categories** tab
2. Fill in:
   - **Category Name**: e.g., "Agents"
   - **Slug**: Auto-generated or customize (e.g., "agents")
   - **Description**: Optional internal reference
3. Click **Create Category**

**Example Categories:**
- Agents
- Bug Reports
- Feature Requests
- IT Support

### 3. Create Forms

Forms are the actual feedback collection tools.

**Steps:**
1. Go to the **Forms** tab
2. Select a category from the dropdown
3. Fill in:
   - **Category**: Choose the parent category
   - **Form Name**: e.g., "Zabbix"
   - **Shortcode**: Auto-generated (e.g., "zabbix")
   - **Description**: Optional helper text
   - **Active**: Check to allow submissions
4. Configure fields (see below)
5. Click **Create Form**

### 4. Configure Form Fields

Use the field builder to add fields to your form:

#### Available Field Types

**Dropdown/Select Field**
- Perfect for: Subject selection, categories, predefined options
- Configuration: Add/remove options, set as required
- Example: Subject dropdown with "upgrade", "config change", "report a bug"

**Text Field**
- Perfect for: Short answers, version numbers, names
- Configuration: Label, placeholder, required flag
- Example: "Agent Version" field

**Text Area**
- Perfect for: Descriptions, comments, detailed feedback
- Configuration: Label, placeholder, rows, required flag
- Example: "Additional Comments" field

**File Upload**
- Perfect for: Screenshots, logs, documentation
- Configuration: Label, required flag
- Note: Respects global attachment settings

#### Field Configuration

For each field you can set:
- **Field Name**: Internal identifier (e.g., "subject", "version")
- **Label**: What users see (e.g., "Subject", "Agent Version")
- **Required**: Whether the field is mandatory
- **Field-specific settings**: Options for dropdowns, rows for textareas, etc.

#### Field Management
- **Reorder**: Use ↑ and ↓ buttons to change field order
- **Remove**: Delete unwanted fields
- **Add More**: Click the buttons to add additional fields

### 5. Example: Creating the "Agents" System

Let's create the exact system you described:

#### Step 1: Create "Agents" Category
```
Name: Agents
Slug: agents
Description: IT Agent feedback and issues
```

#### Step 2: Create Forms for Each Agent

**Zabbix Form:**
```
Category: Agents
Name: Zabbix
Shortcode: zabbix

Fields:
1. Dropdown - "Subject"
   Options: upgrade, config change, report a bug, process for new agent
   
2. Text Area - "Description"
   Label: Describe the issue or request
   Rows: 6
   Required: Yes
   
3. Text - "Agent Version"
   Label: Agent Version
   Placeholder: e.g., 6.0.15
   
4. Text Area - "Comments"
   Label: Additional Comments
   Rows: 4
   
5. File Upload - "Attachment"
   Label: Upload agent or related file
```

**Repeat for:**
- CrowdStrike
- Netbackup
- Splunk
- Flexera
- Qualys
- Centrify
- Illumio
- New Agent (generic form)

### 6. Use Forms on Your Site

#### Embed with Shortcode

Place the shortcode anywhere on your site:

```
[userfeedback form="zabbix"]
```

Or with context:

```
[userfeedback form="crowdstrike" context_id="prod-server-01"]
```

#### Display Changelog

Show resolved issues for a specific form:

```
[feedback_changelog form="zabbix" limit="10"]
```

Or for an entire category:

```
[feedback_changelog category="agents" limit="20"]
```

## Admin Dashboard Updates

### Viewing Submissions

The submissions dashboard now includes:

**New Filters:**
- **Category**: Filter by category (e.g., "Agents")
- **Form**: Filter by specific form (e.g., "Zabbix")
- Category-aware hierarchy

**Submission Display:**
- Shows category and form name as badges
- Displays all dynamic field responses
- Shows "Form Responses" section with all submitted data

### Managing Submissions

All existing features work with dynamic forms:
- Reply to submissions
- Update status
- Add resolution notes
- Export to CSV (includes form info)

## Migration & Compatibility

### Automatic Migration

On activation, the plugin automatically:
1. Creates new database tables
2. Adds new columns to existing tables
3. Renames tables with proper prefixes (`userfeedback_`)
4. Preserves all existing data

### Legacy Support

The old shortcode format still works:

```
[user_feedback type="comment"]
[user_feedback type="bug"]
```

These will continue to function but display as "Legacy" in the dashboard.

### Recommended Transition

1. Create categories matching your current needs
2. Create new forms to replace hardcoded types
3. Update pages to use new shortcodes
4. Monitor submissions in the updated dashboard

## Technical Details

### Naming Conventions

All new functionality uses `userfeedback_` prefix for:
- Table names: `userfeedback_form_categories`, `userfeedback_custom_forms`
- Function names: `userfeedback_get_forms()`, `userfeedback_insert_category()`
- CSS classes: `.userfeedback-form-container`
- AJAX actions: `userfeedback_save_form`

### Field Configuration Storage

Forms store field configurations as JSON:

```json
{
  "fields": [
    {
      "type": "select",
      "name": "subject",
      "label": "Subject",
      "required": true,
      "options": ["upgrade", "config change", "report a bug"]
    },
    {
      "type": "textarea",
      "name": "description",
      "label": "Description",
      "required": true,
      "rows": 6
    }
  ]
}
```

### Submission Data Storage

Dynamic form responses are stored in the `form_data` column as JSON:

```json
{
  "subject": "upgrade",
  "description": "Need to upgrade Zabbix to version 7.0",
  "version": "6.0.15",
  "comments": "Please schedule for after hours"
}
```

## Best Practices

### Naming Conventions

- **Categories**: Use clear, broad groupings (Agents, Bug Reports, etc.)
- **Forms**: Specific tools or purposes (Zabbix, Login Issues, etc.)
- **Fields**: Use descriptive labels users will understand

### Field Design

- **Always include a description field**: Gives context to submissions
- **Use dropdowns for predefined options**: Easier to filter and analyze
- **Mark critical fields as required**: Ensures you get necessary information
- **Add version/identifier fields**: Helps with troubleshooting

### Organization Tips

- Group related forms in categories
- Use consistent naming across similar forms
- Include helpful descriptions in forms
- Test forms before deploying to pages

## Troubleshooting

### Forms not showing?

1. Check that the form is set to **Active**
2. Verify the shortcode matches exactly (case-sensitive)
3. Ensure user is logged in (required for submissions)

### Fields not saving?

1. Check browser console for JavaScript errors
2. Verify all required field settings are filled
3. Try removing and re-adding the problematic field

### Submissions not appearing?

1. Check the correct form filter in dashboard
2. Verify form_id is being submitted (check browser network tab)
3. Ensure database migration completed successfully

## Support

For issues or questions:
1. Check the WordPress debug log
2. Review browser console for errors
3. Verify database tables were created correctly

## Version History

**2.0.0** - Major Update
- Added Form Builder system
- Implemented hierarchical categories
- Created dynamic field configuration
- Updated database schema
- Enhanced admin dashboard
- Maintained backward compatibility

## Next Steps

1. **Create your first category**
2. **Build a test form** with a few fields
3. **Embed it on a page** and test submissions
4. **Review submissions** in the updated dashboard
5. **Scale up** by adding more categories and forms

The system is now fully functional and ready for production use. Create as many categories and forms as you need!

