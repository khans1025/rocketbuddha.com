<?php
/* Template Name: Blog Box */
?>
<?php get_header(); 
$tm_content_position = tm_content_position();
?>
<!-- Start blog-box -->
<div id="main-content" class="main-content blog-page blog-box <?php echo esc_attr(tm_sidebar_position()); ?>">
  <div id="primary" class="content-area">
    <div id="content" class="site-content" role="main">
      <?php if($tm_content_position == 'above') : 
		// Page thumbnail
		templatemela_post_thumbnail();
		the_content(); 
	  endif; ?>
      <div id="container" class="blog-box-container box-container">
        <?php	
			if ( get_query_var('paged') ) { $paged = get_query_var('paged'); } else if ( get_query_var('page') ) {$paged = get_query_var('page'); } else {$paged = 1; }
			$last_class = "";
				$blog_args = array(
					'posts_per_page' 	=> tm_blog_box_posts_per_page(), 
					'paged' 			=> $paged,
					'post_type'			=> 'post',
					'status'			=> 'publish',
				);
				$columns_number = tm_blog_box_columns_number();
				$tm_blog_box_display = tm_blog_box_display();
				query_posts($blog_args);
				if ( have_posts()) : ?>
        <?php if($tm_blog_box_display == 'masonry'): ?>
        <div class="loading"> <img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/megnor/loading.gif" alt="" /> </div>
        <?php endif; ?>
        <div class="<?php echo esc_attr($tm_blog_box_display); ?> <?php echo esc_attr(tm_blog_box_columns_class()); ?>" <?php if($tm_blog_box_display == 'masonry'): ?> style="display:none;" <?php endif; ?>>
          <?php $i = 1;	
				$width = 1;	
				while( $wp_query->have_posts() ): $wp_query->the_post();
				if($width > 5) $width = 1;	
				if($i % $columns_number == 1):
					$last_class = " first"; 	
				elseif($i % $columns_number == 0):
					$last_class = " last";				
					if($tm_blog_box_display == 'masonry'):
						$last_class .= ' width'.$width;		
					endif;				
				else:
					$last_class = '';
				endif; ?>
          <div class="<?php echo esc_attr($tm_blog_box_display."-item"); ?> item<?php echo esc_attr($last_class); ?>">
            <?php  $post_format = get_post_format();
					if ( $post_format ) $post_format = 'format-' . $post_format;
					get_template_part( 'content', $post_format );
					?>
          </div>
          <?php $width++; $i++; endwhile; ?>
          <?php 
				else:  ?>
          <p>
            <?php  __( 'Sorry, no posts matched your criteria.', 'fashion-feast' ); ?>
          </p>
          <?php endif; ?>
        </div>
        <?php // Post navigation.
				   templatemela_paging_nav(); 
				   wp_reset_postdata();  // Reset ?>
      </div>
      <?php if($tm_content_position == 'below') : 
					// Page thumbnail
					templatemela_post_thumbnail();
					the_content(); 
				endif; ?>
    </div>
    <!-- #content -->
  </div>
  <!-- #primary -->
  <?php get_sidebar(); ?>
</div>
<!-- End blog-box -->
<?php get_footer(); ?>