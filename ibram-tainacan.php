<?php
/**
 *
 * @link              https://github.com/l3pufg/ibram-plugin-descarte
 * @since             1.0.0
 * @package           Ibram_Tainacan
 *
 * @wordpress-plugin
 * Plugin Name:       IBRAM_Tainacan
 * Plugin URI:        https://github.com/l3pufg/ibram-plugin-descarte
 * Description:       Funcionalidades extras do Tainacan especialmente para o IBRAM
 * Version:           1.0.0
 * Author:            Rodrigo de Oliveira
 * Author URI:        https://github.com/l3pufg/ibram-plugin-descarte
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ibram-tainacan
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


define('IBRAM_VERSION','0.1');

define( 'IBRAM_PATH', plugin_dir_path( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_ibram_tainacan() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ibram-tainacan-activator.php';
	require_once plugin_dir_path(__FILE__) . 'public/class-import-data-set.php';
	Ibram_Tainacan_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_ibram_tainacan() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ibram-tainacan-deactivator.php';
    require_once plugin_dir_path(__FILE__) . 'public/class-import-data-set.php';
	Ibram_Tainacan_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ibram_tainacan' );
register_deactivation_hook( __FILE__, 'deactivate_ibram_tainacan' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ibram-tainacan.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_ibram_tainacan() {

	$plugin = new Ibram_Tainacan();
	$plugin->run();

}
run_ibram_tainacan();
