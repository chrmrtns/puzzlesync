<?php
/**
 * Plugin Name: PuzzleSync - Multilingual Content Manager
 * Plugin URI: https://puzzlesync.com
 * Description: Advanced multilingual hreflang management system for WordPress with custom database storage
 * Version: 1.0.5
 * Author: Chris Martens
 * Author URI: https://chris-martens.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: puzzlesync
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CHRMRTNS_PUZZLESYNC_VERSION', '1.1.0');
define('CHRMRTNS_PUZZLESYNC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CHRMRTNS_PUZZLESYNC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CHRMRTNS_PUZZLESYNC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('CHRMRTNS_PUZZLESYNC_TABLE_NAME', 'chrmrtns_puzzlesync_hreflang');
define('CHRMRTNS_PUZZLESYNC_FIELD_TRANSLATIONS_TABLE', 'chrmrtns_puzzlesync_field_translations');

// Load autoloader
require_once CHRMRTNS_PUZZLESYNC_PLUGIN_DIR . 'includes/Autoloader.php';
\Chrmrtns\PuzzleSync\Autoloader::register();

// Load migration class (autoloader will handle this, but load explicitly for clarity)
require_once CHRMRTNS_PUZZLESYNC_PLUGIN_DIR . 'includes/Migrations/Migration.php';

// Namespaced classes will be autoloaded
use Chrmrtns\PuzzleSync\Core\Core;
use Chrmrtns\PuzzleSync\Admin\Admin;
use Chrmrtns\PuzzleSync\Frontend\Frontend;

// Plugin activation hook
register_activation_hook(__FILE__, 'chrmrtns_puzzlesync_activate');
function chrmrtns_puzzlesync_activate() {
    chrmrtns_puzzlesync_create_database_table();
    chrmrtns_puzzlesync_create_field_translations_table();
    chrmrtns_puzzlesync_set_default_options();

    // Run migration from old to new
    \Chrmrtns\PuzzleSync\Migrations\Migration::run();

    flush_rewrite_rules();
}

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'chrmrtns_puzzlesync_deactivate');
function chrmrtns_puzzlesync_deactivate() {
    flush_rewrite_rules();
}

// Create database table
function chrmrtns_puzzlesync_create_database_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . CHRMRTNS_PUZZLESYNC_TABLE_NAME;
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
    add_option('chrmrtns_puzzlesync_db_version', CHRMRTNS_PUZZLESYNC_VERSION);
}

// Create field translations table
function chrmrtns_puzzlesync_create_field_translations_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . CHRMRTNS_PUZZLESYNC_FIELD_TRANSLATIONS_TABLE;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        field_name varchar(255) NOT NULL,
        field_type varchar(50) NOT NULL,
        language_code varchar(10) NOT NULL,
        translated_value longtext,
        translation_group varchar(100) DEFAULT NULL,
        is_pro_feature tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY post_id_field (post_id, field_name),
        KEY language_code (language_code),
        KEY translation_group (translation_group),
        UNIQUE KEY unique_post_field_lang (post_id, field_name, language_code)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Check for database upgrades
function chrmrtns_puzzlesync_check_db_upgrade() {
    $current_db_version = get_option('chrmrtns_puzzlesync_db_version', '1.0.0');

    if (version_compare($current_db_version, CHRMRTNS_PUZZLESYNC_VERSION, '<')) {
        // Run database upgrades
        chrmrtns_puzzlesync_create_database_table();
        chrmrtns_puzzlesync_create_field_translations_table();

        // Update database version
        update_option('chrmrtns_puzzlesync_db_version', CHRMRTNS_PUZZLESYNC_VERSION);
    }
}

// Set default plugin options
function chrmrtns_puzzlesync_set_default_options() {
    $default_options = array(
        'chrmrtns_puzzlesync_enabled' => true,
        'chrmrtns_puzzlesync_supported_languages' => array('en', 'de'),
        'chrmrtns_puzzlesync_default_language' => 'en',
        'chrmrtns_puzzlesync_auto_detect' => true,
        'chrmrtns_puzzlesync_show_flags' => true,
        'chrmrtns_puzzlesync_enable_json_ld' => true,
        'chrmrtns_puzzlesync_enable_validation' => true,
        'chrmrtns_puzzlesync_enabled_post_types' => array('post', 'page', 'product'), // v1.1.0
    );

    foreach ($default_options as $option_name => $option_value) {
        if (get_option($option_name) === false) {
            add_option($option_name, $option_value);
        }
    }
}

// Initialize plugin
add_action('plugins_loaded', 'chrmrtns_puzzlesync_init');
function chrmrtns_puzzlesync_init() {
    // Check for database upgrades
    chrmrtns_puzzlesync_check_db_upgrade();

    // Run migration check on every load (only runs if needed)
    \Chrmrtns\PuzzleSync\Migrations\Migration::run();

    // Text domain is automatically loaded by WordPress for plugins hosted on WordPress.org

    // Initialize core classes with namespaced versions
    $core = new Core();
    $core->init();

    // Initialize admin interface if in admin area
    if (is_admin()) {
        $admin = new Admin();
        $admin->init();
    }

    // Initialize frontend output
    if (!is_admin()) {
        $frontend = new Frontend();
        $frontend->init();
    }

    // Initialize translation features (v1.1.0+)
    chrmrtns_puzzlesync_init_translations();
}

// Initialize translation features
function chrmrtns_puzzlesync_init_translations() {
    // Initialize Translation UI for admin
    if (is_admin()) {
        $translation_ui = new \Chrmrtns\PuzzleSync\Translations\TranslationUI();
        $translation_ui->init();

        // Register WooCommerce translator
        if (class_exists('WooCommerce')) {
            $woo_translator = new \Chrmrtns\PuzzleSync\Integrations\WooCommerce();
            $translation_ui->register_translator($woo_translator);
        }
    }

    // Initialize WooCommerce integration (frontend)
    if (!is_admin() && class_exists('WooCommerce')) {
        $woo_integration = new \Chrmrtns\PuzzleSync\Integrations\WooCommerce();
        $woo_integration->init();
    }

    // Initialize Menu translation (both admin and frontend)
    $menu_integration = new \Chrmrtns\PuzzleSync\Integrations\Menus();
    $menu_integration->init();
}

// Plugin action links
add_filter('plugin_action_links_' . CHRMRTNS_PUZZLESYNC_PLUGIN_BASENAME, 'chrmrtns_puzzlesync_action_links');
function chrmrtns_puzzlesync_action_links($links) {
    $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=puzzlesync-settings')) . '">' . esc_html__('Settings', 'puzzlesync') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
