<?php
/*
Plugin Name: Plugin Promoter
Plugin URI: http://coderrr.com/plugin-promoter
Description: Plugin Promoter helps you do just that, promote the awesome WordPress plugins you've created with a stylish download count badge and plugin details shortcode! 
Version: 0.1
Author: Brian Fegter
Author URI: http://coderrr.com
License: GPL2
*/

include_once('widget.php');

function pp_plugins_api($action, $args = null) {
	
	if ( is_array($args) )
		$args = (object)$args;

	if ( !isset($args->per_page) )
		$args->per_page = 24;
		
	$args = apply_filters('plugins_api_args', $args, $action);
	$res = apply_filters('plugins_api', false, $action, $args);
	
	if ( false === $res ) {
		$request = wp_remote_post('http://api.wordpress.org/plugins/info/1.0/', array( 'timeout' => 15, 'body' => array('action' => $action, 'request' => serialize($args))) );
		if ( is_wp_error($request) ) {
			$res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.'), $request->get_error_message() );
		} else {
			$res = unserialize( wp_remote_retrieve_body( $request ) );
			if ( false === $res )
				$res = new WP_Error('plugins_api_failed', __('An unknown error occurred.'), wp_remote_retrieve_body( $request ) );
		}
	} elseif ( !is_wp_error($res) ) {
		$res->external = true;
	}
	return apply_filters('plugins_api_result', $res, $action, $args);
}

add_shortcode('plugin-promoter', 'pp_plugin_information');
function pp_plugin_information($args) {
	
	extract($args);
	
	if(!$plugin)
		return __('Please enter the plugin slug in your shortcode. - Example: [plugin-promoter plugin=foobar]');
	
	$api = get_transient("pp_$plugin");
	if(!$api){
		$api = pp_plugins_api('plugin_information', array('slug' => $plugin ));
		set_transient("pp_$plugin", $api, 60*60*3); //Renew every three hours
	}
	if ( is_wp_error($api) || !$api)
		return;
	
	wp_enqueue_script('jquery-ui-tabs');
	wp_enqueue_script('plugin-promoter', plugins_url( 'js/core.js' , __FILE__ ), array('jquery'));
	wp_enqueue_style('plugin-promoter', plugins_url( 'css/style.css' , __FILE__ ));
	
	$version = $api->version;

	$plugins_allowedtags = array('a' => array('href' => array(), 'title' => array(), 'target' => array()),
		'abbr' => array('title' => array()), 'acronym' => array('title' => array()),
		'code' => array(), 'pre' => array(), 'em' => array(), 'strong' => array(),
		'div' => array(), 'p' => array(), 'ul' => array(), 'ol' => array(), 'li' => array(),
		'h1' => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(), 'h5' => array(), 'h6' => array(),
		'img' => array('src' => array(), 'class' => array(), 'alt' => array()));
	
	//Sanitize HTML
	foreach ( (array)$api->sections as $section_name => $content )
		$api->sections[$section_name] = wp_kses($content, $plugins_allowedtags);
	foreach ( array( 'version', 'author', 'requires', 'tested', 'homepage', 'downloaded', 'slug' ) as $key ) {
		if ( isset( $api->$key ) )
			$api->$key = wp_kses( $api->$key, $plugins_allowedtags );
	}

	$output = "<div id='plugin-information'>";
		
		$banner = "http://plugins.svn.wordpress.org/$plugin/assets/banner-772x250.png";
		$file_headers = @get_headers($banner);
		if($file_headers[0] == 'HTTP/1.1 200 OK'){
			$banner_src = get_bloginfo('wpurl').'/wp-admin/admin-ajax.php?action=pp_images&type=banner&image=banner-772x250.png&plugin='.$plugin;
			$banner_img .= "<img src='$banner_src' alt='Plugin Banner'>";
			$banner_class = 'class="with-banner"';
		}else
			$banner_class = "class='no-banner'";
		$output .= "<div id='plugin-title'$banner_class>";
		$output .= $banner_img;
		$output .= "<h2>$api->name</h2>";
		$output .= '</div>';
		
		
		$output .= '
		<div class="alignright fyi">
			<h2 class="mainheader">'.__('Plugin Information').'</h2>
			<ul>';
				if ( ! empty($api->version) ) :
					$output .= "<li><strong>".__('Version:')."</strong> $api->version</li>";
				endif; if ( ! empty($api->author) ) :
					$output .= "<li><strong>".__('Author:')."</strong> ".links_add_target($api->author, '_blank')."</li>";
				endif; if ( ! empty($api->last_updated) ) :
					$output .= "<li><strong>".__('Last Updated:')."</strong> <span title='{$api->last_updated}'>";
					$output .= sprintf( __('%s ago'), human_time_diff(strtotime($api->last_updated)) )."</span></li>";
				endif; if ( ! empty($api->requires) ) :
					$output .= "<li><strong>".__('Requires WordPress Version:')."</strong> ".sprintf(__('%s or higher'), $api->requires)."</li>";
				endif; if ( ! empty($api->tested) ) :
					$output .= "<li><strong>".__('Compatible up to:')."</strong> $api->tested</li>";
				endif; if ( ! empty($api->downloaded) ) : 
					$output .= "<li><strong>".__('Downloaded:')."</strong> ".sprintf(_n('%s time', '%s times', $api->downloaded), number_format_i18n($api->downloaded))."</li>";
				endif; if ( ! empty($api->slug) && empty($api->external) ) :
					$output .= "<li><a target='_blank' href='http://wordpress.org/extend/plugins/$api->slug/'>".__('WordPress.org Plugin Page &#187;')."</a></li>";
				endif;
			$output .= "</ul>";
			if ( ! empty($api->rating) ) :
			$output .= "
			<h2>".__('Average Rating')."</h2>
			<div class='star-holder' title='". sprintf(_n('(based on %s rating)', '(based on %s ratings)', $api->num_ratings), number_format_i18n($api->num_ratings))."'>
				<div class='star star-rating' style='left:0; width: ".esc_attr($api->rating)."px'></div>
				<div class='star star5'><img src='".admin_url('images/star.png?v=20110615')."' alt='".esc_attr('5 stars')."' /></div>
				<div class='star star4'><img src='".admin_url('images/star.png?v=20110615')."' alt='".esc_attr('4 stars')."' /></div>
				<div class='star star3'><img src='".admin_url('images/star.png?v=20110615')."' alt='".esc_attr('3 stars')."' /></div>
				<div class='star star2'><img src='".admin_url('images/star.png?v=20110615')."' alt='".esc_attr('2 stars')."' /></div>
				<div class='star star1'><img src='".admin_url('images/star.png?v=20110615')."' alt='".esc_attr('1 star')."' /></div>
			</div>
			<small>".sprintf(_n('(based on %s rating)', '(based on %s ratings)', $api->num_ratings), number_format_i18n($api->num_ratings))."</small>";
			endif;
		$output .= "</div>";
		
		//Tabs
		$output .= "<div class='plugin-promoter-tabs'>
		<ul id='sidemenu'>\n";
			foreach ( (array)$api->sections as $section_name => $content ) {
				$title = $section_name;
				$title = ucwords(str_replace('_', ' ', $title));
				$class = ( $section_name == $section ) ? ' class="current"' : '';
				$san_title = esc_attr(sanitize_title_with_dashes($title));
				$href = "#$san_title";
				$output .= "\t<li><a name='$san_title' target='' href='$href'$class>$title</a></li>\n";
			}
		$output .= "</ul><span style='height:50px; display:block;'></span>\n";

		foreach ( (array)$api->sections as $section_name => $content ) {
			
			if($section_name == 'screenshots'){
				if(preg_match_all("~<img [^>]*src='([^']+)'[^>]*>~", $content, $matches)){
					$urls = $matches[1];
					$i = 0;
					foreach($urls as $url){
						$screenshot = explode('?', basename($url));
						$screenshot = $screenshot[0];
						$screenshot_url = get_bloginfo('wpurl')."/wp-admin/admin-ajax.php?action=pp_images&type=screenshot&image=$screenshot&plugin=$plugin&tag=$version";
						$content = str_replace($url, $screenshot_url, $content);
						$i++;
					}
				}
			}
			
			$title = $section_name;
			$title[0] = strtoupper($title[0]);
			$title = str_replace('_', ' ', $title);

			$content = links_add_base_url($content, 'http://wordpress.org/extend/plugins/' . $api->slug . '/');
			$content = links_add_target($content, '_blank');

			$san_title = esc_attr(sanitize_title_with_dashes($title));


			$output .= "\t<div id='$san_title' class='section' >\n";
			$output .= "\t\t<h2 class='long-header'>$title</h2>";
			$output .= $content;
			$output .= "\t</div>\n";
		}
		$output .= "</div>\n";
	$output .= "</div>\n";
	
	return $output;
}

