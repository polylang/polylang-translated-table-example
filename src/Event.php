<?php
/**
 * @package Polylang translated table example
 */

namespace WP_Syntex\PLLTTE;

defined( 'ABSPATH' ) || exit;

/**
 * Event.
 *
 * @since 1.0
 */
class Event {

	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $description;

	/**
	 * @var string
	 */
	public $date_start;

	/**
	 * @var string
	 */
	public $date_end;

	/**
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	public $type;

	/**
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	public $status;

	/**
	 * @var string
	 */
	public $slug;

	/**
	 * @var int
	 */
	public $author;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param array $data Event's data.
	 *
	 * @phpstan-param array{
	 *     id?: int,
	 *     title?: string,
	 *     description?: string,
	 *     date_start?: string,
	 *     date_end?: string,
	 *     type?: non-empty-string,
	 *     status?: non-empty-string,
	 *     slug?: string,
	 *     author?: int
	 * } $data
	 */
	public function __construct( array $data ) {
		$defaults = [
			'id'          => 0,
			'title'       => '',
			'description' => '',
			'date_start'  => '0000-00-00 00:00:00',
			'date_end'    => '0000-00-00 00:00:00',
			'type'        => 'event',
			'status'      => 'publish',
			'slug'        => '',
			'author'      => 0,
		];
		$data     = array_intersect_key( $data, $defaults );
		$data     = array_merge( $defaults, $data );

		foreach ( $data as $prop => $value ) {
			$this->$prop = $value;
		}
	}
}
