<?php
/*
 * Auto-post to Mastodon
 * Auto-submit to IndexNow
 * @package AA-Plug
 */
namespace AAPlug;

defined( 'ABSPATH' ) || die();
/* --------------------------------------------------------
 * Hooks
 */

$maybe_update_msg = false;

if ( get_plugin_option('fedi_post') ) {
	add_action( 'transition_post_status', __NAMESPACE__ . '\cb_autopost', 10, 3 );
	$maybe_update_msg = true;
}
if ( get_plugin_option('indexnow') ) {
	add_action( 'transition_post_status', __NAMESPACE__ . '\cb_indexnow', 10, 3 );
	add_action( 'post_updated'          , __NAMESPACE__ . '\cb_indexnow_updated', 99, 3 ); // update an old post (post.php)
	$maybe_update_msg = true;
}
if ( $maybe_update_msg ) {
	add_filter( 'post_updated_messages' , __NAMESPACE__ . '\cb_post_updated_message', 10, 1 ); # show response message
}

/* --------------------------------------------------------
 * Callables
 */


function cb_autopost( $new_status, $old_status, $post ) {

	if ( 'publish' !== $new_status || 'publish' === $old_status || 'trash' === $old_status ) return;
	if ( 'post' !== $post->post_type ) return;
//	if ( $post->post_modified !== $post->post_date ) return;
//	if ( !empty($post->post_password) ) return;

	setup_postdata( $post );

	# get post categories & tags
	$tags = [];
	$section = '';
	$term_list = get_the_terms( $post, 'category' );
	if ( is_array( $term_list ) ) {
		$tags = wp_list_pluck( $term_list, 'name' );
		$section = empty( $tags[0] ) ? '' : $tags[0] . ' | ' ;
	}
	$term_list = get_the_terms( $post, 'post_tag' );
	if ( is_array( $term_list ) ) {
		$tags = array_merge( $tags, wp_list_pluck( $term_list, 'name' ) );
		foreach ( $tags as $k => $v ) {
			$tags[$k] = str_replace(' ', '_', $v);
			$tags[$k] = str_replace(['/','-'], '', $tags[$k]);
		}
	}
	$tags = empty( $tags ) ? '' : '#' . implode(' #', $tags);

	# the status
	$status = $section . $post->post_title . "\n"
			 . esc_url_raw( get_permalink($post) ) . "\n\n"
			 . wp_strip_all_tags( getExcerpt($post, false) ) . "\n"
			 . $tags; // get_the_excerpt($post) brought some issue here
	// html_entity_decode

	fediPost( $status );
}



function cb_indexnow( $new_status, $old_status, $post ) {
	# published or trashed, plus not protected
	if ( 'publish' !== $new_status || !empty($post->post_password) )
		return;
	if ( !in_array($post->post_type, ['post','page']) )
		return;
	# not newly published
	if ( $old_status === $new_status )
		return; // comment this line to simplify things

//	setup_postdata( $post ); // do we need this?
	indexnow( get_permalink($post) );
}
function cb_indexnow_updated( $post_id, $post, $post_before ) {
	# republish a published
	if ( ( 'publish' !== $post->post_status && 'trash' !== $post->post_status )
		|| 'publish' !== $post_before->post_status
		|| !empty($post->post_password) )
		return;

	if ( $post->post_name !== $post_before->post_name ) {
		// if post slug has changed, submit the old one to notify ablout the redirection status
		indexnow( get_permalink($post_before) );
	} elseif (  $post->post_title   !== $post_before->post_title || 
				$post->post_content !== $post_before->post_content ){
		// submit modified post, skip if content untouched
		indexnow( get_permalink($post) );
	}
}


# Inform you the result of Auto-Post and IndexNow
function cb_post_updated_message( $messages ) {
	// sleep(1);
	if ( !empty( get_transient( 'aa_autopost' ) ) ) {
		$messages['post'][6] .= ' Post to Fedi: ' . get_transient( 'aa_autopost' );	// 'Post published'
		set_transient( 'aa_autopost', '' );
	}
	if ( !empty( get_transient( 'aa_indexnow'  ) ) ) {
		$messages['post'][6] .= ' Indexnow: ' . get_transient( 'aa_indexnow' );
		$messages['post'][1] .= ' Indexnow: ' . get_transient( 'aa_indexnow' );		// 'Post updated.'
		set_transient( 'aa_indexnow' , '' );
	}
	return $messages;
}

/* --------------------------------------------------------
 * Helpers
 */
function fediPost( $status ) {

	$instance_host = get_plugin_option('fedi_host');
	$access_token  = get_plugin_option('fedi_token');
	if ( empty( $instance_host ) || empty( $access_token ) ) 
		return false;

	$visibility    = get_plugin_option('fedi_visibility');
	!empty( $visibility ) || $visibility = null;

	// ref: //developer.wordpress.org/reference/classes/WP_Http/request/
	$options = [
			'method'      => 'POST',
			'body'        => wp_json_encode( ['status' => $status, 'visibility' => $visibility,] ),
			'headers'     => [
				'Content-Type'    => 'application/json',
				'Authorization'   => 'Bearer ' . $access_token,
				'Idempotency-Key' => hash( 'adler32', $status ),
				],
			'redirection' => 1,
			'user-agent'  => 'WordPress/5.0.0;', // hide your version ass, not necessary
		];

	// send request
	$res = wp_safe_remote_request( "https://{$instance_host}/api/v1/statuses", $options );

	// collect response, store in transient for display
	$msg = is_wp_error( $res ) ? 'WP_Error' : ( implode( ' ', $res['response'] ) . '' ); // $res['body']
	set_transient( 'aa_autopost', get_transient( 'aa_autopost' ) . ' ' . $msg );
}

function indexnow( $url ){

	$api_key = get_plugin_option('indexnow_key');
	$searchengine = 'api.indexnow.org';
	// or 'www.bing.com'

	if ( is_array( $url ) ) {
		$site_url  = get_home_url( '/' );
		$site_host = parse_url( $site_url, PHP_URL_HOST );
		$data   = [
				'host'        => $site_host,
				'key'         => $api_key,
				'keyLocation' => $site_url . $api_key . '.txt',
				'urlList'     => $url,
			];
		$options = [
				'method'      => 'POST',
				'body'        => json_encode( $data ),
				'headers'     => [
					'Content-Type'  => 'application/json',
					],
				'user-agent'  => 'WordPress/5.0.0;',
			];
		// send request
		$res = wp_safe_remote_request( "https://{$searchengine}/indexnow", $options );
	} else {
		$url = esc_url_raw( $url );
		// send request
		$res = wp_safe_remote_get( "https://{$searchengine}/indexnow?url={$url}&key={$api_key}" ); // &keyLocation=
	}


	// collect response, store in transient for display
	$msg = is_wp_error( $res ) ? 'WP_Error' : ( implode( ' ', $res['response'] ) . '' ); // $res['body']
	set_transient( 'aa_indexnow', get_transient( 'aa_indexnow' ) . ' ' . $msg );
}


