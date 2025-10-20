<?php
/**
 * PuzzleSync Migration Class
 *
 * Handles migration from old chrmrtns_pml_ prefix to new chrmrtns_puzzlesync_ prefix
 *
 * @package PuzzleSync
 * @since 1.0.4
 */

namespace Chrmrtns\PuzzleSync\Migrations;

if (!defined('ABSPATH')) {
    exit;
}

class Migration {

    /**
     * Migration version
     */
    const MIGRATION_VERSION = '1.0.4';

    /**
     * Run all migrations
     */
    public static function run() {
        $current_version = get_option('chrmrtns_puzzlesync_migration_version', '0');

        if (version_compare($current_version, self::MIGRATION_VERSION, '<')) {
            self::migrate_from_pml_to_puzzlesync();
            update_option('chrmrtns_puzzlesync_migration_version', self::MIGRATION_VERSION);
        }
    }

    /**
     * Migrate from old chrmrtns_pml prefix to new chrmrtns_puzzlesync prefix
     */
    private static function migrate_from_pml_to_puzzlesync() {
        global $wpdb;

        // Check if old table exists
        $old_table = $wpdb->prefix . 'chrmrtns_pml_hreflang';
        $new_table = $wpdb->prefix . 'chrmrtns_puzzlesync_hreflang';

        // Rename database table if old one exists and new one doesn't
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $old_table)) === $old_table &&
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $new_table)) !== $new_table) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query("RENAME TABLE `$old_table` TO `$new_table`");
        }

        // Migrate options
        self::migrate_options();

        // Migrate post meta
        self::migrate_post_meta();
    }

    /**
     * Migrate option names from old to new prefix
     */
    private static function migrate_options() {
        $options_map = array(
            'chrmrtns_pml_db_version' => 'chrmrtns_puzzlesync_db_version',
            'chrmrtns_pml_enabled' => 'chrmrtns_puzzlesync_enabled',
            'chrmrtns_pml_supported_languages' => 'chrmrtns_puzzlesync_supported_languages',
            'chrmrtns_pml_default_language' => 'chrmrtns_puzzlesync_default_language',
            'chrmrtns_pml_auto_detect' => 'chrmrtns_puzzlesync_auto_detect',
            'chrmrtns_pml_show_flags' => 'chrmrtns_puzzlesync_show_flags',
            'chrmrtns_pml_enable_json_ld' => 'chrmrtns_puzzlesync_enable_json_ld',
            'chrmrtns_pml_enable_validation' => 'chrmrtns_puzzlesync_enable_validation',
            'chrmrtns_pml_check_url_accessibility' => 'chrmrtns_puzzlesync_check_url_accessibility',
            'chrmrtns_pml_languages' => 'chrmrtns_puzzlesync_languages',
            'chrmrtns_pml_auto_menu_flags' => 'chrmrtns_puzzlesync_auto_menu_flags',
            'chrmrtns_pml_menu_flags_display' => 'chrmrtns_puzzlesync_menu_flags_display',
        );

        foreach ($options_map as $old_option => $new_option) {
            $value = get_option($old_option);
            if ($value !== false) {
                // Add new option
                add_option($new_option, $value);
                // Keep old option for backward compatibility during transition
                // Will be removed in future version
            }
        }
    }

    /**
     * Migrate post meta keys from old to new prefix
     */
    private static function migrate_post_meta() {
        global $wpdb;

        $meta_keys_patterns = array(
            'chrmrtns_pml_hreflang_%',
            'chrmrtns_pml_translation_group',
            'chrmrtns_pml_hreflang_default',
        );

        foreach ($meta_keys_patterns as $pattern) {
            // Get all posts with old meta keys
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $old_meta = $wpdb->get_results($wpdb->prepare(
                "SELECT post_id, meta_key, meta_value
                FROM {$wpdb->postmeta}
                WHERE meta_key LIKE %s",
                $pattern
            ));

            foreach ($old_meta as $meta) {
                // Create new meta key
                $new_meta_key = str_replace('chrmrtns_pml_', 'chrmrtns_puzzlesync_', $meta->meta_key);

                // Add new meta (don't delete old ones yet for backward compatibility)
                add_post_meta($meta->post_id, $new_meta_key, $meta->meta_value, true);
            }
        }
    }

    /**
     * Cleanup old data (optional - call this manually when ready)
     */
    public static function cleanup_old_data() {
        global $wpdb;

        // Delete old options
        $old_options = array(
            'chrmrtns_pml_db_version',
            'chrmrtns_pml_enabled',
            'chrmrtns_pml_supported_languages',
            'chrmrtns_pml_default_language',
            'chrmrtns_pml_auto_detect',
            'chrmrtns_pml_show_flags',
            'chrmrtns_pml_enable_json_ld',
            'chrmrtns_pml_enable_validation',
            'chrmrtns_pml_check_url_accessibility',
            'chrmrtns_pml_languages',
            'chrmrtns_pml_auto_menu_flags',
            'chrmrtns_pml_menu_flags_display',
        );

        foreach ($old_options as $option) {
            delete_option($option);
        }

        // Delete old post meta
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            "DELETE FROM {$wpdb->postmeta}
            WHERE meta_key LIKE 'chrmrtns_pml_%'"
        );
    }
}
