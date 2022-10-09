<?php

namespace AAPlug;

if ( ! defined( 'ABSPATH' ) ) die();

/* --------------------------------------------------------
 * Settings
 * see 'class-admin-utils.php'
 */


global $wp_version;


/* Plugin Meta box for posts */
$post_meta_box = [
	'post_type' => ['post', 'page'],
	'id'        => 'post-meta-ex',
	'title'     => __('Post Meta Extra', 'aa'),
	'fields'    => [
		[
			'name'       => '_image',
			'type'       => 'text',
			'label'      => 'og:image',
			'attributes' => ['class' => 'large-text' ],
			'description'=> __('', 'aa'),
		],
		/* grouped
		[
			'name' => '_book',
			'type' => 'group',
			'fields' => [],
		]
		*/
	],
];



/* Plugin Option Arrays */
$opt_page = [
	'plugin_base'  => AA_PLUGIN_BASE,
	'parent_slug'  => 'options-general.php',
	'menu_slug'    => 'aa-plug',
	'title'        => 'AA Plug',
	'option_group' => AA_PLUGIN_OPTION,
	'sections'     => [],
	/*
	'scripts'      => [ 'js'  => AA_PLUGIN_BASE . '/inc/admin.js', 
						'css' => AA_PLUGIN_BASE . '/inc/admin.css', ],
	*/
];

