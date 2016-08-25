<?php
/**
 * The template for displaying the footer
 *
 * Contains footer content and the closing of the #main and #page div elements.
 *
 * @package WordPress
 * @subpackage TemplateMela
 * @since TemplateMela 1.0
 */
?>
<?php templatemela_content_after(); ?>
</div>
<!-- .main-content-inner -->
</div>
<!-- .main_inner -->
</div>
<!-- #main -->
<?php templatemela_footer_before(); ?>
<footer id="colophon" class="site-footer">
 <div class="footer-block">
		<?php templatemela_get_widget('footer-block'); ?>
	</div>
  <div class="footer_inner">
    <?php templatemela_footer_inside(); ?>
	 <?php get_sidebar('footer'); ?>
	<!-- .footer-bottom -->
  </div>
    <div class="footer-bottom">
	<div class="footer-bottom-container">
      <?php /*?><div class="footer-menu-links">
        <?php
					$tm_footer_menu=array(
					'menu' => 'TM Footer Navigation',
					'depth'=> 1,
					'echo' => false,
					'menu_class'      => 'footer-menu', 
					'container'       => '', 
					'container_class' => '', 
					'theme_location' => 'footer-menu'
					);
					echo wp_nav_menu($tm_footer_menu);				    
					?>
      </div><?php */?>
      <!-- #footer-menu-links -->
      <div class="site-info">  <?php __( 'Copyright', 'fashion-feast' ); ?> &copy; <?php echo esc_attr(date('Y')); ?> <a href="<?php echo esc_url(get_option('tmoption_footer_link')); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><?php echo esc_attr(get_option('tmoption_footer_slog'));?>
        </a>
        <?php do_action( 'templatemela_credits' ); ?>
      </div>
	  <div id="fifth" class="fifth-widget footer-widget animated" data-animated="fadeInRight">
    	<?php dynamic_sidebar( 'fifth-footer-widget-area' ); ?>
  	  </div>
	  <?php /*?><div class="footer-payment">
	  	<?php templatemela_get_widget('footer-payment'); ?>
	  </div><?php */?>
      <!-- .site-info -->
	  </div>
    </div>
  <!--. Footer inner -->
</footer>
<!-- #colophon -->
<?php templatemela_footer_after(); ?>
</div>
<!-- #page -->
<?php tm_go_top(); ?>
<?php templatemela_get_widget('before-end-body-widget'); ?>
<?php wp_footer(); ?>
<style>
<?php templatemela_custom_css(); ?>
</style>
</body>
</html>