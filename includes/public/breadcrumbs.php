<div class="idx-sr-breadcrumbs">
  <?php
    // Breadcrumbs
    global $post;
    $uri = $_SERVER['REQUEST_URI'];
    $uri = rtrim($uri,'/');
    $links = explode('/', $uri);
    $href = '';

    // Loop through each part of the url
    foreach ($links as $index => $link) {
      $text = str_replace('-', ' ', $link); // Convert dashes to spaces
      $text = ucwords($text); // Uppercase each word
      $href .= $link . '/';
      if ($index == 0) $text = 'Home';
      else echo '<span class="separator">></span>'; // Prepend separator
      echo '<a href="' . $href . '">' . $text . '</a>';
    }
  ?>
</div>