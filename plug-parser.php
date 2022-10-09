<?php
/*
 * Content parser:
 *  - AutoP (WP default)
 *  - HTML
 *  - Markdown, powered by Parsedown
 * @package AA-Plug
 */
namespace AAPlug;

defined( 'ABSPATH' ) || die();
/* --------------------------------------------------------
 * 
 */

global $plug_option;
if ( ! $plug_option['md'] ) {
	return;
}


# Init Parser
class_exists( '\Parsedown'      ) || require_once( __DIR__ . '/vendor/erusev/Parsedown.php' );
class_exists( '\ParsedownExtra' ) || require_once( __DIR__ . '/vendor/erusev/ParsedownExtra.php' );
class_exists( '\ParsedownAA'    ) || require_once( __DIR__ . '/inc/class-parsedown-ext.php' );

if ( !isset($parser) ) {
	$parser = new \ParsedownAA();
}

# Config parser
$parser->setBreaksEnabled( ! (bool)$plug_option['md_nobr'] );
$parser->setBaseEmojiPath( $plug_option['emoji_path'] ? $plug_option['emoji_path'] : ( wp_make_link_relative( AA_PLUGIN_URL ) . '/assets/emoji/' ) );
$parser->setBaseImagePath( UPLOAD_URL . '/' );


/* --------------------------------------------------------
 * Hooks
 */

# Hooks
	// Add additional inline css for markdown syntax
	add_action( 'wp_head' ,  __NAMESPACE__ . '\cb_md_css', 999); // Note: 'wp_print_styles' hooks too early


	# Deal with content filter
	// Remove content filters ( default-filters,formatting.php )
	foreach ( ['the_content', 'the_excerpt'] as $filter ) {
		remove_filter( $filter   , 'wptexturize'       ); // (10) not so good for markdown parsing
		remove_filter( $filter   , 'wpautop'           ); // Remove autoP, add back later
		remove_filter( $filter   , 'shortcode_unautop' );
	}
	// No smilies
	remove_action( 'init'        , 'smilies_init'   , 5); // force disable. or use option 'use_smilies', whatever
	/*
	remove_filter( 'the_excerpt' , 'convert_smilies'   );
	remove_filter( 'the_content' , 'convert_smilies',20);
	remove_filter( 'comment_text', 'convert_smilies',20);
	*/

	// Add content filters
	add_filter( 'the_content'    , __NAMESPACE__ . '\cb_cr2lf'     ,  9);
	add_filter( 'the_content'    , __NAMESPACE__ . '\cb_parse_text', 12); # (11)do_shortcode (Priority is somwhat tricky)
//	add_filter( 'the_excerpt'    , __NAMESPACE__ . '\cb_parse_text',  9); #
	add_filter( 'get_the_excerpt', __NAMESPACE__ . '\cb_parse_text',  9); # 

	# Deal with `post_mime_type`
	// Save parser flag to $post->post_mime_type, non-mainstream but should be fine.
	add_action( 'post_submitbox_misc_actions', __NAMESPACE__ . '\cb_add_post_mime_type_field'  ); // add the form field (ref: meta-boxes.php)
	add_filter( 'wp_insert_post_data'        , __NAMESPACE__ . '\cb_set_post_mime_type', 19, 2 ); // save it
	add_filter( 'the_preview'                , __NAMESPACE__ . '\cb_preview_post_mime_type', 19); // (10)'the_preview'
	// Add REST API support for 'mime_type' field
	add_filter( 'rest_pre_insert_post', __NAMESPACE__ . '\cb_rest_post_post_add_mime_type', 10, 2 ); // WP 4.7+
	add_filter( 'rest_pre_insert_page', __NAMESPACE__ . '\cb_rest_post_post_add_mime_type', 10, 2 );
	add_filter( 'rest_prepare_post'   , __NAMESPACE__ . '\cb_rest_get_post_add_mime_type', 10, 3 ); 
	add_filter( 'rest_prepare_page'   , __NAMESPACE__ . '\cb_rest_get_post_add_mime_type', 10, 3 ); 

/* --------------------------------------------------------
 * Callables
 */

/** 
 * $post->post_mime_type ( name="_post_mime_type" ), better than using post meta
 * - 'text/markdown'  as Markdown
 * - 'text/html'      as HTML
 * - ''               as AutoP (WP default behavior)
 */
function cb_add_post_mime_type_field( $post = null ) {

	if ( is_attachment() )
		return;

	if ( !isset($post) )
		global $post;

	// ref: meta-boxes.php
	$mime_type = $post->post_mime_type;
	echo '<style>.misc-pub-mime-type::before{content:"\f464" !important;vertical-align:text-bottom !important;}</style>' // see dashicon.woff
		 . '<div class="misc-pub-section misc-pub-revisions misc-pub-mime-type">'
		 . ' Parse as: <select name="_post_mime_type">';
	foreach ( [ 'Default'=>'','Markdown'=>'text/markdown','HTML'=>'text/html'] as $key => $value ) {
		$selected = selected( $mime_type , $value, false );
		echo "<option value='$value' $selected />$key</option>";
	}
	echo '</select></div>';
}

# Workaround to save 'post_mime_type', 'cause we can't save it directly, thanks to `post.php:wp_write_post()`
function cb_set_post_mime_type( $data, $postarr ){
	if ( isset($postarr['_post_mime_type']) )
		$data['post_mime_type'] = $postarr['_post_mime_type'];
	return $data;
}
# Enable markdown for new post preview
function cb_preview_post_mime_type( $post_preview ){
	if ( isset($_POST['_post_mime_type']) )
		$post_preview['post_mime_type'] = $_POST['_post_mime_type'];
	return $post_preview;
}
# REST API
function cb_rest_post_post_add_mime_type( $post, $request ){
	if ( ! in_array( $request['mime_type'], ['text/markdown', 'text/html'] ) )
		$post->post_mime_type = $request['mime_type'];
	return $post;
}
function cb_rest_get_post_add_mime_type( $response, $post, $request){
	// WP_REST_Response
	 if ( isset($post) )
		$response->data['mime_type'] = $post->post_mime_type;
	return $response;
}



// Parse content as Markdown/HTML/AutoP
function cb_parse_text( $content ){

	if ( !is_main_query() )
		return;

	global $post;
	global $parser;

	switch ( $post->post_mime_type ) {
		case 'text/html':
			break;
		case 'text/markdown':
			$content = $parser->text($content);
			$content = shortcode_unautop( $content );
			break;
		default:
			$content = wpautop( $content );
			$content = shortcode_unautop( $content );
			break;
	}
	return $content;
}

// priority issue
function cb_cr2lf( $content ){
	return str_replace(["\r\n", "\r"], "\n", $content);
}


// Additional CSS
function cb_md_css() {
	static $printed = false;
	if ( $printed ) return;
	$printed = true; // dunno if we need this
?>
<style type="text/css">
img.emoji{height:1.5em !important;max-height:1.5em;vertical-align:text-bottom;margin:0;padding:0;border:none;box-shadow:none;display:inline;background:none;}
.spoiler{background:#666;color:transparent;text-decoration:none;}.spoiler:hover{color:#fff !important;transition:color 0.1s;}
</style>
<?php
}
