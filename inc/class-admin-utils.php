<?php
/**
 * Plugin option and metabox handler
 */
Namespace AA\Admin;

defined( 'ABSPATH' ) || die();

/**
 * only support grouped option now
 *
 * Todo: add to other pages
 *
 */
class Options {

	use Form;

	protected $type = 'option';
	protected $capability;

	protected $parent_slug;
	protected $slug;
	protected $title;
	protected $group;
	protected $sections;
	protected $scripts;

	public function init( $opt = [] )
	{
		$this->capability  = empty($opt['capability'])  ? 'manage_options'      : $opt['capability'];
		$this->parent_slug = empty($opt['parent_slug']) ? 'options-general.php' : $opt['parent_slug'];
		$this->slug        = $opt['menu_slug'];
		$this->title       = $opt['title'];
		$this->group       = $opt['option_group'];
		$this->sections    = $opt['sections'];
		$this->scripts     = empty($opt['scripts']) ? NULL : $opt['scripts'];
	}

	public function __construct( $opt_page = null )
	{
		// foreach ( $args as $key => $value ) { $this->key = $value; }
		if ( is_array($opt_page) )
			$this->init($opt_page);
	}

	public function register() {
		if ($this->slug) {
			// add submenu
			add_action('admin_menu', [$this,'cb_add_menu' ],     10);
		}
			add_action('admin_init', [$this,'cb_handle_settings']  );
		//to do: network_admin: 'network_admin_menu', ...
		if ( $this->scripts ) {
			add_action('admin_enqueue_scripts', [$this,'cb_add_admin_scripts'] );
		}
	}

	public function cb_add_admin_scripts($hook) {
		if ( 'settings_page_' . $this->slug !== $hook || empty($this->scripts) )
			return;
		$scripts = $this->scripts;
		if ( $scripts['js']  )
			wp_enqueue_script( "{$this->slug}-js",  $scripts['js'], [], false, true );
		if ( $scripts['css'] )
			wp_enqueue_style(  "{$this->slug}-css", $scripts['css'] );
	}


	public function cb_add_menu() {
		add_submenu_page( 
			$this->parent_slug,			// $parent_slug, 
			$this->title,				// $page_title, 
			$this->title,				// $menu_title,
			$this->capability, 			// $capability,
			$this->slug, 				// $menu_slug,
			[$this,'cb_render_page']	// $callback
		);
	}
		public function cb_render_page() {
			global $title;
			$action = 'options.php';
			echo "<div class='wrap'><h1>{$title}</h1><form method='post' action='{$action}'>";
			settings_fields( $this->group );		// $option_group, match in `register_setting()`
			global $plugin_page;
			do_settings_sections( $plugin_page );
			submit_button();
			echo '</form></div>';
		}

	public function cb_handle_settings()
	{
		# Register
		if ( $this->group ) {
			$args = [ 'type'=>'array', 'sanitize_callback'=> [$this,'sanitizeArray'] ]; // sanitizeArray()
			$option_group = $this->group;
			register_setting(
				$option_group,	// $option_group, oneof ['general', 'writing', 'reading', 'discussion', 'media'], 'options'
				$this->group,	// $option_name
				$args			// $args[]
			);
		} else {
			// only support grouped options for now
			return;
		}

		# Render
		foreach ( $this->sections as $sec )	{
			// register and draw a section
			add_settings_section(
				$sec['id'],						// $id, 
				$sec['title'],					// $title,
				[$this,'cb_render_section'], 	// $callback,
				$this->slug 					// $page,   or oneof ['general', 'writing', 'reading', 'discussion', 'media']
			);

			// register and draw a field group
			foreach ( (array)$sec['fields'] as $key => $fd ) {
				$this->handle_field( $fd, $sec['id'] );
			}

		}
	}


