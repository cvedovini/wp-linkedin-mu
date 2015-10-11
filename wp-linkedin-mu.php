<?php
/*
Plugin Name: WP LinkedIn Multi-Users
Plugin URI: http://vedovini.net/plugins/?utm_source=wordpress&utm_medium=plugin&utm_campaign=wp-linkedin-mu
Description: This plugin is an extension to the WP-LinkedIn plugin that enables showing LinkedIn profiles, recommendations and network updates for any registered user.
Author: Claude Vedovini
Author URI: http://vedovini.net/?utm_source=wordpress&utm_medium=plugin&utm_campaign=wp-linkedin-mu
Version: 1.0.1
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

include 'updater.php';


function wp_linkedin_mu_init() {
	add_filter('load_textdomain_mofile', 'wp_linkedin_mu_smarter_load_textdomain', 10, 2);
	load_plugin_textdomain('wp-linkedin-mu', false, dirname(plugin_basename(__FILE__)) . '/languages/' );
	add_shortcode('li_user', 'wp_linkedin_mu_set_user');
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
	} elseif (is_admin()) {
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
