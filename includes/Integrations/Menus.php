<?php
/**
 * Menu Translation Integration
 *
 * Handles translation of WordPress navigation menus
 *
 * @package PuzzleSync
 * @since 1.1.0
 */

namespace Chrmrtns\PuzzleSync\Integrations;

if (!defined('ABSPATH')) {
    exit;
}

class Menus {

    /**
     * @var array Menu translation map
     */
    private $menu_translations = array();

    /**
     * Initialize menu hooks
     */
    public function init() {
        // Admin hooks
        add_action('admin_menu', array($this, 'add_menu_translation_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Frontend hooks
        add_filter('wp_nav_menu_args', array($this, 'translate_menu'), 10, 1);

        // Load menu translations
        $this->load_menu_translations();
    }

    /**
     * Add menu translation management page
     */
    public function add_menu_translation_page() {
        add_submenu_page(
            'options-general.php',
            __('Menu Translations', 'puzzlesync'),
            __('Menu Translations', 'puzzlesync'),
            'manage_options',
            'puzzlesync-menu-translations',
            array($this, 'render_menu_translation_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'puzzlesync_menu_translations',
            'chrmrtns_puzzlesync_menu_translations',
            array(
                'type'              => 'array',
                'sanitize_callback' => array($this, 'sanitize_menu_translations'),
            )
        );
    }

    /**
     * Render menu translation page
     */
    public function render_menu_translation_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle form submission
        if (isset($_POST['puzzlesync_save_menu_translations']) && check_admin_referer('puzzlesync_menu_translations')) {
            $this->save_menu_translations();
            echo '<div class="notice notice-success"><p>' . __('Menu translations saved successfully!', 'puzzlesync') . '</p></div>';
        }

        $menus = wp_get_nav_menus();
        $languages = $this->get_supported_languages();
        $menu_translations = get_option('chrmrtns_puzzlesync_menu_translations', array());

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <p><?php _e('Link your navigation menus to their translated versions. When a visitor views content in a specific language, the corresponding menu will be displayed automatically.', 'puzzlesync'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('puzzlesync_menu_translations'); ?>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Menu', 'puzzlesync'); ?></th>
                            <th><?php _e('Language', 'puzzlesync'); ?></th>
                            <?php foreach ($languages as $lang): ?>
                                <th>
                                    <span class="menu-lang-flag"><?php echo esc_html($lang['flag']); ?></span>
                                    <?php echo esc_html($lang['name']); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($menus as $menu): ?>
                            <tr>
                                <td><strong><?php echo esc_html($menu->name); ?></strong></td>
                                <td>
                                    <select name="menu_language[<?php echo esc_attr($menu->term_id); ?>]">
                                        <option value=""><?php _e('-- Select Language --', 'puzzlesync'); ?></option>
                                        <?php foreach ($languages as $lang): ?>
                                            <?php
                                            $selected = '';
                                            if (isset($menu_translations[$menu->term_id]['language'])) {
                                                $selected = selected($menu_translations[$menu->term_id]['language'], $lang['code'], false);
                                            }
                                            ?>
                                            <option value="<?php echo esc_attr($lang['code']); ?>" <?php echo $selected; ?>>
                                                <?php echo esc_html($lang['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <?php foreach ($languages as $lang): ?>
                                    <td>
                                        <select name="menu_translations[<?php echo esc_attr($menu->term_id); ?>][<?php echo esc_attr($lang['code']); ?>]">
                                            <option value=""><?php _e('-- Select Menu --', 'puzzlesync'); ?></option>
                                            <?php foreach ($menus as $translation_menu): ?>
                                                <?php
                                                $selected = '';
                                                if (isset($menu_translations[$menu->term_id]['translations'][$lang['code']])) {
                                                    $selected = selected($menu_translations[$menu->term_id]['translations'][$lang['code']], $translation_menu->term_id, false);
                                                }
                                                ?>
                                                <option value="<?php echo esc_attr($translation_menu->term_id); ?>" <?php echo $selected; ?>>
                                                    <?php echo esc_html($translation_menu->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="submit">
                    <input type="submit"
                           name="puzzlesync_save_menu_translations"
                           class="button button-primary"
                           value="<?php esc_attr_e('Save Menu Translations', 'puzzlesync'); ?>">
                </p>
            </form>

            <div class="puzzlesync-menu-help">
                <h2><?php _e('How It Works', 'puzzlesync'); ?></h2>
                <ol>
                    <li><?php _e('Create separate navigation menus for each language', 'puzzlesync'); ?></li>
                    <li><?php _e('Assign a language to each menu in the "Language" column', 'puzzlesync'); ?></li>
                    <li><?php _e('Link translated versions of menus in the language columns', 'puzzlesync'); ?></li>
                    <li><?php _e('PuzzleSync will automatically display the correct menu based on the current content language', 'puzzlesync'); ?></li>
                </ol>

                <h3><?php _e('Example Setup', 'puzzlesync'); ?></h3>
                <ul>
                    <li><strong><?php _e('Main Menu', 'puzzlesync'); ?></strong> - <?php _e('Language: English', 'puzzlesync'); ?> → <?php _e('German translation: Hauptmenü', 'puzzlesync'); ?></li>
                    <li><strong><?php _e('Hauptmenü', 'puzzlesync'); ?></strong> - <?php _e('Language: German', 'puzzlesync'); ?> → <?php _e('English translation: Main Menu', 'puzzlesync'); ?></li>
                </ul>
            </div>
        </div>

        <style>
            .menu-lang-flag {
                font-size: 18px;
                margin-right: 5px;
            }
            .widefat th,
            .widefat td {
                padding: 12px;
            }
            .puzzlesync-menu-help {
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 20px;
                margin-top: 30px;
            }
            .puzzlesync-menu-help h2 {
                margin-top: 0;
            }
            .puzzlesync-menu-help ul,
            .puzzlesync-menu-help ol {
                line-height: 1.8;
            }
        </style>
        <?php
    }

    /**
     * Save menu translations
     */
    private function save_menu_translations() {
        if (!isset($_POST['menu_language']) || !isset($_POST['menu_translations'])) {
            return;
        }

        $menu_languages = $_POST['menu_language'];
        $menu_translations = $_POST['menu_translations'];
        $saved_data = array();

        foreach ($menu_languages as $menu_id => $language) {
            if (empty($language)) {
                continue;
            }

            $saved_data[$menu_id] = array(
                'language'     => sanitize_text_field($language),
                'translations' => array(),
            );

            if (isset($menu_translations[$menu_id]) && is_array($menu_translations[$menu_id])) {
                foreach ($menu_translations[$menu_id] as $lang_code => $translation_menu_id) {
                    if (!empty($translation_menu_id)) {
                        $saved_data[$menu_id]['translations'][sanitize_text_field($lang_code)] = intval($translation_menu_id);
                    }
                }
            }
        }

        update_option('chrmrtns_puzzlesync_menu_translations', $saved_data);
        $this->load_menu_translations();
    }

    /**
     * Sanitize menu translations
     *
     * @param array $input Input data
     * @return array Sanitized data
     */
    public function sanitize_menu_translations($input) {
        if (!is_array($input)) {
            return array();
        }

        $sanitized = array();

        foreach ($input as $menu_id => $data) {
            if (!is_array($data)) {
                continue;
            }

            $sanitized[intval($menu_id)] = array(
                'language'     => isset($data['language']) ? sanitize_text_field($data['language']) : '',
                'translations' => array(),
            );

            if (isset($data['translations']) && is_array($data['translations'])) {
                foreach ($data['translations'] as $lang => $translation_id) {
                    $sanitized[intval($menu_id)]['translations'][sanitize_text_field($lang)] = intval($translation_id);
                }
            }
        }

        return $sanitized;
    }

    /**
     * Load menu translations from database
     */
    private function load_menu_translations() {
        $this->menu_translations = get_option('chrmrtns_puzzlesync_menu_translations', array());
    }

    /**
     * Translate menu based on current language
     *
     * @param array $args Menu arguments
     * @return array Modified menu arguments
     */
    public function translate_menu($args) {
        if (empty($args['menu'])) {
            return $args;
        }

        // Get current language
        $current_language = $this->get_current_language();

        // Find the menu for the current language
        $menu = $args['menu'];
        $menu_object = null;

        // Check if menu is a term ID or slug
        if (is_numeric($menu)) {
            $menu_id = intval($menu);
        } else {
            $menu_object = wp_get_nav_menu_object($menu);
            $menu_id = $menu_object ? $menu_object->term_id : 0;
        }

        if (!$menu_id) {
            return $args;
        }

        // Find translation for current language
        foreach ($this->menu_translations as $original_menu_id => $translation_data) {
            if ($original_menu_id == $menu_id || in_array($menu_id, $translation_data['translations'])) {
                // Check if we need to translate
                if (isset($translation_data['translations'][$current_language])) {
                    $translated_menu_id = $translation_data['translations'][$current_language];

                    // Only translate if we're not already showing the correct menu
                    if ($translated_menu_id != $menu_id) {
                        $args['menu'] = $translated_menu_id;
                    }
                }
                break;
            }
        }

        return $args;
    }

    /**
     * Get current language
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
     * Get supported languages
     *
     * @return array
     */
    private function get_supported_languages() {
        return get_option('chrmrtns_puzzlesync_languages', array(
            array('code' => 'en', 'name' => 'English', 'flag' => '🇺🇸'),
            array('code' => 'de', 'name' => 'Deutsch', 'flag' => '🇩🇪'),
        ));
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current page hook
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'settings_page_puzzlesync-menu-translations') {
            return;
        }

        wp_enqueue_style('puzzlesync-admin');
    }
}
