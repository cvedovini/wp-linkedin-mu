<?php
/*
Plugin Name: WP LinkedIn Multi-Users
Plugin URI: http://vedovini.net/plugins/?utm_source=wordpress&utm_medium=plugin&utm_campaign=wp-linkedin-mu
Description: This plugin is an extension to the WP-LinkedIn plugin that enables showing LinkedIn profiles, recommendations and network updates for any registered user.
Author: Claude Vedovini
Author URI: http://vedovini.net/?utm_source=wordpress&utm_medium=plugin&utm_campaign=wp-linkedin-mu
Version: 1.1
Text Domain: wp-linkedin-mu

# The code in this plugin is free software; you can redistribute the code aspects of
# the plugin and/or modify the code under the terms of the GNU Lesser General
# Public License as published by the Free Software Foundation; either
# version 3 of the License, or (at your option) any later version.

# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
# EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
# MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
# NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
# LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
# OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
# WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
#
# See the GNU lesser General Public License for more details.
*/

define('WP_LINKEDIN_MU_VERSION', '1.1');
include 'updater.php';


function wp_linkedin_mu_init() {
	add_filter('load_textdomain_mofile', 'wp_linkedin_mu_smarter_load_textdomain', 10, 2);
	load_plugin_textdomain('wp-linkedin-mu', false, dirname(plugin_basename(__FILE__)) . '/languages/' );
	add_shortcode('li_user', 'wp_linkedin_mu_set_user');
	add_shortcode('li_token_button', 'wp_linkedin_mu_token_button');
	add_filter('linkedin_connection', 'wp_linkedin_mu_connection');
}
add_action('init', 'wp_linkedin_mu_init');


function wp_linkedin_mu_admin_init() {
	require_once 'class-admin.php';
	$admin = new WPLinkedInMUAdmin();
}
add_action('admin_menu', 'wp_linkedin_mu_admin_init', 20);


function wp_linkedin_mu_set_user($atts, $content='') {
	$keys = array('id' => 'id', 'name' => 'login', 'email' => 'email');
	$user = false;

	foreach($keys as $k => $f) {
		if (isset($atts[$k])) {
			$v = $atts[$k];
			$user = get_user_by($f, $v);
			break;
		}
	}

	if (!isset($v)) {
		return sprintf(_('You must provide one of the following attributes for the li_user shortcode to work: %s.', 'wp-linkedin-mu'),
				implode(', ', array_keys($keys)));
	}

	if ($user) {
		$GLOBALS['li_user_id'] = $user->ID;
		if (WP_DEBUG) echo '<!-- for user: ' . $user->display_name . ' -->';
		$content = do_shortcode($content);
		unset($GLOBALS['li_user_id']);
		return $content;
	}

	return sprintf(__('Cannot find a user whose %1$s is %2$s', 'wp-linkedin-mu'), $k, $v);
}


function wp_linkedin_mu_token_button($atts=array()) {
	// Only if the user is currently logged-in
	if (!is_user_logged_in()) return;
	$GLOBALS['li_user_id'] = get_current_user_id();

	// Check version compatibility
	if (version_compare(WP_LINKEDIN_VERSION, '1.17') < 0) {
		$format = __('This shortcode requires at least version 1.17 of the WP-LinkedIn plugin, current installed version is %s', 'wp-linkedin-mu');
		$error = sprintf($format, WP_LINKEDIN_VERSION);
		return $before_error . $error . $after_error;
	}

	$atts = shortcode_atts(array(
			'button_label' => __('Regenerate LinkedIn Access Token', 'wp-linkedin'),
			'before_error' => '<p class="error">',
			'after_error' => '</p>',
			'before_success' => '<p class="info">',
			'after_success' => '</p>'
	), $atts, 'li_token_button');
	extract($atts);
	$linkedin = wp_linkedin_connection();
	$redirect_url = $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
	$redirect_url .= $_SERVER["SERVER_NAME"];
	if ($_SERVER['SERVER_PORT'] != '80') $redirect_url .= ':'.$_SERVER["SERVER_PORT"];
	$redirect_url .= $_SERVER["REQUEST_URI"];

	$split_url = explode('?', $redirect_url, 2);
	$redirect_url = $split_url[0];
	$params = array();
	parse_str($split_url[1], $params);
	unset($params['code']);
	unset($params['state']);
	$redirect_url = $redirect_url.'?'.http_build_query($params);
	$output = '';

	if (isset($_GET['code'])) {
		if (isset($_GET['state']) && $linkedin->check_state_token($_GET['state'])) {
			$retcode = $linkedin->set_access_token($_GET['code'], $redirect_url);

			if (!is_wp_error($retcode)) {
				$linkedin->clear_cache();
				$output .= $before_success . __('The access token has been successfully updated.', 'wp-linkedin') . $after_success;
			}
		} else {
			$retcode = new WP_Error('oauth_error', __('Invalid state', 'wp-linkedin'));
		}

		if (is_wp_error($retcode)) {
			$output .= $before_error . $retcode->get_error_message() . $after_error;
		}
	}

	$authorization_url = $linkedin->get_authorization_url($redirect_url);
	$split_url = explode('?', $authorization_url, 2);
	$action_url = $split_url[0];
	$params = array();
	parse_str($split_url[1], $params);

	$output .= '<form action="'.$action_url.'" method="get">';
	foreach ($params as $k => $v) $output .= '<input type="hidden" name="'.esc_attr($k).'" value="'.esc_attr($v).'"/>';
	$output .= '<button type="submit" class="linkedin-button">'.$button_label.'</button></form>';

	unset($GLOBALS['li_user_id']);
	return $output;
}


function wp_linkedin_mu_connection($conn) {
	require_once 'class-linkedin-connection.php';

	if (isset($GLOBALS['li_user_id'])) {
		$user_id = $GLOBALS['li_user_id'];
	} elseif (is_singular() || in_the_loop()) {
		$user_id = get_the_author_meta('ID');
	} elseif (is_author()) {
		if (get_query_var('author_name')) {
			$curauth = get_user_by('slug', get_query_var('author_name'));
		} else {
			$curauth = get_userdata(get_query_var('author'));
		}

		if ($curauth) $user_id = $curauth->ID;
	} elseif (is_user_logged_in()) {
		$user_id = get_current_user_id();
	} else {
		$user_id = get_option('wp-linkedin-mu_default_user');
	}

	return ($user_id) ? new WPLinkedInMUConnection($user_id) : false;
}


function wp_linkedin_mu_smarter_load_textdomain($mofile, $domain) {
	if ($domain == 'wp-linkedin-mu' && !is_readable($mofile)) {
		extract(pathinfo($mofile));
		$pos = strrpos($filename, '_');

		if ($pos !== false) {
			# cut off the locale part, leaving the language part only
			$filename = substr($filename, 0, $pos);
			$mofile = $dirname . '/' . $filename . '.' . $extension;
		}
	}

	return $mofile;
}
