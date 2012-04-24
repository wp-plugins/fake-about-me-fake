<?php



if (!defined('ABSPATH')) {

    die();

}





//DEFINE FAKEABOUTME PLUGIN CONSTANTS

define('FAKEABOUTME_INIT', true);

define('FAKEABOUTME_VERSION', '2.0.2');

 

define('FAKEABOUTME_DIR', realpath(dirname(__FILE__) . '/..'));

define('FAKEABOUTME_BASE', get_home_url() . '/wp-content/plugins/fake-about-me-fake');


define('FAKEABOUTME_LANG_DIR', 'fake-about-me-fake/languages');  // <-- important last slash


define('FAKEABOUTME_JS_DIR', FAKEABOUTME_BASE . '/js');

define('FAKEABOUTME_CSS_DIR', FAKEABOUTME_BASE . '/css');

define('FAKEABOUTME_INC_DIR', FAKEABOUTME_DIR . '/inc');





/***********************************

	CUSTOM PRINT_R FUNCTION

	BETTER FORMAT TO WATCH ARRAYS

*/

if ( ! function_exists('debug'))

{

    function debug($var = '')

    {

		echo '<pre style="background-color: yellow; color: black; border: 1px solid red; font-size: 11px;"><br />
<br />
';		



		if(is_array($var) || is_object($var) || gettype($var) == gettype(new stdClass()))

			var_dump($var);

		else

			echo "==> ". $var ." < ==";

			

		echo "</pre>";

    }   
}


if ( ! function_exists('fix_http'))

{
	function fix_http($url) {
	
		if(!(strpos($url, "http://") === 0) && !(strpos($url, "https://") === 0)) {
			$url = "http://".$url;
		}
		return $url;	
	}
}
 
