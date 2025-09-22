<?php
/**
 * Admin functionality class for PressML plugin
 *
 * @package PressML
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chrmrtns_Pml_Admin {

    private $db;

    public function __construct() {
        $this->db = new Chrmrtns_Pml_Database();
    }

    public function init() {
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box_data'));

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Add columns to post/page list
        add_filter('manage_posts_columns', array($this, 'add_hreflang_column'));
        add_filter('manage_pages_columns', array($this, 'add_hreflang_column'));
        add_action('manage_posts_custom_column', array($this, 'render_hreflang_column'), 10, 2);
        add_action('manage_pages_custom_column', array($this, 'render_hreflang_column'), 10, 2);

        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // AJAX handlers
        add_action('wp_ajax_chrmrtns_pml_sync_translation_group', array($this, 'ajax_sync_translation_group'));
        add_action('wp_ajax_chrmrtns_pml_validate_urls', array($this, 'ajax_validate_urls'));
    }

    /**
     * Add meta boxes to post edit screen
     */
    public function add_meta_boxes() {
        add_meta_box(
            'chrmrtns_pml_hreflang_meta',
            __('PressML - Multilanguage Settings', 'pressml'),
            array($this, 'render_meta_box'),
            array('post', 'page'),
            'normal',
            'high'
        );
    }

    /**
     * Render the meta box content
     */
    public function render_meta_box($post) {
        wp_nonce_field('chrmrtns_pml_meta_box', 'chrmrtns_pml_meta_box_nonce');

        // Get existing data
        $hreflang_data = $this->db->get_hreflang_by_post($post->ID);
        $translation_group = get_post_meta($post->ID, 'chrmrtns_pml_translation_group', true);
        $default_lang = get_post_meta($post->ID, 'chrmrtns_pml_hreflang_default', true);

        // Prepare data for display
        $urls = array();
        foreach ($hreflang_data as $item) {
            $urls[$item->language_code] = $item->url;
        }
        ?>
        <style>
            .chrmrtns-pml-meta-box table { width: 100%; }
            .chrmrtns-pml-meta-box td { padding: 10px 5px; }
            .chrmrtns-pml-meta-box input[type="url"],
            .chrmrtns-pml-meta-box input[type="text"] { width: 100%; }
            .chrmrtns-pml-info-box {
                background: #f0f0f1;
                padding: 15px;
                margin-top: 20px;
                border-left: 4px solid #2271b1;
            }
        </style>

        <div class="chrmrtns-pml-meta-box">
            <table class="form-table">
                <?php
                $supported_languages = $this->get_supported_languages();
                foreach ($supported_languages as $lang):
                ?>
                <tr>
                    <td style="width: 120px;">
                        <label for="chrmrtns_pml_hreflang_<?php echo esc_attr($lang['code']); ?>">
                            <strong>
                                <?php echo esc_html($lang['flag']); ?>
                                <?php
                                // translators: %s: Language name
                                echo esc_html(sprintf(__('%s URL:', 'pressml'), $lang['name']));
                                ?>
                            </strong>
                        </label>
                    </td>
                    <td>
                        <input type="url"
                               id="chrmrtns_pml_hreflang_<?php echo esc_attr($lang['code']); ?>"
                               name="chrmrtns_pml_hreflang_<?php echo esc_attr($lang['code']); ?>"
                               value="<?php echo esc_attr(isset($urls[$lang['code']]) ? $urls[$lang['code']] : ''); ?>"
                               placeholder="https://example.com/<?php echo esc_attr(strtolower($lang['name'])); ?>-version" />
                    </td>
                </tr>
                <?php endforeach; ?>

                <tr>
                    <td><label for="chrmrtns_pml_translation_group"><strong><?php esc_html_e('Translation Group:', 'pressml'); ?></strong></label></td>
                    <td>
                        <input type="text" id="chrmrtns_pml_translation_group" name="chrmrtns_pml_translation_group"
                               value="<?php echo esc_attr($translation_group); ?>"
                               placeholder="e.g. product-launch-2024" />
                        <br><small><?php esc_html_e('Groups related translations for automatic linking', 'pressml'); ?></small>
                        <br><button type="button" id="chrmrtns_pml_sync_group" class="button" style="margin-top: 5px;">
                            <?php esc_html_e('Sync from Translation Group', 'pressml'); ?>
                        </button>
                    </td>
                </tr>

                <tr>
                    <td><label for="chrmrtns_pml_hreflang_default"><strong><?php esc_html_e('x-default Language:', 'pressml'); ?></strong></label></td>
                    <td>
                        <select id="chrmrtns_pml_hreflang_default" name="chrmrtns_pml_hreflang_default">
                            <option value=""><?php esc_html_e('Automatic', 'pressml'); ?></option>
                            <?php foreach ($supported_languages as $lang): ?>
                            <option value="<?php echo esc_attr($lang['code']); ?>" <?php selected($default_lang, $lang['code']); ?>>
                                <?php echo esc_html($lang['flag'] . ' ' . $lang['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <br><small><?php esc_html_e('Default language for international users', 'pressml'); ?></small>
                    </td>
                </tr>
            </table>

            <div class="chrmrtns-pml-info-box">
                <h4 style="margin-top: 0;"><?php esc_html_e('Automatic Linking', 'pressml'); ?></h4>
                <p><?php echo wp_kses_post(__('<strong>Alternative to manual URLs:</strong> Use categories (language names) + Translation Groups for automatic linking.', 'pressml')); ?></p>
                <p><?php esc_html_e('For each language, create categories or tags with the language name (e.g., "english", "français", "deutsch") or version tags (e.g., "english-version", "français-version").', 'pressml'); ?></p>
                <p><?php echo wp_kses_post(__('<strong>Priority:</strong> Database entries → Custom Fields → Categories + Translation Groups', 'pressml')); ?></p>
                <p><?php echo wp_kses_post(__('<strong>Single language posts:</strong> Leave empty - no hreflang tags will be set!', 'pressml')); ?></p>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#chrmrtns_pml_sync_group').on('click', function(e) {
                e.preventDefault();

                var translationGroup = $('#chrmrtns_pml_translation_group').val();
                if (!translationGroup) {
                    alert('<?php echo esc_js(__('Please enter a translation group first!', 'pressml')); ?>');
                    return;
                }

                var button = $(this);
                button.prop('disabled', true).text('<?php echo esc_js(__('Syncing...', 'pressml')); ?>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'chrmrtns_pml_sync_translation_group',
                        translation_group: translationGroup,
                        post_id: <?php echo (int) $post->ID; ?>,
                        nonce: '<?php echo esc_attr(wp_create_nonce('chrmrtns_pml_sync')); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var urlsUpdated = 0;
                            // Loop through all returned URLs and populate corresponding fields
                            $.each(response.data, function(key, url) {
                                if (key.endsWith('_url')) {
                                    var langCode = key.replace('_url', '');
                                    var fieldId = '#chrmrtns_pml_hreflang_' + langCode;
                                    if ($(fieldId).length) {
                                        $(fieldId).val(url);
                                        urlsUpdated++;
                                    }
                                }
                            });

                            if (urlsUpdated > 0) {
                                alert('<?php echo esc_js(__('Translation URLs synchronized!', 'pressml')); ?>' + ' (' + urlsUpdated + ' <?php echo esc_js(__('fields updated', 'pressml')); ?>)');
                            } else {
                                alert('<?php echo esc_js(__('No matching URL fields found to update', 'pressml')); ?>');
                            }
                        } else {
                            alert(response.data || '<?php echo esc_js(__('Sync failed', 'pressml')); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('AJAX error occurred', 'pressml')); ?>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('<?php echo esc_js(__('Sync from Translation Group', 'pressml')); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Save meta box data
     */
    public function save_meta_box_data($post_id) {
        // Security checks
        if (!isset($_POST['chrmrtns_pml_meta_box_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_pml_meta_box_nonce'])), 'chrmrtns_pml_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save translation group
        $translation_group = '';
        if (isset($_POST['chrmrtns_pml_translation_group'])) {
            $translation_group = sanitize_text_field(wp_unslash($_POST['chrmrtns_pml_translation_group']));
            if (!empty($translation_group)) {
                update_post_meta($post_id, 'chrmrtns_pml_translation_group', $translation_group);
            } else {
                delete_post_meta($post_id, 'chrmrtns_pml_translation_group');
                $translation_group = '';
            }
        } else {
            // If not in POST, get existing translation group
            $translation_group = get_post_meta($post_id, 'chrmrtns_pml_translation_group', true);
        }

        // Save default language preference
        if (isset($_POST['chrmrtns_pml_hreflang_default'])) {
            $default_lang = sanitize_text_field(wp_unslash($_POST['chrmrtns_pml_hreflang_default']));
            if (!empty($default_lang)) {
                update_post_meta($post_id, 'chrmrtns_pml_hreflang_default', $default_lang);
            } else {
                delete_post_meta($post_id, 'chrmrtns_pml_hreflang_default');
            }
        }

        // Save hreflang URLs to database
        $supported_languages = $this->get_supported_languages();
        foreach ($supported_languages as $lang_info) {
            $lang = $lang_info['code'];
            $field_name = 'chrmrtns_pml_hreflang_' . $lang;
            if (isset($_POST[$field_name])) {
                $url = sanitize_url(wp_unslash($_POST[$field_name]));
                if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                    $this->db->insert_or_update_hreflang(
                        $post_id,
                        $lang,
                        $url,
                        isset($translation_group) ? $translation_group : null,
                        1 // Priority 1 for manually entered URLs
                    );
                } else {
                    $this->db->delete_hreflang_entry($post_id, $lang);
                }
            }
        }

        // Update x-default if needed
        if (isset($_POST['chrmrtns_pml_hreflang_default']) && !empty($_POST['chrmrtns_pml_hreflang_default'])) {
            $this->db->set_x_default($post_id, sanitize_text_field(wp_unslash($_POST['chrmrtns_pml_hreflang_default'])));
        }

        // Auto-update other posts in the same translation group
        if (!empty($translation_group)) {
            // First, update other posts with this post's URL
            $this->update_translation_group_posts($post_id, $translation_group);

            // Then, retrieve URLs from other posts in the group for this post
            $this->sync_translation_group_urls($post_id, $translation_group);
        }
    }

    /**
     * Auto-update other posts in the same translation group
     */
    private function update_translation_group_posts($current_post_id, $translation_group) {
        // Get the current post's URL and detected language
        $current_post_url = get_permalink($current_post_id);
        $current_post = get_post($current_post_id);

        if (!$current_post || !$current_post_url) {
            return;
        }

        // Detect the language of the current post
        $current_language = null;
        $supported_languages = $this->get_supported_languages();

        foreach ($supported_languages as $lang) {
            // Use mb_strtolower for proper UTF-8 handling
            $lang_name_lower = function_exists('mb_strtolower') ? mb_strtolower($lang['name'], 'UTF-8') : strtolower($lang['name']);
            $lang_name_cap = function_exists('mb_convert_case') ? mb_convert_case($lang['name'], MB_CASE_TITLE, 'UTF-8') : ucfirst($lang_name_lower);
            $lang_name_orig = $lang['name'];

            // Also check for common variations
            $variations = array(
                $lang_name_lower,
                $lang_name_cap,
                $lang_name_orig,
                $lang['code'], // Also check for language code as category/tag
                strtolower($lang['code']),
                strtoupper($lang['code'])
            );

            $version_variations = array();
            foreach ($variations as $var) {
                $version_variations[] = $var . '-version';
                $version_variations[] = $var . '_version';
                $version_variations[] = $var;
            }

            if (has_category($version_variations, $current_post) ||
                has_tag($version_variations, $current_post)) {
                $current_language = $lang['code'];
                break;
            }
        }

        if (!$current_language) {
            return; // Could not detect language of current post
        }

        // Find all other posts in the same translation group
        $related_posts = get_posts(array(
            'post_type' => array('post', 'page'),
            'post_status' => 'any',
            'posts_per_page' => -1,
            'exclude' => array($current_post_id), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
            'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                array(
                    'key' => 'chrmrtns_pml_translation_group',
                    'value' => $translation_group,
                    'compare' => '='
                )
            )
        ));

        // Update each related post with the current post's URL for the detected language
        foreach ($related_posts as $related_post) {
            $this->db->insert_or_update_hreflang(
                $related_post->ID,
                $current_language,
                $current_post_url,
                $translation_group,
                2 // Priority 2 for auto-updated URLs (lower than manual entries)
            );
        }
    }

    /**
     * Sync URLs from other posts in the translation group to the current post
     */
    private function sync_translation_group_urls($current_post_id, $translation_group) {
        // Find all other posts in the same translation group
        $related_posts = get_posts(array(
            'post_type' => array('post', 'page'),
            'post_status' => 'any',
            'posts_per_page' => -1,
            'exclude' => array($current_post_id), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
            'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                array(
                    'key' => 'chrmrtns_pml_translation_group',
                    'value' => $translation_group,
                    'compare' => '='
                )
            )
        ));

        $supported_languages = $this->get_supported_languages();

        // Process each related post to get its URL and language
        foreach ($related_posts as $related_post) {
            $related_post_url = get_permalink($related_post->ID);

            // Detect the language of the related post
            $related_language = null;

            foreach ($supported_languages as $lang) {
                // Use mb_strtolower for proper UTF-8 handling
                $lang_name_lower = function_exists('mb_strtolower') ? mb_strtolower($lang['name'], 'UTF-8') : strtolower($lang['name']);
                $lang_name_cap = function_exists('mb_convert_case') ? mb_convert_case($lang['name'], MB_CASE_TITLE, 'UTF-8') : ucfirst($lang_name_lower);
                $lang_name_orig = $lang['name'];

                // Also check for common variations
                $variations = array(
                    $lang_name_lower,
                    $lang_name_cap,
                    $lang_name_orig,
                    $lang['code'], // Also check for language code as category/tag
                    strtolower($lang['code']),
                    strtoupper($lang['code'])
                );

                $version_variations = array();
                foreach ($variations as $var) {
                    $version_variations[] = $var . '-version';
                    $version_variations[] = $var . '_version';
                    $version_variations[] = $var;
                }

                if (has_category($version_variations, $related_post) ||
                    has_tag($version_variations, $related_post)) {
                    $related_language = $lang['code'];
                    break;
                }
            }

            // If we detected the language, save the URL for that language in the current post
            if ($related_language && $related_post_url) {
                // Check if there's already a manual entry for this language
                $field_name = 'chrmrtns_pml_hreflang_' . $related_language;

                // Only auto-update if there's no manual entry for this language
                // phpcs:ignore WordPress.Security.NonceVerification.Missing
                if (empty($_POST[$field_name]) || empty(sanitize_url(wp_unslash($_POST[$field_name])))) {
                    $this->db->insert_or_update_hreflang(
                        $current_post_id,
                        $related_language,
                        $related_post_url,
                        $translation_group,
                        3 // Priority 3 for auto-synced URLs (lowest priority)
                    );
                }
            }
        }
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            __('PressML', 'pressml'),
            __('PressML', 'pressml'),
            'manage_options',
            'pressml-settings',
            array($this, 'render_settings_page'),
            $this->get_menu_icon(),
            30
        );

        add_submenu_page(
            'pressml-settings',
            __('Validator', 'pressml'),
            __('Validator', 'pressml'),
            'manage_options',
            'pressml-validator',
            array($this, 'render_validator_page')
        );

        add_submenu_page(
            'pressml-settings',
            __('Languages', 'pressml'),
            __('Languages', 'pressml'),
            'manage_options',
            'pressml-languages',
            array($this, 'render_languages_page')
        );

        add_submenu_page(
            'pressml-settings',
            __('Statistics', 'pressml'),
            __('Statistics', 'pressml'),
            'manage_options',
            'pressml-statistics',
            array($this, 'render_statistics_page')
        );

        add_submenu_page(
            'pressml-settings',
            __('How to Use', 'pressml'),
            __('How to Use', 'pressml'),
            'manage_options',
            'pressml-help',
            array($this, 'render_help_page')
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (isset($_POST['submit']) &&
            isset($_POST['chrmrtns_pml_settings_nonce']) &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_pml_settings_nonce'])), 'chrmrtns_pml_settings') &&
            current_user_can('manage_options')) {
            // Save settings
            update_option('chrmrtns_pml_enabled', isset($_POST['chrmrtns_pml_enabled']));
            update_option('chrmrtns_pml_auto_detect', isset($_POST['chrmrtns_pml_auto_detect']));
            update_option('chrmrtns_pml_enable_json_ld', isset($_POST['chrmrtns_pml_enable_json_ld']));
            update_option('chrmrtns_pml_show_flags', isset($_POST['chrmrtns_pml_show_flags']));
            update_option('chrmrtns_pml_auto_menu_flags', isset($_POST['chrmrtns_pml_auto_menu_flags']));
            update_option('chrmrtns_pml_menu_flags_display', isset($_POST['chrmrtns_pml_menu_flags_display']) ? sanitize_text_field(wp_unslash($_POST['chrmrtns_pml_menu_flags_display'])) : 'row');

            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved!', 'pressml') . '</p></div>';
        }

        $enabled = get_option('chrmrtns_pml_enabled', true);
        $auto_detect = get_option('chrmrtns_pml_auto_detect', true);
        $enable_json_ld = get_option('chrmrtns_pml_enable_json_ld', true);
        $show_flags = get_option('chrmrtns_pml_show_flags', true);
        $auto_menu_flags = get_option('chrmrtns_pml_auto_menu_flags', false);
        $menu_flags_display = get_option('chrmrtns_pml_menu_flags_display', 'row');
        ?>
        <div class="wrap">
            <h1><?php echo wp_kses_post($this->get_header_logo()); ?><?php esc_html_e('PressML Settings', 'pressml'); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('chrmrtns_pml_settings', 'chrmrtns_pml_settings_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable PressML', 'pressml'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="chrmrtns_pml_enabled" value="1" <?php checked($enabled); ?> />
                                <?php esc_html_e('Enable hreflang tags output', 'pressml'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Auto-detect Language', 'pressml'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="chrmrtns_pml_auto_detect" value="1" <?php checked($auto_detect); ?> />
                                <?php esc_html_e('Automatically detect language from categories and tags', 'pressml'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('JSON-LD Output', 'pressml'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="chrmrtns_pml_enable_json_ld" value="1" <?php checked($enable_json_ld); ?> />
                                <?php esc_html_e('Add structured data for multilingual content', 'pressml'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Show Flags', 'pressml'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="chrmrtns_pml_show_flags" value="1" <?php checked($show_flags); ?> />
                                <?php esc_html_e('Show language flags in admin columns', 'pressml'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Automatic Menu Flags', 'pressml'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="chrmrtns_pml_auto_menu_flags" value="1" <?php checked($auto_menu_flags); ?> />
                                <?php esc_html_e('Automatically add language flags to the end of navigation menus', 'pressml'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, language flags will automatically appear at the end of navigation menus. When disabled, you can manually add them using Custom Links with URL "#pressml-language-flags".', 'pressml'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Menu Flags Display', 'pressml'); ?></th>
                        <td>
                            <select name="chrmrtns_pml_menu_flags_display">
                                <option value="row" <?php selected($menu_flags_display, 'row'); ?>>
                                    <?php esc_html_e('Row (side by side)', 'pressml'); ?>
                                </option>
                                <option value="column" <?php selected($menu_flags_display, 'column'); ?>>
                                    <?php esc_html_e('Column (stacked)', 'pressml'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Choose how language flags appear in navigation menus.', 'pressml'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render validator page
     */
    public function render_validator_page() {
        ?>
        <div class="wrap">
            <h1><?php echo wp_kses_post($this->get_header_logo()); ?><?php esc_html_e('PressML Validator', 'pressml'); ?></h1>

            <?php
            if (isset($_POST['validate_hreflang']) &&
                isset($_POST['chrmrtns_pml_validator_nonce']) &&
                wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_pml_validator_nonce'])), 'chrmrtns_pml_validator') &&
                current_user_can('manage_options')) {
                $issues = $this->db->validate_hreflang_urls();

                if (empty($issues)) {
                    echo '<div class="notice notice-success"><p>' . esc_html__('All hreflang configurations are valid!', 'pressml') . '</p></div>';
                } else {
                    // translators: %d: Number of issues found
                    echo '<div class="notice notice-warning"><p>' . esc_html(sprintf(__('Found %d issues:', 'pressml'), count($issues))) . '</p>';
                    echo '<ul>';
                    foreach ($issues as $issue) {
                        echo '<li>' . esc_html($issue) . '</li>';
                    }
                    echo '</ul></div>';
                }
            }

            if (isset($_POST['cleanup_orphaned']) &&
                isset($_POST['chrmrtns_pml_validator_nonce']) &&
                wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_pml_validator_nonce'])), 'chrmrtns_pml_validator') &&
                current_user_can('manage_options')) {
                $deleted = $this->db->cleanup_orphaned_entries();
                // translators: %d: Number of orphaned entries cleaned up
                echo '<div class="notice notice-success"><p>' . esc_html(sprintf(__('Cleaned up %d orphaned entries', 'pressml'), $deleted)) . '</p></div>';
            }
            ?>

            <form method="post">
                <?php wp_nonce_field('chrmrtns_pml_validator', 'chrmrtns_pml_validator_nonce'); ?>
                <p><?php esc_html_e('This tool validates all hreflang configurations across your site.', 'pressml'); ?></p>
                <p class="submit">
                    <input type="submit" name="validate_hreflang" class="button-primary" value="<?php esc_attr_e('Run Validation', 'pressml'); ?>" />
                    <input type="submit" name="cleanup_orphaned" class="button" value="<?php esc_attr_e('Clean Orphaned Entries', 'pressml'); ?>" />
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render statistics page
     */
    public function render_statistics_page() {
        // Handle delete action
        if (isset($_GET['action'], $_GET['post_id'], $_GET['_wpnonce']) &&
            $_GET['action'] === 'delete_hreflang' &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'delete_hreflang_' . intval($_GET['post_id']))) {

            $post_id = intval($_GET['post_id']);
            $deleted = $this->db->delete_hreflang_by_post($post_id);
            delete_post_meta($post_id, 'chrmrtns_pml_translation_group');
            delete_post_meta($post_id, 'chrmrtns_pml_hreflang_default');

            if ($deleted > 0) {
                echo '<div class="notice notice-success"><p>' .
                    esc_html(sprintf(
                        // translators: %1$d: Number of entries deleted, %2$d: Post ID
                        __('Deleted %1$d hreflang entries for post ID %2$d', 'pressml'),
                        $deleted,
                        $post_id
                    )) .
                    '</p></div>';
            }
        }

        $stats = $this->db->get_statistics();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('PressML Statistics', 'pressml'); ?></h1>

            <div class="chrmrtns-pml-card">
                <h2><?php esc_html_e('Overview', 'pressml'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Total Entries:', 'pressml'); ?></th>
                        <td><?php echo esc_html($stats['total_entries']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Posts with Hreflang:', 'pressml'); ?></th>
                        <td><?php echo esc_html($stats['total_posts']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Translation Groups:', 'pressml'); ?></th>
                        <td><?php echo esc_html($stats['total_groups']); ?></td>
                    </tr>
                </table>
            </div>

            <div class="chrmrtns-pml-card">
                <h2><?php esc_html_e('Languages', 'pressml'); ?></h2>
                <table class="form-table">
                    <?php foreach ($stats['languages'] as $lang => $count): ?>
                    <tr>
                        <th><?php echo esc_html(strtoupper($lang)); ?>:</th>
                        <td><?php echo esc_html($count); ?> <?php esc_html_e('entries', 'pressml'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div class="chrmrtns-pml-card">
                <h2><?php esc_html_e('Posts/Pages with Hreflang Entries', 'pressml'); ?></h2>
                <p><?php esc_html_e('All posts and pages that currently have hreflang entries in the database:', 'pressml'); ?></p>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'pressml'); ?></th>
                            <th><?php esc_html_e('Title', 'pressml'); ?></th>
                            <th><?php esc_html_e('Type', 'pressml'); ?></th>
                            <th><?php esc_html_e('Status', 'pressml'); ?></th>
                            <th><?php esc_html_e('Languages', 'pressml'); ?></th>
                            <th><?php esc_html_e('Translation Group', 'pressml'); ?></th>
                            <th><?php esc_html_e('Actions', 'pressml'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $all_posts = $this->db->get_all_posts_with_hreflang();
                        foreach ($all_posts as $post_id) {
                            $post = get_post($post_id);
                            $hreflang_data = $this->db->get_hreflang_by_post($post_id);
                            $translation_group = get_post_meta($post_id, 'chrmrtns_pml_translation_group', true);

                            echo '<tr>';
                            echo '<td>' . esc_html($post_id) . '</td>';

                            if ($post) {
                                echo '<td><a href="' . esc_url(get_edit_post_link($post_id)) . '">' . esc_html($post->post_title) . '</a></td>';
                                echo '<td>' . esc_html($post->post_type) . '</td>';
                                echo '<td>' . esc_html($post->post_status) . '</td>';
                            } else {
                                echo '<td><em>' . esc_html__('Post not found', 'pressml') . '</em></td>';
                                echo '<td>-</td>';
                                echo '<td>' . esc_html__('deleted', 'pressml') . '</td>';
                            }

                            // Show languages
                            echo '<td>';
                            $show_flags = get_option('chrmrtns_pml_show_flags', true);
                            $flags = array();
                            foreach ($hreflang_data as $item) {
                                $lang_info = $this->get_language_info($item->language_code);
                                if ($lang_info) {
                                    $flags[] = $show_flags ? $lang_info['flag'] . ' ' . strtoupper($lang_info['code']) : strtoupper($lang_info['code']);
                                } else {
                                    $flags[] = strtoupper($item->language_code);
                                }
                            }
                            echo esc_html(implode(', ', $flags)) . ' (' . count($hreflang_data) . ' ' . esc_html__('entries', 'pressml') . ')';
                            echo '</td>';

                            // Translation group
                            echo '<td>' . ($translation_group ? esc_html($translation_group) : '-') . '</td>';

                            // Actions
                            echo '<td>';
                            if ($post) {
                                echo '<a href="' . esc_url(get_edit_post_link($post_id)) . '" class="button button-small">' . esc_html__('Edit', 'pressml') . '</a> ';
                            }
                            echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=pressml-statistics&action=delete_hreflang&post_id=' . $post_id), 'delete_hreflang_' . $post_id)) . '" class="button button-small button-link-delete" onclick="return confirm(\'' . esc_js(__('Are you sure you want to delete all hreflang entries for this post?', 'pressml')) . '\')">' . esc_html__('Delete Hreflang', 'pressml') . '</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Render languages management page
     */
    public function render_languages_page() {
        // Handle form submission
        if (isset($_POST['submit']) &&
            isset($_POST['chrmrtns_pml_languages_nonce']) &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_pml_languages_nonce'])), 'chrmrtns_pml_languages') &&
            current_user_can('manage_options')) {

            if (isset($_POST['languages']) && is_array($_POST['languages'])) {
                $languages = array();
                $post_languages = wp_unslash($_POST['languages']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                foreach ($post_languages as $lang_data) {
                    if (isset($lang_data['code'], $lang_data['name'], $lang_data['flag']) &&
                        !empty($lang_data['code']) && !empty($lang_data['name'])) {
                        $languages[] = array(
                            'code' => sanitize_text_field($lang_data['code']),
                            'name' => sanitize_text_field($lang_data['name']),
                            'flag' => sanitize_text_field($lang_data['flag'])
                        );
                    }
                }
                update_option('chrmrtns_pml_languages', $languages);
                echo '<div class="notice notice-success"><p>' . esc_html__('Languages saved!', 'pressml') . '</p></div>';
            }
        }

        // Get current languages
        $languages = get_option('chrmrtns_pml_languages', array(
            array('code' => 'en', 'name' => 'English', 'flag' => '🇺🇸'),
            array('code' => 'de', 'name' => 'Deutsch', 'flag' => '🇩🇪')
        ));

        ?>
        <div class="wrap">
            <h1><?php echo wp_kses_post($this->get_header_logo()); ?><?php esc_html_e('Language Management', 'pressml'); ?></h1>

            <div class="chrmrtns-pml-card">
                <h2><?php esc_html_e('Supported Languages', 'pressml'); ?></h2>
                <p><?php esc_html_e('Add or remove languages for your multilingual site. Each language needs a language code (like "en" or "de"), a name, and a flag emoji.', 'pressml'); ?></p>

                <form method="post" action="">
                    <?php wp_nonce_field('chrmrtns_pml_languages', 'chrmrtns_pml_languages_nonce'); ?>

                    <table class="wp-list-table widefat fixed striped" id="chrmrtns-pml-languages-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;"><?php esc_html_e('Code', 'pressml'); ?></th>
                                <th><?php esc_html_e('Language Name', 'pressml'); ?></th>
                                <th style="width: 80px;"><?php esc_html_e('Flag', 'pressml'); ?></th>
                                <th style="width: 80px;"><?php esc_html_e('Actions', 'pressml'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($languages as $index => $lang): ?>
                            <tr>
                                <td>
                                    <input type="text" name="languages[<?php echo esc_attr($index); ?>][code]"
                                           value="<?php echo esc_attr($lang['code']); ?>"
                                           placeholder="en" maxlength="5" required style="width: 60px;" />
                                </td>
                                <td>
                                    <input type="text" name="languages[<?php echo esc_attr($index); ?>][name]"
                                           value="<?php echo esc_attr($lang['name']); ?>"
                                           placeholder="English" required style="width: 100%;" />
                                </td>
                                <td>
                                    <input type="text" name="languages[<?php echo esc_attr($index); ?>][flag]"
                                           value="<?php echo esc_attr($lang['flag']); ?>"
                                           placeholder="🇺🇸" maxlength="10" required style="width: 60px;" />
                                </td>
                                <td>
                                    <button type="button" class="button chrmrtns-pml-remove-language" <?php echo count($languages) <= 1 ? 'disabled' : ''; ?>>
                                        <?php esc_html_e('Remove', 'pressml'); ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p>
                        <button type="button" class="button" id="chrmrtns-pml-add-language">
                            <?php esc_html_e('Add Language', 'pressml'); ?>
                        </button>
                    </p>

                    <?php submit_button(); ?>
                </form>
            </div>

            <div class="chrmrtns-pml-card">
                <h2><?php esc_html_e('Common Language Codes & Flags', 'pressml'); ?></h2>
                <p><?php esc_html_e('Here are some common language codes and their corresponding flag emojis:', 'pressml'); ?></p>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Language', 'pressml'); ?></th>
                        <th><?php esc_html_e('Code', 'pressml'); ?></th>
                        <th><?php esc_html_e('Flag', 'pressml'); ?></th>
                    </tr>
                    <tr><td>English</td><td>en</td><td>🇺🇸</td></tr>
                    <tr><td>Deutsch</td><td>de</td><td>🇩🇪</td></tr>
                    <tr><td>Français</td><td>fr</td><td>🇫🇷</td></tr>
                    <tr><td>Español</td><td>es</td><td>🇪🇸</td></tr>
                    <tr><td>Italiano</td><td>it</td><td>🇮🇹</td></tr>
                    <tr><td>Nederlands</td><td>nl</td><td>🇳🇱</td></tr>
                    <tr><td>Português</td><td>pt</td><td>🇵🇹</td></tr>
                    <tr><td>日本語</td><td>ja</td><td>🇯🇵</td></tr>
                    <tr><td>中文</td><td>zh</td><td>🇨🇳</td></tr>
                    <tr><td>Русский</td><td>ru</td><td>🇷🇺</td></tr>
                </table>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            let languageIndex = <?php echo count($languages); ?>;

            $('#chrmrtns-pml-add-language').click(function() {
                const newRow = `
                    <tr>
                        <td>
                            <input type="text" name="languages[${languageIndex}][code]"
                                   value="" placeholder="en" maxlength="5" required style="width: 60px;" />
                        </td>
                        <td>
                            <input type="text" name="languages[${languageIndex}][name]"
                                   value="" placeholder="English" required style="width: 100%;" />
                        </td>
                        <td>
                            <input type="text" name="languages[${languageIndex}][flag]"
                                   value="" placeholder="🇺🇸" maxlength="10" required style="width: 60px;" />
                        </td>
                        <td>
                            <button type="button" class="button chrmrtns-pml-remove-language">
                                <?php esc_html_e('Remove', 'pressml'); ?>
                            </button>
                        </td>
                    </tr>
                `;
                $('#chrmrtns-pml-languages-table tbody').append(newRow);
                languageIndex++;
                updateRemoveButtons();
            });

            $(document).on('click', '.chrmrtns-pml-remove-language', function() {
                $(this).closest('tr').remove();
                updateRemoveButtons();
            });

            function updateRemoveButtons() {
                const rows = $('#chrmrtns-pml-languages-table tbody tr').length;
                $('.chrmrtns-pml-remove-language').prop('disabled', rows <= 1);
            }
        });
        </script>
        <?php
    }

    /**
     * Get supported languages
     */
    public function get_supported_languages() {
        return get_option('chrmrtns_pml_languages', array(
            array('code' => 'en', 'name' => 'English', 'flag' => '🇺🇸'),
            array('code' => 'de', 'name' => 'Deutsch', 'flag' => '🇩🇪')
        ));
    }

    /**
     * Get language info by code
     */
    public function get_language_info($code) {
        $languages = $this->get_supported_languages();
        foreach ($languages as $lang) {
            if ($lang['code'] === $code) {
                return $lang;
            }
        }
        return null;
    }

    /**
     * Add hreflang column to post list
     */
    public function add_hreflang_column($columns) {
        $columns['chrmrtns_pml_translations'] = esc_html__('Translations', 'pressml');
        return $columns;
    }

    /**
     * Render hreflang column content
     */
    public function render_hreflang_column($column, $post_id) {
        if ($column === 'chrmrtns_pml_translations') {
            $hreflang_data = $this->db->get_hreflang_by_post($post_id);

            if (empty($hreflang_data)) {
                echo '<span style="color: #999;">' . esc_html__('Single language', 'pressml') . '</span>';
            } else {
                $show_flags = get_option('chrmrtns_pml_show_flags', true);
                $flags = array();

                foreach ($hreflang_data as $item) {
                    $lang_info = $this->get_language_info($item->language_code);
                    if ($lang_info) {
                        $flags[] = $show_flags ? $lang_info['flag'] . ' ' . strtoupper($lang_info['code']) : strtoupper($lang_info['code']);
                    } else {
                        $flags[] = strtoupper($item->language_code);
                    }
                }

                echo esc_html(implode(' + ', $flags));

                $translation_group = get_post_meta($post_id, 'chrmrtns_pml_translation_group', true);
                if ($translation_group) {
                    echo '<br><small style="color: #666;">Group: ' . esc_html($translation_group) . '</small>';
                }
            }
        }
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (in_array($hook, array('post.php', 'post-new.php'))) {
            wp_enqueue_script('jquery');
        }

        // Add admin CSS for PressML pages
        if (strpos($hook, 'pressml') !== false) {
            wp_add_inline_style('wp-admin', '
                .chrmrtns-pml-card {
                    position: relative;
                    margin-top: 20px;
                    padding: 0.7em 2em 1em;
                    min-width: 255px;
                    max-width: 100%;
                    border: 1px solid #c3c4c7;
                    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
                    background: #fff;
                    box-sizing: border-box;
                }
                .chrmrtns-pml-meta-box {
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
                    padding: 12px;
                }
                .chrmrtns-pml-info-box {
                    background: #f1f1f1;
                    border: 1px solid #ddd;
                    padding: 10px;
                    margin-top: 10px;
                    border-radius: 4px;
                }
            ');
        }
    }

    /**
     * AJAX handler for translation group sync
     */
    public function ajax_sync_translation_group() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'chrmrtns_pml_sync')) {
            wp_die('Nonce verification failed');
        }

        if (!isset($_POST['translation_group']) || !isset($_POST['post_id'])) {
            wp_send_json_error(__('Missing required parameters', 'pressml'));
        }

        $translation_group = sanitize_text_field(wp_unslash($_POST['translation_group']));
        $current_post_id = intval(wp_unslash($_POST['post_id']));

        if (empty($translation_group)) {
            wp_send_json_error(__('Translation group is empty', 'pressml'));
        }

        // Get related posts from translation group
        $group_data = $this->db->get_hreflang_by_translation_group($translation_group);

        if (empty($group_data)) {
            // Try to find by post meta
            $related_posts = get_posts(array(
                'post_type' => array('post', 'page'),
                'post_status' => 'publish',
                'posts_per_page' => 10,
                'exclude' => array($current_post_id), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
                'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                    array(
                        'key' => 'chrmrtns_pml_translation_group',
                        'value' => $translation_group,
                        'compare' => '='
                    )
                )
            ));

            $urls = array();
            $supported_languages = $this->get_supported_languages();

            foreach ($related_posts as $post) {
                $post_url = get_permalink($post->ID);

                foreach ($supported_languages as $lang) {
                    // Use mb_strtolower for proper UTF-8 handling
                    $lang_name_lower = function_exists('mb_strtolower') ? mb_strtolower($lang['name'], 'UTF-8') : strtolower($lang['name']);
                    $lang_name_cap = function_exists('mb_convert_case') ? mb_convert_case($lang['name'], MB_CASE_TITLE, 'UTF-8') : ucfirst($lang_name_lower);
                    $lang_name_orig = $lang['name'];

                    // Also check for common variations
                    $variations = array(
                        $lang_name_lower,
                        $lang_name_cap,
                        $lang_name_orig,
                        $lang['code'], // Also check for language code as category/tag
                        strtolower($lang['code']),
                        strtoupper($lang['code'])
                    );

                    $version_variations = array();
                    foreach ($variations as $var) {
                        $version_variations[] = $var . '-version';
                        $version_variations[] = $var . '_version';
                        $version_variations[] = $var;
                    }

                    if (has_category($version_variations, $post) ||
                        has_tag($version_variations, $post)) {
                        $urls[$lang['code'] . '_url'] = $post_url;
                        break;
                    }
                }
            }

            if (empty($urls)) {
                wp_send_json_error(__('No related translations found', 'pressml'));
            }

            wp_send_json_success($urls);
        } else {
            $urls = array();
            foreach ($group_data as $item) {
                if ($item->post_id != $current_post_id) {
                    $urls[$item->language_code . '_url'] = $item->url;
                }
            }

            wp_send_json_success($urls);
        }
    }

    /**
     * AJAX handler for URL validation
     */
    public function ajax_validate_urls() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'chrmrtns_pml_validate')) {
            wp_die('Nonce verification failed');
        }

        if (!isset($_POST['post_id'])) {
            wp_send_json_error(__('Missing post ID parameter', 'pressml'));
        }

        $post_id = intval(wp_unslash($_POST['post_id']));
        $issues = array();

        $hreflang_data = $this->db->get_hreflang_by_post($post_id);

        foreach ($hreflang_data as $item) {
            if (!filter_var($item->url, FILTER_VALIDATE_URL)) {
                // translators: %s: Language code
                $issues[] = sprintf(__('Invalid URL for language %s', 'pressml'), $item->language_code);
            }
        }

        if (empty($issues)) {
            wp_send_json_success(__('All URLs are valid', 'pressml'));
        } else {
            wp_send_json_error($issues);
        }
    }

    /**
     * Render help page
     */
    public function render_help_page() {
        ?>
        <div class="wrap">
            <h1><?php echo wp_kses_post($this->get_header_logo()); ?><?php esc_html_e('How to Use PressML', 'pressml'); ?></h1>

            <div style="max-width: 100%;">

                <div class="chrmrtns-pml-card">
                    <h2><?php esc_html_e('Getting Started', 'pressml'); ?></h2>
                    <p><?php esc_html_e('PressML helps you manage multilingual content with proper hreflang tags. Follow these steps to set up your multilingual site:', 'pressml'); ?></p>

                    <ol>
                        <li><strong><?php esc_html_e('Create categories for languages:', 'pressml'); ?></strong> <?php esc_html_e('Create categories named "English" and "German" (or "english" and "german").', 'pressml'); ?></li>
                        <li><strong><?php esc_html_e('Assign posts to language categories:', 'pressml'); ?></strong> <?php esc_html_e('When creating/editing posts, assign them to the appropriate language category.', 'pressml'); ?></li>
                        <li><strong><?php esc_html_e('Link translations together:', 'pressml'); ?></strong> <?php esc_html_e('Use the PressML meta box in the post editor to link related translations.', 'pressml'); ?></li>
                    </ol>
                </div>

                <div class="chrmrtns-pml-card">
                    <h2><?php esc_html_e('Using the Post Editor', 'pressml'); ?></h2>
                    <p><?php esc_html_e('When editing a post or page, you\'ll find the PressML meta box below the editor:', 'pressml'); ?></p>

                    <h3><?php esc_html_e('Meta Box Fields:', 'pressml'); ?></h3>
                    <ul>
                        <li><strong><?php esc_html_e('English URL:', 'pressml'); ?></strong> <?php esc_html_e('Enter the URL of the English version of this content', 'pressml'); ?></li>
                        <li><strong><?php esc_html_e('German URL:', 'pressml'); ?></strong> <?php esc_html_e('Enter the URL of the German version of this content', 'pressml'); ?></li>
                        <li><strong><?php esc_html_e('Translation Group:', 'pressml'); ?></strong> <?php esc_html_e('A unique identifier to group related translations together', 'pressml'); ?></li>
                        <li><strong><?php esc_html_e('Set as Default Language:', 'pressml'); ?></strong> <?php esc_html_e('Check this if this page should be the default (x-default) version', 'pressml'); ?></li>
                    </ul>
                </div>

                <div class="chrmrtns-pml-card">
                    <h2><?php esc_html_e('Language Detection', 'pressml'); ?></h2>
                    <p><?php esc_html_e('PressML detects the language of your content using:', 'pressml'); ?></p>

                    <ul>
                        <li><strong><?php esc_html_e('Categories:', 'pressml'); ?></strong> "english", "English", "german", "German"</li>
                        <li><strong><?php esc_html_e('Tags:', 'pressml'); ?></strong> "english-version", "English-version", "german-version", "German-version"</li>
                        <li><strong><?php esc_html_e('Site Locale:', 'pressml'); ?></strong> <?php esc_html_e('Falls back to your WordPress locale setting', 'pressml'); ?></li>
                    </ul>
                </div>

                <div class="chrmrtns-pml-card">
                    <h2><?php esc_html_e('Using Shortcodes', 'pressml'); ?></h2>
                    <p><?php esc_html_e('Add language switching functionality to your posts and pages:', 'pressml'); ?></p>

                    <h3><?php esc_html_e('Available Shortcodes:', 'pressml'); ?></h3>

                    <h4><code>[pressml_language_flags]</code></h4>
                    <p><?php esc_html_e('Displays flag icons that link to other language versions.', 'pressml'); ?></p>
                    <p><strong><?php esc_html_e('Parameters:', 'pressml'); ?></strong></p>
                    <ul>
                        <li><code>size="small|medium|large"</code> - <?php esc_html_e('Flag size', 'pressml'); ?></li>
                        <li><code>style="inline|block"</code> - <?php esc_html_e('Display style', 'pressml'); ?></li>
                        <li><code>show_current="true|false"</code> - <?php esc_html_e('Show current language flag', 'pressml'); ?></li>
                        <li><code>debug="true|false"</code> - <?php esc_html_e('Show debug information', 'pressml'); ?></li>
                    </ul>
                    <p><strong><?php esc_html_e('Example:', 'pressml'); ?></strong> <code>[pressml_language_flags size="large" style="block"]</code></p>

                    <h4><code>[pressml_language_switcher]</code></h4>
                    <p><?php esc_html_e('Displays a full language switcher with flags and text.', 'pressml'); ?></p>
                    <p><strong><?php esc_html_e('Parameters:', 'pressml'); ?></strong></p>
                    <ul>
                        <li><code>show_flags="true|false"</code> - <?php esc_html_e('Show flag icons', 'pressml'); ?></li>
                        <li><code>show_names="true|false"</code> - <?php esc_html_e('Show language names', 'pressml'); ?></li>
                        <li><code>separator=" | "</code> - <?php esc_html_e('Text between languages', 'pressml'); ?></li>
                        <li><code>debug="true|false"</code> - <?php esc_html_e('Show debug information', 'pressml'); ?></li>
                    </ul>
                    <p><strong><?php esc_html_e('Example:', 'pressml'); ?></strong> <code>[pressml_language_switcher show_flags="true" show_names="false"]</code></p>

                    <h4><code>[pressml_current_language]</code></h4>
                    <p><?php esc_html_e('Displays the current language.', 'pressml'); ?></p>
                    <p><strong><?php esc_html_e('Parameters:', 'pressml'); ?></strong></p>
                    <ul>
                        <li><code>format="name|code|flag"</code> - <?php esc_html_e('Display format', 'pressml'); ?></li>
                        <li><code>debug="true|false"</code> - <?php esc_html_e('Show debug information', 'pressml'); ?></li>
                    </ul>
                    <p><strong><?php esc_html_e('Example:', 'pressml'); ?></strong> <code>[pressml_current_language format="flag"]</code></p>
                </div>

                <div class="chrmrtns-pml-card">
                    <h2><?php esc_html_e('Troubleshooting', 'pressml'); ?></h2>

                    <h3><?php esc_html_e('Shortcodes not working?', 'pressml'); ?></h3>
                    <p><?php esc_html_e('Add debug="true" to any shortcode to see diagnostic information:', 'pressml'); ?></p>
                    <p><code>[pressml_language_flags debug="true"]</code></p>

                    <h3><?php esc_html_e('No translations found?', 'pressml'); ?></h3>
                    <ul>
                        <li><?php esc_html_e('Make sure posts have the PressML meta box filled out', 'pressml'); ?></li>
                        <li><?php esc_html_e('Check that translation groups match between related posts', 'pressml'); ?></li>
                        <li><?php esc_html_e('Verify URLs are correct and accessible', 'pressml'); ?></li>
                        <li><?php esc_html_e('Use the Validator page to check for issues', 'pressml'); ?></li>
                    </ul>

                    <h3><?php esc_html_e('Language not detected?', 'pressml'); ?></h3>
                    <ul>
                        <li><?php esc_html_e('Assign posts to "English" or "German" categories', 'pressml'); ?></li>
                        <li><?php esc_html_e('Or add "english-version" or "german-version" tags', 'pressml'); ?></li>
                        <li><?php esc_html_e('Both lowercase and capitalized names work', 'pressml'); ?></li>
                    </ul>
                </div>

                <div class="chrmrtns-pml-card">
                    <h2><?php esc_html_e('Navigation Menu Integration', 'pressml'); ?></h2>
                    <p><?php esc_html_e('Add language flags to your navigation menus:', 'pressml'); ?></p>

                    <h3><?php esc_html_e('Method 1: Automatic (Recommended)', 'pressml'); ?></h3>
                    <p><?php esc_html_e('Language flags automatically appear at the end of your navigation menu when viewing posts/pages that have translations. Only shows flags for other languages (not current language). No setup required!', 'pressml'); ?></p>

                    <h3><?php esc_html_e('Method 2: Manual Placement', 'pressml'); ?></h3>
                    <ol>
                        <li><?php esc_html_e('Go to Appearance → Menus', 'pressml'); ?></li>
                        <li><?php esc_html_e('Add a "Custom Link" menu item', 'pressml'); ?></li>
                        <li><?php esc_html_e('Set URL to:', 'pressml'); ?> <code>#pressml-language-flags</code></li>
                        <li><?php esc_html_e('Set Link Text to: "Language Flags"', 'pressml'); ?></li>
                        <li><?php esc_html_e('Save menu', 'pressml'); ?></li>
                    </ol>
                    <p><em><?php esc_html_e('The custom link will be replaced with actual flag links only when translations exist.', 'pressml'); ?></em></p>
                </div>

                <div class="chrmrtns-pml-card">
                    <h2><?php esc_html_e('Tools & Validation', 'pressml'); ?></h2>
                    <p><?php esc_html_e('Use the built-in tools to maintain your multilingual setup:', 'pressml'); ?></p>

                    <ul>
                        <li><strong><?php esc_html_e('Validator:', 'pressml'); ?></strong> <?php esc_html_e('Checks for common issues and can auto-fix some problems', 'pressml'); ?></li>
                        <li><strong><?php esc_html_e('Statistics:', 'pressml'); ?></strong> <?php esc_html_e('Shows overview of your multilingual content', 'pressml'); ?></li>
                        <li><strong><?php esc_html_e('Settings:', 'pressml'); ?></strong> <?php esc_html_e('Configure plugin behavior and supported languages', 'pressml'); ?></li>
                    </ul>
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Get menu icon for admin menu
     */
    private function get_menu_icon() {
        // Use WordPress translation dashicon for admin menu
        return 'dashicons-translation';
    }

    /**
     * Get logo HTML for page headers
     */
    private function get_header_logo() {
        $plugin_dir = dirname(dirname(__FILE__));
        $logo_path = $plugin_dir . '/assets/logo.png';

        if (file_exists($logo_path)) {
            $logo_url = plugin_dir_url(dirname(__FILE__)) . 'assets/logo.png';
            return '<img src="' . esc_url($logo_url) . '" alt="PressML" style="height: 32px; width: auto; vertical-align: middle; margin-right: 10px;" />';
        }

        return '';
    }
}