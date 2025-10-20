=== PuzzleSync - Multilingual Content Manager ===
Contributors: chrmrtns
Tags: multilingual, translation, i18n, language switcher, bricks
Requires at least: 5.8
Tested up to: 6.8
Stable tag: 1.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced multilingual hreflang management system with field-level translation, WooCommerce support, and menu translation for efficient multilingual content management.

== Description ==

PuzzleSync is a powerful multilingual content management plugin that provides advanced hreflang management with custom database storage. It offers efficient translation management and seamless integration with your content workflow.

= Key Features =

* **Field-Level Translation (v1.1.0):** Side-by-side editor for translating content fields
* **WooCommerce Integration (v1.1.0):** Translate products with automatic inventory sync
* **Menu Translation (v1.1.0):** Link menus by language with automatic switching
* **Custom Database Storage:** Optimized database structure for fast multilingual queries
* **Hreflang Management:** Automatic generation and validation of hreflang tags
* **JSON-LD Structured Data:** Automatic generation of JSON-LD schema markup for SEO
* **Translation Groups:** Organize related translations together
* **Validation System:** Built-in validation for multilingual setup
* **Developer Friendly:** Clean codebase following WordPress standards with extensible architecture

= Supported Languages =

* Fully dynamic - supports ANY language configured in settings
* Built-in locale mappings for 30+ languages including:
  * English, German, French, Spanish, Italian, Portuguese
  * Dutch, Polish, Russian, Japanese, Chinese, Korean, Arabic
  * And many more - simply add them in Language Management
* Automatic locale conversion (e.g., 'en' → 'en-US', 'de' → 'de-DE')

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/puzzlesync` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings->PuzzleSync screen to configure the plugin.

== Frequently Asked Questions ==

= How do I add translations for my content? =

Navigate to your post/page editor and use the PuzzleSync meta box to set up translation relationships and hreflang attributes.

= Can I add more languages? =

Yes, the plugin is designed to be extensible. Additional language support can be added through the settings.

= What shortcodes are available? =

Display current language information:
`[puzzlesync_current_language format="name"]`

Available formats: name, code, flag

Display language switcher with flags and text:
`[puzzlesync_language_switcher show_flags="true" show_names="true"]`

Display compact flag-only language switcher:
`[puzzlesync_language_flags size="medium" style="inline"]`

Available sizes: small, medium, large
Available styles: inline, block

= What is JSON-LD structured data? =

PuzzleSync automatically generates JSON-LD structured data for improved SEO. This includes:
* WebPage schema markup with language information
* Multilingual content relationships
* Proper language annotations for search engines

The JSON-LD data is automatically added to the page header when translations are available.

= Are there template functions for developers? =

Yes, several functions are available:
* `puzzlesync_get_current_language()` - Get current language
* `puzzlesync_get_translations($post_id)` - Get available translations for a post
* `puzzlesync_get_hreflang_tags()` - Get hreflang tags for current post

= How do I create custom field integrations? (v1.1.0) =

Extend the `Chrmrtns\PuzzleSync\Translations\FieldTranslator` base class:

```php
use Chrmrtns\PuzzleSync\Translations\FieldTranslator;

class MyCustomIntegration extends FieldTranslator {
    public function __construct() {
        parent::__construct();
        $this->field_type = 'my_custom_fields';
    }

    public function get_translatable_fields($post_id) {
        // Return array of field definitions
    }

    public function get_field_value($post_id, $field_name) {
        // Return field value
    }

    public function set_field_value($post_id, $field_name, $value) {
        // Set field value
    }

