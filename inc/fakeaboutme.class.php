<?php

if (!defined('FAKEABOUTME_INIT')) {
    die('FAKEABOUTME_INIT');
}

$Fakeaboutme = new Fakeaboutme();

/**
* Class Fakeaboutme
*/

class Fakeaboutme{

	function __construct(){
		//Language
		//Can Run on Constructor or any where else, just keep last slash on language dir
        load_plugin_textdomain('fakeaboutme', false, FAKEABOUTME_LANG_DIR );
    }
 


    /**
     * Runs plugin
     *
     * @return void
     */
    function run(){
		/**
		 * Admin Actions and Filters
		 */
 		if( is_admin() )
		{ 
			add_action( 'init', array($this,'register_posts'));
			add_action( 'save_post', array($this,'save'),1, 2);
			add_action( 'admin_menu', array( $this,'admin_bar_menu'));
			add_action( 'admin_print_scripts-post-new.php', array( $this,'fab_service_script'), 11 );
			add_action( 'admin_print_scripts-post.php', array( $this,'fab_service_script'), 11 );	
			add_action( 'wp_ajax_fakeaboutme_service' , array($this,'service_load'));
		}
		else
		{			
			//include aboutme css
			add_action( 'wp_enqueue_scripts', array($this,'fab_widget_css') );
		}
  	 
	} 


 




	//Ajax Service Call FAKENAMEGENERATOR API
	function service_load(){
	 	if( isset($_GET['fakeaboutme_language']) && isset($_GET['fakeaboutme_genre'])){
			$output = $this->runService($_GET['fakeaboutme_country'],$_GET['fakeaboutme_language'],$_GET['fakeaboutme_genre']);
			if($output == 0)
				echo "Error with Service, please check API Key";
		}
 		exit();
	}
	
	 
		

 	//Service to get new identitie -> Fake name Generator Script
	function runService($country,$language,$genre){
		$options = get_option('fake-about-me-settings');
		$apikey = isset($options['fakeaboutme_apikey']) ? $options['fakeaboutme_apikey'] : die('error API KEY');
		
		//country is not implemented
		$country = 'us';

		$serviceUrl = 'http://svc.webservius.com/v1/CorbanWork/fakename?wsvKey='.$apikey.'&output=xml&c='.$country.'&n='.$language.'&gen='.$genre.'';

		//Curl		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$serviceUrl );
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$xmlData = curl_exec($ch);
		curl_close($ch);
 		
		
		$table_data = $this->fakeLoad($xmlData);
		
		if(!is_array($table_data))
			return $table_data;
		
		//get_contents

		//$data = file_get_contents($serviceUrl); 
		
		$this->drawIdentityTable($table_data);

		exit();

	}

	 
	
	

