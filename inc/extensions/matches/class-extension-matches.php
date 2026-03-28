<?php
/**
 * Matches extension: schedule and track matches between teams.
 *
 * Depends on Teams (`cp_teams`) via {@see Skeleton::$requires} only; this is a top-level
 * extension (`parent_slug` empty). Blocks are registered from the root `build/matches` metadata collection.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Extensions;

use Kernowdev\Clanspress\Extensions\Abstract_Settings;
use Kernowdev\Clanspress\Extensions\Matches\Admin as Matches_Settings_Admin;
use Kernowdev\Clanspress\Extensions\Matches\Rest_Controller;
use WP_Post;

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/class-extension-matches-admin.php';
require_once __DIR__ . '/class-matches-rest.php';

/**
 * Registers the `cp_match` post type, REST routes, block libraries, and editor meta UI hooks.
 */
class Matches extends Skeleton {

	public const STATUS_SCHEDULED = 'scheduled';
	public const STATUS_LIVE      = 'live';
	public const STATUS_FINISHED  = 'finished';
	public const STATUS_CANCELLED = 'cancelled';

	/**
	 * Option-backed settings surfaced in the unified Clanspress React admin.
	 *
	 * @var Matches_Settings_Admin
	 */
	protected Matches_Settings_Admin $admin;

	/**
	 * REST controller for public match queries.
	 *
	 * @var Rest_Controller
	 */
	protected Rest_Controller $matches_rest;

	/**
	 * Construct extension metadata and register on the official extensions filter.
	 */
	public function __construct() {
		parent::__construct(
			__( 'Matches', 'clanspress' ),
			'cp_matches',
			__( 'Register matches between teams, track status and scores, and display them with blocks or REST.', 'clanspress' ),
			'',
			'1.0.0',
			array( 'cp_teams' )
		);
	}

	/**
	 * Register as a first-party (whitelisted) extension instead of a third-party filter entry.
	 *
	 * @param string $name        Human-readable name.
	 * @param string $slug        Unique slug.
	 * @param string $description Short description.
	 * @param string $parent_slug Parent extension slug, or empty string for a root extension.
	 * @param string $version     Semantic version `x.y.z`.
	 * @param array  $requires    Required extension slugs.
	 * @return void
	 */
	public function setup_extension(
		string $name,
		string $slug,
		string $description,
		string $parent_slug,
		string $version,
		array $requires
	): void {
		parent::setup_extension(
			$name,
			$slug,
			$description,
			$parent_slug,
			$version,
			$requires
		);

		remove_filter( 'clanspress_registered_extensions', array( $this, 'register_extension' ) );
		add_filter( 'clanspress_official_registered_extensions', array( $this, 'register_extension' ) );
	}

	/**
	 * Ensure rewrite rules exist after the extension is first enabled.
	 *
	 * @return void
	 */
	public function run_installer(): void {
		flush_rewrite_rules( false );
	}

