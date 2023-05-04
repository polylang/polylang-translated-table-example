<?php
/**
 * @package Polylang translated table example
 */

namespace WP_Syntex\PLLTTE;

defined( 'ABSPATH' ) || exit;

/**
 * Generic tools.
 *
 * @since 1.0
 */
class Tools {

	/**
	 * Returns the encoded URL of the events page in the admin.
	 * It can be used as referer when building a URL to query.
	 *
	 * @since 1.0
	 *
	 * @param mixed[] $args Optional. Aditional query args. Defaults to an empty array.
	 * @return string
	 */
	public static function get_admin_page_referer( array $args = [] ) {
		$url = admin_url( 'admin.php?page=plltte-events', 'relative' );

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return rawurlencode( $url );
	}

	/**
	 * Redirects after performing an action and using `add_settings_error()`.
	 *
	 * @since 1.0
	 *
	 * @return void
	 *
	 * @phpstan-return never
	 */
	public static function redirect() {
		set_transient( 'settings_errors', get_settings_errors(), 30 );

		$goback = add_query_arg( 'settings-updated', 'true', wp_get_referer() );
		wp_safe_redirect( esc_url_raw( $goback ) );
		die();
	}
}
