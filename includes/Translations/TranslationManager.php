<?php
/**
 * Translation Manager
 *
 * Core class for managing field-level translations
 *
 * @package PuzzleSync
 * @since 1.1.0
 */

namespace Chrmrtns\PuzzleSync\Translations;

if (!defined('ABSPATH')) {
    exit;
}

class TranslationManager {

    /**
     * @var string Field translations table name
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . CHRMRTNS_PUZZLESYNC_FIELD_TRANSLATIONS_TABLE;
    }

    /**
     * Save a field translation
     *
     * @param int    $post_id           Post ID
     * @param string $field_name        Field name
     * @param string $field_type        Field type (post_meta, acf, metabox, woo_attribute)
     * @param string $language_code     Language code (en, de, etc.)
     * @param string $translated_value  Translated value
     * @param string $translation_group Translation group (optional)
     * @param bool   $is_pro_feature    Whether this is a Pro feature
     * @return bool Success
     */
    public function save_translation($post_id, $field_name, $field_type, $language_code, $translated_value, $translation_group = '', $is_pro_feature = false) {
        global $wpdb;

        // Check if translation exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE post_id = %d AND field_name = %s AND language_code = %s",
            $post_id,
            $field_name,
            $language_code
        ));

        $data = array(
            'post_id'           => $post_id,
            'field_name'        => $field_name,
            'field_type'        => $field_type,
            'language_code'     => $language_code,
            'translated_value'  => $translated_value,
            'translation_group' => $translation_group,
            'is_pro_feature'    => $is_pro_feature ? 1 : 0,
        );

        $format = array('%d', '%s', '%s', '%s', '%s', '%s', '%d');

        if ($existing) {
            // Update existing translation
            $result = $wpdb->update(
                $this->table_name,
                $data,
                array('id' => $existing->id),
                $format,
                array('%d')
            );
        } else {
            // Insert new translation
            $result = $wpdb->insert(
                $this->table_name,
                $data,
                $format
            );
        }

        return $result !== false;
    }

    /**
     * Get a translated field value
     *
     * @param int    $post_id       Post ID
     * @param string $field_name    Field name
     * @param string $language_code Language code
     * @return string|null Translated value or null if not found
     */
    public function get_translation($post_id, $field_name, $language_code) {
        global $wpdb;

        $translation = $wpdb->get_var($wpdb->prepare(
            "SELECT translated_value FROM {$this->table_name} WHERE post_id = %d AND field_name = %s AND language_code = %s",
            $post_id,
            $field_name,
            $language_code
        ));

        return $translation;
    }

    /**
     * Get all translations for a post
     *
     * @param int    $post_id       Post ID
     * @param string $language_code Optional: filter by language code
     * @return array Array of translation objects
     */
    public function get_post_translations($post_id, $language_code = '') {
        global $wpdb;

        if ($language_code) {
            $translations = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE post_id = %d AND language_code = %s ORDER BY field_name",
                $post_id,
                $language_code
            ));
        } else {
            $translations = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE post_id = %d ORDER BY language_code, field_name",
                $post_id
            ));
        }

        return $translations ?: array();
    }

    /**
     * Get all fields that have translations for a post
     *
     * @param int $post_id Post ID
     * @return array Array of field names
     */
    public function get_translated_fields($post_id) {
        global $wpdb;

        $fields = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT field_name FROM {$this->table_name} WHERE post_id = %d ORDER BY field_name",
            $post_id
        ));

        return $fields ?: array();
    }

    /**
     * Delete a field translation
     *
     * @param int    $post_id       Post ID
     * @param string $field_name    Field name
     * @param string $language_code Language code
     * @return bool Success
     */
    public function delete_translation($post_id, $field_name, $language_code) {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table_name,
            array(
                'post_id'       => $post_id,
                'field_name'    => $field_name,
                'language_code' => $language_code,
            ),
            array('%d', '%s', '%s')
        );

        return $result !== false;
    }

    /**
     * Delete all translations for a post
     *
     * @param int $post_id Post ID
     * @return bool Success
     */
    public function delete_post_translations($post_id) {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table_name,
            array('post_id' => $post_id),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Check if a field has a translation
     *
     * @param int    $post_id       Post ID
     * @param string $field_name    Field name
     * @param string $language_code Language code
     * @return bool
     */
    public function has_translation($post_id, $field_name, $language_code) {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE post_id = %d AND field_name = %s AND language_code = %s",
            $post_id,
            $field_name,
            $language_code
        ));

        return $count > 0;
    }

    /**
     * Get translation statistics for a post
     *
     * @param int $post_id Post ID
     * @return array Statistics array with language codes and field counts
     */
    public function get_translation_stats($post_id) {
        global $wpdb;

        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT language_code, COUNT(*) as field_count FROM {$this->table_name} WHERE post_id = %d GROUP BY language_code",
            $post_id
        ), ARRAY_A);

        return $stats ?: array();
    }

    /**
     * Get all posts with translations in a specific language
     *
     * @param string $language_code Language code
     * @return array Array of post IDs
     */
    public function get_posts_by_language($language_code) {
        global $wpdb;

        $post_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$this->table_name} WHERE language_code = %s ORDER BY post_id",
            $language_code
        ));

        return $post_ids ?: array();
    }

    /**
     * Bulk save translations
     *
     * @param int    $post_id           Post ID
     * @param string $language_code     Language code
     * @param array  $translations      Array of field_name => translated_value pairs
     * @param string $field_type        Field type
     * @param string $translation_group Translation group (optional)
     * @param bool   $is_pro_feature    Whether this is a Pro feature
     * @return bool Success
     */
    public function bulk_save_translations($post_id, $language_code, $translations, $field_type, $translation_group = '', $is_pro_feature = false) {
        $success = true;

        foreach ($translations as $field_name => $translated_value) {
            $result = $this->save_translation(
                $post_id,
                $field_name,
                $field_type,
                $language_code,
                $translated_value,
                $translation_group,
                $is_pro_feature
            );

            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }
}
