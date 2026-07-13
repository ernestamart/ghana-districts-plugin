=== Ghana Districts & Regions ===
Contributors: ernestamart
Tags: ghana, africa, address, regions, districts, dropdown, shortcode, contact form 7, cf7
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.1.0
License: GPL v2 or later

Add Ghana region and district dropdowns to WordPress pages, posts, and Contact Form 7.

== Description ==

Add Ghana region and district dropdowns anywhere on your WordPress site.

= Features =

* All 16 Ghana regions
* 260+ districts database (complete)
* Shortcodes: [ghana_regions] and [ghana_districts]
* Contact Form 7 integration with native tag buttons
* Smart dropdowns – select region, districts auto-populate
* Responsive design
* Admin settings page

= Contact Form 7 Integration =

This plugin adds "Ghana Region" and "Ghana District" buttons to the CF7 tag generator. Simply click the buttons to insert the tags into your forms.

Example:
[ghana_region group:"group1"]
[ghana_district group:"group1"]

Make sure both tags use the same "group" value so the district dropdown populates correctly.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/
2. Activate through 'Plugins' menu
3. Add [ghana_regions] and [ghana_districts] to any page, or use the CF7 buttons

== Shortcodes ==

[ghana_regions] – Region dropdown
[ghana_districts] – District dropdown

Optional attributes:
id="custom-id" – Custom ID for the select element
class="custom-class" – Custom CSS class
group="group-name" – Link region and district together

== Contact Form 7 Tags ==

[ghana_region] – Region dropdown for CF7
[ghana_district] – District dropdown for CF7

Both tags support:
group="name" – Link region and district (required)
id="custom-id" – Custom ID
class="custom-class" – Custom CSS class

== Requirements ==

* WordPress 5.0 or higher
* PHP 7.4 or higher
* Contact Form 7 (optional – for CF7 integration)

== Changelog ==

= 1.1.0 =
* Contact Form 7 integration with native tag buttons
* CF7 validation support
* Improved JavaScript for CF7 dynamic forms

= 1.0.0 =
* Initial release
* All 16 regions
* 260+ districts
* Shortcode support
* Admin settings page

== Support ==

Email: ernestamart@gmail.com