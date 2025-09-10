=== Kid QR Grid ===
Contributors: developer
Tags: qr-code, children, activity-tracker, shortcode, pwa
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.4
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

WordPress plugin to display QR codes for child PWA access links on parent pages.

== Description ==

Kid QR Grid plugin allows you to display QR codes for child activity tracking PWA access links on parent pages. Each QR code, when scanned with a smartphone camera, opens the child's dedicated PWA URL for activity tracking.

**Features:**

* Display QR codes for multiple children in a responsive grid layout
* Generate child-specific URLs with unique kid_id parameters
* Copy-to-clipboard functionality for sharing links
* Clean, accessible design that works with light and dark themes
* Configurable API endpoint and PWA base URL
* Client-side QR code generation (no external services)
* Fallback support for older browsers

**Usage:**

1. Configure API endpoint and PWA base URL in Settings > Kid QR Grid
2. Add `[kid_qr_grid]` shortcode to any page or post
3. QR codes will be generated automatically for each child

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/kid-qr-grid` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > Kid QR Grid to configure the plugin
4. Add the shortcode `[kid_qr_grid]` to any page or post where you want to display QR codes

== Frequently Asked Questions ==

= What API format is required? =

The API should return a JSON array with child objects containing `id` and `display_name` fields:
```json
[
  {"id": "uuid-1", "display_name": "Child 1"},
  {"id": "uuid-2", "display_name": "Child 2"}
]
```

= Can I use multiple shortcodes on the same page? =

Yes, the plugin supports multiple shortcode instances on the same page without conflicts.

= Does the plugin work offline? =

QR code generation works offline once the page is loaded, but fetching child data requires an internet connection.

= Is the plugin mobile-friendly? =

Yes, the grid layout is fully responsive and adapts to different screen sizes (2 columns on desktop, 1 column on mobile).

== Screenshots ==

1. QR code grid display with child names and copy buttons
2. Plugin settings page with API configuration
3. Mobile-responsive layout

== Changelog ==

= 1.0.0 =
* Initial release
* QR code generation for child PWA links
* Responsive grid layout
* Copy-to-clipboard functionality
* Admin settings page
* Client-side QR generation with fallback support

== Technical Notes ==

**Security:**
* All user inputs are properly sanitized and escaped
* API requests use WordPress HTTP API
* XSS protection implemented throughout

**Performance:**
* Scripts and styles only load on pages with the shortcode
* CDN-first loading with local fallback for QR library
* Efficient client-side rendering

**Compatibility:**
* Works with WordPress 5.0+
* PHP 7.4+ required
* Modern browsers with Canvas support

== Support ==

For support and feature requests, please contact the plugin developer.

== License ==

This plugin is licensed under the MIT License. You are free to use, modify, and distribute this plugin as needed.