<?php
/**
 * Database operations class for PuzzleSync plugin
 *
 * @package PuzzleSync
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chrmrtns_Pml_Database {

    private $table_name;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . CHRMRTNS_PML_TABLE_NAME;
    }

    /**
     * Get all hreflang entries for a post
     */
    public function get_hreflang_by_post($post_id) {
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name}
             WHERE post_id = %d
             ORDER BY priority DESC, language_code ASC",
            $post_id
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        return $this->wpdb->get_results($query); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Get hreflang entries by translation group
     */
    public function get_hreflang_by_translation_group($translation_group) {
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name}
             WHERE translation_group = %s
             ORDER BY post_id ASC, language_code ASC",
            $translation_group
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        return $this->wpdb->get_results($query); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Insert or update hreflang entry
     */
    public function insert_or_update_hreflang($post_id, $language_code, $url, $translation_group = null, $priority = 0) {
        // Check if entry exists
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->table_name}
             WHERE post_id = %d AND language_code = %s",
            $post_id, $language_code
        ));
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        $data = array(
            'post_id' => $post_id,
            'language_code' => $language_code,
            'url' => $url,
            'translation_group' => $translation_group,
            'priority' => $priority
        );

        if ($existing) {
            // Update existing entry
            $where = array(
                'post_id' => $post_id,
                'language_code' => $language_code
            );

            return $this->wpdb->update($this->table_name, $data, $where);
        } else {
            // Insert new entry
            return $this->wpdb->insert($this->table_name, $data);
        }
    }

    /**
     * Set x-default for a post
     */
    public function set_x_default($post_id, $language_code) {
        // First, unset any existing x-default for this post
        $this->wpdb->update(
            $this->table_name,
            array('is_x_default' => 0),
            array('post_id' => $post_id),
            array('%d'),
            array('%d')
        );

        // Set the new x-default
        return $this->wpdb->update(
            $this->table_name,
            array('is_x_default' => 1),
            array(
                'post_id' => $post_id,
                'language_code' => $language_code
            ),
            array('%d'),
            array('%d', '%s')
        );
    }

    /**
     * Delete hreflang entries for a post
     */
    public function delete_hreflang_by_post($post_id) {
        return $this->wpdb->delete(
            $this->table_name,
            array('post_id' => $post_id),
            array('%d')
        );
    }

    /**
     * Delete specific hreflang entry
     */
    public function delete_hreflang_entry($post_id, $language_code) {
        return $this->wpdb->delete(
            $this->table_name,
            array(
                'post_id' => $post_id,
                'language_code' => $language_code
            ),
            array('%d', '%s')
        );
    }

    /**
     * Get all posts with hreflang configuration
     */
    public function get_all_posts_with_hreflang() {
        $query = "SELECT DISTINCT h.post_id FROM {$this->table_name} h
                  INNER JOIN {$this->wpdb->posts} p ON h.post_id = p.ID
                  WHERE p.post_type IN ('post', 'page') AND p.post_status NOT IN ('revision', 'auto-draft', 'inherit')
                  ORDER BY h.post_id DESC"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $this->wpdb->get_col($query); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Get translation groups
     */
    public function get_translation_groups() {
        $query = "SELECT DISTINCT translation_group
                  FROM {$this->table_name} -- phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                  WHERE translation_group IS NOT NULL
                  ORDER BY translation_group ASC";
        return $this->wpdb->get_col($query); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Validate hreflang URLs
     */
    public function validate_hreflang_urls() {
        $issues = array();

        $all_entries = $this->wpdb->get_results("SELECT * FROM {$this->table_name}"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        foreach ($all_entries as $entry) {
            // Validate URL format
            if (!filter_var($entry->url, FILTER_VALIDATE_URL)) {
                $issues[] = sprintf(
                    'Invalid URL for post ID %d, language %s: %s',
                    $entry->post_id,
                    $entry->language_code,
                    $entry->url
                );
            }

            // Check if post still exists
            if (!get_post($entry->post_id)) {
                $issues[] = sprintf(
                    'Post ID %d no longer exists but has hreflang entries',
                    $entry->post_id
                );
            }
        }

        // Check for bidirectional links
        $translation_groups = $this->get_translation_groups();
        foreach ($translation_groups as $group) {
            $group_entries = $this->get_hreflang_by_translation_group($group);
            $post_ids = array_unique(array_column($group_entries, 'post_id'));

            if (count($post_ids) < 2) {
                $issues[] = sprintf(
                    'Translation group "%s" has only %d post(s)',
                    $group,
                    count($post_ids)
                );
            }
        }

        return $issues;
    }

    /**
     * Get statistics
     */
    public function get_statistics() {
        $stats = array();

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $stats['total_entries'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} h
             INNER JOIN {$this->wpdb->posts} p ON h.post_id = p.ID
             WHERE p.post_type IN ('post', 'page') AND p.post_status NOT IN ('revision', 'auto-draft', 'inherit')"
        );

        $stats['total_posts'] = $this->wpdb->get_var(
            "SELECT COUNT(DISTINCT h.post_id) FROM {$this->table_name} h
             INNER JOIN {$this->wpdb->posts} p ON h.post_id = p.ID
             WHERE p.post_type IN ('post', 'page') AND p.post_status NOT IN ('revision', 'auto-draft', 'inherit')"
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $stats['total_groups'] = $this->wpdb->get_var("SELECT COUNT(DISTINCT translation_group) FROM {$this->table_name} WHERE translation_group IS NOT NULL"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
        $language_counts = $this->wpdb->get_results(
            "SELECT h.language_code, COUNT(*) as count
             FROM {$this->table_name} h
             INNER JOIN {$this->wpdb->posts} p ON h.post_id = p.ID
             WHERE p.post_type IN ('post', 'page') AND p.post_status NOT IN ('revision', 'auto-draft', 'inherit')
             GROUP BY h.language_code
             ORDER BY count DESC"
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

        $stats['languages'] = array();
        foreach ($language_counts as $lang) {
            $stats['languages'][$lang->language_code] = $lang->count;
        }

        return $stats;
    }

    /**
     * Clean up orphaned entries
     */
    public function cleanup_orphaned_entries() {
        $deleted = 0;

        // Get all post IDs from database
        $post_ids = $this->wpdb->get_col("SELECT DISTINCT post_id FROM {$this->table_name}"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        foreach ($post_ids as $post_id) {
            if (!get_post($post_id)) {
                $deleted += $this->delete_hreflang_by_post($post_id);
            }
        }

        return $deleted;
    }
}