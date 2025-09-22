<?php
/**
 * Core functionality class for PressML plugin
 *
 * @package PressML
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chrmrtns_Pml_Core {

    private $db;

    public function __construct() {
        $this->db = new Chrmrtns_Pml_Database();
    }

    public function init() {
        // Hook into WordPress head to output hreflang tags
        add_action('wp_head', array($this, 'output_hreflang_tags'));

        // Modify HTML lang attribute
        add_filter('language_attributes', array($this, 'modify_language_attributes'));

        // Add JSON-LD structured data if enabled
        if (get_option('chrmrtns_pml_enable_json_ld', true)) {
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

        foreach ($hreflang_data as $item) {
            $hreflang = ($item->is_x_default) ? 'x-default' : $item->language_code;
            echo '<link rel="alternate" hreflang="' . esc_attr($hreflang) . '" href="' . esc_url($item->url) . '" />' . "\n";
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
        $hreflang_en = get_post_meta($post_id, 'chrmrtns_pml_hreflang_en', true);
        $hreflang_de = get_post_meta($post_id, 'chrmrtns_pml_hreflang_de', true);

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

        if (has_category('english', $post) || has_tag('english-version', $post)) {
            $german_post = $this->find_translation_by_category($post, 'german');
            if ($german_post) {
                $hreflang_urls['de'] = get_permalink($german_post->ID);
                $hreflang_urls['en'] = get_permalink($post->ID);
            }
        } elseif (has_category('german', $post) || has_tag('german-version', $post)) {
            $english_post = $this->find_translation_by_category($post, 'english');
            if ($english_post) {
                $hreflang_urls['en'] = get_permalink($english_post->ID);
                $hreflang_urls['de'] = get_permalink($post->ID);
            }
        }

        return $hreflang_urls;
    }

    /**
     * Find translation by category
     */
    private function find_translation_by_category($post, $target_language) {
        $translation_group = get_post_meta($post->ID, 'chrmrtns_pml_translation_group', true);

        $query_args = array(
            'post_type' => $post->post_type,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'exclude' => array($post->ID) // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
        );

        if ($translation_group) {
            $query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                array(
                    'key' => 'chrmrtns_pml_translation_group',
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

        if (has_category('english', $post) || has_tag('english-version', $post)) {
            return 'lang="en-US"';
        } elseif (has_category('german', $post) || has_tag('german-version', $post)) {
            return 'lang="de-DE"';
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
            if (!$item->is_x_default && $item->url !== get_permalink($post->ID)) {
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
     * Determine post language
     */
    private function determine_post_language($post) {
        if (has_category('english', $post) || has_tag('english-version', $post)) {
            return 'en-US';
        } elseif (has_category('german', $post) || has_tag('german-version', $post)) {
            return 'de-DE';
        }

        $locale = get_locale();
        return ($locale === 'de_DE') ? 'de-DE' : 'en-US';
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
        $default_meta = get_post_meta($post_id, 'chrmrtns_pml_hreflang_default', true);

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
        // delete_post_meta($post_id, 'chrmrtns_pml_hreflang_en');
        // delete_post_meta($post_id, 'chrmrtns_pml_hreflang_de');
    }
}