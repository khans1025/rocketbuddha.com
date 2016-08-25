<?php
/** Adding TM Menu in admin panel. */
function my_plugin_menu() {	
	add_theme_page( __('Theme Settings','fashion-feast'), __('TM Theme Settings','fashion-feast'), 'manage_options', 'tm_theme_settings', 'templatemela_theme_settings_page' );		
	add_theme_page( __('Hook Manager','fashion-feast'), __('TM Hook Manager','fashion-feast'), 'manage_options', 'tm_hook_manage', 'templatemela_hook_manage_page');	
}
?>