	//js for settings in fab_indetities type
	function fab_service_script(){
		global $post_type; 

		if( 'fab_identities' == $post_type ){
			wp_enqueue_script( 'fab_service_script', FAKEABOUTME_JS_DIR . '/service.js' );
	        wp_localize_script( 'fab_service_script', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		}
	}
	
	
	/**********************************
	* WIDGET CSS
	*/
	function fab_widget_css(){
		wp_register_style( 'fab-widget-style', FAKEABOUTME_CSS_DIR .'/fake-about-me.css' );
		wp_enqueue_style( 'fab-widget-style' );
	}
		



	/**
     * Register Post Type
	 * register post type "fab_identities"
     *
     * @return void
     */
	function register_posts(){
		register_post_type('fab_identities',array(
			 'labels' => array(
				'singular_name' => 'Identity',
				'all_items' => 'All Identities',
				'add_new_item' => 'Add New Identities',
				'edit_item' => 'Edit Identity',
				'new_item' => 'New Identity',
				'view_item' => 'View Identity Page',
				'search_items' => 'Search Identities',
				'not_found' => 'No identities found',
				'not_found_in_trash' => 'No posts found in Trash',
				'menu_name' => 'Identities'
			)
			,'description' => 'This is where you can add new Identities'
			,'label' => 'Identities'
			,'register_meta_box_cb' => array($this,'create_metadata')
			,'public' => true
			,'show_ui' => true
 			,'supports' => array( 'title', 'thumbnail', 'excerpt','revisions' )
			,'menu_icon' => '/wp-admin/images/generic.png'
		));
		 
	}

 

	 

	

    /**
     * Admin bar menu
	 * Add option "Fake About Me" to WordPress Settings menu area
     *
     * @return void
     */

    function admin_bar_menu(){
		add_submenu_page( 'options-general.php',  __('Fake About Me'), 'Fake About Me', 8, 'fake-about-me', array($this,'draw_settings'));
	}

	 

	  
	

 	/**
	*	Draw Settings Page
	*   - view, save, reset
	*/

 	function draw_settings(){
  		//Save Settings
		if($_POST && wp_verify_nonce($_POST['fakeboutme_save_settings_nonce'], __FILE__))
		{
			$data = get_option('fake-about-me-settings');

			$data['fakeaboutme_apikey'] = $_POST['fakeaboutme_apikey'];
			//$data['fakeaboutme_country'] = $_POST['fakeaboutme_country'];
			$data['fakeaboutme_language'] = $_POST['fakeaboutme_language'];
			$data['fakeaboutme_genre'] = $_POST['fakeaboutme_genre'];
			
			update_option('fake-about-me-settings', $data);
 		}
 
		//Load Settings
		//extract -> would be: $apikey, $country.
		extract(get_option('fake-about-me-settings'));
 

		//die(debug(get_option('fake-about-me-settings')));

		?>

        <div class="wrap">
            <div id="icon-users" class="icon32"><br></div>
            <h2><?=_e('Fake About Me','fakeaboutme')?></h2>
            <p><?=_e('Fake Name Generator <b>api key</b> and <b>default settings</b>.','fakeaboutme')?></p>
            <form name="fakeaboutme_form_settings" method="post" action="<?=admin_url('options-general.php?page=fake-about-me')?>">
            <?
				 wp_nonce_field(__FILE__, 'fakeboutme_save_settings_nonce');
			?>	 

                <table id="fakeaboutme_table_settings" name="fakeaboutme_table_settings" class="form-table">

                    <tr class="form-field">

                        <td><?=__('ApiKey','fakeaboutme')?></td>

                        <td><input id="fakeaboutme_apikey" name="fakeaboutme_apikey" type="text" class="widefat" value="<?=$fakeaboutme_apikey?>" autocomplete="off" /></td>

                    </tr>

                <!-- not going to use this by now -->

                <? /*   <tr class="form-field">

                        <td><?=_e('Country','fakeaboutme')?></td>

                        <td><select id="fakeaboutme_country" name="fakeaboutme_country">

                                <option value="au" <?=$country == 'au' ? 'selected="selected"' : ''?>>Australia</option>

                                <option value="as" <?=$country == 'as' ? 'selected="selected"' : ''?>>Austria</option>

                                <option value="bg" <?=$country == 'bg' ? 'selected="selected"' : ''?>>Belgium</option>

                                <option value="br" <?=$country == 'br' ? 'selected="selected"' : ''?>>Brazil</option>

                                <option value="ca <?=$country == 'ca' ? 'selected="selected"' : ''?>">Canada</option>

                                <option value="cyen" <?=$country == 'cyen' ? 'selected="selected"' : ''?>>Cyprus (Anglicized)</option>

                                <option value="cygk" <?=$country == 'cygk' ? 'selected="selected"' : ''?>>Cyprus (Greek)</option>

                                <option value="dk" <?=$country == 'dk' ? 'selected="selected"' : ''?>>Denmark</option>

                                <option value="fi" <?=$country == 'fi' ? 'selected="selected"' : ''?>>Finland</option>

                                <option value="fr" <?=$country == 'fr' ? 'selected="selected"' : ''?>>France</option>

                                <option value="gr" <?=$country == 'gr' ? 'selected="selected"' : ''?>>Germany</option>

                                <option value="hu" <?=$country == 'hu' ? 'selected="selected"' : ''?>>Hungary</option>

                                <option value="is" <?=$country == 'is' ? 'selected="selected"' : ''?>>Iceland</option>

                                <option value="it" <?=$country == 'it' ? 'selected="selected"' : ''?>>Italy</option>

                                <option value="nl" <?=$country == 'nl' ? 'selected="selected"' : ''?>>Netherlands</option>

                                <option value="nz" <?=$country == 'nz' ? 'selected="selected"' : ''?>>New Zealand</option>

                                <option value="no" <?=$country == 'no' ? 'selected="selected"' : ''?>>Norway</option>

                                <option value="pl" <?=$country == 'pl' ? 'selected="selected"' : ''?>>Poland</option>

                                <option value="sl" <?=$country == 'sl' ? 'selected="selected"' : ''?>>Slovenia</option>

                                <option value="sp" <?=$country == 'sp' ? 'selected="selected"' : ''?>>Spain</option>

                                <option value="sw" <?=$country == 'sw' ? 'selected="selected"' : ''?>>Sweden</option>

                                <option value="sz" <?=$country == 'sz' ? 'selected="selected"' : ''?>>Switzerland</option>

                                <option value="uk" <?=$country == 'uk' ? 'selected="selected"' : ''?>>United Kingdom</option>

                                <option value="us" <?=$country == 'us' ? 'selected="selected"' : ''?>>United States</option>

                            </select>

                        </td>

                    </tr> */ ?>

                    <tr class="form-field">

                    	<td><?=_e('Language','fakeaboutme')?></td>

                        <td><select id="fakeaboutme_language" name="fakeaboutme_language">

                                <option value="us" <?=$fakeaboutme_language == 'us' ? 'selected="selected"' : ''?>>American</option>

                                <option value="ar" <?=$fakeaboutme_language == 'ar' ? 'selected="selected"' : ''?>>Arabic</option>

                                <option value="au" <?=$fakeaboutme_language == 'au' ? 'selected="selected"' : ''?>>Australian</option>

                                <option value="br" <?=$fakeaboutme_language == 'br' ? 'selected="selected"' : ''?>>Brazil</option>

                                <option value="ch" <?=$fakeaboutme_language == 'ch' ? 'selected="selected"' : ''?>>Chinese</option>

                                <option value="zhtw" <?=$fakeaboutme_language == 'zhtw' ? 'selected="selected"' : ''?>>Chinese (Traditional)</option>

                                <option value="hr" <?=$fakeaboutme_language == 'hr' ? 'selected="selected"' : ''?>>Croatian</option>

                                <option value="cs" <?=$fakeaboutme_language == 'cs' ? 'selected="selected"' : ''?>>Czech</option>

                                <option value="dk" <?=$fakeaboutme_language == 'dk' ? 'selected="selected"' : ''?>>Danish</option>

                                <option value="nl" <?=$fakeaboutme_language == 'nl' ? 'selected="selected"' : ''?>>Dutch</option>

                                <option value="en" <?=$fakeaboutme_language == 'en' ? 'selected="selected"' : ''?>>England/Wales</option>

                                <option value="er" <?=$fakeaboutme_language == 'er' ? 'selected="selected"' : ''?>>Eritrean</option>

                                <option value="fi" <?=$fakeaboutme_language == 'fi' ? 'selected="selected"' : ''?>>Finnish</option>

                                <option value="fr" <?=$fakeaboutme_language == 'fr' ? 'selected="selected"' : ''?>>French</option>

                                <option value="gr" <?=$fakeaboutme_language == 'gr' ? 'selected="selected"' : ''?>>German</option>

                                <option value="sp" <?=$fakeaboutme_language == 'sp' ? 'selected="selected"' : ''?>>Hispanic</option>

                                <option value="hobbit" <?=$fakeaboutme_language == 'hobbit' ? 'selected="selected"' : ''?>>Hobbit</option>

                                <option value="hu" <?=$fakeaboutme_language == 'hu' ? 'selected="selected"' : ''?>>Hungarian</option>

                                <option value="is" <?=$fakeaboutme_language == 'is' ? 'selected="selected"' : ''?>>Icelandic</option>

                                <option value="ig" <?=$fakeaboutme_language == 'ig' ? 'selected="selected"' : ''?>>Igbo</option>

                                <option value="it" <?=$fakeaboutme_language == 'it' ? 'selected="selected"' : ''?>>Italian</option>

                                <option value="jpja" <?=$fakeaboutme_language == 'jpja' ? 'selected="selected"' : ''?>>Japanese</option>

                                <option value="jp" <?=$fakeaboutme_language == 'jp' ? 'selected="selected"' : ''?>>Japanese (Anglicized)</option>

                                <option value="ninja" <?=$fakeaboutme_language == 'ninja' ? 'selected="selected"' : ''?>>Ninja</option>

                                <option value="no" <?=$fakeaboutme_language == 'no' ? 'selected="selected"' : ''?>>Norwegian</option>

                                <option value="fa" <?=$fakeaboutme_language == 'fa' ? 'selected="selected"' : ''?>>Persian</option>

                                <option value="pl" <?=$fakeaboutme_language == 'pl' ? 'selected="selected"' : ''?>>Polish</option>

                                <option value="ru" <?=$fakeaboutme_language == 'ru' ? 'selected="selected"' : ''?>>Russian</option>

                                <option value="rucyr" <?=$fakeaboutme_language == 'urcyr' ? 'selected="selected"' : ''?>>Russian (Cyrillic)</option>

                                <option value="gd" <?=$fakeaboutme_language == 'gd' ? 'selected="selected"' : ''?>>Scottish</option>

                                <option value="sl" <?=$fakeaboutme_language == 'sl' ? 'selected="selected"' : ''?>>Slovenian</option>

                                <option value="sw" <?=$fakeaboutme_language == 'sw' ? 'selected="selected"' : ''?>>Swedish</option>

                                <option value="vn" <?=$fakeaboutme_language == 'vn' ? 'selected="selected"' : ''?>>Vietnamese</option>

                            </select>

                	</td>

                </tr>   

                <tr class="form-field">

                    <td><?=_e('Genre','fakeaboutme')?></td>

                    <td><select id="fakeaboutme_genre" name="fakeaboutme_genre">

                            <option value="0" <?= $fakeaboutme_genre == 0 ? 'selected="selected"' : '' ?> ><?=_e('Random','fakeaboutme')?></option>

                            <option value="1" <?= $fakeaboutme_genre == 1 ? 'selected="selected"' : '' ?> ><?=_e('Male','fakeaboutme')?></option>

                            <option value="2" <?= $fakeaboutme_genre == 2 ? 'selected="selected"' : '' ?> ><?=_e('Female','fakeaboutme')?></option>

                        </select>

                    </td>

                </tr> 

                </table>

               

              	

                <p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"></p>

                

                <h2><?=_e('More Information','fakeaboutme')?></h2>

                <p> <a href="http://www.webservius.com/services/CorbanWork/fakename" target="_blank"><b>Free API signup</b> - 50 FREE units per month</a> </p>
                <p> <a href="http://www.fakenamegenerator.com/" target="_blank">Fake Name Generator</a></p>
                <p> <a href="http://www.fakenamegenerator.com/api.php" target="_blank">API documentation</a></p>
				<p>This product uses the Fake Name Generator API but is not endorsed or certified by the Fake Name Generator.</p>
            </form>
		</div>

        <?

	}

	
 
	

	/*
	 * Init metadata
	 */
	function create_metadata(){	

		add_meta_box(
			'fab_settings_metadata_wrap',
			__('Settings','fakeaboutme'),
			array($this,'create_metadata_settings'),
			'fab_identities',
			'side',
			'default'
		);

		add_meta_box(
			'fab_metadata_wrap',
			__('Identities','fakeaboutme'),
			array($this,'create_metada_identity'),
			'fab_identities',
			'normal',
			'high'
		);
	}

	

	 

	

	/*
	 * metadata settings
	 */
	function create_metadata_settings(){
		global $post;
 
		//get saves options
		$data = get_post_meta($post->ID, 'fakeaboutme_metadata', true);
		//Get default Options
		$default_data = get_option('fake-about-me-settings');
		
		$fakeaboutme_language = $data['fakeaboutme_language'] == '' ? $default_data['fakeaboutme_language'] : $data['fakeaboutme_language'];
		$fakeaboutme_genre = $data['fakeaboutme_genre'] == '' ? $default_data['fakeaboutme_genre'] : $data['fakeaboutme_genre'];
 
 		?>
 
		<table id="fakeaboutme_table_settings" name="fakeaboutme_table_settings" class="form-table">

 

           <? /*  <tr class="form-field">

				<td><?=_e('Country','fakeaboutme')?></td>

				<td><select id="fakeaboutme_country" name="fakeaboutme_country">

                		<option value="au" <?=$country == 'au' ? 'selected="selected"' : ''?>>Australia</option>

                        <option value="as" <?=$country == 'as' ? 'selected="selected"' : ''?>>Austria</option>

                        <option value="bg" <?=$country == 'bg' ? 'selected="selected"' : ''?>>Belgium</option>

                        <option value="br" <?=$country == 'br' ? 'selected="selected"' : ''?>>Brazil</option>

                        <option value="ca <?=$country == 'ca' ? 'selected="selected"' : ''?>">Canada</option>

                        <option value="cyen" <?=$country == 'cyen' ? 'selected="selected"' : ''?>>Cyprus (Anglicized)</option>

                        <option value="cygk" <?=$country == 'cygk' ? 'selected="selected"' : ''?>>Cyprus (Greek)</option>

                        <option value="dk" <?=$country == 'dk' ? 'selected="selected"' : ''?>>Denmark</option>

                        <option value="fi" <?=$country == 'fi' ? 'selected="selected"' : ''?>>Finland</option>

                        <option value="fr" <?=$country == 'fr' ? 'selected="selected"' : ''?>>France</option>

                        <option value="gr" <?=$country == 'gr' ? 'selected="selected"' : ''?>>Germany</option>

                        <option value="hu" <?=$country == 'hu' ? 'selected="selected"' : ''?>>Hungary</option>

                        <option value="is" <?=$country == 'is' ? 'selected="selected"' : ''?>>Iceland</option>

                        <option value="it" <?=$country == 'it' ? 'selected="selected"' : ''?>>Italy</option>

                        <option value="nl" <?=$country == 'nl' ? 'selected="selected"' : ''?>>Netherlands</option>

                        <option value="nz" <?=$country == 'nz' ? 'selected="selected"' : ''?>>New Zealand</option>

                        <option value="no" <?=$country == 'no' ? 'selected="selected"' : ''?>>Norway</option>

                        <option value="pl" <?=$country == 'pl' ? 'selected="selected"' : ''?>>Poland</option>

                        <option value="sl" <?=$country == 'sl' ? 'selected="selected"' : ''?>>Slovenia</option>

                        <option value="sp" <?=$country == 'sp' ? 'selected="selected"' : ''?>>Spain</option>

                        <option value="sw" <?=$country == 'sw' ? 'selected="selected"' : ''?>>Sweden</option>

                        <option value="sz" <?=$country == 'sz' ? 'selected="selected"' : ''?>>Switzerland</option>

                        <option value="uk" <?=$country == 'uk' ? 'selected="selected"' : ''?>>United Kingdom</option>

                        <option value="us" <?=$country == 'us' ? 'selected="selected"' : ''?>>United States</option>

                	</select>

            	</td>

			</tr> */ ?>

            <tr class="form-field">

				<td><?=_e('Language','fakeaboutme')?></td>

				<td><select id="fakeaboutme_language" name="fakeaboutme_language">

                		<option value="us" <?=$fakeaboutme_language == 'us' ? 'selected="selected"' : ''?>>American</option>

                        <option value="ar" <?=$fakeaboutme_language == 'ar' ? 'selected="selected"' : ''?>>Arabic</option>

                        <option value="au" <?=$fakeaboutme_language == 'au' ? 'selected="selected"' : ''?>>Australian</option>

                        <option value="br" <?=$fakeaboutme_language == 'br' ? 'selected="selected"' : ''?>>Brazil</option>

                        <option value="ch" <?=$fakeaboutme_language == 'ch' ? 'selected="selected"' : ''?>>Chinese</option>

                        <option value="zhtw" <?=$fakeaboutme_language == 'zhtw' ? 'selected="selected"' : ''?>>Chinese (Traditional)</option>

                        <option value="hr" <?=$fakeaboutme_language == 'hr' ? 'selected="selected"' : ''?>>Croatian</option>

                        <option value="cs" <?=$fakeaboutme_language == 'cs' ? 'selected="selected"' : ''?>>Czech</option>

                        <option value="dk" <?=$fakeaboutme_language == 'dk' ? 'selected="selected"' : ''?>>Danish</option>

                        <option value="nl" <?=$fakeaboutme_language == 'nl' ? 'selected="selected"' : ''?>>Dutch</option>

                        <option value="en" <?=$fakeaboutme_language == 'en' ? 'selected="selected"' : ''?>>England/Wales</option>

                        <option value="er" <?=$fakeaboutme_language == 'er' ? 'selected="selected"' : ''?>>Eritrean</option>

                        <option value="fi" <?=$fakeaboutme_language == 'fi' ? 'selected="selected"' : ''?>>Finnish</option>

                        <option value="fr" <?=$fakeaboutme_language == 'fr' ? 'selected="selected"' : ''?>>French</option>

                        <option value="gr" <?=$fakeaboutme_language == 'gr' ? 'selected="selected"' : ''?>>German</option>

                        <option value="sp" <?=$fakeaboutme_language == 'sp' ? 'selected="selected"' : ''?>>Hispanic</option>

                        <option value="hobbit" <?=$fakeaboutme_language == 'hobbit' ? 'selected="selected"' : ''?>>Hobbit</option>

                        <option value="hu" <?=$fakeaboutme_language == 'hu' ? 'selected="selected"' : ''?>>Hungarian</option>

                        <option value="is" <?=$fakeaboutme_language == 'is' ? 'selected="selected"' : ''?>>Icelandic</option>

                        <option value="ig" <?=$fakeaboutme_language == 'ig' ? 'selected="selected"' : ''?>>Igbo</option>

                        <option value="it" <?=$fakeaboutme_language == 'it' ? 'selected="selected"' : ''?>>Italian</option>

                        <option value="jpja" <?=$fakeaboutme_language == 'jpja' ? 'selected="selected"' : ''?>>Japanese</option>

                        <option value="jp" <?=$fakeaboutme_language == 'jp' ? 'selected="selected"' : ''?>>Japanese (Anglicized)</option>

                        <option value="ninja" <?=$fakeaboutme_language == 'ninja' ? 'selected="selected"' : ''?>>Ninja</option>

                        <option value="no" <?=$fakeaboutme_language == 'no' ? 'selected="selected"' : ''?>>Norwegian</option>

                        <option value="fa" <?=$fakeaboutme_language == 'fa' ? 'selected="selected"' : ''?>>Persian</option>

                        <option value="pl" <?=$fakeaboutme_language == 'pl' ? 'selected="selected"' : ''?>>Polish</option>

                        <option value="ru" <?=$fakeaboutme_language == 'ru' ? 'selected="selected"' : ''?>>Russian</option>

                        <option value="rucyr" <?=$fakeaboutme_language == 'urcyr' ? 'selected="selected"' : ''?>>Russian (Cyrillic)</option>

                        <option value="gd" <?=$fakeaboutme_language == 'gd' ? 'selected="selected"' : ''?>>Scottish</option>

                        <option value="sl" <?=$fakeaboutme_language == 'sl' ? 'selected="selected"' : ''?>>Slovenian</option>

                        <option value="sw" <?=$fakeaboutme_language == 'sw' ? 'selected="selected"' : ''?>>Swedish</option>

                        <option value="vn" <?=$fakeaboutme_language == 'vn' ? 'selected="selected"' : ''?>>Vietnamese</option>

                  	</select>

            	</td>

			</tr>   

            <tr class="form-field">

				<td><?=_e('Genre','fakeaboutme')?></td>

				<td><select id="fakeaboutme_genre" name="fakeaboutme_genre">

                		<option value="0" <?= $fakeaboutme_genre == 0 ? 'selected="selected"' : '' ?> ><?=_e('Random','fakeaboutme')?></option>

                        <option value="1" <?= $fakeaboutme_genre == 1 ? 'selected="selected"' : '' ?> ><?=_e('Male','fakeaboutme')?></option>

                        <option value="2" <?= $fakeaboutme_genre == 2 ? 'selected="selected"' : '' ?> ><?=_e('Female','fakeaboutme')?></option>

                    </select>

            	</td>

			</tr> 

		</table>

        
		
        <p class="submit"><input type="button" name="fakeaboutme_btn_settings" id="fakeaboutme_btn_settings" class="button-primary" value="Get Fake Identity"></p>
        <p><a target="_blank" href="<?=admin_url('options-general.php?page=fake-about-me')?>"><?=_e('settings','fakeaboutme')?></a></p>
        <p>This product uses the Fake Name Generator API but is not endorsed or certified by the Fake Name Generator.</p>
		<?php
	}

	 
	
	
	

	

	/*

	 * metadata

	 */

	function create_metada_identity(){
		global $post;		
		//Load metadata
		$data = get_post_meta($post->ID, 'fakeaboutme_metadata', true);
		
		//Load Test DATA
		///$data = $this->fakeLoad(null);
		
		wp_nonce_field(__FILE__, 'fakeboutme_save_nonce');
		$this->drawIdentityTable($data);
	}

	 

	 

	

	

	

	

	/**

	*	Convert xml to array Identity

	*/

	function fakeLoad($xmlData=null){
		//Testig data
		if($xmlData == null){
			$xmlData = '<?xml version="1.0" encoding="UTF-8"?>

			<get_identity generator="zend" version="1.0"><identity><full_name><name>full_name</name><label>Name</label><value>Lucila R. Hesse</value></full_name><given_name><name>given_name</name><label>Given Name</label><value>Lucila</value></given_name><middle_name><name>middle_name</name><label>Middle Name</label><value>R</value></middle_name><surname><name>surname</name><label>Surname</label><value>Hesse</value></surname><maiden_name><name>maiden_name</name><label>Maiden Name</label><value>Aitken</value></maiden_name><gender><name>gender</name><label>Gender</label><value>female</value></gender><email_address><name>email_address</name><label>Email Address</label><value>LucilaRHesse@spambob.com</value></email_address><street1><name>street1</name><label>Address</label><value>Avda. Explanada Barnuevo, 78</value></street1><street2><name>street2</name><label></label><value>35280 Ingenio</value></street2><street3><name>street3</name><label></label><value></value></street3><house_number><name>house_number</name><label>House Number</label><value>78</value></house_number><street><name>street</name><label>Street</label><value>Avda. Explanada Barnuevo</value></street><city><name>city</name><label>City</label><value>Ingenio</value></city><state><name>state</name><label>State</label><value>Las Palmas</value></state><zip><name>zip</name><label>Postal Code</label><value>35280</value></zip><country_code><name>country_code</name><label>Country Code</label><value>ES</value></country_code><phone_number><name>phone_number</name><label>Phone Number</label><value>928 255 976</value></phone_number><birthday><name>birthday</name><label>Birthday</label><value>12/10/1946</value></birthday><occupation><name>occupation</name><label>Occupation</label><value>Administrative law judge</value></occupation><password><name>password</name><label>Password</label><value>yeeph9jo5R</value></password><domain><name>domain</name><label>Domain</label><value>FamousWins.com</value></domain><cc_type><name>cc_type</name><label>Credit Card Type</label><value>MasterCard</value></cc_type><cc_number><name>cc_number</name><label>Credit Card Number</label><value>5179244499219747</value></cc_number><cc_exp_month><name>cc_exp_month</name><label>Credit Card Expiration Month</label><value>3</value></cc_exp_month><cc_exp_year><name>cc_exp_year</name><label>Credit Card Expiration Year</label><value>2015</value></cc_exp_year><cc_cvv><name>cc_cvv</name><label>Credit Card CVV</label><value>093</value></cc_cvv><national_id><name>national_id</name><label>National ID</label><value></value></national_id><national_id_type><name>national_id_type</name><label>National ID Type</label><value></value></national_id_type><blood_type><name>blood_type</name><label>Blood Type</label><value>B+</value></blood_type><weight_kilograms><name>weight_kilograms</name><label>Weight (Kilograms)</label><value>102.0</value></weight_kilograms><weight_pounds><name>weight_pounds</name><label>Weight (Pounds)</label><value>224.4</value></weight_pounds><height_centimeters><name>height_centimeters</name><label>Height (Centimeters)</label><value>164</value></height_centimeters><height_inches><name>height_inches</name><label>Height (Inches)</label><value>65</value></height_inches><ups_tracking_number><name>ups_tracking_number</name><label>UPS Tracking Number</label><value>1Z 818 473 08 1938 974 9</value></ups_tracking_number></identity><status>success</status></get_identity>';

		}

  
  	  	$xml = new SimpleXMLElement($xmlData);
		if($xml->status != 'success') 
			return 'Error with api Key or service is down';


		$data = array();
		$data['fakeaboutme_full_name'] = $xml->identity->full_name->value;
		//$data['fakeaboutme_given_name'] = $xml->identity->given_name->value;
		//$data['fakeaboutme_middle_name'] = $xml->identity->middle_name->value;
		//$data['fakeaboutme_surname'] = $xml->identity->surname->value;
		//$data['fakeaboutme_maiden_name'] = $xml->identity->maiden_name->value;
		$data['fakeaboutme_gender'] = $xml->identity->gender->value;
		$data['fakeaboutme_email'] = $xml->identity->email_address->value;
		$data['fakeaboutme_address'] = $xml->identity->street->value;
		$data['fakeaboutme_city'] = $xml->identity->city->value;
		$data['fakeaboutme_state'] = $xml->identity->state->value;
		$data['fakeaboutme_zip'] = $xml->identity->zip->value;
		//$data['fakeaboutme_country_code'] = $xml->identity->country_code->value;
		$data['fakeaboutme_phone'] = $xml->identity->phone_number->value;
		$data['fakeaboutme_birthday'] = $xml->identity->birthday->value;
		$data['fakeaboutme_occupation'] = $xml->identity->occupation->value;
		$data['fakeaboutme_domain'] = $xml->identity->domain->value;

		//$data['fakeaboutme_blood_type'] = $xml->identity->blood_type->value;
		//$data['fakeaboutme_weight_kilograms'] = $xml->identity->weight_kilograms->value;
		//$data['fakeaboutme_weight_pounds'] = $xml->identity->weight_pounds->value;
		//$data['fakeaboutme_height_centimeters'] = $xml->identity->height_centimeters->value;
		//$data['fakeaboutme_height_inches'] = $xml->identity->height_inches->value;

		return $data;
	}

	

	

	

	

	/*
	*	Draw Identity Table
	*	Receive arrayData
	*/
	function drawIdentityTable($data){
		?>
        <table id="fab_form_identity" class="form-table">
        	<tr class="form-field">
				<td width="100px;"><?=_e('Full Name','fakeaboutme')?></td>
				<td><input id="fakeaboutme_full_name" name="fakeaboutme_full_name" type="text" class="widefat" value="<?=$data['fakeaboutme_full_name']?>" autocomplete="off" /></td>
			</tr>
            <tr class="form-field">
				<td><?=_e('Gender','fakeaboutme')?></td>
				<td><input id="fakeaboutme_gender" name="fakeaboutme_gender" type="text" class="widefat" value="<?=$data['fakeaboutme_gender']?>" autocomplete="off" /></td>
			</tr>
            <tr class="form-field">
				<td><?=_e('Email','fakeaboutme')?></td>
				<td><input id="fakeaboutme_email" name="fakeaboutme_email" type="text" class="widefat" value="<?=$data['fakeaboutme_email']?>" autocomplete="off" /></td>
			</tr>
            <tr class="form-field">
				<td><?=_e('Birthday','fakeaboutme')?></td>
				<td><input id="fakeaboutme_birthday" name="fakeaboutme_birthday" type="text" class="widefat" value="<?=$data['fakeaboutme_birthday']?>" autocomplete="off" /></td>
			</tr>
            <tr class="form-field">
				<td><?=_e('Occupation','fakeaboutme')?></td>
				<td><input id="fakeaboutme_occupation" name="fakeaboutme_occupation" type="text" class="widefat" value="<?=$data['fakeaboutme_occupation']?>" autocomplete="off" />
                </td>
			</tr>
			<tr class="form-field">
				<td><?=_e('Domain','fakeaboutme')?></td>
				<td><input id="fakeaboutme_domain" name="fakeaboutme_domain" type="text" class="widefat" value="<?=$data['fakeaboutme_domain']?>" autocomplete="off" /></td>
			</tr>
            <tr class="form-field">
				<td><?=_e('Address','fakeaboutme')?></td>
				<td><input id="fakeaboutme_address" name="fakeaboutme_address" type="text" class="widefat" value="<?=$data['fakeaboutme_address']?>" autocomplete="off" /></td>
			</tr>
            <tr class="form-field">
				<td><?=_e('City','fakeaboutme')?></td>
				<td><input id="fakeaboutme_city" name="fakeaboutme_city" type="text" class="widefat" value="<?=$data['fakeaboutme_city']?>" autocomplete="off" /></td>
			</tr>
            <tr class="form-field">
				<td><?=_e('State','fakeaboutme')?></td>
				<td><input id="fakeaboutme_state" name="fakeaboutme_state" type="text" class="widefat" value="<?=$data['fakeaboutme_state']?>" autocomplete="off" /></td>
			</tr>
            <tr class="form-field">
				<td><?=_e('Zip','fakeaboutme')?></td>
				<td><input id="fakeaboutme_zip" name="fakeaboutme_zip" type="text" class="widefat" value="<?=$data['fakeaboutme_zip']?>" autocomplete="off" /></td>
			</tr>
            <tr class="form-field">
				<td><?=_e('Phone','fakeaboutme')?></td>
				<td><input id="fakeaboutme_phone" name="fakeaboutme_phone" type="text" class="widefat" value="<?=$data['fakeaboutme_phone']?>" autocomplete="off" /></td>
			</tr>
		</table>
        <?
	}

	 
	 
	 
	/**
	*	SAVE FUNCTION
	*/
	function save($post_id, $post){
		// do nothing on autosave
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
			return;
		}

		// verify the hidden fields for secirity reasons 
		if($_POST && !wp_verify_nonce($_POST['fakeboutme_save_nonce'], __FILE__)){
			return;
		}

	 	//Save Identity 		
		$data = array(
			//Settings
			'fakeaboutme_language'    => $_POST['fakeaboutme_language']
			,'fakeaboutme_genre'      => $_POST['fakeaboutme_genre']	
			
			//Identity			
			,'fakeaboutme_full_name'  => $_POST['fakeaboutme_full_name']
			,'fakeaboutme_gender'     => $_POST['fakeaboutme_gender']
			,'fakeaboutme_email'      => $_POST['fakeaboutme_email']
			,'fakeaboutme_birthday'   => $_POST['fakeaboutme_birthday']
			,'fakeaboutme_occupation' => $_POST['fakeaboutme_occupation']
			,'fakeaboutme_domain'     => $_POST['fakeaboutme_domain']
			,'fakeaboutme_address'    => $_POST['fakeaboutme_address']
			,'fakeaboutme_city'       => $_POST['fakeaboutme_city']
			,'fakeaboutme_state'      => $_POST['fakeaboutme_state']
			,'fakeaboutme_zip'        => $_POST['fakeaboutme_zip']
			,'fakeaboutme_phone'      => $_POST['fakeaboutme_phone']
		);			 
		update_post_meta($post->ID, 'fakeaboutme_metadata', $data);
	}

 

   

}