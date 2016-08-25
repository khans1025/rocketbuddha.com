<?php
/***********************************************************/
// Staff Template options
/***********************************************************/
$prefix = 'tm_staff_list_';
$TM_META_BOXES[] = array(
	'id'		=> 'tm_staff_list_columns',
	'title' 	=> __('TM - List Options', 'fashion-feast'),
	'pages' 	=> array( 'page' ),
	'context' 	=> 'normal',
	'priority' 	=> 'high',
	'local_images' => true,
	'fields' 	=> array(	
		// Show number of posts per page
		array(
			'name'			=> __('Number of posts per page:', 'fashion-feast'),
			'id'    		=> "{$prefix}posts_per_page",
			'type'  		=> 'text',
			'std'   		=> '5',
		),
	),
	'display_on'	=> array( 'template' => array(
		'page-templates/staff-list.php',
	) ),
);
$prefix = 'tm_staff_box_';
$TM_META_BOXES[] = array(
	'id'		=> 'tm_staff_box_columns',
	'title' 	=> __('TM - Box Options', 'fashion-feast'),
	'pages' 	=> array( 'page' ),
	'context' 	=> 'normal',
	'priority' 	=> 'high',
	'local_images' => true,
	'fields' 	=> array(	
		// Show posts per column
		array(
			'name'    		=> __('Columns Options:', 'fashion-feast'),
			'id'      		=> "{$prefix}columns",
			'type'    		=> 'radio',
			'std'			=> 'two',
			'options'		=> array(
				'two'		=> 'Two',
				'three'		=> 'Three',
				'four'		=> 'Four', 
			)
		),
		// Show number of posts per page
		array(
			'name'			=> __('Number of posts per page:', 'fashion-feast'),
			'id'    		=> "{$prefix}posts_per_page",
			'type'  		=> 'text',
			'std'   		=> '5',
		),
	),
	'display_on'	=> array( 'template' => array(
		'page-templates/staff-box.php'
	) ),
);
?>