// Global JS code
jQuery(document).ready(function($) {
  var jobs = [];
  var trash = [];
  var list = $('.idx-sr-list');
  var start; // Elapsed time
  var timer;

  // Add click event for importing jobs
  $('.idx-sr-btn[action]').on('click', function(e) {
    e.preventDefault();
    var button = $(this);
    var action = button.attr('action');
    var date = new Date();

    // Perform actions
    if (button.hasClass('disabled') == false) {
      $('.idx-sr-btn[action]').addClass('disabled'); // Disable all buttons
      jobs = []; // Empty jobs array
      list.empty(); // Empty job HTML list
      updateProgressBar(0, 1, 'Connecting to SmartRecruiters...');
      startTimer();

      // Check the button action
      if (action == 'import-all') {
        // Import all jobs
        importJobs(0, 100);
      }
      else if (action == 'import-recent') {
        //date.setMonth(date.getMonth() - 1); // Last month
        date.setDate(date.getDate() - 7); // Last week
        importJobs(0, 100, date.toISOString());
      }
    }
  });

  function importJobs(offset = 0, limit = 100, updatedAfter) {
    $.post(ajaxurl, { action: 'import_jobs', offset: offset, limit: limit, updatedAfter: updatedAfter }, function(response) { 
      if (response.success == true) {
        var data = response['data'];
        var total = data['totalFound'];
        
        // Check if there are more jobs to search
        if (offset < total) {
          console.log(data);
          data['content'].forEach(function(job) { addJobToList(job); });
          updateProgressBar(offset + limit, total, 'Step 1 of 3: Downloading ' + (offset + limit) + ' of ' + total + ' jobs...');
          importJobs(offset + limit, limit, updatedAfter); // Recursively request more jobs
        }
        else {
          // Finish and begin publishing
          updateProgressBar(0, jobs.length, 'Download complete!');
          publishJobs();
        }
      }
      else {
        // Log error
        console.log(response);
      }
    });
  }

  function updateProgressBar(index = 0, length = 1, text = '') {
    var percent = (index / length) * 100;
    var progress = $('.idx-sr-progress');
    var bar = $('.idx-sr-progress-bar');
    if (percent > 100) percent = 100;
    progress.attr('data-text', text)
    bar.attr('data-percent', parseInt(percent));
    bar.width(percent + '%');
  }

  function addJobToList(job) {
    // Only add jobs with 'PUBLIC' or 'INTERNAL' posting status
    if (job['postingStatus'] == 'PUBLIC' || job['postingStatus'] == 'INTERNAL') {
      jobs.push(job);
      addRowToList(job['id'], '<span class="idx-sr-status queued"></span>' + '(' + jobs.length + ') ' + job['title']);
    }
    else {
      // Add to list of jobs to remove from WP
      trash.push(job);
    }
  }

  function addRowToList(id, text) {
    var row = '<a class="idx-sr-row" id="' + id + '">' + text + '</a>';
    list.prepend(row);
  }

  function publishJobs(index = 0) {
    // Update status if index is less than the number of jobs
    if (index < jobs.length) {
      var id = jobs[index]['id'];
      $.post(ajaxurl, { action: 'publish_job', id: id }, function(response) { 
        // Update the row status
        var data = response['data'];
        var row = $('#' + id);
        console.log(data);
        if (data['status']) row.find('.idx-sr-status').attr('class', 'idx-sr-status ' + data['status']);
        if (data['job']){
          row.attr('href', data['job']['link']);
          row.attr('target', '_blank');
        }
        updateProgressBar(index + 1, jobs.length, 'Step 2 of 3: Publishing ' + (index + 1) + ' of ' + jobs.length + ' job pages...');
        publishJobs(index + 1); // Recursively request next job
      });
    }
    else {
      // Finish job publications and trash old jobs
      updateProgressBar(0, 1, 'Cleaning up old jobs...');
      trashJobs();
    }
  }

  function trashJobs(index = 0) {
    // Trash jobs from SmartRecruiters that are not available anymore.
    if (index < trash.length) {
      var id = trash[index]['id'];
      $.post(ajaxurl, { action: 'trash_job', id: id }, function(response) {
        // Update the row status
        var data = response['data'];
        console.log(data);
        updateProgressBar(index + 1, trash.length, 'Step 3 of 3: Unpublishing ' + (index + 1) + ' of ' + trash.length + ' job pages...');
        trashJobs(index + 1); // Recursively request next job to trash
      });
    }
    else {
      // Finished
      updateProgressBar(0, 1, 'Finished!');
      $('.idx-sr-btn[action]').removeClass('disabled');
      stopTimer();
    }
  }

  function startTimer() {
    start = Date.now();
    timer = setInterval(function() {
      var time = (Date.now() - start) / 1000;
      var hours = parseInt(Math.floor(((time % 31536000) % 86400) / 3600), 10);
      var minutes = parseInt(Math.floor((((time % 31536000) % 86400) % 3600) / 60), 10);
      var seconds = parseInt((((time % 31536000) % 86400) % 3600) % 60, 10);
      $('.idx-sr-time').text(
        ((hours < 10) ? "0" + hours : hours) + ":" + 
        ((minutes < 10) ? "0" + minutes : minutes) + ":" + 
        ((seconds < 10) ? "0" + seconds : seconds)
      );
    }, 1000);
  }

  function stopTimer() {
    clearInterval(timer);
  }

  // Add subscription row delete event
  $(document).on('click', '.webhook-subscriptions .delete', function(e) {
    e.preventDefault();
    var button = $(this);
    var row = button.closest('.subscription');
    var subscriptions = $('.webhook-subscriptions');
    var subscription_id = button.attr('data-id');
    subscriptions.addClass('loading');
    $.post(ajaxurl, { action: 'delete_webhook_subscription', id: subscription_id }, function(response) {
      row.remove(); // Delete row if successful
      subscriptions.removeClass('loading');
    });
  });

  function get_webhook_subscriptions() {
    var subscriptions = $('.webhook-subscriptions');
    if (subscriptions.length > 0) {
      // Query webhook subscriptions from server
      $.post(ajaxurl, { action: 'get_webhook_subscriptions' }, function(response) {
        subscriptions.removeClass('loading');
        response.data.forEach(function(subscription) {
          var row = render_subscription_row(subscription);
          subscriptions.append(row);
        });
      });

      // Add event "Add New" webhook subscription event
      $(document).on('click', '.subscribe-to-webhook', function(e) {
        e.preventDefault();
        subscriptions.addClass('loading');
        $.post(ajaxurl, { action: 'subscribe_to_webhook' }, function(response) {
          var row = render_subscription_row(response.data);
          subscriptions.removeClass('loading');
          subscriptions.append(row)
        });
      });
    }
  }

  function render_subscription_row(subscription) {
    var row =
    '<div class="subscription">' +
      '<div class="id"><strong>subscription id: </strong>' + subscription['id'] + '</div>' +
      '<div class="url"><strong>callbackUrl: </strong>' + subscription['callbackUrl'] + '</div>' +
      '<div class="status"><strong>status: </strong>' + subscription['status'] + '</div>' +
      '<a href="#" data-id="' + subscription['id'] + '" class="btn delete"><span class="dashicons-before dashicons-trash"></span></a>'
    '<div>'
    return row;
  }

  // Add event "Refresh" webhook notifications event
  $(document).on('click', '.refresh-notifications', function(e) {
    e.preventDefault();
    get_webhook_notifications();
  });

  function get_webhook_notifications() {
    var notifications = $('.webhook-notifications');
    if (notifications.length > 0) {
      // Query webhook notifications from server
      notifications.addClass('loading');
      notifications.empty();
      $.post(ajaxurl, { action: 'get_webhook_notifications' }, function(response) {
        notifications.removeClass('loading');
        Object.keys(response.data).forEach(function(key) {
          // Only get notifications with number key
          if (isNaN(key) == false) {
            var notification = response.data[key];
            var row = render_notifications_row(notification);
            if (row) notifications.append(row);
          }
        });
      });
    }
  }

  function render_notifications_row(notification) {
    var timestamp = notification['timestamp'];
    var status = notification['status'];
    var headers = notification['requestDetails']['headers'];
    var body = notification['requestDetails']['body'];
    var event = headers['Event-name'];
    var row;

    if (body) {
      var jobId = JSON.parse(body)['id'];
      row =
        '<div class="notification">' +
          '<div class="id"><strong>job id: </strong>' + jobId + '</div>' +
          '<div class="timestamp"><strong>timestamp: </strong>' + timestamp + '</div>' +
          '<div class="event"><strong>event: </strong>' + event + '</div>' +
          '<div class="status"><strong>status: </strong>' + status + '</div>' +
        '<div>';
    }
    return row;
  }

  // Admin subscription page
  get_webhook_subscriptions();
  get_webhook_notifications();
});