	/**
	 * Remove all match posts and flush rewrites when the extension is disabled.
	 *
	 * @return void
	 */
	public function run_uninstaller(): void {
		$ids = get_posts(
			array(
				'post_type'      => 'cp_match',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $post_id ) {
			wp_delete_post( (int) $post_id, true );
		}
		flush_rewrite_rules( false );
	}

	/**
	 * Run migrations when the bundled extension version increases.
	 *
	 * @return void
	 */
	public function run_updater(): void {
	}

	/**
	 * Wire WordPress hooks for CPT, meta, blocks, REST, admin list columns, and editor assets.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->admin          = new Matches_Settings_Admin();
		$this->matches_rest = new Rest_Controller( $this );

		add_action( 'init', array( $this, 'register_match_post_type' ), 11 );
		add_action( 'init', array( $this, 'register_match_meta' ), 11 );
		add_action( 'init', array( $this, 'register_match_block_libraries' ), 11 );
		add_action( 'rest_api_init', array( $this->matches_rest, 'register_routes' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_match_editor' ) );
		add_filter( 'manage_cp_match_posts_columns', array( $this, 'match_admin_columns' ) );
		add_action( 'manage_cp_match_posts_custom_column', array( $this, 'render_match_admin_column' ), 10, 2 );
		add_action( 'save_post_cp_match', array( $this, 'validate_match_on_save' ), 10, 2 );
		add_filter( 'single_template', array( $this, 'maybe_single_match_template' ) );
	}

	/**
	 * Settings handler for the unified React admin (own tab: root extension).
	 *
	 * @return Abstract_Settings|null
	 */
	public function get_settings_admin(): ?Abstract_Settings {
		return isset( $this->admin ) ? $this->admin : null;
	}

	/**
	 * Shape a match post for REST consumers and internal use.
	 *
	 * @param WP_Post $post Match post object.
	 * @return array<string, mixed>
	 */
	public function match_to_rest_array( WP_Post $post ): array {
		$home_id = (int) get_post_meta( $post->ID, 'cp_match_home_team_id', true );
		$away_id = (int) get_post_meta( $post->ID, 'cp_match_away_team_id', true );
		$fmt     = $this->admin->get( 'datetime_format', 'M j, Y g:i a' );

		return array(
			'id'             => $post->ID,
			'title'          => get_the_title( $post ),
			'slug'           => $post->post_name,
			'link'           => get_permalink( $post ),
			'status'         => (string) get_post_meta( $post->ID, 'cp_match_status', true ),
			'scheduledAt'    => (string) get_post_meta( $post->ID, 'cp_match_scheduled_at', true ),
			'scheduledLabel' => clanspress_matches_format_datetime_local(
				(string) get_post_meta( $post->ID, 'cp_match_scheduled_at', true ),
				(string) $fmt
			),
			'homeTeamId'     => $home_id,
			'awayTeamId'     => $away_id,
			'homeTeamTitle'  => clanspress_matches_team_title( $home_id ),
			'awayTeamTitle'  => clanspress_matches_team_title( $away_id ),
			'homeScore'      => (int) get_post_meta( $post->ID, 'cp_match_home_score', true ),
			'awayScore'      => (int) get_post_meta( $post->ID, 'cp_match_away_score', true ),
			'venue'          => (string) get_post_meta( $post->ID, 'cp_match_venue', true ),
			'postStatus'     => $post->post_status,
		);
	}

	/**
	 * Localized labels for match status keys.
	 *
	 * @return array<string, string> Status slug => label.
	 */
	public function get_status_choices(): array {
		return array(
			self::STATUS_SCHEDULED => __( 'Scheduled', 'clanspress' ),
			self::STATUS_LIVE      => __( 'Live', 'clanspress' ),
			self::STATUS_FINISHED  => __( 'Finished', 'clanspress' ),
			self::STATUS_CANCELLED => __( 'Cancelled', 'clanspress' ),
		);
	}

	/**
	 * Register the public `cp_match` post type.
	 *
	 * @return void
	 */
	public function register_match_post_type(): void {
		$labels = array(
			'name'               => _x( 'Matches', 'post type general name', 'clanspress' ),
			'singular_name'      => _x( 'Match', 'post type singular name', 'clanspress' ),
			'menu_name'          => _x( 'Matches', 'admin menu', 'clanspress' ),
			'name_admin_bar'     => _x( 'Match', 'add new on admin bar', 'clanspress' ),
			'add_new'            => _x( 'Add New', 'match', 'clanspress' ),
			'add_new_item'       => __( 'Add New Match', 'clanspress' ),
			'new_item'           => __( 'New Match', 'clanspress' ),
			'edit_item'          => __( 'Edit Match', 'clanspress' ),
			'view_item'          => __( 'View Match', 'clanspress' ),
			'all_items'          => __( 'All Matches', 'clanspress' ),
			'search_items'       => __( 'Search Matches', 'clanspress' ),
			'parent_item_colon'  => __( 'Parent Match:', 'clanspress' ),
			'not_found'          => __( 'No matches found.', 'clanspress' ),
			'not_found_in_trash' => __( 'No matches found in Trash.', 'clanspress' ),
		);

		register_post_type(
			'cp_match',
			array(
				'labels'          => $labels,
				'description'     => __( 'Scheduled or completed matches between teams.', 'clanspress' ),
				'public'          => true,
				'show_ui'         => true,
				'show_in_menu'    => 'clanspress',
				'show_in_rest'    => true,
				'has_archive'     => true,
				'rewrite'         => array( 'slug' => 'matches' ),
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'menu_icon'       => 'dashicons-calendar-alt',
			)
		);
	}

	/**
	 * Register post meta with REST exposure; editor fields are rendered in JS (see `enqueue_match_editor`).
	 *
	 * @return void
	 */
	public function register_match_meta(): void {
		$status_schema = array(
			'type' => 'string',
			'enum' => array(
				self::STATUS_SCHEDULED,
				self::STATUS_LIVE,
				self::STATUS_FINISHED,
				self::STATUS_CANCELLED,
			),
		);

		register_post_meta(
			'cp_match',
			'cp_match_home_team_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);

		register_post_meta(
			'cp_match',
			'cp_match_away_team_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);

		register_post_meta(
			'cp_match',
			'cp_match_scheduled_at',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_scheduled_at' ),
				'show_in_rest'      => true,
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);

		register_post_meta(
			'cp_match',
			'cp_match_status',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => self::STATUS_SCHEDULED,
				'sanitize_callback' => array( $this, 'sanitize_match_status' ),
				'show_in_rest'      => array(
					'schema' => $status_schema,
				),
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);

		register_post_meta(
			'cp_match',
			'cp_match_home_score',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);

		register_post_meta(
			'cp_match',
			'cp_match_away_score',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);

		register_post_meta(
			'cp_match',
			'cp_match_venue',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'auth_callback'     => array( $this, 'meta_auth_edit_post' ),
			)
		);
	}

	/**
	 * Limit meta writes in REST/editor to users who can edit the post.
	 *
	 * @param mixed $allowed  Prior decision (unused).
	 * @param mixed $meta_key Meta key (unused).
	 * @param mixed $post_id  Post ID the meta belongs to.
	 * @return bool
	 */
	public function meta_auth_edit_post( $allowed, $meta_key, $post_id ): bool {
		unset( $allowed, $meta_key );
		return current_user_can( 'edit_post', (int) $post_id );
	}

	/**
	 * Normalize scheduled datetime meta to GMT MySQL format.
	 *
	 * @param mixed $value Raw meta from REST or classic save.
	 * @return string Empty string or `Y-m-d H:i:s` in GMT.
	 */
	public function sanitize_scheduled_at( $value ): string {
		if ( null === $value || '' === $value ) {
			return '';
		}
		if ( is_numeric( $value ) ) {
			return gmdate( 'Y-m-d H:i:s', (int) $value );
		}
		$str = sanitize_text_field( (string) $value );
		$ts  = strtotime( $str );
		if ( false === $ts ) {
			return '';
		}

		return gmdate( 'Y-m-d H:i:s', $ts );
	}

	/**
	 * Restrict status meta to known slugs.
	 *
	 * @param mixed $value Raw status value.
	 * @return string One of the {@see STATUS_*} constants.
	 */
	public function sanitize_match_status( $value ): string {
		$value = sanitize_key( (string) $value );
		$keys  = array_keys( $this->get_status_choices() );

		return in_array( $value, $keys, true ) ? $value : self::STATUS_SCHEDULED;
	}

	/**
	 * Insert custom columns after the title on the match list screen.
	 *
	 * @param array<string, string> $columns Default columns.
	 * @return array<string, string>
	 */
	public function match_admin_columns( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['cp_match_teams']  = __( 'Teams', 'clanspress' );
				$new['cp_match_when']   = __( 'Scheduled', 'clanspress' );
				$new['cp_match_status'] = __( 'Match status', 'clanspress' );
				$new['cp_match_score']  = __( 'Score', 'clanspress' );
			}
		}

		return $new;
	}

