<?php
/**
 * Tweaks
 * 
*/

namespace AAPlug;

defined( 'ABSPATH' ) || die();
/** Debloats
 -------------------------------------------------------- */

defined( 'IMAGE_EDIT_OVERWRITE' ) || define('IMAGE_EDIT_OVERWRITE', true );

add_action( 'init'        , __NAMESPACE__ . '\cb_debloat_on_init' );
add_action( 'login_init'  , __NAMESPACE__ . '\cb_debloat_on_login');

# Disable password-strength-meter, Jquery Migrate(frontend)
add_action( 'wp_default_scripts', __NAMESPACE__ . '\cb_debloat_scripts', 99); 

# Register meta
add_action( 'rest_api_init', __NAMESPACE__ . '\cb_register_meta' );


# Disable Embeds, Attachment pages, User enumeration via query var

// `author=id` query enumerates `user_nicename`, which === `user_login` by default
add_filter( 'rewrite_rules_array', __NAMESPACE__ . '\cb_unset_rewrites', 999);
add_filter( 'query_vars', function($qvars){ return array_diff( $qvars, ['author','embed','attachment','attachment_id'] ); } );
add_filter( 'attachment_link', function( $link, $post_id ){	// Attachment page-link -> file-link
			$attach_url = wp_get_attachment_url( $post_id, 'full' );
			return ( $attach_url ? $attach_url : $link );
		}, 10, 2 
);
// `post_type` non public for new attachments, WP 4.4+
add_filter( 'register_post_type_args', function ( $args, $slug ) {
			if ( 'attachment' === $slug  ) {
				$args['public'] = false; // won't display slugged permalink in attachment edit page
				$args['publicly_queryable'] = false;
			} return $args;
		}, 10, 2 
);
// close `comment_status` for new attachments, WP 4.3+
// prefix slugs for new attachments,
add_filter( 'wp_insert_attachment_data',
		function ( $data, $postarr ) {
			$post_id = ( empty( $data['ID'] ) && $postarr['post_ID'] ) ? $postarr['post_ID'] : $data['ID'];
			// not an update
			if ( empty( $post_id )  ) {
			// NOTE: for new attachment we haven't an post_ID here yet
			//	$data['post_name'] = hash( 'crc32', wp_generate_uuid4() );
				$data['post_name'] = 'file-' . $data['post_name']; 			// 'file-foo-bar'
				$data['post_name'] = wp_unique_post_slug( $data['post_name'], $post_id, $data['post_status'], $data['post_type'] ,$data['post_parent'] );
			}
			$data['comment_status'] = 'closed'; // close comments of new attachments
			return $data;
		}, 19, 2 
);


/* --------------------------------------------------------
 * 
 */

global $plug_option;

# Updates

if ( $plug_option['no_updates'] ){
	defined( 'AUTOMATIC_UPDATER_DISABLED' ) || define( 'AUTOMATIC_UPDATER_DISABLED', true );
	add_filter('automatic_updater_disabled', '__return_true');
}
	add_filter('auto_update_translation',  '__return_false' ); // annoying


# Inject content after page head/footer (frontend)

if ( ! is_admin() ) {
	if ( $plug_option['head_html'] ) {
		add_action( 'wp_head', function(){
				global $plug_option;
				echo $plug_option['head_html'];
			}, 1000);
	}
	if ( $plug_option['foot_html'] ) {
		add_action( 'wp_footer', function(){
				global $plug_option;
				echo $plug_option['foot_html'];
			}, 1000);
	}
}


# Set excerpt length ( default: 55 words, not good for CJK users )

if ( $plug_option['excerpt_length'] ) {
	$setting = $plug_option['excerpt_length'];
	add_filter('excerpt_length', function() use ($setting) { return (int)$setting; }, 19 );	
}


# Disable author achive

if ( $plug_option['no_author_achive'] ) {
	// Disable Author Archive
	add_filter( 'author_link', function(){return home_url('/');}, 10 );	//
	add_filter( 'author_rewrite_rules' , '__return_empty_array' );		// Unset rewrite rules: /author/
	add_filter( 'query_vars', function($qvars){ return array_diff($qvars,['author_name']); } );
}


# Disable comment avatars

if ( $plug_option['no_comment_av'] ) {
	//	add_filter( 'get_avatar', function( $avatar ){global $in_comment_loop; return $in_comment_loop ? '' : $avatar;});
	add_filter( 'pre_get_avatar', function(){
		global $in_comment_loop;
		return $in_comment_loop ? '' : null;
	});
}


# Remove resource query string (not in use now)

