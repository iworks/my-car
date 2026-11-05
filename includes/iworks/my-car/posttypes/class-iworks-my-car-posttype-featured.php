<?php
/**
 * Class for custom Post Type: featured
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

class iworks_my_car_posttype_featured extends iworks_my_car_posttype {

	/**
	 * Post type name
	 *
	 * @since 1.0.0
	 * @var string $post_type Post type identifier
	 */
	protected string $post_type = 'featured';

	/**
	 * Option names
	 *
	 * @since 1.0.0
	 */
	private string $option_name_url = '_featured_url';

	/**
	 * Button label more option name
	 *
	 * @since 1.0.0
	 */
	private string $option_name_button_label_more = '_featured_button_label_more';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		/**
		 * settings
		 */
		$this->load_plugin_admin_assets = true;
		/**
		 * Post Type Name
		 *
		 * @since 1.0.0
		 */
		$this->register_class_custom_posttype_name( $this->post_type );
		/**
		 * load meta boxes
		 */
		$this->load_meta_boxes( $this->post_type );
		/**
		 * WordPress Hooks
		 */
		add_action( 'pre_get_posts', array( $this, 'admin_set_default_order' ) );
		/**
		 * WordPress Hooks: add column order
		 */
		$this->add_column_order( $this->post_type );
		/**
		 * shortcode
		 */
		add_shortcode( 'iworks-featured', array( $this, 'shortcode_default_list' ) );
	}

	/**
	 * class settings
	 *
	 * @since 1.0.0
	 */
	public function action_init_settings() {
		$this->meta_boxes[ $this->post_type_name[ $this->post_type ] ] = array(
			'featured-url' => array(
				'title'  => __( 'URL Configuration', 'my-car' ),
				'fields' => array(
					array(
						'name'  => $this->option_name_url,
						'type'  => 'url',
						'label' => esc_html__( 'Target Button URL', 'my-car' ),
					),
					array(
						'name'  => $this->option_name_button_label_more,
						'label' => esc_html__( 'Target Button Text', 'my-car' ),
					),
				),
			),
		);
	}

	/**
	 * Set default order for admin
	 *
	 * @since 1.0.0
	 */
	public function admin_set_default_order( $query ) {
		if ( ! is_admin() ) {
			return;
		}
		if ( $this->post_type_name !== $query->get( 'post_type' ) ) {
			return;
		}
		$query->set( 'orderby', 'menu_order' );
		$query->set( 'order', 'ASC' );
	}

	/**
	 * Get promo post list
	 *
	 * @since 1.0.0
	 *
	 * @param string $content current content
	 *
	 * @return string $content
	 */
	public function get_list( $content ) {
		$args      = array(
			'post_type'      => $this->post_type_name,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'posts_per_page' => 3,
			'post_status'    => 'publish',
		);
		$url       = get_bloginfo( 'url' );
		$url_info  = parse_url( $url );
		$host      = $url_info['host'];
		$the_query = new WP_Query( $args );
		// The Loop
		if ( $the_query->have_posts() ) {
			$content .= '<section class="promo" id="promo">';
			$content .= '<div class="section-wrapper">';
			$content .= '<div class="container">';
			$first    = true;
			while ( $the_query->have_posts() ) {
				if ( ! $first ) {
					$content .= '<span class="split"></span>';
				}
				$first = false;
				$the_query->the_post();
				$url               = get_post_meta( get_the_ID(), $this->option_name_url, true );
				$url_info          = parse_url( $url );
				$target            = $host === $url_info['host'] ? '' : ' target="_blank"';
				$content          .= '<div class="post-inner">';
				$content          .= '<div class="thumbnail">';
				$content          .= get_the_post_thumbnail( get_the_ID(), 'full' );
				$content          .= '</div>';
				$content          .= sprintf(
					'<p>%s</p>',
					get_the_title()
				);
				$button_label_more = get_post_meta( get_the_ID(), $this->option_name_button_label_more, true );
				if ( empty( $button_label_more ) ) {
					$button_label_more = _x( 'Find out more', 'Promo button text', 'my-car' );
				}
				$content .= sprintf(
					'<a href="%s" class="button button-small button-invert" %s title="%s">%s</a>',
					esc_url( $url ),
					$target,
					esc_attr(
						sprintf(
							/* translators: %s: article title. */
							__( 'Article on: %s', 'my-car' ),
							get_the_title()
						)
					),
					esc_html( $button_label_more )
				);
				$content .= '</div>';
			}
			$content .= '</div>';
			$content .= '</section>';
		}
		/* Restore original Post Data */
		wp_reset_postdata();
		return $content;
	}

	public function action_init_register_taxonomy() {}

	/**
	 * Register Custom Post Type
	 *
	 * @since 1.0.0
	 */
	public function action_init_register_post_type() {
		$labels = array(
			'name'                  => _x( 'Featured', 'Post Type General Name', 'my-car' ),
			'singular_name'         => _x( 'Featured', 'Post Type Singular Name', 'my-car' ),
			'menu_name'             => __( 'Featured', 'my-car' ),
			'name_admin_bar'        => __( 'Featured', 'my-car' ),
			'archives'              => __( 'Featured', 'my-car' ),
			'all_items'             => __( 'Featured', 'my-car' ),
			'add_new_item'          => __( 'Add New Featured', 'my-car' ),
			'add_new'               => __( 'Add New', 'my-car' ),
			'new_item'              => __( 'New Featured', 'my-car' ),
			'edit_item'             => __( 'Edit Featured', 'my-car' ),
			'update_item'           => __( 'Update Featured', 'my-car' ),
			'view_item'             => __( 'View Featured', 'my-car' ),
			'view_items'            => __( 'View Featured', 'my-car' ),
			'search_items'          => __( 'Search Featured', 'my-car' ),
			'not_found'             => __( 'Not found', 'my-car' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'my-car' ),
			'items_list'            => __( 'Featured list', 'my-car' ),
			'items_list_navigation' => __( 'Featured list navigation', 'my-car' ),
			'filter_items_list'     => __( 'Filter items list', 'my-car' ),
		);
		$args   = array(
			'can_export'          => true,
			'capability_type'     => 'page',
			'description'         => __( 'Featured', 'my-car' ),
			'exclude_from_search' => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'label'               => __( 'Featured', 'my-car' ),
			'labels'              => $labels,
			'menu_icon'           => 'dashicons-businessperson',
			'public'              => false,
			'show_in_admin_bar'   => false,
			'show_in_menu'        => 'edit.php',
			'show_in_nav_menus'   => false,
			'show_ui'             => true,
			'show_in_rest'        => false,
			'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
		);
		$this->register_post_type( $this->post_type, $args );
	}
}
