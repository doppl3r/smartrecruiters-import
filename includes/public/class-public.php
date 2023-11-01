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

    // Register AJAX functions from CMS admin front-end
    add_action('wp_ajax_search_jobs', array($this, 'search_jobs'));
    add_action('wp_ajax_nopriv_search_jobs', array($this, 'search_jobs'));
  }

  public function add_assets() {
    // Register scripts and stylesheets
    wp_register_script('scripts', MY_PLUGIN_DIR . 'assets/public/js/scripts.js', array('jquery'), time());
    wp_register_style('styles', MY_PLUGIN_DIR . 'assets/public/css/styles.css', array(), time());
    
    // Register shortcode search scripts/styles
    wp_register_script('search', MY_PLUGIN_DIR . 'assets/public/js/search.js', array('jquery'), time());
    wp_register_style('search', MY_PLUGIN_DIR . 'assets/public/css/search.css', array(), time());

    // Enqueue scripts (search is enqueued in the shortcodes if used)
    wp_enqueue_script('scripts');
    wp_enqueue_style('styles');

    // Localize scripts (allows ajax in frontend)
    wp_localize_script('scripts', 'admin', array('ajax_url' => admin_url('admin-ajax.php')));
  }

  function load_page_template($template) {
    global $post;
    if ($post->post_type == 'job') {
      return plugin_dir_path( __FILE__ ) . 'page-job.php';
    }
    return $template;
  }

  function search_jobs() {
    $offset = intval($_POST['offset'] ?? 0);
    $limit = intval($_POST['limit'] ?? 10);
    $keywords = $_POST['keywords'];
    $area = $_POST['area']; // ex: City, State, Zip
    $distance = $_POST['distance'];
    $department = $_POST['department'];
    $community = $_POST['community'];
    $full_time = $_POST['full_time'];
    $part_time = $_POST['part_time'];
    $communities = [];
    $departments = [];

    // Query all posts (necessary for community/department filters)
    $args = array(
      'post_type'      => array('job'),
      'post_status'    => 'publish',
      'posts_per_page' => -1,
      'offset'         => 0,
      'meta_query'     => array()
    );

    // Search by keywords
    if (!empty($keywords)) {
      $words = explode(',', $keywords);
      foreach ($words as $key => $word) {
        $args['meta_query'][] = array(
          'relation'  => 'OR',
          array('key' => 'idx_smartrecruiters_title', 'value' => $word, 'compare' => 'LIKE')
        );
      }
    }

    // Search by area
    if (!empty($area)) {
      $words = explode(',', $area);
      foreach ($words as $key => $word) {
        $args['meta_query'][] = array(
          'relation'  => 'OR',
          array('key' => 'idx_smartrecruiters_city', 'value' => $word, 'compare' => 'LIKE'),
          array('key' => 'idx_smartrecruiters_region', 'value' => $word, 'compare' => 'LIKE'),
          array('key' => 'idx_smartrecruiters_region_code', 'value' => $word, 'compare' => 'LIKE'),
          array('key' => 'idx_smartrecruiters_postal_code', 'value' => $word, 'compare' => 'LIKE')
        );
      }
    }

    // Search by department
    if ($department != 'all') {
      $args['meta_query'][] = array(
        'relation'  => 'OR',
        array('key' => 'idx_smartrecruiters_department', 'value' => $department, 'compare' => 'LIKE')
      );
    }

    // Search by community
    if ($community != 'all') {
      $args['meta_query'][] = array(
        'relation'  => 'OR',
        array('key' => 'idx_smartrecruiters_location_name', 'value' => $community, 'compare' => 'LIKE')
      );
    }
    
    // Search by full time booleans
    if ($part_time == 'false') {
      $args['meta_query'][] = array(
        'relation'  => 'OR',
        array('key' => 'idx_smartrecruiters_employment_type', 'value' => 'Full-time', 'compare' => 'LIKE')
      );
    }

    // Search by part time booleans
    if ($full_time == 'false') {
      $args['meta_query'][] = array(
        'relation'  => 'OR',
        array('key' => 'idx_smartrecruiters_employment_type', 'value' => 'Part-time', 'compare' => 'LIKE')
      );
    }

    // Create an array of jobs with post meta
    wp_reset_postdata();
    $query = new WP_Query($args);
    $results = array(
      'filters'        => array(),
      'jobs'           => array(),
      'pagination'    => array(
        'offset'       => $offset,
        'limit'        => $limit,
        'totalFound'   => $query->found_posts
      )
    );

    // Loop through each post result
    $index = 0;
    foreach ($query->posts as $key => $post) {
      // Set post meta variables
      $description = mb_strimwidth($post->post_excerpt, 0, 360, '...');
      $address = get_post_meta($post->ID, 'idx_smartrecruiters_address', true);
      $city = get_post_meta($post->ID, 'idx_smartrecruiters_city', true);
      $region_code = get_post_meta($post->ID, 'idx_smartrecruiters_region_code', true);
      $zip = get_post_meta($post->ID, 'idx_smartrecruiters_postal_code', true);
      $country = get_post_meta($post->ID, 'idx_smartrecruiters_country', true);
      $country_code = get_post_meta($post->ID, 'idx_smartrecruiters_country_code', true);
      $employment = get_post_meta($post->ID, 'idx_smartrecruiters_full_part_time', true);
      $hourly_min = get_post_meta($post->ID, 'idx_smartrecruiters_hourly_rate_minimum', true);
      $hourly_max = get_post_meta($post->ID, 'idx_smartrecruiters_hourly_rate_maximum', true);
      $community_name = get_post_meta($post->ID, 'idx_smartrecruiters_location_name', true);
      $department_name = get_post_meta($post->ID, 'idx_smartrecruiters_department', true);
      $latitude = get_post_meta($post->ID, 'idx_smartrecruiters_latitude', true);
      $longitude = get_post_meta($post->ID, 'idx_smartrecruiters_longitude', true);

      if (!empty($hourly_min) && empty($hourly_max)) $hourly = $hourly_min;
      else if (empty($hourly_min) && !empty($hourly_max)) $hourly = $hourly_max;
      else if (!empty($hourly_min) && !empty($hourly_max)) $hourly = $hourly_min . ' - ' . $hourly_max;
      else $hourly = 'n/a';

      // Only add jobs within offset + limit range
      if (intval($key) >= intval($offset) && intval($key) < (intval($offset) + intval($limit))) {
        // TODO: Geolocate distance between user IP lat/lng to job lat/lng ($latitude, $longitude)
        if ($distance > 0) {
          // Create job array with basic data
          $job = array(
            'title'        => $post->post_title,
            'link'         => get_permalink($post->ID),
            'description'  => $description,
            'address'      => $address,
            'city'         => $city,
            'region_code'  => $region_code,
            'zip'          => $zip,
            'country'      => $country,
            'country_code' => $country_code,
            'community'    => $community_name,
            'employment'   => $employment,
            'hourly'       => $hourly
          );
          $results['jobs'][$index] = $job;
          $index++;
        }
      }

      // Add all available communities/departments
      if (!empty($community_name)) $communities[$community_name] = $community_name; // Map community keys/values
      if (!empty($department_name)) $departments[$department_name] = $department_name; // Map department keys/values
    }

    // Set a list of community array values
    $results['communities'] = array_values($communities);
    $results['departments'] = array_values($departments);
    $results['community'] = $community;
    $results['department'] = $department;

    // Return posts as JSON data
    wp_send_json_success($results);
  }
}
