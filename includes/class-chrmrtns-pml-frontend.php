<?php
/**
 * Frontend functionality class for PuzzleSync plugin
 *
 * @package PuzzleSync
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chrmrtns_Pml_Frontend {

    private $core;

    public function __construct() {
        // Core functionality is handled by Chrmrtns_Pml_Core
        // This class can be extended for additional frontend features
    }

    public function init() {
        // Add any frontend-specific functionality here
        // For example: language switcher widget, frontend language selector, etc.

        // Add shortcodes
        add_shortcode('pressml_language_switcher', array($this, 'language_switcher_shortcode'));
        add_shortcode('pressml_current_language', array($this, 'current_language_shortcode'));
        add_shortcode('pressml_language_flags', array($this, 'language_flags_shortcode'));

        // Add frontend styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));

        // Add menu support
        add_filter('wp_nav_menu_objects', array($this, 'modify_menu_for_translations'), 10, 2);

        // Add automatic menu flags if enabled in settings
        if (get_option('chrmrtns_pml_auto_menu_flags', false)) {
            add_filter('wp_nav_menu_items', array($this, 'add_automatic_menu_flags'), 10, 2);
        }
    }

    /**
     * Get supported languages
     */
    private function get_supported_languages() {
        return get_option('chrmrtns_pml_languages', array(
            array('code' => 'en', 'name' => 'English', 'flag' => '🇺🇸'),
            array('code' => 'de', 'name' => 'Deutsch', 'flag' => '🇩🇪')
        ));
    }

    /**
     * Get language info by code
     */
    private function get_language_info($code) {
        $languages = $this->get_supported_languages();
        foreach ($languages as $lang) {
            if ($lang['code'] === $code) {
                return $lang;
            }
        }
        return null;
    }

    /**
     * Language switcher shortcode
     * Usage: [pressml_language_switcher]
     */
    public function language_switcher_shortcode($atts) {
        if (!is_singular()) {
            return '';
        }

        $atts = shortcode_atts(array(
            'show_flags' => 'true',
            'show_names' => 'true',
            'separator' => ' | ',
            'class' => 'puzzlesync-language-switcher',
            'debug' => 'false'
        ), $atts);

        global $post;
        $db = new Chrmrtns_Pml_Database();
        $hreflang_data = $db->get_hreflang_by_post($post->ID);

        if (empty($hreflang_data)) {
            if ($atts['debug'] === 'true') {
                return '<div style="color: red; border: 1px solid red; padding: 10px;">DEBUG (Language Switcher): No hreflang data found for post ID ' . $post->ID . '</div>';
            }
            return '';
        }

        $current_url = get_permalink($post->ID);
        $debug_output = '';

        if ($atts['debug'] === 'true') {
            $debug_output = '<div style="color: green; border: 1px solid green; padding: 10px; margin-bottom: 10px;">';
            $debug_output .= 'DEBUG (Language Switcher): Found ' . count($hreflang_data) . ' hreflang entries for post ID ' . $post->ID . '<br>';
            $debug_output .= 'Current URL: ' . $current_url . '<br>';
            foreach ($hreflang_data as $item) {
                $debug_output .= 'Language: ' . $item->language_code . ', URL: ' . $item->url . ', X-default: ' . ($item->is_x_default ? 'Yes' : 'No') . '<br>';
            }
            $debug_output .= '</div>';
        }

        $output = '<div class="' . esc_attr($atts['class']) . '">';
        $links = array();

        foreach ($hreflang_data as $item) {
            // Don't skip x-default entries - they should be shown too
            $is_current = ($item->url === $current_url);
            $link_class = $is_current ? 'current-lang' : '';

            $link_content = '';

            $lang_info = $this->get_language_info($item->language_code);
            if ($lang_info) {
                // Add flag if enabled
                if ($atts['show_flags'] === 'true') {
                    $link_content .= '<span class="chrmrtns-pml-flag">' . esc_html($lang_info['flag']) . '</span> ';
                }

                // Add language name if enabled
                if ($atts['show_names'] === 'true') {
                    $link_content .= esc_html($lang_info['name']);
                }
            }

            if ($is_current) {
                $links[] = '<span class="' . esc_attr($link_class) . '">' . $link_content . '</span>';
            } else {
                $links[] = '<a href="' . esc_url($item->url) . '" hreflang="' . esc_attr($item->language_code) . '" class="' . esc_attr($link_class) . '">' . $link_content . '</a>';
            }
        }

        $output .= implode($atts['separator'], $links);
        $output .= '</div>';

        return $debug_output . $output;
    }

    /**
     * Current language shortcode
     * Usage: [pressml_current_language]
     */
    public function current_language_shortcode($atts) {
        if (!is_singular()) {
            return '';
        }

        $atts = shortcode_atts(array(
            'format' => 'name', // 'name', 'code', or 'flag'
            'debug' => 'false'
        ), $atts);

        global $post;

        $debug_output = '';
        if ($atts['debug'] === 'true') {
            $debug_output = '<div style="color: orange; border: 1px solid orange; padding: 10px; margin-bottom: 10px;">';
            $debug_output .= 'DEBUG (Current Language): Post ID ' . $post->ID . '<br>';
            $debug_output .= 'Has English category: ' . ((has_category('english', $post) || has_category('English', $post)) ? 'Yes' : 'No') . '<br>';
            $debug_output .= 'Has English tag: ' . ((has_tag('english-version', $post) || has_tag('English-version', $post)) ? 'Yes' : 'No') . '<br>';
            $debug_output .= 'Has German category: ' . ((has_category('german', $post) || has_category('German', $post)) ? 'Yes' : 'No') . '<br>';
            $debug_output .= 'Has German tag: ' . ((has_tag('german-version', $post) || has_tag('German-version', $post)) ? 'Yes' : 'No') . '<br>';
            $debug_output .= 'Site locale: ' . get_locale() . '<br>';
            $debug_output .= '</div>';
        }

        // Detect current language
        $result = '';

        if (has_category('english', $post) || has_category('English', $post) || has_tag('english-version', $post) || has_tag('English-version', $post)) {
            switch ($atts['format']) {
                case 'code':
                    $result = 'en';
                    break;
                case 'flag':
                    $result = '🇺🇸';
                    break;
                default:
                    $result = 'English';
            }
        } elseif (has_category('german', $post) || has_category('German', $post) || has_tag('german-version', $post) || has_tag('German-version', $post)) {
            switch ($atts['format']) {
                case 'code':
                    $result = 'de';
                    break;
                case 'flag':
                    $result = '🇩🇪';
                    break;
                default:
                    $result = 'Deutsch';
            }
        } else {
            // Fallback to site locale
            $locale = get_locale();
            if ($locale === 'de_DE') {
                switch ($atts['format']) {
                    case 'code':
                        $result = 'de';
                        break;
                    case 'flag':
                        $result = '🇩🇪';
                        break;
                    default:
                        $result = 'Deutsch';
                }
            } else {
                switch ($atts['format']) {
                    case 'code':
                        $result = 'en';
                        break;
                    case 'flag':
                        $result = '🇺🇸';
                        break;
                    default:
                        $result = 'English';
                }
            }
        }

        return $debug_output . $result;
    }

    /**
     * Language flags shortcode - compact flag-only version
     * Usage: [pressml_language_flags size="medium" style="inline"]
     */
    public function language_flags_shortcode($atts) {
        if (!is_singular()) {
            return '';
        }

        $atts = shortcode_atts(array(
            'size' => 'medium', // 'small', 'medium', 'large'
            'style' => 'inline', // 'inline', 'block'
            'show_current' => 'true', // Show current language flag
            'class' => 'puzzlesync-language-flags',
            'debug' => 'false' // Show debug info
        ), $atts);

        global $post;
        $db = new Chrmrtns_Pml_Database();
        $hreflang_data = $db->get_hreflang_by_post($post->ID);

        if (empty($hreflang_data)) {
            if ($atts['debug'] === 'true') {
                return '<div style="color: red; border: 1px solid red; padding: 10px;">DEBUG: No hreflang data found for post ID ' . $post->ID . '</div>';
            }
            return '';
        }

        $current_url = get_permalink($post->ID);
        $debug_output = '';

        if ($atts['debug'] === 'true') {
            $debug_output = '<div style="color: blue; border: 1px solid blue; padding: 10px; margin-bottom: 10px;">';
            $debug_output .= 'DEBUG: Found ' . count($hreflang_data) . ' hreflang entries for post ID ' . $post->ID . '<br>';
            $debug_output .= 'Current URL: ' . $current_url . '<br>';
            foreach ($hreflang_data as $item) {
                $debug_output .= 'Language: ' . $item->language_code . ', URL: ' . $item->url . ', X-default: ' . ($item->is_x_default ? 'Yes' : 'No') . '<br>';
            }
            $debug_output .= '</div>';
        }
        $size_class = 'flag-' . esc_attr($atts['size']);
        $style_class = 'flags-' . esc_attr($atts['style']);
        $output = '<div class="' . esc_attr($atts['class']) . ' ' . $size_class . ' ' . $style_class . '">';

        $flags = array();

        foreach ($hreflang_data as $item) {
            // Don't skip x-default entries - they should be shown too
            $is_current = ($item->url === $current_url);

            // Skip current language if show_current is false
            if ($is_current && $atts['show_current'] === 'false') {
                continue;
            }

            $lang_info = $this->get_language_info($item->language_code);
            $flag_emoji = '';
            $title = '';

            if ($lang_info) {
                $flag_emoji = $lang_info['flag'];
                $title = $lang_info['name'];
            }

            $link_class = $is_current ? 'current-flag' : 'other-flag';

            if ($is_current) {
                $flags[] = '<span class="' . esc_attr($link_class) . '" title="' . esc_attr($title) . '">' . $flag_emoji . '</span>';
            } else {
                $flags[] = '<a href="' . esc_url($item->url) . '" hreflang="' . esc_attr($item->language_code) . '" class="' . esc_attr($link_class) . '" title="' . esc_attr($title) . '">' . $flag_emoji . '</a>';
            }
        }

        $output .= implode(' ', $flags);
        $output .= '</div>';

        return $debug_output . $output;
    }

    /**
     * Enqueue frontend styles
     */
    public function enqueue_frontend_styles() {
        // Only enqueue on pages that might have shortcodes
        if (is_singular()) {
            wp_add_inline_style('wp-block-library', '
                .puzzlesync-language-switcher {
                    display: inline-block;
                    margin: 10px 0;
                }
                .puzzlesync-language-switcher a {
                    text-decoration: none;
                    padding: 5px 8px;
                    margin: 0 2px;
                    border-radius: 3px;
                    background: #f1f1f1;
                    transition: all 0.3s ease;
                }
                .puzzlesync-language-switcher a:hover {
                    background: #0073aa;
                    color: white;
                }
                .puzzlesync-language-switcher .current-lang {
                    padding: 5px 8px;
                    margin: 0 2px;
                    background: #0073aa;
                    color: white;
                    border-radius: 3px;
                    font-weight: bold;
                }
                .puzzlesync-language-switcher .flag {
                    margin-right: 5px;
                }

                .puzzlesync-language-flags {
                    display: inline-block;
                    margin: 5px 0;
                }
                .puzzlesync-language-flags.flags-block {
                    display: block;
                    text-align: center;
                }
                .puzzlesync-language-flags a,
                .puzzlesync-language-flags span {
                    text-decoration: none;
                    margin: 0 3px;
                    transition: all 0.3s ease;
                    display: inline-block;
                }
                .puzzlesync-language-flags a:hover {
                    transform: scale(1.2);
                }
                .puzzlesync-language-flags.flag-small a,
                .puzzlesync-language-flags.flag-small span {
                    font-size: 1.2em;
                }
                .puzzlesync-language-flags.flag-medium a,
                .puzzlesync-language-flags.flag-medium span {
                    font-size: 1.5em;
                }
                .puzzlesync-language-flags.flag-large a,
                .puzzlesync-language-flags.flag-large span {
                    font-size: 2em;
                }
                .puzzlesync-language-flags .current-flag {
                    opacity: 0.7;
                }
                .puzzlesync-language-flags .other-flag {
                    opacity: 1;
                }

                /* Menu language flags styles */
                .chrmrtns-pml-menu-item-language-flags,
                .chrmrtns-pml-language-flags-item {
                    display: flex !important;
                    flex-direction: row !important;
                    gap: 8px !important;
                    align-items: center !important;
                }
                .chrmrtns-pml-menu-item-language-flags.chrmrtns-pml-flags-column,
                .chrmrtns-pml-language-flags-item.chrmrtns-pml-flags-column {
                    flex-direction: column !important;
                    gap: 4px !important;
                    align-items: flex-start !important;
                }
                .chrmrtns-pml-menu-item-language-flags a,
                .chrmrtns-pml-menu-item-language-flags span,
                .chrmrtns-pml-language-flags-item a,
                .chrmrtns-pml-menu-flags-wrapper a {
                    text-decoration: none !important;
                    transition: all 0.3s ease;
                    font-size: 1.2em !important;
                    line-height: 1 !important;
                }
                .chrmrtns-pml-menu-item-language-flags a:hover,
                .chrmrtns-pml-language-flags-item a:hover,
                .chrmrtns-pml-menu-flags-wrapper a:hover {
                    transform: scale(1.2);
                    opacity: 0.8;
                }
                .chrmrtns-pml-menu-flags-wrapper {
                    display: contents; /* This makes the wrapper transparent to flex layout */
                }
                .chrmrtns-pml-language-flags-item > a[data-brx-anchor],
                .chrmrtns-pml-language-flags-item > a[href="#"] {
                    display: none !important;
                }
                /* Override any theme styles that might break the layout */
                .chrmrtns-pml-language-flags-item a[hreflang] {
                    display: inline-block !important;
                    float: none !important;
                    clear: none !important;
                }
            ');
        }
    }

    /**
     * Add language flags automatically to the end of navigation menus
     * Only active when enabled in settings
     */
    public function add_automatic_menu_flags($items, $args) {
        if (!is_singular()) {
            return $items;
        }

        global $post;
        $db = new Chrmrtns_Pml_Database();
        $hreflang_data = $db->get_hreflang_by_post($post->ID);

        if (empty($hreflang_data) || count($hreflang_data) < 2) {
            return $items; // No translations available
        }

        $current_url = get_permalink($post->ID);
        $language_flags = '';

        foreach ($hreflang_data as $item) {
            $is_current = ($item->url === $current_url);

            $lang_info = $this->get_language_info($item->language_code);
            $flag_emoji = '';
            $title = '';

            if ($lang_info) {
                $flag_emoji = $lang_info['flag'];
                $title = $lang_info['name'];
            }

            // Only show non-current flags as clickable links
            if (!$is_current) {
                $language_flags .= '<a href="' . esc_url($item->url) . '" class="chrmrtns-pml-menu-flag-link" title="' . esc_attr($title) . '" hreflang="' . esc_attr($item->language_code) . '">' . $flag_emoji . '</a>';
            }
        }

        if (!empty($language_flags)) {
            $menu_flags_display = get_option('chrmrtns_pml_menu_flags_display', 'row');
            $column_class = ($menu_flags_display === 'column') ? ' chrmrtns-pml-flags-column' : '';
            $menu_item = '<li class="menu-item chrmrtns-pml-menu-item-language-flags' . $column_class . '">' . $language_flags . '</li>';
            $items .= $menu_item;
        }

        return $items;
    }

    /**
     * Add language flags as a custom menu item type
     * This allows users to add it through Appearance > Menus
     * Usage: Add Custom Link with URL "#puzzlesync-language-flags"
     */
    public function modify_menu_for_translations($items, $args) {
        // Check if menu contains a placeholder for language flags
        foreach ($items as $key => $item) {
            if (isset($item->url) && $item->url === '#puzzlesync-language-flags') {
                // Replace with actual language flags
                if (is_singular()) {
                    global $post;
                    $db = new Chrmrtns_Pml_Database();
                    $hreflang_data = $db->get_hreflang_by_post($post->ID);

                    if (!empty($hreflang_data) && count($hreflang_data) >= 2) {
                        $current_url = get_permalink($post->ID);
                        $flags_html = '';

                        $menu_flags_display = get_option('chrmrtns_pml_menu_flags_display', 'row');

                        foreach ($hreflang_data as $translation) {
                            $is_current = ($translation->url === $current_url);

                            $lang_info = $this->get_language_info($translation->language_code);
                            $flag_emoji = '';
                            $title = '';

                            if ($lang_info) {
                                $flag_emoji = $lang_info['flag'];
                                $title = $lang_info['name'];
                            }

                            // Only show other language flags (not current)
                            if (!$is_current && $flag_emoji) {
                                $flags_html .= '<a href="' . esc_url($translation->url) . '" title="' . esc_attr($title) . '" hreflang="' . esc_attr($translation->language_code) . '">' . esc_html($flag_emoji) . '</a>';
                            }
                        }

                        if (!empty($flags_html)) {
                            $item->title = '<span class="chrmrtns-pml-menu-flags-wrapper">' . $flags_html . '</span>';
                            $item->url = '#';
                            $item->classes[] = 'chrmrtns-pml-language-flags-item';
                            if ($menu_flags_display === 'column') {
                                $item->classes[] = 'chrmrtns-pml-flags-column';
                            }
                        } else {
                            // Hide the menu item if no translations
                            unset($items[$key]);
                        }
                    } else {
                        // Hide the menu item if no translations
                        unset($items[$key]);
                    }
                } else {
                    // Hide on non-singular pages
                    unset($items[$key]);
                }
            }
        }

        return $items;
    }
}