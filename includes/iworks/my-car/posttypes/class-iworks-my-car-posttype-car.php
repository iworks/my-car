<?php
/**
 * Class for custom Post Type: Car
 *
 * @since 1.0.0

Copyright 2026-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

this program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

defined( 'ABSPATH' ) || exit;

require_once 'class-iworks-my-car-posttype.php';

/**
 * Class for custom Post Type: Car
 */
class iworks_my_car_posttype_car extends iworks_my_car_posttype {

	/**
	 * Post type name
	 *
	 * @since 1.0.0
	 * @var string $post_type Post type identifier
	 */
	protected string $post_type = 'car';

	/**
	 * Taxonomy: brand
	 *
	 * @since 1.0.0
	 * @var string $taxonomy_brand Taxonomy identifier
	 */
	private string $taxonomy_brand = 'car_brand';

	/**
	 * Taxonomy: body type
	 *
	 * @since 1.0.0
	 * @var string $taxonomy_body_type Taxonomy identifier
	 */
	private string $taxonomy_body_type = 'car_body_type';

	/**
	 * Taxonomy: engine type
	 *
	 * @since 1.0.0
	 * @var string $taxonomy_engine_type Taxonomy identifier
	 */
	private string $taxonomy_engine_type = 'car_engine_type';
	/**
	 * Passport fields whose visibility depends on the engine type.
	 *
	 * @var string[]
	 */
	private array $engine_dependent_fields = array( 'engine_capacity', 'battery_capacity', 'electric_range' );

	/**
	 * Drive types: value => key of the full name in get_drive_type_options().
	 *
	 * @var string[]
	 */
	private array $drive_types = array( 'fwd', 'rwd', 'awd' );

