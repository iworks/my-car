<?php
/**
 * Class for custom Post Type: PROJECT
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

class iworks_my_car_posttype_project extends iworks_my_car_posttype {

	// private string $posttype_name;

	private array $fields                = array();
	private string $option_name_partners = '_partners';
	/**
	 * partners types
	 *
	 * @since 1.0.0
	 */
	private array $partners_types;
	/**
	 * Post type name
	 *
	 * @since 1.0.0
	 * @var string $post_type Post type identifier
	 */
	protected string $post_type = 'project';

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
		add_action( 'pre_get_posts', array( $this, 'set_default_order' ) );
		add_filter( 'the_content', array( $this, 'the_content' ) );
		/**
		 * projects tab on main page
		 */
		add_filter( 'iworks/my-car/project/get_random', array( $this, 'get_random' ), 10, 2 );
		add_filter( 'iworks/my-car/project/get_list', array( $this, 'get_list' ) );
		add_filter( 'iworks/my-car/project/partner/get_types', array( $this, 'filter_get_partners_types' ) );
		/**
		 * shortcode
		 */
		add_shortcode( 'iworks-projects', array( $this, 'shortcode_default_list' ) );
	}

	/**
	 * class settings
	 *
	 * @since 1.0.0
	 */
	public function action_init_settings() {
		$this->load_plugin_admin_assets                                = true;
		$this->meta_boxes[ $this->post_type_name[ $this->post_type ] ] = array(
			'data'  => array(
				'title'  => __( 'Project Data', 'my-car' ),
				'fields' => array(
					'_project_date_start'     => array(
						'label' => __( 'Project start date', 'my-car' ),
						'type'  => 'date',
						'name'  => 'date_start',
					),
					'_project_date_end'       => array(
						'label' => __( 'Project end date', 'my-car' ),
						'type'  => 'date',
						'name'  => 'date_end',
					),
					'_realization_date_start' => array(
						'label' => __( 'Realization start date', 'my-car' ),
						'type'  => 'date',
						'name'  => 'realization_date_start',
					),
					'_realization_date_end'   => array(
						'label' => __( 'Realization end date', 'my-car' ),
						'type'  => 'date',
						'name'  => 'realization_date_end',
					),
					'_project_cost'           => array(
						'label'    => __( 'Project cost', 'my-car' ),
						'type'     => 'number',
						'sanitize' => 'floatval',
						'sufix'    => __( 'PLN', 'my-car' ),
						'name'     => 'cost',
					),
					'_project_funding'        => array(
						'label'    => __( 'Project amount of funding', 'my-car' ),
						'type'     => 'number',
						'sanitize' => 'floatval',
						'sufix'    => __( 'PLN', 'my-car' ),
						'name'     => 'funding',
					),
					'_project_currency'       => array(
						'label'    => __( 'Project currency', 'my-car' ),
						'type'     => 'text',
						'sanitize' => 'esc_html',
						'hide'     => true,
						'name'     => 'currency',
					),
					'_project_url'            => array(
						'label'    => __( 'Project URL', 'my-car' ),
						'type'     => 'url',
						'sanitize' => 'esc_url',
						'name'     => 'url',
					),
				),
			),
			'media' => array(
				'title'  => __( 'Project Media', 'my-car' ),
				'fields' => array(
					array(
						'name'  => 'icon',
						'type'  => 'image',
						'label' => esc_html__( 'Icon', 'my-car' ),
					),
				),
			),
		);
		/**
		 * Partners types
		 *
		 * @since 1.0.0
		 */
		$this->partners_types = array(
			'lider'         => __( 'Liders', 'my-car' ),
			'scientific'    => __( 'Scientific Partners', 'my-car' ),
			'business'      => __( 'Business Partners', 'my-car' ),
			'partner'       => __( 'Partners', 'my-car' ),
			'subcontractor' => __( 'Subcontractors', 'my-car' ),
		);
	}

	/**
	 * Register taxonomy
	 *
	 * @since 1.0.0
	 */
	public function action_init_register_taxonomy() {}

	/**
	 * Get random projects
	 *
	 * @since 1.0.0
	 */
	public function get_random( $content, $posts_per_page ) {
		$args      = array(
			'orderby'        => 'rand',
			'posts_per_page' => max( 1, intval( $posts_per_page ) ),
			'post_status'    => 'publish',
			'post_type'      => $this->post_type_name[ $this->post_type ],
		);
		$the_query = new WP_Query( $args );
		$content  .= sprintf( '<span class="section-title">%s</span>', esc_html__( 'Projects', 'my-car' ) );
		if ( $the_query->have_posts() ) {
			ob_start();
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				get_template_part( 'template-parts/single-content', 'Project' );
			}
			$content .= ob_get_contents();
			ob_end_clean();
		}
		return $content;
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
		if ( $this->post_type_name[ $this->post_type ] !== $query->get( 'post_type' ) ) {
			return;
		}
		$query->set( 'meta_key', $this->get_post_meta_name( 'date_start', 'data' ) );
		$query->set(
			'orderby',
			array(
				'meta_value' => 'DESC',
				'title'      => 'ASC',
			)
		);
	}

	/**
	 * Get list
	 *
	 * @since 1.0.0
	 */
	public function get_list( $content ) {
		$args      = array(
			'nopaging'    => true,
			'post_status' => 'publish',
			'post_type'   => $this->post_type_name[ $this->post_type ],
		);
		$the_query = new WP_Query( $args );
		if ( $the_query->have_posts() ) {
			$args['wp_doing_ajax'] = wp_doing_ajax();
			ob_start();
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				get_template_part( 'template-parts/single-content', 'Project', $args );
			}
			$content .= ob_get_contents();
			ob_end_clean();
		}
		$url = get_post_type_archive_link( $this->post_type_name[ $this->post_type ] );
		if ( $url ) {
			$content .= sprintf(
				'<p class="more %s"><a href="%s" class="button">%s</a></p>',
				esc_attr( $this->post_type_name[ $this->post_type ] ),
				$url,
				esc_html__( 'Browse all projects', 'my-car' )
			);
		}
		return $content;
	}

	/**
	 * get content
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
		 * fields
		 */
		$show = false;
		foreach ( $this->fields as $key => $one ) {
			$value = get_post_meta( $post_ID, $key, true );
			if ( empty( $value ) ) {
				continue;
			}
			if ( isset( $one['type'] ) && 'url' === $one['type'] ) {
				$value = sprintf(
					'<a href="%1$s" target="_blank" class="external" title="%2$s">%1$s</a>',
					esc_url( $value ),
					esc_attr__( 'Opens in a new window', 'my-car' )
				);
			} elseif ( isset( $one['sanitize'] ) ) {
				$value = $one['sanitize']( $value );
			} else {
				$value = esc_html( $value );
			}
			$this->fields[ $key ]['value'] = $value;
			$show                          = true;
		}
		if ( $show ) {
			$args = array(
				'fields' => $this->fields,
			);
			ob_start();
			get_template_part( 'template-parts/project/part', 'data', $args );
			$c .= ob_get_contents();
			ob_end_clean();
		}
		/**
		 * partners
		 */
		ob_start();
		get_template_part( 'template-parts/project/part', 'partners' );
		$c .= ob_get_contents();
		ob_end_clean();
		/**
		 * Content
		 */
		$c .= $content;
		/**
		 * media
		 */
		$c .= $this->get_media_html( $post_ID );
		return apply_filters( 'my_car_project_content', $c );
	}

	/**
	 * Register Custom Post Type
	 *
	 * @since 1.0.0
	 */
	public function action_init_register_post_type() {
		$labels = array(
			'name'                  => _x( 'Projects', 'Post Type General Name', 'my-car' ),
			'singular_name'         => _x( 'Project', 'Post Type Singular Name', 'my-car' ),
			'menu_name'             => __( 'Projects', 'my-car' ),
			'name_admin_bar'        => __( 'Projects', 'my-car' ),
			'archives'              => __( 'Projects', 'my-car' ),
			'all_items'             => __( 'Projects', 'my-car' ),
			'add_new_item'          => __( 'Add New project', 'my-car' ),
			'add_new'               => __( 'Add New', 'my-car' ),
			'new_item'              => __( 'New project', 'my-car' ),
			'edit_item'             => __( 'Edit project', 'my-car' ),
			'update_item'           => __( 'Update project', 'my-car' ),
			'view_item'             => __( 'View project', 'my-car' ),
			'view_items'            => __( 'View project', 'my-car' ),
			'search_items'          => __( 'Search project', 'my-car' ),
			'not_found'             => __( 'Not found', 'my-car' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'my-car' ),
			'items_list'            => __( 'Project list', 'my-car' ),
			'items_list_navigation' => __( 'Project list navigation', 'my-car' ),
			'filter_items_list'     => __( 'Filter items list', 'my-car' ),
		);
		$args   = array(
			'can_export'          => true,
			'capability_type'     => 'page',
			'description'         => __( 'Project', 'my-car' ),
			'exclude_from_search' => true,
			'has_archive'         => true,
			'hierarchical'        => false,
			'label'               => __( 'Projects', 'my-car' ),
			'labels'              => $labels,
			'public'              => true,
			'show_in_admin_bar'   => true,
			'show_in_menu'        => 'edit.php',
			'show_in_nav_menus'   => true,
			'show_ui'             => true,
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
			'rewrite'             => array(
				'slug' => _x( 'project', 'slug for single project', 'my-car' ),
			),
		);
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			unset( $args['rewrite'] );
		}
		$this->register_post_type( $this->post_type, $args );
	}

	/**
	 * HTML for partners
	 *
	 * @since 1.0.0
	 */
	private function html_partners( $post, $type ) {
		printf(
			'<div id="opi-partner-%1$s-container" class="opi-partner-container opi-partner-%1$s-container" data-partner="%1$s" aria-hidden="true">',
			esc_attr( $type )
		);
		echo '<p>';
		printf(
			'<button type="button" class="button button-add-partner">%s</button>',
			esc_html__( 'Add a partner', 'my-car' )
		);
		echo '</p>';
		printf(
			'<div id="opi-partner-%s-container-rows">',
			esc_attr( $type )
		);
		$value   = get_post_meta( $post->ID, $this->option_name_partners, true );
		$parners = isset( $value[ $type ] ) ? $value[ $type ] : array();
		foreach ( $parners as $caption ) {
			$this->partner_row( array( 'caption' => $caption ), $type );
		}
		echo '</div>';
		echo '</div>';
		printf(
			'<script type="text/html" id="tmpl-opi-partner-%s-row">',
			esc_attr( $type )
		);
		$this->partner_row( array(), $type );
		echo '</script>';
	}

	public function html_post_partners_lider( $post ) {
		$this->html_partners( $post, 'lider' );
	}

	public function html_post_partners_scientific( $post ) {
		$this->html_partners( $post, 'scientific' );
	}

	public function html_post_partners_business( $post ) {
		$this->html_partners( $post, 'business' );
	}

	public function html_post_partners_partner( $post ) {
		$this->html_partners( $post, 'partner' );
	}

	public function html_post_partners_subcontractor( $post ) {
		$this->html_partners( $post, 'subcontractor' );
	}

	/**
	 * HTML for metabox
	 *
	 * @since 1.0.0
	 */
	public function html_data( $post ) {
		wp_nonce_field( __CLASS__, '_project_nonce' );
		foreach ( $this->fields as $key => $one ) {
			$value = get_post_meta( $post->ID, $key, true );
			if ( isset( $one['sanitize'] ) ) {
				$value = $one['sanitize']( $value );
			}
			$method = sprintf(
				'input_%s',
				$one['type']
			);
			if ( method_exists( $this, $method ) ) {
				echo $this->$method( $key, $value, $one['label'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_* methods escape their own output
			}
		}
	}

	/**
	 * Get source for media
	 *
	 * @since 1.0.0
	 */
	private function get_src( $post_id ) {
		$src = null;
		if ( isset( $this->images[ $post_id ] ) ) {
			$src = $this->images[ $post_id ];
		}
		$attachment_id = get_post_meta( $post_id, $this->option_name_media, true );
		if ( ! empty( $attachment_id ) ) {
			$src = wp_get_attachment_url( $attachment_id );
		}
		if ( empty( $src ) ) {
			return $src;
		}
		$this->images[ $post_id ] = $src;
		return $src;
	}


	/**
	 * Partner row helper
	 *
	 * @since 1.0.0
	 */
	protected function partner_row( $data = array(), $type = '' ) {
		$data = wp_parse_args(
			$data,
			array(
				'caption' => '{{{data.caption}}}',
			)
		);
		echo '<div class="opi-partner-row">';
		echo '<span class="dashicons dashicons-move"></span>';
		printf(
			'<input type="text" class="text-wide" name="%s[%s][]" value="%s" />',
			esc_attr( $this->option_name_partners ),
			esc_attr( $type ),
			esc_attr( $data['caption'] )
		);
		printf(
			'<button class="trash" type="button" aria-label="%s"><span class="dashicons dashicons-trash"></span></button>',
			esc_attr__( 'Remove Partner', 'my-car' )
		);
		echo '</div>';
	}

	/**
	 * get partners types
	 *
	 * @since 1.0.0
	 */
	public function filter_get_partners_types( $types ) {
		return $this->partners_types;
	}
}
