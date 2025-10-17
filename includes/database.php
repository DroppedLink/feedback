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
    
    // Submissions table
    $submissions_table = $wpdb->prefix . 'user_feedback_submissions';
    $sql_submissions = "CREATE TABLE $submissions_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        type varchar(20) NOT NULL,
        context_id varchar(100) DEFAULT NULL,
        subject varchar(255) NOT NULL,
        message text NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'new',
        admin_reply text DEFAULT NULL,
        resolution_notes text DEFAULT NULL,
        metadata text DEFAULT NULL,
        attachment_id bigint(20) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        resolved_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY type (type),
        KEY status (status),
        KEY context_id (context_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    // Canned responses table
    $responses_table = $wpdb->prefix . 'user_feedback_canned_responses';
    $sql_responses = "CREATE TABLE $responses_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        content text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_submissions);
    dbDelta($sql_responses);
    
    // Store database version
    add_option('user_feedback_db_version', '1.0');
}

/**
 * Check and upgrade database if needed
 */
function user_feedback_check_db_version() {
    $current_version = get_option('user_feedback_db_version', '0');
    
    if (version_compare($current_version, '1.0', '<')) {
        user_feedback_create_tables();
        update_option('user_feedback_db_version', '1.0');
    }
    
    // Migration to 1.1: Add metadata column
    if (version_compare($current_version, '1.1', '<')) {
        user_feedback_migrate_to_1_1();
        update_option('user_feedback_db_version', '1.1');
    }
    
    // Migration to 1.2: Add attachment_id column
    if (version_compare($current_version, '1.2', '<')) {
        user_feedback_migrate_to_1_2();
        update_option('user_feedback_db_version', '1.2');
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
 * Get submissions with filters
 */
function user_feedback_get_submissions($args = array()) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_feedback_submissions';
    
    $defaults = array(
        'type' => '',
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
    $table_name = $wpdb->prefix . 'user_feedback_submissions';
    
    $where = array('1=1');
    $where_values = array();
    
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
    $table_name = $wpdb->prefix . 'user_feedback_submissions';
    
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
    $table_name = $wpdb->prefix . 'user_feedback_submissions';
    
    $insert_data = array(
        'user_id' => $data['user_id'],
        'type' => $data['type'],
        'context_id' => isset($data['context_id']) ? $data['context_id'] : null,
        'subject' => $data['subject'],
        'message' => $data['message'],
        'status' => isset($data['status']) ? $data['status'] : 'new',
    );
    
    $format = array('%d', '%s', '%s', '%s', '%s', '%s');
    
    // Add metadata if provided
    if (isset($data['metadata'])) {
        $insert_data['metadata'] = $data['metadata'];
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
    $table_name = $wpdb->prefix . 'user_feedback_submissions';
    
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
    $table_name = $wpdb->prefix . 'user_feedback_submissions';
    
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
    $table_name = $wpdb->prefix . 'user_feedback_canned_responses';
    
    return $wpdb->get_results("SELECT * FROM $table_name ORDER BY title ASC");
}

/**
 * Get single canned response
 */
function user_feedback_get_canned_response($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_feedback_canned_responses';
    
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
    $table_name = $wpdb->prefix . 'user_feedback_canned_responses';
    
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
    $table_name = $wpdb->prefix . 'user_feedback_canned_responses';
    
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
    $table_name = $wpdb->prefix . 'user_feedback_canned_responses';
    
    return $wpdb->delete(
        $table_name,
        array('id' => $id),
        array('%d')
    );
}

