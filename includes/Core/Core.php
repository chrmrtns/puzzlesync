<?php
/**
 * Core functionality class for PuzzleSync plugin
 *
 * @package PuzzleSync
 * @since 1.0.4
 */
namespace Chrmrtns\PuzzleSync\Core;

use Chrmrtns\PuzzleSync\Database\Database;
if (!defined('ABSPATH')) {
    exit;
}

class Core {

    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function init() {
        // Hook into WordPress head to output hreflang tags
        add_action('wp_head', array($this, 'output_hreflang_tags'));

        // Modify HTML lang attribute
        add_filter('language_attributes', array($this, 'modify_language_attributes'));

        // Add JSON-LD structured data if enabled
        if (get_option('chrmrtns_puzzlesync_enable_json_ld', true)) {
            add_action('wp_footer', array($this, 'output_json_ld'));
        }
    }

    /**
     * Output hreflang tags in the head section
     */
    public function output_hreflang_tags() {
        if (!is_singular()) {
            return;
        }

        global $post;
        $hreflang_data = $this->get_hreflang_data($post->ID);

        if (empty($hreflang_data)) {
            return;
        }

        // First pass: Output all language-specific tags
        foreach ($hreflang_data as $item) {
            echo '<link rel="alternate" hreflang="' . esc_attr($item->language_code) . '" href="' . esc_url($item->url) . '" />' . "\n";
        }

        // Second pass: Output x-default tag if one is marked
        foreach ($hreflang_data as $item) {
            if ($item->is_x_default) {
                echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($item->url) . '" />' . "\n";
                break; // Only output one x-default tag
            }
        }
    }

    /**
     * Get hreflang data for a post
     */
    public function get_hreflang_data($post_id) {
        // Priority 1: Check database for stored hreflang data
        $db_data = $this->db->get_hreflang_by_post($post_id);
        if (!empty($db_data)) {
            return $db_data;
        }

        // Priority 2: Check for custom fields (backward compatibility)
        $hreflang_urls = array();
        $hreflang_en = get_post_meta($post_id, 'chrmrtns_puzzlesync_hreflang_en', true);
        $hreflang_de = get_post_meta($post_id, 'chrmrtns_puzzlesync_hreflang_de', true);

        if ($hreflang_en || $hreflang_de) {
            if ($hreflang_en) {
                $hreflang_urls['en'] = $hreflang_en;
            }
            if ($hreflang_de) {
                $hreflang_urls['de'] = $hreflang_de;
            }

            // Convert to database format and save
            $this->migrate_custom_fields_to_db($post_id, $hreflang_urls);
            return $this->db->get_hreflang_by_post($post_id);
        }

        // Priority 3: Check for category-based automatic linking
        $category_urls = $this->get_category_based_hreflang($post_id);
        if (!empty($category_urls)) {
            $this->save_hreflang_data($post_id, $category_urls);
            return $this->db->get_hreflang_by_post($post_id);
        }

        return array();
    }

    /**
     * Get category-based hreflang URLs
     */
    private function get_category_based_hreflang($post_id) {
        $post = get_post($post_id);
        $hreflang_urls = array();

        $current_lang = $this->detect_post_language($post);
        if (!$current_lang) {
            return $hreflang_urls;
        }

        // Add current post URL for its language
        $hreflang_urls[$current_lang] = get_permalink($post->ID);

        // Find translations in other languages
        $supported_languages = $this->get_supported_languages();
        foreach ($supported_languages as $lang) {
            if ($lang['code'] === $current_lang) {
                continue; // Skip current language
            }

            $translation_post = $this->find_translation_by_category($post, $lang['name']);
            if ($translation_post) {
                $hreflang_urls[$lang['code']] = get_permalink($translation_post->ID);
            }
        }

        return $hreflang_urls;
    }

