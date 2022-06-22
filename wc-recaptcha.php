<?php
/*
Plugin Name:	WooCommerce reCAPTCHA
Plugin URI:		https://progr.interplanety.org/en/wordpress-plugin-woocommerce-recapthca/
Version:		1.0.0
Author:			Nikita Akimov
Author URI:		https://progr.interplanety.org/en/
License:		GPL-3.0-or-later
Description:	Adds Google reCAPTCHA to WooCommerce login and registration forms
*/

//	not run directly
if(!defined('ABSPATH')) {
	exit;
}

// ---------- add .js for working with google api

// add Google reCAPTCHA .js only on login page
function wc_recaptcha_enqueue_script() {
	// if this is page with 'woocommerce_my_account' or 'woocommerce_checkout' shortcode
	global $post;
	if(is_page() && $post 
			&& (has_shortcode($post->post_content, 'woocommerce_my_account') || has_shortcode($post->post_content, 'woocommerce_checkout'))) {
		wp_enqueue_script('recaptcha', '//www.google.com/recaptcha/api.js', '', '', true);
	}
}
add_action('wp_enqueue_scripts', 'wc_recaptcha_enqueue_script');

// ---------- reCAPTCHA - common function for validate Google reCAPTCHA with site secret

function wc_recapthca_check_secret($recaptcha_first_responce) {
	// check reCAPTCHA with secret
	$rez = false;
	if($recaptcha_first_responce) {
		$plugin_options = get_option('wc_recaptcha_plugin_options');
		$recaptcha_secret = $plugin_options['wc_recaptcha_secret_key'];
		$response = wp_remote_get('https://www.google.com/recaptcha/api/siteverify?secret=' . $recaptcha_secret . '&response=' . $recaptcha_first_responce);
		$response_code = wp_remote_retrieve_response_code($response);
		if($response_code == 200) {
			$response = json_decode($response['body'], true);
			// OK or Fail
			$rez = ($response['success'] ? true : false);
		}
	}
	return $rez;
}

// ---------- reCAPTCHA - registration

// embed reCAPTCHA (register form)

function wc_recaptcha_embed_register() {
	// embed reCAPTCHA to register form
	$plugin_options = get_option('wc_recaptcha_plugin_options');
	$recaptcha_site_key = $plugin_options['wc_recaptcha_site_key'];

	echo '<p id="recaptcha" class="g-recaptcha" data-sitekey="'.$recaptcha_site_key.'"></p>';
}

add_action('woocommerce_register_form', 'wc_recaptcha_embed_register', 15);

// validate reCAPTCHA (register form)

function wc_recaptcha_check_register($errors, $username, $email) {
	if(isset($_POST['g-recaptcha-response']) && $_POST['g-recaptcha-response']) {
		// check with secret
		$check_secret = wc_recapthca_check_secret($_POST['g-recaptcha-response']);
		if($check_secret){
			// OK
			return $errors;
		}
	}
	// reCAPTCHA faild - generate Error
	return new WP_Error('Captcha Faild', __('Bot auth protection by Google reCAPTCHA'));
}

add_filter('woocommerce_registration_errors', 'wc_recaptcha_check_register', 10, 3);

// ---------- reCAPTCHA - login

// embed reCAPTCHA (login form)

function wc_recaptcha_embed_login() {
	// embed reCAPTCHA to login form
	$plugin_options = get_option('wc_recaptcha_plugin_options');
	$recaptcha_site_key = $plugin_options['wc_recaptcha_site_key'];

	echo '<p id="recaptcha" class="g-recaptcha" data-sitekey="'.$recaptcha_site_key.'"></p>';
}

add_action('woocommerce_login_form', 'wc_recaptcha_embed_login', 15);

// validate reCAPTCHA (login form)

function wc_recaptcha_check_login($user, $password) {
	// check Google reCAPTCHA on Login form
	if(isset($_POST['g-recaptcha-response']) && $_POST['g-recaptcha-response']) {
		// check with secret
		$check_secret = wc_recapthca_check_secret($_POST['g-recaptcha-response']);
		if($check_secret){
			// OK
			return $user;
		}
	}
	// reCAPTCHA faild - generate Error
	return new WP_Error('Captcha Faild', __('Bot auth protection by Google reCAPTCHA'));
}

add_filter('wp_authenticate_user', 'wc_recaptcha_check_login', 10, 3);

// ---------- reCAPTCHA - lost password

// embed reCAPTCHA (lost password form)

