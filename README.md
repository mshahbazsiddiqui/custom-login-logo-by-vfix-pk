# Custom Login Logo by VFIX.PK

A lightweight, security-hardened WordPress plugin (by VFIX.PK) that replaces the default WordPress logo on the **login screen** and **Lost Password screen**, points it to your site's homepage, and sets its title/alt text to your site name — all without touching WordPress core or your active theme.

## Features

- 🖼️ Replace the login screen logo with your own image, chosen via the native WordPress Media Library.
- 🔐 Applies automatically to both `wp-login.php` and the "Lost your password?" screen.
- 🏠 Logo link points to your site's homepage instead of WordPress.org.
- 🏷️ Logo title/alt text is automatically set to your site name (`get_bloginfo('name')`).
- 🧩 Zero core file edits. Zero theme file edits. Fully self-contained plugin.
- 🪶 No external dependencies, no third-party libraries, no CDN calls.
- 🧹 Clean uninstall — removes its single stored option, no leftover data.
- 🌍 Translation-ready.

## Screenshots

> _Add screenshots here after installing:_
> 1. `screenshot-1.png` — Settings → Login Logo admin screen
> 2. `screenshot-2.png` — Customized login screen in action

## Installation

1. Download or clone this repository into your site's `wp-content/plugins/` directory:
   ```
   wp-content/plugins/custom-login-logo-by-vfix-pk/
   ```
2. Go to **Plugins → Installed Plugins** in your WordPress admin and activate **Custom Login Logo by VFIX.PK**.
3. Go to **Settings → Login Logo**.
4. Click **Choose Image**, select or upload your logo from the Media Library, then click **Save Changes**.
5. Visit `wp-login.php` (or log out) to see your new logo in action.

## Usage

- **Set a logo:** Settings → Login Logo → Choose Image → Save Changes.
- **Remove a logo / restore default:** Settings → Login Logo → Remove Logo → Save Changes.
- The logo automatically:
  - Links to your homepage.
  - Uses your site name as its title/alt text.
  - Appears identically on the login and lost-password screens.

## Requirements

- WordPress 5.8 or later
- PHP 7.4 or later

## Security

This plugin was built to a strict security checklist. Summary:

| Risk | Mitigation |
|---|---|
| **SQL Injection** | No raw SQL is ever written. All storage/retrieval goes exclusively through `get_option()` / `update_option()` / `delete_option()`, which use WordPress's internally prepared queries. |
| **XSS** | Every dynamic value printed to HTML is escaped with `esc_url()`, `esc_attr()`, or `esc_html()` / `esc_html__()` at the point of output. |
| **File Operations** | No direct filesystem calls anywhere in the codebase (no `fopen`, `file_put_contents`, `move_uploaded_file`, etc.). Image handling is delegated entirely to the WordPress Media Library and `wp_get_attachment_image_url()`. |
| **Unsafe User Input** | The only input accepted is an attachment ID from the Media Library, which is sanitized with `absint()` and validated with `wp_attachment_is_image()` before being stored. There are no free-text or path fields. |
| **Permissions** | The settings page and its save handler are gated behind `current_user_can( 'manage_options' )`, and the save form is protected by a WordPress nonce (`wp_nonce_field()` / `check_admin_referer()`). |
| **General hardening** | No `eval()`, no `extract()`, no unserialization of user data, no outbound HTTP requests, no bundled third-party JS/CSS libraries. |

See the inline code comments in `custom-login-logo-by-vfix-pk.php` for exactly where each control is implemented.

## Uninstalling

Deleting the plugin from the Plugins screen triggers `uninstall.php`, which removes the plugin's single option (`cll_logo_attachment_id`) — including across all sites on a multisite network. No other data is created or left behind.

## Frequently Asked Questions

**Does this modify my theme's `functions.php` or any core WordPress files?**
No. All functionality is delivered through standard WordPress hooks and filters (`login_enqueue_scripts`, `login_headerurl`, `login_headertext`, the Settings API) from within the plugin's own files.

**What happens if I delete the image I selected from the Media Library?**
The plugin checks that the attachment still exists at render time and silently falls back to the default WordPress logo if it doesn't — it never errors out on the login screen.

**Does this work on Multisite?**
Yes. Each site keeps its own logo setting, and uninstalling cleans up the option on every site in the network.

## Changelog

### 1.0.1
- Fix: `load_plugin_textdomain()` now hooked to `init` instead of being called directly from the constructor, per current WordPress best practice.
- Fix: Settings page now detects a stored logo attachment that no longer exists in the Media Library and resets the UI (preview, hidden input, "Remove Logo" button) to match reality, so saving doesn't persist a stale ID.
- Fix: Multisite uninstall cleanup now passes `'number' => 0` to `get_sites()` so the option is removed on every site, including networks with more than 100 sites.
- Chore: Added `/languages` directory (with `.gitkeep`) to match the plugin header's `Domain Path`.

### 1.0.0
- Initial release: custom login/lost-password logo, homepage link, site-name title/alt text, full security hardening.

## License

This plugin is licensed under the [GPLv2 (or later)](https://www.gnu.org/licenses/gpl-2.0.html), consistent with WordPress core.

## Contributing

Issues and pull requests are welcome. Please keep any contributions aligned with the security constraints listed above (no raw SQL, no direct file I/O, escape all output, sanitize all input).
