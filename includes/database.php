<?php
/**
 * Database management for User Feedback plugin
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Create database tables
 */
function user_feedback_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Form Categories table (NEW)
    $categories_table = $wpdb->prefix . 'userfeedback_form_categories';
    $sql_categories = "CREATE TABLE $categories_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        description text DEFAULT NULL,
        sort_order int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY sort_order (sort_order)
    ) $charset_collate;";
    
    // Custom Forms table (NEW)
    $forms_table = $wpdb->prefix . 'userfeedback_custom_forms';
    $sql_forms = "CREATE TABLE $forms_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        category_id bigint(20) NOT NULL,
        name varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        description text DEFAULT NULL,
        shortcode varchar(100) NOT NULL,
        field_config longtext DEFAULT NULL,
        sort_order int(11) DEFAULT 0,
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        UNIQUE KEY shortcode (shortcode),
        KEY category_id (category_id),
        KEY is_active (is_active),
        KEY sort_order (sort_order)
    ) $charset_collate;";
    
    // Submissions table (UPDATED with form_id support)
    $submissions_table = $wpdb->prefix . 'userfeedback_submissions';
    $sql_submissions = "CREATE TABLE $submissions_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        form_id bigint(20) DEFAULT NULL,
        type varchar(20) DEFAULT NULL,
        context_id varchar(100) DEFAULT NULL,
        subject varchar(255) NOT NULL,
        message text NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'new',
        admin_reply text DEFAULT NULL,
        resolution_notes text DEFAULT NULL,
        metadata text DEFAULT NULL,
        form_data longtext DEFAULT NULL,
        attachment_id bigint(20) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        resolved_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY form_id (form_id),
        KEY type (type),
        KEY status (status),
        KEY context_id (context_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    // Canned responses table
    $responses_table = $wpdb->prefix . 'userfeedback_canned_responses';
    $sql_responses = "CREATE TABLE $responses_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        content text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_categories);
    dbDelta($sql_forms);
    dbDelta($sql_submissions);
    dbDelta($sql_responses);
    
    // Store database version
    add_option('userfeedback_db_version', '2.0');
}

/**
 * Check and upgrade database if needed
 */
function user_feedback_check_db_version() {
    $current_version = get_option('userfeedback_db_version', get_option('user_feedback_db_version', '0'));
    
    if (version_compare($current_version, '1.0', '<')) {
        user_feedback_create_tables();
        update_option('userfeedback_db_version', '1.0');
    }
    
    // Migration to 1.1: Add metadata column
    if (version_compare($current_version, '1.1', '<')) {
        user_feedback_migrate_to_1_1();
        update_option('userfeedback_db_version', '1.1');
    }
    
    // Migration to 1.2: Add attachment_id column
    if (version_compare($current_version, '1.2', '<')) {
        user_feedback_migrate_to_1_2();
        update_option('userfeedback_db_version', '1.2');
    }
    
    // Migration to 2.0: Form builder system
    if (version_compare($current_version, '2.0', '<')) {
        userfeedback_migrate_to_2_0();
        update_option('userfeedback_db_version', '2.0');
    }
}

/**
 * Migration to version 1.1: Add metadata column
 */
function user_feedback_migrate_to_1_1() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_feedback_submissions';
    
    // Check if metadata column exists
    $column_exists = $wpdb->get_results($wpdb->prepare(
        "SHOW COLUMNS FROM $table_name LIKE %s",
        'metadata'
    ));
    
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN metadata text DEFAULT NULL AFTER resolution_notes");
    }
}

/**
 * Migration to version 1.2: Add attachment_id column
 */
function user_feedback_migrate_to_1_2() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_feedback_submissions';
    
    // Check if attachment_id column exists
    $column_exists = $wpdb->get_results($wpdb->prepare(
        "SHOW COLUMNS FROM $table_name LIKE %s",
        'attachment_id'
    ));
    
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN attachment_id bigint(20) DEFAULT NULL AFTER metadata");
    }
}

/**
 * Migration to version 2.0: Form builder system
 */
