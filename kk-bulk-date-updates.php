<?php
/**
 * Plugin Name: Bulk Date Updates by KK
 * Plugin URI: https://karol.cc/
 * Description: A WordPress plugin for bulk updating dates across posts and pages.
 * Version: 0.0.2
 * Author: Karol K
 * Author URI: https://karol.cc/
 * License: GPL v2 or later
 * Text Domain: kk-bulk-date-updates
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KK_BULK_DATE_UPDATES_VERSION', '1.0.0');
define('KK_BULK_DATE_UPDATES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KK_BULK_DATE_UPDATES_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('KK_BULK_DATE_UPDATES_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class KK_Bulk_Date_Updates {
    
    /**
     * Plugin instance
     *
     * @var KK_Bulk_Date_Updates
     */
    private static $instance = null;
    
    /**
     * Store original hooks state for restoration
     *
     * @var array
     */
    private $original_hooks = array();
    
    /**
     * Get plugin instance
     *
     * @return KK_Bulk_Date_Updates
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Plugin lifecycle hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('KK_Bulk_Date_Updates', 'uninstall'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for internationalization
        load_plugin_textdomain(
            'kk-bulk-date-updates',
            false,
            dirname(KK_BULK_DATE_UPDATES_PLUGIN_BASENAME) . '/languages'
        );
        
        // Initialize admin functionality
        if (is_admin()) {
            $this->init_admin();
        }
    }
    
    /**
     * Initialize admin functionality
     */
    private function init_admin() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add AJAX handlers
        add_action('wp_ajax_kk_bulk_date_updates_action', array($this, 'handle_ajax_request'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            __('Bulk Date Updates', 'kk-bulk-date-updates'),
            __('Bulk Date Updates', 'kk-bulk-date-updates'),
            'manage_options',
            'kk-bulk-date-updates',
            array($this, 'admin_page_callback')
        );
    }
    
    /**
     * Admin page callback
     */
    public function admin_page_callback() {
        include_once KK_BULK_DATE_UPDATES_PLUGIN_PATH . 'includes/admin/admin-page.php';
    }
    
    /**
     * Handle AJAX requests
     */
    public function handle_ajax_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'kk_bulk_date_updates_nonce')) {
            wp_die(__('Security check failed', 'kk-bulk-date-updates'));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'kk-bulk-date-updates'));
        }
        
        // Process the bulk date update request
        $response = $this->process_bulk_date_update();
        
        wp_send_json($response);
    }
    
    /**
     * Process bulk date update request
     */
    private function process_bulk_date_update() {
        // Sanitize and validate form data
        $form_data = $this->sanitize_form_data($_POST);
        
        if (!$form_data) {
            return array(
                'success' => false,
                'message' => __('Invalid form data provided', 'kk-bulk-date-updates')
            );
        }
        
        // Validate required fields based on update method
        $validation_result = $this->validate_form_data($form_data);
        if (!$validation_result['valid']) {
            return array(
                'success' => false,
                'message' => $validation_result['message']
            );
        }
        
        // Get posts to update based on criteria
        $posts = $this->get_posts_to_update($form_data);
        
        if (empty($posts)) {
            return array(
                'success' => false,
                'message' => __('No posts found matching the specified criteria', 'kk-bulk-date-updates')
            );
        }
        
        // Safety check for large operations
        if (count($posts) > 1000 && !$form_data['dry_run']) {
            $memory_limit = ini_get('memory_limit');
            $memory_limit_bytes = $this->convert_to_bytes($memory_limit);
            
            if ($memory_limit_bytes < 536870912) { // Less than 512MB
                return array(
                    'success' => false,
                    'message' => sprintf(
                        __('Large bulk operation detected (%d posts). Please increase PHP memory_limit to at least 512M or reduce the number of posts. Current limit: %s', 'kk-bulk-date-updates'),
                        count($posts),
                        $memory_limit
                    )
                );
            }
        }
        
        // Check if this is a dry run (preview)
        if ($form_data['dry_run']) {
            return $this->generate_preview($posts, $form_data);
        } else {
            return $this->execute_bulk_update($posts, $form_data);
        }
    }
    
    /**
     * Sanitize form data
     */
    private function sanitize_form_data($post_data) {
        $sanitized = array();
        
        // Post types
        $sanitized['post_types'] = isset($post_data['post_types']) && is_array($post_data['post_types']) 
            ? array_map('sanitize_text_field', $post_data['post_types']) 
            : array('post');
            
        // Date fields
        $sanitized['date_fields'] = isset($post_data['date_fields']) && is_array($post_data['date_fields']) 
            ? array_map('sanitize_text_field', $post_data['date_fields']) 
            : array('post_date');
            
        // Modified date offset
        $sanitized['modified_date_offset'] = isset($post_data['modified_date_offset']) 
            ? absint($post_data['modified_date_offset']) 
            : 5;
            
        // Update method
        $sanitized['update_method'] = isset($post_data['update_method']) 
            ? sanitize_text_field($post_data['update_method']) 
            : 'add_days';
            
        // Days value
        $sanitized['days_value'] = isset($post_data['days_value']) 
            ? absint($post_data['days_value']) 
            : 1;
            
        // Post status
        $sanitized['post_status'] = isset($post_data['post_status']) && is_array($post_data['post_status']) 
            ? array_map('sanitize_text_field', $post_data['post_status']) 
            : array('publish');
            
        // Limit posts
        $sanitized['limit_posts'] = isset($post_data['limit_posts']) 
            ? min(absint($post_data['limit_posts']), 10000) 
            : 100;
            
        // Dry run
        $sanitized['dry_run'] = isset($post_data['dry_run']) && $post_data['dry_run'] === '1';
        
        return $sanitized;
    }
    
    /**
     * Validate form data
     */
    private function validate_form_data($form_data) {
        // Check if at least one date field is selected
        if (empty($form_data['date_fields'])) {
            return array(
                'valid' => false,
                'message' => __('Please select at least one date field to update', 'kk-bulk-date-updates')
            );
        }
        
        // Validate update method specific requirements
        switch ($form_data['update_method']) {
            case 'add_days':
            case 'subtract_days':
                if ($form_data['days_value'] < 1) {
                    return array(
                        'valid' => false,
                        'message' => __('Please enter a valid number of days (minimum 1)', 'kk-bulk-date-updates')
                    );
                }
                break;
                

        }
        
        return array('valid' => true);
    }
    
    /**
     * Get posts to update based on criteria
     */
    private function get_posts_to_update($form_data) {
        $args = array(
            'post_type' => $form_data['post_types'],
            'post_status' => $form_data['post_status'],
            'posts_per_page' => $form_data['limit_posts'],
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
            'no_found_rows' => true, // Skip counting total posts for better performance
            'update_post_meta_cache' => false, // Skip meta cache
            'update_post_term_cache' => false, // Skip term cache
            'suppress_filters' => true // Skip filters for better performance
        );
        
        $query = new WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Generate preview for dry run
     */
    private function generate_preview($post_ids, $form_data) {
        $preview_data = array();
        $count = 0;
        
        foreach ($post_ids as $post_id) {
            if ($count >= 10) { // Limit preview to first 10 posts
                break;
            }
            
            $post = get_post($post_id);
            if (!$post) continue;
            
            $changes = $this->calculate_date_changes($post, $form_data);
            if ($changes) {
                $preview_data[] = $changes;
                $count++;
            }
        }
        
        $total_posts = count($post_ids);
        $preview_html = $this->generate_preview_html($preview_data, $total_posts, $form_data);
        
        return array(
            'success' => true,
            'message' => sprintf(__('Preview generated for %d posts', 'kk-bulk-date-updates'), $total_posts),
            'data' => array(
                'preview_html' => $preview_html,
                'total_posts' => $total_posts,
                'preview_count' => count($preview_data)
            )
        );
    }
    
    /**
     * Calculate date changes for a post
     */
    private function calculate_date_changes($post, $form_data) {
        $changes = array(
            'post_id' => $post->ID,
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'current_dates' => array(),
            'new_dates' => array()
        );
        
        $new_published_date = null;
        $both_dates_selected = in_array('post_date', $form_data['date_fields']) && in_array('post_modified', $form_data['date_fields']);
        
        // First pass: calculate published date if selected
        if (in_array('post_date', $form_data['date_fields'])) {
            $current_date = $post->post_date;
            $new_date = $this->calculate_new_date($current_date, $form_data, 'post_date', 0, $post);
            
            if ($current_date && $new_date) {
                $changes['current_dates']['post_date'] = $current_date;
                $changes['new_dates']['post_date'] = $new_date;
                $new_published_date = $new_date;
            }
        }
        
        // Special case: For "Match Modified to Published", always show published date in preview
        if ($form_data['update_method'] === 'match_modified_to_published' && !isset($changes['current_dates']['post_date'])) {
            $changes['current_dates']['post_date'] = $post->post_date;
            $changes['new_dates']['post_date'] = $post->post_date; // No change to published date
        }
        
        // Second pass: calculate modified date
        $should_calculate_modified = in_array('post_modified', $form_data['date_fields']) || $form_data['update_method'] === 'match_modified_to_published';
        
        if ($should_calculate_modified) {
            $current_date = $post->post_modified;
            
            if ($form_data['update_method'] === 'match_modified_to_published') {
                // For "Match Modified to Published", always use published date as base with the specified offset
                $new_date = $this->calculate_modified_date_from_published($post->post_date, $form_data['modified_date_offset']);
            } elseif ($both_dates_selected && $new_published_date) {
                // Use new published date as base for modified date calculation
                $new_date = $this->calculate_modified_date_from_published($new_published_date, $form_data['modified_date_offset']);
            } else {
                // Use current modified date as base
                $new_date = $this->calculate_new_date($current_date, $form_data, 'post_modified', $form_data['modified_date_offset'], $post);
            }
            
            if ($current_date && $new_date) {
                $changes['current_dates']['post_modified'] = $current_date;
                $changes['new_dates']['post_modified'] = $new_date;
            }
        }
        
        return !empty($changes['new_dates']) ? $changes : null;
    }
    
    /**
     * Calculate new date based on update method
     */
    private function calculate_new_date($current_date, $form_data, $date_field, $offset_minutes = 0, $post = null) {
        $timestamp = strtotime($current_date);
        
        switch ($form_data['update_method']) {
            case 'add_days':
                $new_timestamp = $timestamp + ($form_data['days_value'] * DAY_IN_SECONDS);
                break;
                
            case 'subtract_days':
                $new_timestamp = $timestamp - ($form_data['days_value'] * DAY_IN_SECONDS);
                break;
                
            case 'match_modified_to_published':
                // Only applies to modified date field
                if ($date_field === 'post_modified' && $post) {
                    $timestamp = strtotime($post->post_date);
                } else {
                    return $current_date; // No change for published date or if no post object
                }
                break;
                
            default:
                return $current_date; // No change for unsupported methods yet
        }
        
        // Add offset for modified date (can be 0, positive, or negative)
        $new_timestamp += ($offset_minutes * MINUTE_IN_SECONDS);
        
        return date('Y-m-d H:i:s', $new_timestamp);
    }
    
    /**
     * Calculate modified date based on new published date
     */
    private function calculate_modified_date_from_published($published_date, $offset_minutes = 0) {
        $timestamp = strtotime($published_date);
        
        // Add offset for modified date (can be 0, positive, or negative)
        $timestamp += ($offset_minutes * MINUTE_IN_SECONDS);
        
        return date('Y-m-d H:i:s', $timestamp);
    }
    
    /**
     * Generate preview HTML
     */
    private function generate_preview_html($preview_data, $total_posts, $form_data) {
        if (empty($preview_data)) {
            return '<p>' . __('No changes to preview.', 'kk-bulk-date-updates') . '</p>';
        }
        
        $html = '<div class="kk-preview-results">';
        $html .= '<h3>' . sprintf(__('Preview: %d posts will be updated', 'kk-bulk-date-updates'), $total_posts) . '</h3>';
        
        if (count($preview_data) < $total_posts) {
            $html .= '<p><em>' . sprintf(__('Showing first %d posts. All %d matching posts will be updated.', 'kk-bulk-date-updates'), count($preview_data), $total_posts) . '</em></p>';
        }
        
        $html .= '<table class="wp-list-table widefat fixed striped">';
        $html .= '<thead><tr>';
        $html .= '<th>' . __('Post', 'kk-bulk-date-updates') . '</th>';
        $html .= '<th>' . __('Current Dates', 'kk-bulk-date-updates') . '</th>';
        $html .= '<th>' . __('New Dates', 'kk-bulk-date-updates') . '</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($preview_data as $change) {
            $html .= '<tr>';
            $html .= '<td><strong>' . esc_html($change['post_title']) . '</strong><br><small>ID: ' . $change['post_id'] . ' (' . $change['post_type'] . ')</small></td>';
            
            // Current dates
            $html .= '<td>';
            foreach ($change['current_dates'] as $field => $date) {
                $field_label = $field === 'post_date' ? __('Published', 'kk-bulk-date-updates') : __('Modified', 'kk-bulk-date-updates');
                $html .= '<strong>' . $field_label . ':</strong> ' . esc_html($date) . '<br>';
            }
            $html .= '</td>';
            
            // New dates
            $html .= '<td>';
            foreach ($change['new_dates'] as $field => $date) {
                $field_label = $field === 'post_date' ? __('Published', 'kk-bulk-date-updates') : __('Modified', 'kk-bulk-date-updates');
                $html .= '<strong>' . $field_label . ':</strong> ' . esc_html($date) . '<br>';
            }
            $html .= '</td>';
            
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Execute bulk update with optimizations
     */
    private function execute_bulk_update($post_ids, $form_data) {
        global $wpdb;
        
        $updated_count = 0;
        $errors = array();
        $activity_log = array();
        
        // Increase memory limit and execution time for large operations
        if (count($post_ids) > 50) {
            ini_set('memory_limit', '512M');
            set_time_limit(300); // 5 minutes
        }
        
        // Temporarily disable WordPress hooks to improve performance
        $this->disable_post_update_hooks();
        
        // Batch process posts to avoid memory issues
        $batch_size = 50;
        $batches = array_chunk($post_ids, $batch_size);
        
        foreach ($batches as $batch) {
            // Pre-fetch all posts in this batch to reduce database queries
            $posts = $this->get_posts_batch($batch);
            
            foreach ($batch as $post_id) {
                if (!isset($posts[$post_id])) {
                    $errors[] = sprintf(__('Post ID %d not found', 'kk-bulk-date-updates'), $post_id);
                    continue;
                }
                
                $post = $posts[$post_id];
                $update_data = array();
                $updated_fields = array();
                $date_changes = array();
                $new_published_date = null;
                $both_dates_selected = in_array('post_date', $form_data['date_fields']) && in_array('post_modified', $form_data['date_fields']);
                
                // First pass: handle published date if selected
                if (in_array('post_date', $form_data['date_fields'])) {
                    $old_date = $post->post_date;
                    $new_date = $this->calculate_new_date($old_date, $form_data, 'post_date', 0, $post);
                    if ($new_date && $new_date !== $old_date) {
                        $update_data['post_date'] = $new_date;
                        $update_data['post_date_gmt'] = get_gmt_from_date($new_date);
                        $updated_fields[] = 'post_date';
                        $date_changes['post_date'] = array(
                            'old' => $old_date,
                            'new' => $new_date
                        );
                        $new_published_date = $new_date;
                    }
                }
                
                // Second pass: handle modified date
                $should_update_modified = in_array('post_modified', $form_data['date_fields']) || $form_data['update_method'] === 'match_modified_to_published';
                
                if ($should_update_modified) {
                    $old_date = $post->post_modified;
                    
                    if ($form_data['update_method'] === 'match_modified_to_published') {
                        // For "Match Modified to Published", always use published date as base with the specified offset
                        $new_date = $this->calculate_modified_date_from_published($post->post_date, $form_data['modified_date_offset']);
                    } elseif ($both_dates_selected && $new_published_date) {
                        // Use new published date as base for modified date calculation
                        $new_date = $this->calculate_modified_date_from_published($new_published_date, $form_data['modified_date_offset']);
                    } else {
                        // Use current modified date as base
                        $new_date = $this->calculate_new_date($old_date, $form_data, 'post_modified', $form_data['modified_date_offset'], $post);
                    }
                    
                    if ($new_date && $new_date !== $old_date) {
                        $update_data['post_modified'] = $new_date;
                        $update_data['post_modified_gmt'] = get_gmt_from_date($new_date);
                        $updated_fields[] = 'post_modified';
                        $date_changes['post_modified'] = array(
                            'old' => $old_date,
                            'new' => $new_date
                        );
                    }
                }
                
                // Only update if there are changes
                if (!empty($update_data)) {
                    // Use direct database update for better performance
                    $result = $this->update_post_dates_direct($post_id, $update_data);
                    
                    if ($result === false) {
                        $errors[] = sprintf(__('Failed to update post ID %d', 'kk-bulk-date-updates'), $post_id);
                    } else {
                        $updated_count++;
                        
                        // Create log entry for temporary display (reuse post object)
                        $activity_log[] = $this->create_log_entry_optimized($post, $updated_fields, $form_data, $date_changes);
                    }
                }
            }
            
            // Clear memory after each batch
            unset($posts);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
        
        // Re-enable WordPress hooks
        $this->enable_post_update_hooks();
        
        // Clear object cache to prevent stale data
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Add performance info to response
        $performance_info = sprintf(
            __('Processed %d posts in %d batches with optimized database operations.', 'kk-bulk-date-updates'),
            count($post_ids),
            count($batches)
        );
        
        // Prepare response
        if ($updated_count > 0) {
            $message = sprintf(
                _n(
                    'Successfully updated %d post.',
                    'Successfully updated %d posts.',
                    $updated_count,
                    'kk-bulk-date-updates'
                ),
                $updated_count
            );
            
            if (!empty($errors)) {
                $message .= ' ' . sprintf(__('However, %d errors occurred.', 'kk-bulk-date-updates'), count($errors));
            }
            
            return array(
                'success' => true,
                'message' => $message . ' ' . $performance_info,
                'data' => array(
                    'updated_count' => $updated_count,
                    'errors' => $errors,
                    'activity_log' => $activity_log,
                    'performance_info' => $performance_info
                )
            );
        } else {
            return array(
                'success' => false,
                'message' => __('No posts were updated. Please check your settings and try again.', 'kk-bulk-date-updates'),
                'data' => array(
                    'errors' => $errors,
                    'activity_log' => array()
                )
            );
        }
    }
    
    /**
     * Get posts in batch to reduce database queries
     */
    private function get_posts_batch($post_ids) {
        global $wpdb;
        
        if (empty($post_ids)) {
            return array();
        }
        
        $post_ids_str = implode(',', array_map('intval', $post_ids));
        $posts = $wpdb->get_results(
            "SELECT ID, post_title, post_type, post_date, post_modified 
             FROM {$wpdb->posts} 
             WHERE ID IN ($post_ids_str)",
            OBJECT_K
        );
        
        return $posts;
    }
    
    /**
     * Update post dates directly in database for better performance
     */
    private function update_post_dates_direct($post_id, $update_data) {
        global $wpdb;
        
        $set_clauses = array();
        $values = array();
        
        if (isset($update_data['post_date'])) {
            $set_clauses[] = 'post_date = %s';
            $values[] = $update_data['post_date'];
        }
        
        if (isset($update_data['post_date_gmt'])) {
            $set_clauses[] = 'post_date_gmt = %s';
            $values[] = $update_data['post_date_gmt'];
        }
        
        if (isset($update_data['post_modified'])) {
            $set_clauses[] = 'post_modified = %s';
            $values[] = $update_data['post_modified'];
        }
        
        if (isset($update_data['post_modified_gmt'])) {
            $set_clauses[] = 'post_modified_gmt = %s';
            $values[] = $update_data['post_modified_gmt'];
        }
        
        if (empty($set_clauses)) {
            return true;
        }
        
        $values[] = $post_id;
        $sql = "UPDATE {$wpdb->posts} SET " . implode(', ', $set_clauses) . " WHERE ID = %d";
        
        return $wpdb->query($wpdb->prepare($sql, $values));
    }
    
    /**
     * Temporarily disable WordPress hooks that can slow down bulk operations
     */
    private function disable_post_update_hooks() {
        // Store original hook state
        $this->original_hooks = array(
            'save_post' => has_action('save_post'),
            'wp_insert_post_data' => has_filter('wp_insert_post_data'),
            'post_updated' => has_action('post_updated')
        );
        
        // Remove expensive hooks temporarily
        remove_all_actions('save_post');
        remove_all_filters('wp_insert_post_data');
        remove_all_actions('post_updated');
        
        // Disable object cache updates during bulk operation
        wp_suspend_cache_addition(true);
    }
    
    /**
     * Re-enable WordPress hooks after bulk operation
     */
    private function enable_post_update_hooks() {
        // Re-enable cache additions
        wp_suspend_cache_addition(false);
        
        // Note: We don't restore the hooks as they will be re-added on next page load
        // This is safer than trying to restore complex hook structures
    }
    
    /**
     * Create log entry for temporary display (optimized version)
     */
    private function create_log_entry_optimized($post, $updated_fields, $form_data, $date_changes = array()) {
        return array(
            'timestamp' => current_time('mysql'),
            'post_id' => $post->ID,
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'updated_fields' => $updated_fields,
            'date_changes' => $date_changes,
            'update_method' => $form_data['update_method'],
            'user_id' => get_current_user_id()
        );
    }
    
    /**
     * Create log entry for temporary display (legacy method for backward compatibility)
     */
    private function create_log_entry($post_id, $updated_fields, $form_data, $date_changes = array()) {
        $post = get_post($post_id);
        return array(
            'timestamp' => current_time('mysql'),
            'post_id' => $post_id,
            'post_title' => $post ? $post->post_title : __('Unknown Post', 'kk-bulk-date-updates'),
            'post_type' => $post ? $post->post_type : 'unknown',
            'updated_fields' => $updated_fields,
            'date_changes' => $date_changes,
            'update_method' => $form_data['update_method'],
            'user_id' => get_current_user_id()
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on our admin page
        if ('tools_page_kk-bulk-date-updates' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'kk-bulk-date-updates-admin',
            KK_BULK_DATE_UPDATES_PLUGIN_URL . 'css/admin.css',
            array(),
            KK_BULK_DATE_UPDATES_VERSION
        );
        
        wp_enqueue_script(
            'kk-bulk-date-updates-admin',
            KK_BULK_DATE_UPDATES_PLUGIN_URL . 'js/admin.js',
            array('jquery'),
            KK_BULK_DATE_UPDATES_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script(
            'kk-bulk-date-updates-admin',
            'kkBulkDateUpdates',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('kk_bulk_date_updates_nonce'),
                'strings' => array(
                    'processing' => __('Processing...', 'kk-bulk-date-updates'),
                    'error' => __('An error occurred', 'kk-bulk-date-updates'),
                    'success' => __('Operation completed successfully', 'kk-bulk-date-updates')
                )
            )
        );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables if needed
        $this->create_tables();
        
        // Set default options
        add_option('kk_bulk_date_updates_version', KK_BULK_DATE_UPDATES_VERSION);
        
        // Note: flush_rewrite_rules() removed - not needed for date update plugin
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up scheduled events
        wp_clear_scheduled_hook('kk_bulk_date_updates_cron');
        
        // Note: flush_rewrite_rules() removed - not needed for date update plugin
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Remove options
        delete_option('kk_bulk_date_updates_version');
        delete_option('kk_bulk_date_updates_logs'); // Clean up old logs if they exist
        
        // Remove database tables if needed
        // self::drop_tables();
    }
    
    /**
     * Convert memory limit string to bytes
     */
    private function convert_to_bytes($memory_limit) {
        $memory_limit = trim($memory_limit);
        $last = strtolower($memory_limit[strlen($memory_limit) - 1]);
        $memory_limit = (int) $memory_limit;
        
        switch ($last) {
            case 'g':
                $memory_limit *= 1024;
            case 'm':
                $memory_limit *= 1024;
            case 'k':
                $memory_limit *= 1024;
        }
        
        return $memory_limit;
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Example table creation (uncomment if needed)
        /*
        $table_name = $wpdb->prefix . 'kk_bulk_date_updates_log';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            old_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            new_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            updated_by bigint(20) NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY updated_by (updated_by)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        */
    }
}

// Initialize the plugin
function kk_bulk_date_updates_init() {
    return KK_Bulk_Date_Updates::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'kk_bulk_date_updates_init'); 