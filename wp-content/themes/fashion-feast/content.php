<?php
/**
 * The default template for displaying content
 *
 * Used for both single and index/archive/search.
 *
 * @package WordPress
 * @subpackage TemplateMela
 * @since TemplateMela 1.0
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
  <div class="entry-main-content">
  
	<?php if ( is_search() || !is_single()) : // Only display Excerpts for Search and not single pages ?>
	 
	 
	  <?php if ( has_post_thumbnail() && ! post_password_required() ) : ?>
		  <div class="entry-thumbnail">
			<?php 
			the_post_thumbnail('blog-posts-list');
			$postImage = wp_get_attachment_url( get_post_thumbnail_id(get_the_ID()) );
			?>
			<?php 
	if(!empty($postImage)): ?>
	<div class="block_hover">
		<div class="links">
			  <a href="<?php echo esc_url($postImage); ?>" title="Click to view Full Image" data-lightbox="example-set" class="icon mustang-gallery"><i class="fa fa-search"></i></a> <a href="<?php echo esc_url(get_permalink()); ?>" title="Click to view Read More" class="icon readmore"><i class="fa fa-share"></i></a> 
		</div>
	</div>
	<?php	endif; ?>
		  </div>		
		  <?php else : ?>
		  
		  
			  <?php if ($postImage = templatemela_get_first_post_images(get_the_ID())):?>
				  <?php if( $postImage!="0" ) : ?>
				  <div class="entry-thumbnail">
					<div class="entry-image-loop-con main">
					  <?php templatemela_print_images_thumb($postImage, get_the_title(get_the_ID()) , 918, 300,'center'); ?>
					  <article class="da-animate da-slideFromRight" style="display: block;">
						<div class="blog-icon-container"> <span class="zoom"><a data-lightbox="example-set" id="blog-zoom" href="<?php echo esc_url($postImage); ?>" title="Standard Post"></a></span> <span class="single_link"><a href="<?php echo esc_url(get_permalink()); ?>" title="View Full Image" class="standard"></a></span> </div>
					  </article>
					</div>
				  </div>
			  <?php endif; ?>
			<?php endif; ?>
  		<?php endif; ?>
		
		
  	<?php endif; ?>
     <div class="entry-content-other">
	<div class="entry-content-inner"> 
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
		  </div>
	</div>
     
	  <div class="entry-meta-inner">    
     	<div class="entry-content-date">
		  
		</div>
		<div class="entry-meta">
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
	 <!-- .entry-header -->
      <?php if ( is_search() || !is_single()) : // Only display Excerpts for Search and not single pages ?>
      <div class="entry-summary">
        <div class="excerpt"> <?php echo tm_posts_short_description_blog(); ?> </div>
      </div>
      <!-- .entry-summary -->
      <?php else : ?>
      <div class="entry-content">
        <?php the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'fashion-feast' ) ); ?>
        <?php wp_link_pages( array( 'before' => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'fashion-feast' ) . '</span>', 'after' => '</div>', 'link_before' => '<span>', 'link_after' => '</span>' ) ); ?>
      </div>
      <!-- .entry-content -->
      <?php endif; ?>
    <!-- entry-content-other -->
  </div>
</article>
<!-- #post -->