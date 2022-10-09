<?php

namespace AAPlug;

defined( 'ABSPATH' ) || die();

/* --------------------------------------------------------
 * Helpers
 */


function get_plugin_option( $option_name ) {
	$option = get_option( AA_PLUGIN_OPTION );
	if ( is_array( $option ) ){
		$option = $option[$option_name];
	}
	return empty($option) ? null : $option;	
}


/**
 * get excerpt for description and auto-poster
 * 'cause `$excerpt_more` may be filtered by theme, thus affect `get_the_excerpt`
 */
/**
 * Note
	add_filter( 'get_the_excerpt', 'wp_trim_excerpt'  );

		`wp_trim_excerpt()`  uses  `wp_trim_words()`, whose behavior is decided by L10n, as in wp-includes/formatting.php
			`if ( strpos( _x( 'words', 'Word count type. Do not translate!' ), 'characters' ) === 0 )`
	CJK l10n has 'words' translated to 'characters_excluding_spaces' or 'characters_including_spaces'
	without the l10n translation, it has NO EFFECT on CJK text
 */

if ( !function_exists('getExcerpt') ){
function getExcerpt( $post = null, $remove_breaks = true,  $length = 160 ) {
/*
	ref:
	- post-template.php  | $post = get_post( $post ); if ( empty( $post ) ) - return '';
	- default-filters.php| add_filter( 'get_the_excerpt', 'wp_trim_excerpt'  );
	- formatting.php     | wp_trim_excerpt( $text = '', $post = null ){}
 */
	if ( !isset($post) )
		global $post;
	//setup_postdata($post);

	if ( post_password_required( $post ) )
		return 'Protected.'; // no excerpt for protected post

	if ( !empty($post->post_excerpt) )
		$text = $post->post_excerpt;
	else {
		$text = get_the_content(''); // $post->post_content
		if ( get_bloginfo( 'version' )[0] >= 5 )
			$text = excerpt_remove_blocks( $text );
	}

	$text = strip_shortcodes( $text );
	$text = apply_filters( 'the_content', $text );

	$text = str_replace( ']]>', ']]&gt;', $text );
	$text = wp_strip_all_tags( $text, $remove_breaks );
	$text = html_entity_decode( $text );

	//$excerpt_length = apply_filters( 'excerpt_length', $length );
	//$text = wp_trim_words( $text, $excerpt_length, '[&hellip;]' ); //`wp_strip_all_tags` applied, excess \s removed
	$text = truncate( $text, $length, "[&hellip;]" );
	return $text;
}
}


/**
 * eg. truncate( $text, 200, '[&hellip;]' )
 *
 * no space: CJ, Thai, Khmer, Lao, Burmese (Myanmar)
 */
function truncate($text, $length = 160, $ellipsis = '...', $encoding = 'UTF-8') {

	$ellipsis = html_entity_decode($ellipsis);
	$text = trim($text);

	if ( function_exists('mb_strlen') && function_exists('mb_substr') )
	{
		if (mb_strlen($text, $encoding) <= $length)
			return $text;

		$x = $length - mb_strlen($ellipsis, $encoding);
		$truncation = mb_substr($text, 0,  $x, $encoding);
		$boundary   = mb_substr($text, $x-1,2, $encoding);

		// try to avoid breaking a word in the middle
		if ( preg_match('/\pL{2}/u', $boundary) ) {
			$truncation = preg_replace('/(?<!\pL)\pL{1,20}\z/u', '', $truncation, 1);
		}
		//if ( preg_match('/\w\w/', $boundary) )
		//	$truncation = preg_replace('/(?<=\W)\w{1,20}\z/u', '', $truncation);

	} else {
		if (preg_match( '/^.{' . $length . '}/mu', $text, $match ))
			$truncation = $match[0];
		else
			return $text;
	}

	return rtrim($truncation) . $ellipsis;
}


function maybePrintAttr( $arr, $key ){
	if ( empty($arr[$key]) )
		return '';
	return ' ' . $key . '="' . esc_attr($arr[$key]) . '"';
}


function prefixRelativeURL( $url ) {
	// oh so brutal
	if ( $url && 0 !== stripos($url,'http') && 0 !== strpos($url,'//') && ':' !== $url[0] ) {
		$s   = ('/' === $url[0]) ? '' : '/';
		$url = '//' . $_SERVER['HTTP_HOST'] . $s . $url;
	}
	return $url;
}


function scriptRemoveDeps( $script, $handle = '', $deps = [] ) { // $wp_styles|$wp_script
	if ( !isset($script->registered) || 
		 !isset($script->registered[$handle]) || 
		  empty($script->registered[$handle]->deps)
		)
		return;
	$script->registered[$handle]->deps = array_diff(  
	$script->registered[$handle]->deps, $deps );
}


