<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\PLLTTE;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Table listing events.
 *
 * @since 1.0
 */
class EventsTable extends \WP_List_Table {

	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct(
			[
				'screen' => 'plltte_events',
				'plural' => 'Events',
				'ajax'   => false,
			]
		);
	}

	/**
	 * Prepares the list of events for display.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		$clauses = [
			'join'  => '',
			'where' => '',
		];

		/**
		 * Filters the SQL clauses used in the events table query.
		 *
		 * @since 1.0
		 *
		 * @param string[] $clauses Clauses.
		 */
		$clauses = apply_filters( 'plltte_events_table_clauses', $clauses );

		$query  = "FROM $wpdb->plltte_events";
		$query .= $clauses['join'];

		if ( ! empty( $clauses['where'] ) ) {
			$query .= ' WHERE 1=1' . $clauses['where'];
		}

		$total_items = (int) $wpdb->get_var( "SELECT COUNT(*) $query" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, no other way than ignore this.
		$per_page    = $this->get_items_per_page( 'plltte_events_per_page' );

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
			]
		);

		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * $query ORDER BY `date_start` DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, no other way than ignore this.
				$per_page,
				( $this->get_pagenum() - 1 ) * $per_page
			),
			\ARRAY_A
		);

		$this->items = [];

		foreach ( $items as $item ) {
			$this->items[] = new Event( $item );
		}
	}

	/**
	 * Returns the list of columns.
	 *
	 * @since 1.0
	 *
	 * @return string[] The list of column titles.
	 */
	public function get_columns() {
		return [
			'id'         => 'ID',
			'title'      => esc_html__( 'Title', 'polylang-translated-table-example' ),
			'date_start' => esc_html__( 'Start Date', 'polylang-translated-table-example' ),
			'date_end'   => esc_html__( 'End Date', 'polylang-translated-table-example' ),
			'type'       => esc_html__( 'Type', 'polylang-translated-table-example' ),
			'status'     => esc_html__( 'Status', 'polylang-translated-table-example' ),
		];
	}

	/**
	 * Handles the default column output.
	 *
	 * @since 1.0
	 *
	 * @param Event  $item        The event.
	 * @param string $column_name The column name.
	 * @return void
	 */
	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				echo (int) $item->id;
				return;

			case 'title':
				echo esc_html( $item->$column_name );
				return;

			case 'date_start':
			case 'date_end':
				echo esc_html(
					sprintf(
						/* translators: Events list date string. 1: Date, 2: Time. */
						__( '%1$s at %2$s', 'polylang-translated-table-example' ),
						/* translators: Events list date format, see https://www.php.net/manual/datetime.format.php */
						date_i18n( _x( 'M j, Y', 'events list date format', 'polylang-translated-table-example' ), strtotime( $item->$column_name ) ),
						/* translators: Events list time format, see https://www.php.net/manual/datetime.format.php */
						date_i18n( _x( 'H:i', 'events list time format', 'polylang-translated-table-example' ), strtotime( $item->$column_name ) )
					)
				);
				return;

			case 'type':
				$types = [
					'event'      => __( 'Event', 'polylang-translated-table-example' ),
					'conference' => __( 'Conference', 'polylang-translated-table-example' ),
					'seminar'    => __( 'Seminar', 'polylang-translated-table-example' ),
					'other'      => __( 'Other', 'polylang-translated-table-example' ),
					'unknown'    => __( 'Unknown', 'polylang-translated-table-example' ),
				];
				echo esc_html( $types[ $item->type ] );
				return;

			case 'status':
				$statuses = [
					'publish' => __( 'Published', 'polylang-translated-table-example' ),
					'draft'   => __( 'Draft', 'polylang-translated-table-example' ),
				];
				echo esc_html( $statuses[ $item->status ] );
				return;

			default:
				/**
				 * Fires for each custom column of a specific event type in the Events list table.
				 *
				 * @since 1.0
				 *
				 * @param string $column_name The name of the column to display.
				 * @param Event  $item        The item being shown.
				 */
				do_action( "manage_{$this->screen->id}_custom_column", $column_name, $item );
		} // end switch
	}

	/**
	 * Generates and displays row actions links for the list table.
	 *
	 * @since 1.0
	 *
	 * @param Event  $item        The language item being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 * @return string The row actions output.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$delete_url = wp_nonce_url(
			add_query_arg(
				[
					'action'           => 'plltte_delete_event',
					'event'            => $item->id,
					'_wp_http_referer' => Tools::get_admin_page_referer(),
				],
				admin_url( 'admin-post.php' )
			),
			'plltte_delete_event'
		);

		$actions = [
			'delete' => sprintf(
				'<a title="%s" href="%s">%s</a>',
				esc_attr__( 'Delete this event.', 'polylang-translated-table-example' ),
				esc_url( $delete_url ),
				esc_html__( 'Delete', 'polylang-translated-table-example' )
			),
		];

		return $this->row_actions( $actions );
	}
}
