<?php
/**
 * @package Polylang translated table example
 */

namespace WP_Syntex\PLLTTE;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin's main class.
 *
 * @since 1.0
 */
class Plugin {

	/**
	 * @var Admin|null
	 */
	public $admin;

	/**
	 * @var Table
	 */
	private $table;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		$this->table = new Table();
	}

	/**
	 * Plugin's init.
	 *
	 * @since 1.0
	 *
	 * @return self
	 */
	public function init() {
		$is_ready = $this->table->maybe_create_table();

		if ( is_wp_error( $is_ready ) ) {
			// Failed to create the DB table.
			$this->maybe_display_notice( $is_ready->get_error_message() );
			return $this;
		}

		// The DB table is ready.
		if ( is_admin() && ! wp_doing_ajax() ) {
			$this->admin = ( new Admin() )->init();
		}

		return $this;
	}

	/**
	 * Prints and admin notice.
	 *
	 * @since 1.0
	 *
	 * @param string $message The message to display.
	 * @return void
	 */
	private function maybe_display_notice( $message ) {
		if ( ! is_admin() || wp_doing_ajax() ) {
			return;
		}

		add_action(
			'admin_notices',
			function () use ( $message ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}
				?>
				<div class="plltte-notice notice notice-warning">
					<p>
						<?php
						echo esc_html( $message );
						?>
					</p>
				</div>
				<?php
			}
		);
	}
}
