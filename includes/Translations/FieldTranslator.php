<?php
/**
 * Field Translator Base Class
 *
 * Abstract base class for field-level translation integrations
 *
 * @package PuzzleSync
 * @since 1.1.0
 */

namespace Chrmrtns\PuzzleSync\Translations;

if (!defined('ABSPATH')) {
    exit;
}

abstract class FieldTranslator {

    /**
     * @var TranslationManager Translation manager instance
     */
    protected $translation_manager;

    /**
     * @var string Field type identifier
     */
    protected $field_type;

    /**
     * @var bool Whether this is a Pro feature
     */
    protected $is_pro_feature = false;

    /**
     * Constructor
     */
    public function __construct() {
        $this->translation_manager = new TranslationManager();
    }

    /**
     * Get translatable fields for a post
     *
     * @param int $post_id Post ID
     * @return array Array of field definitions
     */
    abstract public function get_translatable_fields($post_id);

    /**
     * Get field value
     *
     * @param int    $post_id    Post ID
     * @param string $field_name Field name
     * @return mixed Field value
     */
    abstract public function get_field_value($post_id, $field_name);

    /**
     * Set field value
     *
     * @param int    $post_id    Post ID
     * @param string $field_name Field name
     * @param mixed  $value      Field value
     * @return bool Success
     */
    abstract public function set_field_value($post_id, $field_name, $value);

    /**
     * Check if a field type is translatable
     *
     * @param string $field_type Field type
     * @return bool
     */
    abstract public function is_translatable_field_type($field_type);

    /**
     * Get translated field value
     *
     * @param int    $post_id       Post ID
     * @param string $field_name    Field name
     * @param string $language_code Language code
     * @return mixed Translated value or original if not found
     */
    public function get_translated_value($post_id, $field_name, $language_code) {
        $translated = $this->translation_manager->get_translation($post_id, $field_name, $language_code);

        if ($translated !== null) {
            return $translated;
        }

        // Return original value if no translation exists
        return $this->get_field_value($post_id, $field_name);
    }

    /**
     * Save translation
     *
     * @param int    $post_id           Post ID
     * @param string $field_name        Field name
     * @param string $language_code     Language code
     * @param mixed  $translated_value  Translated value
     * @param string $translation_group Translation group (optional)
     * @return bool Success
     */
    public function save_translation($post_id, $field_name, $language_code, $translated_value, $translation_group = '') {
        return $this->translation_manager->save_translation(
            $post_id,
            $field_name,
            $this->field_type,
            $language_code,
            $translated_value,
            $translation_group,
            $this->is_pro_feature
        );
    }

    /**
     * Get all translations for a field
     *
     * @param int    $post_id    Post ID
     * @param string $field_name Field name
     * @return array Array of translations by language code
     */
    public function get_field_translations($post_id, $field_name) {
        global $wpdb;
        $table_name = $wpdb->prefix . CHRMRTNS_PUZZLESYNC_FIELD_TRANSLATIONS_TABLE;

        $translations = $wpdb->get_results($wpdb->prepare(
            "SELECT language_code, translated_value FROM {$table_name} WHERE post_id = %d AND field_name = %s",
            $post_id,
            $field_name
        ), ARRAY_A);

        $result = array();
        foreach ($translations as $translation) {
            $result[$translation['language_code']] = $translation['translated_value'];
        }

        return $result;
    }

    /**
     * Delete translation
     *
     * @param int    $post_id       Post ID
     * @param string $field_name    Field name
     * @param string $language_code Language code
     * @return bool Success
     */
    public function delete_translation($post_id, $field_name, $language_code) {
        return $this->translation_manager->delete_translation($post_id, $field_name, $language_code);
    }

    /**
     * Check if field has translation
     *
     * @param int    $post_id       Post ID
     * @param string $field_name    Field name
     * @param string $language_code Language code
     * @return bool
     */
    public function has_translation($post_id, $field_name, $language_code) {
        return $this->translation_manager->has_translation($post_id, $field_name, $language_code);
    }

    /**
     * Get supported languages from settings
     *
     * @return array Array of language configurations
     */
    protected function get_supported_languages() {
        $languages = get_option('chrmrtns_puzzlesync_languages', array(
            array('code' => 'en', 'name' => 'English', 'flag' => '🇺🇸'),
            array('code' => 'de', 'name' => 'Deutsch', 'flag' => '🇩🇪'),
        ));

        return $languages;
    }

    /**
     * Get field type identifier
     *
     * @return string
     */
    public function get_field_type() {
        return $this->field_type;
    }

    /**
     * Check if this is a Pro feature
     *
     * @return bool
     */
    public function is_pro_feature() {
        return $this->is_pro_feature;
    }

    /**
     * Sanitize field value based on type
     *
     * @param mixed  $value      Value to sanitize
     * @param string $field_type Field type
     * @return mixed Sanitized value
     */
    protected function sanitize_value($value, $field_type = 'text') {
        switch ($field_type) {
            case 'textarea':
            case 'wysiwyg':
                return wp_kses_post($value);

            case 'url':
                return esc_url_raw($value);

            case 'email':
                return sanitize_email($value);

            case 'number':
                return floatval($value);

            case 'text':
            default:
                return sanitize_text_field($value);
        }
    }

    /**
     * Format field value for display
     *
     * @param mixed  $value      Value to format
     * @param string $field_type Field type
     * @return string Formatted value
     */
    protected function format_value($value, $field_type = 'text') {
        switch ($field_type) {
            case 'textarea':
            case 'wysiwyg':
                return wpautop($value);

            case 'url':
                return esc_url($value);

            case 'email':
                return sanitize_email($value);

            case 'text':
            default:
                return esc_html($value);
        }
    }
}