add_shortcode('plugin-badge', 'pp_badge');
function pp_badge($args){
	
	extract($args);
	$api = get_transient("pp_$plugin");
	if(!$api){
		$api = pp_plugins_api('plugin_information', array('slug' => $plugin ));
		set_transient("pp_$plugin", $api, 60*60*3); //Renew every three hours
	}
	
	if(!$api)
		return;
	
	wp_enqueue_style('plugin-promoter', plugins_url( 'css/style.css' , __FILE__ ));
	
	$output = "<div id='plugin-promoter-badge'>";
	$output .= "<h2>$api->name</h2>";
	$output .= "<span class='wp-tagline'>WordPress Plugin</span>";
	if ( ! empty($api->downloaded) ) :
		$downloads = $api->downloaded;
		if($downloads > 999 && $downloads < 1000000){
			$downloads = floor($downloads / 1000) . 'K';
		}elseif($downloads >= 1000000){
			$downloads = floor($downloads / 1000000) . 'M+';
		}
		$string_count = strlen($downloads);
		$output .= "<span class='downloaded chars-$string_count'><strong>$downloads</strong><em>Downloads</em></span>";
	endif; if ( ! empty($api->slug) && empty($api->external) ) :
		$output .= "<span class='link'><a target='_blank' href='http://wordpress.org/extend/plugins/$api->slug/'>".__('Download Now &#187;')."</a></span>";
	endif;
	
	$output .= "</div>";
	return $output;
}

add_action('wp_ajax_pp_images', 'pp_images');
add_action('wp_ajax_nopriv_pp_images', 'pp_images');
function pp_images(){
	//nonce
	$type = esc_attr($_REQUEST['type']);
	$plugin = esc_attr($_REQUEST['plugin']);
	$tag = esc_attr($_REQUEST['tag']);
	$image = esc_attr($_REQUEST['image']);
	$image_extension = explode('.', $image);
	$image_extension = $image_extension[1];
	$image_extension = $image_extension == 'jpg' ? 'jpeg' : $image_extension;
	
	
	switch($type){
		case 'banner':
			$url = "http://plugins.svn.wordpress.org/$plugin/assets/banner-772x250.png";
			break;
		case 'screenshot':
			$url = "http://plugins.svn.wordpress.org/$plugin/tags/$tag/$image";
			break;
	}
	
	$url_hash = substr(sha1($url), 0,24);
	$image_data = file_get_contents($url);
	if(!$image_data)
		exit;
	set_transient("pp_$plugin_$type", 1, 60*60*24);
	header("Content-Type: image/$image_extension");
	echo $image_data;
	
	exit;
}