/*
if ( $plug_option['no_src_ver'] ) {
	if ( IS_CP )  {
		add_filter( 'classicpress_asset_version', '__return_empty_string'     ); // CP assets version query
	} else        {
		add_filter( 'style_loader_src' , __NAMESPACE__ . '\cb_remove_src_ver' ); // WP assets version query
		add_filter( 'script_loader_src', __NAMESPACE__ . '\cb_remove_src_ver' );
	}
}
*/

/** Security
 -------------------------------------------------------- */

# Login: Add 1s delay ( ref: /plugin/wp-login-delay/ )
// add_filter( 'wp_authenticate_user', function($user){sleep(1);return $user;}, 1, 1);

# Disable username login, allow email only. WP 4.5+

if ( $plug_option['login_restrict'] && function_exists('wp_authenticate_email_password') ) { // WP 4.5+
	switch ( $plug_option['restrict_login'] ){
		case 'email':
			remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 ); // added by default-filters.php
			break;
		case 'username':
			remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );
			break;
	}
}


# Disable XMP-RPC

if ( $plug_option['no_xmlrpc'] ) {
	// Disable XMP-RPC pingback or disable all methods. [ class-wp-xmlrpc-server.php ]
	switch ( (int) $plug_option['no_xmlrpc'] ) {
		case 1:
		// disable all
			add_filter( 'xmlrpc_enabled', '__return_false'      ); // methods requiring auth, not pingback/custom endpoints
			add_filter( 'xmlrpc_methods', '__return_empty_array');
			// block xmlrpc.php access
			add_action( 'init', function(){
				if ( false === stripos( $_SERVER['REQUEST_URI'], '/xmlrpc.php' ) )
					return;
				status_header( 404 );
				nocache_headers();
				die();
			});
			break;
		case 2:
		// disable Pingback only
			add_filter( 'xmlrpc_methods', function( $methods ) {
						unset( $methods['pingback.ping'] );
						unset( $methods['pingback.extensions.getPingbacks'] );
						return $methods;
					});
			break;
	}
	// Remove from HTTP header
	if ( IS_CP || version_compare( get_bloginfo( 'version' ), '4.4', '>=' ) )
		//add_action( 'wp', function(){header_remove('X-Pingback');}, 9999);
		add_filter( 'pings_open', '__return_false', 99, 1); // should stop adding X-Pingback header
	else
		add_filter( 'wp_headers', function($headers){unset($headers['X-Pingback']);return $headers;} );
}


# Restrict REST API access

if ( $plug_option['api_restrict'] ) {
	// (default-filters.php)
	remove_action( 'xmlrpc_rsd_apis',   'rest_output_rsd'            ); // xmlrpc.php?rsd: <api name="WP-API" ... >
//	remove_action( 'wp_head',           'rest_output_link_wp_head'   ); // removed elsewhere
	remove_action( 'template_redirect', 'rest_output_link_header', 11); // HTTP header: Link: <https://../wp-json/>; rel=...

	if ( IS_CP || version_compare( get_bloginfo('version'), '4.7', '>=' ) ) {
		// WP 4.7+
		add_filter( 'rest_authentication_errors', function($result) {
				// ref: //developer.wordpress.org/rest-api/frequently-asked-questions/
				// If a previous authentication check was applied, pass along
				if ( true === $result || is_wp_error( $result ) ) {
					return $result;
				}
				if ( ! is_user_logged_in() && false !== stripos( $_SERVER['REQUEST_URI'], 'wp/v2' ) ) { // '/wp-json/wp/v2/..'
					return new \WP_Error( 'rest_not_logged_in', __( 'You are not currently logged in.' ), ['status' => 401] );
				}
				return $result;
			}, 19, 1);

		add_filter( 'rest_index', function(){
				return new \WP_Error( 'rest_not_logged_in', __( 'You are not currently logged in.' ), ['status' => 401] );
			});

	} else { // WP 4.4~WP4.6
		add_filter('rest_enabled'      , '__return_false');
		add_filter('rest_jsonp_enabled', '__return_false');
	}
}


/** Privacy
 -------------------------------------------------------- */


# Improve comment privacy, GDPR friendly

