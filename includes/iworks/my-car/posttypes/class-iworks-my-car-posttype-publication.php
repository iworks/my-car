<?php
/**
 * Class for custom Post Type: PUBLICATION
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

class iworks_my_car_posttype_publication extends iworks_my_car_posttype {

	/**
	 * Post type name
	 *
	 * @since 1.0.0
	 * @var string $post_type Post type identifier
	 */
	protected string $post_type = 'publication';

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
		add_filter( 'the_content', array( $this, 'the_content' ) );
		add_action( 'pre_get_posts', array( $this, 'set_default_order' ) );
		/**
		 * My Car Hooks
		 */
		add_filter( 'my_car_' . $this->post_type . '_publications', array( $this, 'get_random' ), 10, 2 );
		add_filter( 'my_car_' . $this->post_type . '_tab_button_more_url', array( $this, 'get_archive_page_url' ), 10, 4 );
	}

	/**
	 * Register taxonomy
	 *
	 * @since 1.0.0
	 */
	public function action_init_register_taxonomy() {}

	/**
	 * class settings
	 *
	 * @since 1.0.0
	 */
	public function action_init_settings() {
		$this->meta_boxes[ $this->post_type_name[ $this->post_type ] ] = array(
			'publication-data' => array(
				'title'  => __( 'Data', 'my-car' ),
				'fields' => array(
					array(
						'name'  => 'language',
						'type'  => 'select',
						'label' => esc_html__( 'Language', 'my-car' ),
					),
					array(
						'name'  => 'year',
						'type'  => 'year',
						'label' => esc_html__( 'Year', 'my-car' ),
					),
					array(
						'name'  => 'authors',
						'label' => esc_html__( 'Authors', 'my-car' ),
					),
					array(
						'name'  => 'where',
						'label' => esc_html__( 'Where', 'my-car' ),
					),
					array(
						'name'  => 'url',
						'type'  => 'url',
						'label' => esc_html__( 'URL', 'my-car' ),
					),
					array(
						'name'  => 'conference',
						'label' => esc_html__( 'Conference', 'my-car' ),
					),
				),
			),
		);
	}

	/**
	 * Set default order
	 *
	 * @since 1.0.0
	 */
	public function set_default_order( $query ) {
		if ( is_admin() ) {
			return;
		}
		if ( $this->post_type_name !== $query->get( 'post_type' ) ) {
			return;
		}
		$query->set( 'meta_key', 'opi_publication_year' );
		$query->set(
			'orderby',
			array(
				'meta_value_num' => 'DESC',
				'title'          => 'ASC',
			)
		);
	}

	/**
	 * Get archive page URL
	 *
	 * @since 1.0.0
	 */
	public function get_archive_page_url( $url, $type, $id, $language ) {
		if ( 'publications' !== $type ) {
			return $url;
		}
		return get_post_type_archive_link( $this->post_type_name[ $this->post_type ] );
	}

	/**
	 * Get random publication
	 *
	 * @since 1.0.0
	 */
	public function get_random( $content, $args ) {
		$args                   = wp_parse_args(
			$args,
			array(
				'post_type'      => $this->post_type_name[ $this->post_type ],
				'orderby'        => 'rand',
				'posts_per_page' => 1,
				'wp_doing_ajax'  => apply_filters( 'wp_doing_ajax', false ),
			)
		);
		$args['posts_per_page'] = max( 1, intval( $args['posts_per_page'] ) );
		$the_query              = new WP_Query( $args );
		if ( 'pl_PL' === get_locale() ) {
			$content .= '<span class="section-title">Publikacje Naukowe OPI PIB</span>';
		} else {
			$content .= sprintf( '<span class="section-title">%s</span>', esc_html__( 'Scientific publications of OPI PIB', 'my-car' ) );
		}
		if ( $the_query->have_posts() ) {
			ob_start();
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				get_template_part( 'template-parts/single-content', 'publication', $args );
			}
			$content .= ob_get_contents();
			ob_end_clean();
		}
		return $content;
	}

	/**
	 * Modify post content
	 *
	 * @since 1.0.0
	 */
	public function the_content( $content ) {
		if ( get_post_type() !== $this->post_type_name[ $this->post_type ] ) {
			return $content;
		}
		$post_ID = get_the_ID();
		$c       = '';
		/**
		 * Authors
		 */
		$value = get_post_meta( $post_ID, 'opi_publication_authors', true );
		if ( ! empty( $value ) ) {
			$c .= sprintf(
				'<p class="opi-publication-authors">%s</p>',
				$value
			);
		}
		/**
		 * Year & where
		 */
		$year  = get_post_meta( $post_ID, 'opi_publication_year', true );
		$value = get_post_meta( $post_ID, 'opi_publication_where', true );
		if ( ! empty( $year ) || ! empty( $value ) ) {
			$c .= '<p class="opi-publication-where">';
			if ( ! empty( $year ) ) {
				$c .= sprintf( '<span class="year">%d</span> ', $year );
			}
			if ( ! empty( $value ) ) {
				$c .= $value;
			}
			$c .= '</p>';
		}
		/**
		 * conference
		 */
		$value = get_post_meta( $post_ID, 'opi_publication_conference', true );
		if ( ! empty( $value ) ) {
			$c .= sprintf(
				'<p class="opi-publication-conference">%s</p>',
				$value
			);
		}
		/**
		 * Content
		 */
		$c .= $content;
		/**
		 * url
		 */
		$value = get_post_meta( $post_ID, 'opi_publication_url', true );
		if ( ! empty( $value ) ) {
			$c .= sprintf(
				'<p class="opi-publication-url"><a href="%1$s" target="_blank">%1$s</a></p>',
				$value
			);
		}
		return $c;
	}

	/**
	 * Register Custom Post Type
	 *
	 * @since 1.0.0
	 */
	public function action_init_register_post_type() {
		$labels = array(
			'name'                  => _x( 'Publications', 'Post Type General Name', 'my-car' ),
			'singular_name'         => _x( 'Publication', 'Post Type Singular Name', 'my-car' ),
			'menu_name'             => __( 'Publications', 'my-car' ),
			'name_admin_bar'        => __( 'Publications', 'my-car' ),
			'archives'              => __( 'Publications', 'my-car' ),
			'all_items'             => __( 'Publications', 'my-car' ),
			'add_new_item'          => __( 'Add New Publication', 'my-car' ),
			'add_new'               => __( 'Add New', 'my-car' ),
			'new_item'              => __( 'New Publication', 'my-car' ),
			'edit_item'             => __( 'Edit Publication', 'my-car' ),
			'update_item'           => __( 'Update Publication', 'my-car' ),
			'view_item'             => __( 'View Publication', 'my-car' ),
			'view_items'            => __( 'View Publication', 'my-car' ),
			'search_items'          => __( 'Search Publication', 'my-car' ),
			'not_found'             => __( 'Not found', 'my-car' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'my-car' ),
			'items_list'            => __( 'Publication list', 'my-car' ),
			'items_list_navigation' => __( 'Publication list navigation', 'my-car' ),
			'filter_items_list'     => __( 'Filter items list', 'my-car' ),
		);
		$args   = array(
			'can_export'          => true,
			'capability_type'     => 'page',
			'description'         => __( 'Publication', 'my-car' ),
			'exclude_from_search' => true,
			'has_archive'         => true,
			'hierarchical'        => false,
			'label'               => __( 'Publications', 'my-car' ),
			'labels'              => $labels,
			'menu_icon'           => 'dashicons-businessperson',
			'public'              => true,
			'show_in_admin_bar'   => true,
			'show_in_menu'        => 'edit.php',
			'show_in_nav_menus'   => true,
			'show_ui'             => true,
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor', 'excerpt' ),
		);
		$this->register_post_type( $this->post_type, $args );
	}
}
