=== WP LinkedIn Multi-Users ===
Author: Claude Vedovini
Contributors: cvedovini
Donate link: http://vedovini.net/plugins/?utm_source=wordpress&utm_medium=plugin&utm_campaign=wp-linkedin-mu
Tags: linkedin,resume,recommendations,profile,multi-users
Requires at least: 2.7
Tested up to: 4.2
Stable tag: 1.3.2
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

This plugin also extends the `[li_profile]` shortcode and other shortcodes with
a new set of attributes that allow to secify which user's profile to show.

For example:

- `[li_profile id="1"]` will show the profile of the user with id 1.
- `[li_profile name="admin"]` will show the profile of the user whose username
is `admin`.
- `[li_profile email="admin@example.com"]` will show the profile of the user
whose email is `admin@example.com`.

**Keywords**

When specifying the `id` attribute, you can also indicate keywords instead of
explicit ids, the id will be calculated automatically depending on the keyword:

- `author`: is the author of the post, page or archive.
- `current`: is the currently logged in user (if no user is logged in an error
message will be displayed).
- `bp_displayed`: support for BuddyPress, shows the currently displayed
BuddyPress profile.
- `bp_current`: support for BuddyPress, shows the currently logged in
BuddyPress user.

**Regenerate Button**

Additionally the plugin also provides the `[li_token_button]` shortcode that
displays a "Regenerate Token" button on any post or page. Optional attributes
for that shortcode are:

- `button_label`: the label on the button.
- `before_error` and `after_error`: HTML code to put before and after an error
message.
- `before_success` and `after_success`: HTML code to put before and after a
success message.
- `redir`: the URL where the user should be redirected to in case of success.
If `redir` is not provided the user will be redirected to the current page.

**Deprecated**

The `[li_user]` shortcode has been deprecated and the recommended method
is now to use the extended attributes to explicitly set the user.


== Installation ==

This plugin follows the [standard WordPress installation method](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins):

1. Upload the `wp-linkedin-mu` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress


== Changelog ==

= Version 1.3.1 =
- Fixing updater code

= Version 1.3 =
- `[li_user]` shortcode is now deprecated in favor of additional attributes to
the standards WP-LinkedIn shortcodes.
- The `id` attribute now accepts keywords such as `author`, `current`,
 `bp_displayed`, `bp_current` (see description for details).


= Version 1.2 =
- Adaptations to support new token retrieval mechanism in WP-LinkedIn


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
