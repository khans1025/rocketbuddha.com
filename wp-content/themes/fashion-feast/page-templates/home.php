<?php
/* Template Name: Home */ 
?>
<?php 
get_header();
?>
<div id="main-content" class="main-content home-page <?php echo esc_attr(tm_sidebar_position()); ?>">
<div class="top_main_outer">
<div class="top_main">
				<?php if (get_option('tmoption_custom_banner') == 'yes') : ?>
				  <div class="topbar-banner">
					<?php templatemela_get_topbar_banner(); ?>
				  </div>
				<?php endif; ?>		
				</div>
			</div>
  <?php
	if ( is_front_page() && templatemela_has_featured_posts() ) {
		// Include the featured content template.
		get_template_part( 'featured-content' );
	}
?>
  <div id="primary" class="content-area">
    <div id="content" class="site-content" role="main">
      <?php
				// Start the Loop.
				while ( have_posts() ) : the_post();
					// Include the page content template.
					get_template_part( 'content', 'page' ); ?>
      <?php endwhile;
			?>
    </div>
    <!-- #content -->
  </div>
  <!-- #primary -->
  <?php get_sidebar(); ?>
</div>
<!-- #main-content -->
<?php get_footer(); ?>