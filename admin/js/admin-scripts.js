/**
 * PressML Admin Scripts
 *
 * @package PressML
 */

(function($) {
    'use strict';

    // Initialize on document ready
    $(document).ready(function() {
        initTranslationGroupSync();
        initUrlValidation();
        initAutoComplete();
        initTabNavigation();
    });

    /**
     * Initialize translation group sync functionality
     */
    function initTranslationGroupSync() {
        // Already handled inline in the meta box PHP
        // This is a placeholder for additional JS functionality if needed
    }

    /**
     * Initialize URL validation
     */
    function initUrlValidation() {
        // Validate URLs on blur
        $('.chrmrtns-pml-meta-box input[type="url"]').on('blur', function() {
            var $input = $(this);
            var url = $input.val();

            if (url && !isValidUrl(url)) {
                $input.css('border-color', '#d63638');
                if (!$input.next('.puzzlesync-error').length) {
                    $input.after('<span class="puzzlesync-error" style="color: #d63638; font-size: 12px;">Please enter a valid URL</span>');
                }
            } else {
                $input.css('border-color', '');
                $input.next('.puzzlesync-error').remove();
            }
        });
    }

    /**
     * Check if URL is valid
     */
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    /**
     * Initialize auto-complete for translation groups
     */
    function initAutoComplete() {
        var $translationGroupInput = $('#chrmrtns_pml_translation_group');

        if ($translationGroupInput.length && typeof pressmlAdmin !== 'undefined' && pressmlAdmin.translationGroups) {
            // Create datalist for autocomplete
            var datalistId = 'puzzlesync-translation-groups';
            var $datalist = $('<datalist id="' + datalistId + '"></datalist>');

            $.each(pressmlAdmin.translationGroups, function(index, group) {
                $datalist.append('<option value="' + group + '">');
            });

            $translationGroupInput.after($datalist);
            $translationGroupInput.attr('list', datalistId);
        }
    }

    /**
     * Initialize tab navigation for settings page
     */
    function initTabNavigation() {
        var $tabs = $('.puzzlesync-settings-tabs');
        if (!$tabs.length) {
            return;
        }

        $tabs.find('a').on('click', function(e) {
            e.preventDefault();

            var $link = $(this);
            var target = $link.attr('href');

            // Update active tab
            $tabs.find('a').removeClass('nav-tab-active');
            $link.addClass('nav-tab-active');

            // Show/hide content
            $('.puzzlesync-settings-content').hide();
            $(target).show();

            // Update URL without reload
            if (history.pushState) {
                history.pushState(null, null, target);
            }
        });

        // Handle initial tab
        var hash = window.location.hash || '#general';
        $tabs.find('a[href="' + hash + '"]').trigger('click');
    }

    /**
     * Handle bulk operations
     */
    window.pressMlBulkOperation = function(operation) {
        if (!confirm('Are you sure you want to perform this bulk operation?')) {
            return;
        }

        var $button = $(event.target);
        $button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'chrmrtns_pml_bulk_operation',
                operation: operation,
                nonce: pressmlAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Operation failed: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    };

    /**
     * Export settings
     */
    window.pressMlExportSettings = function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'chrmrtns_pml_export_settings',
                nonce: pressmlAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var blob = new Blob([JSON.stringify(response.data, null, 2)], {type: 'application/json'});
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'puzzlesync-settings-' + new Date().toISOString().split('T')[0] + '.json';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                } else {
                    alert('Export failed');
                }
            }
        });
    };

    /**
     * Import settings
     */
    window.pressMlImportSettings = function() {
        var $fileInput = $('<input type="file" accept=".json">');

        $fileInput.on('change', function(e) {
            var file = e.target.files[0];
            if (!file) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function(e) {
                try {
                    var settings = JSON.parse(e.target.result);

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'chrmrtns_pml_import_settings',
                            settings: settings,
                            nonce: pressmlAdmin.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Settings imported successfully');
                                location.reload();
                            } else {
                                alert('Import failed: ' + response.data);
                            }
                        }
                    });
                } catch (error) {
                    alert('Invalid settings file');
                }
            };

            reader.readAsText(file);
        });

        $fileInput.trigger('click');
    };

})(jQuery);