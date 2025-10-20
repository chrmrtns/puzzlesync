/**
 * PuzzleSync Translation UI JavaScript
 *
 * @package PuzzleSync
 * @since 1.1.0
 */

(function($) {
    'use strict';

    /**
     * Translation UI Handler
     */
    var PuzzleSyncTranslations = {

        /**
         * Initialize
         */
        init: function() {
            this.setupTabs();
            this.setupFieldTracking();
            this.setupAutoSave();
        },

        /**
         * Setup tab navigation
         */
        setupTabs: function() {
            $('.chrmrtns-puzzlesync-tab-button').on('click', function() {
                var $button = $(this);
                var lang = $button.data('lang');

                // Update active tab
                $('.chrmrtns-puzzlesync-tab-button').removeClass('active');
                $button.addClass('active');

                // Show corresponding panel
                $('.chrmrtns-puzzlesync-translation-panel').hide();
                $('.chrmrtns-puzzlesync-translation-panel[data-lang="' + lang + '"]').fadeIn(300);

                // Store active tab in session
                if (typeof(Storage) !== 'undefined') {
                    sessionStorage.setItem('puzzlesync_active_tab', lang);
                }
            });

            // Restore active tab from session
            if (typeof(Storage) !== 'undefined') {
                var activeTab = sessionStorage.getItem('puzzlesync_active_tab');
                if (activeTab) {
                    $('.chrmrtns-puzzlesync-tab-button[data-lang="' + activeTab + '"]').trigger('click');
                    return;
                }
            }

            // Activate first tab by default
            $('.chrmrtns-puzzlesync-tab-button').first().trigger('click');
        },

        /**
         * Setup field tracking to monitor translation progress
         */
        setupFieldTracking: function() {
            var self = this;

            // Track changes in translation fields
            $('.chrmrtns-puzzlesync-translation-panel input, .chrmrtns-puzzlesync-translation-panel textarea').on('input', function() {
                var $field = $(this);
                var $panel = $field.closest('.chrmrtns-puzzlesync-translation-panel');

                // Update translation status
                self.updateTranslationStatus($panel);
            });

            // Initial status update for all panels
            $('.chrmrtns-puzzlesync-translation-panel').each(function() {
                self.updateTranslationStatus($(this));
            });
        },

        /**
         * Update translation status for a panel
         */
        updateTranslationStatus: function($panel) {
            var totalFields = $panel.find('input[type="text"], textarea').not('[disabled]').length;
            var filledFields = 0;

            $panel.find('input[type="text"], textarea').not('[disabled]').each(function() {
                if ($(this).val().trim() !== '') {
                    filledFields++;
                }
            });

            var lang = $panel.data('lang');
            var $button = $('.chrmrtns-puzzlesync-tab-button[data-lang="' + lang + '"]');

            // Update button appearance based on progress
            if (filledFields === 0) {
                $button.removeClass('partial-translation complete-translation');
            } else if (filledFields < totalFields) {
                $button.addClass('partial-translation').removeClass('complete-translation');
            } else {
                $button.addClass('complete-translation').removeClass('partial-translation');
            }

            // Add progress indicator to button
            var progress = totalFields > 0 ? Math.round((filledFields / totalFields) * 100) : 0;
            var $progress = $button.find('.translation-progress');

            if ($progress.length === 0) {
                $button.append('<span class="translation-progress"></span>');
                $progress = $button.find('.translation-progress');
            }

            if (progress > 0 && progress < 100) {
                $progress.text(' (' + progress + '%)').show();
            } else {
                $progress.hide();
            }
        },

        /**
         * Setup auto-save functionality
         */
        setupAutoSave: function() {
            var saveTimer;
            var self = this;

            // Auto-save on field change (debounced)
            $('.chrmrtns-puzzlesync-translation-panel input, .chrmrtns-puzzlesync-translation-panel textarea').on('input', function() {
                clearTimeout(saveTimer);

                // Show saving indicator
                self.showSaveIndicator('saving');

                // Debounce save for 2 seconds
                saveTimer = setTimeout(function() {
                    // Note: Auto-save is disabled by default
                    // Users must manually save via the Publish/Update button
                    self.showSaveIndicator('ready');
                }, 2000);
            });
        },

        /**
         * Show save indicator
         */
        showSaveIndicator: function(state) {
            var $indicator = $('#puzzlesync-save-indicator');

            if ($indicator.length === 0) {
                $('.chrmrtns-puzzlesync-translations-wrapper').prepend(
                    '<div id="puzzlesync-save-indicator"></div>'
                );
                $indicator = $('#puzzlesync-save-indicator');
            }

            switch (state) {
                case 'saving':
                    $indicator.html('<span class="dashicons dashicons-update spinning"></span> Preparing changes...').show();
                    break;
                case 'saved':
                    $indicator.html('<span class="dashicons dashicons-yes"></span> Translations saved!').show();
                    setTimeout(function() {
                        $indicator.fadeOut();
                    }, 3000);
                    break;
                case 'ready':
                    $indicator.html('<span class="dashicons dashicons-edit"></span> Ready to save').show();
                    setTimeout(function() {
                        $indicator.fadeOut();
                    }, 2000);
                    break;
                case 'error':
                    $indicator.html('<span class="dashicons dashicons-warning"></span> Error saving translations').show();
                    break;
            }
        },

        /**
         * Copy text from original to translation field
         */
        copyOriginalText: function($button) {
            var $row = $button.closest('.chrmrtns-puzzlesync-field-row');
            var originalText = $row.find('.original-value').text().trim();
            var $input = $row.find('input[type="text"], textarea');

            $input.val(originalText).trigger('input');
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if translation wrapper exists
        if ($('.chrmrtns-puzzlesync-translations-wrapper').length > 0) {
            PuzzleSyncTranslations.init();
        }
    });

    // Make it globally accessible
    window.PuzzleSyncTranslations = PuzzleSyncTranslations;

})(jQuery);
