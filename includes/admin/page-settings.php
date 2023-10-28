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

  // Check rewrite value
  $default_slug = 'careers';
  if (isset($_POST['rewrite'])) {
    $rewrite = $_POST['rewrite'];
    if (empty($rewrite)) $rewrite = $default_slug;
    update_option('smartrecruiters_rewrite', $rewrite);
    flush_rewrite_rules();
  }
  else {
    // Get stored rewrite rule
    $rewrite = get_option('smartrecruiters_rewrite', 'careers');
    if (empty($rewrite)) $rewrite = $default_slug;
  }
?>
<div class="idx-sr-admin">
  <?php require_once 'header.php' ?>
  <div class="idx-sr-content">
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
        <label for="rewrite">Post Type Slug</label>
        <input id="rewrite" name="rewrite" value="<?php echo $rewrite; ?>" type="text">
      </div>
      <div class="row">
        <button class="idx-sr-btn" name="save_settings">Save</button>
      </div>
    </form>
  </div>
</div>