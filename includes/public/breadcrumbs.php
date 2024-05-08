<div class="sr-breadcrumbs">
  <?php
    // Breadcrumbs
    global $post;

    // Set default values from options
    $default_slug = 'jobs';
		$slug = get_option('smartrecruiters_rewrite', $default_slug); // Option: 'jobs/search'
		$slug = empty($slug) ? $default_slug : $slug;
    $links = explode('/', '/' . $slug);
    $href = '';

    // Loop through each part of the slug (ex: "/jobs/search": Home > Jobs > Search)
    foreach ($links as $index => $link) {
      $text = str_replace('-', ' ', $link); // Convert dashes to spaces
      $text = ucwords($text); // Uppercase each word
      $href .= $link . '/';
      if ($index == 0) $text = 'Home';
      add_link($href, $text);
    }

    // Append taxonomy terms
    $terms =  get_the_terms($post->ID, 'job_categories');
    foreach ($terms as $term) {
      $href .= $term->slug . '/';
      $text = $term->name;
      add_link($href, $text);
    }

    // Append post slug
    $href .= $post->post_name;
    $text = $post->post_title;
    add_link($href, $text);

    // Render link and text
    function add_link($href, $text) {
      echo '<a href="' . $href . '">' . $text . '</a>';
    }
  ?>
</div>