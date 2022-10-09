<?php
namespace AAPlug;
defined( 'ABSPATH' ) || die();
/* --------------------------------------------------------
 * Custom Shortcodes
 */

add_shortcode( 'block', __NAMESPACE__ . '\sc_block' );
add_shortcode( 'idb'  , __NAMESPACE__ . '\sc_idb'   );


// [block name="" class="" id=""]line1 \n line2 \n [shortcode][/block]
function sc_block( $atts, $content = "" ) {
	$atts = shortcode_atts( ['name'=>'div','class'=>'','id'=>''], $atts, 'block' );
	$content = trim($content);
	$content = str_replace("\n",'<br />', $content );
	$html = "<{$atts['name']}" . maybePrintAttr( $atts, 'class' ) . maybePrintAttr( $atts, 'id' ) . '>' 
			 . do_shortcode( $content )
			 . "</{$atts['name']}>";
	return $html;
}
add_filter('strip_shortcodes_tagnames', function($tags_to_remove){
			return array_diff( $tags_to_remove, ['block'] );
		}); // WP 4.7+



/*
	idb: internet database

	[idb src="goodreads" id=""]
	[idb src="douban" type="book" id=""]
	[idb src="wikipedia" lang="en" id=""]
	[idb src="bangumi" id=""]
	[idb src="neodb" id=""]

	Link formats of social cataloging sites:

	https://book.douban.com/subject/:id/
	https://movie.douban.com/subject/:id/
	https://neodb.social/movies/:id/
	https://neodb.social/books/:id/
	https://www.themoviedb.org/tv/:id
	https://www.themoviedb.org/movie/:id
 */
function sc_idb( $atts ) {

	$atts = shortcode_atts( ['id'=>'','src'=>'','type'=>'','lang'=>''], $atts, 'idb' );

	if ( ! $atts['src'] )
		return;

	$html_template = '<a href="%s" title="%s" rel="external" target="_blank">%s</a>';
	$icon = '<img src="' . AA_PLUGIN_URL . "/assets/icon/{$atts['src']}.svg" . '" class="svg-icon" style="height:1.25em;vertical-align:text-bottom" />';

	switch ( $atts['src'] ) {
		case 'goodreads':
		case 'gr':
			if ( 0 === strpos( $atts['id'], 'series/' ) ) {
				$link = "https://www.goodreads.com/{$atts['id']}";	//www.goodreads.com/series/:id
			} else {
				$link = "https://www.goodreads.com/book/show/{$atts['id']}";
			}
				$html = sprintf( $html_template, $link, 'GoodReads', $icon );
			break;
		case 'douban':
			if ( in_array($atts['type'], ['book','movie']) ) {
				$link = "https://{$atts['type']}.douban.com/subject/{$atts['id']}/";
				$html = sprintf( $html_template, $link, 'Douban', $icon );
			}
			break;
		case 'bangumi':
				$link = "https://bgm.tv/subject/{$atts['id']}/";
				$html = sprintf( $html_template, $link, 'Bangumi', 'bangumi' );
			break;
		case 'wikipedia':
		case 'wiki':
				$atts['lang'] || $atts['lang'] = 'en';
				$link = "https://{$atts['lang']}.wikipedia.org/wiki/{$atts['id']}";
				$html = sprintf( $html_template, $link, 'Wikipedia', $icon );
			break;
	}
	return $html ? $html : '';
}
