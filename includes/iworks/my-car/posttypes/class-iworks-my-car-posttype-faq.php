<?php
/**
 * Class for custom Post Type: FAQ
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

class iworks_my_car_posttype_faq extends iworks_my_car_posttype {

	/**
	 * List of FAQs
	 *
	 * @since 1.0.0
	 * @var array $list Array of FAQ items
	 */
	private array $list = array();

	/**
	 * Post type name
	 *
	 * @since 1.0.0
	 * @var string $post_type Post type identifier
	 */
	protected string $post_type = 'faq';

	/**
	 * Taxonomy name
	 *
	 * @since 1.0.0
	 * @var string $taxonomy Taxonomy identifier
	 */
	private string $taxonomy = 'faq_group';

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
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'action_add_menu_order_value' ), 10, 2 );
		add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'filter_add_menu_order_column' ) );
		add_filter( 'wp_localize_script_iworks_theme', array( $this, 'filter_wp_localize_script_iworks_theme' ) );
		add_shortcode( 'iworks-faq', array( $this, 'shortcode_list' ) );
	}

	/**
	 * class settings
	 *
	 * @since 1.0.0
	 */
	public function action_init_settings() {
	}

	/**
	 * Register FAQs custom post type
	 */
	public function action_init_register_post_type() {

		$labels = array(
			'name'               => _x( 'FAQs', 'Post Type General Name', 'my-car' ),
			'singular_name'      => _x( 'FAQ', 'Post Type Singular Name', 'my-car' ),
			'menu_name'          => _x( 'FAQs', 'Menu Name', 'my-car' ),
			'name_admin_bar'     => _x( 'FAQ', 'Admin Bar Name', 'my-car' ),
			'parent_item_colon'  => __( 'Parent FAQ:', 'my-car' ),
			'all_items'          => __( 'FAQs', 'my-car' ),
			'add_new_item'       => __( 'Add New FAQ', 'my-car' ),
			'add_new'            => __( 'Add New', 'my-car' ),
			'new_item'           => __( 'New FAQ', 'my-car' ),
			'edit_item'          => __( 'Edit FAQ', 'my-car' ),
			'update_item'        => __( 'Update FAQ', 'my-car' ),
			'view_item'          => __( 'View FAQ', 'my-car' ),
			'search_items'       => __( 'Search FAQ', 'my-car' ),
			'not_found'          => __( 'Not found', 'my-car' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'my-car' ),
		);

		$args = array(
			'label'               => __( 'FAQ', 'my-car' ),
			'description'         => __( 'Frequently Asked Questions', 'my-car' ),
			'labels'              => $labels,
			'supports'            => array(
				'title',
				'page-attributes',
				'editor',
			),
			'hierarchical'        => true,
			'public'              => true,
			'exclude_from_search' => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 10,
			'menu_icon'           => 'dashicons-format-chat',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
			'rest_base'           => apply_filters(
				'iworks/my-car/register_post_type/' . $this->post_type . '/rest_base',
				defined( 'ICL_SITEPRESS_VERSION' ) ? 'faq' : __( 'faqs', 'my-car' )
			),
		);
		$this->register_post_type( $this->post_type, $args );
	}

	/**
	 * Register FAQ Group custom taxonomy
	 */
	public function action_init_register_taxonomy() {
		$labels = array(
			'name'                       => _x( 'FAQ Groups', 'Taxonomy General Name', 'my-car' ),
			'singular_name'              => _x( 'FAQ Group', 'Taxonomy Singular Name', 'my-car' ),
			'menu_name'                  => __( 'Groups', 'my-car' ),
			'all_items'                  => __( 'All FAQ Groups', 'my-car' ),
			'parent_item'                => __( 'Parent FAQ Group', 'my-car' ),
			'parent_item_colon'          => __( 'Parent FAQ Group:', 'my-car' ),
			'new_item_name'              => __( 'New FAQ Group Name', 'my-car' ),
			'add_new_item'               => __( 'Add New FAQ Group', 'my-car' ),
			'edit_item'                  => __( 'Edit FAQ Group', 'my-car' ),
			'update_item'                => __( 'Update FAQ Group', 'my-car' ),
			'view_item'                  => __( 'View FAQ Group', 'my-car' ),
			'separate_items_with_commas' => __( 'Separate FAQ Groups with commas', 'my-car' ),
			'add_or_remove_items'        => __( 'Add or remove FAQ Groups', 'my-car' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'my-car' ),
			'popular_items'              => __( 'Popular FAQ Groups', 'my-car' ),
			'search_items'               => __( 'Search FAQ Groups', 'my-car' ),
			'not_found'                  => __( 'Not Found', 'my-car' ),
		);
		$args   = array(
			'labels'              => $labels,
			'hierarchical'        => true,
			'public'              => true,
			'exclude_from_search' => false,
			'rewrite'             => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_admin_column'   => true,
			'show_in_nav_menus'   => true,
			'show_tagcloud'       => false,
			'show_in_rest'        => true,
			'rest_base'           => defined( 'ICL_SITEPRESS_VERSION' ) ? 'faq_groups' : _x( 'faq_groups', 'register taxonomy rest base', 'my-car' ),
		);
		register_taxonomy( $this->taxonomy_name[ $this->taxonomy ], $this->post_type_name[ $this->post_type ], $args );
	}

	/**
	 * Shortcode: List FAQs by group
	 *
	 * @since 1.0.0
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string
	 */
	public function shortcode_list( $atts, $content = '' ) {
		$args = shortcode_atts(
			array(
				'dd'            => 'show',
				'description'   => 'hide',
				'header'        => 'show',
				'header-button' => 'hide',
				'id'            => sprintf( 'iworks-faq-%s', md5( time() + mt_rand( 0, mt_getrandmax() ) ) ),
				'tag'           => 'aside',
				'term_id'       => false, // required
			),
			$atts,
			'iworks/my-car/faq/list'
		);
		if ( empty( $args['term_id'] ) ) {
			return $content;
		}
		$term = get_term( $args['term_id'], $this->taxonomy_name[ $this->taxonomy ] );
		if ( empty( $term ) ) {
			return $content;
		}
		$query_args = array(
			'post_type'      => $this->post_type_name[ $this->post_type ],
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy' => $this->taxonomy_name[ $this->taxonomy ],
					'field'    => 'slug',
					'terms'    => $term->slug,
				),
			),
		);
		$the_query  = new WP_Query( $query_args );
		if ( ! $the_query->have_posts() ) {
			return $content;
		}
		/**
		 * classes
		 */
		$classes  = array(
			'iworks-faq',
			sprintf( 'iworks-faq-header-%s', $args['header'] ),
			sprintf( 'iworks-faq-dd-%s', $args['dd'] ),
			sprintf( 'iworks-faq-tag-%s', $args['tag'] ),
		);
		$content .= sprintf(
			'<%s class="%s" id="%s">',
			$args['tag'],
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $args['id'] )
		);
		if ( 'show' === $args['header'] ) {
			$content .= '<div class="iworks-faq-header">';
			$content .= sprintf( '<h2 class="iworks-faq-header-title">%s</h2>', $term->name );
			if ( 'show' === $args['header-button'] ) {
				$content .= sprintf(
					'<button class="iworks-faq-header-toggle" aria-label="%s" aria-expanded="false" data-target-id="%s" data-expanded="false">%s</button>',
					esc_attr__( 'Expand All', 'my-car' ),
					esc_attr( $args['id'] ),
					esc_html__( 'Expand All', 'my-car' )
				);
			}
			if ( 'show' === $args['description'] ) {
				$description = term_description( $args['term_id'] );
				if ( ! is_wp_error( $description ) ) {
					$description = trim( $description );
					if ( $description ) {
						$content .= '<div class="polon-faq-header-description">';
						$content .= wpautop( $description );
						$content .= '</div>';
					}
				}
			}
			$content .= '</div>';
		}
		$content .= '<div class="iworks-faq-container">';
		$content .= '<dl class="iworks-faq-list">';
		$i        = 1;
		while ( $the_query->have_posts() ) {
			$the_query->the_post();
			$button   = '';
			$classes  = array( 'iworks-faq-item-dd' );
			$dd_attrs = array();
			if ( 'folded' === $args['dd'] ) {
				$is_first_open = 1 === $i && 'unfolded' === $args['first'];
				$id            = sprintf( 'iworks-faq-item-dt-%d', $i++ );
				$button        = sprintf(
					' <button class="iworks-faq-list-toggle-button" aria-expanded="%s" aria-controls="%s" data-target-id="%s" aria-label="%s"><span class="sr-only">%s</span></button>',
					esc_attr( $is_first_open ? 'true' : 'false' ),
					esc_attr( $id ),
					esc_attr( $id ),
					esc_attr(
						sprintf(
							/* translators: %s: FAQ entry title. */
							__( 'Expand %s', 'my-car' ),
							get_the_title()
						)
					),
					esc_html(
						sprintf(
							/* translators: %s: FAQ entry title. */
							__( 'Expand %s', 'my-car' ),
							get_the_title()
						)
					)
				);
				if ( ! $is_first_open ) {
					$dd_attrs[] = 'hidden';
				}
				$dd_attrs[] = sprintf( 'id="%s"', esc_attr( $id ) );
				++$i;
			}

			$content .= sprintf(
				'<dt class="iworks-faq-item-dt"><span class="iworks-faq-list-toggle">%s</span>%s</dt>',
				esc_html( get_the_title() ),
				$button
			);
			$content .= sprintf(
				'<dd class="%s"%s>%s</dd>',
				esc_attr( implode( ' ', $classes ) ),
				implode( ' ', $dd_attrs ),
				get_the_content()
			);
		}
		$content .= '</dl>';
		wp_reset_postdata();
		$content .= '</div>';
		$content .= sprintf( '</%s>', $args['tag'] );
		return apply_filters( 'iworks/theme/post_type/faq/shortcode_list/content', $content, $args );
	}

	/**
	 * Filter wp_localize_script iworks_theme
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Localized data.
	 * @return array
	 */
	public function filter_wp_localize_script_iworks_theme( $data ) {
		$data['i18n']['modules']['faq'] = array(
			'button' => array(
				'expand_all'   => esc_html__( 'Expand All', 'my-car' ),
				'collapse_all' => esc_html__( 'Collapse All', 'my-car' ),
			),
		);
		return apply_filters( 'iworks/theme/post_type/faq/wp_localize_script_iworks_theme', $data );
	}
}