    /**
     * Find translation by category
     */
    private function find_translation_by_category($post, $target_language) {
        $translation_group = get_post_meta($post->ID, 'chrmrtns_puzzlesync_translation_group', true);

        $query_args = array(
            'post_type' => $post->post_type,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'exclude' => array($post->ID) // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
        );

        if ($translation_group) {
            $query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                array(
                    'key' => 'chrmrtns_puzzlesync_translation_group',
                    'value' => $translation_group,
                    'compare' => '='
                )
            );

            $query_args['category_name'] = ($target_language === 'english') ? 'english' : 'german';

            $translation_posts = get_posts($query_args);
            if (!empty($translation_posts)) {
                return $translation_posts[0];
            }
        }

        // Fallback: Search by slug pattern
        $base_slug = preg_replace('/-(en|de)$/', '', $post->post_name);
        $target_slug = $base_slug . '-' . ($target_language === 'english' ? 'en' : 'de');

        $query_args['name'] = $target_slug;
        unset($query_args['meta_query']);
        $query_args['category_name'] = ($target_language === 'english') ? 'english' : 'german';

        $translation_posts = get_posts($query_args);
        return !empty($translation_posts) ? $translation_posts[0] : null;
    }

    /**
     * Modify language attributes based on post language
     */
    public function modify_language_attributes($output) {
        if (!is_singular()) {
            return $output;
        }

        global $post;

        $lang_code = $this->detect_post_language($post);
        if ($lang_code) {
            $locale = $this->language_code_to_locale($lang_code);
            return 'lang="' . esc_attr($locale) . '"';
        }

        // Check hreflang settings
        $hreflang_data = $this->get_hreflang_data($post->ID);
        if (!empty($hreflang_data)) {
            $has_en = false;
            $has_de = false;

            foreach ($hreflang_data as $item) {
                if ($item->language_code === 'en') $has_en = true;
                if ($item->language_code === 'de') $has_de = true;
            }

            if ($has_en && !$has_de) {
                return 'lang="de-DE"';
            } elseif ($has_de && !$has_en) {
                return 'lang="en-US"';
            }
        }

        return $output;
    }

    /**
     * Output JSON-LD structured data
     */
    public function output_json_ld() {
        if (!is_singular()) {
            return;
        }

        global $post;
        $hreflang_data = $this->get_hreflang_data($post->ID);

        if (empty($hreflang_data) || count($hreflang_data) <= 1) {
            return;
        }

        $json_ld = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            '@id' => get_permalink($post->ID),
            'url' => get_permalink($post->ID),
            'name' => get_the_title($post->ID),
            'inLanguage' => $this->determine_post_language($post),
            'isPartOf' => array(
                '@type' => 'WebSite',
                'url' => home_url(),
                'name' => get_bloginfo('name')
            )
        );

        $translations = array();
        foreach ($hreflang_data as $item) {
            // Include all translations except the current page itself
            if ($item->url !== get_permalink($post->ID)) {
                $translations[] = array(
                    '@type' => 'WebPage',
                    'url' => $item->url,
                    'inLanguage' => ($item->language_code === 'en') ? 'en-US' : 'de-DE'
                );
            }
        }

        if (!empty($translations)) {
            $json_ld['translationOfWork'] = $translations;
        }

        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($json_ld, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo "\n" . '</script>' . "\n";
    }

    /**
     * Determine post language (returns locale format like 'en-US')
     */
    private function determine_post_language($post) {
        $lang_code = $this->detect_post_language($post);

        if ($lang_code) {
            return $this->language_code_to_locale($lang_code);
        }

        // Fallback to site locale
        $locale = get_locale();
        $locale_parts = explode('_', $locale);
        if (count($locale_parts) >= 2) {
            return $locale_parts[0] . '-' . strtoupper($locale_parts[1]);
        }

        // Default fallback
        return 'en-US';
    }

    /**
     * Save hreflang data to database
     */
    private function save_hreflang_data($post_id, $urls) {
        foreach ($urls as $lang => $url) {
            $this->db->insert_or_update_hreflang($post_id, $lang, $url);
        }

        // Set x-default
        $default_lang = $this->determine_x_default($urls, $post_id);
        if ($default_lang && isset($urls[$default_lang])) {
            $this->db->set_x_default($post_id, $default_lang);
        }
    }

    /**
     * Determine x-default language
     */
    private function determine_x_default($urls, $post_id) {
        $default_meta = get_post_meta($post_id, 'chrmrtns_puzzlesync_hreflang_default', true);

        if ($default_meta && isset($urls[$default_meta])) {
            return $default_meta;
        }

        $locale = get_locale();
        if ($locale === 'de_DE' && isset($urls['de'])) {
            return 'de';
        } elseif (isset($urls['en'])) {
            return 'en';
        } elseif (isset($urls['de'])) {
            return 'de';
        }

        return null;
    }

    /**
     * Migrate custom fields to database
     */
    private function migrate_custom_fields_to_db($post_id, $urls) {
        $this->save_hreflang_data($post_id, $urls);

        // Optionally remove old custom fields
        // delete_post_meta($post_id, 'chrmrtns_puzzlesync_hreflang_en');
        // delete_post_meta($post_id, 'chrmrtns_puzzlesync_hreflang_de');
    }

    /**
     * Get supported languages from settings
     */
    private function get_supported_languages() {
        return get_option('chrmrtns_puzzlesync_languages', array(
            array('code' => 'en', 'name' => 'English', 'flag' => '🇺🇸'),
            array('code' => 'de', 'name' => 'Deutsch', 'flag' => '🇩🇪')
        ));
    }

    /**
     * Detect post language from categories and tags dynamically
     * Returns language code (e.g., 'en', 'de', 'fr') or null if not detected
     */
    private function detect_post_language($post) {
        $supported_languages = $this->get_supported_languages();

        foreach ($supported_languages as $lang) {
            // Check for various category/tag variations
            $lang_name_lower = function_exists('mb_strtolower') ? mb_strtolower($lang['name'], 'UTF-8') : strtolower($lang['name']);
            $lang_name_cap = function_exists('mb_convert_case') ? mb_convert_case($lang['name'], MB_CASE_TITLE, 'UTF-8') : ucfirst($lang_name_lower);

            $variations = array(
                $lang_name_lower,
                $lang_name_cap,
                $lang['name'],
                $lang['code'],
                strtolower($lang['code']),
                strtoupper($lang['code'])
            );

            foreach ($variations as $var) {
                // Check categories
                if (has_category($var, $post)) {
                    return $lang['code'];
                }
                // Check tags (with and without -version suffix)
                if (has_tag($var, $post) || has_tag($var . '-version', $post) || has_tag($var . '_version', $post)) {
                    return $lang['code'];
                }
            }
        }

        return null;
    }

    /**
     * Convert language code to locale format
     * e.g., 'en' -> 'en-US', 'de' -> 'de-DE', 'fr' -> 'fr-FR'
     */
    private function language_code_to_locale($code) {
        // Common mappings
        $locale_map = array(
            'en' => 'en-US',
            'de' => 'de-DE',
            'fr' => 'fr-FR',
            'es' => 'es-ES',
            'it' => 'it-IT',
            'pt' => 'pt-PT',
            'nl' => 'nl-NL',
            'pl' => 'pl-PL',
            'ru' => 'ru-RU',
            'ja' => 'ja-JP',
            'zh' => 'zh-CN',
            'ko' => 'ko-KR',
            'ar' => 'ar-SA',
            'tr' => 'tr-TR',
            'sv' => 'sv-SE',
            'da' => 'da-DK',
            'no' => 'no-NO',
            'fi' => 'fi-FI',
            'cs' => 'cs-CZ',
            'hu' => 'hu-HU',
            'ro' => 'ro-RO',
            'el' => 'el-GR',
            'he' => 'he-IL',
            'th' => 'th-TH',
            'vi' => 'vi-VN',
            'id' => 'id-ID',
            'uk' => 'uk-UA',
            'hr' => 'hr-HR',
            'sk' => 'sk-SK',
            'bg' => 'bg-BG',
        );

        if (isset($locale_map[$code])) {
            return $locale_map[$code];
        }

        // Fallback: uppercase the code
        return $code . '-' . strtoupper($code);
    }
}