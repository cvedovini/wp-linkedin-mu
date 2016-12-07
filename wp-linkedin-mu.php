<?php
/*
Plugin Name: WP LinkedIn Multi-Users
Plugin URI: http://vdvn.me/pga
Description: This plugin is an extension to the WP-LinkedIn plugin that enables showing LinkedIn profiles, recommendations and network updates for any registered user.
Author: Claude Vedovini
Author URI: http://vdvn.me/
Version: 1.7
Text Domain: wp-linkedin-mu
Domain Path: /languages
Network: True

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

define('WP_LINKEDIN_MU_PLUGIN_VERSION', '1.7');
define('WP_LINKEDIN_MU_PLUGIN_NAME', 'WP LinkedIn Multi-Users');
define('WP_LINKEDIN_MU_DOWNLOAD_ID', 2137);
define('WP_LINKEDIN_MU_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WP_LINKEDIN_MU_CONNECT_WITH_LINKEDIN', get_option('wp-linkedin-mu_connect_with_linkedin'));

include 'class-admin.php';

add_action('plugins_loaded', array('WPLinkedInMUPlugin', 'get_instance'));

class WPLinkedInMUPlugin {

	private static $instance;

	public static function get_instance() {
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	function __construct() {
		// Make plugin available for translation
		// Translations can be filed in the /languages/ directory
		add_filter('load_textdomain_mofile', array(&$this, 'smarter_load_textdomain'), 10, 2);
		load_plugin_textdomain('wp-linkedin-mu', false, dirname(plugin_basename(__FILE__)) . '/languages/' );
		add_action('init', array(&$this, 'init'));
	}

	function init() {
		$this->admin = new WPLinkedInMUAdmin();
		if (class_exists('VDVNPluginUpdater')) {
			$this->updater = new VDVNPluginUpdater(__FILE__, WP_LINKEDIN_MU_PLUGIN_NAME,
					WP_LINKEDIN_MU_PLUGIN_VERSION, WP_LINKEDIN_MU_DOWNLOAD_ID);
		}

		add_shortcode('li_token_button', 'wp_linkedin_mu_token_button');
		add_shortcode('li_login_button', 'wp_linkedin_mu_login_button');
		add_filter('linkedin_connection', array(&$this, 'linkedin_connection'));
		add_filter('linkedin_scope', array(&$this, 'linkedin_scope'));
		add_action('linkedin_connect_fields', array(&$this, 'connect_fields'));
		add_action('linkedin_user_connected', array(&$this, 'update_user_profile'));

		add_filter('shortcode_atts_li_profile', array(&$this, 'filter_userid'), 10, 3);
		add_filter('shortcode_atts_li_recommendations', array(&$this, 'filter_userid'), 10, 3);
		add_filter('shortcode_atts_li_updates', array(&$this, 'filter_userid'), 10, 3);
		add_filter('shortcode_atts_li_card', array(&$this, 'filter_userid'), 10, 3);
		add_filter('shortcode_atts_li_profile', array(&$this, 'filter_userid'), 10, 3);

		if (WP_LINKEDIN_MU_CONNECT_WITH_LINKEDIN) {
			add_action('login_form', array(&$this, 'show_login_button'));
			add_filter('pre_get_avatar_data', array(&$this, 'get_avatar_url'), 10, 2);
		}

		// @deprecated
		add_shortcode('li_user', 'wp_linkedin_mu_set_user');
	}

	function linkedin_scope($scope) {
		$scope[] = 'r_emailaddress';
		return $scope;
	}

	function linkedin_connection($conn) {
		require_once 'class-linkedin-connection.php';
		$user_id = false;

		if (is_admin() || get_query_var('oauth') == 'linkedin') {
			$user_id = get_current_user_id();
		} elseif (isset($GLOBALS['li_user_id'])) {
			// This one just takes precedence over everything
			$user_id = $GLOBALS['li_user_id'];
			unset($GLOBALS['li_user_id']);
		} else {
			if (is_singular() || in_the_loop()) {
				$user_id = get_the_author_meta('ID');
			} elseif (is_author()) {
				if (get_query_var('author_name')) {
					$curauth = get_user_by('slug', get_query_var('author_name'));
				} else {
					$curauth = get_userdata(get_query_var('author'));
				}

				if ($curauth) $user_id = $curauth->ID;
			}

			if (!$user_id) $user_id = get_option('wp-linkedin-mu_default_user');
		}

		return new WPLinkedInMUConnection($user_id);
	}

	function filter_userid($out, $pairs, $atts) {
		$keys = array('id' => 'id', 'name' => 'login', 'email' => 'email');
		$user = false;

		foreach($keys as $k => $f) {
			if (isset($atts[$k]) && !empty($atts[$k])) {
				$key = $k;
				$field = $f;
				$value = $atts[$k];
				break;
			}
		}

		if (!empty($value)) {
			// filter special keywords used with id attribute
			if ($key == 'id') {
				switch ($value) {
					case 'author':
						if (is_singular() || in_the_loop()) {
							$value = get_the_author_meta('ID');
						} elseif (is_author()) {
							if (get_query_var('author_name')) {
								$value = get_user_by('slug', get_query_var('author_name'));
							} else {
								$value = get_userdata(get_query_var('author'));
							}
						}
						break;

					case 'current':
						$value = get_current_user_id();
						break;

					case 'bp_displayed':
						// The BuddyPress displayed user
						if (isset($GLOBALS['bp'])) $value = $GLOBALS['bp']->displayed_user->id;
						break;

					case 'bp_current':
						// The BuddyPress logged in user
						if (isset($GLOBALS['bp'])) $value = $GLOBALS['bp']->loggedin_user->id;
						break;
				}
			}

			$user = get_user_by($field, $value);

			if ($user) {
				if (LI_DEBUG) echo '<!-- for user: ' . $user->display_name . ' -->';
				$GLOBALS['li_user_id'] = $user->ID;
			} else if (LI_DEBUG) {
				printf(__('Cannot find a user whose %1$s is %2$s', 'wp-linkedin-mu'), $key, $value);
			}
		}

		return $out;
	}

	function smarter_load_textdomain($mofile, $domain) {
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

	function show_login_button() {
		if (isset($_REQUEST['redirect_to'])) {
			$redirect_to = $_REQUEST['redirect_to'];
		} else {
			$redirect_to = admin_url();
		}

		$linkedin = wp_linkedin_connection();
		if ($linkedin) {
			$login_url = $linkedin->get_authorization_url($redirect_to);
			echo '<p style="margin-bottom:16px"><a href="' . $login_url . '">' . _x('Connect with LinkedIn', 'On the login page', 'wp-linkedin-mu') . '</a></p>';
		}
	}

	function connect_fields($fields) {
		$extra = array('first-name', 'last-name', 'summary', 'public-profile-url');
		return array_merge($fields, $extra);
	}

	function update_user_profile($profile) {
		$user_id = get_current_user_id();
		$user_info = array('ID' => $user_id,
				'first_name' => $profile->firstName,
				'last_name' => $profile->lastName,
				'description' => $profile->summary,
				'user_url' => $profile->publicProfileUrl);
		wp_update_user($user_info);
	}

	function get_avatar_url($args, $id_or_email) {
		// Process the user identifier.
		if (is_numeric($id_or_email)) {
			$user_id = (int)$id_or_email;
		} elseif (is_string($id_or_email)) {
			if (email_exists($id_or_email)) {
			    $user = get_user_by('email', $id_or_email);
			    $user_id = $user->ID;
			}
		} elseif ($id_or_email instanceof WP_User) {
			// User Object
			$user_id = $id_or_email->ID;
		} elseif ($id_or_email instanceof WP_Post) {
			// Post Object
			$user_id = (int)$id_or_email->post_author;
		} elseif (is_object($id_or_email) && isset($id_or_email->comment_ID)) {
			// Comment Object
			$allowed_comment_types = apply_filters('get_avatar_comment_types', array('comment'));
			if (!empty($id_or_email->comment_type) && !in_array($id_or_email->comment_type, (array)$allowed_comment_types)) {
				return false;
			}

			if (!empty($id_or_email->user_id)) {
				$user_id = $id_or_email->user_id;
			}
		}

		if (isset($user_id)) {
			$GLOBALS['li_user_id'] = $user_id;
			$avatar_url = wp_linkedin_original_profile_picture_url();
			unset($GLOBALS['li_user_id']);
			if (!is_wp_error($avatar_url)) return array('url' => $avatar_url);
		}

		return false;
	}
}

function wp_linkedin_mu_token_button($atts=array()) {
	if (isset($_REQUEST['oauth_status']) && isset($_REQUEST['source']) &&
			$_REQUEST['source'] == 'token-button') {
		$output[] = wp_linkedin_mu_filter_url();

		switch ($_GET['oauth_status']) {
			case 'success':
				$message = isset($_GET['oauth_message']) ? $_GET['oauth_message'] :
					__('The access token has been successfully updated.', 'wp-linkedin');
				$output[] = $before_success . $message . $after_success;
								break;

			case 'error':
				$message = isset($_GET['oauth_message']) ? $_GET['oauth_message'] :
					__('An error has occured while updating the access token, please try again.', 'wp-linkedin');
				$output[] = $before_error . $message . $after_error;
				break;
		}
	}

	// Only if the user is currently logged-in
	if (is_user_logged_in()) {
		$atts = shortcode_atts(array(
				'button_label' => __('Regenerate LinkedIn Access Token', 'wp-linkedin-mu'),
				'before_error' => '<p class="error">',
				'after_error' => '</p>',
				'before_success' => '<p class="info">',
				'after_success' => '</p>',
				'redir' => $_SERVER["REQUEST_URI"]
		), $atts, 'li_token_button');
		extract($atts);

		$GLOBALS['li_user_id'] = get_current_user_id();
		$linkedin = wp_linkedin_connection();

		// Add a query arg so we know where we are coming from
		$redir = add_query_arg('source', 'token-button', $redir);

		$authorization_url = $linkedin->get_authorization_url($redir);
		$split_url = explode('?', $authorization_url, 2);
		$action_url = $split_url[0];
		$params = array();
		parse_str($split_url[1], $params);

		$output[] = '<form action="'.$action_url.'" method="get">';
		foreach ($params as $k => $v) $output[] = '<input type="hidden" name="'.esc_attr($k).'" value="'.esc_attr($v).'"/>';
		$output[] = '<button type="submit" class="linkedin-button">'.$button_label.'</button></form>';

		unset($GLOBALS['li_user_id']);
	}

	if (!empty($output)) return implode($output);
}


function wp_linkedin_mu_login_button($atts=array()) {
	if (isset($_REQUEST['oauth_status']) && isset($_REQUEST['source']) &&
			$_REQUEST['source'] == 'login-button') {
		$output[] = wp_linkedin_mu_filter_url();

		switch ($_GET['oauth_status']) {
			case 'success':
				$message = isset($_GET['oauth_message']) ? $_GET['oauth_message'] :
					__('The access token has been successfully updated.', 'wp-linkedin');
				$output[] = $before_success . $message . $after_success;
				break;

			case 'error':
				$message = isset($_GET['oauth_message']) ? $_GET['oauth_message'] :
					__('An error has occured while updating the access token, please try again.', 'wp-linkedin');
				$output[] = $before_error . $message . $after_error;
				break;
		}
	}

	// Only if the user is not currently logged-in
	if (!is_user_logged_in()) {
		$atts = shortcode_atts(array(
				'button_label' => _x('Connect with LinkedIn', 'On the login button', 'wp-linkedin-mu'),
				'before_error' => '<p class="error">',
				'after_error' => '</p>',
				'before_success' => '<p class="info">',
				'after_success' => '</p>',
				'redir' => $_SERVER["REQUEST_URI"]
		), $atts, 'li_login_button');
		extract($atts);

		$linkedin = wp_linkedin_connection();

		// Add a query arg so we know where we are coming from
		$redir = add_query_arg('source', 'login-button', $redir);

		$authorization_url = $linkedin->get_authorization_url($redir);
		$split_url = explode('?', $authorization_url, 2);
		$action_url = $split_url[0];
		$params = array();
		parse_str($split_url[1], $params);

		$output[] = '<form action="'.$action_url.'" method="get">';
		$output[] = '<input type="hidden" name="generator" value="login-button"/>';
		foreach ($params as $k => $v) $output[] = '<input type="hidden" name="'.esc_attr($k).'" value="'.esc_attr($v).'"/>';
		$output[] = '<button type="submit" class="linkedin-button">'.$button_label.'</button></form>';

		unset($GLOBALS['li_user_id']);
	}

	if (!empty($output)) return implode($output);
}

function wp_linkedin_mu_filter_url() {
	$removable_query_args = apply_filters('removable_query_args', array('source'));

	if (empty( $removable_query_args)) return;

	// Ensure we're using an absolute URL.
	$current_url  = set_url_scheme('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	$filtered_url = remove_query_arg($removable_query_args, $current_url);

	return <<<EOT
<script>
	if (window.history.replaceState) {
		window.history.replaceState( null, null, '$filtered_url' + window.location.hash );
	}
</script>
EOT;
}

/*******************************************************************************
 *
 * All the following is now deprecated...
 *
 ******************************************************************************/

function wp_linkedin_mu_set_user($atts, $content='') {
	$atts = shortcode_atts(array(
			'id' => '',
			'name' => '',
			'login' => '',
			'email' => ''
	), $atts, 'li_user');

	$keys = array('id' => 'id', 'name' => 'login', 'email' => 'email');
	$user = false;

	foreach($keys as $k => $f) {
		if (isset($atts[$k]) && !empty($atts[$k])) {
			$user = get_user_by($f, $atts[$k]);
			break;
		}
	}

	if (!isset($v)) {
		return sprintf(_('You must provide one of the following attributes for the li_user shortcode to work: %s.', 'wp-linkedin-mu'),
				implode(', ', array_keys($keys)));
	}

	if ($user) {
		$GLOBALS['li_user_id'] = $user->ID;
		if (LI_DEBUG) echo '<!-- for user: ' . $user->display_name . ' -->';
		$content = do_shortcode($content);
		unset($GLOBALS['li_user_id']);
		return $content;
	}

	return sprintf(__('Cannot find a user whose %1$s is %2$s', 'wp-linkedin-mu'), $k, $atts[$k]);
}
