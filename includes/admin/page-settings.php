<?php
  // Check if form was submitted
  if (isset($_POST['client_id']) || isset($_POST['client_secret'])) {
    if (isset($_POST['client_id'])) $client_id = $_POST['client_id'];
    if (isset($_POST['client_secret'])) $client_secret = $_POST['client_secret'];

    update_option('smartrecruiters_client_id', $client_id);
    update_option('smartrecruiters_client_secret', $client_secret);
  }
  else {
    // Populate existing SmartRecruiters options
    $client_id = get_option('smartrecruiters_client_id');
    $client_secret = get_option('smartrecruiters_client_secret');
  }

  // Check if form was saved
  if (isset($_POST['rewrite'])) {
    // Force new token on save
    unset($_COOKIE['smartrecruiters_token']); 
    setcookie('smartrecruiters_token', '', -1, '/'); 

    // Update rewrite rules and flush url cache
    $rewrite = trim($_POST['rewrite'], '/');
    if (empty($rewrite)) $rewrite = 'jobs';
    $rewrite = trim($rewrite, '/');
    update_option('smartrecruiters_rewrite', $rewrite);
    flush_rewrite_rules();
  }
  else {
    // Get stored rewrite rule
    $rewrite = get_option('smartrecruiters_rewrite', 'jobs');
    if (empty($rewrite)) $rewrite = 'jobs';
  }

  // Fetch token
  $token = $this->idx_api->fetch_token();
?>
<div class="idx-sr-admin">
  <?php require_once 'header.php' ?>
  <div class="idx-sr-content">
    <div class="row">
      <div class="col">
        <h2>Settings</h2>
        <p>Update IDX SmartRecruiters API settings.</p>
        <form class="idx-sr-settings" action="" method="post">
          <div class="row">
            <label for="client_id">Client ID</label>
            <input id="client_id" name="client_id" value="<?php echo $client_id; ?>" type="text">
          </div>
          <div class="row">
            <label for="client_secret">Client Secret</label>
            <input id="client_secret" name="client_secret" value="<?php echo $client_secret; ?>" type="text">
          </div>
          <div class="row">
            <label for="rewrite">Parent Slug (ex: jobs/search)</label>
            <input id="rewrite" name="rewrite" value="<?php echo $rewrite; ?>" type="text">
          </div>
          <div class="row">
            <label for="token">Session Token (expires every 30 minutes)</label>
            <input id="token" name="token" value="<?php echo $token; ?>" type="text" disabled>
          </div>
          <div class="row">
            <button class="idx-sr-btn" name="save_settings">Save</button>
          </div>
        </form>
      </div>
      <div class="col">
        <h2>Webhook Subscriptions</h2>
        <p>Update Webhook subscriptions.</p>
        <a href="#" class="idx-sr-btn subscribe-to-webhook">Add New</a>
        <!-- Loaded using scripts.js AJAX and class-admin.php -->
        <div class="webhook-subscriptions loading"></div>
        <h2>Webhook Notifications</h2>
        <p>View recent notification.</p>
        <a href="#" class="idx-sr-btn refresh-notifications">Refresh</a>
        <div class="webhook-notifications loading"></div>
      </div>
    </div>
  </div>
</div>