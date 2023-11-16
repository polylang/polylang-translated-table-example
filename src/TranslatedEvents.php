<?php
/**
 * @package Polylang translated table example
 */

namespace WP_Syntex\PLLTTE;

defined( 'ABSPATH' ) || exit;

/**
 * Events language model.
 *
 * @since 1.0
 *
 * @phpstan-import-type DBInfoWithType from \PLL_Translatable_Object_With_Types_Interface
 */
class TranslatedEvents extends \PLL_Translated_Object implements \PLL_Translatable_Object_With_Types_Interface {

	use \PLL_Translatable_Object_With_Types_Trait;

	/**
	 * Taxonomy name for the languages.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $tax_language = 'plltte_events_language';

	/**
	 * Taxonomy name for the translation groups.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $tax_translations = 'plltte_events_translations';

	/**
	 * Object type to use when registering the taxonomy.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string|null
	 */
	protected $object_type = 'plltte_event';

	/**
	 * Identifier that must be unique for each type of content.
	 * Also used when checking capabilities.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $type = 'plltte_event';

	/**
	 * Constructor.
	 *
	 * @since 1.8
	 *
	 * @param \PLL_Model $model Instance of `PLL_Model`.
	 */
	public function __construct( \PLL_Model $model ) {
		$this->cache_type = Query::CACHE_GROUP;

		parent::__construct( $model );
	}

	/**
	 * Adds hooks.
	 *
	 * @since 1.0
	 *
	 * @return self
	 */
	public function init() {
		if ( ! is_admin() || wp_doing_ajax() ) {
			return parent::init();
		}

		add_filter( 'plltte_add_event_query_args', [ $this, 'add_lang_query_arg' ] );
		add_action( 'plltte_event_inserted', [ $this, 'set_language_to_inserted_event' ] );
		add_filter( 'plltte_events_table_clauses', [ $this, 'filter_events_table_clauses' ] );
		add_filter( 'manage_plltte_events_columns', [ $this, 'add_lang_columns' ], 100 );
		add_action( 'manage_plltte_events_custom_column', [ $this, 'add_columns_content' ], 10, 2 );
		add_action( 'admin_post_plltte_add_translation', [ $this, 'action_add_translation' ] );

		return parent::init();
	}

	/**
	 * Returns object types (event types) that need to be translated.
	 * The events list is cached for better performance.
	 * The method waits for 'after_setup_theme' to apply the cache to allow themes adding the filter in functions.php.
	 *
	 * @since 3.4
	 *
	 * @param bool $filter True if we should return only valid registered object types. Not used here.
	 * @return string[] Object type names for which Polylang manages languages.
	 *
	 * @phpstan-return array<non-empty-string, non-empty-string>
	 */
	public function get_translated_object_types( $filter = true ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$types = $this->model->cache->get( 'plltte_types' );

		if ( is_array( $types ) ) {
			/** @var array<non-empty-string, non-empty-string> $types */
			return $types;
		}

		$types = [
			'event'      => 'event',
			'conference' => 'conference',
			'seminar'    => 'seminar',
		];

		/**
		 * Filters the list of types available for translation.
		 * The filter must be added soon in the WordPress loading process:
		 * in a function hooked to ‘plugins_loaded’ or directly in functions.php for themes.
		 *
		 * @since 1.0
		 *
		 * @param string[] $types List of type names (as array keys and values).
		 */
		$types = apply_filters( 'plltte_translated_types', $types );
		$types = array_combine( $types, $types );

		if ( did_action( 'after_setup_theme' ) ) {
			$this->model->cache->set( 'plltte_types', $types );
		}

		/** @var array<non-empty-string, non-empty-string> $types */
		return $types;
	}

	/**
	 * Adds the `lang` query arg. This will be used by `set_language_to_inserted_event()` to know which language to
	 * assign to a newly created event.
	 * Hooked to `plltte_add_event_query_args`.
	 *
	 * @since 1.0
	 *
	 * @param mixed[] $args Query args.
	 * @return mixed[]
	 */
	public function add_lang_query_arg( $args ) {
		if ( isset( $args['lang'] ) ) {
			return $args;
		}

		$lang = ! empty( PLL()->pref_lang ) ? PLL()->pref_lang : $this->model->get_default_language();

		if ( empty( $lang ) ) {
			return $args;
		}

		$args['lang'] = $lang->slug;
		return $args;
	}

