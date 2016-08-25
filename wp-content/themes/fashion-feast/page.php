<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages and that
 * other 'pages' on your WordPress site will use a different template.
 *
 * @package WordPress
 * @subpackage TemplateMela
 * @since TemplateMela 1.0
 */
get_header();
?>
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
						 <?php ?>  <?php // If comments are open or we have at least one comment, load up the comment template.
				if ( is_user_logged_in() ){
			if ( comments_open() || get_comments_number() ) {
			comments_template();
			}
		} ?>
      <?php  endwhile;	?>
	  
    </div>
    <!-- #content -->
  </div>
  <!-- #primary -->
  <?php get_sidebar(); ?>
<?php get_footer(); ?>