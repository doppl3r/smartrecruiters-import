<!-- Global header -->
<?php get_header(); ?>

<?php
  // Get post content
  global $post;

  // Set variables from post/meta
  $id = get_post_meta($post->ID, 'idx_smartrecruiters_id', true);
  $title = get_the_title();
  $description = mb_strimwidth($post->post_excerpt, 0, 120, '...');
  $address = get_post_meta($post->ID, 'idx_smartrecruiters_address', true);
  $city = get_post_meta($post->ID, 'idx_smartrecruiters_city', true);
  $region_code = get_post_meta($post->ID, 'idx_smartrecruiters_region_code', true);
  $zip = get_post_meta($post->ID, 'idx_smartrecruiters_postal_code', true);
  $country = get_post_meta($post->ID, 'idx_smartrecruiters_country', true);
  $country_code = get_post_meta($post->ID, 'idx_smartrecruiters_country_code', true);
  $community = get_post_meta($post->ID, 'idx_smartrecruiters_location_name', true);
  $employment = get_post_meta($post->ID, 'idx_smartrecruiters_full_part_time', true);
  $hourly_min = get_post_meta($post->ID, 'idx_smartrecruiters_hourly_rate_minimum', true);
  $hourly_max = get_post_meta($post->ID, 'idx_smartrecruiters_hourly_rate_maximum', true);
  if (!empty($hourly_min) && empty($hourly_max)) $hourly = $hourly_min;
  else if (empty($hourly_min) && !empty($hourly_max)) $hourly = $hourly_max;
  else if (!empty($hourly_min) && !empty($hourly_max)) $hourly = $hourly_min . ' - ' . $hourly_max;
  else $hourly = 'n/a';
  $department_name = get_post_meta($post->ID, 'idx_smartrecruiters_department', true);
  $employment_type = get_post_meta($post->ID, 'idx_smartrecruiters_employment_type', true);
  $created_on = get_post_meta($post->ID, 'idx_smartrecruiters_created_on', true);
  $latitude = get_post_meta($post->ID, 'idx_smartrecruiters_latitude', true);
  $longitude = get_post_meta($post->ID, 'idx_smartrecruiters_longitude', true);
  $directions = 'https://www.google.com/maps?saddr=My+Location&daddr=' . $address . ', ' . $city . ', ' .$region_code . ' ' . $zip . ', ' . $country;
  $apply_url = get_post_meta($post->ID, 'idx_smartrecruiters_apply', true);
  
  // Post type slug
  $default_slug = 'jobs';
  $slug = get_option('smartrecruiters_rewrite', $default_slug); // Option: 'jobs/search'
  $slug = empty($slug) ? $default_slug : $slug;

  // Taxonomy terms
  $terms =  get_the_terms($post->ID, 'job_categories');
  $terms_slug = '';
  foreach ($terms as $term) {
    $terms_slug .= $term->slug . '/';
  }

  // Get a list of other communities
  $query = new WP_Query(array(
    'posts_per_page'  => 3,
    'post_type'       => 'job',
    'meta_key'        => 'idx_smartrecruiters_location_name',
    'meta_value'      => $community,
    'orderby'         => 'rand',
    'post__not_in'    => array($post->ID) // Exclude current post
  ));
  $other_communities = $query->posts;

  // Enqueue leaflet map scripts (required for the map)
  wp_enqueue_script('leaflet');
  wp_enqueue_style('leaflet');
  wp_localize_script('leaflet', 'path', MY_PLUGIN_DIR);
  wp_localize_script('leaflet', 'jobs',
    array(
      array(
        'title'      => $title,
        'geo'        => array($latitude, $longitude),
        'directions' => $directions
      )
    )
  );
?>

