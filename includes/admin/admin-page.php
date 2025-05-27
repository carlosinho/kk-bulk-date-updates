<?php
/**
 * Admin Page Template
 * 
 * @package KK_Bulk_Date_Updates
 * @version 0.0.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap kk-bulk-date-updates-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php /*
    <div class="notice notice-info">
        <p><strong><?php _e('Performance Optimized:', 'kk-bulk-date-updates'); ?></strong> 
        <?php _e('This plugin uses optimized database operations and batch processing for better performance. Large operations (1000+ posts) require at least 512MB PHP memory limit.', 'kk-bulk-date-updates'); ?></p>
    </div>
    */ ?>

    <div class="kk-bulk-date-updates-form">
        <h2><?php _e('Bulk Date Update Settings', 'kk-bulk-date-updates'); ?></h2>
        
        <form method="post" action="" class="kk-bulk-date-updates-form">
            <?php wp_nonce_field('kk_bulk_date_updates_nonce', 'kk_bulk_date_updates_nonce'); ?>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="post_types"><?php _e('Post Types', 'kk-bulk-date-updates'); ?></label>
                        </th>
                        <td>
                            <?php
                            $post_types = get_post_types(array('public' => true), 'objects');
                            foreach ($post_types as $post_type) {
                                if ($post_type->name !== 'attachment') {
                                    echo '<label>';
                                    echo '<input type="checkbox" name="post_types[]" value="' . esc_attr($post_type->name) . '" checked> ';
                                    echo esc_html($post_type->label);
                                    echo '</label><br>';
                                }
                            }
                            ?>
                            <p class="description"><?php _e('Select which post types to update.', 'kk-bulk-date-updates'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e('Date Fields', 'kk-bulk-date-updates'); ?></label>
                        </th>
                        <td>
                            <div class="kk-date-fields-container">
                                <label>
                                    <input type="checkbox" name="date_fields[]" value="post_date" id="date_field_published" checked>
                                    <?php _e('Published Date', 'kk-bulk-date-updates'); ?>
                                </label>
                                
                                <label>
                                    <input type="checkbox" name="date_fields[]" value="post_modified" id="date_field_modified">
                                    <?php _e('Modified Date', 'kk-bulk-date-updates'); ?>
                                    <span class="kk-modified-date-offset">
                                        <?php _e('with', 'kk-bulk-date-updates'); ?>
                                        <input type="number" name="modified_date_offset" id="modified_date_offset" min="0" max="1440" value="5" class="small-text">
                                        <?php _e('minutes offset', 'kk-bulk-date-updates'); ?>
                                    </span>
                                </label>
                            </div>
                            
                            <p class="description"><?php _e('Select which date fields to update.', 'kk-bulk-date-updates'); ?></p>
                            <p class="description kk-match-method-note" style="display: none; color: #0073aa; font-style: italic;"><?php _e('Both date fields are automatically selected and locked when using "Match Modified to Published" method.', 'kk-bulk-date-updates'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="update_method"><?php _e('Update Method', 'kk-bulk-date-updates'); ?></label>
                        </th>
                        <td>
                            <select name="update_method" id="update_method">
                                <option value="add_days"><?php _e('Add Days', 'kk-bulk-date-updates'); ?></option>
                                <option value="subtract_days"><?php _e('Subtract Days', 'kk-bulk-date-updates'); ?></option>
                                <option value="match_modified_to_published"><?php _e('Match Modified to Published', 'kk-bulk-date-updates'); ?></option>
                                <option value="specific_date"><?php _e('Set Specific Date (TBA)', 'kk-bulk-date-updates'); ?></option>
                                <option value="random_range"><?php _e('Random Date Range (TBA)', 'kk-bulk-date-updates'); ?></option>
                            </select>
                            <p class="description"><?php _e('Choose how to update the dates.', 'kk-bulk-date-updates'); ?></p>
                        </td>
                    </tr>
                    
                    <tr id="specific_date_row">
                        <th scope="row">
                            <label for="specific_date"><?php _e('Specific Date', 'kk-bulk-date-updates'); ?></label>
                        </th>
                        <td>
                            <input type="date" name="specific_date" id="specific_date" class="regular-text">
                            <input type="time" name="specific_time" id="specific_time" value="12:00">
                            <p class="description"><?php _e('Set the exact date and time.', 'kk-bulk-date-updates'); ?></p>
                        </td>
                    </tr>
                    
                    <tr id="days_row" style="display: none;">
                        <th scope="row">
                            <label for="days_value"><?php _e('Number of Days', 'kk-bulk-date-updates'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="days_value" id="days_value" min="1" max="3650" value="1" class="small-text">
                            <p class="description"><?php _e('Number of days to add or subtract.', 'kk-bulk-date-updates'); ?></p>
                        </td>
                    </tr>
                    
                    <tr id="date_range_row" style="display: none;">
                        <th scope="row">
                            <label for="start_date"><?php _e('Date Range', 'kk-bulk-date-updates'); ?></label>
                        </th>
                        <td>
                            <input type="date" name="start_date" id="start_date" class="regular-text">
                            <span><?php _e('to', 'kk-bulk-date-updates'); ?></span>
                            <input type="date" name="end_date" id="end_date" class="regular-text">
                            <p class="description"><?php _e('Posts will be assigned random dates within this range.', 'kk-bulk-date-updates'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="post_status"><?php _e('Post Status', 'kk-bulk-date-updates'); ?></label>
                        </th>
                        <td>
                            <select name="post_status[]" id="post_status" multiple size="4">
                                <option value="publish" selected><?php _e('Published', 'kk-bulk-date-updates'); ?></option>
                                <option value="draft"><?php _e('Draft', 'kk-bulk-date-updates'); ?></option>
                                <option value="private"><?php _e('Private', 'kk-bulk-date-updates'); ?></option>
                                <option value="pending"><?php _e('Pending Review', 'kk-bulk-date-updates'); ?></option>
                            </select>
                            <p class="description"><?php _e('OTHER POST TYPES UNTESTED! Select which post statuses to include. Hold Ctrl/Cmd to select multiple.', 'kk-bulk-date-updates'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="limit_posts"><?php _e('Limit Posts', 'kk-bulk-date-updates'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="limit_posts" id="limit_posts" min="1" max="10000" value="100" class="small-text">
                            <p class="description"><?php _e('Maximum number of posts to update (for safety).', 'kk-bulk-date-updates'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="dry_run"><?php _e('Test Mode', 'kk-bulk-date-updates'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="dry_run" id="dry_run" value="1" checked>
                                <?php _e('Enable test mode (preview changes without updating)', 'kk-bulk-date-updates'); ?>
                            </label>
                            <p class="description"><?php _e('Recommended: Test your settings first before making actual changes.', 'kk-bulk-date-updates'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div class="kk-bulk-date-updates-progress" style="display: none;">
                <div class="kk-bulk-date-updates-progress-bar">
                    <div class="kk-bulk-date-updates-progress-fill"></div>
                </div>
            </div>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary kk-bulk-date-updates-button" value="<?php _e('Preview Changes', 'kk-bulk-date-updates'); ?>">
                <button type="button" class="button kk-reset-button"><?php _e('Reset Form', 'kk-bulk-date-updates'); ?></button>
            </p>
        </form>
    </div>
    
    <div class="kk-bulk-date-updates-form" id="kk-recent-activity-section">
        <h2><?php _e('Recent Activity', 'kk-bulk-date-updates'); ?></h2>
        <?php /* <p><?php _e('Recent bulk date update operations will be displayed here after each operation.', 'kk-bulk-date-updates'); ?></p> */ ?>
        
        <div id="kk-activity-log-container">
            <table class="wp-list-table widefat striped" id="kk-activity-log-table">
                <thead>
                    <tr>
                        <th><?php _e('Post', 'kk-bulk-date-updates'); ?></th>
                        <th><?php _e('Fields Updated', 'kk-bulk-date-updates'); ?></th>
                        <th><?php _e('Method', 'kk-bulk-date-updates'); ?></th>
                    </tr>
                </thead>
                <tbody id="kk-activity-log-tbody">
                    <tr id="kk-no-activity-row">
                        <td colspan="3"><?php _e('No recent activity. Perform a bulk update operation to see results here.', 'kk-bulk-date-updates'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Show/hide fields based on update method
    $('#update_method').on('change', function() {
        const method = $(this).val();
        
        // Hide all conditional rows
        $('#specific_date_row, #days_row, #date_range_row').hide();
        
        // Handle Match Modified to Published special behavior
        const $publishedCheckbox = $('#date_field_published');
        const $modifiedCheckbox = $('#date_field_modified');
        const $offsetField = $('#modified_date_offset');
        
        if (method === 'match_modified_to_published') {
            // Check both checkboxes and disable them
            $publishedCheckbox.prop('checked', true).prop('disabled', true);
            $modifiedCheckbox.prop('checked', true).prop('disabled', true);
            
            // Keep offset field active and show its container
            $offsetField.prop('disabled', false);
            $offsetField.closest('span').show();
            
            // Show explanatory note
            $('.kk-match-method-note').show();
        } else {
            // Re-enable checkboxes for other methods
            $publishedCheckbox.prop('disabled', false);
            $modifiedCheckbox.prop('disabled', false);
            
            // Hide explanatory note
            $('.kk-match-method-note').hide();
            
            // Trigger the modified date checkbox change to handle offset field visibility
            toggleModifiedDateOffset();
        }
        
        // Show relevant row
        switch(method) {
            case 'specific_date':
                $('#specific_date_row').show();
                break;
            case 'add_days':
            case 'subtract_days':
                $('#days_row').show();
                break;
            case 'random_range':
                $('#date_range_row').show();
                break;
        }
    });
    
    // Handle modified date checkbox and offset field
    function toggleModifiedDateOffset() {
        const $modifiedCheckbox = $('#date_field_modified');
        const $offsetField = $('#modified_date_offset');
        const $offsetContainer = $offsetField.closest('span');
        
        if ($modifiedCheckbox.is(':checked')) {
            $offsetContainer.show();
            $offsetField.prop('disabled', false);
        } else {
            $offsetContainer.hide();
            $offsetField.prop('disabled', true);
        }
    }
    
    // Initialize offset field state
    toggleModifiedDateOffset();
    
    // Initialize update method state
    $('#update_method').trigger('change');
    
    // Handle modified date checkbox change
    $('#date_field_modified').on('change', toggleModifiedDateOffset);
    
    // Ensure at least one date field is selected
    $('input[name="date_fields[]"]').on('change', function() {
        // Don't interfere if checkboxes are disabled (Match Modified to Published mode)
        if ($(this).prop('disabled')) {
            return;
        }
        
        const checkedFields = $('input[name="date_fields[]"]:checked').length;
        if (checkedFields === 0) {
            // If no fields are checked, check the published date by default
            $('#date_field_published').prop('checked', true);
        }
    });
    
    // Update submit button text based on dry run
    $('#dry_run').on('change', function() {
        const $submitBtn = $('#submit');
        if ($(this).is(':checked')) {
            $submitBtn.val('<?php _e('Preview Changes', 'kk-bulk-date-updates'); ?>');
        } else {
            $submitBtn.val('<?php _e('Update Dates', 'kk-bulk-date-updates'); ?>');
        }
    });
});
</script> 