    public function is_translatable_field_type($field_type) {
        // Return true if field type is translatable
    }
}
```

Then register it:
```php
add_action('plugins_loaded', function() {
    if (class_exists('\Chrmrtns\PuzzleSync\Translations\TranslationUI')) {
        $ui = new \Chrmrtns\PuzzleSync\Translations\TranslationUI();
        $my_integration = new MyCustomIntegration();
        $ui->register_translator($my_integration);
    }
});
```

== Screenshots ==

1. Settings page overview
2. Language management
3. Statistics
4. Posts with hreflang entries
5. Help screen/How to use

== Changelog ==

= 1.1.0 =
* Added: Field-level translation system with side-by-side editor
* Added: WooCommerce product translation (title, description, short description)
* Added: WooCommerce inventory sync across translations
* Added: WordPress menu translation with automatic language switching
* Added: Translation management dashboard
* Added: New database table for field translations
* Added: Translation UI with tabbed interface for each language
* Added: Support for custom post types in translation system
* Improved: Modular architecture with FieldTranslator base class
* Improved: Extensible integration system for third-party plugins
* Note: WooCommerce attributes and variations marked as Pro features for future release
* Note: Fully backward compatible with v1.0.x - automatic database upgrade

= 1.0.5 =
* Fixed: Critical error in Frontend.php - missing Database namespace import
* Fixed: Broken asset paths for logo and CSS files after directory restructuring
* Fixed: CSS class names updated from chrmrtns-pml-* to chrmrtns-puzzlesync-*
* Fixed: All JavaScript paths now use correct plugin URL constants
* Improved: Dynamic language detection - now supports ALL configured languages, not just English/German
* Improved: Flexible tag naming - tags work with OR without "-version" suffix (e.g., both "english" and "english-version")
* Improved: Standardized language code handling - uses ISO codes (en, de) in database, locale format (en-US, de-DE) for HTML lang attribute
* Added: Support for 30+ language locale mappings (French, Spanish, Italian, Portuguese, Dutch, Polish, Russian, Japanese, Chinese, Korean, Arabic, and more)
* Added: Complete dynamic language detection system across all plugin components
* Changed: Language detection now configuration-driven instead of hardcoded
* Note: All fixes maintain backward compatibility - no data migration required

= 1.0.4 =
* Added: Proper PHP namespaces (Chrmrtns\PuzzleSync\) for better code organization
* Added: PSR-4 compliant autoloader
* Added: Automatic migration from old chrmrtns_pml_ prefix to chrmrtns_puzzlesync_
* Changed: All internal prefixes updated from chrmrtns_pml_ to chrmrtns_puzzlesync_
* Changed: Database table renamed from chrmrtns_pml_hreflang to chrmrtns_puzzlesync_hreflang
* Changed: All option names and meta keys updated to use chrmrtns_puzzlesync_ prefix
* Fixed: JavaScript object naming (pressmlMetaBox → puzzlesyncMetaBox, pressmlSettings → puzzlesyncSettings)
* Improved: Code structure and maintainability with namespaced classes
* Note: Automatic migration preserves all existing data and settings

= 1.0.3 =
* Changed: Plugin renamed from "PressML" to "PuzzleSync" to comply with WordPress.org trademark guidelines
* Changed: Text domain updated from 'pressml' to 'puzzlesync'
* Changed: All shortcodes renamed (pressml_* → puzzlesync_*)
* Changed: Plugin slug updated from 'pressml' to 'puzzlesync'
* Note: Database tables and internal functions remain unchanged (chrmrtns_pml_*) - no migration needed
* Note: This is a branding change only - all functionality remains identical to v1.0.2

= 1.0.2 =
* Fixed: Hreflang tags now correctly output both language-specific tags (en, de) AND x-default tag
* Fixed: JSON-LD structured data now includes translations even when marked as x-default
* Improved: Better SEO compliance with complete hreflang tag implementation

= 1.0.1 =
* Fixed: Removed inline scripts and styles to comply with WordPress.org plugin guidelines
* Improved: Properly enqueued CSS and JavaScript assets using WordPress standards
* Improved: Added wp_localize_script() for JavaScript translations and data passing
* Code quality: Enhanced security and maintainability by following WordPress best practices

= 1.0.0 =
* Initial release as "PressML"
* Custom database storage for hreflang data
* Translation group management
* Validation system with auto-fix capabilities
* Admin interface for content management
* Security hardening and WordPress standards compliance

== Upgrade Notice ==

= 1.0.4 =
* Added: Proper PHP namespaces (Chrmrtns\PuzzleSync\) for better code organization
* Added: PSR-4 compliant autoloader
* Added: Automatic migration from old chrmrtns_pml_ prefix to chrmrtns_puzzlesync_
* Changed: All internal prefixes updated from chrmrtns_pml_ to chrmrtns_puzzlesync_
* Changed: Database table renamed from chrmrtns_pml_hreflang to chrmrtns_puzzlesync_hreflang
* Changed: All option names and meta keys updated to use chrmrtns_puzzlesync_ prefix
* Fixed: JavaScript object naming (pressmlMetaBox → puzzlesyncMetaBox, pressmlSettings → puzzlesyncSettings)
* Improved: Code structure and maintainability with namespaced classes
* Note: Automatic migration preserves all existing data and settings

= 1.0.3 =
Plugin renamed to PuzzleSync to comply with WordPress trademark guidelines. All functionality remains identical. Database tables unchanged - no migration needed.

= 1.0.0 =
Initial release of PressML - Multilingual Content Manager.

== Developer Documentation ==

= Hooks & Filters =

The plugin provides several hooks for customization:

**Core Hooks:**
* `puzzlesync_supported_languages` - Modify supported languages
* `puzzlesync_detect_language` - Custom language detection
* `puzzlesync_hreflang_tags` - Modify hreflang output

**Translation Hooks (v1.1.0):**
* `puzzlesync_translation_post_types` - Filter enabled post types for translation
* `puzzlesync_translatable_fields` - Modify translatable fields list
* `puzzlesync_is_pro_active` - Check if Pro version is active (default: false)

= Database Structure =

**Hreflang Table:** `wp_chrmrtns_puzzlesync_hreflang`
* post_id (bigint) - Post ID
* language_code (varchar) - ISO language code
* url (text) - Translation URL
* is_x_default (tinyint) - Default language flag
* translation_group (varchar) - Group identifier
* priority (tinyint) - Priority order
* created_at, updated_at (datetime)

**Field Translations Table (v1.1.0):** `wp_chrmrtns_puzzlesync_field_translations`
* post_id (bigint) - Post ID
* field_name (varchar) - Field identifier
* field_type (varchar) - Field type (post_meta, woocommerce, acf, etc.)
* language_code (varchar) - ISO language code
* translated_value (longtext) - Translated content
* translation_group (varchar) - Optional group identifier
* is_pro_feature (tinyint) - Pro feature flag
* created_at, updated_at (datetime)

= Architecture (v1.1.0) =

**Core Classes:**
* `Chrmrtns\PuzzleSync\Translations\TranslationManager` - Database operations for field translations
* `Chrmrtns\PuzzleSync\Translations\FieldTranslator` - Abstract base class for integrations
* `Chrmrtns\PuzzleSync\Translations\TranslationUI` - Admin interface component

**Integrations:**
* `Chrmrtns\PuzzleSync\Integrations\WooCommerce` - WooCommerce product translation
* `Chrmrtns\PuzzleSync\Integrations\Menus` - WordPress menu translation

= Requirements =

* WordPress 5.8+
* PHP 7.4+
* MySQL 5.7+