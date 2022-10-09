<?php
/** 
 * 
 */
namespace AAPlug;

defined( 'ABSPATH' ) || die();
/* --------------------------------------------------------
 * Hooks
 */

# on option change

/*
add_action( 'update_option_' . AA_PLUGIN_OPTION, function($old_value, $value){
	// won't fire if the old option value is exactly the same as the new one
	
	}, 10, 2
);
*/

/* --------------------------------------------------------
 * debloats
 */

# Debloat admin panel

add_action( 'admin_init', __NAMESPACE__ . '\cb_debloat_admin' );

function cb_debloat_admin() {
	//wp_deregister_script( 'autosave' );
	// core update nag
	remove_action(        'admin_notices', 'update_nag', 3);
	remove_action('network_admin_notices', 'update_nag', 3);
	remove_filter('update_footer',    'core_update_footer');
	// footer branding
	   add_filter('admin_footer_text','__return_empty_string', 10); // Note. some plugins might use this area
	// set legacy uploader as default
	set_user_setting( 'uploader', 1 );
}


# Disable Editor ( TinyMCE and QuickTags ). WP 4.0+

if ( get_plugin_option('md') ) {
	add_filter('wp_editor_settings', function($settings){
			$settings['default_editor'] = 'quicktags';
			$settings['quicktags'] = false;
			$settings['tinymce']   = false;
		//	$settings['teeny']     = true;
		//	$settings['wpautop']   = false;
			return $settings;
	});
	// Remove TinyMCE/QuickTags scripts ( ref: class-wp-editor.php )
	add_action('wp_enqueue_editor', function(){
			remove_action( 'admin_print_footer_scripts', ['_WP_Editors', 'editor_js'      ], 50);
			remove_action( 'admin_print_footer_scripts', ['_WP_Editors', 'enqueue_scripts'],  1);
	});
	// Remove some scripts
	add_filter('wp_editor_expand' , '__return_false'    ); // remove 'editor-expand' script
	// Disable the visual editor for users
	add_filter('user_can_richedit', '__return_false', 50);
}


# Agressive debloat, disable Media templates & scripts, breaks "Choose Image"

if ( get_plugin_option('debloat_more') ) {

/*
admin: custom-background/header, edit-form-advanced, upload.php('grid' mode), includes\media.php
wpinc\widget: class-wp-widget-media/text.php
wpinc\customize: class-wp-customize-media/header-image-control.php
wpinc\media.php

add_action( 'admin_print_scripts-widgets.php', array( $this, 'enqueue_admin_scripts' ) );
remove_action( 'admin_print_scripts-widgets.php', ['WP_Widget_Media', 'enqueue_admin_scripts' ] );
*/
	add_action( 'wp_enqueue_media', function(){ 		// in wp_enqueue_media(). ref: media.php
				wp_dequeue_script( 'media-editor' );
				wp_dequeue_script( 'media-audiovideo' );
				wp_dequeue_script( 'mce-view' ); 		// TinyMCE view API, live preview certain shortcodes
				wp_dequeue_script( 'image-edit' );
				wp_dequeue_style( 'media-views' );
				wp_dequeue_style( 'imgareaselect' );
				# Media templates
				remove_action('admin_footer', 'wp_print_media_templates');
				remove_action('wp_footer'   , 'wp_print_media_templates');
				remove_action('customize_controls_print_footer_scripts' , 'wp_print_media_templates');
	});
	# 
	add_action( 'wp_default_scripts',
			function( $scripts ){
				$scripts->remove([
							//	'moxiejs', 'plupload', // moxiejs(88k)<-plupload(15k)<-wp-plupload(5k) <-media-views(98k)<-media-editor(10k)
								'media-views', 'media-editor',
								'imgareaselect',	// jquery.imgareaselect(9k)
								'mediaelement',		// 'mediaelement-vimeo', //156+67+1+..
								'wp-mediaelement',	// Audio/Video Media JS Player
								'media-widgets',
							]);
				// background/site icon/logo image settings will fail, while the customizer do loads 
				scriptRemoveDeps( $scripts, 'custom-background', ['media-views'] );
				scriptRemoveDeps( $scripts, 'customize-views'  , ['imgareaselect', 'media-editor', 'media-views'] );
			}, 99
	);
	# Workaround to repair the "Remove featured image" js function, since it's in media-editor.js which we detached
	add_action( 'admin_post_thumbnail_html', 
			function ( $content, $post_id, $thumbnail_id ){
				$nonce = wp_create_nonce( "set_post_thumbnail-$post_id" );
				// $help_text = "<a href='#' onclick='WPRemoveThumbnail( \"$ajax_nonce\");return false;'>Remove</a>";
				return $content . '<script>(function($){ $("#postimagediv").on("click","#remove-post-thumbnail",function(){'
					 . "WPRemoveThumbnail('$nonce');return false;"
					 . '}); })(jQuery);</script>';
			}, 99, 3
	);
	# Remove editor's media button, since it's useless now
	add_filter('wp_editor_settings', function($settings) {
			$settings['media_buttons'] = false;
			return $settings;
	});
}


/* --------------------------------------------------------
 * Disable updates
 */

if ( 2 === (int) get_plugin_option('no_updates') ) {
	remove_action(    'init' , 'wp_schedule_update_checks' );
	add_action( 'admin_init', function(){
		// admin-filters.php
		remove_action( 'upgrader_process_complete',  ['Language_Pack_Upgrader', 'async_upgrade'], 20);
		remove_action( 'upgrader_process_complete', 'wp_version_check' , 10);
		remove_action( 'upgrader_process_complete', 'wp_update_plugins', 10);
		remove_action( 'upgrader_process_complete', 'wp_update_themes' , 10);
		// update.php
		remove_action( 'admin_init', '_maybe_update_core'   ); // check every 12h
		remove_action( 'admin_init', '_maybe_update_plugins');
		remove_action( 'admin_init', '_maybe_update_themes' );
		remove_action( 'wp_version_check'    , 'wp_version_check' );
		remove_action( 'wp_maybe_auto_update', 'wp_maybe_auto_update');

		remove_all_action( 'load-update.php', 10 );
		remove_all_action( 'load-update-core.php', 10 );
		/*
		foreach ( ['load-update.php','load-update-core.php'] as $hook ){
			remove_action( $hook, 'wp_update_plugins' );
			remove_action( $hook, 'wp_update_themes'  );
		} unset $hook;
		*/
		remove_action( 'load-plugins.php',  'wp_update_plugins' );
		remove_action( 'load-themes.php',   'wp_update_themes'  );
		remove_action( 'wp_update_plugins', 'wp_update_plugins' );
		remove_action( 'wp_update_themes',  'wp_update_themes'  );
		wp_clear_scheduled_hook( 'wp_maybe_auto_update' );
		wp_clear_scheduled_hook( 'wp_version_check'     );
		wp_clear_scheduled_hook( 'wp_update_plugins'    );
		wp_clear_scheduled_hook( 'wp_update_themes'     );
	}, 99);
}