function userfeedback_migrate_to_2_0() {
    global $wpdb;
    
    // Rename old tables to new naming convention
    $old_submissions = $wpdb->prefix . 'user_feedback_submissions';
    $new_submissions = $wpdb->prefix . 'userfeedback_submissions';
    $old_responses = $wpdb->prefix . 'user_feedback_canned_responses';
    $new_responses = $wpdb->prefix . 'userfeedback_canned_responses';
    
    // Check if old tables exist and rename them
    $old_sub_exists = $wpdb->get_var("SHOW TABLES LIKE '$old_submissions'");
    if ($old_sub_exists && $old_sub_exists === $old_submissions) {
        $wpdb->query("RENAME TABLE $old_submissions TO $new_submissions");
    }
    
    $old_resp_exists = $wpdb->get_var("SHOW TABLES LIKE '$old_responses'");
    if ($old_resp_exists && $old_resp_exists === $old_responses) {
        $wpdb->query("RENAME TABLE $old_responses TO $new_responses");
    }
    
    // Add form_id column to submissions table
    $column_exists = $wpdb->get_results($wpdb->prepare(
        "SHOW COLUMNS FROM $new_submissions LIKE %s",
        'form_id'
    ));
    
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE $new_submissions ADD COLUMN form_id bigint(20) DEFAULT NULL AFTER user_id");
        $wpdb->query("ALTER TABLE $new_submissions ADD KEY form_id (form_id)");
    }
    
    // Add form_data column for storing dynamic field responses
    $form_data_exists = $wpdb->get_results($wpdb->prepare(
        "SHOW COLUMNS FROM $new_submissions LIKE %s",
        'form_data'
    ));
    
    if (empty($form_data_exists)) {
        $wpdb->query("ALTER TABLE $new_submissions ADD COLUMN form_data longtext DEFAULT NULL AFTER metadata");
    }
    
    // Create new form categories and custom forms tables
    user_feedback_create_tables();
}

/**
 * Get submissions with filters
 */
function user_feedback_get_submissions($args = array()) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_submissions';
    
    $defaults = array(
        'type' => '',
        'form_id' => '',
        'category_id' => '',
        'status' => '',
        'user_id' => '',
        'context_id' => '',
        'search' => '',
        'orderby' => 'created_at',
        'order' => 'DESC',
        'limit' => 50,
        'offset' => 0
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $where = array('1=1');
    $where_values = array();
    
    // Specific form filter (highest priority)
    if (!empty($args['form_id'])) {
        $where[] = 'form_id = %d';
        $where_values[] = $args['form_id'];
    }
    // Category filter (shows all forms in that category)
    elseif (!empty($args['category_id'])) {
        // Get all forms in this category
        $forms = userfeedback_get_forms(array('category_id' => $args['category_id']));
        if (!empty($forms)) {
            $form_ids = wp_list_pluck($forms, 'id');
            $placeholders = implode(',', array_fill(0, count($form_ids), '%d'));
            $where[] = "form_id IN ($placeholders)";
            $where_values = array_merge($where_values, $form_ids);
        } else {
            // No forms in category, but might have legacy submissions
            // Only show legacy type submissions for this category (if applicable)
            $where[] = '(form_id IS NULL OR form_id = 0)';
        }
    }
    
    // Legacy support for type filter (works independently or with category/form)
    if (!empty($args['type'])) {
        $where[] = 'type = %s';
        $where_values[] = $args['type'];
    }
    
    if (!empty($args['status'])) {
        $where[] = 'status = %s';
        $where_values[] = $args['status'];
    }
    
    if (!empty($args['user_id'])) {
        $where[] = 'user_id = %d';
        $where_values[] = $args['user_id'];
    }
    
    if (!empty($args['context_id'])) {
        $where[] = 'context_id = %s';
        $where_values[] = $args['context_id'];
    }
    
    if (!empty($args['search'])) {
        $where[] = '(subject LIKE %s OR message LIKE %s)';
        $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
        $where_values[] = $search_term;
        $where_values[] = $search_term;
    }
    
    $where_clause = implode(' AND ', $where);
    
    $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
    if (!$orderby) {
        $orderby = 'created_at DESC';
    }
    
    $query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY $orderby LIMIT %d OFFSET %d";
    $where_values[] = $args['limit'];
    $where_values[] = $args['offset'];
    
    if (!empty($where_values)) {
        $query = $wpdb->prepare($query, $where_values);
    }
    
    return $wpdb->get_results($query);
}

/**
 * Get total count of submissions
 */
