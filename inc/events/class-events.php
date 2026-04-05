<?php
/**
 * Core events system (RSVPs, visibility adapters, REST).
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Events;
defined( 'ABSPATH' ) || exit;

/**
 * Initializes the core events/RSVP system.
 *
 * This is intentionally generic so Teams/Matches and group integrations share the same RSVP
 * storage + REST API + hooks. Group capabilities use {@see clanspress_groups_user_can_manage()}
 * and {@see clanspress_groups_user_is_member()} (extensions filter those; core does not call add-ons).
 */
final class Events {
	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registers `cp_event` and meta.
	 *
	 * @var Event_Post_Type|null
	 */
	private ?Event_Post_Type $event_post_type = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->register();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		$functions = __DIR__ . '/functions.php';
		if ( is_readable( $functions ) ) {
			require_once $functions;
		}

		$this->event_post_type = new Event_Post_Type();
		$this->event_post_type->register();

		add_action( 'init', array( $this, 'maybe_create_tables' ), 5 );
		add_action( 'init', array( $this, 'register_cp_players_events_template_filter' ), 3 );
		add_action( 'init', array( $this, 'register_group_events_block_template' ), 4 );
		add_action( 'init', array( $this, 'register_team_events_subpage' ), 15 );
		add_action( 'init', array( $this, 'register_player_events_subpage' ), 15 );
		add_action( 'init', array( $this, 'register_group_events_subpage' ), 15 );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'init', array( $this, 'register_blocks' ), 20 );
		add_action( 'admin_post_clanspress_delete_event', array( $this, 'handle_frontend_delete_event' ) );
		add_filter( 'clanspress_event_can_view_attendees', array( $this, 'filter_cp_event_attendee_list_access' ), 15, 4 );
		add_filter( 'clanspress_profile_subpage_nav_visible', array( $this, 'filter_profile_subpage_events_nav_visible' ), 10, 5 );
	}

	/**
	 * Hide the shared `events` subpage tab when it does not apply (owner-only player calendar, per-team flag).
	 *
	 * @param bool   $visible   Prior visibility.
	 * @param string $context   Profile context (`player`, `team`, `group`, …).
	 * @param string $slug      Subpage slug.
	 * @param int    $object_id Subject id for the context.
	 * @param array  $config    Subpage configuration.
	 * @return bool
	 */
	public function filter_profile_subpage_events_nav_visible( bool $visible, string $context, string $slug, int $object_id, array $config ): bool {
		if ( ! $visible || 'events' !== $slug ) {
			return $visible;
		}

		if ( 'player' === $context ) {
			return function_exists( '\clanspress_player_profile_events_subpage_visible_for_viewer' )
				&& \clanspress_player_profile_events_subpage_visible_for_viewer( $object_id );
		}

		if ( 'team' === $context ) {
			return function_exists( '\clanspress_events_are_enabled_for_team' )
				&& \clanspress_events_are_enabled_for_team( $object_id );
		}

		return $visible;
	}

	/**
	 * Register the team profile "Events" tab when Teams uses directory URLs (`/teams/{slug}/events/`).
	 *
	 * @return void
	 */
	public function register_team_events_subpage(): void {
		if ( ! function_exists( 'clanspress_register_team_subpage' ) ) {
			return;
		}

		if ( ! function_exists( 'clanspress_teams_get_team_mode' ) ) {
			return;
		}

		if ( ! function_exists( 'clanspress_events_are_globally_enabled' ) || ! clanspress_events_are_globally_enabled() ) {
			return;
		}

		if ( ! function_exists( '\clanspress_events_subpage_team_enabled' ) || ! \clanspress_events_subpage_team_enabled() ) {
			return;
		}

		if ( 'team_directories' !== clanspress_teams_get_team_mode() ) {
			return;
		}

		clanspress_register_team_subpage(
			'events',
			array(
				'label'    => __( 'Events', 'clanspress' ),
				'position' => 15,
			)
		);
	}

	/**
	 * Register the player profile “Events” tab (`/players/{nicename}/events/`).
	 *
	 * @return void
	 */
	public function register_player_events_subpage(): void {
		if ( ! function_exists( 'clanspress_register_player_subpage' ) ) {
			return;
		}

		if ( ! function_exists( 'clanspress_events_are_globally_enabled' ) || ! clanspress_events_are_globally_enabled() ) {
			return;
		}

		if ( ! function_exists( '\clanspress_events_subpage_player_enabled' ) || ! \clanspress_events_subpage_player_enabled() ) {
			return;
		}

		clanspress_register_player_subpage(
			'events',
			array(
				'label'    => __( 'Events', 'clanspress' ),
				'position' => 20,
			)
		);
	}

	/**
	 * Register the group profile “Events” tab for integrations (`cp_group` + Social Kit).
	 *
	 * @return void
	 */
	public function register_group_events_subpage(): void {
		if ( ! function_exists( 'clanspress_register_group_subpage' ) ) {
			return;
		}

		if ( ! function_exists( 'clanspress_events_are_globally_enabled' ) || ! clanspress_events_are_globally_enabled() ) {
			return;
		}

		if ( ! function_exists( '\clanspress_events_subpage_group_enabled' ) || ! \clanspress_events_subpage_group_enabled() ) {
			return;
		}

		clanspress_register_group_subpage(
			'events',
			array(
				'label'       => __( 'Events', 'clanspress' ),
				'position'    => 15,
				'template_id' => 'clanspress-group-events',
			)
		);
	}

	/**
	 * Add `player-events` to the Players extension FSE templates (before `init` priority 10).
	 *
	 * @return void
	 */
	public function register_cp_players_events_template_filter(): void {
		add_filter( 'clanspress_extension_cp_players_templates', array( $this, 'filter_cp_players_events_template' ), 10, 2 );
	}

	/**
	 * Merge player events subpage template into Players templates.
	 *
	 * @param array<string, array<string, string>>     $templates Template definitions keyed by slug.
	 * @param \Kernowdev\Clanspress\Extensions\Skeleton $extension Calling extension.
	 * @return array<string, array<string, string>>
	 */
	public function filter_cp_players_events_template( array $templates, $extension ): array {
		if ( ! $extension instanceof \Kernowdev\Clanspress\Extensions\Skeleton ) {
			return $templates;
		}

		if ( ! function_exists( 'clanspress_events_are_globally_enabled' ) || ! clanspress_events_are_globally_enabled() ) {
			return $templates;
		}

		if ( ! function_exists( '\clanspress_events_subpage_player_enabled' ) || ! \clanspress_events_subpage_player_enabled() ) {
			return $templates;
		}

		$path = \clanspress()->path . 'templates/players/player-events.html';
		if ( ! is_readable( $path ) ) {
			return $templates;
		}

		$templates['player-events'] = array(
			'title' => __( 'Player events', 'clanspress' ),
			'path'  => $path,
		);

		return $templates;
	}

	/**
	 * Register the group events block template for Site Editor and extensions.
	 *
	 * @return void
	 */
	public function register_group_events_block_template(): void {
		if ( ! function_exists( 'clanspress_events_are_globally_enabled' ) || ! clanspress_events_are_globally_enabled() ) {
			return;
		}

		if ( ! function_exists( '\clanspress_events_subpage_group_enabled' ) || ! \clanspress_events_subpage_group_enabled() ) {
			return;
		}

		if ( ! function_exists( 'register_block_template' ) ) {
			return;
		}

		$path = \clanspress()->path . 'templates/groups/group-events.html';
		if ( ! is_readable( $path ) ) {
			return;
		}

		$content = file_get_contents( $path );
		if ( false === $content ) {
			return;
		}

		register_block_template(
			'clanspress//clanspress-group-events',
			array(
				'title'       => __( 'Group events', 'clanspress' ),
				'description' => __( 'Scheduled events and calendar on a group profile.', 'clanspress' ),
				'content'     => $content,
			)
		);
	}

	/**
	 * Allow team/group members to see hidden attendee lists for `cp_event` RSVPs.
	 *
	 * @param bool   $can_view_attendees Prior visibility.
	 * @param string $event_type         RSVP event type slug.
	 * @param int    $event_id           Event object ID (`cp_event` post ID).
	 * @param int    $viewer_id          Viewer user ID.
	 * @return bool
	 */
	public function filter_cp_event_attendee_list_access( bool $can_view_attendees, string $event_type, int $event_id, int $viewer_id ): bool {
		if ( 'clanspress_event' !== $event_type ) {
			return $can_view_attendees;
		}
		if ( $can_view_attendees ) {
			return true;
		}
		$post = get_post( $event_id );
		if ( ! ( $post instanceof \WP_Post ) || Event_Post_Type::POST_TYPE !== $post->post_type ) {
			return false;
		}
		return Event_Permissions::can_view_attendees( $post, $viewer_id );
	}

	/**
	 * Register core event blocks (RSVP UI, etc.).
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		$base = \clanspress()->path . 'build/events/';
		foreach ( array( 'event-rsvp', 'event-list', 'event-detail', 'event-create-form', 'event-calendar' ) as $dir ) {
			$path = $base . $dir;
			if ( is_dir( $path ) ) {
				register_block_type( $path );
			}
		}
	}

	/**
	 * Create tables if needed.
	 *
	 * @return void
	 */
	public function maybe_create_tables(): void {
		Event_Rsvp_Schema::maybe_upgrade();
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		( new Event_Rsvp_Rest_Controller() )->register_routes();
		( new Event_Entity_Rest_Controller() )->register_routes();
	}

	/**
	 * Trash an event from a front-end POST (event detail block).
	 *
	 * @return void
	 */
	public function handle_frontend_delete_event(): void {
		if ( ! is_user_logged_in() ) {
			$after_login = wp_get_referer();
			if ( ! is_string( $after_login ) || '' === $after_login ) {
				$after_login = home_url( '/' );
			}
			wp_safe_redirect( wp_login_url( $after_login ) );
			exit;
		}

		$event_id = isset( $_POST['clanspress_event_id'] ) ? absint( wp_unslash( $_POST['clanspress_event_id'] ) ) : 0;
		if ( $event_id < 1 ) {
			wp_die( esc_html__( 'Invalid request.', 'clanspress' ), '', array( 'response' => 400 ) );
		}

		check_admin_referer( 'clanspress_delete_event_' . $event_id );

		if ( ! Event_Permissions::user_can_manage_event( $event_id, (int) get_current_user_id() ) ) {
			wp_die( esc_html__( 'You cannot delete this event.', 'clanspress' ), '', array( 'response' => 403 ) );
		}

		$post = get_post( $event_id );
		if ( ! ( $post instanceof \WP_Post ) || Event_Post_Type::POST_TYPE !== $post->post_type ) {
			wp_die( esc_html__( 'Event not found.', 'clanspress' ), '', array( 'response' => 404 ) );
		}

		wp_trash_post( $event_id );

		$redirect = isset( $_POST['clanspress_event_delete_redirect'] )
			? esc_url_raw( wp_unslash( $_POST['clanspress_event_delete_redirect'] ) )
			: '';
		if ( '' === $redirect ) {
			$redirect = home_url( '/' );
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Check whether a viewer is allowed to see an event at all.
	 *
	 * Default behavior:
	 * - `match`: defer to Matches extension visibility checks when available.
	 * - otherwise: allow.
	 *
	 * Extensions should filter this for their own event types.
	 *
	 * @param string $event_type Event type slug (e.g. `match`, `group_event`).
	 * @param int    $event_id   Event object ID.
	 * @param int    $viewer_id  Viewer user ID (0 for anon).
	 * @return bool
	 */
	public static function viewer_can_see_event( string $event_type, int $event_id, int $viewer_id ): bool {
		$event_type = sanitize_key( $event_type );
		$event_id   = absint( $event_id );
		$viewer_id  = absint( $viewer_id );

		$can = true;

		if ( 'match' === $event_type && function_exists( 'clanspress_matches' ) ) {
			$matches = clanspress_matches();
			if ( $matches instanceof \Kernowdev\Clanspress\Extensions\Matches ) {
				$post = get_post( $event_id );
				if ( $post instanceof \WP_Post ) {
					$can = (bool) $matches->viewer_can_see_match( $post, $viewer_id );
				}
			}
		}

		if ( 'clanspress_event' === $event_type ) {
			$post = get_post( $event_id );
			if ( $post instanceof \WP_Post && Event_Post_Type::POST_TYPE === $post->post_type ) {
				$can = Event_Permissions::viewer_can_see( $post, $viewer_id );
			} else {
				$can = false;
			}
		}

		/**
		 * Filter whether the viewer can see an event.
		 *
		 * Use this to enforce visibility rules for custom event types (e.g. Groups).
		 *
		 * @param bool   $can        Whether the viewer can see the event.
		 * @param string $event_type Event type slug.
		 * @param int    $event_id   Event ID.
		 * @param int    $viewer_id  Viewer user ID (0 for anon).
		 */
		return (bool) apply_filters( 'clanspress_event_viewer_can_see', $can, $event_type, $event_id, $viewer_id );
	}

	/**
	 * Get event attendee list visibility.
	 *
	 * Returned value is one of:
	 * - `public`: anyone who can see the event can see the attendee list.
	 * - `hidden`: attendee list is hidden (except for privileged viewers via filters).
	 *
	 * @param string $event_type Event type slug.
	 * @param int    $event_id   Event ID.
	 * @return string `public` or `hidden`.
	 */
	public static function get_attendees_visibility( string $event_type, int $event_id ): string {
		$event_type = sanitize_key( $event_type );
		$event_id   = absint( $event_id );

		$visibility = 'hidden';

		if ( 'match' === $event_type ) {
			$raw        = (string) get_post_meta( $event_id, 'cp_match_attendees_visibility', true );
			$visibility = in_array( $raw, array( 'public', 'hidden' ), true ) ? $raw : 'hidden';
		}

		if ( 'clanspress_event' === $event_type ) {
			$raw        = (string) get_post_meta( $event_id, 'cp_event_attendees_visibility', true );
			$visibility = in_array( $raw, array( 'public', 'hidden' ), true ) ? $raw : 'hidden';
		}

		/**
		 * Filter attendee visibility for an event.
		 *
		 * @param string $visibility `public` or `hidden`.
		 * @param string $event_type Event type slug.
		 * @param int    $event_id   Event ID.
		 */
		$visibility = (string) apply_filters( 'clanspress_event_attendees_visibility', $visibility, $event_type, $event_id );

		return in_array( $visibility, array( 'public', 'hidden' ), true ) ? $visibility : 'hidden';
	}
}

