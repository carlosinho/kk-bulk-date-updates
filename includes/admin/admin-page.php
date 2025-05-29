<?php
/**
 * Admin Page Template
 * 
 * @package KK_Bulk_Date_Updates
 * @version 0.1.0
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
                            <p class="description"><?php _e('Select which post types to update.', 'kk-bulk-date-updates'); ?> <?php _e('This plugin only works with published content. Draft, private, and pending posts are not affected.', 'kk-bulk-date-updates'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="content_filter"><?php _e('Content Filters', 'kk-bulk-date-updates'); ?></label>
                        </th>
                        <td>
                            <select name="content_filter" id="content_filter">
                                <option value="none"><?php _e('No Filter (All Content)', 'kk-bulk-date-updates'); ?></option>
                                <option value="date_range"><?php _e('Published Date Range', 'kk-bulk-date-updates'); ?></option>
                                <option value="count_limit"><?php _e('Latest/Oldest Posts', 'kk-bulk-date-updates'); ?></option>
                                <option value="category_tag"><?php _e('Category/Tag Filter', 'kk-bulk-date-updates'); ?></option>
                            </select>
                            <p class="description"><?php _e('Filter which content to include in the bulk update.', 'kk-bulk-date-updates'); ?></p>
                            
                            <!-- Date Range Filter -->
                            <div id="filter_date_range_options" class="kk-filter-options" style="display: none; margin-top: 10px;">
                                <label><?php _e('Published between:', 'kk-bulk-date-updates'); ?></label><br>
                                <input type="date" name="filter_date_start" id="filter_date_start" class="regular-text">
                                <span><?php _e('and', 'kk-bulk-date-updates'); ?></span>
                                <input type="date" name="filter_date_end" id="filter_date_end" class="regular-text">
                                <p class="description"><?php _e('Only update content published within this date range.', 'kk-bulk-date-updates'); ?></p>
                            </div>
                            
                            <!-- Count Limit Filter -->
                            <div id="filter_count_limit_options" class="kk-filter-options" style="display: none; margin-top: 10px;">
                                <label><?php _e('Update only:', 'kk-bulk-date-updates'); ?></label><br>
                                <input type="number" name="filter_count" id="filter_count" min="1" max="1000" value="10" class="small-text">
                                <label>
                                    <input type="radio" name="filter_order" value="latest" checked> <?php _e('Latest posts', 'kk-bulk-date-updates'); ?>
                                </label>
                                <label>
                                    <input type="radio" name="filter_order" value="oldest"> <?php _e('Oldest posts', 'kk-bulk-date-updates'); ?>
                                </label>
                                <p class="description"><?php _e('Limit the update to a specific number of latest or oldest posts.', 'kk-bulk-date-updates'); ?></p>
                            </div>
                            
                            <!-- Category/Tag Filter -->
                            <div id="filter_category_tag_options" class="kk-filter-options" style="display: none; margin-top: 10px;">
                                <div id="kk-taxonomy-selectors">
                                    <!-- Categories (for posts) -->
                                    <div class="kk-taxonomy-group" data-post-type="post">
                                        <label><?php _e('Categories:', 'kk-bulk-date-updates'); ?></label><br>
                                        <select name="filter_categories[]" id="filter_categories" multiple size="4" style="min-width: 300px;">
                                            <?php
                                            $categories = get_categories(array('hide_empty' => false));
                                            foreach ($categories as $category) {
                                                echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . ' (' . $category->count . ')</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="kk-taxonomy-group" data-post-type="post" style="margin-top: 10px;">
                                        <label><?php _e('Tags:', 'kk-bulk-date-updates'); ?></label><br>
                                        <select name="filter_tags[]" id="filter_tags" multiple size="4" style="min-width: 300px;">
                                            <?php
                                            $tags = get_tags(array('hide_empty' => false));
                                            foreach ($tags as $tag) {
                                                echo '<option value="' . esc_attr($tag->term_id) . '">' . esc_html($tag->name) . ' (' . $tag->count . ')</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Pages (no categories/tags by default) -->
                                    <div class="kk-taxonomy-group" data-post-type="page" style="display: none;">
                                        <p><em><?php _e('Pages do not have categories or tags by default.', 'kk-bulk-date-updates'); ?></em></p>
                                    </div>
                                </div>
                                <p class="description"><?php _e('Select categories or tags to filter content. Hold Ctrl/Cmd to select multiple.', 'kk-bulk-date-updates'); ?></p>
                            </div>
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
                            <label for="limit_posts"><?php _e('Limit Posts', 'kk-bulk-date-updates'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="limit_posts" id="limit_posts" min="1" max="10000" value="100" class="small-text">
                            <p class="description"><?php _e('Maximum number of posts to update (for safety). This applies after content filters.', 'kk-bulk-date-updates'); ?></p>
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
    
    // Handle content filter changes
    $('#content_filter').on('change', function() {
        const filterType = $(this).val();
        
        // Hide all filter options
        $('.kk-filter-options').hide();
        
        // Show relevant filter options
        switch(filterType) {
            case 'date_range':
                $('#filter_date_range_options').show();
                break;
            case 'count_limit':
                $('#filter_count_limit_options').show();
                break;
            case 'category_tag':
                $('#filter_category_tag_options').show();
                updateTaxonomyVisibility();
                break;
        }
    });
    
    // Handle post type changes to show/hide relevant taxonomies
    $('input[name="post_types[]"]').on('change', function() {
        updateTaxonomyVisibility();
    });
    
    function updateTaxonomyVisibility() {
        const selectedPostTypes = $('input[name="post_types[]"]:checked').map(function() {
            return this.value;
        }).get();
        
        // Hide all taxonomy groups
        $('.kk-taxonomy-group').hide();
        
        // Show taxonomy groups for selected post types
        selectedPostTypes.forEach(function(postType) {
            $('.kk-taxonomy-group[data-post-type="' + postType + '"]').show();
        });
        
        // If no post types are selected or only non-post types, show a message
        if (selectedPostTypes.length === 0 || (selectedPostTypes.length === 1 && !selectedPostTypes.includes('post'))) {
            if (selectedPostTypes.includes('page') && selectedPostTypes.length === 1) {
                $('.kk-taxonomy-group[data-post-type="page"]').show();
            }
        }
    }
    
    // Initialize content filter state
    $('#content_filter').trigger('change');
    
    // Initialize taxonomy visibility
    updateTaxonomyVisibility();
});
</script> 