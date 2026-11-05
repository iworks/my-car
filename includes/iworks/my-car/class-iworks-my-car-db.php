<?php
/**
 * My Car - Database Class
 *
 * Handles all database operations for the My Car plugin.
 *
 * @package My_Car
 * @author  Marcin Pietrzak <marcin@iworks.pl>
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'iworks_my_car_db' ) ) {
	return;
}

require_once dirname( __DIR__ ) . '/class-iworks-my-car-base.php';

/**
 * Database functionality for My Car.
 *
 * This class handles all database-specific functionality including:
 * - Database table creation and updates
 * - Database version tracking
 * - Table registration with $wpdb
 * - Table cleanup on uninstall
 *
 * @since 1.0.0
 */
class iworks_my_car_db extends iworks_my_car_base {

	/**
	 * Option name for storing the database version.
	 *
	 * This is used to track the current version of the database schema
	 * and to determine if database updates are needed.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $_db_version = 'my_car_db_version';

	/**
	 * Array of database table names without prefix.
	 *
	 * Contains the names of all custom database tables used by the plugin.
	 * These names will be automatically prefixed with the WordPress table prefix.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	private array $table_names = array(
		'my_car_car_records',
	);
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		$this->register_tables();
		/**
		 * WordPress hooks.
		 */
		add_action( 'shutdown', array( $this, 'db_install' ) );
		/**
		 * My Car hooks.
		 */
		add_action( 'iworks/my-car/register_uninstall_hook', array( $this, 'drop_tables' ) );
		add_action( 'iworks/my-car/register_activation_hook', array( $this, 'db_install' ) );
	}

	/**
	 * Register plugin tables with $wpdb.
	 *
	 * This allows the tables to be referenced as $wpdb->{table_name} throughout the plugin.
	 * Tables are registered with the WordPress database prefix.
	 *
	 * @since 1.0.0
	 */
	private function register_tables() {
		global $wpdb;

		// Register each table.
		foreach ( $this->table_names as $key => $table_name ) {
			$wpdb->$table_name = $wpdb->prefix . $table_name;
		}
	}

	/**
	 * Create the database table if it doesn't exist.
	 *
	 * @since 1.0.0
	 */
	public function db_install() {
		/**
		 * Get current DB version.
		 */
		$db_version = intval( get_option( $this->_db_version ) );
		if ( 1 > $db_version ) {
			add_option( $this->_db_version, 0, '', 'no' );
		}
		/**
		 * Car-record join table.
		 */
		$version_to_update = 2;
		if ( $db_version < $version_to_update ) {
			global $wpdb;
			$table_name      = $wpdb->prefix . 'my_car_car_records';
			$charset_collate = $wpdb->get_charset_collate();
			$sql             = "CREATE TABLE $table_name (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				car_id bigint(20) unsigned NOT NULL COMMENT 'Car post ID (iworks_car)',
				record_id bigint(20) unsigned NOT NULL COMMENT 'Service record post ID (iworks_service_record)',
				event_date date NOT NULL COMMENT 'Date of the service event',
				event_mileage bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT 'Mileage at the event',
				created_at datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
				PRIMARY KEY  (id),
				KEY car_id (car_id),
				KEY record_id (record_id),
				KEY event_date (event_date),
				UNIQUE KEY car_record (car_id, record_id)
			) $charset_collate;";
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
			update_option( $this->_db_version, $version_to_update );
			$db_version = intval( get_option( $this->_db_version ) );
		}
	}

	/**
	 * Link a service record to a car.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $car_id        Car post ID.
	 * @param int    $record_id     Service record post ID.
	 * @param string $event_date    Event date (Y-m-d).
	 * @param int    $event_mileage Mileage at the event.
	 * @return void
	 */
	public function link_record( $car_id, $record_id, $event_date, $event_mileage ) {
		global $wpdb;
		if ( ! $car_id || ! $record_id ) {
			return;
		}
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO {$wpdb->my_car_car_records} (car_id, record_id, event_date, event_mileage) VALUES (%d, %d, %s, %d) ON DUPLICATE KEY UPDATE event_date = VALUES(event_date), event_mileage = VALUES(event_mileage)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$car_id,
				$record_id,
				$event_date,
				$event_mileage
			)
		);
	}

	/**
	 * Unlink a service record from all cars.
	 *
	 * @since 1.0.0
	 *
	 * @param int $record_id Service record post ID.
	 * @return void
	 */
	public function unlink_record( $record_id ) {
		global $wpdb;
		if ( ! $record_id ) {
			return;
		}
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->my_car_car_records} WHERE record_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$record_id
			)
		);
	}

	/**
	 * Unlink all records of a car.
	 *
	 * @since 1.0.0
	 *
	 * @param int $car_id Car post ID.
	 * @return void
	 */
	public function unlink_car( $car_id ) {
		global $wpdb;
		if ( ! $car_id ) {
			return;
		}
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->my_car_car_records} WHERE car_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$car_id
			)
		);
	}

	/**
	 * Get service records linked to a car, newest first.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $car_id Car post ID.
	 * @param array $args   Optional: per_page, paged.
	 * @return object[] Rows: record_id, event_date, event_mileage.
	 */
	public function get_car_records( $car_id, $args = array() ) {
		global $wpdb;
		if ( ! $car_id ) {
			return array();
		}
		$per_page = isset( $args['per_page'] ) ? absint( $args['per_page'] ) : 0;
		$sql      = sprintf(
			'SELECT record_id, event_date, event_mileage FROM %s WHERE car_id = %%d ORDER BY event_date DESC, event_mileage DESC%s',
			$wpdb->my_car_car_records,
			$per_page ? sprintf( ' LIMIT %d', $per_page ) : ''
		);
		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare( $sql, $car_id ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
	}

	/**
	 * Get the latest linked record data for a car.
	 *
	 * @since 1.0.0
	 *
	 * @param int $car_id Car post ID.
	 * @return object|null Row: record_id, event_date, event_mileage.
	 */
	public function get_latest_car_record( $car_id ) {
		$rows = $this->get_car_records( $car_id, array( 'per_page' => 1 ) );
		return $rows ? $rows[0] : null;
	}

	/**
	 * Drop all plugin database tables
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function drop_tables() {
		global $wpdb;

		// Disable foreign key checks to avoid issues with table dependencies.
		$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		// Drop each table. Table names are hardcoded (see $table_names),
		// validated against a safe charset — they cannot be used with
		// placeholders because they must not be quoted.
		foreach ( $this->table_names as $table_name ) {
			if ( ! preg_match( '/^[A-Za-z0-9_]+$/', $table_name ) ) {
				continue;
			}
			$wpdb->query( sprintf( 'DROP TABLE IF EXISTS `%s%s`', $wpdb->prefix, $table_name ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// Re-enable foreign key checks.
		$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		// Delete the database version option.
		delete_option( $this->_db_version );
	}
}
