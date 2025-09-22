<?php
/**
 * Validator class for PressML plugin
 * Validates hreflang implementation and checks for common issues
 *
 * @package PressML
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chrmrtns_Pml_Validator {

    private $db;
    private $issues = array();
    private $warnings = array();

    public function __construct() {
        $this->db = new Chrmrtns_Pml_Database();
    }

    /**
     * Run full validation
     */
    public function validate_all() {
        $this->issues = array();
        $this->warnings = array();

        // Run all validation checks
        $this->validate_urls();
        $this->validate_bidirectional_links();
        $this->validate_translation_groups();
        $this->validate_x_default();
        $this->validate_language_consistency();
        $this->check_orphaned_entries();
        $this->check_duplicate_entries();

        return array(
            'issues' => $this->issues,
            'warnings' => $this->warnings,
            'summary' => $this->get_summary()
        );
    }

    /**
     * Validate URLs format and accessibility
     */
    private function validate_urls() {
        $all_entries = $this->db->get_all_posts_with_hreflang();

        foreach ($all_entries as $post_id) {
            $hreflang_data = $this->db->get_hreflang_by_post($post_id);
            $post = get_post($post_id);

            if (!$post) {
                $this->issues[] = sprintf(
                    // translators: %d: Post ID
                    __('Post ID %d no longer exists but has hreflang entries', 'pressml'),
                    $post_id
                );
                continue;
            }

            foreach ($hreflang_data as $entry) {
                // Validate URL format
                if (!filter_var($entry->url, FILTER_VALIDATE_URL)) {
                    $this->issues[] = sprintf(
                        // translators: %1$s: Post title, %2$s: Language code, %3$s: URL
                        __('Invalid URL format for %1$s (language: %2$s): %3$s', 'pressml'),
                        $post->post_title,
                        $entry->language_code,
                        $entry->url
                    );
                }

                // Check if URL is accessible (optional, can be slow)
                if ($this->should_check_url_accessibility()) {
                    $response = wp_remote_head($entry->url, array('timeout' => 5));
                    if (is_wp_error($response)) {
                        $this->warnings[] = sprintf(
                            // translators: %1$s: Post title, %2$s: Language code, %3$s: URL
                            __('URL not accessible for %1$s (language: %2$s): %3$s', 'pressml'),
                            $post->post_title,
                            $entry->language_code,
                            $entry->url
                        );
                    } elseif (wp_remote_retrieve_response_code($response) >= 400) {
                        $this->issues[] = sprintf(
                            // translators: %1$d: HTTP response code, %2$s: Post title, %3$s: Language code, %4$s: URL
                            __('URL returns error %1$d for %2$s (language: %3$s): %4$s', 'pressml'),
                            wp_remote_retrieve_response_code($response),
                            $post->post_title,
                            $entry->language_code,
                            $entry->url
                        );
                    }
                }
            }
        }
    }

    /**
     * Validate bidirectional links
     */
    private function validate_bidirectional_links() {
        $translation_groups = $this->db->get_translation_groups();

        foreach ($translation_groups as $group) {
            $group_entries = $this->db->get_hreflang_by_translation_group($group);
            $posts_by_lang = array();

            foreach ($group_entries as $entry) {
                if (!isset($posts_by_lang[$entry->language_code])) {
                    $posts_by_lang[$entry->language_code] = array();
                }
                $posts_by_lang[$entry->language_code][] = $entry->post_id;
            }

            // Check if all languages have the same number of posts
            $post_counts = array_map('count', $posts_by_lang);
            if (count(array_unique($post_counts)) > 1) {
                $this->issues[] = sprintf(
                    // translators: %s: Translation group name
                    __('Translation group "%s" has inconsistent language coverage', 'pressml'),
                    $group
                );
            }

            // Check if each post in the group references all other languages
            foreach ($group_entries as $entry) {
                $post_hreflang = $this->db->get_hreflang_by_post($entry->post_id);
                $languages_referenced = array_column($post_hreflang, 'language_code');

                foreach (array_keys($posts_by_lang) as $lang) {
                    if ($lang !== $entry->language_code && !in_array($lang, $languages_referenced)) {
                        $post = get_post($entry->post_id);
                        $this->warnings[] = sprintf(
                            // translators: %1$s: Post title, %2$d: Post ID, %3$s: Language code
                            __('Post "%1$s" (ID: %2$d) is missing reference to %3$s language', 'pressml'),
                            $post ? $post->post_title : 'Unknown',
                            $entry->post_id,
                            $lang
                        );
                    }
                }
            }
        }
    }

    /**
     * Validate translation groups
     */
    private function validate_translation_groups() {
        $translation_groups = $this->db->get_translation_groups();

        foreach ($translation_groups as $group) {
            $group_entries = $this->db->get_hreflang_by_translation_group($group);
            $unique_posts = array_unique(array_column($group_entries, 'post_id'));

            if (count($unique_posts) < 2) {
                $this->warnings[] = sprintf(
                    // translators: %1$s: Translation group name, %2$d: Number of posts
                    __('Translation group "%1$s" has only %2$d post(s)', 'pressml'),
                    $group,
                    count($unique_posts)
                );
            }

            // Check for multiple entries of same language in group
            $lang_post_map = array();
            foreach ($group_entries as $entry) {
                $key = $entry->language_code;
                if (!isset($lang_post_map[$key])) {
                    $lang_post_map[$key] = array();
                }
                $lang_post_map[$key][] = $entry->post_id;
            }

            foreach ($lang_post_map as $lang => $post_ids) {
                if (count(array_unique($post_ids)) > 1) {
                    $this->issues[] = sprintf(
                        // translators: %1$s: Translation group name, %2$s: Language code
                        __('Translation group "%1$s" has multiple posts for language %2$s', 'pressml'),
                        $group,
                        $lang
                    );
                }
            }
        }
    }

    /**
     * Validate x-default settings
     */
    private function validate_x_default() {
        $all_entries = $this->db->get_all_posts_with_hreflang();

        foreach ($all_entries as $post_id) {
            $hreflang_data = $this->db->get_hreflang_by_post($post_id);
            $x_default_count = 0;

            foreach ($hreflang_data as $entry) {
                if ($entry->is_x_default) {
                    $x_default_count++;
                }
            }

            if ($x_default_count > 1) {
                $post = get_post($post_id);
                $this->issues[] = sprintf(
                    // translators: %1$s: Post title, %2$d: Post ID
                    __('Post "%1$s" (ID: %2$d) has multiple x-default entries', 'pressml'),
                    $post ? $post->post_title : 'Unknown',
                    $post_id
                );
            }

            // Recommend x-default if multiple languages exist but none is set
            if (count($hreflang_data) > 1 && $x_default_count === 0) {
                $post = get_post($post_id);
                $this->warnings[] = sprintf(
                    // translators: %1$s: Post title, %2$d: Post ID
                    __('Post "%1$s" (ID: %2$d) has multiple languages but no x-default set', 'pressml'),
                    $post ? $post->post_title : 'Unknown',
                    $post_id
                );
            }
        }
    }

    /**
     * Validate language consistency
     */
    private function validate_language_consistency() {
        $all_entries = $this->db->get_all_posts_with_hreflang();

        foreach ($all_entries as $post_id) {
            $post = get_post($post_id);
            if (!$post) continue;

            $hreflang_data = $this->db->get_hreflang_by_post($post_id);

            // Check if post language matches its hreflang self-reference
            $current_url = get_permalink($post_id);
            $detected_language = null;

            if (has_category('english', $post) || has_tag('english-version', $post)) {
                $detected_language = 'en';
            } elseif (has_category('german', $post) || has_tag('german-version', $post)) {
                $detected_language = 'de';
            }

            if ($detected_language) {
                $self_reference_found = false;
                foreach ($hreflang_data as $entry) {
                    if ($entry->url === $current_url && $entry->language_code !== $detected_language) {
                        $this->warnings[] = sprintf(
                            // translators: %1$s: Post title, %2$s: Detected language, %3$s: Self-referenced language
                            __('Post "%1$s" detected as %2$s but self-references as %3$s', 'pressml'),
                            $post->post_title,
                            $detected_language,
                            $entry->language_code
                        );
                    }
                    if ($entry->url === $current_url) {
                        $self_reference_found = true;
                    }
                }

                if (!$self_reference_found && count($hreflang_data) > 0) {
                    $this->warnings[] = sprintf(
                        // translators: %s: Post title
                        __('Post "%s" has hreflang tags but no self-reference', 'pressml'),
                        $post->post_title
                    );
                }
            }
        }
    }

    /**
     * Check for orphaned entries
     */
    private function check_orphaned_entries() {
        $all_entries = $this->db->get_all_posts_with_hreflang();
        $orphaned_count = 0;

        foreach ($all_entries as $post_id) {
            if (!get_post($post_id)) {
                $orphaned_count++;
            }
        }

        if ($orphaned_count > 0) {
            $this->issues[] = sprintf(
                // translators: %d: Number of orphaned entries
                __('%d orphaned hreflang entries found (posts no longer exist)', 'pressml'),
                $orphaned_count
            );
        }
    }

    /**
     * Check for duplicate entries
     */
    private function check_duplicate_entries() {
        global $wpdb;
        $table_name = $wpdb->prefix . CHRMRTNS_PML_TABLE_NAME;

        // This should not happen due to unique constraint, but check anyway
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
        $duplicates = $wpdb->get_results(
            "SELECT post_id, language_code, COUNT(*) as count
             FROM {$table_name}
             GROUP BY post_id, language_code
             HAVING count > 1"
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

        foreach ($duplicates as $duplicate) {
            $post = get_post($duplicate->post_id);
            $this->issues[] = sprintf(
                // translators: %1$s: Post title, %2$d: Post ID, %3$s: Language code
                __('Duplicate entries found for post "%1$s" (ID: %2$d), language %3$s', 'pressml'),
                $post ? $post->post_title : 'Unknown',
                $duplicate->post_id,
                $duplicate->language_code
            );
        }
    }

    /**
     * Check if URL accessibility should be checked
     */
    private function should_check_url_accessibility() {
        return get_option('chrmrtns_pml_enable_validation', true) &&
               get_option('chrmrtns_pml_check_url_accessibility', false);
    }

    /**
     * Get validation summary
     */
    private function get_summary() {
        $stats = $this->db->get_statistics();

        return array(
            'total_issues' => count($this->issues),
            'total_warnings' => count($this->warnings),
            'total_posts' => $stats['total_posts'],
            'total_entries' => $stats['total_entries'],
            'total_groups' => $stats['total_groups'],
            'status' => empty($this->issues) ? 'pass' : 'fail'
        );
    }

    /**
     * Fix common issues automatically
     */
    public function auto_fix_issues() {
        $fixes_applied = array();

        // Remove orphaned entries
        $orphaned_deleted = $this->db->cleanup_orphaned_entries();
        if ($orphaned_deleted > 0) {
            $fixes_applied[] = sprintf(
                // translators: %d: Number of orphaned entries removed
                __('Removed %d orphaned entries', 'pressml'),
                $orphaned_deleted
            );
        }

        // Add self-references where missing
        $self_refs_added = $this->add_missing_self_references();
        if ($self_refs_added > 0) {
            $fixes_applied[] = sprintf(
                // translators: %d: Number of self-references added
                __('Added %d missing self-references', 'pressml'),
                $self_refs_added
            );
        }

        // Set x-default where missing
        $x_defaults_set = $this->set_missing_x_defaults();
        if ($x_defaults_set > 0) {
            $fixes_applied[] = sprintf(
                // translators: %d: Number of x-default values set
                __('Set %d missing x-default values', 'pressml'),
                $x_defaults_set
            );
        }

        return $fixes_applied;
    }

    /**
     * Add missing self-references
     */
    private function add_missing_self_references() {
        $added = 0;
        $all_entries = $this->db->get_all_posts_with_hreflang();

        foreach ($all_entries as $post_id) {
            $post = get_post($post_id);
            if (!$post) continue;

            $current_url = get_permalink($post_id);
            $hreflang_data = $this->db->get_hreflang_by_post($post_id);

            $has_self_reference = false;
            foreach ($hreflang_data as $entry) {
                if ($entry->url === $current_url) {
                    $has_self_reference = true;
                    break;
                }
            }

            if (!$has_self_reference && count($hreflang_data) > 0) {
                // Detect language
                $language = null;
                if (has_category('english', $post) || has_tag('english-version', $post)) {
                    $language = 'en';
                } elseif (has_category('german', $post) || has_tag('german-version', $post)) {
                    $language = 'de';
                }

                if ($language) {
                    $translation_group = get_post_meta($post_id, 'chrmrtns_pml_translation_group', true);
                    $this->db->insert_or_update_hreflang($post_id, $language, $current_url, $translation_group);
                    $added++;
                }
            }
        }

        return $added;
    }

    /**
     * Set missing x-default values
     */
    private function set_missing_x_defaults() {
        $set = 0;
        $all_entries = $this->db->get_all_posts_with_hreflang();

        foreach ($all_entries as $post_id) {
            $hreflang_data = $this->db->get_hreflang_by_post($post_id);

            if (count($hreflang_data) > 1) {
                $has_x_default = false;
                foreach ($hreflang_data as $entry) {
                    if ($entry->is_x_default) {
                        $has_x_default = true;
                        break;
                    }
                }

                if (!$has_x_default) {
                    // Set English as default, or first available language
                    $default_lang = null;
                    foreach ($hreflang_data as $entry) {
                        if ($entry->language_code === 'en') {
                            $default_lang = 'en';
                            break;
                        }
                    }

                    if (!$default_lang && count($hreflang_data) > 0) {
                        $default_lang = $hreflang_data[0]->language_code;
                    }

                    if ($default_lang) {
                        $this->db->set_x_default($post_id, $default_lang);
                        $set++;
                    }
                }
            }
        }

        return $set;
    }
}