	/**
	 * Sets a language to a newly created event.
	 * Hooked to `plltte_event_inserted`.
	 *
	 * @since 1.0
	 *
	 * @param Event $event Event.
	 * @return void
	 */
	public function set_language_to_inserted_event( $event ) {
		if ( ! $this->is_translated_object_type( $event->type ) ) {
			return;
		}

		if ( empty( $_GET['lang'] ) || ! is_string( $_GET['lang'] ) || 'all' === $_GET['lang'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, nonce verified in `Admin::action_add_event()`.
			return;
		}

		$lang = wp_unslash( $_GET['lang'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended, sanitized by the following `preg_match()` and nonce verified in `Admin::action_add_event()`.

		// Same pattern than `PLL_Admin_Model::validate_lang()`.
		if ( ! preg_match( '#^[a-z_-]+$#', $lang ) ) {
			return;
		}

		$this->set_language( $event->id, $lang );
	}

	/**
	 * Filters the SQL clauses used in the events table query.
	 * This allows to filter the table by the language selected in the admin bar.
	 * Hooked to `plltte_events_table_clauses`.
	 *
	 * @since 1.0
	 *
	 * @param string[] $clauses Clauses.
	 * @return string[]
	 */
	public function filter_events_table_clauses( $clauses ) {
		if ( empty( PLL()->filter_lang ) ) {
			return $clauses;
		}

		$clauses['join']  .= $this->join_clause();
		$clauses['where'] .= $this->where_clause( PLL()->filter_lang );

		return $clauses;
	}

	/**
	 * Adds the language and translations columns in the events table.
	 * Hooked to `manage_plltte_events_columns`.
	 *
	 * @since 1.0
	 *
	 * @param string[] $columns List of columns.
	 * @return string[] Modified list of columns.
	 */
	public function add_lang_columns( $columns ) {
		foreach ( $this->model->get_languages_list() as $language ) {
			$columns[ "language_{$language->slug}" ] = sprintf(
				'%s<span class="screen-reader-text">%s</span>',
				$this->get_flag_html( $language ),
				esc_html( $language->name )
			);
		}

		return $columns;
	}

	/**
	 * Fills the language and translations columns in the events table.
	 * Hooked to `manage_plltte_events_custom_column`.
	 *
	 * @since 1.0
	 *
	 * @param string $column_name The name of the column to display.
	 * @param Event  $item        The item being shown.
	 * @return void
	 */
	public function add_columns_content( $column_name, $item ) {
		if ( ! preg_match( '/^language_(?<lang>.+)$/', $column_name, $matches ) ) {
			return;
		}

		$langs = $this->model->get_languages_list( [ 'fields' => 'slug' ] );

		if ( ! in_array( $matches['lang'], $langs, true ) ) {
			return;
		}

		$item_lang = $this->get_language( $item->id );

		if ( empty( $item_lang ) ) {
			return;
		}

		$col_lang = $this->model->get_language( $matches['lang'] );

		if ( empty( $col_lang ) ) {
			return;
		}

		if ( $item_lang->slug === $col_lang->slug ) {
			printf(
				'<span class="pll_column_flag"><span class="screen-reader-text">%1$s</span>%2$s</span>',
				/* translators: accessibility text, %s is a native language name */
				esc_html( sprintf( __( 'This event is in %s', 'polylang-translated-table-example' ), $item_lang->name ) ),
				$this->get_flag_html( $item_lang ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
			return;
		}

		$tr_id = $this->get( $item->id, $col_lang );

		if ( empty( $tr_id ) ) {
			$url = $this->get_add_translation_url( $item, $col_lang );
			/* translators: accessibility text, %s is a native language name */
			$hint = sprintf( __( 'Add a translation in %s', 'polylang-translated-table-example' ), $col_lang->name );

			printf(
				'<a href="%1$s" title="%2$s" class="pll_icon_add"><span class="screen-reader-text">%3$s</span></a>',
				esc_url( $url ),
				esc_attr( $hint ),
				esc_html( $hint )
			);
			return;
		}

		printf(
			'%1$d <span class="screen-reader-text">%2$s</span>',
			(int) $tr_id,
			esc_html(
				sprintf(
					/* translators: accessibility text, %s is a native language name */
					__( 'This event already has a translation in %s', 'polylang-translated-table-example' ),
					$col_lang->name
				)
			)
		);
	}

	/**
	 * Creates a translation after clicking the "Add a translation" button.
	 * Hooked to `admin_post_plltte_add_translation`.
	 *
	 * @since 1.0
	 *
	 * @return void
	 *
	 * @phpstan-return never
	 */
	public function action_add_translation() {
		check_admin_referer( 'plltte_add_translation' );

		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to create events.', 'polylang-translated-table-example' ) );
		}

		$source_id = ! empty( $_GET['event'] ) && is_numeric( $_GET['event'] ) ? (int) $_GET['event'] : 0;

		if ( $source_id <= 0 ) {
			wp_die( esc_html__( 'Invalid source event.', 'polylang-translated-table-example' ) );
		}

		if ( empty( $_GET['lang'] ) || ! is_string( $_GET['lang'] ) || 'all' === $_GET['lang'] ) {
			wp_die( esc_html__( 'Invalid target language.', 'polylang-translated-table-example' ) );
		}

		$target_lang = wp_unslash( $_GET['lang'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, sanitized by the following `preg_match()`.

		// Same pattern than `PLL_Admin_Model::validate_lang()`.
		if ( ! preg_match( '#^[a-z_-]+$#', $target_lang ) ) {
			wp_die( esc_html__( 'Invalid target language.', 'polylang-translated-table-example' ) );
		}

		$query        = new Query();
		$source_event = $query->get( $source_id );

		if ( null === $source_event ) {
			wp_die( esc_html__( 'Invalid source event.', 'polylang-translated-table-example' ) );
		}

		$target_lang = PLL()->model->get_language( $target_lang );

		if ( empty( $target_lang ) ) {
			wp_die( esc_html__( 'Invalid target language.', 'polylang-translated-table-example' ) );
		}

		if ( $this->get_translation( $source_id, $target_lang ) ) {
			wp_die( esc_html__( 'This event already has a translation for this language.', 'polylang-translated-table-example' ) );
		}

		// Copy the source event.
		$source_event->title       .= " ({$target_lang->slug})";
		$source_event->description .= " (translated into {$target_lang->slug})";
		$source_event->slug        .= "-{$target_lang->slug}";

		$translation = $query->insert( (array) $source_event );

		if ( is_wp_error( $translation ) ) {
			add_settings_error(
				'general',
				'plltte_translation_not_created',
				sprintf(
					/* translators: %s is an error message */
					__( 'Could not create translation: %s.', 'polylang-translated-table-example' ),
					$translation->get_error_message()
				),
				'error'
			);

			Tools::redirect();
		}

		// Set the target language to the new event.
		$this->set_language( $translation->id, $target_lang );

		// Tells that the new event is a translation of the source event.
		$translations = $this->get_translations( $source_id );

		$translations[ $target_lang->slug ] = $translation->id;
		$this->save_translations( $source_id, $translations );

		add_settings_error(
			'general',
			'plltte_translation_created',
			sprintf(
				/* translators: %s is the title of an event. */
				__( 'Translation "%s" added.', 'polylang-translated-table-example' ),
				$translation->title
			),
			'updated'
		);

		Tools::redirect();
	}

	/**
	 * Returns database-related informations that can be used in some of this class methods.
	 * These are specific to the table containing the objects.
	 *
	 * @see PLL_Translatable_Object::join_clause()
	 * @see PLL_Translatable_Object::get_objects_with_no_lang_sql()
	 *
	 * @since 1.1
	 *
	 * @return string[] {
	 *     @type string $table         Name of the table.
	 *     @type string $id_column     Name of the column containing the object's ID.
	 *     @type string $default_alias Default alias corresponding to the object's table.
	 * }
	 * @phpstan-return DBInfoWithType
	 */
	protected function get_db_infos() {
		return [
			'table'         => Table::get_table_name(),
			'id_column'     => 'id',
			'type_column'   => 'type',
			'default_alias' => Table::get_table_name(),
		];
	}

	/**
	 * Returns a URL to create a translation.
	 *
	 * @since 1.0
	 *
	 * @param Event         $source       Source event.
	 * @param \PLL_Language $target_language Target language.
	 * @return string
	 */
	private function get_add_translation_url( Event $source, \PLL_Language $target_language ) {
		$args = [
			'event'            => $source->id,
			'lang'             => $target_language->slug,
			'action'           => 'plltte_add_translation',
			'_wp_http_referer' => Tools::get_admin_page_referer(),
		];

		return add_query_arg(
			$args,
			wp_nonce_url( admin_url( 'admin-post.php' ), 'plltte_add_translation' )
		);
	}

	/**
	 * Returns the language flag or the language slug if there is no flag.
	 *
	 * @since 1.8
	 *
	 * @param \PLL_Language $language PLL_Language object.
	 * @return string
	 */
	private function get_flag_html( $language ) {
		return sprintf(
			'%s<span class="screen-reader-text">%s</span>',
			$language->flag ? $language->flag : sprintf( '<abbr>%s</abbr>', esc_html( $language->slug ) ),
			esc_html( $language->name )
		);
	}
}
