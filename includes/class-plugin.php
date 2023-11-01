<?php

class IDX_SmartRecruiters_Plugin {
	protected $admin;

	public function __construct() {
		// Initialize admin class
		require_once 'admin/class-admin.php';
		$admin = new IDX_SmartRecruiters_Admin();
		
		// Initialize public class
		require_once 'public/class-public.php';
		$public = new IDX_SmartRecruiters_Public();
	}

	public function run() {
		// Register custom post type 'jobs'
		add_action('init', array($this, 'register_custom_post_type_taxonomy'));
		add_action('save_post', array($this, 'default_taxonomy_term'), 100, 2);
		add_action('init', array($this, 'register_custom_post_type'), 10, 2);

		// Update links
		add_filter('post_type_link', array($this, 'filter_post_type_link'), 10, 2);
		add_filter('cptp_is_rewrite_supported_by_job',  '__return_false'); // Disable CPTP plugin for job post type
	}

	public function register_custom_post_type_taxonomy() {
		$post_type = 'job';
		$default_slug = 'jobs';
		$slug = get_option('smartrecruiters_rewrite', $default_slug); // Option: 'jobs/search'
		$slug = empty($slug) ? $default_slug : $slug;

		$labels = array(
			'name'                  => 'Job Categories',
			'singular_name'         => 'Job Category',
			'search_items'          => 'Search Job Categories',
			'all_items'             => 'All Job Categories',
			'parent_item'           => 'Parent Job Category',
			'parent_item_colon'     => 'Parent Job Category:',
			'edit_item'             => 'Edit Job Category',
			'update_item'           => 'Update Job Category',
			'add_new_item'          => 'Add New Job Category',
			'new_item_name'         => 'New Job Category Name',
			'menu_name'             => 'Job Category'
		);
		$args = array(
			'hierarchical'          => false,
			'public'                => true,
			'publicly_queryable'    => true,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'query_var'             => true,
			'show_admin_column'     => true,
			'has_archive'           => true,
			'rewrite'               => array(
				'with_front'        => true
			),
		);
		register_taxonomy('job_categories', array($post_type), $args);
	}

	function filter_post_type_link($link, $post) {
		$post_type = 'job';
		if ($post->post_type !== $post_type) return $link;
		if ($cats = get_the_terms($post->ID, 'job_categories')) $link = str_replace('%job_categories%', array_pop($cats)->slug, $link);
		return $link;
	}

	function default_taxonomy_term($post_id, $post) {
		if ($post->post_status === 'publish') {
			$defaults = array('job_categories' => array('Other'));
			$taxonomies = get_object_taxonomies($post->post_type);
			foreach ((array) $taxonomies as $taxonomy) {
				$terms = wp_get_post_terms($post_id, $taxonomy);
				if (empty($terms) && array_key_exists($taxonomy, $defaults)) {
					wp_set_object_terms($post_id, $defaults[$taxonomy], $taxonomy);
				}
			}
		}
	}

	public function register_custom_post_type() {
		// Initialize post type slug
		$post_type = 'job';
		
		// Define strings by post type
		$singular = $post_type;
		$plural = $singular . 's';
		$default_slug = 'jobs';
		$slug = get_option('smartrecruiters_rewrite', $default_slug); // Option: 'jobs/search'
		$slug = empty($slug) ? $default_slug : $slug;
		$uppercaseSingular = ucwords($singular);
		$uppercasePlural = ucwords($plural);

		// Create labels
		$labels = array(
			'name'                  => $uppercasePlural,
			'singular_name'         => $uppercaseSingular,
			'menu_name'             => $uppercaseSingular,
			'name_admin_bar'        => $uppercaseSingular,
			'archives'              => $uppercaseSingular . ' Archives',
			'attributes'            => $uppercaseSingular . ' Attributes',
			'parent_item_colon'     => 'Parent ' . $uppercaseSingular . ':',
			'all_items'             => 'All ' . $uppercasePlural,
			'add_new_item'          => 'Add New ' . $uppercaseSingular,
			'add_new'               => 'Add New',
			'new_item'              => 'New ' . $uppercaseSingular,
			'edit_item'             => 'Edit ' . $uppercaseSingular,
			'update_item'           => 'Update ' . $uppercaseSingular,
			'view_item'             => 'View ' . $uppercaseSingular,
			'view_items'            => 'View ' . $uppercaseSingular,
			'search_items'          => 'Search ' . $uppercaseSingular,
			'not_found'             => 'Not found',
			'not_found_in_trash'    => 'Not found in Trash',
			'featured_image'        => 'Featured Image',
			'set_featured_image'    => 'Set featured image',
			'remove_featured_image' => 'Remove featured image',
			'use_featured_image'    => 'Use as featured image',
			'insert_into_item'      => 'Insert into ' . $singular,
			'uploaded_to_this_item' => 'Uploaded to this ' . $singular,
			'items_list'            => $uppercasePlural . ' list',
			'items_list_navigation' => $uppercasePlural . ' list navigation',
			'filter_items_list'     => 'Filter ' . $uppercaseSingular . ' list',
		);
		$args = array(
			'label'                 => $uppercaseSingular,
			'description'           => $uppercaseSingular,
			'labels'                => $labels,
			'supports'              => array('title', 'editor'),
			'taxonomies'            => array('job_categories'),
			'hierarchical'          => false,
			'show_ui'               => true,
			'show_in_menu'          => false,
			'menu_position'         => 5,
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'public'                => true,
			'query_var'				=> true,
			'publicly_queryable'    => true,
			'exclude_from_search'   => false,
			'has_archive'           => true,
			'rewrite'            	=> array(
				'slug' => $slug . '/%job_categories%',
				'with_front' => true
			),
			'capability_type'       => 'post',
			'show_in_rest'          => true,
		);
		register_post_type($post_type, $args);
	}
}
