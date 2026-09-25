<?php
/**
 * Plugin Name: Numpo
 * Description: Numpo PHP-only web crawler for WordPress.
 * Version: 0.3.0
 * Requires PHP: 7.4
 */
if(!defined('ABSPATH')) exit;
define('NUMPO_VERSION','0.3.0');
define('NUMPO_DIR',plugin_dir_path(__FILE__));
define('NUMPO_MAIN_FILE',__FILE__);
require_once NUMPO_DIR.'includes/class-numpo-settings.php';
require_once NUMPO_DIR.'includes/class-numpo-db.php';
require_once NUMPO_DIR.'includes/class-numpo-crawler.php';
require_once NUMPO_DIR.'includes/class-numpo-discovery.php';
require_once NUMPO_DIR.'includes/class-numpo-worker.php';
require_once NUMPO_DIR.'includes/class-numpo-api.php';
require_once NUMPO_DIR.'includes/class-numpo-admin.php';
require_once NUMPO_DIR.'includes/class-numpo-diagnostics.php';
add_action('plugins_loaded', function(){
 Numpo_DB::install();
 Numpo_Settings::init();
 Numpo_Worker::init();
 Numpo_API::init();
 Numpo_Admin::init();
 Numpo_Diagnostics::init();
});
register_activation_hook(__FILE__, ['Numpo_DB','install']);
