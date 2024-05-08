<?php

class SmartRecruiters_API {
  public function __construct() {

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
        $expires_in = 1799; // SmartRecruiters tokens expire in 30 minutes
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
    // Get a list of all jobs from SmartRecruiters API
    $jobs = $this->get_job_list();

    // Return JSON results
    if (isset($jobs['content'])) wp_send_json_success($jobs);
    else wp_send_json_error($jobs);
  }

  public function publish_job() {
    $job = $this->get_job_details();
    $status = 'queued';

    // Default post data
    $post_arr = array(
      'post_status'  => 'publish',
      'post_type'    => 'job',
      'post_title'   => $job['title']
    );

    // Update post content from SmartRecruiters "jobAd sections
    $content = '';
    foreach ($job['jobAd']['sections'] as $index => $section) {
      if (isset($section['title'])) $content .= '<h3>' . $section['title'] . '</h3>';
      if (isset($section['text'])) $content .= $section['text'];
    }
    $post_arr['post_content'] = $content;
    $post_arr['post_excerpt'] = trim(strip_tags(str_replace('<', ' <', $job['jobAd']['sections']['jobDescription']['text'])));

    // Update post meta from SmartRecruiters data
    $meta_arr = array(
      'smartrecruiters_id' => $job['id'],
      'smartrecruiters_status' => $job['status'], // Ex: SOURCING, CANCELLED etc.
      'smartrecruiters_posting_status' => $job['postingStatus'], // Ex: PUBLIC, NOT_PUBLISHED etc.
      'smartrecruiters_title' => $job['title'],
      'smartrecruiters_country' => $job['location']['country'],
      'smartrecruiters_country_code' => $job['location']['countryCode'],
      'smartrecruiters_city' => $job['location']['city'],
      'smartrecruiters_region' => $job['location']['region'],
      'smartrecruiters_region_code' => $job['location']['regionCode'],
      'smartrecruiters_postal_code' => $job['location']['postalCode'],
      'smartrecruiters_address' => $job['location']['address'],
      'smartrecruiters_latitude' => $job['location']['latitude'],
      'smartrecruiters_longitude' => $job['location']['longitude'],
      'smartrecruiters_apply' => $job['actions']['applyOnWeb']['url'],
      'smartrecruiters_department' => $job['department']['label'],
      'smartrecruiters_industry' => $job['industry']['label'],
      'smartrecruiters_employment_type' => $job['typeOfEmployment']['label'],
      'smartrecruiters_experience_level' => $job['experienceLevel']['label'],
      'smartrecruiters_created_on' => $job['createdOn'],
    );

    // Convert Smartrecruiters 'properties' array into post meta
    foreach ($job['properties'] as $index => $property) {
      $key = preg_replace('/[^a-zA-Z0-9]+/', '_', $property['label']);
      $key = strtolower($key);
      $key = trim($key, '_');
      $meta_arr['smartrecruiters_' . $key] = $property['value']['label']; 
    }

    // Check if posts with matching job id exist
    $posts = get_posts(array(
      'numberposts'   => 1,
      'post_type'     => 'job',
      'post_status'   => array('publish', 'trash'), // Query published or trashed posts
      'meta_key'      => 'smartrecruiters_id',
      'meta_value'    => $_POST['id']
    ));

    // Add job if it does not exist
    if (count($posts) == 0) {
      // Add new post if title is not empty
      if (!empty($job['title'])) {
        // Insert post and update status
        $status = 'published';
        $post_id = wp_insert_post($post_arr);
        $posts = array(get_post($post_id));
  
        // Add each post meta
        foreach ($meta_arr as $key => $meta) {
          add_post_meta($post_id, $key, $meta);
        }
      }
    }
    else {
      // Update existing post
      $status = 'updated';
      $post_id = $posts[0]->ID;
      $post_arr['ID'] = $post_id;
      wp_update_post($post_arr);
      
      // Update each post meta
      foreach ($meta_arr as $key => $meta) {
        update_post_meta($post_id, $key, $meta);
      }
    }

    // Trash job post if postingStatus is not "PUBLIC"
    if ($job['postingStatus'] != 'PUBLIC') {
      $this->trash_job();
    }

    // Set post taxonomy tag from SmartRecruiters job location
    $taxonomy = 'job_categories';
    $taxonomy_term = $meta_arr['smartrecruiters_location_name'];
    wp_set_post_terms($post_id, array($taxonomy_term), $taxonomy);

    // Send results back as json data
    $job['link'] = get_permalink($post_id);
    if (isset($job['id'])) wp_send_json_success(array('status' => $status, 'job' => $job));
    else wp_send_json_error(array('status' => 'error', 'job' => $job));
  }

  public function trash_job() {
    $post_id = 0;
    $job_id = $_POST['id'];
    
    // Check if posts with matching job id exist
    $posts = get_posts(array(
      'numberposts'   => 1,
      'post_type'     => 'job',
      'post_status'   => array('publish'), // Only query 'public' posts
      'meta_key'      => 'smartrecruiters_id',
      'meta_value'    => $job_id
    ));

    // Safely check if post exists before trashing
    if (count($posts) > 0) {
      $post_id = $posts[0]->ID;
      wp_trash_post($post_id);
    }

    // Send results back as json data
    wp_send_json_success(array(
      'status' => 'trashed',
      'job' => array(
        'id' => $job_id,
        'link' => get_permalink($post_id)
      )
    ));
  }

  public function get_job_list() {
    // Get parameters with defaults
    $offset = intval($_POST['offset'] ?? 0);
    $limit = intval($_POST['limit'] ?? 100);
    $updatedAfter = $_POST['updatedAfter'];

    // Resolve Smartrecruiters limit (100)
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