if ( $plug_option['no_comment_ip'] ) {
	$setting = $plug_option['no_comment_ip'];
	add_action( 'init' , function() use ( $setting ) {
		// Don't log comment IP/UA of logged in users
		if ( is_user_logged_in() ){
			add_filter( 'pre_comment_user_ip'      , '__return_empty_string' );
			add_filter( 'pre_comment_user_agent'   , '__return_empty_string' );
		//	add_filter( 'pre_comment_author_email' , '__return_empty_string' );	//	plus 'pre_comment_author_url'
		} else {
		// Visitor comments, 1: dont' log, 2: IP anonymized, 3: hashed
			switch ( (int)$setting ) {
				case 1:
						add_filter( 'pre_comment_user_ip', '__return_empty_string' );
					break;
				case 2:
						add_filter( 'pre_comment_user_ip', function($ip){return hash( 'crc32', $ip );} );
					break;
				case 3:
					if ( function_exists( 'wp_privacy_anonymize_ip' ) )
						add_filter( 'pre_comment_user_ip', 'wp_privacy_anonymize_ip', 99, 1 ); // WP 4.9.6+
					break;
			}
		}
	});
}


/** SEO
 * - post meta `_image` is used
 -------------------------------------------------------- */


# Serve IndexNow keyfile
if ( $plug_option['indexnow'] && $plug_option['indexnow_key'] ) {

	$indexnow_key = $plug_option['indexnow_key'];

	add_action( 'init',  function() use ( $indexnow_key ) {
			if ( '/' . $indexnow_key . '.txt' !== $_SERVER['REQUEST_URI'] ) return;

			header( 'Content-Type: text/plain' );
			header( 'X-Robots-Tag: noindex' );
			status_header( 200 );
			echo $indexnow_key;
			die();
	}, 2);
}

# OGP tags

if ( $plug_option['ogp'] ) {
	// $site_logo_url
	if ( function_exists( 'has_custom_logo' ) && has_custom_logo() ) {  // WP 4.5+
		$site_logo_url = esc_url( wp_get_attachment_url( get_theme_mod('custom_logo') ) );
	} else {
		$site_logo_url = esc_url( $plug_option['site_logo'] );
	}
	add_action( 'wp_head', __NAMESPACE__ . '\cb_add_meta_tags', 99 );
}


# Feed: Add source link to full-text feed content

if ( $plug_option['feed_linkback'] ) {
	add_filter( 'the_content_feed', function($content) {
		global $post;
		return $content . '<p><a href="' . get_permalink($post->ID) . '">Source</a></p>';
	});
}


/** 503 Maintenace mode
 -------------------------------------------------------- */

if ( $plug_option['maintenance'] ) {
	add_action( 'init',  __NAMESPACE__ . '\cb_maintenance', 2);
}


/** Callbacks
 -------------------------------------------------------- */


function cb_add_meta_tags() {

	if ( is_search() || is_404() || is_attachment() ) return;

	$meta = [
	//	'og:site_name'   => get_bloginfo( 'name' ),
		'og:title'       => wp_get_document_title(),  // WP 4.4+
		'og:type'        => 'website',
		'og:url'         => '',
		'og:site_name'   => get_bloginfo( 'name' ),
		];
	
	global $site_logo_url;
	if ( $site_logo_url )
		$meta['og:image'] = $site_logo_url;


	if ( is_singular() ) {
		## 
		global $post;
		setup_postdata($post);

		$meta['og:url']  = get_permalink();

		// Decide og:image
		$foo = get_post_meta( $post->ID, '_image', true );
		if ( !empty($foo) ) {
			$meta['og:image'] = $foo;
		} elseif ( has_post_thumbnail() ) {
			$meta['og:image'] = get_the_post_thumbnail_url();
		}

		if ( is_single() ) {

			$meta['og:type'] = 'article';

			if ( empty($meta['og:image']) ) {
				// get 1st image
				$cont = get_the_content( '', false );
				if ( preg_match('/<img [^>]*?src=[\'"](.*?)[\'"][^<]*?>/i', $cont, $matches) ) {
					$meta['og:image'] = $matches[1];
				}
				/*
				elseif ( 'text/markdown' === $post->post_mime_type && preg_match('/!\[.*?\]\((.*?)\)/i', $cont, $matches) ) {
					$meta['og:image'] = explode( ' ', $matches[1] )[0] ;
				}
				*/
			}

			// Category
			$categories = get_the_category();
			if ( ! empty( $categories ) ) {
				foreach ( $categories as $cat ) {
					printf( '<meta property="article:section" content="%s" />'."\n", esc_attr($cat->name) );
				}
			}

			// Excerpt.
			// `get_the_excerpt()` may not work well, since `excerpt_more` may be filtered by theme
			$meta['og:description'] = getExcerpt($post);

			//switch( get_post_format( $post ) ){}
		}

		$meta['og:image'] = prefixRelativeURL( $meta['og:image'] );

	} elseif ( is_archive() ) {
		## Archive Page
		if ( is_date() ) {
			echo '<meta name="robots" content="noindex,follow" />'."\n";
		} elseif ( is_author() ) {
			// $meta['og:image']   = get_avatar_data( get_the_author_meta( 'ID' ) )['url']; // WP 4.2+
		}
		// $meta['og:url']  = '';
		$meta['og:description'] = wp_strip_all_tags( get_the_archive_description(), true ); // WP 4.1+
		
	} else {
		## 
		$meta['og:url']         = get_bloginfo( 'url' );
		$meta['og:description'] = get_bloginfo( 'description' );
	}

	// Print og: tags
	foreach( $meta as $key => $value ) {
		echo joinMetaTag( 'property', $key, $value );
	}
	echo joinMetaTag( 'name', 'description', $meta['og:description'] );
	echo     '<meta name="twitter:card" content="summary" />'."\n";
}


