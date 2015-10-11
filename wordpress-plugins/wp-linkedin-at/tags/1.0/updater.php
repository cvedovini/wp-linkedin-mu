<?php
define('WP_LINKEDIN_AT_STORE_URL', 'http://vedovini.net' );
define('WP_LINKEDIN_AT_ITEM_NAME', 'WP LinkedIn Advanced Templates' );
define('WP_LINKEDIN_AT_AUTHOR', 'Claude Vedovini');


if (!class_exists('EDD_SL_Plugin_Updater')) {
	include( dirname( __FILE__ ) . '/lib/EDD_SL_Plugin_Updater.php' );
}


// retrieve our license key from the DB
$wp_linkedin_at_license_key = trim(get_option('wp-linkedin-at_license_key'));

// setup the updater
$wp_linkedin_at_updater = new EDD_SL_Plugin_Updater(WP_LINKEDIN_AT_STORE_URL,
	'wp-linkedin-at/wp-linkedin-at.php', array(
		'version' 	=> WP_LINKEDIN_AT_VERSION, 		// current version number
		'license' 	=> $wp_linkedin_at_license_key, // license key (used get_option above to retrieve from DB)
		'item_name' => WP_LINKEDIN_AT_ITEM_NAME, 	// name of this plugin
		'author' 	=> WP_LINKEDIN_AT_AUTHOR,  		// author of this plugin
		'url'       => home_url()
));


function wp_linkedin_at_license_menu() {
	add_settings_section('licensing', __('License Keys', 'wp-linkedin-at'), false, 'wp-linkedin');
	register_setting('wp-linkedin', 'wp-linkedin-at_license_key', 'wp_linkedin_at_sanitize_license');
	add_settings_field('wp-linkedin-at_license_key', __('Advanced templates', 'wp-linkedin-at'),
			'wp_linkedin_at_license_key_field', 'wp-linkedin', 'licensing');
}
add_action('admin_menu', 'wp_linkedin_at_license_menu', 30);


function wp_linkedin_at_license_key_field() {
	$license = get_option('wp-linkedin-at_license_key');
	$status = get_option('wp-linkedin-at_license_status'); ?>
	<p><input id="wp-linkedin-at_license_key" name="wp-linkedin-at_license_key" type="text" class="regular-text" value="<?php esc_attr_e($license); ?>" />
	<?php if ($status !== false && $status == 'valid') { ?>
		<span style="color:green;"><?php _e('Active', 'wp-linkedin-at'); ?></span>
		<?php wp_nonce_field('wp_linkedin_at_nonce', 'wp_linkedin_at_nonce'); ?>
		<input type="submit" class="button-secondary" name="wp_linkedin_at_license_deactivate" value="<?php _e('Deactivate License', 'wp-linkedin-at'); ?>"/>
	<?php } else {
		wp_nonce_field('wp_linkedin_at_nonce', 'wp_linkedin_at_nonce'); ?>
		<input type="submit" class="button-secondary" name="wp_linkedin_at_license_activate" value="<?php _e('Activate License', 'wp-linkedin-at'); ?>"/>
	<?php } ?></p><?php
}


function wp_linkedin_at_sanitize_license($new) {
	$old = get_option('wp-linkedin-at_license_key');

	if ($old && $old != $new) {
		delete_option('wp-linkedin-at_license_status'); // new license has been entered, so must reactivate
	}

	return $new;
}


function wp_linkedin_at_activate_license() {
	// listen for our activate button to be clicked
	if (isset( $_POST['wp_linkedin_at_license_activate'])) {
		// run a quick security check
	 	if (!check_admin_referer('wp_linkedin_at_nonce', 'wp_linkedin_at_nonce'))
			return; // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = trim(get_option('wp-linkedin-at_license_key'));

		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'activate_license',
			'license' 	=> $license,
			'item_name' => urlencode(WP_LINKEDIN_AT_ITEM_NAME)
		);

		// Call the custom API.
		$response = wp_remote_get(add_query_arg($api_params, WP_LINKEDIN_AT_STORE_URL), array('timeout' => 15, 'sslverify' => false));

		// make sure the response came back okay
		if (is_wp_error($response))
			return false;

		// decode the license data
		$license_data = json_decode(wp_remote_retrieve_body($response));

		// $license_data->license will be either "active" or "inactive"
		update_option('wp-linkedin-at_license_status', $license_data->license);

	}
}
add_action('admin_menu', 'wp_linkedin_at_activate_license');


function wp_linkedin_at_deactivate_license() {
	// listen for our activate button to be clicked
	if (isset($_POST['wp_linkedin_at_license_deactivate'])) {
		// run a quick security check
	 	if(!check_admin_referer('wp_linkedin_at_nonce', 'wp_linkedin_at_nonce'))
			return; // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = trim(get_option('wp-linkedin-at_license_key'));

		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'deactivate_license',
			'license' 	=> $license,
			'item_name' => urlencode(WP_LINKEDIN_AT_ITEM_NAME)
		);

		// Call the custom API.
		$response = wp_remote_get(add_query_arg($api_params, WP_LINKEDIN_AT_STORE_URL), array('timeout' => 15, 'sslverify' => false));

		// make sure the response came back okay
		if (is_wp_error($response))
			return false;

		// decode the license data
		$license_data = json_decode(wp_remote_retrieve_body($response));

		// $license_data->license will be either "deactivated" or "failed"
		if ($license_data->license == 'deactivated')
			delete_option('wp-linkedin-at_license_status');

	}
}
add_action('admin_menu', 'wp_linkedin_at_deactivate_license');
