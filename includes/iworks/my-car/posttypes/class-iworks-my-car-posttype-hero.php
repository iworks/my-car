<?php
/**
 * Class for custom Post Type: HERO
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

class iworks_my_car_posttype_hero extends iworks_my_car_posttype {

	/**
	 * Post type name
	 *
	 * @since 1.0.0
	 * @var string $post_type Post type identifier
	 */
	protected string $post_type = 'hero';

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
		add_shortcode( 'iworks-heroes', array( $this, 'get_list' ) );
	}

	/**
	 * class settings
	 *
	 * @since 1.0.0
	 */
	public function action_init_settings() {
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
				'posts_per_page' => 2,
				'post_status'    => 'publish',
				'title'          => esc_html__( 'Learn what our employees are saying.', 'my-car' ),
				'subtitle'       => esc_html__( 'Become one of them!', 'my-car' ),
			),
			$atts,
			'iworks/my-car/hero/list'
		);
		return $this->shortcode_list( $args, $content );
	}

	/**
	 * Register Custom Taxonomy
	 *
	 * @since 1.0.0
	 */
	public function action_init_register_taxonomy() {}

	/**
	 * Register Custom Post Type
	 *
	 * @since 1.0.0
	 */
	public function action_init_register_post_type() {
		$labels = array(
			'name'                  => _x( 'Heroes', 'Post Type General Name', 'my-car' ),
			'singular_name'         => _x( 'Hero', 'Post Type Singular Name', 'my-car' ),
			'menu_name'             => __( 'Heroes', 'my-car' ),
			'name_admin_bar'        => __( 'Heroes', 'my-car' ),
			'archives'              => __( 'Heroes', 'my-car' ),
			'all_items'             => __( 'Heroes', 'my-car' ),
			'add_new_item'          => __( 'Add New Hero', 'my-car' ),
			'add_new'               => __( 'Add New', 'my-car' ),
			'new_item'              => __( 'New Hero', 'my-car' ),
			'edit_item'             => __( 'Edit Hero', 'my-car' ),
			'update_item'           => __( 'Update Hero', 'my-car' ),
			'view_item'             => __( 'View Hero', 'my-car' ),
			'view_items'            => __( 'View Hero', 'my-car' ),
			'search_items'          => __( 'Search Hero', 'my-car' ),
			'not_found'             => __( 'Not found', 'my-car' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'my-car' ),
			'items_list'            => __( 'Hero list', 'my-car' ),
			'items_list_navigation' => __( 'Hero list navigation', 'my-car' ),
			'filter_items_list'     => __( 'Filter items list', 'my-car' ),
		);
		$args   = array(
			'can_export'          => true,
			'capability_type'     => 'page',
			'description'         => __( 'Hero', 'my-car' ),
			'exclude_from_search' => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'label'               => __( 'Heroes', 'my-car' ),
			'labels'              => $labels,
			'menu_icon'           => 'dashicons-businessperson',
			'public'              => false,
			'show_in_admin_bar'   => false,
			'show_in_menu'        => 'edit.php',
			'show_in_nav_menus'   => false,
			'show_ui'             => true,
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'thumbnail', 'editor', 'excerpt', 'page-attributes' ),
		);
		$this->register_post_type( $this->post_type, $args );
	}
}
