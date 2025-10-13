=== PressML - Multilingual Content Manager ===
Contributors: chrmrtns
Tags: multilingual, translation, i18n, language switcher, bricks
Requires at least: 5.8
Tested up to: 6.8
Stable tag: 1.0.2
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced multilingual hreflang management system with custom database storage for efficient content translation management.

== Description ==

PressML is a powerful multilingual content management plugin that provides advanced hreflang management with custom database storage. It offers efficient translation management and seamless integration with your content workflow.

= Key Features =

* Custom Database Storage: Optimized database structure for fast multilingual queries
* Hreflang Management: Automatic generation and validation of hreflang tags
* JSON-LD Structured Data: Automatic generation of JSON-LD schema markup for SEO
* Translation Groups: Organize related translations together
* Validation System: Built-in validation for multilingual setup
* Developer Friendly: Clean codebase following WordPress standards

= Supported Languages =

* English (en)
* German (de)
* Extensible for additional languages

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/pressml` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings->PressML screen to configure the plugin.

== Frequently Asked Questions ==

= How do I add translations for my content? =

Navigate to your post/page editor and use the PressML meta box to set up translation relationships and hreflang attributes.

= Can I add more languages? =

Yes, the plugin is designed to be extensible. Additional language support can be added through the settings.

= What shortcodes are available? =

Display current language information:
`[pressml_current_language format="name"]`

Available formats: name, code, flag

Display language switcher with flags and text:
`[pressml_language_switcher show_flags="true" show_names="true"]`

Display compact flag-only language switcher:
`[pressml_language_flags size="medium" style="inline"]`

Available sizes: small, medium, large
Available styles: inline, block

= What is JSON-LD structured data? =

PressML automatically generates JSON-LD structured data for improved SEO. This includes:
* WebPage schema markup with language information
* Multilingual content relationships
* Proper language annotations for search engines

The JSON-LD data is automatically added to the page header when translations are available.

= Are there template functions for developers? =

Yes, several functions are available:
* `pressml_get_current_language()` - Get current language
* `pressml_get_translations($post_id)` - Get available translations for a post
* `pressml_get_hreflang_tags()` - Get hreflang tags for current post

== Screenshots ==

1. Settings page overview
2. Post editor meta box
3. Validation results
4. Statistics dashboard

== Changelog ==

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
* Initial release
* Custom database storage for hreflang data
* Translation group management
* Validation system with auto-fix capabilities
* Admin interface for content management
* Security hardening and WordPress standards compliance

== Upgrade Notice ==

= 1.0.0 =
Initial release of PressML - Multilingual Content Manager.