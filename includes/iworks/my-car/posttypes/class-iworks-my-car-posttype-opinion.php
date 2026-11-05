<?php
/**
 * Class for custom Post Type: OPINION
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

class iworks_my_car_posttype_opinion extends iworks_my_car_posttype {

	/**
	 * List of opinions
	 *
	 * @var array $list
	 */
	private array $list = array();

	/**
	 * Post type name
	 *
	 * @since 1.0.0
	 * @var string $post_type Post type identifier
	 */
	protected string $post_type = 'opinion';

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
		 * WordPress Hooks
		 */
		add_action( 'add_meta_boxes_' . $this->post_type_name[ $this->post_type ], array( $this, 'add_meta_boxes' ) );
		/**
		 * Shortcodes
		 */
		add_shortcode( 'iworks-opinions', array( $this, 'get_list' ) );
	}

	/**
	 * class settings
	 *
	 * @since 1.0.0
	 */
	public function action_init_settings() {
		/**
		 * Settings
		 */
		$this->meta_boxes[ $this->post_type_name[ $this->post_type ] ] = array(
			'opinion-data' => array(
				'title'  => __( 'Opinion Data', 'my-car' ),
				'fields' => array(
					array(
						'name'    => 'stars',
						'type'    => 'select',
						'label'   => esc_html__( 'The Opinion Stars', 'my-car' ),
						'options' => array(
							'5' => '&bigstar;&bigstar;&bigstar;&bigstar;&bigstar;',
							'4' => '&bigstar;&bigstar;&bigstar;&bigstar;',
							'3' => '&bigstar;&bigstar;&bigstar;',
							'2' => '&bigstar;&bigstar;',
							'1' => '&bigstar;',
						),
					),
					array(
						'name'  => 'opinion_url',
						'type'  => 'url',
						'label' => esc_html__( 'The Opinion URL', 'my-car' ),
					),
					array(
						'name'  => 'author_url',
						'type'  => 'url',
						'label' => esc_html__( 'The Opinion Author URL', 'my-car' ),
					),
				),
			),
		);
	}

	/**
	 * Get post list
	 *
	 * @since 1.0.0
	 *
	 * @param array  $atts Shortcode attributes
	 * @param string $content current content
	 *
	 * @return string $content
	 */
	public function get_list( $atts, $content = '' ) {
		$args = shortcode_atts(
			array(
				'orderby'        => 'rand',
				'posts_per_page' => 4,
			),
			$atts,
			'iworks/my-car/opinion/list'
		);
		/**
		 * Post type
		 */
		$args['post_type'] = $this->post_type_name[ $this->post_type ];
		/**
		 * Post status
		 */
		$args['post_status'] = 'publish';
		/**
		 * Query
		 */
		$the_query = new WP_Query( $args );
		/**
		 * No data!
		 */
		if ( ! $the_query->have_posts() ) {
			return $content;
		}
		/**
		 * Content
		 */
		ob_start();
		$file = $this->get_module_file( 'header', 'opinions' );
		if ( $file ) {
			include_once $file;
		}
		$join = rand( 0, 2 );
		$i    = 0;
		$file = $this->get_module_file( 'one', 'opinions' );
		while ( $the_query->have_posts() ) {
			$the_query->the_post();
			$one = array(

				'join' => $join,
				'i'    => $i++,
			);
			if ( $file ) {
				include_once $file;
			}
		}
		/* Restore original Post Data */
		wp_reset_postdata();
		$file = $this->get_module_file( 'footer', 'opinions' );
		if ( $file ) {
			include_once $file;
		}
		$content = ob_get_contents();
		ob_end_clean();
		return apply_filters( 'iworks/my-car/opinion/get_list', $content );
	}

	/**
	 * Register Custom Post Type
	 *
	 * @since 1.0.0
	 */
	public function action_init_register_post_type() {
		$labels = array(
			'name'                  => _x( 'Opinions', 'Post Type General Name', 'my-car' ),
			'singular_name'         => _x( 'Opinion', 'Post Type Singular Name', 'my-car' ),
			'menu_name'             => __( 'Opinions', 'my-car' ),
			'name_admin_bar'        => __( 'Opinions', 'my-car' ),
			'archives'              => __( 'Opinions', 'my-car' ),
			'all_items'             => __( 'Opinions', 'my-car' ),
			'add_new_item'          => __( 'Add New Opinion', 'my-car' ),
			'add_new'               => __( 'Add New', 'my-car' ),
			'new_item'              => __( 'New Opinion', 'my-car' ),
			'edit_item'             => __( 'Edit Opinion', 'my-car' ),
			'update_item'           => __( 'Update Opinion', 'my-car' ),
			'view_item'             => __( 'View Opinion', 'my-car' ),
			'view_items'            => __( 'View Opinion', 'my-car' ),
			'search_items'          => __( 'Search Opinion', 'my-car' ),
			'not_found'             => __( 'Not found', 'my-car' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'my-car' ),
			'items_list'            => __( 'Opinion list', 'my-car' ),
			'items_list_navigation' => __( 'Opinion list navigation', 'my-car' ),
			'filter_items_list'     => __( 'Filter items list', 'my-car' ),
		);
		$args   = array(
			'can_export'          => true,
			'capability_type'     => 'page',
			'description'         => __( 'Opinion', 'my-car' ),
			'exclude_from_search' => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'label'               => __( 'Opinions', 'my-car' ),
			'labels'              => $labels,
			'menu_icon'           => 'dashicons-businessopinion',
			'public'              => false,
			'show_in_admin_bar'   => false,
			'menu_position'       => 20,
			'show_in_nav_menus'   => false,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php',
			'show_in_rest'        => false,
			'supports'            => array( 'title', 'thumbnail', 'editor', 'revisions' ),
			'rewrite'             => array(
				'slug' => defined( 'ICL_SITEPRESS_VERSION' ) ? 'opinion' : _x( 'opinion', 'My Car Post Type Opinion SLUG', 'my-car' ),
			),
		);
		$this->register_post_type( $this->post_type, $args );
	}

	/**
	 * Register Custom Taxonomy
	 *
	 * @since 1.0.0
	 */
	public function action_init_register_taxonomy() {}

	/**
	 * Get post list
	 *
	 * @param array $list options list
	 * @param array $atts WP_Query attributes
	 *
	 * @return string $content
	 */
	public function get_options_list_array( $list, $atts = array() ) {
		if ( ! empty( $this->list ) ) {
			return $this->list;
		}
		$list       = $this->get_select_array( $this->post_type_name['opinion'] );
		$this->list = $list;
		return $list;
	}
}
