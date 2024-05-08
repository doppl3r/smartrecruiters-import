// Global JS code
jQuery(document).ready(function($) {
  var form = $('#search-jobs');
  var filters = $('#search-filters');
  var results = $('#search-results');
  var pagination = $('#search-pagination');
  
  // Add form submit listener
  form.on('submit', function(e) {
    e.preventDefault();

    // TODO: Search by pagination
    searchJobs(0, 10);
  });

  // Add filter reset action
  filters.find('.sr-reset-filter').on('click', function(e) {
    e.preventDefault();
    resetFilter();
    form.submit();
  });

  // Add filter change listener
  $(document).on('change', 'input, select', function(e) {
    form.submit();
  });
  
  function searchJobs(offset = 0, limit = 10) {
    // Only search if form is not loading
    if (form.hasClass('loading') == false) {
      // Prevent multiple submissions
      form.addClass('loading');
      results.addClass('loading');

      // Get input variables
      var keywords = form.find('input[name="keywords"]').val();
      var area = form.find('input[name="area"]').val();
      var distance = form.find('select[name="distance"]').val();
      var department = filters.find('select[name="department"]').val();
      var full_time = filters.find('input[name="full-time"]').is(":checked");
      var part_time = filters.find('input[name="part-time"]').is(":checked");
      var community = '';
      
      // Populate array of values into community array
      var community_checkboxes = filters.find('#sr-community input[type="checkbox"]');
      var community_array = [];
      community_checkboxes.each(function(index, checkbox) {
        if (index > -1) {
          var checked = (checkbox.checked) ? ':checked' : '';
          community_array.push(checkbox.value + checked);
        }
      });

      // Create string from community array
      community = community_array.join(',');

      console.log(community)

      // Request jobs from server
      $.post(admin.ajax_url, { action: 'search_jobs', offset: offset, limit: limit, keywords: keywords, area: area, distance: distance, department: department, community: community, full_time: full_time, part_time: part_time },
        function(response) {
          console.log(response);
          // Render results
          var data = response['data'];
          updateResults(data);
          updateFilters(data);

          // Remove loading styling
          form.removeClass('loading'); // Ready!
          results.removeClass('loading'); // Ready!
        }
      );
    }
  }

  function updateResults(data) {
    results.empty();
    var jobs = data['jobs'];
    var total = data['pagination']['totalFound'];

    // Append total
    results.append('<div class="sr-total">' + total + ' jobs found.</div>');

    // Append jobs
    jobs.forEach(function(job) {
      var row =
      '<div class="sr-job">' +
        '<h3 class="title"><a href="' + job['link'] + '">' + job['title'] + '</a></h3>' +
        '<span class="subtitle">' + job['city'] + ', ' + job['region_code'] + ' - ' + job['community'] + '</span>' +
        '<p class="description">' + job['description'] + '</p>' +
        '<div class="sr-details">' +
            '<span class="address">' + job['address'] + ', ' + job['city'] + ', ' + job['region_code'] + ' ' + job['zip'] + ', ' + job['country_code'].toUpperCase()+ ', ' + job['distance']+' Miles away ' + '</span>' +
            '<span class="employment">' + job['employment'] + '</span>' +
            '<span class="hourly-rate">Hourly Rate: ' + job['hourly'] + '</span>' +
            '<a class="sr-btn" href="' + job['link'] + '">Apply Now</a>' +
        '</div>' +
      '</div>';
      results.append(row);
    });

    // Append pagination links
    updatePagination(data);
  }

  function updatePagination(data) {
    var offset = data['pagination']['offset'];
    var limit = data['pagination']['limit'];
    var total = data['pagination']['totalFound'];
    var length = Math.ceil(total / limit);
    var range = 4;

    pagination.empty();
    pagination.append('<span class="label">Page ' + ((offset / limit) + 1) + ' of ' + Math.ceil(length)) + '</span>';
    for (var i = 0; i < length; i++) {
      var index = (offset / limit);
      var current = (i == index) ? 'current' : '';
      var is_first = (i == 0);
      var is_second = (i == 1);
      var is_last = (i == length - 1);
      var is_second_to_last = (i == length - 2);
      var in_range = (i >= index - (range - 1) && i < index + range);

      // Only render items within range (or first & last)
      if ((is_first || is_last) || (in_range)) {
        var link = $('<a href="#" class="item ' + current + '" data-offset="' + (i * limit)  + '">' + (i + 1) + '</a>');
        link.on('click', function(e) {
          e.preventDefault();
          var item_offset = parseInt($(this).attr('data-offset'));
          var top = $('.sr-search').offset().top - $('header').height() - $('#wpadminbar').height();
          searchJobs(item_offset, limit);
          $('html, body').animate({ scrollTop: top }, 1000);
        })
        pagination.append(link);
      }
      else {
        // Add ... for items out of range
        if (is_second || is_second_to_last) {
          pagination.append('<span class="separator">...</span>');
        }
      }
    }
  }

  function updateFilters(data) {
    var department = filters.find('select[name="department"]');
    var community = filters.find('#sr-community');

    // Update options from search results
    department.children().not(':first').remove();
    data['departments'].sort().forEach(function(option) {
      var text = option;
      if (text.indexOf(' - ') > 0) text = text.substring(text.indexOf(' - ') + 3); // Remove "# - " from department option
      department.append('<option value="' + option + '">' + text + '</option>');
    });
    department.find('option[value="' + data['department'] + '"]').prop('selected', true);
    
    // Populate and update checkbox values
    community.children().not(':first').remove();
    data['communities'].sort().forEach(
      function(checkbox) {
        var i = checkbox.indexOf(':checked');
        var value = checkbox;
        var checked = '';

        // Set checked attribute if included in value
        if (i > -1) {
          value = value.substring(0, i);
          checked = 'checked';
        }

        // Append group of input/label (and set "checked" value)
        community.append(
          '<div class="group">' +
            '<input type="checkbox" id="' + value + '" name="' + value + '" value="' + value + '" ' + checked + '>' + 
            '<label for="' + value + '"> ' + value + '</label>' +
          '</div>'
        );
      }
    );
  }

  function resetFilter() {
    // Reset filter input values
    filters.find('select[name="department"]').val(filters.find('select[name="department"] option:first').val());
    filters.find('#sr-community .group:first-of-type input').prop('checked', true);
    filters.find('input[name="full-time"]').prop('checked', true);
    filters.find('input[name="part-time"]').prop('checked', true);
  }

  // Load all jobs immediately
  searchJobs();
});