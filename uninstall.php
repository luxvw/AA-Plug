<?php 

defined( 'WP_UNINSTALL_PLUGIN' ) || die();

/* not supported yet
if( is_multisite() )
	delete_network_option( null, 'aa_plug_settings' );
 */

delete_option('aa_settings');
delete_transient('aa_autopost');
delete_transient('aa_indexnow');
