<?php
/**
 * My Car - Database Class
 *
 * Handles all database operations for the My Car plugin.
 *
 * @package WordPress_Plugin_Stub
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
	private array $table_names = array();
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
		 * chemistry table
		 */
		$version_to_update = 2;
		if ( $db_version < $version_to_update ) {
			// $sql = "CREATE TABLE IF NOT EXISTS X (id bigint(20) unsigned NOT NULL AUTO_INCREMENT)";
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			// dbDelta( $sql );
			update_option( $this->_db_version, $version_to_update );
			$db_version = intval( get_option( $this->_db_version ) );
		}
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

		// Delete the database version option
		delete_option( $this->_db_version );
	}
}
