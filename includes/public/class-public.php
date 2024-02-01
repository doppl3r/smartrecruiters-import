<?php

class IDX_SmartRecruiters_Public {
  private $shortcodes;
  private $idx_api;

  public function __construct() {
    // Require API class helper
		require_once __DIR__ . '/../class-api.php';
		$this->idx_api = new IDX_SmartRecruiters_API();

    // Initialize assets
    add_action('wp_enqueue_scripts', array($this, 'add_assets'));

    // Register single page template
    add_filter('single_template', array($this, 'load_page_template'));

    // Add webhook endpoint
    add_action('rest_api_init', array($this, 'register_custom_route'));

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

    // Register vendor (3rd party) scripts and stylesheets
    wp_register_script('leaflet', MY_PLUGIN_DIR . 'vendor/leaflet/leaflet.js', array(), time());
    wp_register_style('leaflet', MY_PLUGIN_DIR . 'vendor/leaflet/leaflet.css', array(), time());

    // Enqueue scripts (search is enqueued in the shortcodes if used)
    wp_enqueue_script('scripts');
    wp_enqueue_style('styles');

    // Localize scripts (allows ajax in frontend)
    wp_localize_script('scripts', 'admin', array('ajax_url' => admin_url('admin-ajax.php')));
  }

  public function get_user_ip() {
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $ip;
  }

