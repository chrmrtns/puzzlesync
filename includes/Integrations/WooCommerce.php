<?php
/**
 * WooCommerce Integration
 *
 * Handles translation of WooCommerce products
 *
 * @package PuzzleSync
 * @since 1.1.0
 */

namespace Chrmrtns\PuzzleSync\Integrations;

use Chrmrtns\PuzzleSync\Translations\FieldTranslator;

if (!defined('ABSPATH')) {
    exit;
}

class WooCommerce extends FieldTranslator {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->field_type = 'woocommerce';
    }

    /**
     * Initialize WooCommerce hooks
     */
    public function init() {
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Filter product data on frontend
        add_filter('the_title', array($this, 'filter_product_title'), 10, 2);
        add_filter('the_content', array($this, 'filter_product_content'), 10);
        add_filter('get_the_excerpt', array($this, 'filter_product_excerpt'), 10, 2);
        add_filter('woocommerce_short_description', array($this, 'filter_short_description'), 10);

        // Sync inventory across translations
        add_action('woocommerce_product_set_stock', array($this, 'sync_inventory'), 10, 1);
        add_action('woocommerce_variation_set_stock', array($this, 'sync_inventory'), 10, 1);
    }

    /**
     * Get translatable fields for a product
     *
     * @param int $post_id Product ID
     * @return array
     */
    public function get_translatable_fields($post_id) {
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'product') {
            return array();
        }

        $product = wc_get_product($post_id);

        if (!$product) {
            return array();
        }

        $fields = array(
            array(
                'name'        => 'product_name',
                'label'       => __('Product Name', 'puzzlesync'),
                'type'        => 'text',
                'field_type'  => $this->field_type,
                'is_pro'      => false,
                'description' => __('Product title', 'puzzlesync'),
            ),
            array(
                'name'        => 'product_description',
                'label'       => __('Product Description', 'puzzlesync'),
                'type'        => 'textarea',
                'field_type'  => $this->field_type,
                'is_pro'      => false,
                'description' => __('Full product description', 'puzzlesync'),
            ),
            array(
                'name'        => 'product_short_description',
                'label'       => __('Short Description', 'puzzlesync'),
                'type'        => 'textarea',
                'field_type'  => $this->field_type,
                'is_pro'      => false,
                'description' => __('Short product description', 'puzzlesync'),
            ),
        );

        // Add product attributes (Pro feature)
        $attributes = $product->get_attributes();
        if (!empty($attributes)) {
            foreach ($attributes as $attribute) {
                if (is_object($attribute)) {
                    $attr_name = $attribute->get_name();
                    $fields[] = array(
                        'name'        => 'attribute_' . $attr_name,
                        'label'       => sprintf(__('Attribute: %s', 'puzzlesync'), $attr_name),
                        'type'        => 'text',
                        'field_type'  => $this->field_type,
                        'is_pro'      => true,
                        'description' => sprintf(__('Product attribute: %s', 'puzzlesync'), $attr_name),
                    );
                }
            }
        }

        return $fields;
    }

    /**
     * Get field value
     *
     * @param int    $post_id    Product ID
     * @param string $field_name Field name
     * @return mixed
     */
    public function get_field_value($post_id, $field_name) {
        $product = wc_get_product($post_id);

        if (!$product) {
            return '';
        }

        switch ($field_name) {
            case 'product_name':
                return $product->get_name();

            case 'product_description':
                return $product->get_description();

            case 'product_short_description':
                return $product->get_short_description();

            default:
                // Handle attributes
                if (strpos($field_name, 'attribute_') === 0) {
                    $attr_name = str_replace('attribute_', '', $field_name);
                    return $product->get_attribute($attr_name);
                }
                return '';
        }
    }

    /**
     * Set field value
     *
     * @param int    $post_id    Product ID
     * @param string $field_name Field name
     * @param mixed  $value      Field value
     * @return bool
     */
    public function set_field_value($post_id, $field_name, $value) {
        $product = wc_get_product($post_id);

        if (!$product) {
            return false;
        }

        switch ($field_name) {
            case 'product_name':
                $product->set_name($value);
                break;

            case 'product_description':
                $product->set_description($value);
                break;

            case 'product_short_description':
                $product->set_short_description($value);
                break;

            default:
                // Handle attributes
                if (strpos($field_name, 'attribute_') === 0) {
                    // Attribute setting is more complex, handle in Pro version
                    return false;
                }
                return false;
        }

        $product->save();
        return true;
    }

    /**
     * Check if field type is translatable
     *
     * @param string $field_type Field type
     * @return bool
     */
    public function is_translatable_field_type($field_type) {
        $translatable = array('text', 'textarea', 'wysiwyg', 'select');
        return in_array($field_type, $translatable);
    }

    /**
     * Filter product title on frontend
     *
     * @param string $title   Post title
     * @param int    $post_id Post ID
     * @return string
     */
    public function filter_product_title($title, $post_id = 0) {
        if (!$post_id || get_post_type($post_id) !== 'product') {
            return $title;
        }

        $language = $this->get_current_language();
        $translated = $this->get_translated_value($post_id, 'product_name', $language);

        return $translated ?: $title;
    }

    /**
     * Filter product content on frontend
     *
     * @param string $content Post content
     * @return string
     */
    public function filter_product_content($content) {
        if (!is_singular('product')) {
            return $content;
        }

        $post_id = get_the_ID();
        $language = $this->get_current_language();
        $translated = $this->get_translated_value($post_id, 'product_description', $language);

        return $translated ?: $content;
    }

    /**
     * Filter product excerpt on frontend
     *
     * @param string $excerpt Post excerpt
     * @param object $post    Post object
     * @return string
     */
    public function filter_product_excerpt($excerpt, $post = null) {
        if (!$post || $post->post_type !== 'product') {
            return $excerpt;
        }

        $language = $this->get_current_language();
        $translated = $this->get_translated_value($post->ID, 'product_short_description', $language);

        return $translated ?: $excerpt;
    }

    /**
     * Filter short description on frontend
     *
     * @param string $description Short description
     * @return string
     */
    public function filter_short_description($description) {
        if (!is_singular('product')) {
            return $description;
        }

        $post_id = get_the_ID();
        $language = $this->get_current_language();
        $translated = $this->get_translated_value($post_id, 'product_short_description', $language);

        return $translated ?: $description;
    }

    /**
     * Sync inventory across translations
     *
     * @param \WC_Product $product Product object
     */
    public function sync_inventory($product) {
        if (!$product) {
            return;
        }

        $product_id = $product->get_id();
        $stock_quantity = $product->get_stock_quantity();
        $stock_status = $product->get_stock_status();

        // Find all translations of this product
        $translations = $this->find_product_translations($product_id);

        foreach ($translations as $translation_id) {
            if ($translation_id == $product_id) {
                continue;
            }

            $translated_product = wc_get_product($translation_id);
            if ($translated_product) {
                $translated_product->set_stock_quantity($stock_quantity);
                $translated_product->set_stock_status($stock_status);
                $translated_product->save();
            }
        }
    }

    /**
     * Find all translations of a product
     *
     * @param int $product_id Product ID
     * @return array Array of product IDs
     */
    private function find_product_translations($product_id) {
        global $wpdb;
        $hreflang_table = $wpdb->prefix . CHRMRTNS_PUZZLESYNC_TABLE_NAME;

        // Get translation group
        $translation_group = $wpdb->get_var($wpdb->prepare(
            "SELECT translation_group FROM {$hreflang_table} WHERE post_id = %d LIMIT 1",
            $product_id
        ));

        if (!$translation_group) {
            return array($product_id);
        }

        // Get all products in the same translation group
        $product_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$hreflang_table} WHERE translation_group = %s",
            $translation_group
        ));

        return $product_ids ?: array($product_id);
    }

    /**
     * Get current language from context
     *
     * @return string Language code
     */
    private function get_current_language() {
        // Try to detect from current post
        if (is_singular()) {
            $post_id = get_the_ID();
            $post = get_post($post_id);

            if ($post) {
                $languages = $this->get_supported_languages();

                foreach ($languages as $lang) {
                    $variations = array(
                        strtolower($lang['name']),
                        ucfirst(strtolower($lang['name'])),
                        $lang['code'],
                        strtolower($lang['code']),
                        strtoupper($lang['code']),
                    );

                    foreach ($variations as $var) {
                        if (has_category($var, $post) || has_tag($var, $post) || has_tag($var . '-version', $post)) {
                            return $lang['code'];
                        }
                    }
                }
            }
        }

        return get_option('chrmrtns_puzzlesync_default_language', 'en');
    }

    /**
     * Translate product categories
     *
     * @param int   $product_id Product ID
     * @param array $categories Category IDs or names
     * @param string $language  Language code
     * @return bool
     */
    public function translate_categories($product_id, $categories, $language) {
        // This will be implemented in future updates
        // For now, categories can be manually assigned
        return true;
    }

    /**
     * Translate product tags
     *
     * @param int    $product_id Product ID
     * @param array  $tags       Tag IDs or names
     * @param string $language   Language code
     * @return bool
     */
    public function translate_tags($product_id, $tags, $language) {
        // This will be implemented in future updates
        // For now, tags can be manually assigned
        return true;
    }
}
