<?php
/**
 * Widget implementation for User Feedback plugin
 */

if (!defined('WPINC')) {
    die;
}

/**
 * User Feedback Widget Class
 */
class User_Feedback_Widget extends WP_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'user_feedback_widget',
            'User Feedback Form',
            array(
                'description' => 'Display a feedback submission form in your sidebar'
            )
        );
    }
    
    /**
     * Front-end display of widget
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $type = !empty($instance['type']) ? $instance['type'] : 'comment';
        $context_id = !empty($instance['context_id']) ? $instance['context_id'] : '';
        
        // Use the shortcode handler
        echo user_feedback_shortcode_handler(array(
            'type' => $type,
            'context_id' => $context_id
        ));
        
        echo $args['after_widget'];
    }
    
    /**
     * Back-end widget form
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $type = !empty($instance['type']) ? $instance['type'] : 'comment';
        $context_id = !empty($instance['context_id']) ? $instance['context_id'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Title:</label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('type')); ?>">Feedback Type:</label>
            <select class="widefat" 
                    id="<?php echo esc_attr($this->get_field_id('type')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('type')); ?>">
                <option value="comment" <?php selected($type, 'comment'); ?>>Comment/Question</option>
                <option value="bug" <?php selected($type, 'bug'); ?>>Bug Report</option>
            </select>
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('context_id')); ?>">Context ID (optional):</label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('context_id')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('context_id')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($context_id); ?>"
                   placeholder="e.g., page-checkout">
            <small>Used to track bugs for specific pages/features</small>
        </p>
        <?php
    }
    
    /**
     * Sanitize widget form values as they are saved
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['type'] = !empty($new_instance['type']) ? sanitize_text_field($new_instance['type']) : 'comment';
        $instance['context_id'] = !empty($new_instance['context_id']) ? sanitize_text_field($new_instance['context_id']) : '';
        
        return $instance;
    }
}

/**
 * Register widget
 */
add_action('widgets_init', 'user_feedback_register_widget');
function user_feedback_register_widget() {
    register_widget('User_Feedback_Widget');
}

