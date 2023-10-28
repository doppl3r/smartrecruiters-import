<?php

class IDX_SmartRecruiters_Public {
	private $shortcodes;

	public function __construct() {
		// Initialize assets
		add_action('wp_enqueue_scripts', array($this, 'add_assets'));

		// Register single page template
		add_filter('single_template', array($this, 'load_page_template'));

		// Load shortcode class
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-shortcodes.php';
		$this->shortcodes = new IDX_SmartRecruiters_Shortcodes();
	}

	public function add_assets() {
		// Register scripts and stylesheets
		wp_register_script('scripts', MY_PLUGIN_DIR . 'assets/public/js/scripts.js', array('jquery'), time());
		wp_register_style('styles', MY_PLUGIN_DIR . 'assets/public/css/styles.css', array(), time());

		// Enqueue scripts
		wp_enqueue_script('scripts');
		wp_enqueue_style('styles');
	}

	function load_page_template($template) {
		global $post;
		if ($post->post_type == 'career') {
			return plugin_dir_path( __FILE__ ) . 'page-career.php';
		}
		return $template;
	}
}
