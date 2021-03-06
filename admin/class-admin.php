<?php
/**
 * @package Admin
 */

if ( !defined( 'DCM_VERSION' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	die;
}

/**
 * Class that holds most of the admin functionality.
 */
class DCM_Admin {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'requires_wordpress_version') );
		add_action( 'admin_init', array( $this, 'options_init' ) );
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_filter( 'plugin_action_links', array( $this, 'add_action_link' ), 10, 2 );
	}

	/**
	 * Register all the options needed for config pages
	 * @return void
	 */
	public function options_init() {
		// '1' in elem_* means it’s enabled
		$options = array(
			'elem_contributor' => '1',
			'elem_coverage'    => '1',
			'elem_creator'     => '1',
			'elem_date'        => '1',
			'elem_description' => '1',
			'elem_format'      => '1',
			'elem_identifier'  => '1',
			'elem_language'    => '0',
			'elem_publisher'   => '1',
			'elem_relation'    => '1',
			'elem_rights'      => '1',
			'elem_source'      => '1',
			'elem_subject'     => '1',
			'elem_title'       => '1',
			'elem_type'        => '1',
			'rights_url'       => '',
			'output_html'      => 'xhtml',
			'post_types'       => $this->list_post_types( false ),
		);
		add_option( "_joost_dcm_options", $options, "", "yes" );
		register_setting( 'joost_dcm_options', '_joost_dcm_options', array( $this, 'dcm_validate') );
	}


	/**
	 * Returns an array with the current post types as key and 
	 * either the name of the post type as value OR a 1
	 * @param   str $output_val If set to 'names' the returned array valus will the post type's name
	 * @return  arr           Custom post types
	 */
	public function list_post_types( $output ) {
		$args = array(
			'public' => true,
		);
		$output = array();
		if ($output === 'names') {
			foreach ( get_post_types( $args, 'objects' ) as $post_type => $vars) {
				$output[$post_type] = $vars->labels->name;
			} 
			return $output;
		} else {
			return get_post_types( $args );
		}
		
	}

	/**
	 * Register the menu item & page
	 * @return void
	 */
	public function register_settings_page() {
		add_options_page(
			__( 'Dublin Core Meta Tags', 'dc-meta-tags' ),
			__( 'DC Meta Tags', 'dc-meta-tags' ),
			'manage_options',
			'dcm_settings',
			array( $this, 'config_page' )
		);
	}

	/**
	 * Loads the form for the settings page
	 * @return void
	 */
	public function config_page() {
		if ( isset( $_GET['page'] ) && 'dcm_settings' == $_GET['page'] )
			include( DCM_PATH . '/admin/pages/settings.php' );
	}

	/**
	 * Sanitize and validate input
	 * @param  arr $options    Admin options with values
	 * @return arr             Sanitized admin options with values
	 */
	public function dcm_validate( $options ) {
		// Our first value is either 0 or 1
		$options['elem_contributor']= ( $options['elem_contributor'] == 1 ? 1 : 0 );
		$options['elem_coverage']   = ( $options['elem_coverage'] == 1 ? 1 : 0 );
		$options['elem_creator']    = ( $options['elem_creator'] == 1 ? 1 : 0 );
		$options['elem_date']       = ( $options['elem_date'] == 1 ? 1 : 0 );
		$options['elem_description']= ( $options['elem_description'] == 1 ? 1 : 0 );
		$options['elem_format']     = ( $options['elem_format'] == 1 ? 1 : 0 );
		$options['elem_identifier'] = ( $options['elem_identifier'] == 1 ? 1 : 0 );
		$options['elem_language']   = ( $options['elem_language'] == 1 ? 1 : 0 );
		$options['elem_publisher']  = ( $options['elem_publisher'] == 1 ? 1 : 0 );
		$options['elem_relation']   = ( $options['elem_relation'] == 1 ? 1 : 0 );
		$options['elem_rights']     = ( $options['elem_rights'] == 1 ? 1 : 0 );
		$options['elem_source']     = ( $options['elem_source'] == 1 ? 1 : 0 );
		$options['elem_subject']    = ( $options['elem_subject'] == 1 ? 1 : 0 );
		$options['elem_title']      = ( $options['elem_title'] == 1 ? 1 : 0 );
		$options['elem_type']       = ( $options['elem_type'] == 1 ? 1 : 0 );
		$options['output_html']     = wp_filter_nohtml_kses( $options['output_html'] );
		$options['rights_url']      = wp_filter_nohtml_kses( $options['rights_url'] );
		foreach ($options['post_types'] as $key => $val)
			$options['post_types'][$key] = wp_filter_nohtml_kses( $val );

		return $options;
	}

	/**
	 * Checks if the current WP install is newer than $wp_version
	 * @return void
	 */
	public function requires_wordpress_version() {
		global $wp_version;
		$plugin = DCM_BASENAME;
		$plugin_data = get_plugin_data( DCM_MAINFILE, false );

		if ( version_compare($wp_version, DCM_MIN_WP_VERSION, "<" ) ) {
			if( is_plugin_active( $plugin ) ) {
				// deactivate plugin, print error message
				deactivate_plugins( $plugin );
				// TRANSLATORS: first placeholder for plugin name, second for version number
				$msg_title = sprintf( __( '%1$s %2$s not activated', 'licence-picker' ), $plugin_data['Name'], $plugin_data['Version'] );
				// TRANSLATORS: first placeholder for current WordPress version, second for required version
				$msg_para = sprintf( __( 'You are running WordPress version %1$s. This plugin requires version %2$s or higher, and has been deactivated! Please upgrade WordPress and try again.', 'licence-picker' ), $wp_version, DCM_MIN_WP_VERSION );
				$msg_back = __( 'Back to WordPress admin', 'licence-picker' );
				wp_die(  sprintf( '<h1>%s</h1><p>%s</p><p><a href="%s">%s</a></p>' , $msg_title, $msg_para, admin_url(), $msg_back ) );
			}
		}
	}

	/**
	 * Add a link to the settings page to the plugins list
	 *
	 * @staticvar string $this_plugin holds the directory & filename for the plugin
	 * @param array  $links array of links for the plugins, adapted when the current plugin is found.
	 * @param string $file  the filename for the current plugin, which the filter loops through.
	 * @return array $links
	 */
	public function add_action_link( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) 
			$this_plugin = 'dc-meta-tags/dc-meta-tags.php';
		if ( $file == $this_plugin ) {
			$settings_link = '<a href="' . admin_url( 'admin.php?page=dcm_settings' ) . '">' . __( 'Settings', 'dc-meta-tags' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}
}

// Globalize the var first as it's needed globally.
global $dcm_admin;
$dcm_admin = new DCM_Admin();