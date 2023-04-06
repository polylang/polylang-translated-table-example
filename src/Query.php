<?php
/**
 * @package Polylang translated table example
 */

namespace WP_Syntex\PLLTTE;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Query events.
 *
 * @since 1.0
 * @phpstan-type Data array{
 *     id: positive-int,
 *     title: string,
 *     description: string,
 *     date_start: non-empty-string,
 *     date_end: non-empty-string,
 *     type: non-empty-string,
 *     status: non-empty-string,
 *     slug: string,
 *     author: int
 * }
 */
class Query {

	const CACHE_GROUP = 'plltte_events';

	const PLACEHOLDERS = [
		'title'       => '%s',
		'description' => '%s',
		'date_start'  => '%s',
		'date_end'    => '%s',
		'type'        => '%s',
		'status'      => '%s',
		'slug'        => '%s',
		'author'      => '%d',
	];

	const DEFAULTS = [
		'title'       => '',
		'description' => '',
		'date_start'  => '0000-00-00 00:00:00',
		'date_end'    => '0000-00-00 00:00:00',
		'type'        => 'event',
		'status'      => 'publish',
		'slug'        => '',
		'author'      => 0,
	];

	/**
	 * Inserts an event into the DB.
	 *
	 * @since 1.0
	 *
	 * @param (string|int)[] $data Data to insert for the new event.
	 * @return Event|WP_Error An `Event` object on success. A `WP_Error` object otherwise.
	 *
	 * @phpstan-param array{
	 *     title: string,
	 *     description?: string,
	 *     date_start?: string,
	 *     date_end?: string,
	 *     type?: non-empty-string,
	 *     status?: non-empty-string,
	 *     slug?: string,
	 *     author?: int
	 * } $data
	 */
	public function insert( array $data ) {
		global $wpdb;

		$data = array_intersect_key( array_merge( self::DEFAULTS, $data ), self::DEFAULTS );

		if ( empty( $data['slug'] ) ) {
			$data['slug'] = sanitize_title( $data['title'] );
		}

		if ( empty( $data['author'] ) ) {
			$data['author'] = get_current_user_id();
		}

		$result = $wpdb->insert(
			$wpdb->plltte_events,
			$data,
			self::PLACEHOLDERS
		);

		if ( false === $result || $wpdb->insert_id <= 0 ) {
			return new WP_Error( 'event_insertion_failure', $wpdb->last_error );
		}

		$event = $this->get( (int) $wpdb->insert_id );

		if ( null === $event ) {
			return new WP_Error( 'event_request_failure', __( 'Failed to fetch the created event.', 'polylang-translated-table-example' ) );
		}

		return $event;
	}

	/**
	 * Fetches an event from the database.
	 *
	 * @since 1.0
	 *
	 * @param int $id An event ID.
	 * @return Event|null An `Event` object, or `null` if not found.
	 */
	public function get( $id ) {
		global $wpdb;

		$cache_key = "plltte_query_get:{$id}";
		$data      = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( is_array( $data ) ) {
			if ( empty( $data ) ) {
				return null;
			}

			/** @phpstan-var Data $data */
			return new Event( $data );
		}

		$data = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $wpdb->plltte_events WHERE id = %d", $id ),
			\ARRAY_A
		);

		if ( ! is_array( $data ) ) {
			wp_cache_set( $cache_key, [], self::CACHE_GROUP );
			return null;
		}

		wp_cache_set( $cache_key, $data, self::CACHE_GROUP );

		/** @phpstan-var Data $data */
		return new Event( $data );
	}

	/**
	 * Deletes an event from the database.
	 *
	 * @since 1.0
	 *
	 * @param int $id An event ID.
	 * @return int|WP_Error The number of deleted rows (`0` or `1`). A `WP_Error` object on error.
	 */
	public function delete( $id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$wpdb->plltte_events,
			[ 'id' => $id ],
			[ '%d' ]
		);

		if ( false === $result ) {
			return new WP_Error( 'event_deletion_failure', $wpdb->last_error );
		}

		wp_cache_delete( "plltte_query_get:{$id}", self::CACHE_GROUP );

		return (int) $result;
	}
}
