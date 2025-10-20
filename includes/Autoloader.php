<?php
/**
 * PuzzleSync Autoloader
 *
 * PSR-4 compliant autoloader for PuzzleSync classes
 *
 * @package PuzzleSync
 * @since 1.0.4
 */

namespace Chrmrtns\PuzzleSync;

if (!defined('ABSPATH')) {
    exit;
}

class Autoloader {

    /**
     * Register the autoloader
     */
    public static function register() {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /**
     * Autoload classes
     *
     * @param string $class The fully-qualified class name
     */
    public static function autoload($class) {
        // Project-specific namespace prefix
        $prefix = 'Chrmrtns\\PuzzleSync\\';

        // Base directory for the namespace prefix
        $base_dir = defined('CHRMRTNS_PUZZLESYNC_PLUGIN_DIR')
            ? CHRMRTNS_PUZZLESYNC_PLUGIN_DIR . 'includes/'
            : dirname(__DIR__) . '/includes/';

        // Does the class use the namespace prefix?
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            // No, move to the next registered autoloader
            return;
        }

        // Get the relative class name
        $relative_class = substr($class, $len);

        // Replace namespace separators with directory separators
        $relative_class = str_replace('\\', '/', $relative_class);

        // Convert class name for file lookup
        // Try multiple naming conventions

        // First try: Namespace\ClassName -> Namespace/ClassName.php
        $file = $base_dir . $relative_class . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }

        // Second try: Namespace\ClassName -> namespace/class-classname.php
        $parts = explode('/', $relative_class);
        $class_name = array_pop($parts);
        $file_name = 'class-' . strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $class_name)) . '.php';

        if (!empty($parts)) {
            $file = $base_dir . strtolower(implode('/', $parts)) . '/' . $file_name;
        } else {
            $file = $base_dir . $file_name;
        }

        if (file_exists($file)) {
            require $file;
            return;
        }

        // Third try: Just the class name in includes root
        $file = $base_dir . $class_name . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
}
