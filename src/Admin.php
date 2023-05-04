<?php
/**
 * @package Polylang translated table example
 */

namespace WP_Syntex\PLLTTE;

defined( 'ABSPATH' ) || exit;

/**
 * Admin page.
 *
 * @since 1.0
 */
class Admin {

	/**
	 * Class init.
	 *
	 * @since 1.0
	 *
	 * @return self
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_print_styles-toplevel_page_plltte-events', [ $this, 'print_styles' ] );
		add_action( 'admin_post_plltte_add_event', [ $this, 'action_add_event' ] );
		add_action( 'admin_post_plltte_delete_event', [ $this, 'action_delete_event' ] );
		return $this;
	}

	/**
	 * Adds an entry to the admin menu.
	 * Hooked to `admin_menu`.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			__( 'All Events', 'polylang-translated-table-example' ),
			__( 'Events', 'polylang-translated-table-example' ),
			'publish_posts',
			'plltte-events',
			[ $this, 'list_events' ],
			'dashicons-calendar-alt'
		);
	}

	/**
	 * Displays the content of the admin page listing all events.
	 * Hooked to `toplevel_page_plltte-events`.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function list_events() {
		require ABSPATH . 'wp-admin/options-head.php';
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'All Events', 'polylang-translated-table-example' ); ?></h1>
			<a href="<?php echo esc_url( $this->get_add_event_url() ); ?>" class="page-title-action"><?php echo esc_html_x( 'Add', 'event', 'polylang-translated-table-example' ); ?></a>
			<hr class="wp-header-end">
			<?php
			$list_table = new EventsTable();
			$list_table->prepare_items();
			$list_table->display();
			?>
		</div><!-- wrap -->
		<?php
	}

	/**
	 * Prints some basic styles into the events page.
	 * Hooked to `admin_print_styles-toplevel_page_plltte-events`.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function print_styles() {
		echo '<style>.column-id{width:3em;}</style>';
	}

	/**
	 * Creates a random event after clicking the "Add" button.
	 * Hooked to `admin_post_plltte_add_event`.
	 *
	 * @since 1.0
	 *
	 * @return void
	 *
	 * @phpstan-return never
	 */
	public function action_add_event() {
		check_admin_referer( 'plltte_add_event' );

		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to create events.', 'polylang-translated-table-example' ) );
		}

		$title = 'Some event #' . wp_generate_password( 7, false, false );
		$time  = new \DateTimeImmutable( 'today midnight', wp_timezone() );
		$time  = $time->add(
			new \DateInterval(
				sprintf(
					'P%dDT%dH',
					wp_rand( 0, 23 ),
					wp_rand( 0, 30 )
				)
			)
		);
		$start = $time->format( 'Y-m-d H:i:s' );
		$end   = $time->add(
			new \DateInterval(
				sprintf(
					'P%dD',
					wp_rand( 1, 3 )
				)
			)
		)->format( 'Y-m-d H:i:s' );
		$types = [
			'event',
			'conference',
			'seminar',
			'other',
			'unknown',
		];
		$type  = $types[ wp_rand( 0, 4 ) ];

		$query = new Query();
		$event = $query->insert(
			[
				'title'       => $title,
				'description' => 'Some description',
				'date_start'  => $start,
				'date_end'    => $end,
				'type'        => $type,
			]
		);

		if ( is_wp_error( $event ) ) {
			add_settings_error(
				'general',
				'plltte_event_not_created',
				sprintf(
					/* translators: %s is an error message */
					__( 'Could not create event: %s.', 'polylang-translated-table-example' ),
					$event->get_error_message()
				),
				'error'
			);

			Tools::redirect();
		}

		add_settings_error(
			'general',
			'plltte_event_created',
			sprintf(
				/* translators: %s is the title of an event. */
				__( 'Event "%s" added.', 'polylang-translated-table-example' ),
				$event->title
			),
			'updated'
		);

		/**
		 * Fires after an event has been created.
		 *
		 * @since 1.0
		 *
		 * @param Event $event Event.
		 */
		do_action( 'plltte_event_inserted', $event );

		Tools::redirect();
	}

	/**
	 * Deletes an event after clicking the "Delete" button.
	 * Hooked to `admin_post_plltte_delete_event`.
	 *
	 * @since 1.0
	 *
	 * @return void
	 *
	 * @phpstan-return never
	 */
	public function action_delete_event() {
		check_admin_referer( 'plltte_delete_event' );

		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to delete events.', 'polylang-translated-table-example' ) );
		}

		$event_id = ! empty( $_GET['event'] ) && is_numeric( $_GET['event'] ) ? (int) $_GET['event'] : 0;

		if ( $event_id <= 0 ) {
			wp_die( esc_html__( 'Invalid event.', 'polylang-translated-table-example' ) );
		}

		$result = ( new Query() )->delete( $event_id );

		if ( is_wp_error( $result ) ) {
			add_settings_error(
				'general',
				'plltte_event_not_deleted',
				sprintf(
					/* translators: %s is an error message */
					__( 'Could not delete event: %s.', 'polylang-translated-table-example' ),
					$result->get_error_message()
				),
				'error'
			);

			Tools::redirect();
		}

		add_settings_error( 'general', 'plltte_event_deleted', __( 'Event deleted.', 'polylang-translated-table-example' ), 'updated' );

		/**
		 * Fires after an event has been created.
		 *
		 * @since 1.0
		 *
		 * @param int $event_id Event ID.
		 */
		do_action( 'plltte_event_deleted', $event_id );

		Tools::redirect();
	}

	/**
	 * Returns a URL to create an event.
	 *
	 * @since 1.0
	 *
	 * @param mixed[] $args         Optional. Aditional query args. Defaults to an empty array.
	 * @param mixed[] $referer_args Optional. Aditional query args for the referer. Defaults to an empty array.
	 * @return string
	 */
	public function get_add_event_url( array $args = [], array $referer_args = [] ) {
		/**
		 * Filters the query args to create an event.
		 *
		 * @since 1.0
		 *
		 * @param array $args
		 */
		$args = (array) apply_filters( 'plltte_add_event_query_args', $args );
		$args = array_merge(
			$args,
			[
				'action'           => 'plltte_add_event',
				'_wp_http_referer' => Tools::get_admin_page_referer( $referer_args ),
			]
		);

		return add_query_arg(
			$args,
			wp_nonce_url( admin_url( 'admin-post.php' ), 'plltte_add_event' )
		);
	}
}
