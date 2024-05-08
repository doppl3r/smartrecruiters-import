<?php
  $client_id = get_option('smartrecruiters_client_id');
  $client_secret = get_option('smartrecruiters_client_secret');
?>
<div class="sr-admin">
  <?php require_once 'header.php' ?>
  <div class="sr-content">
    <h2>Import Jobs</h2>
    <p>The SmartRecruiters plugin is designed to make it easy to import SmartRecruiters jobs into WordPress. Importing may take 30-60 minutes, so please wait until the import is finished.</p>
    
    <!-- Import actions -->
    <div class="sr-actions">
      <?php if (empty($client_id) || empty($client_secret)) : ?>
        <!-- Redirect new users to the settings page -->
        <a href="/wp-admin/admin.php?page=smartrecruiters-settings" class="sr-btn">Update settings <span class="dashicons-before dashicons-admin-tools"></span></a>
      <?php else : ?>
        <a class="sr-btn ready" action="import-all" data-tip="Import all jobs">Import All <span class="dashicons-before dashicons-database"></span></a>
        <a class="sr-btn ready" action="import-recent" data-tip="Import jobs that were updated within the last week (faster)">Import Recent <span class="dashicons-before dashicons-clock"></span></a>
        <a class="sr-btn ready" action="import-single" data-tip="Import a single job">Import Single<span class="dashicons-before dashicons-location"></span></a>
        <span class="sr-time">00:00:00</span>
      <?php endif ?>
    </div>
      
    <!-- Progress bar -->
    <div class="sr-progress" data-text="Waiting for import to start...">
      <div class="sr-progress-bar" data-percent="0"></div>
    </div>

    <h3>SmartRecruiters Jobs</h3>
    <p>The following jobs will be published to your WordPress website as individual job pages.</p>

    <!-- Results -->
    <div class="sr-results">
      <div class="sr-legend">
        <label><span class="sr-status queued"></span> Queued</label>
        <label><span class="sr-status published"></span> Published</label>
        <label><span class="sr-status updated"></span> Updated</label>
        <label><span class="sr-status trashed"></span> Trashed</label>
        <label><span class="sr-status error"></span> Error</label>
      </div>
      <div class="sr-list"></div>
    </div>
  </div>
</div>