	/**
	 * Dimension and weight field names.
	 *
	 * @var string[]
	 */
	private array $dimension_fields = array(
		'length',
		'width',
		'width_with_mirrors',
		'height',
		'wheelbase',
		'weight_unladen',
		'gross_vehicle_weight',
		'max_payload',
		'cargo_volume',
		'cargo_volume_max',
		'cargo_volume_frunk',
		'roof_load',
		'tow_hitch_possible',
		'towing_weight_unbraked',
		'towing_weight_braked',
		'vertical_load_max',
	);

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		/**
		 * Post Type Name
		 *
		 * @since 1.0.0
		 */
		$this->register_class_custom_posttype_name( $this->post_type );
		/**
		 * Taxonomy names
		 */
		$this->register_class_custom_taxonomy_name( $this->taxonomy_brand );
		$this->register_class_custom_taxonomy_name( $this->taxonomy_body_type );
		$this->register_class_custom_taxonomy_name( $this->taxonomy_engine_type );
		/**
		 * WordPress Hooks
		 */
		add_action( 'add_meta_boxes_' . $this->post_type_name[ $this->post_type ], array( $this, 'add_meta_boxes' ) );
		/**
		 * Show car modal on the admin list
		 */
		$this->load_plugin_admin_assets = true;
		add_filter( 'iworks/my-car/wp_localize_script/admin', array( $this, 'filter_wp_localize_script_admin' ) );
		add_filter( 'manage_' . $this->post_type_name[ $this->post_type ] . '_posts_columns', array( $this, 'filter_add_show_column' ) );
		add_action( 'manage_' . $this->post_type_name[ $this->post_type ] . '_posts_custom_column', array( $this, 'action_render_show_column' ), 10, 2 );
		add_action( 'admin_footer-edit.php', array( $this, 'action_render_show_modal' ) );
		add_action( 'wp_ajax_my-car-get-car', array( $this, 'handle_ajax_get_car' ) );
		add_action( 'wp_ajax_my-car-get-car-tab', array( $this, 'handle_ajax_get_car_tab' ) );
		add_action( 'wp_ajax_my-car-add-mileage', array( $this, 'handle_ajax_add_mileage' ) );
		/**
		 * Settings page metaboxes and import handler
		 */
		add_filter( 'iworks/my-car/etc/config/options', array( $this, 'filter_add_import_options' ) );
		add_action( 'admin_post_my-car-import-predefined', array( $this, 'handle_import_predefined' ) );
		add_action( 'admin_notices', array( $this, 'action_admin_notices' ) );
		/**
		 * Engine type term edit screen: configurable passport fields
		 */
		add_action( $this->taxonomy_name[ $this->taxonomy_engine_type ] . '_add_form_fields', array( $this, 'render_engine_type_add_fields' ) );
		add_action( $this->taxonomy_name[ $this->taxonomy_engine_type ] . '_edit_form_fields', array( $this, 'render_engine_type_edit_fields' ) );
		add_action( 'created_' . $this->taxonomy_name[ $this->taxonomy_engine_type ], array( $this, 'save_engine_type_fields' ) );
		add_action( 'edited_' . $this->taxonomy_name[ $this->taxonomy_engine_type ], array( $this, 'save_engine_type_fields' ) );
		/**
		 * Frontend: car data table on single car pages
		 */
		add_filter( 'the_content', array( $this, 'filter_the_content' ), 20 );
		/**
		 * Frontend visibility metabox on the car edit screen
		 */
		add_action( 'add_meta_boxes_' . $this->post_type_name[ $this->post_type ], array( $this, 'add_meta_box_frontend_visibility' ) );
		add_action( 'save_post_' . $this->post_type_name[ $this->post_type ], array( $this, 'save_frontend_visibility' ) );
	}

	/**
	 * Register the "Frontend Visibility" metabox.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Car post.
	 * @return void
	 */
	public function add_meta_box_frontend_visibility( $post ) {
		add_meta_box(
			'my-car-frontend-visibility',
			__( 'Frontend Visibility', 'my-car' ),
			array( $this, 'render_meta_box_frontend_visibility' ),
			$post->post_type,
			'side',
			'default'
		);
	}

	/**
	 * Render the "Frontend Visibility" metabox.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Car post.
	 * @return void
	 */
	public function render_meta_box_frontend_visibility( $post ) {
		wp_nonce_field( 'my-car-frontend-visibility', $this->get_meta_name( 'frontend_visibility_nonce' ) );
		$saved   = get_post_meta( $post->ID, $this->get_meta_name( 'frontend_visibility' ), true );
		$vis     = is_array( $saved ) ? $saved : array();
		$rows    = array(
			'vin'             => __( 'VIN', 'my-car' ),
			'license_plate'   => __( 'License Plate Number', 'my-car' ),
			'mileage_log'     => __( 'Mileage Log', 'my-car' ),
			'service_records' => __( 'Service Records', 'my-car' ),
		);
		$options = array(
			'owner' => __( 'Show for owner', 'my-car' ),
			'all'   => __( 'Show for all', 'my-car' ),
			'hide'  => __( 'Hide', 'my-car' ),
		);
		foreach ( $rows as $key => $label ) {
			$value = isset( $vis[ $key ] ) ? $vis[ $key ] : 'owner';
			echo '<p><strong>' . esc_html( $label ) . '</strong><br />';
			foreach ( $options as $option => $option_label ) {
				printf(
					'<label style="display:block;"><input type="radio" name="%1$s[%2$s]" value="%3$s" %4$s /> %5$s</label>',
					esc_attr( $this->get_meta_name( 'frontend_visibility' ) ),
					esc_attr( $key ),
					esc_attr( $option ),
					checked( $value, $option, false ),
					esc_html( $option_label )
				);
			}
			echo '</p>';
		}
		echo '<p class="description">' . esc_html__( 'Controls the frontend data table only — the admin modal always shows everything.', 'my-car' ) . '</p>';
	}

	/**
	 * Save the "Frontend Visibility" metabox.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Car post ID.
	 * @return void
	 */
	public function save_frontend_visibility( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$nonce_name = $this->get_meta_name( 'frontend_visibility_nonce' );
		if ( ! isset( $_POST[ $nonce_name ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_name ] ) ), 'my-car-frontend-visibility' ) ) {
			return;
		}
		$name    = $this->get_meta_name( 'frontend_visibility' );
		$allowed = array( 'vin', 'license_plate', 'mileage_log', 'service_records' );
		$vis     = array();
		$raw     = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- values sanitized below via allow-list
		if ( is_array( $raw ) ) {
			foreach ( $raw as $key => $value ) {
				$key = sanitize_key( $key );
				if ( in_array( $key, $allowed, true ) && in_array( $value, array( 'owner', 'all', 'hide' ), true ) ) {
					$vis[ $key ] = $value;
				}
			}
		}
		update_post_meta( $post_id, $name, $vis );
	}

	/**
	 * Check whether a car data element is visible on the frontend.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $car_id Car post ID.
	 * @param string $key Element key: vin, license_plate or mileage_log.
	 * @return bool True when visible for the current user.
	 */
	private function is_visible_on_frontend( $car_id, $key ) {
		$saved = get_post_meta( $car_id, $this->get_meta_name( 'frontend_visibility' ), true );
		$vis   = is_array( $saved ) ? $saved : array();
		$mode  = isset( $vis[ $key ] ) ? $vis[ $key ] : 'owner';
		if ( 'all' === $mode ) {
			return true;
		}
		if ( 'hide' === $mode ) {
			return false;
		}
		/**
		 * Owner: users who can edit this car (the author and editors/admins).
		 */
		return current_user_can( 'edit_post', $car_id );
	}

	/**
	 * Class settings
	 *
	 * @since 1.0.0
	 */
	public function action_init_settings() {
		/**
		 * Settings
		 */
		$this->meta_boxes[ $this->post_type_name[ $this->post_type ] ] = array(
			'car-data'   => array(
				'title'  => __( 'Vehicle Digital Passport', 'my-car' ),
				'fields' => array(
					array(
						'name'       => 'vin',
						'type'       => 'text',
						'label'      => esc_html__( 'Vehicle Identification Number (VIN)', 'my-car' ),
						'maxlength'  => 17,
						'large-text' => false,
					),
					array(
						'name'  => 'production_year',
						'type'  => 'number',
						'label' => esc_html__( 'Production Year', 'my-car' ),
					),
					array(
						'name'  => 'engine_capacity',
						'type'  => 'number',
						'label' => esc_html__( 'Engine Capacity (cm³)', 'my-car' ),
					),
					array(
						'name'  => 'engine_power',
						'type'  => 'number',
						'label' => esc_html__( 'Engine Power (kW)', 'my-car' ),
					),
					array(
						'name'    => 'drive',
						'type'    => 'radio',
						'label'   => esc_html__( 'Drive', 'my-car' ),
						'options' => $this->get_drive_type_options(),
					),
					array(
						'name'  => 'battery_capacity',
						'type'  => 'number',
						'label' => esc_html__( 'Battery Capacity (kWh)', 'my-car' ),
					),
					array(
						'name'  => 'electric_range',
						'type'  => 'number',
						'label' => esc_html__( 'Electric Range (km)', 'my-car' ),
					),
					array(
						'name'       => 'license_plate',
						'type'       => 'text',
						'label'      => esc_html__( 'License Plate Number', 'my-car' ),
						'large-text' => false,
					),
				),
			),
			'dimensions' => array(
				'title'  => __( 'Dimensions and Weight', 'my-car' ),
				'folded' => true,
				'fields' => array(
					array(
						'name'  => 'length',
						'type'  => 'number',
						'label' => esc_html__( 'Length (mm)', 'my-car' ),
					),
					array(
						'name'  => 'width',
						'type'  => 'number',
						'label' => esc_html__( 'Width (mm)', 'my-car' ),
					),
					array(
						'name'  => 'width_with_mirrors',
						'type'  => 'number',
						'label' => esc_html__( 'Width with Mirrors (mm)', 'my-car' ),
					),
					array(
						'name'  => 'height',
						'type'  => 'number',
						'label' => esc_html__( 'Height (mm)', 'my-car' ),
					),
					array(
						'name'  => 'wheelbase',
						'type'  => 'number',
						'label' => esc_html__( 'Wheelbase (mm)', 'my-car' ),
					),
					array(
						'name'  => 'weight_unladen',
						'type'  => 'number',
						'label' => esc_html__( 'Weight Unladen (kg)', 'my-car' ),
					),
					array(
						'name'  => 'gross_vehicle_weight',
						'type'  => 'number',
						'label' => esc_html__( 'Gross Vehicle Weight (kg)', 'my-car' ),
					),
					array(
						'name'  => 'max_payload',
						'type'  => 'number',
						'label' => esc_html__( 'Max. Payload (kg)', 'my-car' ),
					),
					array(
						'name'  => 'cargo_volume',
						'type'  => 'number',
						'label' => esc_html__( 'Cargo Volume (l)', 'my-car' ),
					),
					array(
						'name'  => 'cargo_volume_max',
						'type'  => 'number',
						'label' => esc_html__( 'Cargo Volume Max (l)', 'my-car' ),
					),
					array(
						'name'  => 'cargo_volume_frunk',
						'type'  => 'number',
						'label' => esc_html__( 'Cargo Volume Frunk (l)', 'my-car' ),
					),
					array(
						'name'  => 'roof_load',
						'type'  => 'number',
						'label' => esc_html__( 'Roof Load (kg)', 'my-car' ),
					),
					array(
						'name'  => 'tow_hitch_possible',
						'type'  => 'checkbox',
						'label' => esc_html__( 'Tow Hitch Possible', 'my-car' ),
					),
					array(
						'name'  => 'towing_weight_unbraked',
						'type'  => 'number',
						'label' => esc_html__( 'Towing Weight Unbraked (kg)', 'my-car' ),
					),
					array(
						'name'  => 'towing_weight_braked',
						'type'  => 'number',
						'label' => esc_html__( 'Towing Weight Braked (kg)', 'my-car' ),
					),
					array(
						'name'  => 'vertical_load_max',
						'type'  => 'number',
						'label' => esc_html__( 'Vertical Load Max (kg)', 'my-car' ),
					),
				),
			),
		);
	}

	/**
	 * Register Car custom post type
	 *
	 * @since 1.0.0
	 */
	public function action_init_register_post_type() {
		$this->check_option_object();
		$labels = array(
			'name'               => _x( 'Cars', 'Post Type General Name', 'my-car' ),
			'singular_name'      => _x( 'Car', 'Post Type Singular Name', 'my-car' ),
			'menu_name'          => _x( 'Cars', 'Menu Name', 'my-car' ),
			'name_admin_bar'     => _x( 'Car', 'Admin Bar Name', 'my-car' ),
			'parent_item_colon'  => __( 'Parent Car:', 'my-car' ),
			'all_items'          => __( 'All Cars', 'my-car' ),
			'add_new_item'       => __( 'Add New Car', 'my-car' ),
			'add_new'            => __( 'Add New', 'my-car' ),
			'new_item'           => __( 'New Car', 'my-car' ),
			'edit_item'          => __( 'Edit Car', 'my-car' ),
			'update_item'        => __( 'Update Car', 'my-car' ),
			'view_item'          => __( 'View Car', 'my-car' ),
			'search_items'       => __( 'Search Cars', 'my-car' ),
			'not_found'          => __( 'Not found', 'my-car' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'my-car' ),
		);

		$args = array(
			'label'               => __( 'Car', 'my-car' ),
			'description'         => __( 'Cars managed by the My Car plugin', 'my-car' ),
			'labels'              => $labels,
			'supports'            => array(
				'title',
				'editor',
				'thumbnail',
				'page-attributes',
			),
			'hierarchical'        => false,
			'public'              => true,
			'exclude_from_search' => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => absint( $this->options->get_option( 'admin_menu_priority' ) ) ?: 60,
			'menu_icon'           => 'dashicons-car',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => $this->has_archive(),
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
			'rest_base'           => apply_filters(
				'iworks/my-car/register_post_type/' . $this->post_type . '/rest_base',
				defined( 'ICL_SITEPRESS_VERSION' ) ? 'car' : __( 'cars', 'my-car' )
			),
		);
		$this->register_post_type( $this->post_type, $args );
	}

	/**
	 * Should the cars archive be enabled?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function has_archive() {
		$this->check_option_object();
		return (bool) $this->options->get_option( 'car_enable_archive' );
	}

	/**
	 * Add the Import tab to the options page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $options Existing options fields.
	 * @return array
	 */
	public function filter_add_import_options( $options ) {
		$options[] = array(
			'type'  => 'heading',
			'label' => __( 'Import', 'my-car' ),
			'since' => '1.0.0',
		);
		$options[] = array(
			'name'              => 'import_brands_models',
			'type'              => 'checkbox',
			'th'                => __( 'Brands & Models', 'my-car' ),
			'description'       => __( 'Import predefined brands and their models.', 'my-car' ),
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'classes'           => array( 'switch-button' ),
			'since'             => '1.0.0',
		);
		$options[] = array(
			'name'              => 'import_body_types',
			'type'              => 'checkbox',
			'th'                => __( 'Body Types', 'my-car' ),
			'description'       => __( 'Import predefined body types.', 'my-car' ),
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'classes'           => array( 'switch-button' ),
			'since'             => '1.0.0',
		);
		$options[] = array(
			'name'              => 'import_engine_types',
			'type'              => 'checkbox',
			'th'                => __( 'Engine Types', 'my-car' ),
			'description'       => __( 'Import predefined engine types.', 'my-car' ),
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'classes'           => array( 'switch-button' ),
			'since'             => '1.0.0',
		);
		$options[] = array(
			'name'              => 'import_service_types',
			'type'              => 'checkbox',
			'th'                => __( 'Service Types', 'my-car' ),
			'description'       => __( 'Import predefined service types.', 'my-car' ),
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'classes'           => array( 'switch-button' ),
			'since'             => '1.0.0',
		);
		$url       = wp_nonce_url( admin_url( 'admin-post.php?action=my-car-import-predefined' ), 'my-car-import-predefined' );
		$options[] = array(
			'type'  => 'info',
			'value' => sprintf(
				'<p style="margin-top:10px;"><a href="%s" class="button button-secondary">%s</a></p>',
				esc_url( $url ),
				esc_html__( 'Import predefined data', 'my-car' )
			),
			'since' => '1.0.0',
		);
		return $options;
	}

	/**
	 * Get the path of a predefined data file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename JSON filename.
	 * @return string
	 */
	private function get_data_file_path( $filename ) {
		/**
		 * Locale-specific preset, e.g. etc/data/pl_PL/car-body-types.json
		 */
		$locale = strtolower( get_locale() );
		if ( '' !== $locale ) {
			$file = dirname( $this->base, 2 ) . '/etc/data/' . $locale . '/' . $filename;
			if ( is_file( $file ) ) {
				return $file;
			}
		}
		return dirname( $this->base, 2 ) . '/etc/data/' . $filename;
	}

	/**
	 * Add modal data to the admin script localization.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Localized data.
	 * @return array
	 */
	public function filter_wp_localize_script_admin( $data ) {
		$this->check_option_object();
		$data['show_car'] = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'my-car-show-car' ),
			'unit'     => $this->options->get_option( 'units_distance' ) ? $this->options->get_option( 'units_distance' ) : 'km',
			'l10n'     => array(
				'error'  => esc_html__( 'Unable to load car data.', 'my-car' ),
				'saved'  => esc_html__( 'Mileage saved.', 'my-car' ),
				'log'    => esc_html__( 'Mileage log:', 'my-car' ),
				'no_log' => esc_html__( 'No mileage entries yet.', 'my-car' ),
			),
		);
		return $data;
	}

	/**
	 * Add the "Show" column to the cars admin list.
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns List table columns.
	 * @return array
	 */
	public function filter_add_show_column( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['car_show'] = __( 'Show', 'my-car' );
			}
		}
		return $new;
	}

	/**
	 * Render the "Show" column content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function action_render_show_column( $column, $post_id ) {
		if ( 'car_show' !== $column ) {
			return;
		}
		printf(
			'<button type="button" class="button button-secondary my-car-show-car" data-car-id="%d">%s</button>',
			intval( $post_id ),
			esc_html__( 'Show', 'my-car' )
		);
	}

	/**
	 * Render the car modal markup on the cars list screen.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function action_render_show_modal() {
		global $typenow;
		if ( $this->post_type_name[ $this->post_type ] !== $typenow ) {
			return;
		}
		?>
		<dialog id="my-car-car-modal" class="my-car-car-modal">
			<div class="my-car-car-modal-header">
				<h2 id="my-car-car-modal-title"></h2>
				<button type="button" class="button-link my-car-car-modal-close" aria-label="<?php esc_attr_e( 'Close', 'my-car' ); ?>">&times;</button>
			</div>
			<div class="my-car-car-modal-tabs" role="tablist">
				<button type="button" class="my-car-car-modal-tab is-active" data-tab="data" role="tab" aria-selected="true"><?php esc_html_e( 'Car Data', 'my-car' ); ?></button>
				<button type="button" class="my-car-car-modal-tab" data-tab="mileage" role="tab" aria-selected="false"><?php esc_html_e( 'Mileage Log', 'my-car' ); ?></button>
				<button type="button" class="my-car-car-modal-tab" data-tab="service" role="tab" aria-selected="false"><?php esc_html_e( 'Service Records', 'my-car' ); ?></button>
			</div>
			<div class="my-car-car-modal-body" data-panel="data">
				<div class="my-car-car-modal-media"><img id="my-car-car-modal-image" alt="" hidden /></div>
				<table class="my-car-car-modal-fields widefat striped">
					<tbody id="my-car-car-modal-fields"></tbody>
				</table>
			</div>
			<div class="my-car-car-modal-body" data-panel="mileage" hidden>
				<table id="my-car-mileage-table" class="my-car-mileage-table widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'my-car' ); ?></th>
							<th><?php esc_html_e( 'Mileage', 'my-car' ); ?></th>
						</tr>
					</thead>
					<tbody id="my-car-mileage-log"></tbody>
				</table>
				<form id="my-car-mileage-form" hidden>
					<h3><?php esc_html_e( 'Add Mileage Entry', 'my-car' ); ?></h3>
					<div class="my-car-mileage-fields">
						<p>
							<label for="my-car-mileage-date"><?php esc_html_e( 'Date', 'my-car' ); ?></label>
							<input type="date" id="my-car-mileage-date" name="date" required />
						</p>
						<p>
							<label for="my-car-mileage-value"><?php esc_html_e( 'Mileage', 'my-car' ); ?></label>
							<input type="number" id="my-car-mileage-value" name="mileage" min="0" step="1" placeholder="0" required />
						</p>
					</div>
					<p class="my-car-mileage-actions">
						<button type="button" class="button button-secondary my-car-mileage-cancel"><?php esc_html_e( 'Cancel', 'my-car' ); ?></button>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Mileage', 'my-car' ); ?></button>
					</p>
				</form>
			</div>
			<div class="my-car-car-modal-body" data-panel="service" hidden>
				<table id="my-car-service-table" class="my-car-service-table widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'my-car' ); ?></th>
							<th><?php esc_html_e( 'Mileage', 'my-car' ); ?></th>
							<th><?php esc_html_e( 'Service Types', 'my-car' ); ?></th>
						</tr>
					</thead>
					<tbody id="my-car-service-log"></tbody>
				</table>
				<p id="my-car-service-empty" hidden><?php esc_html_e( 'No service records yet.', 'my-car' ); ?></p>
			</div>
			<div class="my-car-car-modal-footer">
				<button type="button" class="button button-secondary my-car-mileage-toggle"><?php esc_html_e( 'Add Car Mileage', 'my-car' ); ?></button>
			</div>
		</dialog>
		<?php
	}

	/**
	 * Get car data for the modal (AJAX).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_ajax_get_car() {
		check_ajax_referer( 'my-car-show-car', 'nonce' );
		if ( ! current_user_can( $this->get_this_capability() ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'my-car' ) ), 403 );
		}
		$car_id = isset( $_POST['car_id'] ) ? absint( $_POST['car_id'] ) : 0;
		$car    = get_post( $car_id );
		if ( ! $car || $this->post_type_name[ $this->post_type ] !== $car->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Car not found.', 'my-car' ) ), 404 );
		}
		/**
		 * Passport fields
		 *
		 * Fields listed in $this->engine_dependent_fields are shown only when
		 * the car's engine type terms enable them (configured on the engine
		 * type term edit screen; legacy terms without a config fall back to
		 * sensible defaults).
		 */
		$passport = $this->get_passport_rows( $car_id );
		/**
		 * Taxonomies
		 */
		$taxonomy_data = array(
			'brands'       => array(
				'label' => __( 'Brand & Model', 'my-car' ),
				'terms' => $this->get_car_terms_hierarchy( $car_id, $this->taxonomy_name[ $this->taxonomy_brand ] ),
			),
			'body_types'   => array(
				'label' => __( 'Body Types', 'my-car' ),
				'terms' => $this->get_car_terms_hierarchy( $car_id, $this->taxonomy_name[ $this->taxonomy_body_type ] ),
			),
			'engine_types' => array(
				'label' => __( 'Engine Types', 'my-car' ),
				'terms' => $this->get_car_terms_hierarchy( $car_id, $this->taxonomy_name[ $this->taxonomy_engine_type ] ),
			),
		);
		$image         = get_the_post_thumbnail_url( $car_id, 'medium' );
		wp_send_json_success(
			array(
				'title'      => esc_html( get_the_title( $car ) ),
				'permalink'  => get_permalink( $car ),
				'image'      => $image ? esc_url( $image ) : '',
				'passport'   => $passport,
				'taxonomies' => $taxonomy_data,
			)
		);
	}

	/**
	 * Get tab data for the car modal (AJAX, lazy-loaded per tab).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_ajax_get_car_tab() {
		check_ajax_referer( 'my-car-show-car', 'nonce' );
		if ( ! current_user_can( $this->get_this_capability() ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'my-car' ) ), 403 );
		}
		$car_id = isset( $_POST['car_id'] ) ? absint( $_POST['car_id'] ) : 0;
		$car    = get_post( $car_id );
		if ( ! $car || $this->post_type_name[ $this->post_type ] !== $car->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Car not found.', 'my-car' ) ), 404 );
		}
		$tab = isset( $_POST['tab'] ) ? sanitize_key( $_POST['tab'] ) : '';
		if ( 'mileage' === $tab ) {
			wp_send_json_success( array( 'mileage_log' => $this->get_mileage_log( $car_id ) ) );
		}
		if ( 'service' === $tab ) {
			wp_send_json_success( array( 'service_records' => $this->get_car_service_records( $car_id ) ) );
		}
		wp_send_json_error( array( 'message' => __( 'Unknown tab.', 'my-car' ) ), 400 );
	}

	/**
	 * Get the service records of a car for the modal.
	 *
	 * @since 1.0.0
	 *
	 * @param int $car_id Car post ID.
	 * @return array[] Rows: date, mileage, types, workshop, status.
	 */
	private function get_car_service_records( $car_id ) {
		$filename = dirname( __DIR__ ) . '/class-iworks-my-car-db.php';
		if ( ! class_exists( 'iworks_my_car_db' ) && is_file( $filename ) ) {
			require_once $filename;
		}
		if ( ! class_exists( 'iworks_my_car_db' ) ) {
			return array();
		}
		$db      = new iworks_my_car_db();
		$rows    = $db->get_car_records( $car_id );
		$records = array();
		foreach ( $rows as $row ) {
			$types     = get_the_terms( $row->record_id, 'iworks_car_service_type' );
			$records[] = array(
				'date'    => esc_html( $row->event_date ),
				'mileage' => absint( $row->event_mileage ),
				'types'   => is_array( $types ) ? array_map( 'strval', wp_list_pluck( $types, 'name' ) ) : array(),
			);
		}
		return $records;
	}

	/**
	 * Get the passport rows of a car (engine-type aware).
	 *
	 * Fields listed in $this->engine_dependent_fields are shown only when
	 * the car's engine type terms enable them (configured on the engine
	 * type term edit screen; legacy terms without a config fall back to
	 * sensible defaults).
	 *
	 * @since 1.0.0
	 *
	/**
	 * Get drive type options, translated.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Value => full translated name.
	 */
	private function get_drive_type_options(): array {
		return array(
			'fwd' => __( 'Front-Wheel Drive (FWD)', 'my-car' ),
			'rwd' => __( 'Rear-Wheel Drive (RWD)', 'my-car' ),
			'awd' => __( 'All-Wheel Drive (AWD)', 'my-car' ),
		);
	}

	/**
	 * Get passport rows for the car data table and modal.
	 *
	 * @since 1.0.0
	 *
	 * @param int $car_id Car post ID.
	 * @return array[] Rows: label, value.
	 */
	private function get_passport_rows( $car_id ) {
		$fields = array(
			'vin'                    => __( 'VIN', 'my-car' ),
			'production_year'        => __( 'Production Year', 'my-car' ),
			'engine_capacity'        => __( 'Engine Capacity (cm³)', 'my-car' ),
			'engine_power'           => __( 'Engine Power (kW)', 'my-car' ),
			'drive'                  => __( 'Drive', 'my-car' ),
			'battery_capacity'       => __( 'Battery Capacity (kWh)', 'my-car' ),
			'electric_range'         => __( 'Electric Range (km)', 'my-car' ),
			'license_plate'          => __( 'License Plate Number', 'my-car' ),
			'length'                 => __( 'Length (mm)', 'my-car' ),
			'width'                  => __( 'Width (mm)', 'my-car' ),
			'width_with_mirrors'     => __( 'Width with Mirrors (mm)', 'my-car' ),
			'height'                 => __( 'Height (mm)', 'my-car' ),
			'wheelbase'              => __( 'Wheelbase (mm)', 'my-car' ),
			'weight_unladen'         => __( 'Weight Unladen (kg)', 'my-car' ),
			'gross_vehicle_weight'   => __( 'Gross Vehicle Weight (kg)', 'my-car' ),
			'max_payload'            => __( 'Max. Payload (kg)', 'my-car' ),
			'cargo_volume'           => __( 'Cargo Volume (l)', 'my-car' ),
			'cargo_volume_max'       => __( 'Cargo Volume Max (l)', 'my-car' ),
			'cargo_volume_frunk'     => __( 'Cargo Volume Frunk (l)', 'my-car' ),
			'roof_load'              => __( 'Roof Load (kg)', 'my-car' ),
			'tow_hitch_possible'     => __( 'Tow Hitch Possible', 'my-car' ),
			'towing_weight_unbraked' => __( 'Towing Weight Unbraked (kg)', 'my-car' ),
			'towing_weight_braked'   => __( 'Towing Weight Braked (kg)', 'my-car' ),
			'vertical_load_max'      => __( 'Vertical Load Max (kg)', 'my-car' ),
		);
		/**
		 * Filter the passport fields.
		 *
		 * @param array $fields Fields: name => label.
		 */
		$fields        = apply_filters( 'iworks/my-car/passport-fields', $fields );
		$engine_fields = $this->get_engine_type_fields_for_car( $car_id );
		$rows          = array();
		foreach ( $fields as $name => $label ) {
			if ( in_array( $name, $this->engine_dependent_fields, true ) && ! in_array( $name, $engine_fields, true ) ) {
				continue;
			}
			$group = in_array( $name, $this->dimension_fields, true ) ? 'dimensions' : 'car-data';
			$value = get_post_meta( $car_id, $this->get_post_meta_name( $name, $group ), true );
			if ( 'drive' === $name && in_array( $value, $this->drive_types, true ) ) {
				$value = $this->get_drive_type_options()[ $value ];
			}
			if ( 'tow_hitch_possible' === $name && '' !== $value ) {
				$value = 'yes' === $value ? __( 'Yes', 'my-car' ) : __( 'No', 'my-car' );
			}
			if ( '' === $value || null === $value ) {
				$value = '—';
			}
			$rows[] = array(
				'name'  => $name,
				'label' => $label,
				'value' => esc_html( $value ),
			);
		}
		return $rows;
	}

	/**
	 * Append a data table to the car single page content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Post content.
	 * @return string Content with the car data table appended.
	 */
	public function filter_the_content( $content ) {
		if ( ! is_singular( $this->post_type_name[ $this->post_type ] ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$car_id = get_the_ID();
		if ( ! $car_id ) {
			return $content;
		}
		$unit = $this->options->get_option( 'units_distance' ) ? $this->options->get_option( 'units_distance' ) : 'km';
		/**
		 * Passport rows
		 */
		$rows = $this->get_passport_rows( $car_id );
		/**
		 * Taxonomies
		 */
		$taxonomies  = array(
			array(
				'label' => __( 'Brand & Model', 'my-car' ),
				'terms' => $this->get_car_terms_hierarchy( $car_id, $this->taxonomy_name[ $this->taxonomy_brand ] ),
			),
			array(
				'label' => __( 'Body Types', 'my-car' ),
				'terms' => $this->get_car_terms_hierarchy( $car_id, $this->taxonomy_name[ $this->taxonomy_body_type ] ),
			),
			array(
				'label' => __( 'Engine Types', 'my-car' ),
				'terms' => $this->get_car_terms_hierarchy( $car_id, $this->taxonomy_name[ $this->taxonomy_engine_type ] ),
			),
		);
		$mileage_log = $this->get_mileage_log( $car_id );
		/**
		 * Table
		 */
		$table  = '<div class="my-car-frontend-table">';
		$table .= '<h2>' . esc_html__( 'Vehicle Digital Passport', 'my-car' ) . '</h2>';
		$table .= '<table class="my-car-car-data widefat striped"><tbody>';
		foreach ( $rows as $row ) {
			if ( in_array( $row['name'], array( 'vin', 'license_plate' ), true ) && ! $this->is_visible_on_frontend( $car_id, $row['name'] ) ) {
				continue;
			}
			$table .= '<tr><th scope="row">' . esc_html( $row['label'] ) . '</th><td>' . esc_html( $row['value'] ) . '</td></tr>';
		}
		foreach ( $taxonomies as $taxonomy ) {
			if ( empty( $taxonomy['terms'] ) ) {
				continue;
			}
			$links = array();
			foreach ( $taxonomy['terms'] as $term ) {
				$links[] = '<a href="' . esc_url( $term['link'] ) . '">' . esc_html( $term['label'] ) . '</a>';
			}
			$table .= '<tr><th scope="row">' . esc_html( $taxonomy['label'] ) . '</th><td>' . implode( ', ', $links ) . '</td></tr>';
		}
		$table .= '</tbody></table>';
		if ( ! empty( $mileage_log ) && $this->is_visible_on_frontend( $car_id, 'mileage_log' ) ) {
			$table .= '<h2>' . esc_html__( 'Mileage Log', 'my-car' ) . '</h2>';
			$table .= '<table class="my-car-mileage widefat striped"><thead><tr>';
			$table .= '<th>' . esc_html__( 'Date', 'my-car' ) . '</th>';
			$table .= '<th>' . esc_html__( 'Mileage', 'my-car' ) . '</th>';
			$table .= '</tr></thead><tbody>';
			foreach ( $mileage_log as $entry ) {
				$table .= '<tr><td>' . esc_html( $entry['date'] ) . '</td><td>' . esc_html( $entry['mileage'] . ' ' . $unit ) . '</td></tr>';
			}
			$table .= '</tbody></table>';
		}
		$service_records = $this->get_car_service_records( $car_id );
		if ( ! empty( $service_records ) && $this->is_visible_on_frontend( $car_id, 'service_records' ) ) {
			$table .= '<h2>' . esc_html__( 'Service Records', 'my-car' ) . '</h2>';
			$table .= '<table class="my-car-service widefat striped"><thead><tr>';
			$table .= '<th>' . esc_html__( 'Date', 'my-car' ) . '</th>';
			$table .= '<th>' . esc_html__( 'Mileage', 'my-car' ) . '</th>';
			$table .= '<th>' . esc_html__( 'Service Types', 'my-car' ) . '</th>';
			$table .= '</tr></thead><tbody>';
			foreach ( $service_records as $record ) {
				$table .= '<tr><td>' . esc_html( $record['date'] ) . '</td><td>' . esc_html( $record['mileage'] ? $record['mileage'] . ' ' . $unit : '—' ) . '</td><td>' . esc_html( implode( ', ', $record['types'] ) ) . '</td></tr>';
			}
			$table .= '</tbody></table>';
		}
		$table .= '</div>';
		/**
		 * Filter the frontend car data table.
		 *
		 * @param string $table Generated HTML.
		 * @param int    $car_id Car post ID.
		 */
		return $content . apply_filters( 'iworks/my-car/frontend-table', $table, $car_id );
	}

	/**
	 * Get the passport fields enabled by a car's engine types.
	 *
	 * @since 1.0.0
	 *
	 * @param int $car_id Car post ID.
	 * @return string[] Enabled field names.
	 */
	private function get_engine_type_fields_for_car( $car_id ) {
		$terms  = get_the_terms( $car_id, $this->taxonomy_name[ $this->taxonomy_engine_type ] );
		$fields = array();
		if ( ! is_array( $terms ) ) {
			return $fields;
		}
		$meta_name = $this->get_meta_name( 'engine_type_fields' );
		foreach ( $terms as $term ) {
			$saved       = get_term_meta( $term->term_id, $meta_name, true );
			$term_fields = is_array( $saved ) ? $saved : $this->get_engine_type_fields_defaults( $term->slug );
			$fields      = array_unique( array_merge( $fields, $term_fields ) );
		}
		return $fields;
	}

	/**
	 * Default passport fields for an engine type slug without a saved config.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Engine type slug.
	 * @return string[] Enabled field names.
	 */
	private function get_engine_type_fields_defaults( $slug ) {
		$defaults = array(
			'petrol'         => array( 'engine_capacity' ),
			'diesel'         => array( 'engine_capacity' ),
			'lpg'            => array( 'engine_capacity' ),
			'cng'            => array( 'engine_capacity' ),
			'hybrid'         => array( 'engine_capacity', 'battery_capacity', 'electric_range' ),
			'plug-in-hybrid' => array( 'engine_capacity', 'battery_capacity', 'electric_range' ),
			'electric'       => array( 'battery_capacity', 'electric_range' ),
			'hydrogen'       => array( 'battery_capacity', 'electric_range' ),
		);
		/**
		 * Filter the default passport fields per engine type slug.
		 *
		 * @param array $defaults Defaults: slug => field names.
		 */
		$defaults = apply_filters( 'iworks/my-car/engine-type-default-fields', $defaults );
		return isset( $defaults[ $slug ] ) ? $defaults[ $slug ] : $this->engine_dependent_fields;
	}

	/**
	 * Render passport field checkboxes on the engine type add screen.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_engine_type_add_fields() {
		$this->render_engine_type_checkboxes( array() );
		wp_nonce_field( 'my-car-engine-type-fields', $this->get_meta_name( 'engine_type_fields_nonce' ) );
	}

	/**
	 * Render passport field checkboxes on the engine type edit screen.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term $term Engine type term.
	 * @return void
	 */
	public function render_engine_type_edit_fields( $term ) {
		$saved = get_term_meta( $term->term_id, $this->get_meta_name( 'engine_type_fields' ), true );
		$value = is_array( $saved ) ? $saved : $this->get_engine_type_fields_defaults( $term->slug );
		?>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Passport Fields', 'my-car' ); ?></th>
			<td><?php $this->render_engine_type_checkboxes( $value ); ?></td>
		</tr>
		<?php
		wp_nonce_field( 'my-car-engine-type-fields', $this->get_meta_name( 'engine_type_fields_nonce' ) );
	}

	/**
	 * Render the passport field checkboxes.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $enabled Enabled field names.
	 * @return void
	 */
	private function render_engine_type_checkboxes( $enabled ) {
		$fields = array(
			'engine_capacity'  => __( 'Engine Capacity (cm³)', 'my-car' ),
			'battery_capacity' => __( 'Battery Capacity (kWh)', 'my-car' ),
			'electric_range'   => __( 'Electric Range (km)', 'my-car' ),
		);
		?>
		<fieldset>
			<p class="description"><?php esc_html_e( 'Select which passport fields apply to this engine type. They will be shown in the car modal only for cars with this engine type.', 'my-car' ); ?></p>
			<?php foreach ( $fields as $name => $label ) : ?>
				<label style="display:block;">
					<input type="checkbox" name="<?php echo esc_attr( $this->get_meta_name( 'engine_type_fields' ) ); ?>[]" value="<?php echo esc_attr( $name ); ?>" <?php checked( in_array( $name, $enabled, true ) ); ?> />
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<?php
	}

	/**
	 * Save passport fields config for an engine type term.
	 *
	 * @since 1.0.0
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function save_engine_type_fields( $term_id ) {
		$nonce_name = $this->get_meta_name( 'engine_type_fields_nonce' );
		if ( ! isset( $_POST[ $nonce_name ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_name ] ) ), 'my-car-engine-type-fields' ) ) {
			return;
		}
		$name   = $this->get_meta_name( 'engine_type_fields' );
		$fields = isset( $_POST[ $name ] ) && is_array( $_POST[ $name ] ) ? array_map( 'sanitize_key', wp_unslash( $_POST[ $name ] ) ) : array();
		$fields = array_intersect( $fields, $this->engine_dependent_fields );
		update_term_meta( $term_id, $name, $fields );
	}

	/**
	 * Get the mileage log of a car.
	 *
	 * @since 1.0.0
	 *
	 * @param int $car_id Car post ID.
	 * @return array[] Entries: date, mileage.
	 */
	private function get_mileage_log( $car_id ) {
		$log = get_post_meta( $car_id, $this->get_meta_name( 'mileage_log' ), true );
		if ( ! is_array( $log ) ) {
			return array();
		}
		usort(
			$log,
			static function ( $a, $b ) {
				return strcmp( $b['date'], $a['date'] );
			}
		);
		return array_slice( $log, 0, 10 );
	}

	/**
	 * Save a mileage entry (AJAX).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_ajax_add_mileage() {
		check_ajax_referer( 'my-car-show-car', 'nonce' );
		if ( ! current_user_can( $this->get_this_capability() ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'my-car' ) ), 403 );
		}
		$car_id = isset( $_POST['car_id'] ) ? absint( $_POST['car_id'] ) : 0;
		$car    = get_post( $car_id );
		if ( ! $car || $this->post_type_name[ $this->post_type ] !== $car->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Car not found.', 'my-car' ) ), 404 );
		}
		$date    = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
		$mileage = isset( $_POST['mileage'] ) ? absint( $_POST['mileage'] ) : 0;
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || ! $mileage ) {
			wp_send_json_error( array( 'message' => __( 'Invalid date or mileage.', 'my-car' ) ), 400 );
		}
		$log   = get_post_meta( $car_id, $this->get_meta_name( 'mileage_log' ), true );
		$log   = is_array( $log ) ? $log : array();
		$log[] = array(
			'date'    => $date,
			'mileage' => $mileage,
		);
		update_post_meta( $car_id, $this->get_meta_name( 'mileage_log' ), $log );
		/**
		 * Keep the latest mileage as the passport value.
		 */
		update_post_meta( $car_id, $this->get_post_meta_name( 'mileage', 'car-data' ), $mileage );
		wp_send_json_success(
			array(
				'mileage_log' => $this->get_mileage_log( $car_id ),
			)
		);
	}

	/**
	 * Get car terms with their parent (for hierarchical taxonomies).
	 *
	 * @since 1.0.0
	 *
	 * @param int    $car_id   Car post ID.
	 * @param string $taxonomy Taxonomy name.
	 * @return array[] Array of arrays: label (parent › child) and link.
	 */
	private function get_car_terms_hierarchy( $car_id, $taxonomy ) {
		$terms = wp_get_post_terms( $car_id, $taxonomy );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}
		$result = array();
		foreach ( $terms as $term ) {
			$labels = array( $term->name );
			if ( $term->parent ) {
				$parent = get_term( $term->parent, $taxonomy );
				if ( $parent && ! is_wp_error( $parent ) ) {
					array_unshift( $labels, $parent->name );
				}
			}
			$result[] = array(
				'label' => esc_html( implode( ' › ', $labels ) ),
				'link'  => esc_url( get_term_link( $term ) ),
			);
		}
		return $result;
	}

	/**
	 * Insert a term if it does not exist.
	 *
	 * @since 1.0.0
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param string $name     Term name.
	 * @param string $slug     Term slug.
	 * @param int    $parent_id Optional parent term ID.
	 * @return bool True if a new term was created.
	 */
	private function insert_term( $taxonomy, $name, $slug, $parent_id = 0 ) {
		$exists = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'slug'       => $slug,
				'hide_empty' => false,
				'parent'     => $parent_id,
				'fields'     => 'ids',
				'number'     => 1,
			)
		);
		if ( ! is_wp_error( $exists ) && ! empty( $exists ) ) {
			return false;
		}
		$args = array( 'slug' => $slug );
		if ( $parent_id ) {
			$args['parent'] = $parent_id;
		}
		$result = wp_insert_term( $name, $taxonomy, $args );
		return ! is_wp_error( $result );
	}

	/**
	 * Handle the predefined data import.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_import_predefined() {
		$this->check_option_object();
		if ( ! current_user_can( $this->get_this_capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'my-car' ) );
		}
		check_admin_referer( 'my-car-import-predefined' );
		$counts = array(
			'brands'        => 0,
			'models'        => 0,
			'body_types'    => 0,
			'engine_types'  => 0,
			'service_types' => 0,
		);
		/**
		 * Brands & models (models are children of their brand)
		 */
		$data = wp_json_file_decode( $this->get_data_file_path( 'car-brands.json' ), array( 'associative' => true ) );
		if ( $this->options->get_option( 'import_brands_models' ) && is_array( $data ) && ! empty( $data['brands'] ) && is_array( $data['brands'] ) ) {
			foreach ( $data['brands'] as $brand ) {
				if ( empty( $brand['name'] ) || empty( $brand['slug'] ) ) {
					continue;
				}
				$brand_term = get_term_by( 'slug', $brand['slug'], $this->taxonomy_name[ $this->taxonomy_brand ] );
				if ( ! $brand_term ) {
					$result = wp_insert_term( $brand['name'], $this->taxonomy_name[ $this->taxonomy_brand ], array( 'slug' => $brand['slug'] ) );
					if ( is_wp_error( $result ) ) {
						continue;
					}
					++$counts['brands'];
					$brand_term = get_term( $result['term_id'] );
				}
				if ( empty( $brand['models'] ) || ! is_array( $brand['models'] ) || ! $brand_term ) {
					continue;
				}
				foreach ( $brand['models'] as $model ) {
					if ( empty( $model['name'] ) || empty( $model['slug'] ) ) {
						continue;
					}
					if ( $this->insert_term( $this->taxonomy_name[ $this->taxonomy_brand ], $model['name'], $model['slug'], $brand_term->term_id ) ) {
						++$counts['models'];
					}
				}
			}
		}
		/**
		 * Body types
		 */
		$data = wp_json_file_decode( $this->get_data_file_path( 'car-body-types.json' ), array( 'associative' => true ) );
		if ( $this->options->get_option( 'import_body_types' ) && is_array( $data ) && ! empty( $data['body_types'] ) && is_array( $data['body_types'] ) ) {
			foreach ( $data['body_types'] as $body_type ) {
				if ( empty( $body_type['name'] ) || empty( $body_type['slug'] ) ) {
					continue;
				}
				if ( $this->insert_term( $this->taxonomy_name[ $this->taxonomy_body_type ], $body_type['name'], $body_type['slug'] ) ) {
					++$counts['body_types'];
				}
			}
		}
		/**
		 * Engine types
		 */
		$data = wp_json_file_decode( $this->get_data_file_path( 'car-engine-types.json' ), array( 'associative' => true ) );
		if ( $this->options->get_option( 'import_engine_types' ) && is_array( $data ) && ! empty( $data['engine_types'] ) && is_array( $data['engine_types'] ) ) {
			foreach ( $data['engine_types'] as $engine_type ) {
				if ( empty( $engine_type['name'] ) || empty( $engine_type['slug'] ) ) {
					continue;
				}
				if ( $this->insert_term( $this->taxonomy_name[ $this->taxonomy_engine_type ], $engine_type['name'], $engine_type['slug'] ) ) {
					++$counts['engine_types'];
				}
			}
		}
		/**
		 * Service types
		 */
		$data = wp_json_file_decode( $this->get_data_file_path( 'car-service-types.json' ), array( 'associative' => true ) );
		if ( $this->options->get_option( 'import_service_types' ) && is_array( $data ) && ! empty( $data['service_types'] ) && is_array( $data['service_types'] ) ) {
			foreach ( $data['service_types'] as $service_type ) {
				if ( empty( $service_type['name'] ) || empty( $service_type['slug'] ) ) {
					continue;
				}
				if ( $this->insert_term( $this->taxonomy_name['service_type'], $service_type['name'], $service_type['slug'] ) ) {
					++$counts['service_types'];
				}
			}
		}
		/**
		 * Redirect back with a notice
		 */
		set_transient( 'my-car-import-predefined-notice', $counts, 60 );
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=iworks_my-car_index' ) );
		exit;
	}

	/**
	 * Show the import result notice.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function action_admin_notices() {
		$counts = get_transient( 'my-car-import-predefined-notice' );
		if ( ! is_array( $counts ) ) {
			return;
		}
		delete_transient( 'my-car-import-predefined-notice' );
		$message = sprintf(
			/* translators: 1: number of brands, 2: number of models, 3: number of body types, 4: number of engine types, 5: number of service types. */
			__( 'Import finished. Added %1$d brands, %2$d models, %3$d body types, %4$d engine types and %5$d service types.', 'my-car' ),
			$counts['brands'],
			$counts['models'],
			$counts['body_types'],
			$counts['engine_types'],
			$counts['service_types']
		);
		printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $message ) );
	}

	/**
	 * Register Car taxonomies
	 *
	 * @since 1.0.0
	 */
	public function action_init_register_taxonomy() {
		$this->check_option_object();
		$taxonomies       = array(
			array(
				'name'      => $this->taxonomy_brand,
				'label'     => __( 'Brand & Model', 'my-car' ),
				'general'   => _x( 'Brands & Models', 'Taxonomy General Name', 'my-car' ),
				'singular'  => _x( 'Brand & Model', 'Taxonomy Singular Name', 'my-car' ),
				'rest_base' => 'car_brands',
			),
			array(
				'name'      => $this->taxonomy_body_type,
				'label'     => __( 'Body Type', 'my-car' ),
				'general'   => _x( 'Body Types', 'Taxonomy General Name', 'my-car' ),
				'singular'  => _x( 'Body Type', 'Taxonomy Singular Name', 'my-car' ),
				'rest_base' => 'car_body_types',
			),
			array(
				'name'      => $this->taxonomy_engine_type,
				'label'     => __( 'Engine Type', 'my-car' ),
				'general'   => _x( 'Engine Types', 'Taxonomy General Name', 'my-car' ),
				'singular'  => _x( 'Engine Type', 'Taxonomy Singular Name', 'my-car' ),
				'rest_base' => 'car_engine_types',
			),
		);
		$hide_option_name = array(
			$this->taxonomy_brand       => 'hide_taxonomy_brand',
			$this->taxonomy_body_type   => 'hide_taxonomy_body_type',
			$this->taxonomy_engine_type => 'hide_taxonomy_engine_type',
		);
		foreach ( $taxonomies as $one ) {
			$labels = array(
				'name'                       => $one['general'],
				'singular_name'              => $one['singular'],
				'menu_name'                  => $one['label'],
				/* translators: %s: taxonomy label. */
				'all_items'                  => sprintf( __( 'All %s', 'my-car' ), $one['label'] ),
				/* translators: %s: taxonomy singular label. */
				'parent_item'                => sprintf( __( 'Parent %s', 'my-car' ), $one['singular'] ),
				/* translators: %s: taxonomy singular label. */
				'parent_item_colon'          => sprintf( __( 'Parent %s:', 'my-car' ), $one['singular'] ),
				/* translators: %s: taxonomy singular label. */
				'new_item_name'              => sprintf( __( 'New %s Name', 'my-car' ), $one['singular'] ),
				/* translators: %s: taxonomy singular label. */
				'add_new_item'               => sprintf( __( 'Add New %s', 'my-car' ), $one['singular'] ),
				/* translators: %s: taxonomy singular label. */
				'edit_item'                  => sprintf( __( 'Edit %s', 'my-car' ), $one['singular'] ),
				/* translators: %s: taxonomy singular label. */
				'update_item'                => sprintf( __( 'Update %s', 'my-car' ), $one['singular'] ),
				/* translators: %s: taxonomy singular label. */
				'view_item'                  => sprintf( __( 'View %s', 'my-car' ), $one['singular'] ),
				/* translators: %s: taxonomy label. */
				'separate_items_with_commas' => sprintf( __( 'Separate %s with commas', 'my-car' ), $one['label'] ),
				/* translators: %s: taxonomy label. */
				'add_or_remove_items'        => sprintf( __( 'Add or remove %s', 'my-car' ), $one['label'] ),
				'choose_from_most_used'      => __( 'Choose from the most used', 'my-car' ),
				/* translators: %s: taxonomy label. */
				'popular_items'              => sprintf( __( 'Popular %s', 'my-car' ), $one['label'] ),
				/* translators: %s: taxonomy label. */
				'search_items'               => sprintf( __( 'Search %s', 'my-car' ), $one['label'] ),
				'not_found'                  => __( 'Not Found', 'my-car' ),
			);
			$args   = array(
				'labels'              => $labels,
				'hierarchical'        => true,
				'public'              => true,
				'exclude_from_search' => false,
				'rewrite'             => false,
				'show_ui'             => true,
				'show_in_menu'        => empty( $hide_option_name[ $one['name'] ] ) || ! $this->options->get_option( $hide_option_name[ $one['name'] ] ),
				'show_admin_column'   => true,
				'show_in_nav_menus'   => true,
				'show_tagcloud'       => false,
				'show_in_rest'        => true,
				'rest_base'           => $one['rest_base'],
			);
			register_taxonomy( $this->taxonomy_name[ $one['name'] ], $this->post_type_name[ $this->post_type ], $args );
		}
	}
}
