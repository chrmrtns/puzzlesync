<?php
/**
 * PressML Uninstall
 *
 * Uninstalling PressML deletes user settings, database tables and post meta data.
 *
 * @package PressML
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

// Define table name
$table_name = $wpdb->prefix . 'chrmrtns_pml_hreflang';

// Drop the custom database table
$wpdb->query("DROP TABLE IF EXISTS {$table_name}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange

// Delete plugin options
$options_to_delete = array(
    'chrmrtns_pml_db_version',
    'chrmrtns_pml_enabled',
    'chrmrtns_pml_supported_languages',
    'chrmrtns_pml_default_language',
    'chrmrtns_pml_auto_detect',
    'chrmrtns_pml_show_flags',
    'chrmrtns_pml_enable_json_ld',
    'chrmrtns_pml_enable_validation',
    'chrmrtns_pml_check_url_accessibility'
);

foreach ($options_to_delete as $option) {
    delete_option($option);
}

// Delete post meta data
$meta_keys_to_delete = array(
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

foreach ($meta_keys_to_delete as $meta_key) {
    $wpdb->delete($wpdb->postmeta, array('meta_key' => $meta_key), array('%s')); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
}

// Clear any cached data
wp_cache_flush();