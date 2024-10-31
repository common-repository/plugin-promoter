=== Plugin Promoter ===
Contributors: misternifty
Author: Brian Fegter
Author URI: http://coderrr.com
Plugin URI: http://coderrr.com/plugin-promoter
Tags: developer, promote, plugin, plugins, plugin-api, api, badge, badges, tabs
Requires at least: 2.5
Tested up to: 3.3
Stable tag: 0.1

Plugin Promoter helps you do just that, promote the awesome WordPress plugins you've created! 

== Description ==

Plugin Promoter will help you get the word out about the great plugins you've released at WordPress.org.

Here's what's baked in:

* A nifty plugin badge widget that gives you a download count and download link
* A shortcode to display the details of your plugin right from WordPress.org (Similar to what you see on wp-admin when searching for a plugin)

To display your plugin details on any page or post:

`[plugin-promoter plugin=wordpress-importer]`

To display your plugin badge on any page or post:

`[plugin-badge plugin=wordpress-importer]`

To insert your badge programmatically:

`<?php echo pp_badge(array('plugin'=>'plugin-slug'));?>`

== Installation ==

Upload the Plugin Promoter plugin to your `wp-content/plugins/` directory and activate.

== Screenshots ==

1. Plugin Badge Widget
2. Plugin Detail Page
3. Plugin Detail Page w/ Screenshots

== Changelog ==

= 0.1 =
* Added badge widget and plugin details shortcode.
