=== PuzzleSync - Multilingual Content Manager ===
Contributors: chrmrtns
Tags: multilingual, hreflang, translation, seo, language switcher
Requires at least: 5.8
Tested up to: 6.8
Stable tag: 1.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage multilingual content with automatic hreflang tags, field-level translations, WooCommerce support, and menu translation for better SEO.

== Description ==

**PuzzleSync helps you rank in multiple countries by properly managing multilingual content for search engines.**

If you run a multilingual WordPress site, you need proper hreflang implementation to tell Google which language version to show in each country. PuzzleSync makes this easy with automatic hreflang tag generation, translation management, and SEO validation - without requiring expensive translation plugins or complex configurations.

= Why Choose PuzzleSync? =

**🎯 Simple Language Detection**
Just add a category or tag to your posts (like "English" or "Deutsch") and PuzzleSync automatically:
* Detects the content language
* Generates proper hreflang tags
* Sets the correct HTML lang attribute
* Creates JSON-LD structured data for search engines

No need for complex language codes or technical setup!

**🌍 Truly Multilingual**
Unlike plugins limited to 2-3 languages, PuzzleSync supports ANY language you configure:
* English, German, French, Spanish, Italian, Portuguese, Dutch, Polish, Russian
* Japanese, Chinese, Korean, Arabic, Turkish, Swedish, Danish, Norwegian
* 30+ built-in language mappings, easily add more in settings
* Automatic locale conversion (en → en-US, de → de-DE, etc.)

**⚡ Fast & Lightweight**
* Custom database storage (not post meta) for instant queries
* No impact on page load speed
* Works with any theme or page builder (Gutenberg, Elementor, Bricks, etc.)

**✅ Built-in Validation**
* Automatic detection of broken translation links
* One-click fixes for common issues
* Validation dashboard shows exactly what needs attention

**🔧 Flexible & Developer-Friendly**
* Works with categories OR tags (your choice)
* Tag naming is flexible: "english", "English", "en", "english-version" all work!
* Translation Groups for automatic linking
* Clean, modern PHP code with namespaces
* Shortcodes for language switchers

**🛒 WooCommerce Integration (NEW in v1.1.0)**
* Translate product titles, descriptions, and short descriptions
* Side-by-side translation editor in product edit screen
* Automatic inventory sync across translations (shared stock)
* Pricing synced across language versions
* Works seamlessly with your existing WooCommerce setup

**📝 Field-Level Translation (NEW in v1.1.0)**
* Translate custom fields with intuitive side-by-side editor
* Support for posts, pages, and custom post types
* Visual translation progress indicators
* Tabbed interface for managing multiple languages
* Extensible architecture for third-party plugins

**🍔 Menu Translation (NEW in v1.1.0)**
* Link navigation menus by language
* Automatic menu switching based on content language
* Easy-to-use admin interface
* No coding required

= Perfect For =

* Multilingual blogs and business sites
* International e-commerce stores
* Content creators targeting multiple countries
* SEO professionals managing multilingual sites
* Agencies building sites for international clients

= How It Works =

1. **Add languages** in settings (English, German, French, etc.)
2. **Tag your content** with categories or tags (e.g., "english", "deutsch")
3. **Link translations** using Translation Groups or manual URLs
4. **Done!** PuzzleSync automatically generates all hreflang tags and SEO markup

= Language Support =

**Supports ANY language** - just add it in settings! Built-in support includes:

* **European:** English, German, French, Spanish, Italian, Portuguese, Dutch, Polish, Russian, Czech, Hungarian, Romanian, Greek, Ukrainian, Croatian, Slovak, Bulgarian, Swedish, Danish, Norwegian, Finnish
* **Asian:** Japanese, Chinese, Korean, Thai, Vietnamese, Indonesian, Hebrew
* **Middle Eastern:** Arabic, Turkish
* **And more** - easily add any language you need!

PuzzleSync automatically handles proper locale formatting (en → en-US, de → de-DE, ja → ja-JP, etc.)

== Installation ==

= Automatic Installation (Recommended) =

