<?php

/**
 * @wordpress-plugin
 * Plugin Name:       SmartRecruiters WordPress Plugin
 * Description:       Import and manage job postings from SmartRecruiters
 * Version:           1.0.0
 * Author:            Jacob DeBenedetto
 * Author URI:        https://www.investisdigital.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       smartrecruiters
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) { die; }

// Define plugin path
define('MY_PLUGIN_DIR', plugin_dir_url(__FILE__));

// Add optional plugin behavior
function activate_smartrecruiters() {}
function deactivate_smartrecruiters() {}
function uninstall_smartrecruiters() {}

// Use core activation/deactivation hooks
register_activation_hook(__FILE__, 'activate_smartrecruiters');
register_deactivation_hook(__FILE__, 'deactivate_smartrecruiters');
register_uninstall_hook(__FILE__, 'uninstall_smartrecruiters');

// Evaluate the main file and include classes
require_once 'includes/class-plugin.php';

// Run the plugin
function run_smartrecruiters() {
  // Make available for other classes
  global $smartrecruiters_plugin;

  // Init plugin class
  $smartrecruiters_plugin = new SmartRecruiters_Plugin();
  $smartrecruiters_plugin->run();
}

// Call the plugin
run_smartrecruiters();