function cb_debloat_on_init() {

	# Heartbeat
//	wp_deregister_script('heartbeat');

	global $wp_version;

	# Debloat <head> tags
	// default-filters.php
	foreach ( ['rsd_link', 'wp_generator', 'wlwmanifest_link', 'wp_shortlink_wp_head'] as $action ) {
		remove_action('wp_head',  $action ); // maybe remove 'adjacent_posts_rel_link_wp_head'
	}
	//	remove_action( 'wp_head', 'feed_links_extra' ,  3);  # feeds: category, tag, search ...
	foreach ( ['rss2_head','commentsrss2_head','rss_head','rdf_header','atom_head','comments_atom_head','opml_head','app_head'] as $hook ){
		remove_action( $hook   , 'the_generator' ); // Feed <generator> Tags
	}
	add_filter( 'the_generator', '__return_empty_string' ); // alternate, in case there's more

	// Remove WPEmoji. Native emoji font is enough nowadays
	// WP 4.2+. if we're on WP 4.2-, we probably should write another plugin
		remove_action('wp_head'            , 'print_emoji_detection_script', 7);
		remove_action('wp_print_styles'    , 'print_emoji_styles'          );
		remove_action('admin_print_scripts', 'print_emoji_detection_script');	// from wp-admin
		remove_action('admin_print_styles' , 'print_emoji_styles'          );
		remove_filter('embed_head'         , 'print_emoji_detection_script');	// from Embeds
		remove_filter('the_content_feed'   , 'wp_staticize_emoji'          );	// from Feeds
		remove_filter('comment_text_rss'   , 'wp_staticize_emoji'          );
		remove_filter('wp_mail'            , 'wp_staticize_emoji_for_email');	// from E-mails
		   add_filter('emoji_svg_url'      , '__return_false'       );			// dns-prefetch [WP4.6+]
	//	remove_action('wp_head'            , 'wp_resource_hints', 2 );			// WP 4.6+, alternate
	//	remove_filter('customize_controls_print_styles', 'wp_resource_hints', 1 );
		   add_filter('tiny_mce_plugins'   , function($plugins){
						return is_array($plugins) ? array_diff( $plugins, ['wpemoji','wpembed'] ) : $plugins;
			}); // 'wpembed' combo

	// Remove Embeds, with it's REST api routes
	if ( IS_CP || version_compare( $wp_version, '4.4', '>=' ) )
	{	// REST API <head> tag
		remove_action( 'wp_head',  'rest_output_link_wp_head' );
		// oEmbed
		remove_action( 'rest_api_init'          , 'wp_oembed_register_route'     ); // REST API routes '/oembed/1.0/embed|proxy'
		remove_filter( 'rest_pre_serve_request' , '_oembed_rest_pre_serve_request', 10 );
		remove_action( 'wp_head'                , 'wp_oembed_add_discovery_links'); // oEmbed Discovery Links
		remove_action( 'wp_head'                , 'wp_oembed_add_host_js'        ); // wp_enqueue_script('wp-embed')
		remove_action( 'the_content_feed'       , '_oembed_filter_feed_content'  );
		// oEmbed discovery
		   add_filter( 'embed_oembed_discover'  , '__return_false' );		// don't explore <a href=""> for oEmbed
	//	remove_filter( 'pre_oembed_result'      , 'wp_filter_pre_oembed_result',10);
	}

	// Remove recent comments inline style
	global $wp_widget_factory;
	remove_action( 'wp_head', [ $wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style' ] );

	// Debloat default content filters
	add_filter( 'run_wptexturize', '__return_false'     ); // WP 4.0, formatting.php:wptexturize(). Annoying

	remove_filter( 'comment_text', 'make_clickable'  , 9); // stop auto linking, might reduce spam
	remove_filter( 'comment_text', 'capital_P_dangit',31); // Wordpress -> WordPress
	foreach ( ['the_content','the_title','wp_title'] as $filter ){
		remove_filter( $filter   , 'capital_P_dangit',11); // WP5.8 + 'document_title', leave it, anyway
	}

	// Remove mediaelement js, which loads with [video] [audio] shortcodes as well as Media Widgets. 
	// Use browser native control instead ( [playlist] still loads that sh.. )
	add_filter( 'wp_video_shortcode_library', '__return_empty_string' );
	add_filter( 'wp_audio_shortcode_library', '__return_empty_string' );

}

function cb_debloat_on_login(){
	// Remove WP branding
	// if ( 'wp-login.php' === $GLOBALS['pagenow'] || did_action( 'login_init' ) ) {}
		add_filter( 'login_title', function(){return __('Log In');} );	// WP-4.9+
	if ( !is_multisite() ) { // multisite is fine
		add_filter( 'login_headerurl'  , '__return_empty_string' );	// no wordpress.org link. | or use home_url( '/' )
		add_filter( 'login_headertitle', '__return_empty_string' );	// WP 5.1- | or get_bloginfo( 'name', 'display' );
		add_filter( 'login_headertext' , '__return_empty_string' );	// WP 5.2+
	}
	//	add_filter( 'login_errors', function(){return 'Failed';} );	// error msg hints username. but login_footer() has it anyway

	// Remove dashicons style
	add_action( 'login_enqueue_scripts', function(){
		scriptRemoveDeps( $GLOBALS['wp_styles'], 'login', ['dashicons'] );  	// 45kb+
		});
	// Remove dns-prefetch, added by default-filters.php
	remove_filter( 'login_head', 'wp_resource_hints', 8 );	// WP 4.6+
}

function cb_debloat_scripts($scripts) {
		// ref: script-loader.php
		// Remove password-strength-meter. zxcvbn(800k+)<--zxcvbn-async<-password-strength-meter<-user-profile
			scriptRemoveDeps( $scripts, 'user-profile', ['password-strength-meter'] );
		if ( !is_admin() ) {
			scriptRemoveDeps( $scripts, 'jquery', ['jquery-migrate'] );
		} else {
			scriptRemoveDeps( $scripts, 'admin-comments', ['quicktags'] );
			scriptRemoveDeps( $scripts, 'media-views', ['wp-mediaelement'] );
			// scriptRemoveDeps( $scripts, 'wp-edit-post', ['wp-embed'] );	// WP 5.? +
			// Note. we can't remove 'word-count' (<-post)
			// (script-loader.php, class.wp-dependencies.php)
			$scripts->remove(['heartbeat', 'autosave', // autosave(5k)->heartbeat(5k)<-wp-auth-check
							//	'wp-embed',
							]);
		}
}


function cb_maintenance(){
	// let registered users in
	if ( is_user_logged_in() && current_user_can('activate_plugins') )
		return;

	// status_header( 503 );
	$protocol = wp_get_server_protocol();
	header( "$protocol 503 Service Unavailable", true, 503 );
	header( 'Retry-After: 21600' ); // 6h
	header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
	header( 'Content-Type: text/html; charset=utf-8' );

	echo 'Site maintenance, please come back later.';
	/* or we could draw a page instead:
?>
<!DOCTYPE html>
<html><head>
<meta charset="utf-8"/>
<title><?php _e( 'Maintenance' ); ?></title>
</head><body>
<h1><?php _e( 'Maintenance' ); ?></h1>
<p></p>
</body></html>
<?php
	*/
	die();
	/* or a somewhat heavier alternate:
	nocache_headers();
	wp_die( '<h1>Maintenance</h1><p>Please check back soon.</p>', 'Maintenance', ['response'=>'503'] );
	*/
}


function cb_register_meta() {
	// Register og:image meta '_image' field to include it in REST API
	register_meta( 'post', '_image', [
		'type'        => 'string',
		'description' => 'AA-Plug meta for post image.',
		'single'      => true,
		'show_in_rest'=> true,
	] );	// or hook to rest_api_init
}


function cb_remove_src_ver( $src ) {
	return strpos( $src, 'ver=' ) ? remove_query_arg( 'ver', $src ) : $src;
}

function joinMetaTag( $propname, $prop, $content ) {
	return ( !empty($content) ) ? 
		'<meta ' . $propname . '="' . $prop . '" content="' . esc_attr( $content ) . '" />'."\n" : "";
	// htmlspecialchars( '$', ENT_QUOTES) (make '&amp;hellip;' ), so esc_attr( '$' )
}