  public function get_ip_details($ip) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'http://ipwho.is/'.$ip,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response ;
  }
  public function calculate_distance_between_lat_long($userLat, $userLong,$clientLat,$clientLong) {
    // Haversine formula to calculate distance
    $radius = 3958.8; // Earth's radius in kilometers
    $dlat = deg2rad($userLat - $clientLat);
    $dlon = deg2rad($userLong - $clientLong);
    $a = sin($dlat / 2) * sin($dlat / 2) + cos(deg2rad($clientLat)) * cos(deg2rad($userLat)) * sin($dlon / 2) * sin($dlon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $radius * $c;
    return json_decode($distance, true);
  }

  public function load_page_template($template) {
    global $post;
    if ($post->post_type == 'job') {
      return plugin_dir_path( __FILE__ ) . 'page-job.php';
    }
    return $template;
  }

  public function register_custom_route() {
    // Register REST webhook
    register_rest_route( 'jobs/v1', 'webhook',
      array(
        'methods' => 'POST, GET',
        'callback' => array($this, 'run_webhook')
      )
    );
  }

  public function run_webhook($request) {
    // Assign header values as an array
    $header = $request->get_headers(wp_unslash($_SERVER));
    
    // Save results for testing
    //file_put_contents(wp_upload_dir()['basedir'] . '/smartrecruiters-webhook.txt', json_encode($header));

    // Return secret to active subscription
    if (isset($header['x_hook_secret'])) {
      // Return header for webhook activation handshake
      $secret = $header['x_hook_secret'][0];
      header('X-Hook-Secret: ' . $secret);
    }
    
    // Check if SmartRecruiter event is defined in header
    if (isset($header['event_name'])) {
      // Assign event name from header value
      $event = $header['event_name'][0];

      // Check if link key exists
      if (isset($header['link'])) {
        // Format json slashes to single slashes
        $link = $header['link'][0];
        $link = str_replace('\/', '/', $link);

        // Remove '<' and anything after '>' from "<https://example.com>; rel=self"
        if (preg_match('/<(.*?)>/', $link, $match) == 1) { $link = $match[1]; }

        // Get job id from link
        $offset = strpos($link, '/jobs/') + 6;
        $id = substr($link, $offset);

        // Remove extra routes if needed (ex: 'job.status.updated' includes '/status/history' at the end)
        if (strpos($id, '/') > 0) {
          $offset = strpos($id, '/');
          $id = substr($id, 0, $offset);
        }

        // Publish or update job page
        if ($event == 'job.created' || $event == 'job.updated' || $event == 'job.status.updated') {
          $_POST['id'] = $id;
          $this->idx_api->publish_job();
        }
      }
    }
  }

  public function get_ip_address(){
    foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
      if (array_key_exists($key, $_SERVER) === true){
        foreach (explode(',', $_SERVER[$key]) as $ip){
          $ip = trim($ip);

          if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
            return $ip;
          }
        }
      }
    }
  }

  public function compareById($a, $b) {
    return $a->distance - $b->distance;
  }

  public function search_jobs() {
    $offset = intval($_POST['offset'] ?? 0);
    $limit = intval($_POST['limit'] ?? 10);
    $keywords = $_POST['keywords'];
    $area = $_POST['area']; // ex: City, State, Zip
    $distance = intval($_POST['distance']);
    $department = $_POST['department'];
    $community = $_POST['community'];
    $full_time = $_POST['full_time'];
    $part_time = $_POST['part_time'];
    $departments = [];
    $userIP  ='24.251.210.84';

    // Local testing
    if($userIP == '172.18.0.1'){
      $userIP = '24.251.210.84';
    }
    $userDetails = json_decode($this->get_ip_details($userIP),true);
    $userLat = floatval($userDetails['latitude']);
    $userLong = floatval($userDetails['longitude']);

    // Query all posts (necessary for community/department filters)
    $args = array(
      'post_type'      => array('job'),
      'post_status'    => 'publish',
      'posts_per_page' => -1,
      'offset'         => 0,
      'meta_query'     => array()
    );

    $queryComunity = new WP_Query($args);

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

    // Search by communities
    foreach ($queryComunity->posts as $key => $post) {
      $community_name = get_post_meta($post->ID, 'idx_smartrecruiters_location_name', true);
      if (!empty($community_name)) $communities[$community_name] = $community_name; // Map community keys/values 
    }
    $all_comunities = "";
    $communities_array = explode(',', $community);

    foreach ($communities_array as $key => $single_community) {
      if($single_community != 'all:checked')
      {
        $community_values = explode(':', $single_community);
        $community_name = $community_values[0];
            
        // Only search if checked
        if (isset($community_values[1])) {
          $community_checked = $community_values[1] == 'checked';
          $communities[$community_name] = $community_name.":checked";

          $args['meta_query'][] = array(
            'relation'  => 'OR',
            array('key' => 'idx_smartrecruiters_location_name', 'value' => $community_name, 'compare' => 'LIKE')
          );
        }
      }
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
        'limit'        => $limit
      )
    );


    // Loop through each post result
    $index = 0;
    $totalPosts = 0;
    $posts = array();    
    foreach ($query->posts as $key => $post) {
      $latitude = floatval(get_post_meta($post->ID, 'idx_smartrecruiters_latitude', true));
      $longitude = floatval(get_post_meta($post->ID, 'idx_smartrecruiters_longitude', true));
      $distanceBetween = round($this->calculate_distance_between_lat_long($userLat, $userLong,$latitude,$longitude));
      if ($distanceBetween <= $distance) {
          $post->distance = $distanceBetween;
          $posts[] = $post; 
          $totalPosts++;
      }
    }

    // Sorts the array by distance
    usort($posts, array($this, 'compareById'));
    foreach ($posts as $key => $post) {
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
      $latitude = floatval(get_post_meta($post->ID, 'idx_smartrecruiters_latitude', true));
      $longitude = floatval(get_post_meta($post->ID, 'idx_smartrecruiters_longitude', true));
      $distanceBetween = apply_filters( 'distance', $post->distance );
      if (!empty($hourly_min) && empty($hourly_max)) $hourly = $hourly_min;
      else if (empty($hourly_min) && !empty($hourly_max)) $hourly = $hourly_max;
      else if (!empty($hourly_min) && !empty($hourly_max)) $hourly = $hourly_min . ' - ' . $hourly_max;
      else $hourly = 'n/a';

      // Only add jobs within offset + limit range
      if (intval($key) >= intval($offset) && intval($key) < (intval($offset) + intval($limit))) {        
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
          'hourly'       => $hourly,
          'distance'     => $distanceBetween
        );
        $results['jobs'][$index] = $job;
        $index++;
        
      }
  
      // Add all available communities/departments
      // if (!empty($community_name)) $communities[$community_name] = $community_name; // Map community keys/values
      if (!empty($department_name)) $departments[$department_name] = $department_name; // Map department keys/values
    }

    $results['pagination']['totalFound'] = $totalPosts;
    
    // Set a list of community array values
    $results['communities'] = array_values($communities);
    $results['departments'] = array_values($departments);
    $results['community'] = $community;
    $results['department'] = $department;

    // Return posts as JSON data
    wp_send_json_success($results);
  }
}
