<?php
/**
 * Notifications subsystem bootstrap (hooks, blocks, REST registration).
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Extensions\Notification;
defined( 'ABSPATH' ) || exit;

/**
 * Registers WordPress hooks for the Notifications extension runtime.
 */
final class Notifications_Runtime {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

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
		add_action( 'init', array( $this, 'register_cp_players_template_filter' ), 3 );
		add_action( 'init', array( $this, 'maybe_create_tables' ), 5 );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'init', array( $this, 'register_notifications_shortcode' ), 15 );
		add_action( 'init', array( $this, 'register_blocks' ), 20 );
		add_action( 'init', array( $this, 'register_notifications_subpage' ), 20 );
		add_action( 'template_redirect', array( $this, 'maybe_redirect_guest_from_notifications_subpage' ), 5 );

		add_action( 'delete_user', array( $this, 'on_user_deleted' ) );
		add_action( 'clanspress_team_deleted', array( $this, 'on_team_deleted' ) );
	}

	/**
	 * Register before Players `init` (priority 10) registers block templates.
	 *
	 * @return void
	 */
	public function register_cp_players_template_filter(): void {
		add_filter( 'clanspress_extension_cp_players_templates', array( $this, 'filter_cp_players_templates' ), 10, 2 );
	}

	/**
	 * Add `player-notifications` to the Players extension FSE templates.
	 *
	 * @param array<string, array<string, string>> $templates Template definitions keyed by slug.
	 * @param \Kernowdev\Clanspress\Extensions\Skeleton $extension Calling extension (Players).
	 * @return array<string, array<string, string>>
	 */
	public function filter_cp_players_templates( array $templates, $extension ): array {
		if ( ! $extension instanceof \Kernowdev\Clanspress\Extensions\Skeleton ) {
			return $templates;
		}

		if ( ! function_exists( 'clanspress_notifications_extension_active' ) || ! clanspress_notifications_extension_active() ) {
			return $templates;
		}

		if ( ! function_exists( '\clanspress_notifications_subpage_player_enabled' ) || ! \clanspress_notifications_subpage_player_enabled() ) {
			return $templates;
		}

		$path = \clanspress()->path . 'templates/players/player-notifications.html';
		if ( ! is_readable( $path ) ) {
			return $templates;
		}

		$templates['player-notifications'] = array(
			'title' => __( 'Player notifications', 'clanspress' ),
			'path'  => $path,
		);

		return $templates;
	}

	/**
	 * Register shortcode used by `player-notifications.html`.
	 *
	 * @return void
	 */
	public function register_notifications_shortcode(): void {
		if ( function_exists( 'clanspress_player_notifications_shortcode' ) ) {
			add_shortcode( 'clanspress_player_notifications', 'clanspress_player_notifications_shortcode' );
		}
	}

	/**
	 * Require login for `/players/{nicename}/notifications/` (same behaviour as the former subpage template).
	 *
	 * @return void
	 */
	public function maybe_redirect_guest_from_notifications_subpage(): void {
		if ( ! function_exists( 'clanspress_notifications_extension_active' ) || ! clanspress_notifications_extension_active() ) {
			return;
		}

		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( ! is_author() ) {
			return;
		}

		if ( 'notifications' !== sanitize_key( (string) get_query_var( 'cp_player_subpage' ) ) ) {
			return;
		}

		if ( is_user_logged_in() ) {
			return;
		}

		$req_uri = clanspress_sanitize_request_uri( '' );
		$path    = wp_parse_url( $req_uri, PHP_URL_PATH );
		$path    = is_string( $path ) ? $path : '';
		$redirect_to = '' !== $path ? home_url( $path ) : home_url( '/' );
		$redirect_to = wp_validate_redirect( $redirect_to, home_url( '/' ) );

		wp_safe_redirect( wp_login_url( $redirect_to ) );
		exit;
	}

	/**
	 * Create tables if needed.
	 *
	 * @return void
	 */
	public function maybe_create_tables(): void {
		Notification_Schema::maybe_upgrade();
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		( new Notification_Rest_Controller() )->register_routes();
	}

	/**
	 * Register the notification bell block.
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		$block_path = \clanspress()->path . 'build/notifications/notification-bell';
		if ( is_dir( $block_path ) ) {
			register_block_type( $block_path );
		}
	}

	/**
	 * Register the notifications player subpage.
	 *
	 * @return void
	 */
	public function register_notifications_subpage(): void {
		if ( ! function_exists( 'clanspress_register_player_subpage' ) ) {
			return;
		}

		if ( ! function_exists( '\clanspress_notifications_subpage_player_enabled' ) || ! \clanspress_notifications_subpage_player_enabled() ) {
			return;
		}

		clanspress_register_player_subpage(
			'notifications',
			array(
				'label'    => __( 'Notifications', 'clanspress' ),
				'position' => 80,
			)
		);
	}

	/**
	 * Clean up when user is deleted.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function on_user_deleted( int $user_id ): void {
		Notification_Data_Access::delete_all_for_user( $user_id );
	}

	/**
	 * Clean up when team is deleted.
	 *
	 * @param int $team_id Team ID.
	 * @return void
	 */
	public function on_team_deleted( int $team_id ): void {
		Notification_Data_Access::delete_by_object( 'team', $team_id );
	}
}
