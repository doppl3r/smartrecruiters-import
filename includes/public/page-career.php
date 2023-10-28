<!-- Global header -->
<?php get_header(); ?>

<?php
    // Get post content
    global $post;

    // TODO: Set variables from post
?>

<!-- Body content -->
<div id="primary" class="content-area idx-sr-single">
  <main id="main" class="site-main pb-0">
    <div class="container">
      <div class="row">
        <div class="col">
          <?php require_once 'breadcrumbs.php'; ?>
          <h1><?php echo get_the_title(); ?></h1>
        </div>
      </div>
      <div class="row">
        <div class="col-md-8 col-lg-9">
          <p>Company Description</p>
          <?php echo get_the_content(); ?>
        </div>
        <div class="col-md-4 col-lg-3">
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Global footer -->
<?php get_footer(); ?>