// the sections
$opt_page['sections'] = [
[
	'id'    => 'debloat',
	'title' => __('Debloat', 'aa'),
	'fields'=> [
		[
			'name'       => 'debloat',
			'type'       => 'checkbox',
			'label'      => __('Default debloats', 'aa'),
			'description'=> __('Attachment page, WPEmoji, Embeds, Heartbeat, Autosave, Password strength meter, jquery-migrate.js(front-end), MediaElement.js, Junk header tags, Core "update" nag, User enumeration via "author=id" query, etc. Not configurable by now.', 'aa'),
			'attributes' => ['disabled' => 'true', 'checked' => 'true', ],
		//	'fields'     => [],
		],[
			'name'       => 'debloat_more',
			'type'       => 'checkbox',
			'label'      => __('Aggressive debloats (Backend)', 'aa'),
			'description'=> __('Remove media templates and scripts to speed up admin panel. Not recommended as it breaks media related features.', 'aa'),
		],
	/*	[
			'name'       => 'debloat_widgets',
			'type'       => 'checkbox',
			'label'      => __('Disable Widgets: Media, Calendar and Meta', 'aa'),
			'description'=> __('Custom HTML widget should be enough.', 'aa'),
		],
	*/
	],
],[
	'id'    => 'markdown',
	'title' => 'Markdown',
	'fields'=> [
		[
			'name'        => 'md',
			'type'        => 'checkbox',
			'label'       => __('Enable Markdown Parser for posts and pages', 'aa'),
		],[
			'title'       => __('Newlines', 'aa'),
			'name'        => 'md_nobr',
			'type'        => 'checkbox',
			'label'       => __('Do not parse newlines as HTML line breaks', 'aa'),
			'class'       => 'md-settings ' . ( get_plugin_option('md') ? '' : 'hide-if-js' ),
		],[
			'title'       => __('Custom emoji path', 'aa'),
			'name'        => 'emoji_path',
			'type'        => 'text' ,
			'list'        => [ wp_make_link_relative( WP_CONTENT_URL ) . '/uploads/emoji/', ],
			'description' => __('Default path', 'aa') . ': <code>PLUGIN_URL/assets/emoji/</code>',
			'class'       => 'md-settings ' . ( get_plugin_option('md') ? '' : 'hide-if-js' ),
		],
	],
],[
	'id'    => 'seo',
	'title' => __('SEO', 'aa'),
	'fields'=> [
		[
			'title'      => 'Open Graph',
			'name'       => 'ogp',
			'type'       => 'checkbox',
			'label'      => __('Add Open Graph meta tags', 'aa'),
		//	'fields'     => [[],],
		],[
			'title'      => __('Default og:image', 'aa'),
			'name'       => 'site_logo',
			'type'       => 'url',
			'class'      => 'ogp-settings ' . ( get_plugin_option('ogp') ? '' : 'hide-if-js' ),
		],[
			'title'      => 'IndexNow',
			'name'       => 'indexnow',
			'type'       => 'checkbox',
			'label'      => __('Auto-submit URLs to IndexNow', 'aa'),
		],[
			'title'      => 'IndexNow API Key',
			'name'       => 'indexnow_key',
			'type'       => 'text',
			'description'=> __('API key is auto-generated. Normally there&rsquo;s no need to change it.', 'aa'),
			'class'      => 'indexnow-settings ' . ( get_plugin_option('indexnow') ? '' : 'hide-if-js' ),
		],[
			'name'       => 'feed_linkback',
			'type'       => 'checkbox',
			'label'      => __('Add source links to full-text feed contents.', 'aa'),
			'title'      => 'Feeds',
		],
	],
],[
	'id'    => 'autopost',
	'title' => __('Auto-Post', 'aa'),
	'fields'=> [
		[
			'name'       => 'fedi_post',
			'type'       => 'checkbox',
			'label'      => __('Auto-post to Mastodon', 'aa'),
		],[
			'title'      => 'Instance Hostname',
			'name'       => 'fedi_host',
			'type'       => 'text',
			'attributes' => ['placeholder' => 'mastodon.social'],
			'class'      => 'fedi_post-settings ' . ( get_plugin_option('fedi_post') ? '' : 'hide-if-js' ),
		],[
			'title'      => 'Access Token',
			'name'       => 'fedi_token',
			'type'       => 'text',
			'description'=> __('Required scopes', 'aa') . ': <code>write:statuses</code>',
			'class'      => 'fedi_post-settings ' . ( get_plugin_option('fedi_post') ? '' : 'hide-if-js' ),
		],[
			'title'      => __('Visibility', 'aa'),
			'name'       => 'fedi_visibility',
			'type'       => 'select',
			'options'    => [__('Default'),
							 'public'  => 'Public' , 'unlisted' => 'Unlisted', 
							 'private' => 'Private', 'direct'   => 'Direct'  ,
							], 
			'class'      => 'fedi_post-settings ' . ( get_plugin_option('fedi_post') ? '' : 'hide-if-js' ),
		],
	],
],[
	'id'    => 'privacy',
	'title' => __('Privacy', 'aa'),
	'fields'=> [
		[
			'title'      => 'Comment IP logging',
			'name'       => 'no_comment_ip',
			'type'       => 'select',
			'options'    => [ __('Default'),
							  __('Disabled for visitors', 'aa'),
							  __('Hashed for visitors', 'aa'),
							  __('Anonymized for visitors (WP 4.9.6+)', 'aa'),
							],
			'description'=> __('IP and UA logging for registered users will also be disabled.', 'aa'),
		],
	],
],[
	'id'    => 'security',
	'title' => __('Security', 'aa'),
	'fields'=> [
		[
			'title'      => 'XML-RPC',
			'name'       => 'no_xmlrpc',
			'type'       => 'select',
			'options'    => [__('Default'),
							 __('Disable XML-RPC and block xmlrpc.php access', 'aa'),
							 __('Disable pingback only', 'aa'),
							],
			'description'=> __('Better to block xmlrpc.php access with server configuration such as .htaccess.', 'aa'),
		],[
			'title'      => 'REST API',
			'name'       => 'api_restrict',
			'type'       => 'checkbox',
			'label'      => version_compare($wp_version,'4.7','>=')
							 ? __('Restrict core REST API access to authenticated users only', 'aa')
							 : __('Disable REST API', 'aa'),
			'description'=> version_compare($wp_version,'4.7','>=')
							 ? __('Apply to wp/v2 namespace. Third party routes are not affected (e.g. "webmention/1.0").', 'aa')
							 : '',
		],[
			'title'      => 'Login Method',
			'name'       => 'login_restrict',
			'type'       => 'select',
			'options'    => [__('Username and Email'),
							 'email'   =>__('Email login only', 'aa'),
							 'username'=>__('Username login only', 'aa'),
							],
			'description'=> __('Require WP 4.5+', 'aa'),
		],
	],
],[
	'id'    => 'tweaks',
	'title' => __('Tweaks', 'aa'),
	'fields'=> [
		[
			'name'       => 'no_comment_av',
			'type'       => 'checkbox',
			'label'      => __('Disable comment user avatars', 'aa'),
		],[
			'name'       => 'no_author_achive',
			'type'       => 'checkbox',
			'label'      => __('Disable Author Archive', 'aa'),
			'description'=> __('Manually flush the rewrite rules after change, if needed.', 'aa'),
		],[
			'label'      => __('Excerpt length: %s', 'aa') . sprintf(' (<span class="description">%s</span>)', __('Default: 55 words', 'aa')),
			'name'       => 'excerpt_length',
			'type'       => 'number',
			'attributes' => ['min'=> 0, 'step'=>10],
		],[
			'title'      => __('Update Behavior'),
			'name'       => 'no_updates',
			'type'       => 'select',
			'options'    => [__('Default'),
							 __('No auto-updates', 'aa'),
							 __('No update checks', 'aa'),
							],
		],[
			'title'      => __('Head HTML', 'aa'),
			'name'       => 'head_html',
			'type'       => 'textarea',
			'description'=> __('Inject content to page head', 'aa'),
		],[
			'title'      => __('Footer HTML', 'aa'),
			'name'       => 'foot_html',
			'type'       => 'textarea',
			'description'=> __('Inject content to page footer', 'aa'),
		],
	],
],[
	'id'    => 'sitemaintenance',
	'title' => __('Site Maintenance', 'aa'),
	'fields'=> [
		[
			'name'       => 'maintenance',
			'type'       => 'checkbox',
			'label'      => __('Turn on 503 Maintenance Mode', 'aa'),
			'description'=> __('Deactivate your cache plugin if needed.', 'aa'),
		],
	],
],
];



