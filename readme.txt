=== Custom Login Logo by VFIX.PK ===
Contributors: vfixpk
Tags: login, logo, branding, security, admin
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Replace the WordPress login screen logo (and Lost Password screen logo) with your own, link it to your homepage, and set its title to your site name.

== Description ==

Custom Login Logo by VFIX.PK is a lightweight, security-focused plugin that lets you replace the default WordPress logo shown on `wp-login.php` and the "Lost your password?" screen with your own branding — without editing any core files or your active theme.

**Features:**

* Upload/select a custom logo via the native WordPress Media Library
* Applies to both the login screen and the lost-password screen
* Logo links to your site's homepage instead of WordPress.org
* Logo title/alt text is automatically set to your site name
* No core file edits, no theme file edits
* No custom database tables — uses a single WordPress option
* No direct file operations — all image handling goes through the Media Library
* Full capability + nonce protection on the settings form
* Clean uninstall with no leftover data

= Security =

This plugin was built against a strict internal checklist covering SQL injection, XSS, file operations, user input handling, and permissions. See the "Security" section of the bundled README.md for full details.

== Installation ==

1. Upload the `custom-login-logo-by-vfix-pk` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Go to **Settings → Login Logo**.
4. Choose an image from the Media Library and save.

== Frequently Asked Questions ==

= Does this modify wp-login.php or my theme? =

No. It uses standard WordPress hooks (`login_enqueue_scripts`, `login_headerurl`, `login_headertext`) and the Settings API only.

= What happens if I delete the selected image from the Media Library? =

The plugin detects the missing attachment at render time and falls back to the default WordPress logo automatically.

= Does it support Multisite? =

Yes, each site has its own independent logo setting, and uninstalling cleans up every site's option.

== Screenshots ==

1. Settings → Login Logo admin screen.
2. Customized login screen with your logo, homepage link, and site name.

== Changelog ==

= 1.0.2 =
* Fix: escape login logo URL directly at the point of output.
* Fix: removed manual `load_plugin_textdomain()` call (unneeded/discouraged for wordpress.org-hosted plugins; translations load automatically).
* Fix: sanitize submitted attachment ID inline at the point `$_POST` is read.
* Chore: prefixed uninstall.php loop variables to avoid global-namespace collisions.

= 1.0.1 =
* Fix: textdomain now loaded on `init` instead of in the constructor.
* Fix: settings page correctly resets state when the selected logo has been deleted from the Media Library.
* Fix: multisite uninstall now cleans up options on all sites, not just the first 100.
* Chore: added `/languages` directory to match the Domain Path header.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.2 =
Code-quality/security fixes flagged by the WordPress.org Plugin Check tool. No functional changes; safe to update.

= 1.0.1 =
Bug fixes for multisite cleanup, stale logo detection, and textdomain loading. Safe to update, no breaking changes.

= 1.0.0 =
Initial release.