<!-- Body content -->
<div id="primary" class="content-area idx-sr-single">
  <main id="main" class="site-main pb-0">
    <div class="container">
      <div class="row">
        <div class="col">
          <?php require_once 'breadcrumbs.php'; ?>
          <?php
            // Load community logo by community name (ex: "Park Creek Place North Wales.jpg")
            global $wpdb;
            $attachments = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE post_title = '$community' AND post_type = 'attachment' ", OBJECT );
            if ($attachments) echo '<a href="/' . $slug . '/' . $terms_slug . '"><img class="idx-sr-logo" src="' . $attachments[0]->guid . '" alt="' . $community . '"></a>';
          ?>
          <h2><?php echo $title; ?></h2>
          <div class="idx-sr-details">
            <span class="address"><?php echo $address; ?>, <?php echo $city; ?>, <?php echo $region_code; ?> <?php echo $zip; ?>, <?php echo $country; ?></span>
            <span class="employment"><?php echo $employment; ?></span>
            <span class="hourly-rate">Hourly Rate: <?php echo $hourly; ?></span>
          </div>
        </div>
      </div>
      <div class="row py-5">
        <div class="col-md-8">
          <div class="idx-sr-content">
            <?php echo get_the_content(); ?>
            <h3>Job Location</h3>
            <div id="idx-sr-map" class="idx-sr-map"></div>
            <p>
              <a href="<?php echo $directions; ?>" target="_blank">Get Directions</a>
            </p>
            <?php
              // List all post meta for debugging
              if (is_user_logged_in()) {
                echo '<strong style="margin-bottom: 16px;">Debugger (only visible to admin users)</strong>';
                echo '<ul style="overflow-y: auto; max-height: 240px; background-color: #f1f1f1; list-style: none; padding: 12px;">';
                $meta = get_post_meta($post->ID);
                foreach($meta as $key => $value) {
                  if (str_contains($key, 'idx_')) {
                    echo '<li style="margin: 0;">' . $key . ': ' . $value[0] . '</li>';
                  }
                }
                echo '</ul>';
              }
            ?>
          </div>
        </div>
        <div class="col-md-4">
          <div class="sidebar">
            <h3>Get Started</h3>
            <p>Your job is just around the corner!</p>
            <div class="actions">
              <a class="btn btn-primary" href="<?php echo $apply_url; ?>" target="_blank">Apply Now</a>
              <a class="btn" href="#" target="_blank">Refer a Friend</a>
            </div>
            <h3>Share This Job</h3>
            <div class="idx-sr-social">
              <a class="fab fa-linkedin-in" href="https://www.linkedin.com/shareArticle?title=<?php echo $title; ?>&mini=true&source=Senior%20Lifestyle&summary=<?php echo $description; ?>&url=<?php echo get_permalink(); ?>" aria-label="LinkedIn" target="_blank"></a>
              <a class="fab fa-facebook" href="http://www.facebook.com/sharer/sharer.php?s=100&u=<?php echo get_permalink(); ?>" aria-label="Facebook" target="_blank"></a>
              <a class="fab fa-twitter" href="https://twitter.com/share?text=Senior%20Lifestyle%20is%20looking%20for%3A%20Assisted%20Living%20Attendant%2FCaregiver.&via=Senior Lifestyle&url=<?php echo get_permalink(); ?>" aria-label="Twitter" target="_blank"></a>
              <a class="fas fa-envelope" href="mailto:?&subject=<?php echo $title; ?>&body=<?php echo $description ?>" aria-label="Mail" target="_blank"></a>
            </div>
            <?php if (count($other_communities) > 0) : ?>
              <h3>Other Jobs at <?php echo $community; ?></h3>
              <ul class="idx-sr-other-communities">
                <?php
                  foreach($other_communities as $index => $p) {
                    $other_title = $p->post_title;
                    $other_city = get_post_meta($p->ID, 'idx_smartrecruiters_city', true);
                    $other_region_code = get_post_meta($p->ID, 'idx_smartrecruiters_region_code', true);
                    $other_link = get_permalink($p->ID);
                    echo '<li><a href="' . $other_link . '"><span class="title">' . $p->post_title . '</span><span class="address">' . $other_city . ', ' . $other_region_code . '</span></a></li>';
                  }
                ?>
              </ul>
              <a class="search-link" href="/<?php echo $slug; ?>/<?php echo $terms_slug; ?>">Show all jobs</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<script type="application/ld+json">
  {
    "@context" : "https://schema.org/",
    "@type" : "JobPosting",
    "title" : "<?php echo $title; ?>",
    "description" : "<?php echo $description; ?>",
    "identifier": {
      "@type": "PropertyValue",
      "name": "<?php echo $title; ?>",
      "value": "<?php echo $id; ?>"
    },
    "datePosted" : "<?php echo $created_on; ?>",
    "employmentType" : "<?php echo $employment_type; ?>",
    "hiringOrganization" : {
      "@type" : "Organization",
      "name" : "Senior Lifestyle",
      "sameAs" : "https://www.seniorlifestyle.com",
      "logo" : "https://www.seniorlifestyle.com/wp-content/themes/senior-lifestyle-child/assets/img/logos/logo-senior-living.webp"
    },
    "jobLocation": {
    "@type": "Place",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "<?php echo $address; ?>",
        "addressLocality": "<?php echo $city; ?>",
        "addressRegion": "<?php echo $region_code; ?>",
        "postalCode": "<?php echo $zip; ?>",
        "addressCountry": "<?php echo $country_code; ?>"
      }
    },
    "baseSalary": {
      "@type": "MonetaryAmount",
      "currency": "USD",
      "value": {
        "@type": "QuantitativeValue",
        "value": "<?php echo $hourly; ?>",
        "unitText": "HOUR"
      }
    }
  }
</script>

<!-- Global footer -->
<?php get_footer(); ?>