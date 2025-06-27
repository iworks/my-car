<?php
/**
 * iWorks My Car Base Class
 *
 * This is the base class for the My Car, providing
 * common functionality and properties for the plugin.
 *
 * @package    iWorks
 * @subpackage My Car
 * @author     Marcin Pietrzak <marcin@iworks.pl>
 * @copyright  2025-PLUGIN_TILL_YEAR Marcin Pietrzak
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0
 * @version    1.0.0
 */
defined( 'ABSPATH' ) || exit;

/**
 * Prevent multiple class definitions
 */
if ( class_exists( 'iworks_my_car_base' ) ) {
	return;
}

/**
 * iWorks My Car Base Class
 *
 * This class provides the foundation for the My Car,
 * offering essential properties and methods used throughout the plugin.
 *
 * @since 1.0.0
 */
class iworks_my_car_base {

	/**
	 * Developer mode flag
	 *
	 * @since 1.0.0
	 * @var bool $dev Whether developer mode is enabled
	 */
	protected $dev;

	/**
	 * Meta data prefix
	 *
	 * @since 1.0.0
	 * @var string $meta_prefix Prefix for meta data keys
	 */
	protected $meta_prefix = '_iw';

	/**
	 * Base directory path
	 *
	 * @since 1.0.0
	 * @var string $base Absolute path to the plugin directory
	 */
	protected $base;

	/**
	 * Directory path
	 *
	 * @since 1.0.0
	 * @var string $dir Plugin directory path
	 */
	protected $dir;

	/**
	 * URL path
	 *
	 * @since 1.0.0
	 * @var string $url Plugin URL path
	 */
	protected $url;

	/**
	 * Plugin file name
	 *
	 * @since 1.0.0
	 * @var string $plugin_file Name of the plugin file
	 */
	protected $plugin_file;

	/**
	 * Plugin file path
	 *
	 * @since 1.0.0
	 * @var string $plugin_file_path Full path to the plugin file
	 */
	protected $plugin_file_path;

	/**
	 * Plugin capability
	 *
	 * @since 1.0.0
	 * @var string $capability Required capability for plugin settings
	 */
	private string $capability = 'manage_options';

	/**
	 * Plugin version
	 *
	 * @since 1.0.0
	 * @var string $version Current plugin version
	 */
	protected string $version = 'PLUGIN_VERSION';

	/**
	 * Includes directory
	 *
	 * @since 1.0.0
	 * @var string $includes_directory Path to plugin includes directory
	 */
	protected string $includes_directory;

	/**
	 * Debug mode flag
	 *
	 * @since 1.0.0
	 * @var bool $debug Whether debug mode is enabled
	 */
	protected $debug = false;

	/**
	 * End of line character
	 *
	 * @since 1.0.0
	 * @var string $eol End of line character for output
	 */
	protected string $eol = '';

	/**
	 * iWorks Options Class Object
	 *
	 * @since 1.0.0
	 * @var iworks_options $options Instance of the options class
	 */
	protected $options;