	private function handle_field( $fd, $section )
	{
		if ( empty($fd) )
			return;

		$fd['value'] = $this->get_value( $fd['name'] );
		if ( in_array($fd['type'], ['text','url','email','tel','password','number']) )
			$fd['label_for'] = $fd['name'];

		add_settings_field(
			$fd['name'],				// $id, 
			$fd['title'],				// $title,
			[$this,'printFieldHTML'],	// $callback,
			$this->slug,				// $page_slug,
			$section,					// $section='default' (Optional),
			$fd							// $args = [] (Optional)
		);

/*
		if ( $fd['fields'] ) {
			foreach ( (array) $fd['fields'] as $sub_fd ) {
				$this->handle_field( $sub_fd, $section );
			}
		}
*/
	}


	public function cb_render_section() {
		echo '<hr />';
	}


	// Get field value
	private function get_value( $option_name = '' ){
		if ( $this->group ){
			$option = (array) get_option( $this->group );
			return ( array_key_exists($option_name, $option) ? $option[$option_name] : null );
		} else {
			$option = get_option( $option_name );
			return (false !== $option) ? $option : null;
		}
	}

	// drop empty values, convert string num to int num
	public function sanitizeArray( $data )
	{
		if ( is_array($data) ) {
			foreach ( $data as $key => $value ) {
				if ( empty($value) ){
					unset($data[$key]); // drop empty value, don't store in database
				} elseif ( '0' !== $value[0] && preg_match( '/^\d{1,3}$/', $value ) ){
					$data[$key] = (int)$value; // convert to int
				}
			}
		}
		return $data;
	}
}

/**
	[
	'id'      => '',
	'title'   => '',
	'group_name' => '', // optional
	'fields'  => [
			[	'name'  => '_parse', 
				'type'  => 'select', 
				...
			],
		],
	];
 *
 * Note. `register_meta()` method not included, because this file is supposed to be loaded into admin panel only
 */
class Metabox {

	use Form;

	protected $type = 'metabox';
	protected $post_type;
	protected $id;
	protected $title;
	protected $fields;

	protected $nonce;

	public function init( $metabox = [] ) {
		$this->id       = $metabox['id'];
		$this->title    = $metabox['title'];
		$this->fields   = (array)$metabox['fields'];
		$this->post_type= (array)$metabox['post_type'];
		$this->nonce    = "nonce_{$this->id}";
	}

	public function __construct( $metabox = null ) {
		if ( is_array($metabox) )
			$this->init($metabox);
	}

	public function register() {
		// Todo: maybe support comment metabox 'add_meta_boxes_comment'
		add_action( 'add_meta_boxes', [ $this,'cb_add'  ] );
		add_action( 'save_post'     , [ $this,'cb_save' ] );
	}
	public function cb_add() {
		foreach ( $this->post_type as $post_type ) {
			add_meta_box(
				$this->id,						// $id,
				$this->title,					// $title,
				[$this,'cb_render_mb_fields'],	// $callback,
				$post_type,						// $screen,   $post_type/'link'/'comment'/null*
				'advanced',						// $context,  'normal'/'side'/'advanced'*(Post only)
				'high',							// $priority, 'high/core/default/low'
				null							// array $callback_args = null, 2nd parameter passed to $callback
			);
		}
		// add more ?
	}

	public function cb_save( $post_id ) {
		// Verify nonce
		if ( !isset($_POST[$this->nonce]) || !wp_verify_nonce($_POST[$this->nonce],"{$this->nonce}_action")
			|| ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) )
			return;