function user_feedback_get_submissions_count($args = array()) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_submissions';
    
    $where = array('1=1');
    $where_values = array();
    
    // Specific form filter (highest priority)
    if (!empty($args['form_id'])) {
        $where[] = 'form_id = %d';
        $where_values[] = $args['form_id'];
    }
    // Category filter (shows all forms in that category)
    elseif (!empty($args['category_id'])) {
        // Get all forms in this category
        $forms = userfeedback_get_forms(array('category_id' => $args['category_id']));
        if (!empty($forms)) {
            $form_ids = wp_list_pluck($forms, 'id');
            $placeholders = implode(',', array_fill(0, count($form_ids), '%d'));
            $where[] = "form_id IN ($placeholders)";
            $where_values = array_merge($where_values, $form_ids);
        } else {
            // No forms in category, but might have legacy submissions
            $where[] = '(form_id IS NULL OR form_id = 0)';
        }
    }
    
    // Legacy support for type filter
    if (!empty($args['type'])) {
        $where[] = 'type = %s';
        $where_values[] = $args['type'];
    }
    
    if (!empty($args['status'])) {
        $where[] = 'status = %s';
        $where_values[] = $args['status'];
    }
    
    if (!empty($args['user_id'])) {
        $where[] = 'user_id = %d';
        $where_values[] = $args['user_id'];
    }
    
    if (!empty($args['search'])) {
        $where[] = '(subject LIKE %s OR message LIKE %s)';
        $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
        $where_values[] = $search_term;
        $where_values[] = $search_term;
    }
    
    $where_clause = implode(' AND ', $where);
    $query = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";
    
    if (!empty($where_values)) {
        $query = $wpdb->prepare($query, $where_values);
    }
    
    return $wpdb->get_var($query);
}

/**
 * Get single submission by ID
 */
function user_feedback_get_submission($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_submissions';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $id
    ));
}

/**
 * Insert new submission
 */
function user_feedback_insert_submission($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_submissions';
    
    $insert_data = array(
        'user_id' => $data['user_id'],
        'subject' => $data['subject'],
        'message' => $data['message'],
        'status' => isset($data['status']) ? $data['status'] : 'new',
    );
    
    $format = array('%d', '%s', '%s', '%s');
    
    // Add form_id if provided (new system)
    if (isset($data['form_id'])) {
        $insert_data['form_id'] = $data['form_id'];
        $format[] = '%d';
    }
    
    // Add type if provided (legacy support)
    if (isset($data['type'])) {
        $insert_data['type'] = $data['type'];
        $format[] = '%s';
    }
    
    // Add context_id if provided
    if (isset($data['context_id'])) {
        $insert_data['context_id'] = $data['context_id'];
        $format[] = '%s';
    }
    
    // Add metadata if provided
    if (isset($data['metadata'])) {
        $insert_data['metadata'] = $data['metadata'];
        $format[] = '%s';
    }
    
    // Add form_data if provided (dynamic field responses)
    if (isset($data['form_data'])) {
        $insert_data['form_data'] = $data['form_data'];
        $format[] = '%s';
    }
    
    // Add attachment_id if provided
    if (isset($data['attachment_id'])) {
        $insert_data['attachment_id'] = $data['attachment_id'];
        $format[] = '%d';
    }
    
    $result = $wpdb->insert($table_name, $insert_data, $format);
    
    if ($result) {
        return $wpdb->insert_id;
    }
    
    return false;
}

/**
 * Update submission
 */
function user_feedback_update_submission($id, $data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_submissions';
    
    $update_data = array();
    $format = array();
    
    if (isset($data['status'])) {
        $update_data['status'] = $data['status'];
        $format[] = '%s';
        
        // Set resolved_at if status is resolved
        if ($data['status'] === 'resolved') {
            $update_data['resolved_at'] = current_time('mysql');
            $format[] = '%s';
        }
    }
    
    if (isset($data['admin_reply'])) {
        $update_data['admin_reply'] = $data['admin_reply'];
        $format[] = '%s';
    }
    
    if (isset($data['resolution_notes'])) {
        $update_data['resolution_notes'] = $data['resolution_notes'];
        $format[] = '%s';
    }
    
    if (empty($update_data)) {
        return false;
    }
    
    return $wpdb->update(
        $table_name,
        $update_data,
        array('id' => $id),
        $format,
        array('%d')
    );
}

/**
 * Delete submission
 */
function user_feedback_delete_submission($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_submissions';
    
    return $wpdb->delete(
        $table_name,
        array('id' => $id),
        array('%d')
    );
}

