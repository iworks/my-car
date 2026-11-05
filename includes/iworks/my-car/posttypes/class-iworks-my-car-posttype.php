<?php
/**

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

require_once dirname( __DIR__, 2 ) . '/class-iworks-my-car-base.php';

abstract class iworks_my_car_posttype extends iworks_my_car_base {

	/**
	 * Post type names array
	 *
	 * @since 1.0.0
	 * @var array $post_type_name Array of post type identifiers
	 */
	protected array $post_type_name = array(
		'car'            => 'iworks_car',
		'faq'            => 'iworks_faq',
		'featured'       => 'iworks_featured',
		'hero'           => 'iworks_hero',
		'opinion'        => 'iworks_opinion',
		'page'           => 'page', // for WordPress pages
		'person'         => 'iworks_person',
		'post'           => 'post', // for WordPress posts
		'project'        => 'iworks_project',
		'publication'    => 'iworks_publication',
		'service_record' => 'iworks_car_service',
		'testimonial'    => 'iworks_testimonial',
	);

	/**
	 * Taxonomy names array
	 *
	 * @since 1.0.0
	 * @var array $taxonomy_name Array of taxonomy identifiers
	 */
	protected array $taxonomy_name = array(
		'car_body_type'      => 'iworks_car_body_type',
		'car_brand'          => 'iworks_car_brand',
		'car_engine_type'    => 'iworks_car_engine_type',
		'faq_group'          => 'iworks_faq_group',
		'person_role'        => 'iworks_person_role',
		'service_type'       => 'iworks_car_service_type',
	);


	/**
	 * Meta boxes configuration
	 *
	 * @since 1.0.0
	 * @var array $meta_boxes Array of meta box configurations
	 */
	protected array $meta_boxes = array();

	/**
	 * Media Option Name
	 *
	 * @since 1.0.0
	 */
	protected string $option_name_media = 'media';

	/**
	 * Load admin assets
	 *
	 * @since 1.0.0
	 */
	protected bool $load_plugin_admin_assets = false;

	/**
	 * Constructor
	 *
	 * Sets up WordPress hooks for post type and taxonomy registration.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		/**
		 * set option name media
		 */
		$this->option_name_media = $this->get_post_meta_name( $this->option_name_media );
		/**
		 * WordPress Hooks
		 */
		add_action( 'init', array( $this, 'action_init_settings' ), 0 );
		add_action( 'init', array( $this, 'action_init_register_post_type' ), 1 );
		add_action( 'init', array( $this, 'action_init_register_taxonomy' ), 2 );
		add_action( 'load-post-new.php', array( $this, 'action_load_admin_maybe_enqueue_assets' ) );
		add_action( 'load-post.php', array( $this, 'action_load_admin_maybe_enqueue_assets' ) );
		add_action( 'load-edit.php', array( $this, 'action_load_admin_maybe_enqueue_assets' ) );
		add_action( 'save_post', array( $this, 'action_save_post_meta' ), 10, 3 );
		/**
		 * own hooks
		 */
		add_filter( 'iworks/my-car/wp_localize_script/admin', array( $this, 'filter_wp_localize_script_admin' ) );
		/**
		 * My Car Hooks
		 */
		/**
		 * Settings
		 */
		$this->post_type_name = apply_filters(
			'iworks/my-car/post_type_name',
			$this->post_type_name
		);
		$this->taxonomy_name  = apply_filters(
			'iworks/my-car/taxonomy_name',
			$this->taxonomy_name
		);
	}

	abstract public function action_init_register_post_type();
	abstract public function action_init_register_taxonomy();
	abstract public function action_init_settings();

	/**
	 * handle load meta boxes
	 *
	 * @since 1.0.0
	 */
	protected function load_meta_boxes( $post_type ) {
		add_action( 'add_meta_boxes_' . $this->post_type_name[ $post_type ], array( $this, 'add_meta_boxes' ) );
	}

	/**
	 * Handle custom columns for a post type.
	 *
	 * @since 1.0.0
	 */
	protected function handle_custom_columns( $post_type ) {
		add_filter( 'manage_' . $this->post_type_name[ $post_type ] . '_posts_columns', array( $this, 'filter_manage_post_type_posts_columns' ) );
		add_action( 'manage_' . $this->post_type_name[ $post_type ] . '_posts_custom_column', array( $this, 'action_manage_post_type_posts_custom_column' ), 10, 2 );
		add_filter( 'manage_edit-' . $this->post_type_name[ $post_type ] . '_sortable_columns', array( $this, 'filter_manage_sortable_columns' ) );
	}

	/**
	 * Filter the wp_localize_script admin.
	 *
	 * @since 1.0.0
	 */
	public function filter_wp_localize_script_admin( $translation_array ) {
		$translation_array['post_type_name'] = $this->post_type_name;
		$translation_array['taxonomy_name']  = $this->taxonomy_name;
		return $translation_array;
	}

	/**
	 * Register the Post Type Name in the Class Parent Class.
	 *
	 * @since 1.0.0
	 */
	protected function register_class_custom_posttype_name( $posttype_name, $prefix = '', $sufix = '' ) {
		if ( ! empty( $prefix ) ) {
			$prefix = sprintf( '%s_', $prefix );
		}
		if ( ! empty( $sufix ) ) {
			$sufix = sprintf( '_%s', $sufix );
		}
		$this->post_type_name[ $posttype_name ] = $prefix . $this->post_type_name[ $posttype_name ] . $sufix;
	}

	/**
	 * Register the Taxonomy Name in the Class Parent Class.
	 *
	 * @since 1.0.0
	 */
	protected function register_class_custom_taxonomy_name( $taxonomy_name, $prefix = '', $sufix = '' ) {
		if ( ! empty( $prefix ) ) {
			$prefix = sprintf( '%s_', $prefix );
		}
		if ( ! empty( $sufix ) ) {
			$sufix = sprintf( '_%s', $sufix );
		}
		$this->taxonomy_name[ $taxonomy_name ] = $prefix . $this->taxonomy_name[ $taxonomy_name ] . $sufix;
	}

	/**
	 * Save entry action
	 *
	 * @since 1.0.0
	 */
	public function action_save_post( $post_id, $post, $update ) {
		$this->save_meta( $post_id, $post, $update, $this->post_type_name );
	}

	/**
	 * Get select array for dropdowns
	 *
	 * @since 1.0.0
	 */
	protected function get_select_array( $post_type, $atts = array() ) {
		$args      = wp_parse_args(
			$atts,
			array(
				'order'          => 'ASC',
				'orderby'        => 'title',
				'posts_per_page' => -1,
				'post_type'      => $post_type,
				'post_status'    => 'publish',
			)
		);
		$list[0]   = __( '&mdash; Select &mdash;', 'my-car' );
		$the_query = new WP_Query( $args );
		foreach ( $the_query->posts as $post ) {
			$list[ $post->ID ] = $post->post_title;
		}
		return $list;
	}

	/**
	 * Add meta boxes to post editor
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes( $post ) {
		/**
		 * check available fields
		 */
		if ( empty( $this->meta_boxes ) ) {
			return;
		}
		foreach ( $this->meta_boxes as $meta_box_key => $meta_box_data ) {
			foreach ( $meta_box_data as $id => $data ) {
				add_meta_box(
					$id,
					$data['title'],
					array( $this, 'render_meta_box_content' ),
					$post->post_type,
					isset( $data['context'] ) ? $data['context'] : 'advanced',
					isset( $data['priority'] ) ? $data['priority'] : 'default',
					array(
						'posttype_name' => $post->post_type,
						'id'            => $id,
					)
				);
			}
		}
	}

	/**
	 * Meta Field: main render method
	 *
	 * @since 1.0.0
	 */
	protected function render( $post, $one ) {
		$one     = wp_parse_args(
			$one,
			array(
				'type' => 'text',
			)
		);
		$method  = sprintf( 'render_meta_%s', $one['type'] );
		$classes = array(
			'iworks-field',
			sprintf( 'iworks-field-%s', $one['type'] ),
		);
		if ( isset( $one['classes'] ) ) {
			$classes = wp_parse_args(
				$one['classes'],
				$classes
			);
		}
		printf( '<div class="%s">', esc_attr( implode( ' ', $classes ) ) );
		if ( method_exists( $this, $method ) ) {
			$this->$method( $post, $one );
		} else {
			echo '<p>';
			echo '<label>';
			if ( isset( $one['label'] ) ) {
				echo esc_html( $one['label'] );
				echo '<br />';
			}
			$classes = array();
			switch ( $one['type'] ) {
				case 'text':
				case 'url':
					if ( ! isset( $one['large-text'] ) || $one['large-text'] ) {
						$classes[] = 'large-text';
					}
					break;
			}
			printf(
				'<input type="%s" value="%s" name="%s" class="%s"%s />',
				esc_attr( $one['type'] ),
				esc_attr( $one['meta']['value'] ),
				esc_attr( $one['meta']['key'] ),
				esc_attr( implode( ' ', $classes ) ),
				isset( $one['maxlength'] ) && is_integer( $one['maxlength'] ) ? esc_attr( sprintf( ' maxlength="%d"', $one['maxlength'] ) ) : ''
			);
			echo '</label>';
			if ( isset( $one['description'] ) ) {
				printf( '<span class="description">%s</span>', esc_html( $one['description'] ) );
			}
			echo '</p>';
		}
		echo '</div>';
	}

	/**
	 * Meta Field: main render method: image
	 *
	 * @since 1.0.0
	 */
	private function render_meta_image( $post, $one ) {
		if ( is_admin() ) {
			wp_enqueue_media();
		}
		if ( isset( $one['label'] ) ) {
			echo '<p>';
			echo esc_html( $one['label'] );
			echo '</p>';
		}
		echo '<div class="iworks-field-image-row">';
		/**
		 * image
		 */
		$src = wp_get_attachment_image_src( $one['meta']['value'] );
		if ( $src ) {
			$src = $src[0];
		} else {
			$src = '';
		}
		printf(
			'<img src="%s" alt="" style="%s%sclear:right;display:block;margin-bottom:10px;" />',
			esc_attr( $src ? $src : '' ),
			array_key_exists( 'max-width', $one ) && is_integer( $one['max-width'] ) ? sprintf( 'max-width: %dpx;', $one['max-width'] ) : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- int only
			array_key_exists( 'max-height', $one ) && is_integer( $one['max-height'] ) ? sprintf( 'max-height: %dpx;', $one['max-height'] ) : '' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- int only
		);
		printf(
			'<input class="attachment-id" type="hidden" name="%s" value="%s">',
			esc_attr( $one['meta']['key'] ),
			esc_attr( $one['meta']['value'] )
		);
		echo '<div class="iworks-field-image-actions">';
		printf(
			'<input type="button" class="button button-upload" value="%s" />',
			esc_attr__( 'Select Image', 'my-car' ),
		);
		printf(
			'<input type="button" value="%s" class="button button-delete%s">',
			esc_attr__( 'Delete image', 'my-car' ),
			empty( $value ) ? ' hidden' : ''
		);
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Meta Field: main render method: checkbox
	 *
	 * @since 1.0.0
	 */
	private function render_meta_checkbox( $post, $one ) {
		echo '<p>';
		echo '<label>';
		$classes = array();
		printf(
			'<input type="%s" value="1" name="%s" class="%s" %s />',
			esc_attr( $one['type'] ),
			esc_attr( $one['meta']['key'] ),
			esc_attr( implode( ' ', $classes ) ),
			( 'yes' === $one['meta']['value'] ) ? 'checked="checked"' : ''
		);
		if ( isset( $one['label'] ) ) {
			echo ' ';
			echo esc_html( $one['label'] );
		}
		echo '</label>';
		if ( isset( $one['description'] ) ) {
			printf( '<span class="description">%s</span>', esc_html( $one['description'] ) );
		}
		echo '</p>';
	}

	/**
	 * Meta Field: main render method: select
	 *
	 * @since 1.0.0
	 */
	private function render_meta_select( $post, $one ) {
		$value = get_post_meta( $post->ID, $one['meta']['key'], true );
		echo '<p>';
		echo '<label>';
		if ( isset( $one['label'] ) ) {
			echo esc_html( $one['label'] );
			echo '<br />';
		}
		printf(
			'<select name="%s">',
			esc_attr( $one['meta']['key'] )
		);
		printf( '<option value="">%s</option>', esc_html__( '&mdash; Select &mdash;', 'my-car' ) );
		foreach ( $one['options'] as $option_value => $option_name ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $option_value ),
				selected( $option_value, $value, false ),
				esc_html( $option_name )
			);
		}
		echo '</select>';
		echo '</label>';
		echo '</p>';
	}

	/**
	 * Meta Field: main render method: radio
	 *
	 * Renders a radio field for meta boxes.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Current post object.
	 * @param array   $one  Field configuration.
	 */
	private function render_meta_radio( $post, $one ) {
		$value = get_post_meta( $post->ID, $one['name'], true );
		echo '<p>';
		if ( isset( $one['label'] ) ) {
			echo '<label>';
			echo esc_html( $one['label'] );
			echo '<br />';
			echo '</label>';
		}
		echo '</p>';
		printf(
			'<ul name="%s">',
			esc_attr( $one['name'] )
		);
		foreach ( $one['options'] as $option_value => $option_name ) {
			printf(
				'<li><label><input name="%s" type="radio" value="%s" %s>%s</label></li>',
				esc_attr( $one['name'] ),
				esc_attr( $option_value ),
				checked( $option_value, $value, false ),
				esc_html( $option_name )
			);
		}
		echo '</ul>';
	}

	/**
	 * Setup post one field
	 *
	 * @since 1.0.0
	 */
	private function setup_post_one_field( $post_id, $field, $meta_box_id, $index ) {
		$field                = $this->set_field_defaults( $field, $index );
		$field['meta_box_id'] = $meta_box_id;
		$name                 = isset( $field['name'] ) ? $field['name'] : $this->meta_prefix . hash( 'crc32', serialize( $field ) );
		$post_meta_name       = $this->get_post_meta_name( $name, $meta_box_id );
		$field['meta']        = array(
			'key'   => $post_meta_name,
			'value' => get_post_meta(
				$post_id,
				$post_meta_name,
				$field['single']
			),
		);
		return apply_filters(
			'iworks/my-car/post/meta/field',
			$field,
			$post_id
		);
	}

	/**
	 * Render meta field
	 *
	 * Renders a meta field based on its type configuration.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Current post object.
	 * @param array   $one  Field configuration.
	 */
	public function render_meta_box_content( $post, $args ) {
		$posttype_name = $args['args']['posttype_name'];
		$meta_box_id   = $args['args']['id'];
		wp_nonce_field( $meta_box_id, $this->get_post_meta_name( $meta_box_id ) );
		foreach ( $this->meta_boxes[ $posttype_name ][ $meta_box_id ]['fields'] as $index => $one ) {
			$one = $this->setup_post_one_field( $post->ID, $one, $meta_box_id, $index );
			$this->render( $post, $one );
		}
	}

	/**
	 * Save meta data
	 *
	 * Saves meta data for a post when it's saved.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an update.
	 * @param string  $post_type Post type being saved.
	 */
	protected function save_meta( $post_id, $post, $update, $post_type ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( get_post_type( $post ) !== $post_type ) {
			return;
		}
		if ( ! isset( $this->meta_boxes[ $post_type ] ) ) {
			return;
		}
		foreach ( $this->meta_boxes[ $post_type ] as $id => $data ) {
			$nonce_name  = $this->get_post_meta_name( $id );
			$nonce_value = filter_input( INPUT_POST, $nonce_name );
			if ( ! wp_verify_nonce( $nonce_value, $id ) ) {
				return;
			}
			foreach ( $data['fields'] as $index => $field ) {
				$field = $this->set_field_defaults( $field, $index );
				$value = '';
				switch ( $field['type'] ) {
					case 'url':
						$value = filter_input( INPUT_POST, $field['name'], FILTER_SANITIZE_URL );
						break;
					case 'checkbox':
						$value = isset( $_POST[ $field['name'] ] ) ? 'yes' : 'no';
						break;
					default:
						$value = wp_kses_post( filter_input( INPUT_POST, $field['name'], FILTER_UNSAFE_RAW ) );
						break;
				}
				$this->update_meta( $post_id, $field['name'], $value );
			}
		}
	}

	/**
	 * Add menu order column to posts list
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns Columns to display.
	 * @return array
	 */
	public function filter_add_menu_order_column( $columns ) {
		$inserted = array(
			'menu_order' => __( 'Order', 'my-car' ),
		);
		$columns  = array_merge(
			array_slice( $columns, 0, 1, true ),
			$inserted,
			$columns
		);
		return $columns;
	}

	/**
	 * Display menu order value in posts list
	 *
	 * @since 1.0.0
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 */
	public function action_add_menu_order_value( $column, $post_id ) {
		switch ( $column ) {
			case 'menu_order':
				printf(
					'<span class="alignright">%d</span>',
					intval( get_post_field( $column, $post_id ) )
				);
				return;
		}
	}

	/**
	 * Get taxonomy name
	 *
	 * @since 1.0.0
	 */
	protected function get_taxonomy( $taxonomy_name ) {
		if ( ! isset( $this->taxonomy_name[ $taxonomy_name ] ) ) {
			$this->taxonomy_name = apply_filters(
				'iworks/my-car/taxonomy_name/array',
				$this->taxonomy_name
			);
		}
		if ( isset( $this->taxonomy_name[ $taxonomy_name ] ) ) {
			return $this->taxonomy_name[ $taxonomy_name ];
		}
		return new WP_Error( 'taxonomy', esc_html__( 'Selected Taxonomy dosn\'t exists.', 'my-car' ) );
	}

	/**
	 * Save post metadata when a post is saved.
	 *
	 * @param int  $post_id The post ID.
	 * @param post $post The post object.
	 * @param bool $update Whether this is an existing post being updated or not.
	 */
	public function action_save_post_meta( $post_id, $post, $update ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$post_type = get_post_type( $post_id );
		/**
		 *  If this isn't a correct post, don't update it.
		 */
		if ( $this->post_type_name[ $this->get_post_type() ] !== $post_type ) {
			return;
		}
		/**
		 * check available fields
		 */
		if (
			empty( $this->meta_boxes )
			|| ! is_array( $this->meta_boxes )
			|| ! isset( $this->meta_boxes[ $post_type ] )
		) {
			return;
		}
		/**
		 * check user permissions
		 */
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		/**
		 * save fields
		 */
		foreach ( $this->meta_boxes[ $post_type ] as $group => $meta_box_data ) {
			if ( ! isset( $meta_box_data['fields'] ) ) {
				continue;
			}
			if ( ! is_array( $meta_box_data['fields'] ) ) {
				continue;
			}
			/**
			 * check nonce
			 */
			$nonce = filter_input( INPUT_POST, $this->get_post_meta_name( $group ) );
			if ( ! wp_verify_nonce( $nonce, $group ) ) {
				continue;
			}
			/**
			 * handle fields
			 */
			foreach ( $meta_box_data['fields'] as $index => $field ) {
				$field = $this->set_field_defaults( $field, $index );
				$key   = $this->get_post_meta_name( $field['name'], $group );
				$value = filter_input( INPUT_POST, $key );
				switch ( $field['type'] ) {
					case 'checkbox':
						$value = $value ? 'yes' : 'no';
						break;
					case 'image':
						$value = intval( $value );
						break;
					case 'text':
						$value = wp_kses_post( $value );
						if ( isset( $field['maxlength'] ) && is_integer( $field['maxlength'] ) ) {
							$value = mb_substr( $value, 0, $field['maxlength'] );
						}
						break;
				}
				delete_post_meta( $post_id, $key );
				if ( $value ) {
					update_post_meta( $post_id, $key, $value );
				}
				do_action( 'iworks/my-car/postmeta/update', $post_id, $field, $key, $value );
			}
		}
	}

	protected function get_post_meta_name( $name, $group = '' ) {
		if ( ! empty( $group ) ) {
			return sprintf(
				'%s_%s_%s',
				esc_attr( $this->meta_prefix ),
				esc_attr( $group ),
				esc_attr( $name )
			);
		}
		return sprintf(
			'%s_%s',
			esc_attr( $this->meta_prefix ),
			esc_attr( $name )
		);
	}

	/**
	 * Enqueue plugin assets.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type The post type being edited.
	 */
	public function action_load_admin_maybe_enqueue_assets() {
		if ( ! $this->load_plugin_admin_assets ) {
			return;
		}
		global $typenow;
		if ( ! in_array( $typenow, $this->post_type_name, true ) ) {
			return;
		}
		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts_enqueue_assets' ), 118 );
	}

	/**
	 * Enqueue plugin assets.
	 *
	 * @since 1.0.0
	 */
	public function action_admin_enqueue_scripts_enqueue_assets() {
		$this->check_option_object();
		$name = $this->options->get_option_name( 'admin' );
		wp_enqueue_script( $name );
		wp_enqueue_style( $name );
	}

	/**
	 * Get icon SVG.
	 *
	 * @since 1.0.0
	 */
	protected function get_icon_svg( $icon ) {
		return sprintf(
			'data:image/svg+xml;base64,%s',
			base64_encode(
				file_get_contents(
					sprintf(
						'%s/assets/images/%s.svg',
						dirname( $this->base, 2 ),
						sanitize_file_name( $icon )
					)
				)
			)
		);
	}

	/**
	 * get filter name for feature in register_post_type() function.
	 *
	 * @since 1.0.0
	 */
	protected function get_register_post_filter_name( $post_type, $feature ) {
		return sprintf(
			'iworks/my-car/register-post-type/%s/%s',
			$this->post_type_name[ $post_type ],
			$feature
		);
	}

	/**
	 * get filter name for feature in register_taxonomy() function.
	 *
	 * @since 1.0.0
	 */
	protected function get_register_taxonomy_filter_name( $taxonomy, $feature ) {
		return sprintf(
			'iworks/my-car/register-taxonomy/%s/%s',
			$this->taxonomy_name[ $taxonomy ],
			$feature
		);
	}

	/**
	 * add columns to post type
	 *
	 * @since 1.0.0
	 */
	public function filter_manage_post_type_posts_columns( $posts_columns ) {
		$screen = get_current_screen();
		if ( ! is_a( $screen, 'WP_Screen' ) ) {
			return $posts_columns;
		}
		foreach ( $this->meta_boxes[ $screen->post_type ] as $group => $one ) {
			foreach ( $one['fields'] as $field_id => $field ) {
				if (
					isset( $field['column'] )
					&& preg_match( '/^(show|sortable)$/', $field['column'] )
				) {
					$column_name                   = $this->get_post_meta_name( $field['name'], $group );
					$posts_columns[ $column_name ] = $field['label'];
				}
			}
		}
		return $posts_columns;
	}

	public function action_manage_post_type_posts_custom_column( $column_name, $post_id ) {
		$screen = get_current_screen();
		if ( ! is_a( $screen, 'WP_Screen' ) ) {
			return;
		}
		foreach ( $this->meta_boxes[ $screen->post_type ] as $group => $one ) {
			foreach ( $one['fields'] as $field_id => $field ) {
				if (
					isset( $field['column'] )
					&& preg_match( '/^(show|sortable)$/', $field['column'] )
					&& $this->get_post_meta_name( $field['name'], $group ) === $column_name
				) {
					echo wp_kses_post(
						apply_filters(
							'iworks/my-car/post/meta',
							get_post_meta( $post_id, $column_name, true ),
							$group,
							$field
						)
					);
				}
			}
		}
	}

	/**
	 * add sortable column
	 *
	 * @since 1.0.0
	 */
	public function filter_manage_sortable_columns( $sortable_columns ) {
		$screen = get_current_screen();
		if ( ! is_a( $screen, 'WP_Screen' ) ) {
			return $posts_columns;
		}
		foreach ( $this->meta_boxes[ $screen->post_type ] as $group => $one ) {
			foreach ( $one['fields'] as $field_id => $field ) {
				if (
					isset( $field['column'] )
					&& preg_match( '/^sortable$/', $field['column'] )
				) {
					$column_name                      = $this->get_post_meta_name( $field['name'], $group );
					$sortable_columns[ $column_name ] = array(
						$column_name,
						false,
						$field['label'],
						$field['add_column']['description'],
						$field['add_column']['default_order'],
					);
				}
			}
		}
		return $sortable_columns;
	}

	/**
	 * Register post type
	 *
	 * @since 1.0.0
	 */
	protected function register_post_type( $post_type, $args ) {
		register_post_type(
			$this->post_type_name[ $post_type ],
			apply_filters(
				$this->get_register_post_filter_name( $post_type, 'arguments' ),
				$args,
				$post_type
			)
		);
	}

	/**
	 * Register taxonomy
	 *
	 * @since 1.0.0
	 */
	protected function register_taxonomy( $taxonomy, $post_types, $args ) {
		if ( is_string( $post_types ) ) {
			$post_types = array( $post_types );
		}
		$registered_post_types = array();
		foreach ( $post_types as $post_type ) {
			$registered_post_types[] = $this->post_type_name[ $post_type ];
		}
		register_taxonomy(
			$this->taxonomy_name[ $taxonomy ],
			apply_filters(
				$this->get_register_taxonomy_filter_name( $taxonomy, 'post_types' ),
				$registered_post_types,
				$taxonomy,
				$args
			),
			apply_filters(
				$this->get_register_taxonomy_filter_name( $taxonomy, 'arguments' ),
				$args,
				$taxonomy,
				$registered_post_types
			)
		);
	}

	protected function shortcode_list( $atts, $content = '' ) {
		$args                = wp_parse_args(
			$atts,
			array(
				'orderby'        => 'rand',
				'posts_per_page' => 5,
			)
		);
		$args['post_type']   = $this->post_type_name[ $this->post_type ];
		$args['post_status'] = 'publish';
		/**
		 * Query
		 */
		$wp_query_args = array(
			'post_type'      => $this->post_type_name[ $this->post_type ],
			'posts_per_page' => $args['posts_per_page'],
			'post_status'    => $args['post_status'],
			'orderby'        => $args['orderby'],
		);
		/**
		 * Tax query
		 */
		if ( isset( $args['tax_query'] ) ) {
			$wp_query_args['tax_query'] = $args['tax_query'];
		}
		/**
		 * Meta query
		 */
		if ( isset( $args['meta_query'] ) ) {
			$wp_query_args['meta_query'] = $args['meta_query'];
		}
		$the_query = new WP_Query( $wp_query_args );
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
		/**
		 * header
		 */
		$file = $this->get_module_file( 'header', $this->post_type );
		if ( $file ) {
			include_once $file;
		}
		/**
		 * loop
		 */
		$file = $this->get_module_file( 'one', $this->post_type );
		while ( $the_query->have_posts() ) {
			$the_query->the_post();
			if ( $file ) {
				include $file;
			}
		}
		/**
		 * Restore original Post Data
		 */
		wp_reset_postdata();
		/**
		 * footer
		 */
		$file = $this->get_module_file( 'footer', $this->post_type );
		if ( $file ) {
			include_once $file;
		}
		$content .= ob_get_clean();
		return apply_filters( 'iworks/my-car/' . $this->post_type . '/get_list', $content );
	}

	/**
	 * shortcode
	 *
	 * @since 1.0.0
	 */
	public function shortcode_default_list( $atts, $content = '' ) {
		return $this->shortcode_list( $atts, $content );
	}

	/**
	 * Set default values for a field
	 *
	 * @since 1.0.0
	 */
	private function set_field_defaults( $field, $index ) {
		return wp_parse_args(
			$field,
			array(
				'name'   => $index,
				'type'   => 'text',
				'single' => true,
			)
		);
	}

	/**
	 * Add column order
	 *
	 * @since 1.0.0
	 */
	protected function add_column_order( $post_type ) {
		$post_type_name = $this->post_type_name[ $post_type ];
		add_action( 'manage_' . $post_type_name . '_posts_custom_column', array( $this, 'action_add_menu_order_value' ), 10, 2 );
		add_filter( 'manage_' . $post_type_name . '_posts_columns', array( $this, 'filter_add_menu_order_column' ), 10, 2 );
	}
}
