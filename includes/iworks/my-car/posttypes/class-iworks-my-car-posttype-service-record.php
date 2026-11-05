<?php
/**
 * Class for custom Post Type: Service Record
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
Foundation, Inc., 51 Franklin St, Boston, MA  02110-1301  USA
 */

defined( 'ABSPATH' ) || exit;

require_once 'class-iworks-my-car-posttype.php';

/**
 * Class for custom Post Type: Service Record
 */
class iworks_my_car_posttype_service_record extends iworks_my_car_posttype {

	/**
	 * Post type name
	 *
	 * @since 1.0.0
	 * @var string $post_type Post type identifier
	 */
	protected string $post_type = 'service_record';

	/**
	 * Database class instance
	 *
	 * @since 1.0.0
	 * @var iworks_my_car_db $db Database class
	 */
	private iworks_my_car_db $db;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		/**
		 * The DB class is loaded by the main plugin class after the post
		 * types loader, so require it here when missing.
		 */
		$filename = dirname( __DIR__ ) . '/class-iworks-my-car-db.php';
		if ( ! class_exists( 'iworks_my_car_db' ) && is_file( $filename ) ) {
			require_once $filename;
		}
		$this->db = new iworks_my_car_db();
		/**
		 * Join table maintenance
		 */
		add_action( 'save_post_' . $this->post_type_name[ $this->post_type ], array( $this, 'action_sync_join_table' ), 20, 3 );
		add_action( 'before_delete_post', array( $this, 'action_delete_sync' ) );
		add_action( 'trash_post', array( $this, 'action_delete_sync' ) );
		add_action( 'before_delete_post', array( $this, 'action_delete_car_sync' ) );
		add_action( 'pre_get_posts', array( $this, 'action_pre_get_posts' ) );
	}

	/**
	 * Handle admin list sorting by event date and mileage.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query Query.
	 * @return void
	 */
	public function action_pre_get_posts( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( $this->post_type_name[ $this->post_type ] !== $query->get( 'post_type' ) ) {
			return;
		}
		$orderby = $query->get( 'orderby' );
		if ( 'event_date' === $orderby ) {
			$query->set( 'meta_key', $this->get_post_meta_name( 'event_date', 'service-record' ) );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( $this->get_post_meta_name( 'event_mileage', 'service-record' ) === $orderby ) {
			$query->set( 'meta_key', $orderby );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * Register post type
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function action_init_register_post_type() {
		$labels = array(
			'name'               => _x( 'Service Records', 'Post Type General Name', 'my-car' ),
			'singular_name'      => _x( 'Service Record', 'Post Type Singular Name', 'my-car' ),
			'menu_name'          => _x( 'Service Records', 'Menu Name', 'my-car' ),
			'name_admin_bar'     => _x( 'Service Record', 'Admin Bar Name', 'my-car' ),
			'all_items'          => __( 'Service Records', 'my-car' ),
			'add_new_item'       => __( 'Add New Service Record', 'my-car' ),
			'add_new'            => __( 'Add New', 'my-car' ),
			'new_item'           => __( 'New Service Record', 'my-car' ),
			'edit_item'          => __( 'Edit Service Record', 'my-car' ),
			'update_item'        => __( 'Update Service Record', 'my-car' ),
			'view_item'          => __( 'View Service Record', 'my-car' ),
			'search_items'       => __( 'Search Service Records', 'my-car' ),
			'not_found'          => __( 'Not found', 'my-car' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'my-car' ),
		);

		$args = array(
			'label'               => __( 'Service Record', 'my-car' ),
			'description'         => __( 'Inspections, replacements and service history of cars', 'my-car' ),
			'labels'              => $labels,
			'supports'            => array(
				'title',
				'editor',
			),
			'hierarchical'        => false,
			'public'              => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=' . $this->post_type_name['car'],
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'post',
			'show_in_rest'        => false,
		);
		$this->register_post_type( $this->post_type, $args );
	}

	/**
	 * Register taxonomies
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function action_init_register_taxonomy() {
		$labels = array(
			'name'          => _x( 'Service Types', 'Taxonomy General Name', 'my-car' ),
			'singular_name' => _x( 'Service Type', 'Taxonomy Singular Name', 'my-car' ),
			'menu_name'     => __( 'Service Types', 'my-car' ),
			'all_items'     => __( 'All Service Types', 'my-car' ),
			'edit_item'     => __( 'Edit Service Type', 'my-car' ),
			'view_item'     => __( 'View Service Type', 'my-car' ),
			'update_item'   => __( 'Update Service Type', 'my-car' ),
			'add_new_item'  => __( 'Add New Service Type', 'my-car' ),
			'new_item_name' => __( 'New Service Type Name', 'my-car' ),
			'search_items'  => __( 'Search Service Types', 'my-car' ),
			'not_found'     => __( 'Not found', 'my-car' ),
		);
		$args   = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => false,
			'show_in_menu'      => 'edit.php?post_type=' . $this->post_type_name['car'],
			'show_in_nav_menus' => false,
			'show_tagcloud'     => false,
			'show_in_rest'      => true,
			'rest_base'         => 'car_service_types',
		);
		$this->register_taxonomy( 'service_type', $this->post_type, $args );
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
	}

	/**
	 * Add the Service Types screen under the Cars menu.
	 *
	 * WordPress adds taxonomy submenus only for top-level post type menus,
	 * so the taxonomy needs its own manual entry here.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function action_admin_menu() {
		$taxonomy = get_taxonomy( $this->taxonomy_name['service_type'] );
		if ( ! $taxonomy ) {
			return;
		}
		add_submenu_page(
			'edit.php?post_type=' . $this->post_type_name['car'],
			$taxonomy->labels->menu_name,
			$taxonomy->labels->menu_name,
			$taxonomy->cap->manage_terms,
			sprintf( 'edit-tags.php?taxonomy=%s&post_type=%s', $this->taxonomy_name['service_type'], $this->post_type_name[ $this->post_type ] )
		);
	}

	/**
	 * Settings: metabox configuration
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function action_init_settings() {
		$this->load_meta_boxes( $this->post_type );
		$this->handle_custom_columns( $this->post_type );
		$this->meta_boxes[ $this->post_type_name[ $this->post_type ] ] = array(
			'service-record' => array(
				'title'  => __( 'Service Event', 'my-car' ),
				'fields' => array(
					array(
						'name'  => 'car',
						'type'  => 'car',
						'label' => esc_html__( 'Car', 'my-car' ),
					),
					array(
						'name'  => 'event_date',
						'type'  => 'date',
						'label' => esc_html__( 'Date', 'my-car' ),
					),
					array(
						'name'  => 'event_mileage',
						'type'  => 'number',
						'label' => esc_html__( 'Mileage', 'my-car' ),
					),
					array(
						'name'  => 'workshop',
						'type'  => 'text',
						'label' => esc_html__( 'Workshop', 'my-car' ),
					),
					array(
						'name'  => 'cost',
						'type'  => 'number',
						'label' => esc_html__( 'Cost', 'my-car' ),
						'step'  => '0.01',
					),
					array(
						'name'    => 'status',
						'type'    => 'select',
						'label'   => esc_html__( 'Status', 'my-car' ),
						'options' => array(
							'done'    => esc_html__( 'Done', 'my-car' ),
							'planned' => esc_html__( 'Planned', 'my-car' ),
						),
					),
				),
			),
		);
	}

	/**
	 * Render the car select field.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Current post object.
	 * @param array   $one  Field configuration.
	 * @return void
	 */
	public function render_meta_car( $post, $one ) {
		$cars  = get_posts(
			array(
				'post_type'      => $this->post_type_name['car'],
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		$value = get_post_meta( $post->ID, $one['meta']['key'], true );
		echo '<p>';
		echo '<label>';
		echo esc_html( $one['label'] );
		echo '<br />';
		printf( '<select name="%s">', esc_attr( $one['meta']['key'] ) );
		printf( '<option value="">%s</option>', esc_html__( '&mdash; Select &mdash;', 'my-car' ) );
		foreach ( $cars as $car ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $car->ID ),
				selected( (string) $car->ID, (string) $value, false ),
				esc_html( $car->post_title )
			);
		}
		echo '</select>';
		echo '</label>';
		echo '</p>';
	}

	/**
	 * Sync the car <-> record join table after saving a record.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an update.
	 * @return void
	 */
	public function action_sync_join_table( $post_id, $post, $update ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$car_id  = absint( get_post_meta( $post_id, $this->get_post_meta_name( 'car', 'service-record' ), true ) );
		$date    = get_post_meta( $post_id, $this->get_post_meta_name( 'event_date', 'service-record' ), true );
		$mileage = absint( get_post_meta( $post_id, $this->get_post_meta_name( 'event_mileage', 'service-record' ), true ) );
		/**
		 * Auto-generate the title from car + date when empty.
		 */
		if ( '' === trim( (string) $post->post_title ) && $car_id ) {
			$title = sprintf(
				'%s — %s',
				get_the_title( $car_id ),
				$date ? $date : __( 'Service', 'my-car' )
			);
			remove_action( 'save_post_' . $this->post_type_name[ $this->post_type ], array( $this, 'action_sync_join_table' ), 20 );
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $title,
				)
			);
			add_action( 'save_post_' . $this->post_type_name[ $this->post_type ], array( $this, 'action_sync_join_table' ), 20, 3 );
		}
		if ( ! $car_id || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $date ) ) {
			$this->db->unlink_record( $post_id );
			return;
		}
		$this->db->link_record( $car_id, $post_id, $date, $mileage );
	}

	/**
	 * Remove join table rows when a record is deleted or trashed.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function action_delete_sync( $post_id ) {
		if ( get_post_type( $post_id ) !== $this->post_type_name[ $this->post_type ] ) {
			return;
		}
		$this->db->unlink_record( $post_id );
	}

	/**
	 * Remove join table rows when a car is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function action_delete_car_sync( $post_id ) {
		if ( get_post_type( $post_id ) !== $this->post_type_name['car'] ) {
			return;
		}
		$this->db->unlink_car( $post_id );
	}

	/**
	 * Admin list columns.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $columns Columns.
	 * @return string[] Columns.
	 */
	public function filter_manage_post_type_posts_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['car']           = __( 'Car', 'my-car' );
				$new['service_types'] = __( 'Service Types', 'my-car' );
				$new['event_date']    = __( 'Date', 'my-car' );
				$new['mileage']       = __( 'Mileage', 'my-car' );
				$new['workshop']      = __( 'Workshop', 'my-car' );
				$new['status']        = __( 'Status', 'my-car' );
			}
		}
		return $new;
	}

	/**
	 * Admin list column content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function action_manage_post_type_posts_custom_column( $column, $post_id ) {
		switch ( $column ) {
			case 'car':
				$car_id = absint( get_post_meta( $post_id, $this->get_post_meta_name( 'car', 'service-record' ), true ) );
				echo $car_id ? esc_html( get_the_title( $car_id ) ) : '&mdash;';
				break;
			case 'service_types':
				$terms = get_the_terms( $post_id, $this->taxonomy_name['service_type'] );
				if ( is_array( $terms ) ) {
					echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
				} else {
					echo '&mdash;';
				}
				break;
			case 'event_date':
				echo esc_html( get_post_meta( $post_id, $this->get_post_meta_name( 'event_date', 'service-record' ), true ) );
				break;
			case 'mileage':
				$mileage = get_post_meta( $post_id, $this->get_post_meta_name( 'event_mileage', 'service-record' ), true );
				echo esc_html( $mileage ? $mileage . ' km' : '' );
				break;
			case 'workshop':
				echo esc_html( get_post_meta( $post_id, $this->get_post_meta_name( 'workshop', 'service-record' ), true ) );
				break;
			case 'status':
				$status = get_post_meta( $post_id, $this->get_post_meta_name( 'status', 'service-record' ), true );
				echo 'planned' === $status ? esc_html__( 'Planned', 'my-car' ) : esc_html__( 'Done', 'my-car' );
				break;
		}
	}

	/**
	 * Sortable columns.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $columns Columns.
	 * @return string[] Columns.
	 */
	public function filter_manage_sortable_columns( $columns ) {
		$columns['event_date'] = 'event_date';
		$columns['mileage']    = $this->get_post_meta_name( 'event_mileage', 'service-record' );
		return $columns;
	}
}
