<?php
/**
 * Form Builder Admin Interface for User Feedback plugin
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Add Form Builder to admin menu (priority 20 = 2nd position after Submissions)
 */
add_action('admin_menu', 'userfeedback_add_form_builder_menu', 20);
function userfeedback_add_form_builder_menu() {
    add_submenu_page(
        'user-feedback',
        'Form Builder',
        'Forms',
        'manage_options',
        'user-feedback-form-builder',
        'userfeedback_form_builder_page'
    );
}

/**
 * Main Form Builder page with tabs
 */
function userfeedback_form_builder_page() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    // Get active tab
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'categories';
    ?>
    <div class="wrap">
        <h1>Form Builder</h1>
        
        <nav class="nav-tab-wrapper">
            <a href="?page=user-feedback-form-builder&tab=categories" 
               class="nav-tab <?php echo $active_tab === 'categories' ? 'nav-tab-active' : ''; ?>">
                Categories
            </a>
            <a href="?page=user-feedback-form-builder&tab=forms" 
               class="nav-tab <?php echo $active_tab === 'forms' ? 'nav-tab-active' : ''; ?>">
                Forms
            </a>
        </nav>
        
        <div class="userfeedback-tab-content">
            <?php
            if ($active_tab === 'categories') {
                userfeedback_render_categories_tab();
            } elseif ($active_tab === 'forms') {
                userfeedback_render_forms_tab();
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Render Categories Tab
 */
function userfeedback_render_categories_tab() {
    $categories = userfeedback_get_categories();
    $edit_mode = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
    $category_to_edit = null;
    
    if ($edit_mode) {
        $category_to_edit = userfeedback_get_category(intval($_GET['id']));
    }
    ?>
    <div class="userfeedback-form-builder-section">
        <div class="userfeedback-fb-sidebar">
            <h2>Categories</h2>
            <p>Organize your forms into categories. Each category can contain multiple forms.</p>
            
            <div class="userfeedback-categories-list">
                <?php if (empty($categories)): ?>
                    <p class="userfeedback-empty-message">No categories yet. Create your first one!</p>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <div class="userfeedback-category-item" data-id="<?php echo esc_attr($category->id); ?>">
                            <div class="userfeedback-category-info">
                                <strong><?php echo esc_html($category->name); ?></strong>
                                <?php if (!empty($category->description)): ?>
                                    <p class="description"><?php echo esc_html($category->description); ?></p>
                                <?php endif; ?>
                                <?php
                                $form_count = count(userfeedback_get_forms(array('category_id' => $category->id)));
                                ?>
                                <span class="userfeedback-badge"><?php echo esc_html($form_count); ?> forms</span>
                            </div>
                            <div class="userfeedback-category-actions">
                                <a href="?page=user-feedback-form-builder&tab=categories&action=edit&id=<?php echo esc_attr($category->id); ?>" 
                                   class="button button-small">Edit</a>
                                <button class="button button-small userfeedback-delete-category" 
                                        data-id="<?php echo esc_attr($category->id); ?>"
                                        <?php echo $form_count > 0 ? 'disabled title="Cannot delete category with forms"' : ''; ?>>
                                    Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="userfeedback-fb-editor">
            <h2><?php echo $edit_mode ? 'Edit Category' : 'Add New Category'; ?></h2>
            
            <form id="userfeedback-category-form" class="userfeedback-editor-form">
                <?php if ($edit_mode && $category_to_edit): ?>
                    <input type="hidden" name="category_id" value="<?php echo esc_attr($category_to_edit->id); ?>">
                <?php endif; ?>
                
                <div class="userfeedback-form-field">
                    <label for="category-name">Category Name *</label>
                    <input type="text" 
                           id="category-name" 
                           name="name" 
                           class="regular-text" 
                           value="<?php echo $edit_mode && $category_to_edit ? esc_attr($category_to_edit->name) : ''; ?>" 
                           required>
                    <p class="description">e.g., "Agents", "Bug Reports", "Feature Requests"</p>
                </div>
                
                <div class="userfeedback-form-field">
                    <label for="category-slug">Slug *</label>
                    <input type="text" 
                           id="category-slug" 
                           name="slug" 
                           class="regular-text" 
                           value="<?php echo $edit_mode && $category_to_edit ? esc_attr($category_to_edit->slug) : ''; ?>" 
                           pattern="[a-z0-9-]+" 
                           required>
                    <p class="description">URL-friendly version (lowercase, hyphens only)</p>
                </div>
                
                <div class="userfeedback-form-field">
                    <label for="category-description">Description</label>
                    <textarea id="category-description" 
                              name="description" 
                              class="large-text" 
                              rows="3"><?php echo $edit_mode && $category_to_edit ? esc_textarea($category_to_edit->description) : ''; ?></textarea>
                    <p class="description">Optional description for internal reference</p>
                </div>
                
                <div class="userfeedback-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php echo $edit_mode ? 'Update Category' : 'Create Category'; ?>
                    </button>
                    <?php if ($edit_mode): ?>
                        <a href="?page=user-feedback-form-builder&tab=categories" class="button">Cancel</a>
                    <?php endif; ?>
                </div>
                
                <div class="userfeedback-form-message"></div>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Render Forms Tab
 */
function userfeedback_render_forms_tab() {
    $categories = userfeedback_get_categories();
    $selected_category = isset($_GET['category']) ? intval($_GET['category']) : '';
    $edit_mode = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
    $form_to_edit = null;
    
    if ($edit_mode) {
        $form_to_edit = userfeedback_get_form(intval($_GET['id']));
        if ($form_to_edit) {
            $selected_category = $form_to_edit->category_id;
        }
    }
    
    $forms = array();
    if ($selected_category) {
        $forms = userfeedback_get_forms(array('category_id' => $selected_category));
    }
    ?>
    <div class="userfeedback-form-builder-section">
        <div class="userfeedback-fb-sidebar">
            <h2>Forms</h2>
            
            <?php if (empty($categories)): ?>
                <div class="userfeedback-notice userfeedback-notice-warning">
                    <p>You need to create at least one category first.</p>
                    <a href="?page=user-feedback-form-builder&tab=categories" class="button">Go to Categories</a>
                </div>
            <?php else: ?>
                <div class="userfeedback-form-field">
                    <label for="filter-category">Filter by Category:</label>
                    <select id="filter-category" class="regular-text">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo esc_attr($category->id); ?>" 
                                    <?php selected($selected_category, $category->id); ?>>
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="userfeedback-forms-list">
                    <?php if (empty($forms)): ?>
                        <p class="userfeedback-empty-message">
                            <?php echo $selected_category ? 'No forms in this category yet.' : 'Select a category to view forms.'; ?>
                        </p>
                    <?php else: ?>
                        <?php foreach ($forms as $form): ?>
                            <div class="userfeedback-form-item <?php echo $form->is_active ? '' : 'inactive'; ?>" 
                                 data-id="<?php echo esc_attr($form->id); ?>">
                                <div class="userfeedback-form-info">
                                    <strong><?php echo esc_html($form->name); ?></strong>
                                    <div class="userfeedback-form-meta">
                                        <code class="userfeedback-shortcode-display">[userfeedback form="<strong><?php echo esc_attr($form->shortcode); ?></strong>"]</code>
                                        <button type="button" 
                                                class="button button-small userfeedback-copy-shortcode" 
                                                data-shortcode='[userfeedback form="<?php echo esc_attr($form->shortcode); ?>"]'>
                                            Copy
                                        </button>
                                        <?php if (!$form->is_active): ?>
                                            <span class="userfeedback-badge inactive">Inactive</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($form->description)): ?>
                                        <p class="description"><?php echo esc_html($form->description); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="userfeedback-form-actions">
                                    <a href="?page=user-feedback-form-builder&tab=forms&action=edit&id=<?php echo esc_attr($form->id); ?>" 
                                       class="button button-small">Edit</a>
                                    <button class="button button-small userfeedback-delete-form" 
                                            data-id="<?php echo esc_attr($form->id); ?>">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="userfeedback-fb-editor">
            <?php if (!empty($categories)): ?>
                <h2><?php echo $edit_mode ? 'Edit Form' : 'Add New Form'; ?></h2>
                
                <form id="userfeedback-form-editor" class="userfeedback-editor-form">
                    <?php if ($edit_mode && $form_to_edit): ?>
                        <input type="hidden" name="form_id" value="<?php echo esc_attr($form_to_edit->id); ?>">
                    <?php endif; ?>
                    
                    <div class="userfeedback-form-field">
                        <label for="form-category">Category *</label>
                        <select id="form-category" name="category_id" class="regular-text" required>
                            <option value="">Select a category...</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category->id); ?>" 
                                        <?php echo ($edit_mode && $form_to_edit && $form_to_edit->category_id == $category->id) ? 'selected' : ''; ?>>
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="userfeedback-form-field">
                        <label for="form-name">Form Name *</label>
                        <input type="text" 
                               id="form-name" 
                               name="name" 
                               class="regular-text" 
                               value="<?php echo $edit_mode && $form_to_edit ? esc_attr($form_to_edit->name) : ''; ?>" 
                               required>
                        <p class="description">e.g., "Zabbix", "CrowdStrike", "General Feedback"</p>
                    </div>
                    
                    <div class="userfeedback-form-field">
                        <label for="form-shortcode">Shortcode *</label>
                        <input type="text" 
                               id="form-shortcode" 
                               name="shortcode" 
                               class="regular-text" 
                               value="<?php echo $edit_mode && $form_to_edit ? esc_attr($form_to_edit->shortcode) : ''; ?>" 
                               pattern="[a-z0-9_-]+" 
                               required>
                        <p class="description">Use in posts as: <code>[userfeedback form="<span id="shortcode-preview">your-form</span>"]</code></p>
                    </div>
                    
                    <div class="userfeedback-form-field">
                        <label for="form-description">Description</label>
                        <textarea id="form-description" 
                                  name="description" 
                                  class="large-text" 
                                  rows="2"><?php echo $edit_mode && $form_to_edit ? esc_textarea($form_to_edit->description) : ''; ?></textarea>
                    </div>
                    
                    <div class="userfeedback-form-field">
                        <label>
                            <input type="checkbox" 
                                   name="is_active" 
                                   value="1" 
                                   <?php echo (!$edit_mode || ($form_to_edit && $form_to_edit->is_active)) ? 'checked' : ''; ?>>
                            Active (users can submit)
                        </label>
                    </div>
                    
                    <hr>
                    
                    <h3>Form Fields Configuration</h3>
                    <p class="description">Build your form by adding fields below. Users will see these fields when they submit feedback.</p>
                    
                    <div id="userfeedback-fields-container">
                        <!-- Fields will be rendered here by JavaScript -->
                    </div>
                    
                    <div class="userfeedback-add-field-buttons">
                        <button type="button" class="button" data-field-type="select">
                            + Add Dropdown
                        </button>
                        <button type="button" class="button" data-field-type="text">
                            + Add Text Field
                        </button>
                        <button type="button" class="button" data-field-type="textarea">
                            + Add Text Area
                        </button>
                        <button type="button" class="button" data-field-type="file">
                            + Add File Upload
                        </button>
                    </div>
                    
                    <input type="hidden" name="field_config" id="field-config-json" value="">
                    
                    <div class="userfeedback-form-actions">
                        <button type="submit" class="button button-primary button-large">
                            <?php echo $edit_mode ? 'Update Form' : 'Create Form'; ?>
                        </button>
                        <?php if ($edit_mode): ?>
                            <a href="?page=user-feedback-form-builder&tab=forms<?php echo $selected_category ? '&category=' . $selected_category : ''; ?>" 
                               class="button">Cancel</a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="userfeedback-form-message"></div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($edit_mode && $form_to_edit && !empty($form_to_edit->field_config)): ?>
        <script type="text/javascript">
            var userfeedbackInitialFields = <?php echo wp_json_encode(json_decode($form_to_edit->field_config)); ?>;
        </script>
    <?php endif; ?>
    <?php
}

