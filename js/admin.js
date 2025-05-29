/**
 * KK Bulk Date Updates - Admin JavaScript
 * 
 * @package KK_Bulk_Date_Updates
 * @version 0.1.0
 */

(function($) {
    'use strict';

    /**
     * Admin functionality object
     */
    const KKBulkDateUpdatesAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initComponents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Form submission - prevent any form submission
            $(document).on('submit', '.kk-bulk-date-updates-form', this.handleFormSubmit.bind(this));
            
            // Submit button clicks - handle before form submission
            $(document).on('click', '.kk-bulk-date-updates-button', this.handleSubmitClick.bind(this));
            
            // Reset functionality
            $(document).on('click', '.kk-reset-button', this.handleReset.bind(this));
        },

        /**
         * Initialize components
         */
        initComponents: function() {
            // Initialize date pickers if available
            if ($.fn.datepicker) {
                $('.kk-date-picker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }

            // Initialize tooltips
            this.initTooltips();
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                const $this = $(this);
                const title = $this.data('tooltip');
                
                $this.attr('title', title).tooltip({
                    position: { my: "left+15 center", at: "right center" },
                    content: title
                });
            });
        },

        /**
         * Handle submit button click
         */
        handleSubmitClick: function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const $button = $(e.target);
            const $form = $button.closest('form');
            
            this.submitForm($form, $button);
            
            return false;
        },

        /**
         * Handle form submission
         */
        handleFormSubmit: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // This should not be reached if button click is handled properly
            return false;
        },

        /**
         * Submit form via AJAX
         */
        submitForm: function($form, $submitButton) {
            const formData = new FormData($form[0]);
            
            // Add action and nonce
            formData.append('action', 'kk_bulk_date_updates_action');
            formData.append('nonce', kkBulkDateUpdates.nonce);
            
            // Disable submit button and show loading
            $submitButton.prop('disabled', true);
            this.showLoading($submitButton);
            
            // Clear previous messages and preview
            this.clearMessages();
            this.clearPreview();
            
            // Store reference to this for use in callbacks
            const self = this;
            
            // Make AJAX request
            $.ajax({
                url: kkBulkDateUpdates.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    self.handleAjaxSuccess(response);
                },
                error: function(xhr, status, error) {
                    self.handleAjaxError(xhr, status, error);
                },
                complete: function() {
                    // Always re-enable button and hide loading, regardless of success/failure
                    $submitButton.prop('disabled', false);
                    self.hideLoading($submitButton);
                }
            });
        },

        /**
         * Handle reset functionality
         */
        handleReset: function(e) {
            e.preventDefault();
            
            if (confirm(kkBulkDateUpdates.strings.confirmReset || 'Are you sure you want to reset all fields?')) {
                const $form = $(e.target).closest('form');
                $form[0].reset();
                this.clearMessages();
                this.clearPreview();
                
                // Trigger change events to reset UI state
                $form.find('#update_method').trigger('change');
                $form.find('#dry_run').trigger('change');
            }
        },

        /**
         * Perform specific action
         */
        performAction: function(action, $button) {
            const data = {
                action: 'kk_bulk_date_updates_action',
                nonce: kkBulkDateUpdates.nonce,
                bulk_action: action
            };
            
            $button.prop('disabled', true);
            this.showLoading($button);
            
            $.ajax({
                url: kkBulkDateUpdates.ajaxUrl,
                type: 'POST',
                data: data,
                success: this.handleAjaxSuccess.bind(this),
                error: this.handleAjaxError.bind(this),
                complete: function() {
                    $button.prop('disabled', false);
                    KKBulkDateUpdatesAdmin.hideLoading($button);
                }
            });
        },

        /**
         * Handle AJAX success
         */
        handleAjaxSuccess: function(response) {
            if (response.success) {
                // Always show message below the form for consistency
                this.showMessage(response.data.message || kkBulkDateUpdates.strings.success, 'success', true);
                
                // Handle preview data
                if (response.data && response.data.preview_html) {
                    this.displayPreview(response.data.preview_html);
                }
                
                // Handle activity log data
                if (response.data && response.data.activity_log) {
                    this.displayActivityLog(response.data.activity_log);
                }
                
                // Handle specific response data
                if (response.data && response.data.redirect) {
                    window.location.href = response.data.redirect;
                }
                
                if (response.data && response.data.reload) {
                    window.location.reload();
                }
                
                // Update progress if available
                if (response.data && response.data.progress) {
                    this.updateProgress(response.data.progress);
                }
            } else {
                // Show error messages below form as well - handle both data.message and direct message
                const errorMessage = (response.data && response.data.message) || response.message || kkBulkDateUpdates.strings.error;
                this.showMessage(errorMessage, 'error', true);
            }
        },

        /**
         * Handle AJAX error
         */
        handleAjaxError: function(xhr, status, error) {
            console.error('AJAX Error:', status, error, xhr.responseText);
            
            let errorMessage = kkBulkDateUpdates.strings.error;
            
            // Try to get more specific error message
            if (xhr.responseText) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.data && response.data.message) {
                        errorMessage = response.data.message;
                    } else if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    // If parsing fails, check for common HTTP errors
                    if (xhr.status === 403) {
                        errorMessage = 'Permission denied. Please refresh the page and try again.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error occurred. Please try again.';
                    } else if (xhr.status === 0) {
                        errorMessage = 'Network connection error. Please check your connection and try again.';
                    }
                }
            }
            
            this.showMessage(errorMessage, 'error', true);
        },

        /**
         * Show loading indicator
         */
        showLoading: function($element) {
            const originalText = $element.text();
            $element.data('original-text', originalText);
            $element.html('<span class="kk-bulk-date-updates-spinner"></span>' + kkBulkDateUpdates.strings.processing);
        },

        /**
         * Hide loading indicator
         */
        hideLoading: function($element) {
            const originalText = $element.data('original-text');
            if (originalText) {
                $element.text(originalText);
            }
        },

        /**
         * Show message
         */
        showMessage: function(message, type, belowForm) {
            const $message = $('<div class="kk-bulk-date-updates-message ' + type + '">' + message + '</div>');
            
            // Remove existing messages
            this.clearMessages();
            
            if (belowForm) {
                // Insert message below the form (after submit buttons)
                const $submitSection = $('.kk-bulk-date-updates-form .submit');
                if ($submitSection.length) {
                    $submitSection.after($message);
                } else {
                    // Fallback: after the form
                    $('.kk-bulk-date-updates-form').first().after($message);
                }
            } else {
                // Insert at top of container (fallback behavior)
                const $container = $('.kk-bulk-date-updates-admin');
                $container.prepend($message);
                
                // Scroll to message only for top placement
                $('html, body').animate({
                    scrollTop: $message.offset().top - 50
                }, 300);
            }
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(function() {
                    $message.fadeOut();
                }, 5000);
            }
        },

        /**
         * Clear messages
         */
        clearMessages: function() {
            $('.kk-bulk-date-updates-message').remove();
        },

        /**
         * Clear preview
         */
        clearPreview: function() {
            $('.kk-preview-container').remove();
        },

        /**
         * Update progress bar
         */
        updateProgress: function(progress) {
            const $progressContainer = $('.kk-bulk-date-updates-progress');
            const $progressFill = $('.kk-bulk-date-updates-progress-fill');
            
            if ($progressContainer.length === 0) {
                return;
            }
            
            $progressContainer.show();
            $progressFill.css('width', progress + '%');
            
            if (progress >= 100) {
                setTimeout(function() {
                    $progressContainer.fadeOut();
                }, 2000);
            }
        },

        /**
         * Display preview results
         */
        displayPreview: function(previewHtml) {
            // Remove any existing preview
            $('.kk-preview-container').remove();
            
            // Create preview container
            const $previewContainer = $('<div class="kk-preview-container"></div>');
            $previewContainer.html(previewHtml);
            
            // Insert after the form
            $('.kk-bulk-date-updates-form').first().after($previewContainer);
            
            // Smooth scroll to preview (with a slight delay to avoid conflicts)
            setTimeout(function() {
                $('html, body').animate({
                    scrollTop: $previewContainer.offset().top - 20
                }, 400);
            }, 100);
        },

        /**
         * Display activity log
         */
        displayActivityLog: function(activityLog) {
            const $tbody = $('#kk-activity-log-tbody');
            const $noActivityRow = $('#kk-no-activity-row');
            
            if (!$tbody.length) {
                return;
            }
            
            // Clear existing rows except the "no activity" row
            $tbody.find('tr:not(#kk-no-activity-row)').remove();
            
            if (activityLog && activityLog.length > 0) {
                // Hide the "no activity" row
                $noActivityRow.hide();
                
                // Add new activity log entries
                activityLog.forEach(function(entry) {
                    const $row = $('<tr></tr>');
                    
                    // Format updated fields with date changes
                    let fieldsHtml = '';
                    if (entry.date_changes) {
                        const changes = [];
                        
                        if (entry.date_changes.post_date) {
                            const change = entry.date_changes.post_date;
                            changes.push('<div class="date-change"><strong>Published:</strong><br>' + change.old + ' → ' + change.new + '</div>');
                        }
                        
                        if (entry.date_changes.post_modified) {
                            const change = entry.date_changes.post_modified;
                            changes.push('<div class="date-change"><strong>Modified:</strong><br>' + change.old + ' → ' + change.new + '</div>');
                        }
                        
                        fieldsHtml = changes.join('');
                    } else {
                        // Fallback to simple field names if no date changes available
                        fieldsHtml = entry.updated_fields.map(function(field) {
                            return field === 'post_date' ? 'Published Date' : 'Modified Date';
                        }).join(', ');
                    }
                    
                    // Format update method
                    const methodText = entry.update_method.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    
                    $row.html(
                        '<td><strong>' + entry.post_title + '</strong><br><small>ID: ' + entry.post_id + ' (' + entry.post_type + ')</small></td>' +
                        '<td>' + fieldsHtml + '</td>' +
                        '<td>' + methodText + '</td>'
                    );
                    
                    $tbody.append($row);
                });
                
                // Scroll to activity log section
                setTimeout(function() {
                    $('html, body').animate({
                        scrollTop: $('#kk-recent-activity-section').offset().top - 20
                    }, 400);
                }, 100);
            } else {
                // Show the "no activity" row if no entries
                $noActivityRow.show();
            }
        },

        /**
         * Utility: Debounce function
         */
        debounce: function(func, wait, immediate) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        KKBulkDateUpdatesAdmin.init();
    });

    // Make object globally available
    window.KKBulkDateUpdatesAdmin = KKBulkDateUpdatesAdmin;

})(jQuery); 