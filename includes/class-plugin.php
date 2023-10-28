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
		add_action('init', array($this, 'register_custom_post_type'));
	}

	public function register_custom_post_type() {
		// Initialize post type slug
		$post_type = 'career';
		
		// Define strings by post type
		$singular = $post_type;
		$plural = $singular . 's';
		$default_slug = 'careers';
		$slug = get_option('smartrecruiters_rewrite', $default_slug); // Option: 'careers/search'
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
			'taxonomies'            => array($post_type),
			'hierarchical'          => false,
			'show_ui'               => true,
			'show_in_menu'          => false,
			'menu_position'         => 5,
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'public'                => true,
			'query_var'				=> true,
			'has_archive'           => true,
			'publicly_queryable'    => true,
			'exclude_from_search'   => false,
			'rewrite'            	=> array('slug' => $slug),
			'capability_type'       => 'page',
			'show_in_rest'          => true,
		);
		register_post_type($post_type, $args);
	}
}