/**
 * Get all canned responses
 */
function user_feedback_get_canned_responses() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_canned_responses';
    
    return $wpdb->get_results("SELECT * FROM $table_name ORDER BY title ASC");
}

/**
 * Get single canned response
 */
function user_feedback_get_canned_response($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_canned_responses';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $id
    ));
}

/**
 * Insert canned response
 */
function user_feedback_insert_canned_response($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_canned_responses';
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'title' => $data['title'],
            'content' => $data['content']
        ),
        array('%s', '%s')
    );
    
    if ($result) {
        return $wpdb->insert_id;
    }
    
    return false;
}

/**
 * Update canned response
 */
function user_feedback_update_canned_response($id, $data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_canned_responses';
    
    return $wpdb->update(
        $table_name,
        array(
            'title' => $data['title'],
            'content' => $data['content']
        ),
        array('id' => $id),
        array('%s', '%s'),
        array('%d')
    );
}

/**
 * Delete canned response
 */
function user_feedback_delete_canned_response($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_canned_responses';
    
    return $wpdb->delete(
        $table_name,
        array('id' => $id),
        array('%d')
    );
}

// ============================================================================
// FORM CATEGORIES CRUD FUNCTIONS
// ============================================================================

/**
 * Get all form categories
 *
 * @param array $args Optional query arguments
 * @return array
 */
function userfeedback_get_categories($args = array()) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_form_categories';
    
    $defaults = array(
        'orderby' => 'sort_order',
        'order' => 'ASC'
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
    if (!$orderby) {
        $orderby = 'sort_order ASC';
    }
    
    $query = "SELECT * FROM $table_name ORDER BY $orderby";
    
    return $wpdb->get_results($query);
}

/**
 * Get single category by ID
 *
 * @param int $id Category ID
 * @return object|null
 */
function userfeedback_get_category($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_form_categories';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $id
    ));
}

/**
 * Get category by slug
 *
 * @param string $slug Category slug
 * @return object|null
 */
function userfeedback_get_category_by_slug($slug) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_form_categories';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE slug = %s",
        $slug
    ));
}

/**
 * Insert new category
 *
 * @param array $data Category data
 * @return int|false
 */
function userfeedback_insert_category($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_form_categories';
    
    $insert_data = array(
        'name' => $data['name'],
        'slug' => isset($data['slug']) ? $data['slug'] : sanitize_title($data['name']),
        'description' => isset($data['description']) ? $data['description'] : '',
        'sort_order' => isset($data['sort_order']) ? intval($data['sort_order']) : 0
    );
    
    $result = $wpdb->insert(
        $table_name,
        $insert_data,
        array('%s', '%s', '%s', '%d')
    );
    
    if ($result) {
        return $wpdb->insert_id;
    }
    
    return false;
}

/**
 * Update category
 *
 * @param int $id Category ID
 * @param array $data Category data
 * @return int|false
 */
function userfeedback_update_category($id, $data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_form_categories';
    
    $update_data = array();
    $format = array();
    
    if (isset($data['name'])) {
        $update_data['name'] = $data['name'];
        $format[] = '%s';
    }
    
    if (isset($data['slug'])) {
        $update_data['slug'] = $data['slug'];
        $format[] = '%s';
    }
    
    if (isset($data['description'])) {
        $update_data['description'] = $data['description'];
        $format[] = '%s';
    }
    
    if (isset($data['sort_order'])) {
        $update_data['sort_order'] = intval($data['sort_order']);
        $format[] = '%d';
    }
    
    if (empty($update_data)) {
        return false;
    }
    
    return $wpdb->update(
        $table_name,
        $update_data,
        array('id' => $id),
        $format,
        array('%d')
    );
}

/**
 * Delete category
 *
 * @param int $id Category ID
 * @return int|false
 */
function userfeedback_delete_category($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_form_categories';
    
    // Check if category has forms
    $forms = userfeedback_get_forms(array('category_id' => $id));
    if (!empty($forms)) {
        return false; // Cannot delete category with forms
    }
    
    return $wpdb->delete(
        $table_name,
        array('id' => $id),
        array('%d')
    );
}

// ============================================================================
// CUSTOM FORMS CRUD FUNCTIONS
// ============================================================================

/**
 * Get forms with filters
 *
 * @param array $args Optional query arguments
 * @return array
 */
