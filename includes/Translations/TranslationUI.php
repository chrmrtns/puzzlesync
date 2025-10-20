<?php
/**
 * Translation UI
 *
 * Renders side-by-side translation editor in WordPress admin
 *
 * @package PuzzleSync
 * @since 1.1.0
 */

namespace Chrmrtns\PuzzleSync\Translations;

if (!defined('ABSPATH')) {
    exit;
}

class TranslationUI {

    /**
     * @var TranslationManager Translation manager instance
     */
    private $translation_manager;

    /**
     * @var array Registered field translators
     */
    private $translators = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->translation_manager = new TranslationManager();
    }

    /**
     * Initialize UI hooks
     */
    public function init() {
        add_action('add_meta_boxes', array($this, 'add_translation_meta_box'));
        add_action('save_post', array($this, 'save_translations'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Register a field translator
     *
     * @param FieldTranslator $translator Field translator instance
     */
    public function register_translator($translator) {
        $this->translators[$translator->get_field_type()] = $translator;
    }

    /**
     * Add translation meta box
     */
    public function add_translation_meta_box() {
        $post_types = $this->get_enabled_post_types();

        foreach ($post_types as $post_type) {
            add_meta_box(
                'puzzlesync_translations',
                __('Field Translations', 'puzzlesync'),
                array($this, 'render_translation_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Get enabled post types for translation
     *
     * @return array
     */
    private function get_enabled_post_types() {
        $enabled = get_option('chrmrtns_puzzlesync_enabled_post_types', array('post', 'page', 'product'));
        return apply_filters('puzzlesync_translation_post_types', $enabled);
    }

    /**
     * Render translation meta box
     *
     * @param \WP_Post $post Post object
     */
    public function render_translation_meta_box($post) {
        wp_nonce_field('puzzlesync_save_translations', 'puzzlesync_translations_nonce');

        $languages = $this->get_supported_languages();
        $current_lang = $this->detect_post_language($post->ID);

        ?>
        <div class="chrmrtns-puzzlesync-translations-wrapper">
            <div class="chrmrtns-puzzlesync-translation-tabs">
                <?php foreach ($languages as $lang): ?>
                    <?php if ($lang['code'] !== $current_lang): ?>
                        <button type="button"
                                class="chrmrtns-puzzlesync-tab-button"
                                data-lang="<?php echo esc_attr($lang['code']); ?>">
                            <span class="flag"><?php echo esc_html($lang['flag']); ?></span>
                            <?php echo esc_html($lang['name']); ?>
                        </button>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <?php foreach ($languages as $lang): ?>
                <?php if ($lang['code'] !== $current_lang): ?>
                    <div class="chrmrtns-puzzlesync-translation-panel"
                         data-lang="<?php echo esc_attr($lang['code']); ?>"
                         style="display: none;">
                        <?php $this->render_translation_fields($post, $lang); ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <style>
            .chrmrtns-puzzlesync-translations-wrapper {
                margin: 10px 0;
            }
            .chrmrtns-puzzlesync-translation-tabs {
                display: flex;
                gap: 5px;
                margin-bottom: 15px;
                border-bottom: 1px solid #ccc;
                padding-bottom: 10px;
            }
            .chrmrtns-puzzlesync-tab-button {
                background: #f0f0f1;
                border: 1px solid #ccc;
                padding: 8px 15px;
                cursor: pointer;
                border-radius: 3px;
                transition: all 0.3s;
            }
            .chrmrtns-puzzlesync-tab-button:hover,
            .chrmrtns-puzzlesync-tab-button.active {
                background: #2271b1;
                color: white;
                border-color: #2271b1;
            }
            .chrmrtns-puzzlesync-tab-button .flag {
                margin-right: 5px;
            }
            .chrmrtns-puzzlesync-translation-panel {
                padding: 15px;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 3px;
            }
            .chrmrtns-puzzlesync-field-row {
                margin-bottom: 20px;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                align-items: start;
            }
            .chrmrtns-puzzlesync-field-column {
                background: white;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 3px;
            }
            .chrmrtns-puzzlesync-field-column h4 {
                margin: 0 0 10px 0;
                padding: 0;
                font-size: 13px;
                color: #666;
            }
            .chrmrtns-puzzlesync-field-column .original-value {
                color: #555;
                font-style: italic;
                min-height: 40px;
                padding: 8px;
                background: #f9f9f9;
                border: 1px solid #e0e0e0;
                border-radius: 3px;
            }
            .chrmrtns-puzzlesync-field-column input[type="text"],
            .chrmrtns-puzzlesync-field-column textarea {
                width: 100%;
                padding: 8px;
            }
            .chrmrtns-puzzlesync-field-column textarea {
                min-height: 100px;
                resize: vertical;
            }
            .chrmrtns-puzzlesync-field-label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
                color: #23282d;
            }
            .chrmrtns-puzzlesync-pro-badge {
                background: #f0ad4e;
                color: white;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: bold;
                margin-left: 5px;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('.chrmrtns-puzzlesync-tab-button').on('click', function() {
                var lang = $(this).data('lang');

                $('.chrmrtns-puzzlesync-tab-button').removeClass('active');
                $(this).addClass('active');

                $('.chrmrtns-puzzlesync-translation-panel').hide();
                $('.chrmrtns-puzzlesync-translation-panel[data-lang="' + lang + '"]').show();
            });

            // Activate first tab by default
            $('.chrmrtns-puzzlesync-tab-button').first().trigger('click');
        });
        </script>
        <?php
    }

    /**
     * Render translation fields for a language
     *
     * @param \WP_Post $post Post object
     * @param array    $lang Language configuration
     */
    private function render_translation_fields($post, $lang) {
        $lang_code = $lang['code'];

        echo '<h3>' . sprintf(__('Translate to %s', 'puzzlesync'), $lang['name']) . '</h3>';

        // Get translatable fields from all registered translators
        $fields = $this->get_all_translatable_fields($post->ID);

        if (empty($fields)) {
            echo '<p>' . __('No translatable fields found for this post.', 'puzzlesync') . '</p>';
            return;
        }

        foreach ($fields as $field) {
            $this->render_field_row($post->ID, $field, $lang_code);
        }
    }

    /**
     * Get all translatable fields from registered translators
     *
     * @param int $post_id Post ID
     * @return array
     */
    private function get_all_translatable_fields($post_id) {
        $fields = array();

        // Add standard post fields
        $fields[] = array(
            'name'        => 'post_title',
            'label'       => __('Title', 'puzzlesync'),
            'type'        => 'text',
            'field_type'  => 'post_meta',
            'is_pro'      => false,
            'description' => __('Post title', 'puzzlesync'),
        );

        $fields[] = array(
            'name'        => 'post_content',
            'label'       => __('Content', 'puzzlesync'),
            'type'        => 'textarea',
            'field_type'  => 'post_meta',
            'is_pro'      => false,
            'description' => __('Post content', 'puzzlesync'),
        );

        $fields[] = array(
            'name'        => 'post_excerpt',
            'label'       => __('Excerpt', 'puzzlesync'),
            'type'        => 'textarea',
            'field_type'  => 'post_meta',
            'is_pro'      => false,
            'description' => __('Post excerpt', 'puzzlesync'),
        );

        // Get fields from registered translators
        foreach ($this->translators as $translator) {
            $translator_fields = $translator->get_translatable_fields($post_id);
            $fields = array_merge($fields, $translator_fields);
        }

        return apply_filters('puzzlesync_translatable_fields', $fields, $post_id);
    }

    /**
     * Render a single field row
     *
     * @param int    $post_id   Post ID
     * @param array  $field     Field configuration
     * @param string $lang_code Language code
     */
    private function render_field_row($post_id, $field, $lang_code) {
        $field_name = $field['name'];
        $field_label = $field['label'];
        $field_type = $field['type'];
        $is_pro = isset($field['is_pro']) && $field['is_pro'];

        // Get original value
        if (in_array($field_name, array('post_title', 'post_content', 'post_excerpt'))) {
            $post = get_post($post_id);
            $original_value = $post->{$field_name};
        } else {
            $original_value = get_post_meta($post_id, $field_name, true);
        }

        // Get translated value
        $translated_value = $this->translation_manager->get_translation($post_id, $field_name, $lang_code);

        ?>
        <div class="chrmrtns-puzzlesync-field-row">
            <div class="chrmrtns-puzzlesync-field-column">
                <h4>
                    <?php echo esc_html($field_label); ?>
                    <?php if ($is_pro): ?>
                        <span class="chrmrtns-puzzlesync-pro-badge">PRO</span>
                    <?php endif; ?>
                    <span style="color: #999; font-weight: normal;"> - Original</span>
                </h4>
                <div class="original-value">
                    <?php
                    if ($field_type === 'textarea') {
                        echo nl2br(esc_html(wp_trim_words($original_value, 50)));
                    } else {
                        echo esc_html($original_value);
                    }
                    ?>
                </div>
            </div>

            <div class="chrmrtns-puzzlesync-field-column">
                <h4>
                    <label class="chrmrtns-puzzlesync-field-label">
                        <?php echo esc_html($field_label); ?> - Translation
                    </label>
                </h4>
                <?php if ($field_type === 'textarea'): ?>
                    <textarea
                        name="puzzlesync_translations[<?php echo esc_attr($lang_code); ?>][<?php echo esc_attr($field_name); ?>]"
                        rows="5"
                        <?php echo $is_pro && !$this->is_pro_active() ? 'disabled' : ''; ?>
                    ><?php echo esc_textarea($translated_value); ?></textarea>
                <?php else: ?>
                    <input
                        type="text"
                        name="puzzlesync_translations[<?php echo esc_attr($lang_code); ?>][<?php echo esc_attr($field_name); ?>]"
                        value="<?php echo esc_attr($translated_value); ?>"
                        <?php echo $is_pro && !$this->is_pro_active() ? 'disabled' : ''; ?>
                    />
                <?php endif; ?>
                <?php if ($is_pro && !$this->is_pro_active()): ?>
                    <p style="color: #999; font-size: 12px; margin-top: 5px;">
                        <?php _e('This feature requires PuzzleSync Pro', 'puzzlesync'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Save translations
     *
     * @param int      $post_id Post ID
     * @param \WP_Post $post    Post object
     */
    public function save_translations($post_id, $post) {
        // Security checks
        if (!isset($_POST['puzzlesync_translations_nonce']) ||
            !wp_verify_nonce($_POST['puzzlesync_translations_nonce'], 'puzzlesync_save_translations')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Get translations data
        if (!isset($_POST['puzzlesync_translations']) || !is_array($_POST['puzzlesync_translations'])) {
            return;
        }

        $translations = $_POST['puzzlesync_translations'];

        // Save translations for each language
        foreach ($translations as $lang_code => $fields) {
            foreach ($fields as $field_name => $translated_value) {
                // Skip empty translations
                if (empty($translated_value)) {
                    continue;
                }

                // Sanitize based on field type
                $sanitized_value = $this->sanitize_translation_value($translated_value, $field_name);

                // Save translation
                $this->translation_manager->save_translation(
                    $post_id,
                    $field_name,
                    'post_meta', // Default field type
                    $lang_code,
                    $sanitized_value,
                    '', // translation_group
                    false // is_pro_feature
                );
            }
        }
    }

    /**
     * Sanitize translation value
     *
     * @param mixed  $value      Value to sanitize
     * @param string $field_name Field name
     * @return mixed Sanitized value
     */
    private function sanitize_translation_value($value, $field_name) {
        if (in_array($field_name, array('post_content', 'post_excerpt'))) {
            return wp_kses_post($value);
        }

        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        }

        return sanitize_text_field($value);
    }

    /**
     * Enqueue assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        wp_enqueue_style(
            'puzzlesync-translations',
            CHRMRTNS_PUZZLESYNC_PLUGIN_URL . 'assets/css/translations.css',
            array(),
            CHRMRTNS_PUZZLESYNC_VERSION
        );

        wp_enqueue_script(
            'puzzlesync-translations',
            CHRMRTNS_PUZZLESYNC_PLUGIN_URL . 'assets/js/translations.js',
            array('jquery'),
            CHRMRTNS_PUZZLESYNC_VERSION,
            true
        );
    }

    /**
     * Get supported languages
     *
     * @return array
     */
    private function get_supported_languages() {
        return get_option('chrmrtns_puzzlesync_languages', array(
            array('code' => 'en', 'name' => 'English', 'flag' => '🇺🇸'),
            array('code' => 'de', 'name' => 'Deutsch', 'flag' => '🇩🇪'),
        ));
    }

    /**
     * Detect post language
     *
     * @param int $post_id Post ID
     * @return string Language code
     */
    private function detect_post_language($post_id) {
        $post = get_post($post_id);
        $languages = $this->get_supported_languages();

        foreach ($languages as $lang) {
            $variations = array(
                strtolower($lang['name']),
                ucfirst(strtolower($lang['name'])),
                $lang['code'],
                strtolower($lang['code']),
                strtoupper($lang['code']),
            );

            foreach ($variations as $var) {
                if (has_category($var, $post) || has_tag($var, $post) || has_tag($var . '-version', $post)) {
                    return $lang['code'];
                }
            }
        }

        return get_option('chrmrtns_puzzlesync_default_language', 'en');
    }

    /**
     * Check if Pro version is active
     *
     * @return bool
     */
    private function is_pro_active() {
        return apply_filters('puzzlesync_is_pro_active', false);
    }
}
