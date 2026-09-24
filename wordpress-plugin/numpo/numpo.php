<?php
/**
 * Plugin Name: Numpo
 * Description: Numpo crawler control plane for WordPress.
 * Version: 0.1.0
 * Requires PHP: 7.4
 */
if (!defined('ABSPATH')) exit;
define('NUMPO_VERSION','0.1.0');
define('NUMPO_DIR',plugin_dir_path(__FILE__));
require_once NUMPO_DIR.'includes/class-numpo-settings.php';
require_once NUMPO_DIR.'includes/class-numpo-api.php';
require_once NUMPO_DIR.'includes/class-numpo-admin.php';
add_action('plugins_loaded', function(){ Numpo_Settings::init(); Numpo_API::init(); Numpo_Admin::init(); });
register_activation_hook(__FILE__, function(){ if(!get_option('numpo_engine_url')) update_option('numpo_engine_url','http://127.0.0.1:8080'); });
