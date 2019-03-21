=== AP Theme Utility Plugin ===
Contributors: Access Keys
Tags: import, content, demo, theme options, customizer options, widgets
Donate link: http://accesspressthemes.com/donation/
Requires at least: 4.0
Tested up to: 4.7
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A plugin to import your demo content, widgets, theme settings and cusomizer settings with one click.

== Description ==
[Support](https://accesspressthemes.com/support)

Tested with WordPress 4.7

<strong>Instant Demo Importer</strong> is a <strong>Free WordPress plugin</strong> to import the demo contents, widgets, theme settings and customizer settings with one click in your website!

You just need to create a new instance for the plugin class somewhere in your theme and define the path for the demo content, widgets, theme option settings and customizer settings and the path for the demo folder location.

== Installation ==
1. Upload `instant-demo-imporder` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Create an object instance for the `EDTU_Class` class within your theme file.
4. Place `&lt;?php do_action('EDTU_Class'); ?&gt;` in your templates this will provide you with the default demo for the ScrollMe theme by AccessPress to install.
5. You may replace these default demo by adding your own demo content files to the theme folder and setting the demo file path location to the `EDTU_Class` object.

for e.g.
`&lt;?php
	if(class_exists('EDTU_Class')) :
		$demoimporter = new EDTU_Class();

		$demoimporter->demos = array(
			'scrollme-demo' => array(
				'title' => __('Scrollme Demo', 'parallaxsome-pro'),
				'name' => 'scrollme-demo',
				'screenshot' => get_template_directory_uri().'/welcome/demos/scrollme-demo/screen.png',
				'home_page' => 'front-page',
				'menus' => array(
					'Main Menu' => 'primary'
				)
			),
		);

		$demoimporter->demo_dir = get_template_directory().'/welcome/demos/';
	endif;
?&gt;`


== Frequently Asked Questions ==
= What does this plugin do? =
This plugin provides the ability to to import the demo contents, widgets, theme option settings and customizer settings in your site.

== Screenshots ==
1. Backend Demo Installation Option.

== Changelog ==
= 1.0.0 =
* Plugin submitted to http://wordpress.org for review and approval

== Upgrade Notice ==
There is an update available for the Instant Demo Importer .Please update to recieve new updates and bug fixes.