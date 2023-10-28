<?php

class IDX_SmartRecruiters_Admin {
  public function __construct() {
    add_action('admin_menu', array($this, 'add_menu_page')); /* Add admin menu and page */
    add_action('admin_enqueue_scripts', array($this, 'add_assets'));
    add_action('wp_ajax_import_jobs', array($this, 'import_jobs'));
    add_action('wp_ajax_publish_job', array($this, 'publish_job'));
  }

  public function add_menu_page() {
    // Only show for admin users
    if (current_user_can('administrator')) {
      // Top level menu
      $page_title = 'SmartRecruiters';
      $menu_title = 'SmartRecruiters';
      $capability = 'edit_others_posts';
      $menu_slug = 'idx-smartrecruiters';
      $parent_slug = $menu_slug;
      $function = array($this, 'render_import_page');
      $icon_url = 'dashicons-groups';
      $position = 6;
      add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
    
      // Sub level menu
      $capability = 'manage_options';

      // Import
      $page_title = 'Import';
      $menu_title = 'Import';
      $menu_slug = $parent_slug;
      $function = array($this, 'render_import_page');
      add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
      
      // List
      $page_title = 'View All';
      $menu_title = 'View All';
      $menu_slug = 'edit.php?post_type=career';
      add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug);

      // Settings
      $page_title = 'Settings';
      $menu_title = 'Settings';
      $menu_slug = $parent_slug . "-settings";
      $function = array($this, 'render_settings_page');
      add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
    }
  }

  public function add_assets($hook) {
    // Register scripts and stylesheets
    wp_register_script('scripts', MY_PLUGIN_DIR . 'assets/admin/js/scripts.js', array('jquery'), time());
    wp_register_style('styles', MY_PLUGIN_DIR . 'assets/admin/css/styles.css', array(), time());

    // Enqueue scripts
    wp_enqueue_script('scripts');
    wp_enqueue_style('styles');
  }

  public function render_import_page() {
    require_once 'page-import.php';
  }
  
  public function render_settings_page() {
    require_once 'page-settings.php';
  }

  public function fetch_token() {
    // Return token from cookie (expires in 30 minutes)
    if (isset($_COOKIE['smartrecruiters_token'])) {
      return $_COOKIE['smartrecruiters_token'];
    }
    else {
      // Request new token from SmartRecruiters API (expires in 30 minutes)
      $curl = curl_init();
      $response = array();
      $client_id = get_option('smartrecruiters_client_id');
      $client_secret = get_option('smartrecruiters_client_secret');
      $token = '';
  
      // Configure curl array
      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.smartrecruiters.com/identity/oauth/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'client_id=' . $client_id . '&client_secret=' . $client_secret . '&grant_type=client_credentials',
        CURLOPT_HTTPHEADER => array(
          'Content-Type: application/x-www-form-urlencoded'
        ),
      ));
      $response = json_decode(curl_exec($curl), true);
      curl_close($curl);
  
      // Get token from response data and save cookie
      if (isset($response['access_token'])) {
        $token = $response['access_token'];
        $expires_in = 1799; // SmartRecruiter tokens expire in 30 minutes
        setcookie('smartrecruiters_token', $token, time() + $expires_in, '/');
      }
      return $token;
    }
  }

  public function fetch_jobs() {
    // This function can fetch a single job or multiple jobs
    $token = $this->fetch_token();
    $query = $_POST['query'];
    $curl = curl_init();

    // Configure curl array
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.smartrecruiters.com/jobs/' . $query,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'authorization: Bearer ' . $token
      ),
    ));

    // Return resopnse array
    $response = json_decode(curl_exec($curl), true);
    curl_close($curl);
    return $response;
  }

  public function import_jobs() {
    // Get a list of all jobs from SmartRecruiter API
    $jobs = $this->get_job_list();

    // Return JSON results
    if (isset($jobs['content'])) wp_send_json_success($jobs);
    else wp_send_json_error($jobs);
  }

  public function publish_job() {
    $job = $this->get_job_details();

    // Default post data
    $post_arr = array(
      'post_status'  => 'publish',
      'post_type'    => 'career',
      'post_title'   => $job['title']
    );

    // Update post content from SmartRecruiters "jobAd sections
    $content = '';
    foreach ($job['jobAd']['sections'] as $index => $section) {
      $content .= '<h3>' . $section['title'] . '</h3>';
      $content .= $section['text'];
    }
    $post_arr['post_content'] = $content;

    // Update post meta from SmartRecruiters data
    $meta_arr = array(
      'idx_smartrecruiters_id' => $job['id'],
      'idx_smartrecruiters_country' => $job['location']['country'],
      'idx_smartrecruiters_country_code' => $job['location']['countryCode'],
      'idx_smartrecruiters_city' => $job['location']['city'],
      'idx_smartrecruiters_region' => $job['location']['region'],
      'idx_smartrecruiters_region_code' => $job['location']['regionCode'],
      'idx_smartrecruiters_postal_code' => $job['location']['postalCode'],
      'idx_smartrecruiters_address' => $job['location']['address'],
      'idx_smartrecruiters_latitude' => $job['location']['latitude'],
      'idx_smartrecruiters_longitude' => $job['location']['longitude'],
      'idx_smartrecruiters_apply' => $job['actions']['applyOnWeb']['url'],
      'idx_smartrecruiters_department' => $job['department']['label'],
      'idx_smartrecruiters_industry' => $job['industry']['label'],
      'idx_smartrecruiters_employment_type' => $job['typeOfEmployment']['label'],
      'idx_smartrecruiters_experience_level' => $job['experienceLevel']['label']
    );

    // Convert Smartrecruiters 'properties' array into post meta
    foreach ($job['properties'] as $index => $property) {
      $key = preg_replace('/[^a-zA-Z0-9]+/', '_', $property['label']);
      $key = strtolower($key);
      $key = trim($key, '_');
      $meta_arr['idx_smartrecruiters_' . $key] = $property['value']['label']; 
    }

    // Check if posts with matching job id exist
    $posts = get_posts(array(
      'numberposts'   => 1,
      'post_type'     => 'career',
      'meta_key'      => 'idx_smartrecruiters_id',
      'meta_value'    => $_POST['id']
    ));
    
    if (count($posts) > 0) {
      // Update existing post
      $post_id = $posts['ID'];
      $post_arr['ID'] = $post_id;
      wp_update_post($post_arr);

      // Update each post meta
      foreach ($meta_arr as $key => $meta) {
        update_post_meta($post_id, $key, $meta);
      }
    }
    else {
      // Add new post
      $post_id = wp_insert_post($post_arr);
      $posts = array(get_post($post_id));

      // Add each post meta
      foreach ($meta_arr as $key => $meta) {
        add_post_meta($post_id, $key, $meta);
      }
    }

    // Send results back as json data
    if (isset($job['id'])) wp_send_json_success($job);
    else wp_send_json_error($job);
  }

  public function get_job_list() {
    // Get parameters with defaults
    $offset = intval($_POST['offset'] ?? 0);
    $limit = intval($_POST['limit'] ?? 10);
    $updatedAfter = $_POST['updatedAfter'];

    // Resolve Smartrecruiter limit (100)
    if ($limit > 100) $limit = 100; 

    // Update query from parameters
    $query = '?offset=' . $offset . '&limit=' . $limit;
    if (isset($updatedAfter)) $query .= '&updatedAfter=' . $updatedAfter;

    // Update the query for the job fetch
    $_POST['query'] = $query; // Set query with parameters
    return $this->fetch_jobs();
  }

  public function get_job_details() {
    $_POST['query'] = $_POST['id']; // Set query to a single job ID
    return $this->fetch_jobs();
  }
}