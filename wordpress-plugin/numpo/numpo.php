<?php
/**
 * Plugin Name: Numpo
 * Description: Numpo crawler control plane for WordPress.
 * Version: 0.2.0
 * Requires PHP: 7.4
 */
if (!defined('ABSPATH')) exit;
define('NUMPO_VERSION','0.2.0');
define('NUMPO_DIR',plugin_dir_path(__FILE__));
define('NUMPO_MAIN_FILE',__FILE__);
require_once NUMPO_DIR.'includes/class-numpo-settings.php';
require_once NUMPO_DIR.'includes/class-numpo-runtime.php';
require_once NUMPO_DIR.'includes/class-numpo-api.php';
require_once NUMPO_DIR.'includes/class-numpo-admin.php';
add_action('plugins_loaded', function(){ Numpo_Settings::init(); Numpo_Runtime::init(); Numpo_API::init(); Numpo_Admin::init(); });
register_activation_hook(__FILE__, ['Numpo_Runtime','activate']);
register_deactivation_hook(__FILE__, ['Numpo_Runtime','stop']);
register_uninstall_hook(__FILE__, ['Numpo_Runtime','uninstall']);
