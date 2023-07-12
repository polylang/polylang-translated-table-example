<?php
/**
 * Polylang translated table example
 *
 * @package           Polylang translated table example
 * @author            WP SYNTEX
 * @license           GPL-3.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Polylang translated table example
 * Plugin URI:        https://polylang.pro
 * Description:       Example plugin that creates translated a custom DB table.
 * Version:           1.1
 * Requires at least: 5.8
 * Requires PHP:      5.6
 * Author:            WP SYNTEX
 * Author URI:        https://polylang.pro
 * Text Domain:       polylang-translated-table-example
 * Domain Path:       /languages
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * Copyright 2011-2019 Frédéric Demarle
 * Copyright 2019-2023 WP SYNTEX
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace WP_Syntex\PLLTTE;

defined( 'ABSPATH' ) || exit;

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

/**
 * Compatibility with Polylang.
 * Note: `pll_model_init` is triggered early, on `plugins_loaded` with priority 1 (see `Polylang`'s constructor).
 */
add_action(
	'pll_model_init',
	function ( $model ) {
		// Register the DB table in Polylang.
		$translatedEvents = ( new TranslatedEvents( $model ) )->init();
		$model->translatable_objects->register( $translatedEvents );
	}
);

// Create the DB table, etc.
add_action(
	'plugins_loaded',
	function () {
		$GLOBALS['plltte'] = ( new Plugin() )->init();
	}
);
