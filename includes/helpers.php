<?php
/**
 * Helper functions shared across the User Feedback plugin.
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Determine if the quick collector feature is enabled in settings.
 */
function user_feedback_is_quick_collector_enabled() {
    return get_option('user_feedback_quick_collector_enabled', '0') === '1';
}

/**
 * Determine if the navigation menu link integration is enabled.
 */
function user_feedback_is_menu_link_enabled() {
    return get_option('user_feedback_menu_link_enabled', '1') === '1';
}

/**
 * Determine whether shared assets should be enqueued globally (beyond plugin screens).
 */
function user_feedback_should_enqueue_global_assets() {
    if (!is_user_logged_in()) {
        return false;
    }

    return user_feedback_is_quick_collector_enabled() || user_feedback_is_menu_link_enabled();
}

/**
 * Get the maximum file size allowed for uploads (in megabytes).
 */
function user_feedback_get_max_file_size_mb() {
    $max = intval(get_option('user_feedback_max_file_size', 25));
    if ($max < 1) {
        $max = 1;
    }
    // Allow up to 200 MB (still limited by server PHP settings)
    if ($max > 200) {
        $max = 200;
    }

    return $max;
}

/**
 * Get the maximum file size allowed for uploads (in bytes).
 */
function user_feedback_get_max_file_size_bytes() {
    return user_feedback_get_max_file_size_mb() * 1024 * 1024;
}

/**
 * Get the list of allowed file extensions for attachments.
 */
function user_feedback_get_allowed_file_types() {
    $default_types = 'jpg,jpeg,png,gif,webp,svg,bmp,ico,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,rtf,log,zip,rar,7z,tar,gz,tgz,bz2,xz,json,xml,csv,yaml,yml,ini,conf,cfg,config,properties,toml,env,md,markdown,rst,sql,db,sqlite,html,css,js,ts,jsx,tsx,py,php,rb,go,rs,c,cpp,h,hpp,java,kt,swift,sh,bash,ps1,bat,cmd,pl,lua,vbs,dockerfile,tf,tfvars,pem,crt,key,cer,dump,dmp,trace,pcap,pcapng,cap,patch,diff,bin,dat,mp4,mov,avi,webm,mkv,odt,ods,odp';
    $raw = get_option('user_feedback_allowed_file_types', $default_types);
    $types = array_filter(array_map('strtolower', array_map('trim', explode(',', $raw))));

    if (empty($types)) {
        $types = explode(',', $default_types);
    }

    return array_values(array_unique($types));
}

/**
 * Convert allowed file extensions into MIME types for validation.
 */
function user_feedback_get_allowed_mime_types() {
    $extensions = user_feedback_get_allowed_file_types();
    $mime_types = wp_get_mime_types();
    $allowed = array();

    foreach ($mime_types as $ext_group => $mime) {
        $ext_parts = array_map('trim', explode('|', $ext_group));
        foreach ($extensions as $extension) {
            if (in_array($extension, $ext_parts, true)) {
                $allowed[$mime] = true;
            }
        }
    }

    if (empty($allowed)) {
        $allowed = array(
            'image/jpeg' => true,
            'image/png' => true,
            'image/gif' => true,
            'image/webp' => true,
        );
    }

    return array_keys($allowed);
}

/**
 * Build MIME override array for wp_handle_upload based on allowed extensions.
 */
function user_feedback_get_upload_mime_overrides() {
    $extensions = user_feedback_get_allowed_file_types();
    $mime_types = wp_get_mime_types();
    $overrides = array();

    foreach ($extensions as $extension) {
        foreach ($mime_types as $ext_group => $mime) {
            $ext_parts = array_map('trim', explode('|', $ext_group));
            if (in_array($extension, $ext_parts, true)) {
                $overrides[$extension] = $mime;
                break;
            }
        }
    }

    if (empty($overrides)) {
        $overrides = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        );
    }

    return $overrides;
}

/**
 * Normalize a raw metadata payload into sanitized JSON for storage.
 *
 * @param mixed $raw_metadata Raw metadata (JSON string or array).
 */
function user_feedback_normalize_metadata($raw_metadata) {
    if (empty($raw_metadata)) {
        return '';
    }

    if (is_array($raw_metadata)) {
        $decoded = $raw_metadata;
    } else {
        $decoded = json_decode(wp_unslash($raw_metadata), true);
    }

    if (!is_array($decoded)) {
        return '';
    }

    $sanitized = user_feedback_sanitize_metadata_value($decoded);

    return wp_json_encode($sanitized);
}

/**
 * Recursively sanitize metadata values.
 *
 * @param mixed $value Metadata value.
 * @return mixed
 */
function user_feedback_sanitize_metadata_value($value) {
    if (is_array($value)) {
        $sanitized = array();
        foreach ($value as $key => $item) {
            $sanitized_key = is_string($key) ? sanitize_text_field($key) : $key;
            $sanitized[$sanitized_key] = user_feedback_sanitize_metadata_value($item);
        }
        return $sanitized;
    }

    if (is_scalar($value) || null === $value) {
        return is_string($value) ? sanitize_text_field($value) : $value;
    }

    return '';
}

