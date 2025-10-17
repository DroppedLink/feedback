<?php
/**
 * Canned responses management for User Feedback plugin
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Canned responses page
 */
function user_feedback_canned_responses_page() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    // Handle form submissions
    if (isset($_POST['add_canned_response'])) {
        check_admin_referer('user_feedback_add_canned_response');
        
        $title = sanitize_text_field($_POST['title']);
        $content = sanitize_textarea_field($_POST['content']);
        
        if (!empty($title) && !empty($content)) {
            $result = user_feedback_insert_canned_response(array(
                'title' => $title,
                'content' => $content
            ));
            
            if ($result) {
                echo '<div class="notice notice-success is-dismissible"><p>Canned response added successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Failed to add canned response.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Title and content are required.</p></div>';
        }
    }
    
    if (isset($_POST['edit_canned_response'])) {
        check_admin_referer('user_feedback_edit_canned_response');
        
        $id = intval($_POST['response_id']);
        $title = sanitize_text_field($_POST['title']);
        $content = sanitize_textarea_field($_POST['content']);
        
        if (!empty($title) && !empty($content)) {
            $result = user_feedback_update_canned_response($id, array(
                'title' => $title,
                'content' => $content
            ));
            
            if ($result !== false) {
                echo '<div class="notice notice-success is-dismissible"><p>Canned response updated successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Failed to update canned response.</p></div>';
            }
        }
    }
    
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        check_admin_referer('user_feedback_delete_canned_response_' . $_GET['id']);
        
        $id = intval($_GET['id']);
        $result = user_feedback_delete_canned_response($id);
        
        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p>Canned response deleted successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Failed to delete canned response.</p></div>';
        }
    }
    
    // Get all canned responses
    $canned_responses = user_feedback_get_canned_responses();
    
    // Check if editing
    $editing = false;
    $edit_response = null;
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $editing = true;
        $edit_response = user_feedback_get_canned_response(intval($_GET['id']));
    }
    ?>
    <div class="wrap">
        <h1>Canned Responses</h1>
        <p>Create reusable responses for common feedback scenarios.</p>
        
        <!-- Add/Edit Form -->
        <div class="user-feedback-canned-form">
            <h2><?php echo $editing ? 'Edit' : 'Add New'; ?> Canned Response</h2>
            
            <form method="post" action="">
                <?php
                if ($editing) {
                    wp_nonce_field('user_feedback_edit_canned_response');
                    echo '<input type="hidden" name="response_id" value="' . esc_attr($edit_response->id) . '">';
                } else {
                    wp_nonce_field('user_feedback_add_canned_response');
                }
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="title">Title *</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="title" 
                                   name="title" 
                                   value="<?php echo $editing ? esc_attr($edit_response->title) : ''; ?>" 
                                   class="regular-text" 
                                   required>
                            <p class="description">A short title to identify this response</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="content">Content *</label>
                        </th>
                        <td>
                            <textarea id="content" 
                                      name="content" 
                                      rows="8" 
                                      class="large-text" 
                                      required><?php echo $editing ? esc_textarea($edit_response->content) : ''; ?></textarea>
                            <p class="description">The response text that will be sent to users</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" 
                           name="<?php echo $editing ? 'edit_canned_response' : 'add_canned_response'; ?>" 
                           class="button button-primary" 
                           value="<?php echo $editing ? 'Update Response' : 'Add Response'; ?>">
                    <?php if ($editing): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=user-feedback-responses')); ?>" class="button">Cancel</a>
                    <?php endif; ?>
                </p>
            </form>
        </div>
        
        <!-- List of Canned Responses -->
        <div class="user-feedback-canned-list">
            <h2>Existing Canned Responses</h2>
            
            <?php if (empty($canned_responses)): ?>
                <p>No canned responses yet. Add your first one above!</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Content Preview</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($canned_responses as $response): ?>
                            <tr>
                                <td><strong><?php echo esc_html($response->title); ?></strong></td>
                                <td>
                                    <?php
                                    $preview = strlen($response->content) > 100 
                                        ? substr($response->content, 0, 100) . '...' 
                                        : $response->content;
                                    echo esc_html($preview);
                                    ?>
                                </td>
                                <td><?php echo esc_html(date('M d, Y', strtotime($response->created_at))); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=user-feedback-responses&action=edit&id=' . $response->id)); ?>" 
                                       class="button button-small">Edit</a>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=user-feedback-responses&action=delete&id=' . $response->id), 'user_feedback_delete_canned_response_' . $response->id)); ?>" 
                                       class="button button-small button-link-delete"
                                       onclick="return confirm('Are you sure you want to delete this canned response?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

