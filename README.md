# KK Bulk Date Updates WordPress Plugin

A comprehensive WordPress plugin for bulk updating dates across posts and content with advanced features and safety measures.

## Features

- **Backend-Only Plugin**: Focused exclusively on admin functionality for bulk date updates
- **Multiple Update Methods**: 
  - ✅ Add/subtract days from existing dates
  - ✅ Match modified dates to published dates with offset
  - 🚧 Set specific dates (TBA)
  - 🚧 Assign random dates within a range (TBA)
- **Post Type Support**: Works with all public post types (posts, pages, custom post types)
- **Date Field Options**: Update published dates, modified dates, or both
- **Safety Features**: Test mode (dry run), post limits, and comprehensive validation
- **AJAX Interface**: Modern, responsive admin interface with progress indicators
- **Activity Logging**: Track recent bulk update operations with detailed information
- **Security**: Proper nonce verification, capability checks, and data sanitization
- **Performance Optimized**: Batch processing, memory management, and efficient database operations
- **Internationalization**: Ready for translation with proper text domains

## File Structure

```
kk-bulk-date-updates/
├── kk-bulk-date-updates.php    # Main plugin file
├── css/                        # Stylesheets
│   └── admin.css              # Admin interface styles
├── js/                         # JavaScript files
│   └── admin.js               # Admin functionality
├── includes/                   # PHP includes
│   └── admin/
│       └── admin-page.php     # Admin page template
├── languages/                  # Translation files (to be created)
└── README.md                  # This file
```

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Tools > Bulk Date Updates** to access the plugin

## Usage

### Admin Interface

The plugin adds a new page under **Tools > Bulk Date Updates** with the following options:

#### Post Selection
- **Post Types**: Select which post types to update (posts, pages, custom post types)
- **Post Status**: Choose which post statuses to include (published, draft, private, pending)
- **Limit Posts**: Set maximum number of posts to update (safety feature)

#### Date Update Options
- **Date Fields**: Choose between published date, modified date, or both
- **Modified Date Offset**: When updating modified dates, add a specified number of minutes offset
- **Update Method**: 
  - ✅ **Add Days**: Add a specified number of days to existing dates
  - ✅ **Subtract Days**: Subtract a specified number of days from existing dates
  - ✅ **Match Modified to Published**: Set modified dates to match published dates (with optional offset)
  - 🚧 **Set Specific Date**: Assign the same date/time to all selected posts (TBA)
  - 🚧 **Random Date Range**: Assign random dates within a specified range (TBA)

#### Safety Features
- **Test Mode**: Preview changes without actually updating (recommended)
- **Progress Indicator**: Visual feedback during bulk operations
- **Activity Log**: Track recent bulk update operations with detailed information

### JavaScript Functionality

#### Admin Features
- Dynamic form field visibility based on update method
- AJAX form submission with progress tracking
- Real-time validation and user feedback
- Loading indicators and error handling
- Form reset functionality

## WordPress Coding Standards

This plugin follows WordPress coding standards and best practices:

- **Security**: Proper nonce verification, capability checks, and data sanitization
- **Database**: Uses WordPress database abstraction layer (`$wpdb`) with prepared statements
- **Hooks**: Utilizes WordPress action and filter hooks for extensibility
- **Internationalization**: All strings are translatable using `__()` and `_e()` functions
- **Asset Management**: Proper enqueueing of scripts and styles
- **Error Handling**: Comprehensive error handling and logging
- **Performance**: Optimized database queries, batch processing, and memory management

## Development

### Key Classes and Functions

#### Main Plugin Class: `KK_Bulk_Date_Updates`
- Singleton pattern implementation
- Handles plugin lifecycle (activation, deactivation, uninstall)
- Manages admin interface and AJAX handlers
- Enqueues scripts and styles

#### Admin Interface
- Located in `includes/admin/admin-page.php`
- Provides comprehensive form for bulk date updates
- Includes JavaScript for dynamic form behavior

#### Admin CSS Classes
- `kk-bulk-date-updates-admin`: Main admin container
- `kk-bulk-date-updates-form`: Form styling
- `kk-bulk-date-updates-button`: Button styling
- `kk-bulk-date-updates-message`: Message notifications
- `kk-bulk-date-updates-progress`: Progress indicators

### Extending the Plugin

The plugin is designed to be extensible through WordPress hooks:

```php
// Add custom post types to the selection
add_filter('kk_bulk_date_updates_post_types', function($post_types) {
    $post_types['custom_type'] = 'Custom Type';
    return $post_types;
});

// Modify update methods
add_filter('kk_bulk_date_updates_methods', function($methods) {
    $methods['custom_method'] = 'Custom Method';
    return $methods;
});
```

## Security Considerations

- All AJAX requests are protected with nonces
- User capability checks (`manage_options`) are enforced
- Input data is properly sanitized and validated
- SQL queries use prepared statements
- Direct file access is prevented

## Performance

- Efficient database queries with proper indexing
- Batch processing for large datasets (handles 1000+ posts)
- Progress tracking for long-running operations
- Optimized asset loading (only on relevant admin pages)
- Memory management for large operations (requires 512MB+ for 1000+ posts)

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Responsive design for mobile devices
- Graceful degradation for older browsers

## Contributing

1. Follow WordPress coding standards
2. Include proper documentation
3. Add security checks for all user inputs
4. Test thoroughly before submitting

## License

GPL v2 or later

## Changelog

### Version 0.0.2 (Current)
- ✅ Add Days functionality
- ✅ Subtract Days functionality  
- ✅ Match Modified to Published functionality
- ✅ Test mode (dry run) with detailed preview
- ✅ Activity logging system
- ✅ Performance optimizations for large datasets
- ✅ Enhanced security and validation
- ✅ AJAX interface with progress tracking
- 🚧 Set Specific Date method (TBA)
- 🚧 Random Date Range method (TBA)

### Version 1.0.0 (Planned)
- 🚧 Set Specific Date functionality
- 🚧 Random Date Range functionality
- 🚧 Additional update methods
- 🚧 Enhanced filtering options

## Support

For support and feature requests, please contact the plugin author or submit issues through the appropriate channels.

## Legend
- ✅ Implemented and working
- 🚧 To Be Announced (TBA) - Not yet implemented 