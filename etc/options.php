<?php
/**
 * My Car Options Configuration
 *
 * This file contains the configuration options for the My Car.
 * It defines the structure of the plugin's options and settings pages.
 *
 * @package    iWorks
 * @subpackage My Car
 * @author     Marcin Pietrzak <marcin@iworks.pl>
 * @copyright  2026-PLUGIN_TILL_YEAR Marcin Pietrzak
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0
 * @version    1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get plugin options configuration
 *
 * Returns an array containing the configuration for the plugin's options pages
 * and settings.
 *
 * @return array Array of options configuration
 * @since 1.0.0
 */
if ( ! function_exists( 'iworks_my_car_options' ) ) {
	/**
	 * Returns an array containing the configuration for the plugin's options pages
	 * and settings.
	 *
	 * @return array Array of options configuration
	 * @since 1.0.0
	 */
	function iworks_my_car_options() {
		/**
		 * Initialize empty options array
		 */
		$options = array();

		/**
		 * Parent page for submenu items: the Cars post type menu
		 */
		$parent = 'edit.php?post_type=iworks_car';

		/**
		 * Main settings configuration
		 *
		 * Defines the structure of the main options page including:
		 * - Version number
		 * - Page title
		 * - Menu type
		 * - Options array
		 * - Metaboxes array
		 * - Subpages array
		 */
		$options['index'] = array(
			/**
			 * Current version of the options configuration
			 */
			'version'    => '0.0',

			/**
			 * Title of the options page
			 */
			'page_title' => __( 'Settings', 'my-car' ),

			/**
			 * Menu type for the options page
			 *
			 * Possible values:
			 * - 'options'      - Add as a top-level menu item (default)
			 * - 'submenu'      - Add as a submenu item (requires 'parent' to be set)
			 * - 'management'   - Add under Tools menu
			 * - 'theme'        - Add under Appearance menu
			 * - 'posts'        - Add under Posts menu
			 * - 'pages'        - Add under Pages menu
			 * - 'users'        - Add under Users menu (or Profile for single)
			 * - 'plugins'      - Add under Plugins menu
			 * - 'comments'     - Add under Comments menu
			 * - 'dashboard'    - Add under Dashboard menu
			 * - 'settings'     - Add under Settings menu
			 * - 'media'        - Add under Media menu
			 * - 'custom'       - Custom menu position (requires 'menu_slug' to be set)
			 */
			'menu'       => 'submenu',

			/**
			 * Parent page for submenu items
			 *
			 * Required when 'menu' is set to 'submenu' or when nesting under another menu.
			 * Can be one of the following:
			 * - The file name of a standard WordPress admin page (e.g., 'edit.php' for Posts)
			 * - The value of 'menu_slug' from another options page
			 * - The plugin file if you want to nest under a plugin's main menu
			 *
			 * Common WordPress admin page values:
			 * - 'index.php'                  - Dashboard
			 * - 'edit.php'                   - Posts
			 * - 'upload.php'                 - Media
			 * - 'edit.php?post_type=page'    - Pages
			 * - 'edit-comments.php'          - Comments
			 * - 'themes.php'                 - Appearance
			 * - 'plugins.php'                - Plugins
			 * - 'users.php'                  - Users
			 * - 'tools.php'                  - Tools
			 * - 'options-general.php'        - Settings
			 * - 'options-general.php?page=YOUR_PAGE' - Custom settings page
			 *
			 * Example for nesting under a plugin's main menu:
			 * 'parent' => 'my-plugin-slug',
			 */
			'parent'     => $parent,

			/**
			 * Use tabs for options page
			 *
			 * Possible values:
			 * - true - use tabs
			 * - false - options will be shown flat on one screen
			 */
			'use_tabs'   => true,

			/**
			 * Array of options fields
			 */
			'options'    => apply_filters(
				'iworks/my-car/etc/config/options',
				array(
					array(
						'type'  => 'heading',
						'label' => __( 'General', 'my-car' ),
						'since' => '1.0.0',
					),
					array(
						'name'              => 'units_distance',
						'type'              => 'select',
						'th'                => __( 'Distance Unit', 'my-car' ),
						'description'       => __( 'Unit used for distances and odometer readings.', 'my-car' ),
						'options'           => array(
							'km' => esc_html__( 'Kilometers (km)', 'my-car' ),
							'mi' => esc_html__( 'Miles (mi)', 'my-car' ),
						),
						'default'           => 'km',
						'sanitize_callback' => 'sanitize_key',
						'since'             => '1.0.0',
					),
					array(
						'name'              => 'currency',
						'type'              => 'select',
						'th'                => __( 'Currency', 'my-car' ),
						'description'       => __( 'Currency used for costs and expenses.', 'my-car' ),
						'options'           => array(
							'PLN' => esc_html__( 'Polish Złoty (PLN)', 'my-car' ),
							'EUR' => esc_html__( 'Euro (EUR)', 'my-car' ),
							'USD' => esc_html__( 'US Dollar (USD)', 'my-car' ),
							'GBP' => esc_html__( 'British Pound (GBP)', 'my-car' ),
						),
						'default'           => 'PLN',
						'sanitize_callback' => 'sanitize_text_field',
						'since'             => '1.0.0',
					),
					array(
						'type'  => 'heading',
						'label' => __( 'Cars', 'my-car' ),
						'since' => '1.0.0',
					),
					array(
						'name'              => 'car_enable_archive',
						'type'              => 'checkbox',
						'th'                => __( 'Cars Archive', 'my-car' ),
						'description'       => __( 'Enable the public cars archive page.', 'my-car' ),
						'default'           => 0,
						'sanitize_callback' => 'absint',
						'classes'           => array( 'switch-button' ),
						'since'             => '1.0.0',
					),
					array(
						'name'              => 'car_show_vin',
						'type'              => 'checkbox',
						'th'                => __( 'Show VIN', 'my-car' ),
						'description'       => __( 'Display the VIN number on public car pages.', 'my-car' ),
						'default'           => 0,
						'sanitize_callback' => 'absint',
						'classes'           => array( 'switch-button' ),
						'since'             => '1.0.0',
					),
					array(
						'type'  => 'heading',
						'label' => __( 'Data', 'my-car' ),
						'since' => '1.0.0',
					),
					array(
						'name'              => 'delete_on_uninstall',
						'type'              => 'checkbox',
						'th'                => __( 'Delete Data on Uninstall', 'my-car' ),
						'description'       => __( 'Remove all plugin data (posts, options, meta) when the plugin is uninstalled.', 'my-car' ),
						'default'           => 0,
						'sanitize_callback' => 'absint',
						'classes'           => array( 'switch-button' ),
						'since'             => '1.0.0',
					),
					array(
						'type'  => 'heading',
						'label' => __( 'Misc', 'my-car' ),
						'since' => '1.0.0',
					),
					array(
						'name'              => 'admin_menu_priority',
						'type'              => 'number',
						'th'                => __( 'Admin Menu Priority', 'my-car' ),
						'description'       => __( 'Position of the Cars menu in the admin sidebar. Lower value = higher placement.', 'my-car' ),
						'default'           => 60,
						'sanitize_callback' => 'absint',
						'since'             => '1.0.0',
					),
				)
			),

			/**
			 * Array of metaboxes
			 */
			'metaboxes'  => apply_filters( 'iworks/my-car/etc/config/metaboxes', array() ),

			/**
			 * Array of subpages
			 */
			'pages'      => apply_filters( 'iworks/my-car/etc/config/pages', array() ),
		);

		/**
		 * Return the complete options configuration
		 */
		return $options;
	}
}
