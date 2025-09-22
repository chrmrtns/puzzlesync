<?php
/**
 * Plugin Name: PressML - Multilingual Content Manager
 * Plugin URI: https://github.com/chrmrtns/pressML
 * Description: Advanced multilingual hreflang management system for WordPress with custom database storage
 * Version: 1.0.0
 * Author: Chris Martens
 * Author URI: https://pressml.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pressml
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CHRMRTNS_PML_VERSION', '1.0.0');
define('CHRMRTNS_PML_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CHRMRTNS_PML_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CHRMRTNS_PML_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('CHRMRTNS_PML_TABLE_NAME', 'chrmrtns_pml_hreflang');

// Plugin activation hook
register_activation_hook(__FILE__, 'chrmrtns_pml_activate');
function chrmrtns_pml_activate() {
    chrmrtns_pml_create_database_table();
    chrmrtns_pml_set_default_options();
    flush_rewrite_rules();
}

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'chrmrtns_pml_deactivate');
function chrmrtns_pml_deactivate() {
    flush_rewrite_rules();
}

// Create database table
function chrmrtns_pml_create_database_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . CHRMRTNS_PML_TABLE_NAME;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        language_code varchar(10) NOT NULL,
        url text NOT NULL,
        is_x_default tinyint(1) DEFAULT 0,
        translation_group varchar(100) DEFAULT NULL,
        priority tinyint(2) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY post_id (post_id),
        KEY language_code (language_code),
        KEY translation_group (translation_group),
        UNIQUE KEY unique_post_lang (post_id, language_code)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Store database version for future updates
    add_option('chrmrtns_pml_db_version', CHRMRTNS_PML_VERSION);
}

// Set default plugin options
function chrmrtns_pml_set_default_options() {
    $default_options = array(
        'chrmrtns_pml_enabled' => true,
        'chrmrtns_pml_supported_languages' => array('en', 'de'),
        'chrmrtns_pml_default_language' => 'en',
        'chrmrtns_pml_auto_detect' => true,
        'chrmrtns_pml_show_flags' => true,
        'chrmrtns_pml_enable_json_ld' => true,
        'chrmrtns_pml_enable_validation' => true,
    );

    foreach ($default_options as $option_name => $option_value) {
        if (get_option($option_name) === false) {
            add_option($option_name, $option_value);
        }
    }
}

// Load plugin core files
require_once CHRMRTNS_PML_PLUGIN_DIR . 'includes/class-chrmrtns-pml-core.php';
require_once CHRMRTNS_PML_PLUGIN_DIR . 'includes/class-chrmrtns-pml-admin.php';
require_once CHRMRTNS_PML_PLUGIN_DIR . 'includes/class-chrmrtns-pml-frontend.php';
require_once CHRMRTNS_PML_PLUGIN_DIR . 'includes/class-chrmrtns-pml-database.php';
require_once CHRMRTNS_PML_PLUGIN_DIR . 'includes/class-chrmrtns-pml-validator.php';

// Initialize plugin
add_action('plugins_loaded', 'chrmrtns_pml_init');
function chrmrtns_pml_init() {
    // Text domain is automatically loaded by WordPress for plugins hosted on WordPress.org

    // Initialize core classes
    $core = new Chrmrtns_Pml_Core();
    $core->init();

    // Initialize admin interface if in admin area
    if (is_admin()) {
        $admin = new Chrmrtns_Pml_Admin();
        $admin->init();
    }

    // Initialize frontend output
    if (!is_admin()) {
        $frontend = new Chrmrtns_Pml_Frontend();
        $frontend->init();
    }
}

// Plugin action links
add_filter('plugin_action_links_' . CHRMRTNS_PML_PLUGIN_BASENAME, 'chrmrtns_pml_action_links');
function chrmrtns_pml_action_links($links) {
    $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=pressml-settings')) . '">' . esc_html__('Settings', 'pressml') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}