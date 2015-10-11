<?php

class WPLinkedInMUAdmin {

	function __construct() {
		if (function_exists('wp_linkedin_connection')) {
			$this->linkedin = wp_linkedin_connection();
			add_action('admin_menu', array(&$this, 'admin_menu'), 20);
			add_action('show_user_profile', array(&$this, 'show_user_profile_section'));
			add_action('personal_options_update', array(&$this, 'save_user_profile_section'));
		}

		add_filter('plugin_action_links_' . WP_LINKEDIN_MU_PLUGIN_BASENAME, array(&$this, 'add_settings_link'));
		add_action('admin_notices', array(&$this, 'admin_notices'));
		add_action('network_admin_notices', array(&$this, 'admin_notices'));
	}

	function admin_menu() {
		// Remove the "send mail" option from the general settings so we can have our own, per-user, one
		// not working: unregister_setting('wp-linkedin', 'wp-linkedin_sendmail_on_token_expiry');
		global $new_whitelist_options, $wp_settings_fields;
		$pos = array_search('wp-linkedin_sendmail_on_token_expiry', (array) $new_whitelist_options['wp-linkedin']);
		if ($pos !== false) unset($new_whitelist_options['wp-linkedin'][$pos] );
		unset($wp_settings_fields['wp-linkedin']['default']['wp-linkedin_sendmail_on_token_expiry']);

		$this->add_settings_field('wp-linkedin-mu_default_user',
				__('Default user', 'wp-linkedin-mu'), 'settings_field_default_user');
		$this->add_settings_field('wp-linkedin-mu_connect_with_linkedin',
				_x('Connect with LinkedIn', 'On the settings page', 'wp-linkedin-mu'), 'settings_field_connect_with_linkedin');
		$this->add_settings_field('wp-linkedin-mu_default_user_role',
				__('Default user role', 'wp-linkedin-mu'), 'settings_field_default_user_role');
	}

	function add_settings_link($links) {
		$url = admin_url('options-general.php?page=wp-linkedin');
		$links['settings'] = '<a href="' . $url . '">' . __('Settings') . '</a>';
		return $links;
	}

	function add_settings_field($id, $title, $callback) {
		register_setting('wp-linkedin', $id);
		add_settings_field($id, $title, array(&$this, $callback), 'wp-linkedin');
	}

	function show_user_profile_section() {
		if (isset($_GET['clear_cache'])) {
			$this->linkedin->clear_cache(); ?>
			<div class="updated"><p><strong><?php _e('The cache has been cleared.', 'wp-linkedin'); ?></strong></p></div><?php
		} ?>
		<h3><?php _e("LinkedIn Options", "wp-linkedin-mu"); ?></h3>

		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<?php _e('Send mail on token expiry', 'wp-linkedin-mu'); ?>
				</th>
				<td>
					<label><input type="checkbox" name="wp-linkedin_sendmail_on_token_expiry"
						value="1" <?php checked(get_user_option('wp-linkedin_sendmail_on_token_expiry')); ?> />&nbsp;
						<?php _e('Check this option if you want the plugin to send you an email when the token has expired or is invalid.', 'wp-linkedin-mu') ?></label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"></th>
				<td>
					<span class="submit"><a href="<?php echo $this->linkedin->get_authorization_url(); ?>" class="button button-primary"><?php _e('Regenerate LinkedIn Access Token', 'wp-linkedin-mu'); ?></a></span>
					<span class="submit"><a href="<?php echo admin_url('profile.php?clear_cache'); ?>" class="button button-primary"><?php _e('Clear the Cache', 'wp-linkedin-mu'); ?></a></span>
				</td>
			</tr>
		</table><?php
	}

	function save_user_profile_section($user_id) {
		if (!current_user_can('edit_user', $user_id)) return false;
		if (isset($_POST['wp-linkedin_sendmail_on_token_expiry'])) {
			update_user_option($user_id, 'wp-linkedin_sendmail_on_token_expiry',
					$_POST['wp-linkedin_sendmail_on_token_expiry']);
		}
	}

	function settings_field_default_user() {
		$default_user = get_option('wp-linkedin-mu_default_user');
		$users = get_users(); ?>
		<select id="wp-linkedin-mu_default_user" name="wp-linkedin-mu_default_user">
			<option value="0" <?php selected($default_user, '0'); ?>><?php _e('None', 'wp-linkedin'); ?></option>
			<?php foreach($users as $user): ?>
				<option value="<?php echo $user->ID; ?>" <?php selected($default_user, $user->ID); ?>><?php echo $user->display_name; ?></option>
			<?php endforeach; ?>
		</select>
		<p><em><?php _e('Select the user profile to use when the plugin cannot determine whose data to show.', 'wp-linkedin-mu'); ?></em></p><?php
	}

	function settings_field_connect_with_linkedin() {
		$connect_with_linkedin = get_option('wp-linkedin-mu_connect_with_linkedin'); ?>
		<label><input type="checkbox" name="wp-linkedin-mu_connect_with_linkedin"
			value="1" <?php checked($connect_with_linkedin); ?> />&nbsp;
			<?php _e('Check this option to allow users to register and log in using LinkedIn.', 'wp-linkedin') ?></label><?php
	}

	function settings_field_default_user_role() {
		$default_user_role = get_option('wp-linkedin-mu_default_user_role'); ?>
		<select id="wp-linkedin-mu_default_user_role" name="wp-linkedin-mu_default_user_role">
   			<?php wp_dropdown_roles($default_user_role); ?>
		</select>
		<p><em><?php _e('Select the role that will be assigned to the users the first time they log in using LinkedIn.', 'wp-linkedin-mu'); ?></em></p><?php
	}

	function admin_notices() {
		if (current_user_can('install_plugins')) {
			if (!function_exists('wp_linkedin_connection')): ?>
				<div class="error"><p><?php _e('The WP LinkedIn Multi-Users plugin needs the WP LinkedIn plugin to be installed and activated.', 'wp-linkedin-mu'); ?></p></div>
			<?php elseif (version_compare(WP_LINKEDIN_VERSION, '2.3') < 0):
				$format = __('The WP LinkedIn Multi-Users plugin requires at least version %s of the WP-LinkedIn plugin, current installed version is %s', 'wp-linkedin-mu');
				$error = sprintf($format, '2.3', WP_LINKEDIN_VERSION); ?>
				<div class="error"><p><?php echo $error; ?></p></div>
			<?php endif;
		}
	}

}