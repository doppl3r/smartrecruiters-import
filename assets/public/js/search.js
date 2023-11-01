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
  filters.find('.idx-sr-reset-filter').on('click', function(e) {
    e.preventDefault();
    resetFilter();
    form.submit();
  });

  filters.find('input, select').on('change', function(e) {
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
      var community = filters.find('select[name="community"]').val();
      var full_time = filters.find('input[name="full-time"]').is(":checked");
      var part_time = filters.find('input[name="part-time"]').is(":checked");
  
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
    pagination.empty();
    var jobs = data['jobs'];
    var offset = data['pagination']['offset'];
    var limit = data['pagination']['limit'];
    var total = data['pagination']['totalFound'];

    // Append total
    results.append('<div class="idx-sr-total">' + total + ' jobs found.</div>');

    // Append jobs
    jobs.forEach(function(job) {
      var row =
      '<div class="idx-sr-job">' +
        '<h3 class="title"><a href="' + job['link'] + '">' + job['title'] + '</a></h3>' +
        '<span class="subtitle">' + job['city'] + ', ' + job['region_code'] + ' - ' + job['community'] + '</span>' +
        '<p class="description">' + job['description'] + '</p>' +
        '<div class="idx-sr-details">' +
            '<span class="address">' + job['address'] + ', ' + job['city'] + ', ' + job['region_code'] + ' ' + job['zip'] + ', ' + job['country_code'].toUpperCase() + '</span>' +
            '<span class="employment">' + job['employment'] + '</span>' +
            '<span class="hourly-rate">Hourly Rate: ' + job['hourly'] + '</span>' +
            '<a class="idx-sr-btn" href="' + job['link'] + '">Apply Now</a>' +
        '</div>' +
      '</div>';
      results.append(row);
    });

    // Append pagination links
    pagination.append('<span>Page ' + ((offset / limit) + 1) + ' of ' + Math.ceil(total / limit)) + '</span>';
    for (var i = 0; i < (total / limit); i++) {
      var index = (offset / limit);
      var current = (i == index) ? 'current' : '';
      var link = $('<a href="#" class="item ' + current + '" data-offset="' + (i * limit)  + '">' + (i + 1) + '</a>');
      link.on('click', function(e) {
        e.preventDefault();
        var item_offset = parseInt($(this).attr('data-offset'));
        var top = $('.idx-sr-search').offset().top - $('header').height() - $('#wpadminbar').height();
        searchJobs(item_offset, limit);
        $('html, body').animate({ scrollTop: top }, 1000);
      })
      pagination.append(link);
    }
  }

  function updateFilters(data) {
    var department = filters.find('select[name="department"]');
    var community = filters.find('select[name="community"]');

    // Update options from search results
    department.children().not(':first').remove();
    community.children().not(':first').remove();
    data['departments'].sort().forEach(function(option) { department.append('<option value="' + option + '">' + option + '</option>'); });
    data['communities'].sort().forEach(function(option) { community.append('<option value="' + option + '">' + option + '</option>'); });
    department.find('option[value="' + data['department'] + '"]').prop('selected', true);
    community.find('option[value="' + data['community'] + '"]').prop('selected', true);
  }

  function resetFilter() {
    // Reset filter input values
    filters.find('select[name="department"]').val(filters.find('select[name="department"] option:first').val());
    filters.find('select[name="community"]').val(filters.find('select[name="community"] option:first').val());
    filters.find('input[name="full-time"]').prop('checked', true);
    filters.find('input[name="part-time"]').prop('checked', true);
  }

  // Load all jobs immediately
  searchJobs();
});