function wc_recaptcha_embed_lost_password() {
	// embed reCAPTCHA to lost password form
	$plugin_options = get_option('wc_recaptcha_plugin_options');
	$recaptcha_site_key = $plugin_options['wc_recaptcha_site_key'];

	echo '<p id="recaptcha" class="g-recaptcha" data-sitekey="'.$recaptcha_site_key.'"></p>';
}

add_action('woocommerce_lostpassword_form', 'wc_recaptcha_embed_lost_password', 15);

// validate reCAPTCHA (lost password form)

function wc_recaptcha_check_lost_password($errors, $user_id) {
	// check Google reCAPTCHA on Lost Password form
	if(isset($_POST['g-recaptcha-response']) && $_POST['g-recaptcha-response']) {
		// check with secret
		$check_secret = wc_recapthca_check_secret($_POST['g-recaptcha-response']);
		var_dump($check_secret);
		if($check_secret){
			// OK
			return $errors;
		}
	}
	// reCAPTCHA faild - generate Error
	return new WP_Error('Captcha Faild', __('Bot auth protection by Google reCAPTCHA'));
}

add_filter('allow_password_reset', 'wc_recaptcha_check_lost_password', 10, 3);


// ---------- reCAPTCHA - checkout (validation is not required - the same with register form)

// embed reCAPTCHA - checkout form

function wc_recaptcha_embed_checkout() {
	// embed reCAPTCHA to checkout form
	$plugin_options = get_option('wc_recaptcha_plugin_options');
	$recaptcha_site_key = $plugin_options['wc_recaptcha_site_key'];

	echo '<p id="recaptcha" class="g-recaptcha" data-sitekey="'.$recaptcha_site_key.'"></p>';
}

add_action('woocommerce_after_checkout_registration_form', 'wc_recaptcha_embed_checkout', 15);


// ---------- settings menu

function wc_recaptcha_add_options_page() {
	// function to render the options page
	add_options_page(
		'WC reCAPTCHA',		// page title text
		'WC reCAPTCHA',		// menu item text
		'manage_options',	// user rights
		'wc-recaptcha/wc-recaptcha-options.php'		// file to render the options view page
	);
}

add_action('admin_menu',  'wc_recaptcha_add_options_page');


// ---------- page

function wc_recaptcha_register_settings() {
	// function to register plugin options
    // whole plugin settings
	register_setting(
		'wc_recaptcha_plugin_options',	// option group
		'wc_recaptcha_plugin_options'	// option name
	);
	// Google reCAPTCHA section
    add_settings_section(
		'google_recaptcha_api',			// id
		'Gooble reCAPTCHA',				// title
		'wc_recaptcha_section_text',	// function name for render section title
		'wc_recaptcha_plugin'			// page id for do_settings_section
	);
	// options fields
	// site key
    add_settings_field(
		'wc_recaptcha_site_key',	// id
		'Site Key',					// field title
		'wc_recapthca_site_key',	// function name for rendering this option
		'wc_recaptcha_plugin',		// menu page id
		'google_recaptcha_api'		// section id
	);
	// secret key
    add_settings_field(
		'wc_recaptcha_secret_key',	// id
		'Secret Key',				// field title
		'wc_recapthca_secret_key',	// function name for rendering this option
		'wc_recaptcha_plugin',		// menu page id
		'google_recaptcha_api'		// section id
	);
}
// function for render section title
function wc_recaptcha_section_text() {
	echo 'Google reCAPTCHA settings:';
}
// functions for current option render
function wc_recapthca_site_key() {
    $options = get_option('wc_recaptcha_plugin_options');
    echo '<input id="' . 'wc_recaptcha_site_key" name="'.'wc_recaptcha_plugin_options['.'wc_recaptcha_site_key]" type="text"
		value="' . esc_attr($options['wc_recaptcha_site_key']) . '" style="width: 90%;">';
}
function wc_recapthca_secret_key() {
    $options = get_option('wc_recaptcha_plugin_options');
    echo '<input id="' . 'wc_recaptcha_secret_key" name="'.'wc_recaptcha_plugin_options['.'wc_recaptcha_secret_key]" type="text"
		value="' . esc_attr($options['wc_recaptcha_secret_key']) . '" style="width: 90%;">';
}

add_action('admin_init',  'wc_recaptcha_register_settings');


// ---------- "settings" link for plugin on plugins page

function wc_recaptcha_settings_link($links) {
    return array_merge(
		array(
			'settings' => '<a href="options-general.php?page=wc-recaptcha/wc-recaptcha-options.php">' . __('Settings') . '</a>'
		),
		$links
	);
}

add_filter('plugin_action_links_' . plugin_basename( __FILE__ ),  'wc_recaptcha_settings_link');

?>
