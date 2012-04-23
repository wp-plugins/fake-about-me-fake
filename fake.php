<?php

/**
 * @package fakeaboutme
 * @version 1.0
*/
/*

Plugin Name: FAKE About Me

Plugin URI: http://wordpress.org/extend/plugins/fake-about-me/

Description: Create a Fake identities or just create your own about me description, creates a new widget that allow you to choose your diferent identities.

Author: Brlocky
Author URI: http://www.gabyweb.com/

Version: 1.0
License: A "Slug" license name e.g. GPL2


*/

 
 

if (!defined('ABSPATH')) {
    die('ABSPATH');
}



if (!defined('FAKEABOUTME_INIT')) {    
 

	/**
     * Require plugin configuration
     */
    require_once dirname(__FILE__) . '/inc/define.php';
 
	//Fake About Me Class
	require_once FAKEABOUTME_INC_DIR . '/fakeaboutme.class.php';

	//Widget Class
	require_once FAKEABOUTME_INC_DIR . '/fakeaboutme_widget.class.php';


	 
	
	
	//LANG TEST
		
	
/*	$locale = "deu_DEU";
	
	putenv("LC_ALL=$locale");
	setlocale(LC_ALL, $locale);
	
	//FAKEABOUTME_LANG_DIR
		
	bindtextdomain("greetings", "./locale");
	textdomain("greetings");
	
	echo _("Hello World");
	*/	
	
	
	
	
	
	
	
 

	/**
     * Start Widget
     */	 
	$Fakeaboutme->run();
   
   	
   
	add_action("widgets_init", 'fakeaboutme_register_widget');  
	//load_plugin_textdomain('Fakeaboutme_Widget', false, FAKEABOUTME_LANG_DIR );
	
	register_activation_hook( __FILE__, 'fakeaboutme_activate');
	register_deactivation_hook( __FILE__, 'fakeaboutme_deactivate');

	  
	

	/*************************************
		REGISTER WIDGET
	*/
	function fakeaboutme_register_widget(){
		register_widget( 'Fakeaboutme_Widget' );
	}
 
	

	/*************************************
		PLUGIN ACTIVATE
	*/
	function fakeaboutme_activate(){

		$data = array( 
					'fakeaboutme_apikey' => '1tDWy7y_TRXVsad76lO1QxQH7bV3w18g'
					,'fakeaboutme_country' => 'us'
					,'fakeaboutme_language' => 'us'
					,'fakeaboutme_genre' => '0'
				);
  
		if ( ! get_option('fake-about-me-settings')){
			add_option('fake-about-me-settings' , $data);
		} else {
			update_option('fake-about-me-settings' , $data);
		}
	}
 
	

	/*************************************
		PLUGIN DEACTIVATE
	*/
	function fakeaboutme_deactivate(){		
		delete_option('fake-about-me-settings');
	}
	
}
?>