		// Cluster save
		foreach ( $this->fields as $fd) {
			if ( ! array_key_exists( $fd['name'], $_POST ) ) continue;

			$value = $this->sanitize_value( $_POST[$fd['name']], $fd);
			if ( $value )
				update_post_meta( $post_id, $fd['name'], $value );
			else
				delete_post_meta( $post_id, $fd['name'] );
		}
	}

	public function cb_render_mb_fields( $post ) 
	{
		echo "<div class='form-table aa-metabox'>"; //wrap it for styling
		foreach ( $this->fields as $fd ) {
			// grouped meta field
			if ( $fd['fields'] ) {
				echo "<div class='nested-fields'>";
				foreach ( $fd['fields'] as $sub_fd) {
					$sub_fd['value'] = $this->get_value( $sub_fd['name'], $post, $fd['name'] );
					$this->printFieldHTML( $sub_fd, $fd );
				}
				echo '</div>';
			} else {
			// plain meta field
				$fd['value'] = $this->get_value( $fd['name'], $post );
				echo '<p>';
				$this->printFieldHTML( $fd );
				echo '</p>';
			}
		}
		wp_nonce_field( "{$this->nonce}_action", $this->nonce );
		echo '</div>';
	}

	private function sanitize_value( $value, $fd ) {

		if ( !is_array($value) ) {
			$value = trim($value);
			if ( empty($value) )
				return null;
			switch ( $fd['type'] ) {
				case 'url':
					$value = esc_url($value);
					break;
				case 'checkbox':
					$value = (int)$value;
					break;
			}
			return $value;
		} else {
			foreach ( (array)$fd['fields'] as $sub_fd ) {
				if ( isset($value[$sub_fd['name']]) )
					$value[$sub_fd['name']] = $this->sanitize_value( $value[$sub_fd['name']], $sub_fd );
			}
			return $value;
		}
	}

	private function get_value( $meta_name = '', $post = null, $meta_group = null ) {

		if ( !isset($meta_group) ){
			$meta = get_post_meta( $post->ID, $meta_name, true );
			return ( false !== $meta && '' !== $meta ) ? $meta : null;
		} else {
			$meta = (array) get_post_meta( $post->ID, $meta_group, true );
			return ( array_key_exists($meta_name, $meta) ? $meta[$meta_name] : null );
		}
	}

}

trait Form {

	// currently not in use
	public function printFieldsHTML( $fields = null ) {
	//	if ( ! isset($fields) ) $fields = $this->fields;
		if ( empty($fields) )
			return;
		foreach ( (array) $fields as $field ) {
			$this->printFieldHTML( $field );
		}
	}

