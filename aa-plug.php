<?php
/**
 * Plugin Name:       AA-Plug
 * Description:       De-bloat, Stealth, Markdown, Open Graph, IndexNow, Post to Mastodon, Maintenance, etc.
 * Version:           1.0.0
 * Requires PHP:      5.6
 * Requires at least: 4.4
 * Tested up to:      4.9
 * Author:            Lux
 * Author URI:        https://github.com/luxvw
 * Plugin URI:        https://github.com/luxvw/AA-Plug/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       aa
 */
namespace AAPlug;

if ( ! defined( 'ABSPATH' ) ) die();

/* --------------------------------------------------------
 * Activation / Deactivation, Pluin Constants
 */
register_activation_hook(   __FILE__, __NAMESPACE__ . '\activate'   );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\deactivate' );

/* --------------------------------------------------------
 * Constants, global variables
 */
defined( 'AA_PLUGIN_URL'  ) || define( 'AA_PLUGIN_URL',  plugins_url( '', __FILE__ )  );  // https://.../plugins/foo
defined( 'AA_PLUGIN_BASE' ) || define( 'AA_PLUGIN_BASE', plugin_basename( __FILE__ )  );

defined( 'UPLOAD_URL'  ) || define( 'UPLOAD_URL',  wp_get_upload_dir()['baseurl'] );      // https://.../uploads
defined( 'IS_CP'       ) || define( 'IS_CP', function_exists( 'classicpress_version' ) ); // identify ClassicPress

/* --------------------------------------------------------
 * Loader
 */
//defined( 'AA_PLUGIN_OPTION' ) || define( 'AA_PLUGIN_OPTION', aa_settings' );
const AA_PLUGIN_OPTION = 'aa_settings';


$plug_option = get_option( AA_PLUGIN_OPTION );
is_array( $plug_option ) || $plug_option = [];


	require_once( __DIR__ . '/function.php'  );
	require_once( __DIR__ . '/plug-tweaks.php'  );
	require_once( __DIR__ . '/plug-parser.php'  );
	require_once( __DIR__ . '/plug-shortcodes.php'  );
if ( is_admin() ) {
	// load backend part
	require_once( __DIR__ . '/settings.php'        );
	include_once( __DIR__ . '/plug-be-debloat.php' );
	include_once( __DIR__ . '/plug-be-request.php' );
}


/* --------------------------------------------------------
 * functions
 */


function activate() {

	// Initialize plugin option
	$option = get_option( AA_PLUGIN_OPTION );

	if ( false === $option || !is_array( $option ) ) {
		$option = [];
		add_option( AA_PLUGIN_OPTION, $option );
	}

	// Maybe generate IndexNow key
	if ( empty($option['indexnow_key']) ) {
		$option['indexnow_key'] = str_replace( '-', '', wp_generate_uuid4() );
		update_option( AA_PLUGIN_OPTION, $option );
	}

	// Unset some rewrite rules
		add_filter( 'rewrite_rules_array'      , __NAMESPACE__ . '\cb_unset_rewrites', 999 );
	if ( !empty($option['no_author_achive']) ) {
		add_filter( 'author_rewrite_rules' , '__return_empty_array' ); # `author/nice_name` | `?author_name=`
	}
	//	add_filter( 'post_format_rewrite_rules', '__return_empty_array' ); # `type/:post_format/*` | `?post_format=`, aside/quote/...

	flush_rewrite_rules(); //hard flush: true* - update .htaccess, false - rewrite_rules transient

	// Do some subtle hardening work
	do_hardening();

}


function deactivate() {
	flush_rewrite_rules();
}


/* --------------------------------------------------------
 * Hooks
 */
 
# Hardening work after core upgrade
	add_action( '_core_updated_successfully', __NAMESPACE__ . '\do_hardening' );

/* --------------------------------------------------------
 * functions
 */

function cb_unset_rewrites( $rules ) {
	foreach ( $rules as $rule => $rewrite ) {
		if ( false !== strpos( $rewrite, 'embed=true') || 
			 false !== strpos( $rewrite, 'attachment=$') ) {
				 unset( $rules[$rule] );
			 }
	}
	return $rules;
}

function do_hardening() {
	if ( file_exists( ABSPATH . 'readme.html'   ) ) @unlink( ABSPATH . 'readme.html' );
	if ( file_exists( ABSPATH . 'license.txt'   ) ) @unlink( ABSPATH . 'license.txt' );
	if ( file_exists( ABSPATH . 'wp-config.php' ) )  @chmod( ABSPATH . 'wp-config.php', 0600);
//	if ( file_exists( ABSPATH . 'wp-config.php' ) && '0600' !== substr( sprintf('%o', fileperms(ABSPATH . 'wp-config.php')), -4) )
}