function userfeedback_get_forms($args = array()) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_custom_forms';
    
    $defaults = array(
        'category_id' => '',
        'is_active' => '',
        'orderby' => 'sort_order',
        'order' => 'ASC'
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $where = array('1=1');
    $where_values = array();
    
    if (!empty($args['category_id'])) {
        $where[] = 'category_id = %d';
        $where_values[] = $args['category_id'];
    }
    
    if ($args['is_active'] !== '') {
        $where[] = 'is_active = %d';
        $where_values[] = $args['is_active'] ? 1 : 0;
    }
    
    $where_clause = implode(' AND ', $where);
    
    $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
    if (!$orderby) {
        $orderby = 'sort_order ASC';
    }
    
    $query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY $orderby";
    
    if (!empty($where_values)) {
        $query = $wpdb->prepare($query, $where_values);
    }
    
    return $wpdb->get_results($query);
}

/**
 * Get single form by ID
 *
 * @param int $id Form ID
 * @return object|null
 */
function userfeedback_get_form($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_custom_forms';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $id
    ));
}

/**
 * Get form by slug
 *
 * @param string $slug Form slug
 * @return object|null
 */
function userfeedback_get_form_by_slug($slug) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_custom_forms';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE slug = %s",
        $slug
    ));
}

/**
 * Get form by shortcode
 *
 * @param string $shortcode Shortcode value
 * @return object|null
 */
function userfeedback_get_form_by_shortcode($shortcode) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_custom_forms';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE shortcode = %s AND is_active = 1",
        $shortcode
    ));
}

/**
 * Insert new form
 *
 * @param array $data Form data
 * @return int|false
 */
function userfeedback_insert_form($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_custom_forms';
    
    $insert_data = array(
        'category_id' => $data['category_id'],
        'name' => $data['name'],
        'slug' => isset($data['slug']) ? $data['slug'] : sanitize_title($data['name']),
        'description' => isset($data['description']) ? $data['description'] : '',
        'shortcode' => $data['shortcode'],
        'field_config' => isset($data['field_config']) ? $data['field_config'] : '',
        'sort_order' => isset($data['sort_order']) ? intval($data['sort_order']) : 0,
        'is_active' => isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1
    );
    
    $result = $wpdb->insert(
        $table_name,
        $insert_data,
        array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d')
    );
    
    if ($result) {
        return $wpdb->insert_id;
    }
    
    return false;
}

/**
 * Update form
 *
 * @param int $id Form ID
 * @param array $data Form data
 * @return int|false
 */
function userfeedback_update_form($id, $data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_custom_forms';
    
    $update_data = array();
    $format = array();
    
    if (isset($data['category_id'])) {
        $update_data['category_id'] = intval($data['category_id']);
        $format[] = '%d';
    }
    
    if (isset($data['name'])) {
        $update_data['name'] = $data['name'];
        $format[] = '%s';
    }
    
    if (isset($data['slug'])) {
        $update_data['slug'] = $data['slug'];
        $format[] = '%s';
    }
    
    if (isset($data['description'])) {
        $update_data['description'] = $data['description'];
        $format[] = '%s';
    }
    
    if (isset($data['shortcode'])) {
        $update_data['shortcode'] = $data['shortcode'];
        $format[] = '%s';
    }
    
    if (isset($data['field_config'])) {
        $update_data['field_config'] = $data['field_config'];
        $format[] = '%s';
    }
    
    if (isset($data['sort_order'])) {
        $update_data['sort_order'] = intval($data['sort_order']);
        $format[] = '%d';
    }
    
    if (isset($data['is_active'])) {
        $update_data['is_active'] = $data['is_active'] ? 1 : 0;
        $format[] = '%d';
    }
    
    if (empty($update_data)) {
        return false;
    }
    
    return $wpdb->update(
        $table_name,
        $update_data,
        array('id' => $id),
        $format,
        array('%d')
    );
}

/**
 * Delete form
 *
 * @param int $id Form ID
 * @return int|false
 */
function userfeedback_delete_form($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userfeedback_custom_forms';
    
    // Check if form has submissions
    $submissions = user_feedback_get_submissions(array('form_id' => $id, 'limit' => 1));
    if (!empty($submissions)) {
        return false; // Cannot delete form with submissions
    }
    
    return $wpdb->delete(
        $table_name,
        array('id' => $id),
        array('%d')
    );
}

