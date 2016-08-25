<?php
/*
  Plugin Name: Templatemela Custom Widgets
  Plugin URI: http://www.templatemela.com
  Description: Templatemela Default Homepage Slide Show for templatemela wordpress themes.
  Version: 1.0
  Author: Templatemela
  Author URI: http://www.templatemela.com
  @copyright  Copyright (c) 2010 TemplateMela. (http://www.templatemela.com)
  @license    http://www.templatemela.com/license/
 */
?>
<?php 
//  Creating Widget 
// Reference : http://codex.wordpress.org/Widgets_API
/**
 * Register widgetized areas, including two sidebars and four widget-ready columns in the footer.
 *
 * To override templatemela_widgets_init() in a child theme, remove the action hook and add your own
 * function tied to the init hook.
 *
 * @uses register_sidebar
 */
function templatemela_register_sidebars() {
	register_sidebar( array(
		'name' => __( 'Header Area', 'fashion-feast' ),
		'id' => 'header-widget',
		'description' => __( 'The primary widget area on header', 'fashion-feast' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s tab_content">',
		'after_widget' => "</aside>",
		'before_title' => '<div class="top-arrow"> </div> <h3 class="widget-title">',
		'after_title' => '</h3>',
	) );
	register_sidebar( array(
		'name' => __( 'Footer Top Area', 'fashion-feast' ),
		'id' => 'footer-block',
		'description' => __( 'The footer top widget area', 'fashion-feast' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget' => "</aside>",
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );
		
	register_sidebar( array(
		'name' => __( 'First Footer Widget Area', 'fashion-feast' ),
		'id' => 'first-footer-widget-area',
		'description' => __( 'The first footer widget area', 'fashion-feast' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget' => "</aside>",
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );
	
	register_sidebar( array(
		'name' => __( 'Second Footer Widget Area', 'fashion-feast' ),
		'id' => 'second-footer-widget-area',
		'description' => __( 'The second footer widget area', 'fashion-feast' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget' => "</aside>",
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );
	
	register_sidebar( array(
		'name' => __( 'Third Footer Widget Area', 'fashion-feast' ),
		'id' => 'third-footer-widget-area',
		'description' => __( 'The third footer widget area', 'fashion-feast' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget' => "</aside>",
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );
	
	register_sidebar( array(
		'name' => __( 'Fourth Footer Widget Area', 'fashion-feast' ),
		'id' => 'forth-footer-widget-area',
		'description' => __( 'The forth footer widget area', 'fashion-feast' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget' => "</aside>",
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );
	
	register_sidebar( array(
		'name' => __( 'Fifth Footer Widget Area', 'fashion-feast' ),
		'id' => 'fifth-footer-widget-area',
		'description' => __( 'The fifth footer widget area', 'fashion-feast' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget' => "</aside>",
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );
	register_sidebar( array(
		'name' => __( 'Header Search Widget Area', 'fashion-feast' ),
		'id' => 'header-search',
		'description' => __( 'The header search widget area', 'fashion-feast' ),
		'before_widget' => '',
		'after_widget' => " ",
		'before_title' => ' ',
		'after_title' => ' ',
	) );
}
/**
 * Register sidebars by running templatemela_widgets_init() on the widgets_init hook. 
 */
add_action( 'widgets_init', 'templatemela_register_sidebars' );
get_template_part('templatemela/widgets/tm-aboutus');
get_template_part('templatemela/widgets/tm-advertise');
get_template_part('templatemela/widgets/tm-flickr');
get_template_part('templatemela/widgets/tm-follow-us');
get_template_part('templatemela/widgets/tm-footer-contactus');
get_template_part('templatemela/widgets/tm-header-contact');
get_template_part('templatemela/widgets/tm-static-links');
get_template_part('templatemela/widgets/tm-static-text');
get_template_part('templatemela/widgets/tm-left-banner');
get_template_part('templatemela/widgets/tm-cmsblock');
get_template_part('templatemela/widgets/tm-footer-aboutme');
?>