	// Print single form control html
	public function printFieldHTML( $fd = null, $parent_fd = null ) {

		if ( empty($fd) || !is_array($fd) )
			return;

		isset($fd['id']) || $fd['id'] = $fd['name'];

		// grouped name
		if ( $this->group && !empty($fd['name']) ){
			$fd['name'] = "{$this->group}[{$fd['name']}]";
		}

		// nested field, for metabox
		if ( isset($parent_fd) && !empty($parent_fd['name']) ) {
			$fd['name'] = "{$parent_fd['name']}[{$fd['name']}]";
			$fd['id'] = $parent_fd['name'] . '-' . $fd['id'];
		}

		// Prepare attributes <ELEMENT id="" name="" type="" list="" ... >
		$att = array_intersect_key( $fd, ['id'=>NULL,'name'=>NULL,'type'=>NULL] );
		$att = !empty($fd['attributes']) ? array_merge( $att, (array) $fd['attributes'] ) : $att;
		// maybe add attribute: "list"="list-id"
		if ( $fd['list'] )
			$att['list'] = 'list-' . $fd['id'];


		// wrap our field, we can style it later
	//	printf( '<div class="aa-field aa-%s-field" id="%s-field">', $fd['type'], $fd['id'] );

		// draw form control
		switch ( $fd['type'] ) {

			// group flag, nothing to draw
			case 'group':
				break;

			// raw html
			case 'html':
				echo $fd['html'];
				break;

			// <label id='' >...</label>
			case 'label':
				unset( $att['type'] );
				unset( $att['name'] );
				printf( '<label %1$s>%2$s</label>', $this->joinAtt($att), $fd['label'] );
				break;

			// <textarea id='' name='' class=''>...</textarea>
			case 'textarea':
				unset( $att['type'] );
				isset( $att['class']) || $att['class'] = 'large-text code';
				echo ( $fd['label'] ? "<label for='$att[id]'>{$fd['label']}</label>" : '' );
				printf( '<textarea rows="2" %s>%s</textarea>', $this->joinAtt($att), $fd['value'] );
				break;

			// <select ><option value="" selected>...</option></select>
			case 'select':
				unset( $att['type'] );
				$atts = $this->joinAtt($att);
				echo ( $fd['label'] ? "<label for='$att[id]'>{$fd['label']}</label>" : '' );
				echo "<select $atts >";
				foreach ( (array)$fd['options'] as $k => $v ) {
					$selected = selected( (string) $fd['value'], (string)$k, false );
					echo "<option value='$k' $selected >$v</option>";
				}
				echo '</select>';
				break;

			// <label><input type="radio" checked /> ...</label>
			case 'radio':
				$atts = $this->joinAtt($att);
				foreach ( (array)$fd['options'] as $k => $v ) {
					$checked = checked( (string) $fd['value'], (string)$k, false );
					echo "<label><input $atts value='$k' $checked /> $v</label>";
				}
				break;

			// <label><input type="checkbox" checked /> ...</label>
			case 'checkbox':
				$atts = $this->joinAtt($att);
				$checked = checked( (int) $fd['value'], 1, false );
				echo "<label><input $atts value=1  $checked /> {$fd['label']}</label>";
				break;

			case 'hidden':
				$att['value'] = $fd['value'];
				$atts = $this->joinAtt($att);
				echo "<input $atts />";
				break;

			// <input type="" value=""/>: 
			case 'text':
			case 'url':
			case 'email':
			case 'tel':
			case 'password':
			case 'number':
			case 'file':
			case 'range':
			case 'color':
			case 'date':
			case 'month':
			case 'time':
			case 'search':
				if ( !isset($att['class']) ){
					if ( 'number' === $att['type']) {
						$att['class'] = 'small-text';
					} elseif ( in_array($att['type'], ['text','url']) ) {
						$att['class'] = 'regular-text';
					}
				}
				$att['value'] = $fd['value'];
				$atts = $this->joinAtt($att);

				if ( false != strpos( $fd['label'], '%s') ) {
					printf( "<label>{$fd['label']}</label>", "<input $atts />" );
				} else {
					echo ( $fd['label'] ? "<label for='$att[id]'>{$fd['label']}</label>" : '' );
					echo "<input $atts />";
				}

				// maybe print datalist
				if ( !empty($fd['list']) ) {
					echo "<datalist id='list-$fd[id]'>";
					foreach ( (array)$fd['list'] as $opt ) {
						printf( '<option value="%s">', htmlspecialchars( $opt, ENT_QUOTES ) );
					}
					echo '</datalist>';
				}
				break;

			// others
			default:
				break;
		}

		// maybe print description
		if ( !empty( $fd['description'] ) )
			echo "<p class='description'>{$fd['description']}</p>";

	//	echo '</div>';

		// maybe print sub fields, for options only

		/*
		*/
		if ( $fd['fields'] && 'option' === $this->type ) {
			foreach ( $fd['fields'] as $sub_fd ) {
				if ( ! is_array($sub_fd) ) return;
				if ( 'checkbox' === $fd['type'] ) {
					$sub_fd['class'] .= " {$fd['id']}-settings " . ( $fd['value'] ? '' : 'hide-if-js' );
				}
				$sub_fd['value'] = $this->get_value( $sub_fd['name'] );
				$this->printFieldHTML( $sub_fd );
			}
			return;
		}

	}


	// join element attributes, skip unset ones
	private function joinAtt( $array ) {
		$s = '';
		foreach ( $array as $key => $value ) {
			if ( isset( $value ) ) {
				$s .= $key . '="' . htmlspecialchars( $value, ENT_QUOTES ) . '" ';
			//	$s .= $key . '="' . esc_attr( $value ) . '" ';
			}
		}
		return $s;
	}

}
