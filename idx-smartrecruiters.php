<?php

/**
 * @wordpress-plugin
 * Plugin Name:       IDX SmartRecruiters
 * Description:       Import and manage job postings from SmartRecruiters
 * Version:           1.0.0
 * Author:            IDX (Investis Digital)
 * Author URI:        https://www.investisdigital.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       idx-smartrecruiters
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) { die; }

// Define plugin path
define('MY_PLUGIN_DIR', plugin_dir_url(__FILE__));

// Add optional plugin behavior
function activate_idx_smartrecruiters() {}
function deactivate_idx_smartrecruiters() {}
function uninstall_idx_smartrecruiters() {}

// Use core activation/deactivation hooks
register_activation_hook(__FILE__, 'activate_idx_smartrecruiters');
register_deactivation_hook(__FILE__, 'deactivate_idx_smartrecruiters');
register_uninstall_hook(__FILE__, 'uninstall_idx_smartrecruiters');

// Evaluate the main file and include classes
require_once 'includes/class-plugin.php';

// Run the plugin
function run_idx_smartrecruiters() {
  // Make available for other classes
  global $idx_smartrecruiters_plugin;

  // Init plugin class
  $idx_smartrecruiters_plugin = new IDX_SmartRecruiters_Plugin();
  $idx_smartrecruiters_plugin->run();
}

// Call the plugin
run_idx_smartrecruiters();