$profile_setting = [
	'plugin_base'  => AA_PLUGIN_BASE,
	'parent_slug'  => 'profile.php',
	'menu_slug'    => NULL,
	'title'        => NULL,
	'sections'     => [
		[
			'id'    => 'debloat',
			'title' => __('Debloat', 'aa'),
			'fields'=> [
				[
					'name'       => 'user_avatar',
					'type'       => 'url',
					'label'      => __('User avatar', 'aa'),
					'attributes' => [],
					'description'=> __('Custom user avatar', 'aa'),
				],
			],
		],
	],
];

/* --------------------------------------------------------
 * Settings
 */

/* do metaboxes */
use AA\Admin;

if ( ! class_exists( '\AA\Admin\Options' ) ) {
	require_once( __DIR__ . '/inc/class-admin-utils.php' );
}


$plugin_options = new \AA\Admin\Options( $opt_page );
$plugin_options->register();

$plugin_metabox = new \AA\Admin\Metabox( $post_meta_box );
$plugin_metabox->register();




# Add plugin link
add_filter('plugin_action_links_' . AA_PLUGIN_BASE, function($actions) {
	$actions[] = '<a href="' . admin_url( 'options-general.php?page=aa-plug' ) . '">' . __('Settings') . '</a>';
	return $actions;
});


# Add additional js to improve the display of plugin page
add_action( 'admin_footer-' . 'settings_page_' . 'aa-plug', 
	function(){
	?>
	<script>
		(function($){
			const togglers = ['md', 'fedi_post', 'indexnow', 'ogp'];
			togglers.forEach((element) => {
				var parent   = $( '#' + element ),
					children = $( '.' + element + '-settings' )
				parent.change(function(){ children.toggleClass( 'hide-if-js', ! this.checked ); });
			});
		})(jQuery);
	</script>
	<?php
	}
, 999); 