1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "PuzzleSync"
3. Click **Install Now**, then **Activate**
4. Go to **PuzzleSync > Language Management** to add your languages
5. Start tagging your content!

= Manual Installation =

1. Download the plugin zip file
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the zip file and click **Install Now**
4. Click **Activate Plugin**
5. Go to **PuzzleSync > Language Management** to configure

= First Steps After Installation =

1. **Add Languages:** Go to PuzzleSync > Language Management and add the languages your site uses
2. **Tag Your Content:** Add a category or tag to your posts matching the language (e.g., "english", "deutsch")
3. **Link Translations:** Use the same Translation Group name in the PuzzleSync meta box for related posts
4. **Verify Setup:** Go to PuzzleSync > Validator to check everything is working correctly

== Frequently Asked Questions ==

= How do I set up my first multilingual content? =

1. Go to **PuzzleSync > Language Management** and add your languages (e.g., English, German)
2. Create/edit a post and assign it a category or tag matching the language (e.g., "English" category or "english" tag)
3. Create the translation post and assign it the other language (e.g., "German" category)
4. Give both posts the same **Translation Group** name in the PuzzleSync meta box
5. Done! PuzzleSync automatically generates hreflang tags linking them together

= What language tags/categories do I need to use? =

Very flexible! All of these work:
* Categories: "english", "English", "deutsch", "Deutsch"
* Tags: "english", "en", "EN", "english-version" (all work!)
* Language codes: "en", "de", "fr", "es" etc.

PuzzleSync detects them all automatically. Use whatever makes sense for your workflow!

= Do I need to use categories or tags? =

Either one! You can use:
* **Categories only** (easier if you organize content by language)
* **Tags only** (if you prefer to keep categories for other things)
* **Both** (PuzzleSync checks both)

Choose what fits your site structure best.

= Can I add more languages beyond English and German? =

Absolutely! Go to **PuzzleSync > Language Management** and add any language:
* French, Spanish, Italian, Portuguese, Dutch, Polish
* Japanese, Chinese, Korean, Arabic, Russian
* Any language you need - PuzzleSync supports them all!

Just add the language, then tag your content with that language name.

= Will this slow down my site? =

No! PuzzleSync uses a custom database table (not WordPress post meta) for lightning-fast queries. The hreflang tags are generated server-side with zero JavaScript, so there's no impact on page load speed.

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

= How do I translate WooCommerce products? (v1.1.0) =

1. Edit a product in WooCommerce
2. Assign a language category or tag (e.g., "English")
3. Scroll down to the **Field Translations** meta box
4. Click a language tab (e.g., German)
5. Enter translations for product name, description, and short description
6. Save the product

Inventory and pricing are automatically synced across all language versions!

= How do I set up menu translation? (v1.1.0) =

1. Create separate navigation menus for each language (e.g., "Main Menu" and "Hauptmenü")
2. Go to **Settings > Menu Translations**
3. Assign a language to each menu
4. Link the translated versions using the dropdown columns
5. Save - menus will automatically switch based on content language!

= How does the field translation editor work? (v1.1.0) =

When editing a post, page, or product, you'll see a **Field Translations** meta box with:
* Tabs for each language you've configured
* Side-by-side view showing original text and translation field
* Visual progress indicators showing translation completion
* Support for text fields, textareas, and rich content

Simply click a language tab, fill in the translations, and save!

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

= 1.1.0 =
Major feature release! Adds field-level translation system, WooCommerce product translation, and menu translation. Side-by-side translation editor makes managing multilingual content easier than ever. Fully backward compatible with automatic database upgrade.

= 1.0.5 =
Critical bug fixes and major improvements! This update fixes a fatal error from 1.0.4, restores missing admin assets, and adds dynamic support for ALL languages (not just English/German). Highly recommended upgrade with full backward compatibility.

= 1.0.4 =
Major code refactoring to modern PHP namespaces. Automatic migration included - all data preserved. Safe to upgrade.

= 1.0.3 =
Plugin renamed to PuzzleSync to comply with WordPress trademark guidelines. All functionality remains identical. Database tables unchanged - no migration needed.