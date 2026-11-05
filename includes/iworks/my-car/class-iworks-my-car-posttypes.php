<?php
/**
 * My Car Post Types Class
 *
 * This class handles the loading and management of custom post types
 * for the My Car.
 *
 * @package    My_Car
 * @subpackage My_Car
 * @author     Marcin Pietrzak <marcin@iworks.pl>
 * @copyright  2026-PLUGIN_TILL_YEAR Marcin Pietrzak
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0
 * @version    1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prevent multiple class definitions
 */
if ( class_exists( 'iworks_my_car_posttypes' ) ) {
	return;
}

/**
 * iWorks WordPress Plugin Post Types Class
 *
 * This class manages the loading and initialization of custom post types
 * for the My Car.
 *
 * @since 1.0.0
 */
class iworks_my_car_posttypes {
	/**
	 * Array of post type objects
	 *
	 * Stores instances of all loaded post type classes
	 *
	 * @since 1.0.0
	 * @var array $posttype_objects
	 */
	protected $posttype_objects = array();

	/**
	 * Constructor for the post types class
	 *
	 * Loads and initializes the post type classes, based on
	 * the filter settings.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( apply_filters( 'iworks/my-car/load/post-type/car', false ) ) {
			include_once __DIR__ . '/posttypes/class-iworks-my-car-posttype-car.php';
			$this->posttype_objects['car'] = new iworks_my_car_posttype_car();
		}
		if ( apply_filters( 'iworks/my-car/load/post-type/service-record', false ) ) {
			include_once __DIR__ . '/posttypes/class-iworks-my-car-posttype-service-record.php';
			$this->posttype_objects['service-record'] = new iworks_my_car_posttype_service_record();
		}
	}
}
