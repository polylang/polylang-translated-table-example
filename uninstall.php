<?php
/**
 * @package Polylang translated table example
 */

namespace WP_Syntex\PLLTTE;

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/src/Table.php';

( new Table() )->drop_table();