	/**
	 * Output HTML for a single admin list column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Match post ID.
	 * @return void
	 */
	public function render_match_admin_column( string $column, int $post_id ): void {
		if ( 'cp_match_teams' === $column ) {
			$h = (int) get_post_meta( $post_id, 'cp_match_home_team_id', true );
			$a = (int) get_post_meta( $post_id, 'cp_match_away_team_id', true );
			echo esc_html( clanspress_matches_team_title( $h ) . ' vs ' . clanspress_matches_team_title( $a ) );
			return;
		}
		if ( 'cp_match_when' === $column ) {
			$raw = (string) get_post_meta( $post_id, 'cp_match_scheduled_at', true );
			echo esc_html(
				clanspress_matches_format_datetime_local( $raw, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) )
			);
			return;
		}
		if ( 'cp_match_status' === $column ) {
			$s       = (string) get_post_meta( $post_id, 'cp_match_status', true );
			$choices = $this->get_status_choices();
			echo esc_html( $choices[ $s ] ?? $s );
			return;
		}
		if ( 'cp_match_score' === $column ) {
			$hs = (int) get_post_meta( $post_id, 'cp_match_home_score', true );
			$as = (int) get_post_meta( $post_id, 'cp_match_away_score', true );
			echo esc_html( "{$hs} – {$as}" );
		}
	}

	/**
	 * Strip invalid team IDs and ensure a default schedule time on save.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function validate_match_on_save( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( 'cp_match' !== $post->post_type ) {
			return;
		}

		$home = (int) get_post_meta( $post_id, 'cp_match_home_team_id', true );
		$away = (int) get_post_meta( $post_id, 'cp_match_away_team_id', true );

		if ( $home > 0 ) {
			$h_post = get_post( $home );
			if ( ! $h_post || 'cp_team' !== $h_post->post_type ) {
				delete_post_meta( $post_id, 'cp_match_home_team_id' );
			}
		}
		if ( $away > 0 ) {
			$a_post = get_post( $away );
			if ( ! $a_post || 'cp_team' !== $a_post->post_type ) {
				delete_post_meta( $post_id, 'cp_match_away_team_id' );
			}
		}

		$sched = (string) get_post_meta( $post_id, 'cp_match_scheduled_at', true );
		if ( '' === $sched ) {
			update_post_meta( $post_id, 'cp_match_scheduled_at', gmdate( 'Y-m-d H:i:s' ) );
		}
	}

	/**
	 * Load Match list and Match card blocks from the plugin root `build/matches` bundle (metadata collection).
	 *
	 * @return void
	 */
	public function register_match_block_libraries(): void {
		$base = clanspress()->path . 'build/matches';
		$manifest = $base . '/blocks-manifest.php';

		if ( ! is_dir( $base ) || ! is_readable( $manifest ) ) {
			add_action( 'admin_notices', array( $this, 'render_notice_missing_match_assets' ) );
			return;
		}

		$this->register_extension_block_types_from_metadata_collection( 'build/matches' );
	}

	/**
	 * Admin notice when block build output is missing (run package `npm run build`).
	 *
	 * @return void
	 */
	public function render_notice_missing_match_assets(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}
		$relevant = false;
		if ( 'edit-cp_match' === $screen->id ) {
			$relevant = true;
		}
		if ( 'post' === $screen->base && 'cp_match' === $screen->post_type ) {
			$relevant = true;
		}
		if ( 'toplevel_page_clanspress' === $screen->id || 'clanspress_page_clanspress' === $screen->id ) {
			$relevant = true;
		}
		if ( ! $relevant ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__(
				'Clanspress Matches: block assets are missing. From the plugin directory, run npm ci and npm run build:production (or npm run plugin-zip).',
				'clanspress'
			)
		);
	}

	/**
	 * Enqueue the block editor script that registers the Match details document sidebar.
	 *
	 * @return void
	 */
	public function enqueue_match_editor(): void {
		$path = clanspress()->path . 'build/cp-match-editor/index.asset.php';
		if ( ! is_readable( $path ) ) {
			return;
		}

		$asset = include $path;
		$deps  = isset( $asset['dependencies'] ) && is_array( $asset['dependencies'] ) ? $asset['dependencies'] : array();
		$ver   = isset( $asset['version'] ) ? (string) $asset['version'] : '1.0.0';

		wp_enqueue_script(
			'clanspress-cp-match-editor',
			clanspress()->url . 'build/cp-match-editor/index.js',
			$deps,
			$ver,
			true
		);
	}

	/**
	 * Build a `meta_query` fragment for list blocks (optional team + status).
	 *
	 * @param int    $team_id `cp_team` post ID or 0 for any team.
	 * @param string $status  Status slug or empty for any status.
	 * @return array<int|string, mixed>
	 */
	protected function build_block_meta_query( int $team_id, string $status ): array {
		$parts = array();
		if ( $team_id > 0 ) {
			$parts[] = array(
				'relation' => 'OR',
				array(
					'key'   => 'cp_match_home_team_id',
					'value' => $team_id,
				),
				array(
					'key'   => 'cp_match_away_team_id',
					'value' => $team_id,
				),
			);
		}
		$allowed = array_keys( $this->get_status_choices() );
		if ( '' !== $status && in_array( $status, $allowed, true ) ) {
			$parts[] = array(
				'key'   => 'cp_match_status',
				'value' => $status,
			);
		}
		if ( count( $parts ) === 0 ) {
			return array();
		}
		if ( count( $parts ) === 1 ) {
			return $parts[0];
		}

		return array_merge( array( 'relation' => 'AND' ), $parts );
	}

	/**
	 * HTML for the Match list block (`render.php` entry point).
	 *
	 * @param array<string, mixed> $attributes Block attributes (`teamId`, `limit`, `statusFilter`, `order`).
	 * @return string HTML (escaped internally).
	 */
	public function render_list_block_markup( array $attributes ): string {
		$team_id = (int) ( $attributes['teamId'] ?? 0 );
		$limit   = (int) ( $attributes['limit'] ?? 0 );
		if ( $limit < 1 ) {
			$limit = max( 1, (int) $this->admin->get( 'default_list_limit', 10 ) );
		}
		$limit = min( 50, max( 1, $limit ) );

		$status = sanitize_key( (string) ( $attributes['statusFilter'] ?? '' ) );
		$order  = strtolower( (string) ( $attributes['order'] ?? 'asc' ) ) === 'desc' ? 'DESC' : 'ASC';

		$show_scores = (bool) $this->admin->get( 'show_scores', true );
		$fmt         = (string) $this->admin->get( 'datetime_format', 'M j, Y g:i a' );

		$args = array(
			'post_type'      => 'cp_match',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'meta_value',
			'meta_key'       => 'cp_match_scheduled_at',
			'meta_type'      => 'DATETIME',
			'order'          => $order,
		);

		$meta_query = $this->build_block_meta_query( $team_id, $status );
		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		$q = new \WP_Query( $args );

		ob_start();
		if ( ! $q->have_posts() ) {
			echo '<div class="clanspress-match-list clanspress-match-list--empty"><p>' . esc_html__( 'No matches to show.', 'clanspress' ) . '</p></div>';
			return (string) ob_get_clean();
		}

		echo '<ul class="clanspress-match-list">';
		while ( $q->have_posts() ) {
			$q->the_post();
			$pid = (int) get_the_ID();
			$h   = (int) get_post_meta( $pid, 'cp_match_home_team_id', true );
			$a   = (int) get_post_meta( $pid, 'cp_match_away_team_id', true );
			$st  = (string) get_post_meta( $pid, 'cp_match_status', true );
			$raw = (string) get_post_meta( $pid, 'cp_match_scheduled_at', true );
			$hs  = (int) get_post_meta( $pid, 'cp_match_home_score', true );
			$as  = (int) get_post_meta( $pid, 'cp_match_away_score', true );

			$choices = $this->get_status_choices();
			$st_lbl  = $choices[ $st ] ?? $st;

			echo '<li class="clanspress-match-list__item">';
			echo '<a class="clanspress-match-list__link" href="' . esc_url( get_permalink() ) . '">';
			echo '<span class="clanspress-match-list__teams">';
			echo esc_html( clanspress_matches_team_title( $h ) . ' vs ' . clanspress_matches_team_title( $a ) );
			echo '</span>';
			echo '<span class="clanspress-match-list__meta">';
			echo esc_html( clanspress_matches_format_datetime_local( $raw, $fmt ) );
			echo ' · ';
			echo esc_html( $st_lbl );
			if ( $show_scores ) {
				echo ' · ';
				echo esc_html( (string) $hs . ' – ' . (string) $as );
			}
			echo '</span>';
			echo '</a>';
			echo '</li>';
		}
		echo '</ul>';
		wp_reset_postdata();

		return (string) ob_get_clean();
	}

	/**
	 * HTML for the Match card block (`render.php` entry point).
	 *
	 * @param array<string, mixed> $attributes Block attributes (`matchId`).
	 * @return string HTML (escaped internally).
	 */
	public function render_card_block_markup( array $attributes ): string {
		$match_id = (int) ( $attributes['matchId'] ?? 0 );
		if ( $match_id <= 0 ) {
			return '<div class="clanspress-match-card clanspress-match-card--placeholder"><p>' . esc_html__( 'Select a match in the block settings.', 'clanspress' ) . '</p></div>';
		}

		$post = get_post( $match_id );
		if ( ! $post || 'cp_match' !== $post->post_type || 'publish' !== $post->post_status ) {
			return '<div class="clanspress-match-card clanspress-match-card--missing"><p>' . esc_html__( 'Match not found.', 'clanspress' ) . '</p></div>';
		}

		$data        = $this->match_to_rest_array( $post );
		$show_scores = (bool) $this->admin->get( 'show_scores', true );
		$choices     = $this->get_status_choices();
		$st_lbl      = $choices[ $data['status'] ] ?? $data['status'];

		ob_start();
		?>
		<article class="clanspress-match-card">
			<h3 class="clanspress-match-card__title">
				<a href="<?php echo esc_url( $data['link'] ); ?>"><?php echo esc_html( $data['title'] ); ?></a>
			</h3>
			<p class="clanspress-match-card__teams">
				<span class="clanspress-match-card__home"><?php echo esc_html( $data['homeTeamTitle'] ); ?></span>
				<span class="clanspress-match-card__vs"> <?php esc_html_e( 'vs', 'clanspress' ); ?> </span>
				<span class="clanspress-match-card__away"><?php echo esc_html( $data['awayTeamTitle'] ); ?></span>
			</p>
			<p class="clanspress-match-card__when"><?php echo esc_html( $data['scheduledLabel'] ); ?></p>
			<p class="clanspress-match-card__status"><?php echo esc_html( $st_lbl ); ?></p>
			<?php if ( $show_scores ) : ?>
				<p class="clanspress-match-card__score">
					<?php echo esc_html( (string) $data['homeScore'] . ' – ' . (string) $data['awayScore'] ); ?>
				</p>
			<?php endif; ?>
			<?php if ( ! empty( $data['venue'] ) ) : ?>
				<p class="clanspress-match-card__venue"><?php echo esc_html( $data['venue'] ); ?></p>
			<?php endif; ?>
		</article>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Use the plugin single template for match permalinks when the theme has no override.
	 *
	 * @param string $template Path passed by WordPress.
	 * @return string Template path to load.
	 */
	public function maybe_single_match_template( string $template ): string {
		if ( ! is_singular( 'cp_match' ) ) {
			return $template;
		}

		$plugin = clanspress()->path . 'templates/matches/single-cp_match.php';
		if ( is_readable( $plugin ) ) {
			return $plugin;
		}

		return $template;
	}
}
