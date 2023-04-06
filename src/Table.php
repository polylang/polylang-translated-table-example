<?php
/**
 * @package Polylang translated table example
 */

namespace WP_Syntex\PLLTTE;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * DB Table.
 *
 * @since 1.0
 */
class Table {

	const DB_TABLE_VERSION    = 100;
	const DB_TABLE_SHORT_NAME = 'plltte_events';

	/**
	 * Returns the full name of the DB table (with prefix).
	 *
	 * @since 1.0
	 *
	 * @return string
	 *
	 * @phpstan-return non-empty-string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::DB_TABLE_SHORT_NAME;
	}

	/**
	 * Creates the custom table if it doesn't exist yet.
	 *
	 * @since 1.0
	 *
	 * @return true|WP_Error True if the table is ready, a WP_Error object otherwise.
	 */
	public function maybe_create_table() {
		$option_name   = self::DB_TABLE_SHORT_NAME . '_db_version';
		$table_version = get_option( $option_name, 0 );
		$table_version = is_numeric( $table_version ) ? (int) $table_version : 0;

		if ( self::DB_TABLE_VERSION === $table_version ) {
			// The table is up-to-date.
			$this->set_table();
			return true;
		}

		$created = $this->create_table();

		if ( is_wp_error( $created ) ) {
			return $created;
		}

		// All good.
		$this->set_table();
		update_option( $option_name, self::DB_TABLE_VERSION );
		return true;
	}

	/**
	 * Creates the custom table.
	 *
	 * @since 1.0
	 *
	 * @return true|WP_Error True if the table has been created, a WP_Error object otherwise.
	 */
	private function create_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$wpdb->hide_errors();

		$schema_query = "
			id bigint(20) unsigned NOT NULL auto_increment,
			title text NOT NULL default '',
			description longtext NOT NULL default '',
			date_start datetime NOT NULL default '0000-00-00 00:00:00',
			date_end datetime NOT NULL default '0000-00-00 00:00:00',
			type varchar(100) NOT NULL default 'event',
			status varchar(20) NOT NULL DEFAULT 'publish',
			slug varchar(200) NOT NULL DEFAULT '',
			author bigint(20) unsigned NOT NULL DEFAULT '0',
			PRIMARY KEY  (id),
			KEY type_status_date_start (type, status, date_start, id),
			KEY type_status_date_end (type, status, date_end, id)";

		dbDelta(
			sprintf(
				"CREATE TABLE `%s` ($schema_query) %s;",
				self::get_table_name(),
				$wpdb->get_charset_collate()
			)
		);

		if ( ! empty( $wpdb->last_error ) ) {
			// Error.
			return new WP_Error(
				'db_sql_error',
				sprintf(
					/* translators: %s is an error message. */
					__( 'SQL error when creating "Polylang translated table example"‘s table: %s.', 'polylang-translated-table-example' ),
					$wpdb->last_error
				)
			);
		}

		$result = $wpdb->get_var(
			sprintf(
				"SHOW TABLES LIKE '%s'",
				$wpdb->esc_like( self::get_table_name() ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			)
		);

		if ( self::get_table_name() !== $result ) {
			// Something is wrong, the table doesn't exist.
			return new WP_Error(
				'db_table_not_exist',
				__( 'Failed to create "Polylang translated table example"‘s table.', 'polylang-translated-table-example' )
			);
		}

		// All good.
		return true;
	}

	/**
	 * Sets related properties in `$wpdb`.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function set_table() {
		global $wpdb;
		$wpdb->{self::DB_TABLE_SHORT_NAME} = $this->get_table_name();
		$wpdb->tables[]                    = self::DB_TABLE_SHORT_NAME;
	}

	/**
	 * Deletes the table and the option containing its version.
	 *
	 * @since 1.0
	 *
	 * @return self
	 */
	public function drop_table() {
		global $wpdb;

		$wpdb->query( sprintf( 'DROP TABLE `%s`', self::get_table_name() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		delete_option( self::DB_TABLE_SHORT_NAME . '_db_version' );
		return $this;
	}
}
