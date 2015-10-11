<?php
/*
Plugin Name: WP LinkedIn Advanced Templates
Plugin URI: http://vdvn.me/pga
Description: This plugin provides advanced templates for WP-LinkedIn
Author: Claude Vedovini
Author URI: http://vdvn.me/
Version: 1.1
Text Domain: wp-linkedin-at

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

define('WP_LINKEDIN_AT_PLUGIN_VERSION', '1.1');
define('WP_LINKEDIN_AT_PLUGIN_NAME', 'WP LinkedIn Advanced Templates');
define('WP_LINKEDIN_AT_DOWNLOAD_ID', 3683);
define('WP_LINKEDIN_AT_PLUGIN_BASENAME', plugin_basename(__FILE__));

add_action('plugins_loaded', array('WPLinkedInATPlugin', 'get_instance'));

class WPLinkedInATPlugin {

	private static $instance;

	public static function get_instance() {
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	function __construct() {
		add_action('init', array(&$this, 'init'));

		// Make plugin available for translation
		// Translations can be filed in the /languages/ directory
		add_filter('load_textdomain_mofile', array(&$this, 'smarter_load_textdomain'), 10, 2);
		load_plugin_textdomain('wp-linkedin-at', false, dirname(plugin_basename(__FILE__)) . '/languages/' );

		if (class_exists('VDVNPluginUpdater')) {
			$this->updater = new VDVNPluginUpdater(__FILE__, WP_LINKEDIN_AT_PLUGIN_NAME,
					WP_LINKEDIN_AT_PLUGIN_VERSION, WP_LINKEDIN_AT_DOWNLOAD_ID);
		}
	}

	function init() {
		add_filter('linkedin_template', array(&$this, 'linkedin_template'));
		add_action('admin_notices', array(&$this, 'admin_notices'));
	}

	function smarter_load_textdomain($mofile, $domain) {
		if ($domain == 'wp-linkedin-at' && !is_readable($mofile)) {
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

	function linkedin_template($template) {
		$name = basename($template, '.php');
		$extended = dirname(__FILE__) . '/templates/' . $name . '.php';

		if (file_exists($extended)) {
			return $extended;
		} else {
			return $template;
		}
	}

	function admin_notices() {
		if (current_user_can('update_plugins')) {
			if (!function_exists('wp_linkedin_connection')): ?>
				<div class="error"><p><?php _e('The WP LinkedIn Advanced Templates plugin needs the WP LinkedIn plugin to be installed and activated.', 'wp-linkedin-at'); ?></p></div>
			<?php elseif (version_compare(WP_LINKEDIN_VERSION, '2.3') < 0):
				$format = __('The WP LinkedIn Advanced Templates plugin requires at least version %s of the WP-LinkedIn plugin, current installed version is %s', 'wp-linkedin-at');
				$error = sprintf($format, '2.3', WP_LINKEDIN_VERSION); ?>
				<div class="error"><p><?php echo $error; ?></p></div>
			<?php endif;
		}
	}
}
