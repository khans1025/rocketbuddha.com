<?php
/**
 * The template for displaying posts in the Video post format
 *
 * @package WordPress
 * @subpackage TemplateMela
 * @since TemplateMela 1.0
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
  <div class="entry-main-content">
	 
	 
    <!-- .entry-content-other -->
	 <div class="entry-video">
        <?php
					the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'fashion-feast' ) );
					wp_link_pages( array(
						'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'fashion-feast' ) . '</span>',
						'after'       => '</div>',
						'link_before' => '<span>',
						'link_after'  => '</span>',
					) );
				?>
		</div>	
		<div class="entry-content-other">
    	  <div class="entry-content"> 
			<div class="entry-main-header">
			<header class="entry-header">
				<?php 		
					if ( is_single() ) :
						the_title( '<h1 class="entry-title">', '</h1>' );
					else :
						the_title( '<h1 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h1>' );
					endif;
				?>
		  </header>
      <!-- .entry-header -->
	 </div>
	 
      </div>
      <!-- .entry-content -->
	  <div class="entry-meta-inner"> 
	  <div class="entry-content-date">
		
		</div>
	  <div class="entry-meta"> <span class="post-format"> <a class="entry-format" href="<?php echo esc_url( get_post_format_link( 'video' ) ); ?>"><?php echo esc_attr(get_post_format_string( 'video' )); ?></a> </span>
	  		    <?php tm_post_entry_date(); ?>
			  <?php templatemela_categories_links(); ?>
			  <?php templatemela_tags_links(); ?>
			  <?php templatemela_author_link(); ?>
			  <?php templatemela_comments_link(); ?>
			  <?php edit_post_link( __( 'Edit', 'fashion-feast' ), '<span class="edit-link"><i class="fa fa-pencil"></i>', '</span>' ); ?>
			</div>
			<!-- .entry-meta -->
		</div>
    </div>	
			
  </div>
  <!-- .entry-main-content -->
</article>
<!-- #post-## -->