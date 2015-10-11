=== WP LinkedIn Multi-Users ===
Author: Claude Vedovini
Contributors: cvedovini
Donate link: http://vedovini.net/plugins/?utm_source=wordpress&utm_medium=plugin&utm_campaign=wp-linkedin-mu
Tags: linkedin,resume,recommendations,profile,multi-users
Requires at least: 2.7
Tested up to: 3.9
Stable tag: 1.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html


== Description ==

This plugin is an extension to the WP-LinkedIn plugin that enables showing
LinkedIn profiles, recommendations and network updates for any registered user.

By default, the behavior of the [WP-LinkedIn plugin](http://wordpress.org/plugins/wp-linkedin/)
will be changed so that shortcodes and widgets show the data of the author of
the post or page. If the plugin cannot decide whose data to show (e.g. on the
home page or archive pages) you can configure it to either show nothing or a
specific user's data.

This plugin also provides the `[li_user]` shortcode that can be used to decorate
any [WP-LinkedIn plugin](http://wordpress.org/plugins/wp-linkedin/) shortcode
and force the user. For example:

- `[li_user id="1"][li_profile][/li_user]` will show the profile of the user
with id 1.
- `[li_user name="admin"][li_profile][/li_user]` will show the profile of the
user whose username is `admin`.
- `[li_user email="admin@example.com"][li_profile][/li_user]` will show the profile of the
user whose email is `admin@example.com`.
- `[li_token_button]` displays a "Regenerate Token" button on any post or page.
Optional attributes are `button_label`, `before_error`, `after_error`,
`before_success` and `after_success`. The user will be redirected to the page where
the button is and the token will be retrieved. *IMPORTANT* The page's url must be
registered as a `redirect_uri` with the LinkedIn API.


== Installation ==

This plugin follows the [standard WordPress installation method](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins):

1. Upload the `wp-linkedin-mu` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress


== Changelog ==


= Version 1.1 =
- Added Dutch translations.
- Added a shortcode that displays a "Regenerate Token" button on any page or post
for logged-in users.


= Version 1.0.1 =
- Small fixes.

= Version 1.0 =
- Initial release.


== Credits ==

Following is the list of people and projects who helped me with this plugin,
many thanks to them :)

- [Jan Spoelstra](http://www.linkedin.com/in/janspoelstra): Contributed the
Dutch translations.
- [Nathalie Aynié](http://nathalieaynie.com/): Contributed the Italian
translations.
