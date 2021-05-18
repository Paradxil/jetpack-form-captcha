<?php
/**
 * Plugin Name: Icon Captcha for Jetpack
 * Plugin URI: 
 * Description: Adds a captcha to jetpack forms
 * Version: 0.0.2
 * Author: Hunter Stratton
 * Author URI: https://hunterstratton.com
 * Text Domain: jetpack-form-captcha
 * License: MIT
 * 
 */
 
require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/Paradxil/jetpack-form-captcha',
	__FILE__,
	'jetpack-form-captcha'
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

/**
 * Detect plugin. For use on Front End only.
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

// check for plugin using plugin name
if (!is_plugin_active( 'jetpack/jetpack.php' ) ) {
    //plugin is activated
    return;
} 

include_once(ABSPATH . 'wp-content/plugins/jetpack/modules/contact-form/grunion-contact-form.php');

//Check if the page has a contact form
// POST handler
if (
	isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' == strtoupper( $_SERVER['REQUEST_METHOD'] )
	&&
	isset( $_POST['action'] ) && 'grunion-contact-form' == $_POST['action']
	&&
	isset( $_POST['contact-form-id'] )
) {
    //Add custom action to check the captcha and then call grunion process_form_submission
	add_action( 'template_redirect', 'jfc_process_captcha_form_submission');
}


function jfc_init() {
    //Load the captcha api from the captcha server
    wp_register_script( 'captcha-api', 'https://iconcaptcha.com/captcha/api.js', null, null, true );
    wp_enqueue_script('captcha-api');
    
    //Load the captcha block
    wp_register_script(
        'captcha-block-script',
        plugins_url( 'captcha-block.js', __FILE__ )
    );
 
    //Register a custom captcha block.
    //This block will only be shown when editing jetpack forms.
    register_block_type( 'jetpack/captcha', array(
        'api_version' => 2,
        'editor_script' => 'captcha-block-script'
    ) );
}
add_action('init', 'jfc_init');


/**
 * Deactivation hook.
 */
function jfc_deactivate() {
    add_action('template_redirect', array(Grunion_Contact_Form_Plugin::init(), 'process_form_submission' ));
	remove_action( 'template_redirect', 'jfc_process_captcha_form_submission');
}
register_deactivation_hook( __FILE__, 'jfc_deactivate' );


function jfc_loaded() {
    //Remove the default grunion redirect action.
    //We will call it manually later.
    remove_action('template_redirect', array(Grunion_Contact_Form_Plugin::init(), 'process_form_submission' ));
}
add_action( 'wp_loaded', 'jfc_loaded' );


function jfc_process_captcha_form_submission() {
    //Check if the captcha-id field is set
    if (isset($_POST['captcha-id'])) {
        $captchaid = $_POST['captcha-id'];
        
        //Contact the captcha server to verify that the captcha submission was valid
        if(jfc_is_captcha_valid($captchaid)) {
            //If it was valid call Grunion's process_form_submission 
            return Grunion_Contact_Form_Plugin::init()->process_form_submission();
        }
    }
    return false;
}


function jfc_is_captcha_valid($id) {
    //Send a POST request to the captcha server with the captcha id.
    //Each captcha can only be verified once.
    $url = 'https://iconcaptcha.com/captcha/verify/';
    $data = array('id' => $id);
    
    // use key 'http' even if you send the request to https://...
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) { 
        return false;
    }
    
    $res = json_decode($result, true);
    
    //If the captcha request was valid 'verified' will be set to true
    if(isset($res['verified'])) {
        if($res["verified"] === true) {
            return true;
        }
    }
    
    return false;
}
