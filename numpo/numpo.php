<?php
/**
 * Plugin Name: Numpo Control Plane
 * Description: WordPress control plane for the Numpo crawler engine.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) exit;

define('NUMPO_VERSION', '0.1.0');
define('NUMPO_FILE', __FILE__);
define('NUMPO_DIR', plugin_dir_path(__FILE__));
define('NUMPO_URL', plugin_dir_url(__FILE__));

require_once NUMPO_DIR . 'includes/class-numpo-db.php';
require_once NUMPO_DIR . 'includes/class-numpo-engine-client.php';
require_once NUMPO_DIR . 'includes/class-numpo-rest.php';
require_once NUMPO_DIR . 'includes/class-numpo-admin.php';

register_activation_hook(__FILE__, array('Numpo_DB', 'activate'));

add_action('plugins_loaded', function () {
    Numpo_DB::init();
    Numpo_Engine_Client::init();
    Numpo_REST::init();
    Numpo_Admin::init();
});