	/**
	 * Constructor for the base class
	 *
	 * Initializes all necessary properties and sets up the plugin environment
	 * including debug mode, directories, URLs, and WordPress hooks
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		/**
		 * static settings
		 */
		$this->debug = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( defined( 'IWORKS_DEV_MODE' ) && IWORKS_DEV_MODE );
		/**
		 * use minimized scripts if not debug
		 */
		$this->dev = $this->debug ? '' : '.min';
		/**
		 * add EOL if debug
		 */
		$this->eol = $this->debug ? PHP_EOL : '';
		/**
		 * directories and urls
		 */
		$this->base = __DIR__;
		$this->dir  = basename( dirname( $this->base, 2 ) );
		$this->url  = plugins_url( $this->dir );
		/**
		 * plugin ID
		 */
		$this->plugin_file_path = $this->base . '/my-car.php';
		$this->plugin_file      = plugin_basename( $this->plugin_file_path );
		/**
		 * plugin includes directory
		 */
		$this->includes_directory = __DIR__ . '/my-car';
		/**
		 * WordPress Hooks
		 */
	}

	/**
	 * Get the plugin version
	 *
	 * Returns either the current version or a timestamp/file hash in dev mode
	 *
	 * @param string|null $file Optional file path for hash generation
	 * @return string Version string or timestamp/hash
	 * @since 1.0.0
	 */
	public function get_version( $file = null ) {
		if ( defined( 'IWORKS_DEV_MODE' ) && IWORKS_DEV_MODE ) {
			if ( null != $file ) {
				$file = dirname( $this->base ) . $file;
				if ( is_file( $file ) ) {
					return md5_file( $file );
				}
			}
			return time();
		}
		return $this->version;
	}

	/**
	 * Generate a meta key name
	 *
	 * Creates a properly formatted meta key name using the prefix
	 *
	 * @param string $name Base name for the meta key
	 * @return string Formatted meta key name
	 * @since 1.0.0
	 */
	protected function get_meta_name( $name ) {
		return sprintf( '%s_%s', $this->meta_prefix, sanitize_title( $name ) );
	}

	/**
	 * Get the post type
	 *
	 * Returns the current post type being handled
	 *
	 * @return string Post type name
	 * @since 1.0.0
	 */
	public function get_post_type() {
		return $this->post_type;
	}

	/**
	 * Get the plugin capability
	 *
	 * Returns the required capability for plugin settings
	 *
	 * @return string Capability name
	 * @since 1.0.0
	 */
	public function get_this_capability() {
		return $this->capability;
	}

	/**
	 * Generate a slug name
	 *
	 * Creates a URL-safe slug from the given name
	 *
	 * @param string $name Input name to convert
	 * @return string URL-safe slug
	 * @since 1.0.0
	 */
	private function slug_name( $name ) {
		return preg_replace( '/[_ ]+/', '-', strtolower( __CLASS__ . '_' . $name ) );
	}

	/**
	 * Get post meta value
	 *
	 * Retrieves a post meta value using the plugin's meta prefix
	 *
	 * @param int $post_id Post ID to get meta for
	 * @param string $meta_key Meta key name
	 * @return mixed Meta value
	 * @since 1.0.0
	 */
	public function get_post_meta( $post_id, $meta_key ) {
		return get_post_meta( $post_id, $this->get_meta_name( $meta_key ), true );
	}

	/**
	 * Print table body for post meta fields
	 *
	 * Generates an HTML table with form inputs for post meta fields
	 *
	 * @param int $post_id Post ID to display meta for
	 * @param array $fields Array of field definitions
	 * @return void Outputs HTML directly
	 * @since 1.0.0
	 */
	protected function print_table_body( $post_id, $fields ) {
		echo '<table class="widefat striped"><tbody>';
		foreach ( $fields as $name => $data ) {
			$key   = $this->get_meta_name( $name );
			$value = $this->get_post_meta( $post_id, $name );
			/**
			 * extra
			 */
			$extra = isset( $data['placeholder'] ) ? sprintf( ' placeholder="%s" ', esc_attr( $data['placeholder'] ) ) : '';
			foreach ( array( 'placeholder', 'style', 'class', 'id' ) as $extra_key ) {
				if ( isset( $data[ $extra_key ] ) ) {
					$extra .= sprintf( ' min="%d" ', esc_attr( $data[ $extra_key ] ) );
				}
			}
			/**
			 * start row
			 */
			echo '<tr>';
			printf( '<th scope="row" style="width: 130px">%s</th>', $data['title'] );
			echo '<td>';
			switch ( $data['type'] ) {
				case 'number':
					foreach ( array( 'min', 'max', 'step' ) as $extra_key ) {
						if ( isset( $data[ $extra_key ] ) ) {
							$extra .= sprintf( ' min="%d" ', intval( $data[ $extra_key ] ) );
						}
					}
					printf(
						'<input type="number" name="%s" value="%d" %s />',
						esc_attr( $key ),
						intval( $value ),
						// data string escaped few lines above
						$extra
					);
					break;
				case 'date':
					$date = intval( $this->get_post_meta( $post_id, $name ) );
					if ( empty( $date ) ) {
						$date = strtotime( 'now' );
					}
					printf(
						'<input type="text" class="datepicker" name="%s" value="%s" />',
						esc_attr( $this->get_meta_name( $name ) ),
						esc_attr( $date )
					);
					break;
			}
			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	protected function get_module_file( $filename, $vendor = 'iworks' ) {
		return realpath(
			sprintf(
				'%s/%s/%s/%s.php',
				$this->base,
				$vendor,
				$this->dir,
				$filename
			)
		);
	}

	protected function html_title( $text ) {
		printf( '<h1 class="wp-heading-inline">%s</h1>', esc_html( $text ) );
	}

	/**
	 * check option object
	 *
	 * @since 1.0.0
	 */
	protected function check_option_object() {
		if ( is_a( $this->options, 'iworks_options' ) ) {
			return;
		}
		$this->options = iworks_my_car_get_options();
	}

	/**
	 * Get plugin stub metadata.
	 *
	 * Returns an array containing the plugin's metadata including:
	 * - Publication date
	 * - Current version
	 * - GitHub repository URL
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 *     Plugin metadata.
	 *
	 *     @type string $published The publication date in 'YYYY-MM-DD' format.
	 *     @type string $version   The current version of the plugin.
	 *     @type string $github    The GitHub repository URL.
	 * }
	 */
	public function get_stub_data() {
		return array(
			'published' => '2025-05-21',
			'version'   => '2.0.0',
			'github'    => 'https://github.com/iworks/my-car',
		);
	}
}
