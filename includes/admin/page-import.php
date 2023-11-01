<?php
  $client_id = get_option('smartrecruiters_client_id');
  $client_secret = get_option('smartrecruiters_client_secret');
?>
<div class="idx-sr-admin">
  <?php require_once 'header.php' ?>
  <div class="idx-sr-content">
    <h2>Import Jobs</h2>
    <p>The IDX SmartRecruiters plugin is designed to make it easy to import SmartRecruiters jobs into WordPress. Importing may take 30-60 minutes, so please wait until the import is finished.</p>
    
    <!-- Import actions -->
    <div class="idx-sr-actions">
      <?php if (empty($client_id) || empty($client_secret)) : ?>
        <!-- Redirect new users to the settings page -->
        <a href="/wp-admin/admin.php?page=idx-smartrecruiters-settings" class="idx-sr-btn">Update settings <span class="dashicons-before dashicons-admin-tools"></span></a>
      <?php else : ?>
        <a class="idx-sr-btn ready" action="import-all" data-tip="Import all jobs">Import All <span class="dashicons-before dashicons-database"></span></a>
        <a class="idx-sr-btn ready" action="import-recent" data-tip="Import jobs that were updated within the last week (faster)">Import Recent <span class="dashicons-before dashicons-clock"></span></a>
        <span class="idx-sr-time">00:00:00</span>
      <?php endif ?>
    </div>
      
    <!-- Progress bar -->
    <div class="idx-sr-progress" data-text="Waiting for import to start...">
      <div class="idx-sr-progress-bar" data-percent="0"></div>
    </div>

    <h3>SmartRecruiters Jobs</h3>
    <p>The following jobs will be published to your WordPress website as individual job pages.</p>

    <!-- Results -->
    <div class="idx-sr-results">
      <div class="idx-sr-legend">
        <label><span class="idx-sr-status queued"></span> Queued</label>
        <label><span class="idx-sr-status published"></span> Published</label>
        <label><span class="idx-sr-status updated"></span> Updated</label>
        <label><span class="idx-sr-status error"></span> Error</label>
      </div>
      <div class="idx-sr-list"></div>
    </div>
  </div>
</div>