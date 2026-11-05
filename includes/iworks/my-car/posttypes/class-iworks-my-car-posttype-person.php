<?php
/**
 * Class for custom Post Type: PERSON
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

class iworks_my_car_posttype_person extends iworks_my_car_posttype {

	/**
	 * Post type name
	 *
	 * @since 1.0.0
	 * @var string $post_type Post type identifier
	 */
	protected string $post_type = 'person';

	/**
	 * Taxonomy name
	 *
	 * @since 1.0.0
	 * @var string $taxonomy Taxonomy identifier
	 */
	private string $taxonomy = 'person_role';

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
		 * Taxonomy name
		 */
		$this->register_class_custom_taxonomy_name( $this->taxonomy );
		/**
		 * WordPress Hooks
		 */
		add_shortcode( 'iworks-persons', array( $this, 'shortcode_persons' ) );
		add_filter( 'og_og_type_value', array( $this, 'filter_og_og_type_value' ) );
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
	public function shortcode_persons( $atts, $content = '' ) {
		return $this->shortcode_list( $atts, $content );
	}

	/**
	 * Register Custom Post Type
	 *
	 * @since 1.0.0
	 */
	public function action_init_register_post_type() {
		$labels = array(
			'name'                  => _x( 'Persons', 'Post Type General Name', 'my-car' ),
			'singular_name'         => _x( 'Person', 'Post Type Singular Name', 'my-car' ),
			'menu_name'             => __( 'Persons', 'my-car' ),
			'name_admin_bar'        => __( 'Persons', 'my-car' ),
			'archives'              => __( 'Persons', 'my-car' ),
			'all_items'             => __( 'Persons', 'my-car' ),
			'add_new_item'          => __( 'Add New Person', 'my-car' ),
			'add_new'               => __( 'Add New', 'my-car' ),
			'new_item'              => __( 'New Person', 'my-car' ),
			'edit_item'             => __( 'Edit Person', 'my-car' ),
			'update_item'           => __( 'Update Person', 'my-car' ),
			'view_item'             => __( 'View Person', 'my-car' ),
			'view_items'            => __( 'View Person', 'my-car' ),
			'search_items'          => __( 'Search Person', 'my-car' ),
			'not_found'             => __( 'Not found', 'my-car' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'my-car' ),
			'items_list'            => __( 'Person list', 'my-car' ),
			'items_list_navigation' => __( 'Person list navigation', 'my-car' ),
			'filter_items_list'     => __( 'Filter items list', 'my-car' ),
		);
		$args   = array(
			'can_export'          => true,
			'capability_type'     => 'page',
			'description'         => __( 'Person', 'my-car' ),
			'exclude_from_search' => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'label'               => __( 'Persons', 'my-car' ),
			'labels'              => $labels,
			'menu_icon'           => 'dashicons-businessperson',
			'public'              => true,
			'show_in_admin_bar'   => false,
			'menu_position'       => 20,
			'show_in_nav_menus'   => false,
			'show_ui'             => true,
			'show_in_rest'        => false,
			'supports'            => array( 'title', 'thumbnail', 'editor', 'revisions' ),
			'rewrite'             => array(
				'slug' => defined( 'ICL_SITEPRESS_VERSION' ) ? 'person' : _x( 'person', 'My Car Post Type Person SLUG', 'my-car' ),
			),
		);
		$this->register_post_type( $this->post_type, $args );
	}

	/**
	 * Register Custom Taxonomy
	 *
	 * @since 1.0.0
	 */
	public function action_init_register_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Roles', 'Role General Name', 'my-car' ),
			'singular_name'              => _x( 'Role', 'Role Singular Name', 'my-car' ),
			'menu_name'                  => __( 'Roles', 'my-car' ),
			'all_items'                  => __( 'All Roles', 'my-car' ),
			'parent_item'                => __( 'Parent Role', 'my-car' ),
			'parent_item_colon'          => __( 'Parent Role:', 'my-car' ),
			'new_item_name'              => __( 'New Role Name', 'my-car' ),
			'add_new_item'               => __( 'Add New Role', 'my-car' ),
			'edit_item'                  => __( 'Edit Role', 'my-car' ),
			'update_item'                => __( 'Update Role', 'my-car' ),
			'view_item'                  => __( 'View Role', 'my-car' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'my-car' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'my-car' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'my-car' ),
			'popular_items'              => __( 'Popular Roles', 'my-car' ),
			'search_items'               => __( 'Search Roles', 'my-car' ),
			'not_found'                  => __( 'Not Found', 'my-car' ),
			'no_terms'                   => __( 'No items', 'my-car' ),
			'items_list'                 => __( 'Roles list', 'my-car' ),
			'items_list_navigation'      => __( 'Roles list navigation', 'my-car' ),
		);
		$args   = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_admin_column' => true,
			'show_tagcloud'     => false,
			'rewrite'           => array(
				'slug' => defined( 'ICL_SITEPRESS_VERSION' ) ? 'role' : _x( 'role', 'My Car Post Type Person SLUG', 'my-car' ),
			),
		);
		$this->register_taxonomy( $this->taxonomy, $this->post_type, $args );
	}

	/**
	 * Filter Open Graph type value
	 *
	 * @since 1.0.0
	 */
	public function filter_og_og_type_value( $value ) {
		if ( is_singular( $this->post_type_name ) ) {
			return 'profile';
		}
		return $value;
	}
}
