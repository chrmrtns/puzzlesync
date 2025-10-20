<?php
/**
 * PuzzleSync Uninstall
 *
 * Uninstalling PuzzleSync deletes user settings, database tables and post meta data.
 *
 * @package PuzzleSync
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check user permissions
if (!current_user_can('activate_plugins')) {
    return;
}

// Get WordPress database object
global $wpdb;

// Drop both old and new database tables
$old_table = $wpdb->prefix . 'chrmrtns_pml_hreflang';
$new_table = $wpdb->prefix . 'chrmrtns_puzzlesync_hreflang';

$wpdb->query("DROP TABLE IF EXISTS {$old_table}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query("DROP TABLE IF EXISTS {$new_table}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange

// Delete plugin options (both old and new)
$options_to_delete = array(
    // New options
    'chrmrtns_puzzlesync_db_version',
    'chrmrtns_puzzlesync_enabled',
    'chrmrtns_puzzlesync_supported_languages',
    'chrmrtns_puzzlesync_default_language',
    'chrmrtns_puzzlesync_auto_detect',
    'chrmrtns_puzzlesync_show_flags',
    'chrmrtns_puzzlesync_enable_json_ld',
    'chrmrtns_puzzlesync_enable_validation',
    'chrmrtns_puzzlesync_check_url_accessibility',
    'chrmrtns_puzzlesync_languages',
    'chrmrtns_puzzlesync_auto_menu_flags',
    'chrmrtns_puzzlesync_menu_flags_display',
    'chrmrtns_puzzlesync_migration_version',
    // Old options (for backward compatibility)
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

foreach ($options_to_delete as $option) {
    delete_option($option);
}

// Delete post meta data (both old and new)
$meta_keys_to_delete = array(
    // New meta keys
    'chrmrtns_puzzlesync_translation_group',
    'chrmrtns_puzzlesync_hreflang_default',
    // Old meta keys
    'chrmrtns_pml_hreflang_en',
    'chrmrtns_pml_hreflang_de',
    'chrmrtns_pml_translation_group',
    'chrmrtns_pml_hreflang_default',
    // Legacy meta keys (for backward compatibility)
    'hreflang_en',
    'hreflang_de',
    'translation_group',
    'hreflang_default'
);

// Delete language-specific hreflang fields (dynamic)
$wpdb->delete($wpdb->postmeta, array('meta_key' => 'chrmrtns_pml_hreflang_%'), array('%s')); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'chrmrtns_puzzlesync_hreflang_%'"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

foreach ($meta_keys_to_delete as $meta_key) {
    $wpdb->delete($wpdb->postmeta, array('meta_key' => $meta_key), array('%s')); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
}

// Clear any cached data
wp_cache_flush();
