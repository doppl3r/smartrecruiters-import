<?php

class SmartRecruiters_Admin {
  protected $api;

  public function __construct() {
    // Require API class helper
		require_once __DIR__ . '/../class-api.php';
		$this->api = new SmartRecruiters_API();

    add_action('admin_menu', array($this, 'add_menu_page')); /* Add admin menu and page */
    add_action('admin_enqueue_scripts', array($this, 'add_assets'));

    // Register AJAX functions from CMS admin front-end
    add_action('wp_ajax_import_jobs', array($this->api, 'import_jobs'));
    add_action('wp_ajax_publish_job', array($this->api, 'publish_job'));
    add_action('wp_ajax_trash_job', array($this->api, 'trash_job'));
    add_action('wp_ajax_subscribe_to_webhook', array($this, 'subscribe_to_webhook'));
    add_action('wp_ajax_get_webhook_subscriptions', array($this, 'get_webhook_subscriptions'));
    add_action('wp_ajax_delete_webhook_subscription', array($this, 'delete_webhook_subscription'));
    add_action('wp_ajax_get_webhook_notifications', array($this, 'get_webhook_notifications'));
  }

  public function add_menu_page() {
    // Only show for admin users
    if (current_user_can('administrator')) {
      // Top level menu
      $page_title = 'SmartRecruiters';
      $menu_title = 'SmartRecruiters';
      $capability = 'edit_others_posts';
      $menu_slug = 'smartrecruiters';
      $parent_slug = $menu_slug;
      $function = array($this, 'render_import_page');
      $icon_url = 'dashicons-cloud-saved';
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
      $page_title = 'Job Pages';
      $menu_title = 'Job Pages';
      $menu_slug = 'edit.php?post_type=job';
      add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug);

      // List
      $page_title = 'Job Categories';
      $menu_title = 'Job Categories';
      $menu_slug = 'edit-tags.php?taxonomy=job_categories&post_type=job';
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

  public function subscribe_to_webhook() {
    $curl = curl_init();
    $response = array();
    $url = get_site_url();
    $callback = $url . '/wp-json/jobs/v1/webhook/';
    $token = $this->api->fetch_token();

    // Configure curl array
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.smartrecruiters.com/webhooks-api/v201907/subscriptions',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => json_encode([
        'callbackUrl' => $callback,
        'events' => [
          'job.created',
          'job.updated',
          'job.status.updated'
        ]
      ]),
      CURLOPT_HTTPHEADER => array(
        'accept: application/json',
        'authorization: Bearer ' . $token,
        'content-type: application/json',
      ),
    ));
    $response = json_decode(curl_exec($curl), true);
    curl_close($curl);

    // Store webhook subscription id
    update_option('smartrecruiters_webhook_subscription_id', $response['id']);

    // Activate the webhook subscription (inactive by default)
    $response['secretkey'] = $this->generate_webhook_secretkey($response['id']);
    $response['subscription'] = $this->activate_webhook_subscription($response['id']);

    // Return response if token exists
    wp_send_json_success($response);
  }

  public function generate_webhook_secretkey($id) {
    $curl = curl_init();
    $response = array();
    $token = $this->api->fetch_token();

    // Configure curl array
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.smartrecruiters.com/webhooks-api/v201907/subscriptions/' . $id . '/secret-key',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_HTTPHEADER => array(
        'accept: application/json',
        'authorization: Bearer ' . $token
      ),
    ));
    $response = json_decode(curl_exec($curl), true);
    curl_close($curl);

    // Return response if token exists
    return $response;
  }

  public function activate_webhook_subscription($id) {
    $curl = curl_init();
    $response = array();
    $token = $this->api->fetch_token();

    // Configure curl array
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.smartrecruiters.com/webhooks-api/v201907/subscriptions/' . $id . '/activation',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'PUT',
      CURLOPT_HTTPHEADER => array(
        'accept: application/json',
        'authorization: Bearer ' . $token
      ),
    ));
    $response = json_decode(curl_exec($curl), true);
    $err = curl_error($curl);
    if ($err) $response = $err;
    curl_close($curl);

    // Return response if token exists
    return $response;
  }

  public function delete_webhook_subscription() {
    $curl = curl_init();
    $response = array();
    $token = $this->api->fetch_token();
    $id = $_POST['id'];

    // Configure curl array
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.smartrecruiters.com/webhooks-api/v201907/subscriptions/' . $id,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'DELETE',
      CURLOPT_HTTPHEADER => array(
        'accept: application/json',
        'authorization: Bearer ' . $token
      ),
    ));
    $response = json_decode(curl_exec($curl), true);
    curl_close($curl);

    // Return response if token exists
    wp_send_json_success($response);
  }

  public function get_webhook_subscriptions() {
    $curl = curl_init();
    $response = array();
    $token = $this->api->fetch_token();

    // Configure curl array
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.smartrecruiters.com/webhooks-api/v201907/subscriptions',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'authorization: Bearer ' . $token,
        'Content-Type: application/json',
      ),
    ));
    $response = json_decode(curl_exec($curl), true);
    curl_close($curl);

    // Return response if token exists
    wp_send_json_success($response);
  }

  public function get_webhook_notifications() {
    $curl = curl_init();
    $response = array();
    $token = $this->api->fetch_token();
    $subscription_id = get_option('smartrecruiters_webhook_subscription_id');

    // Configure curl array
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.smartrecruiters.com/webhooks-api/v201907/subscriptions/' . $subscription_id . '/callbacks-log?limit=100',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'accept: application/json',
        'authorization: Bearer ' . $token
      ),
    ));
    $response = json_decode(curl_exec($curl), true);
    $response['id'] = $subscription_id;
    curl_close($curl);

    // Return response if token exists
    wp_send_json_success($response);
  }
}