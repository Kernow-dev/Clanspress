<?php

namespace Kernowdev\Clanspress\Extensions;

use Kernowdev\Clanspress\Main;
use Kernowdev\Clanspress\Extensions\Abstract_Settings;
use Kernowdev\Clanspress\Extensions\Teams\Admin;
use Kernowdev\Clanspress\Extensions\Teams\Team;
use Kernowdev\Clanspress\Extensions\Teams\Team_Challenges;
use Kernowdev\Clanspress\Extensions\Teams\Team_Data_Store;
use Kernowdev\Clanspress\Extensions\Teams\Team_Data_Store_CPT;

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../data-stores/class-wp-post-meta-data-store.php';
require_once __DIR__ . '/class-team-data-store-cpt.php';
require_once __DIR__ . '/class-team-challenges.php';

/**
 * Extension skeleton.
 *
 * This skeleton outlines the requirements for an extension for the Clanspress plugin.
 * All extensions will need to extend this class.
 */
class Teams extends Skeleton {
	public const TEAM_ROLE_ADMIN  = 'admin';
	public const TEAM_ROLE_EDITOR = 'editor';
	public const TEAM_ROLE_MEMBER = 'member';
	public const TEAM_ROLE_BANNED = 'banned';

	/**
	 * Slug for the virtual `wp_template_part` resolved from `templates/teams/parts/team-profile-header.html`.
	 */
	public const TEAM_PROFILE_HEADER_TEMPLATE_PART_SLUG = 'clanspress-team-profile-header';

	/**
	 * Cached {@see \WP_Block_Template} for {@see Teams::TEAM_PROFILE_HEADER_TEMPLATE_PART_SLUG} (per request).
	 *
	 * @var \WP_Block_Template|null
	 */
	protected static ?\WP_Block_Template $team_profile_header_template_part_cache = null;

	/**
	 * Teams admin settings manager.
	 *
	 * @var Admin
	 */
	protected Admin $admin;

	/**
	 * Team entity persistence (filtered via `clanspress_team_data_store`).
	 *
	 * @var Team_Data_Store|null
	 */
	protected ?Team_Data_Store $team_data_store = null;

	/**
	 * Sets up our extension loader.
	 */
	public function __construct() {
		parent::__construct(
			'Teams',
			'cp_teams',
			'Adds team functionality.',
			'',
			'1.0.0',
			array( 'cp_players' )
		);
	}

	/**
	 * Setup and validate extension values.
	 *
	 * @param string $name        The human-readable name of the extension.
	 * @param string $slug        The extensions slug.
	 * @param string $description The extensions description.
	 * @param string $parent_slug The slug of the parent extension.
	 * @param string $version     The extension version.
	 * @param array  $requires    An array of required extensions.
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

		// Built-in extensions register as official, not third-party.
		remove_filter( 'clanspress_registered_extensions', array( $this, 'register_extension' ) );
		add_filter( 'clanspress_official_registered_extensions', array( $this, 'register_extension' ) );
	}

	public function run_installer(): void {}

	public function run_uninstaller(): void {}

	public function run_updater(): void {}

	public function run(): void {
		$this->admin = new Admin();

		$team_mode = $this->get_team_mode();

		/**
		 * Fires after teams mode has been resolved.
		 *
		 * @param string $team_mode Team mode slug.
		 * @param Teams  $extension Teams extension instance.
		 */
		do_action( 'clanspress_teams_mode_loaded', $team_mode, $this );

		/**
		 * Dynamic teams mode hook for mode-specific boot behavior.
		 *
		 * @param Teams $extension Teams extension instance.
		 */
		do_action( "clanspress_teams_mode_{$team_mode}", $this );

		// After `cp_team` rewrites register (init:10) so `teams/create` wins over `teams/([^/]+)`.
		add_filter( 'pre_get_block_file_template', array( $this, 'filter_pre_get_block_file_template_team_profile_header' ), 10, 3 );
		add_filter( 'get_block_templates', array( $this, 'filter_get_block_templates_include_team_profile_header' ), 10, 3 );
		add_action( 'init', array( $this, 'register_team_front_routes' ), 15 );
		add_action( 'init', array( $this, 'register_team_post_type' ), 10 );
		add_action( 'init', array( $this, 'register_team_meta' ), 10 );
		add_action( 'init', array( $this, 'register_team_blocks' ), 10 );
		add_action( 'init', array( $this, 'register_team_templates' ), 10 );
		add_action( 'admin_post_clanspress_create_team', array( $this, 'handle_create_team' ) );
		add_action( 'admin_post_nopriv_clanspress_create_team', array( $this, 'handle_create_team' ) );
		add_action( 'admin_post_clanspress_save_team_manage', array( $this, 'handle_save_team_manage' ) );
		add_action( 'admin_post_nopriv_clanspress_save_team_manage', array( $this, 'handle_save_team_manage_nopriv' ) );
		add_action( 'admin_post_clanspress_delete_team', array( $this, 'handle_delete_team' ) );
		add_action( 'admin_post_nopriv_clanspress_delete_team', array( $this, 'handle_delete_team_nopriv' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_team_editor_assets' ) );
		add_action( 'wp_ajax_clanspress_team_invite_search', array( $this, 'ajax_team_invite_search' ) );

		// Register notification action handlers for team invites.
		add_filter( 'clanspress_notification_action_handler', array( $this, 'handle_notification_actions' ), 10, 5 );
		add_action( 'init', array( $this, 'integrate_team_preferences' ), 5 );
		add_filter( 'query_vars', array( $this, 'register_team_query_vars' ) );
		add_filter( 'request', array( $this, 'filter_request_for_team_virtual_pages' ), 99 );
		add_action( 'parse_query', array( $this, 'parse_query_for_team_virtual_pages' ) );
		add_filter( 'posts_pre_query', array( $this, 'posts_pre_query_team_virtual_pages' ), 10, 2 );
		add_filter( 'template_include', array( $this, 'maybe_load_team_virtual_templates' ), 100 );
		add_action( 'wp', array( $this, 'set_plugin_block_template_id_for_site_editor' ), 99 );
		add_action( 'template_redirect', array( $this, 'maybe_fix_team_create_route_404' ), 0 );
		add_action( 'template_redirect', array( $this, 'maybe_fix_team_events_route_404' ), 0 );
		add_action( 'template_redirect', array( $this, 'maybe_block_banned_team_access' ), 5 );
		add_filter( 'pre_render_block', array( $this, 'prime_cp_team_single_loop_for_plugin_template' ), 0, 3 );
		add_filter( 'render_block_context', array( $this, 'filter_team_singular_block_context' ), 10, 3 );
		add_filter( 'get_block_templates', array( $this, 'prefer_plugin_single_cp_team_block_template' ), 100, 3 );
		add_filter( 'single_template', array( $this, 'maybe_single_team_template' ) );
		add_filter( 'rest_prepare_cp_team', array( $this, 'rest_prepare_cp_team_merge_meta' ), 10, 3 );
		add_action( 'add_meta_boxes', array( $this, 'add_team_events_meta_box' ) );
		add_action( 'save_post_cp_team', array( $this, 'save_team_events_meta_box' ), 10, 2 );
		add_filter( 'map_meta_cap', array( $this, 'map_team_front_edit_meta_cap' ), 10, 4 );
		add_filter( 'wp_unique_post_slug', array( $this, 'reserve_team_route_slugs' ), 10, 6 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_team_manage_form_assets' ), 20 );

		Team_Challenges::instance()->register();
	}

	/**
	 * Team data store instance (swappable).
	 *
	 * @return Team_Data_Store
	 */
	public function get_team_data_store(): Team_Data_Store {
		if ( null === $this->team_data_store ) {
			$default = new Team_Data_Store_CPT( $this );
			/**
			 * Filter team persistence implementation.
			 *
			 * @param Team_Data_Store $store     Default CPT-backed store.
			 * @param Teams           $extension Teams extension instance.
			 */
			$store                 = apply_filters( 'clanspress_team_data_store', $default, $this );
			$this->team_data_store = $store instanceof Team_Data_Store ? $store : $default;
		}

		return $this->team_data_store;
	}

	/**
	 * Hydrate a {@see Team} from storage.
	 *
	 * @param int $id Team post ID.
	 * @return Team|null
	 */
	public function get_team( int $id ): ?Team {
		return $this->get_team_data_store()->read( $id );
	}

	/**
	 * Let team admins/editors update cp_team from the front-end manage form.
	 *
	 * @param array  $caps    Primitive caps for the user.
	 * @param string $cap     Capability name.
	 * @param int    $user_id User ID.
	 * @param array  $args    Extra args (post ID for edit_post).
	 * @return array
	 */
	public function map_team_front_edit_meta_cap( array $caps, string $cap, $user_id, array $args ): array {
		if ( 'edit_post' !== $cap || empty( $args ) ) {
			return $caps;
		}

		$user_id = (int) $user_id;
		$post_id = (int) $args[0];
		$post    = get_post( $post_id );
		if ( ! $post || 'cp_team' !== $post->post_type ) {
			return $caps;
		}

		if ( ! $this->user_can_manage_team_on_frontend( $post_id, $user_id ) ) {
			return $caps;
		}

		if ( ! $this->user_is_teams_site_admin( $user_id ) && ! $this->can_edit_team_frontend( $post_id ) ) {
			return $caps;
		}

		return array( 'read' );
	}

	/**
	 * Avoid team slugs that collide with the top-level /teams/create/ route.
	 *
	 * @param string $slug        Post slug.
	 * @param int    $post_id     Post ID.
	 * @param string $post_status Post status.
	 * @param string $post_type   Post type.
	 * @param int    $post_parent Parent post ID.
	 * @param string $original    Original slug.
	 * @return string
	 */
	public function reserve_team_route_slugs( string $slug, $post_id, $post_status, $post_type, $post_parent, $original ): string {
		unset( $post_status, $post_parent, $original );
		if ( 'cp_team' !== $post_type ) {
			return $slug;
		}

		if ( 'create' === $slug ) {
			return $slug . '-team';
		}

		return $slug;
	}

	/**
	 * Register user meta and (when Players profiles are on) player settings UI for team invites.
	 *
	 * @return void
	 */
	public function integrate_team_preferences(): void {
		register_meta(
			'user',
			'cp_open_to_team_invites',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'show_in_rest'      => true,
				'default'           => true,
				'auth_callback'     => static function ( $allowed, $meta_key, $user_id ) {
					unset( $allowed, $meta_key );
					$uid = get_current_user_id();
					$uid = (int) $uid;
					$oid = (int) $user_id;
					return $uid === $oid || current_user_can( 'edit_user', $oid );
				},
			)
		);

		$loader = clanspress()->extensions;
		if ( null === $loader ) {
			return;
		}

		$players = $loader->get( 'cp_players' );
		if ( ! $players instanceof Players ) {
			return;
		}

		if ( ! $players->get_setting( 'enable_profiles' ) ) {
			return;
		}

		add_filter( 'clanspress_players_settings_nav_items', array( $this, 'register_teams_player_settings_nav' ), 20 );
		add_filter( 'clanspress_players_settings_nav_teams_sub_items', array( $this, 'register_teams_player_settings_sub_nav' ) );
		add_action( 'clanspress_player_settings_panel_teams-preferences', array( $this, 'render_player_team_preferences_panel' ) );
		add_action( 'clanspress_save_player_settings', array( $this, 'save_player_team_preferences' ), 15, 4 );
	}

	/**
	 * @param array<string, array<string, string>> $items Nav items.
	 * @return array<string, array<string, string>>
	 */
	public function register_teams_player_settings_nav( array $items ): array {
		$items['teams'] = array(
			'label'       => __( 'Teams', 'clanspress' ),
			'description' => __( 'Invites and team visibility', 'clanspress' ),
		);

		return $items;
	}

	/**
	 * @param array<string, array<string, string>> $items Sub nav items.
	 * @return array<string, array<string, string>>
	 */
	public function register_teams_player_settings_sub_nav( array $items ): array {
		$items['teams-preferences'] = array(
			'label' => __( 'Preferences', 'clanspress' ),
		);

		return $items;
	}

	/**
	 * Front-end player settings: team invite preference.
	 *
	 * @return void
	 */
	public function render_player_team_preferences_panel(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$open = $this->user_accepts_team_invites( $user_id );
		?>
		<div class="settings-section">
			<h2 class="settings-section-title"><?php esc_html_e( 'Team invites', 'clanspress' ); ?></h2>
			<div class="settings-section-row">
				<div class="form-item">
					<div class="form-input">
						<label for="clanspress-open-to-team-invites">
							<input
								type="checkbox"
								id="clanspress-open-to-team-invites"
								name="open_to_team_invites"
								value="1"
								<?php checked( $open ); ?>
							/>
							<?php esc_html_e( 'Allow team invites', 'clanspress' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'If you turn this off, you will not appear in team invite search when captains add players to a new team.', 'clanspress' ); ?>
						</p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Persist team invite preference from player settings save.
	 *
	 * @param array $filtered_data Sanitized-oriented POST data.
	 * @param array $data          Raw POST.
	 * @param array $files         Uploaded files.
	 * @param int   $user_id       User ID.
	 * @return void
	 */
	public function save_player_team_preferences( $filtered_data, $data, $files, $user_id ): void {
		if ( ! isset( $filtered_data['open_to_team_invites'] ) ) {
			return;
		}

		$open = rest_sanitize_boolean( $filtered_data['open_to_team_invites'] );
		update_user_meta( (int) $user_id, 'cp_open_to_team_invites', $open ? '1' : '0' );
	}

	/**
	 * Global setting: players may belong to more than one team.
	 *
	 * @return bool
	 */
	public function allows_multiple_team_memberships(): bool {
		$mode = sanitize_key( (string) $this->get_setting( 'player_team_membership', 'multiple' ) );

		return 'single' !== $mode;
	}

	/**
	 * Count team associations for a user (default: teams they author).
	 *
	 * Third parties can raise this count via filter when they track membership elsewhere.
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	public function get_user_team_membership_count( int $user_id ): int {
		$indexed = get_user_meta( $user_id, 'cp_team_membership_ids', true );
		if ( is_array( $indexed ) && array() !== $indexed ) {
			$count = 0;
			foreach ( $indexed as $team_id ) {
				$team_id = (int) $team_id;
				$role    = $this->get_team_member_role( $team_id, $user_id );
				if ( $role && self::TEAM_ROLE_BANNED !== $role ) {
					++$count;
				}
			}
		} else {
			$count = (int) count_user_posts( $user_id, 'cp_team', true );
		}

		/**
		 * Filter how many team memberships count toward global limits.
		 *
		 * @param int   $count   Membership count.
		 * @param int   $user_id User ID.
		 * @param Teams $extension Teams extension.
		 */
		return (int) apply_filters( 'clanspress_user_team_membership_count', $count, $user_id, $this );
	}

	/**
	 * Whether the user accepts unsolicited team invites (player setting).
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public function user_accepts_team_invites( int $user_id ): bool {
		$raw = get_user_meta( $user_id, 'cp_open_to_team_invites', true );
		if ( '' === $raw || null === $raw ) {
			$accepts = true;
		} else {
			$accepts = rest_sanitize_boolean( $raw );
		}

		/**
		 * Filter whether a user accepts team invites.
		 *
		 * @param bool  $accepts Whether invites are accepted.
		 * @param int   $user_id User ID.
		 * @param Teams $extension Teams extension.
		 */
		return (bool) apply_filters( 'clanspress_user_accepts_team_invites', $accepts, $user_id, $this );
	}

	/**
	 * Whether the user already has the maximum teams allowed by global settings.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public function user_at_team_membership_limit( int $user_id ): bool {
		if ( $this->allows_multiple_team_memberships() ) {
			return false;
		}

		return $this->get_user_team_membership_count( $user_id ) >= 1;
	}

	/**
	 * Whether a user should appear in team invite autocomplete / pending lists.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public function user_can_appear_in_team_invite_search( int $user_id ): bool {
		$can = $this->user_accepts_team_invites( $user_id )
			&& ! $this->user_at_team_membership_limit( $user_id );

		/**
		 * Filter final invite-search visibility for a user.
		 *
		 * @param bool  $can     Whether to include the user.
		 * @param int   $user_id User ID.
		 * @param Teams $extension Teams extension.
		 */
		return (bool) apply_filters( 'clanspress_user_can_appear_in_team_invite_search', $can, $user_id, $this );
	}

	/**
	 * Action slugs used for `teams/{slug}/{action}/` rewrite registration (may omit `events` when the Events extension is off).
	 *
	 * @return list<string>
	 */
	protected function get_team_front_action_slugs_for_rewrites(): array {
		$actions = array_keys( $this->get_team_front_action_rewrite_slugs() );
		if ( ! $this->events_extension_is_active() ) {
			$actions = array_values( array_diff( $actions, array( 'events' ) ) );
		}

		return $actions;
	}

	/**
	 * Whether the Events extension is enabled so team event routes and templates should resolve.
	 *
	 * @return bool
	 */
	protected function events_extension_is_active(): bool {
		return function_exists( 'clanspress_events_extension_active' ) && clanspress_events_extension_active();
	}

	/**
	 * Front routes (BuddyPress-style: component / item / action).
	 *
	 * - /teams/create/ — global create (no team context).
	 * - /teams/{slug}/manage/ — team-scoped action (more actions can be registered later).
	 *
	 * @return void
	 */
	public function register_team_front_routes(): void {
		add_rewrite_rule( '^teams/create/?$', 'index.php?clanspress_team_create=1', 'top' );

		$actions = $this->get_team_front_action_slugs_for_rewrites();
		foreach ( $actions as $action_slug ) {
			$action_slug = sanitize_key( (string) $action_slug );
			if ( '' === $action_slug || 'create' === $action_slug ) {
				continue;
			}
			add_rewrite_rule(
				'^teams/([^/]+)/' . preg_quote( $action_slug, '/' ) . '/?$',
				'index.php?clanspress_team_slug=$matches[1]&clanspress_team_action=' . $action_slug,
				'top'
			);
		}

		if ( $this->events_extension_is_active() ) {
			// teams/{slug}/events/create/ — add event (before numeric event ID rule).
			add_rewrite_rule(
				'^teams/([^/]+)/events/create/?$',
				'index.php?clanspress_team_slug=$matches[1]&clanspress_team_action=events&clanspress_team_events_sub=create',
				'top'
			);

			// teams/{slug}/events/{id}/ — single scheduled event (must be registered after the generic …/events/ rule).
			add_rewrite_rule(
				'^teams/([^/]+)/events/([0-9]+)/?$',
				'index.php?clanspress_team_slug=$matches[1]&clanspress_team_action=events&clanspress_team_event_id=$matches[2]',
				'top'
			);
		}

		// Back-compat: /teams/manage/{slug}/ resolves like /teams/{slug}/manage/.
		add_rewrite_rule(
			'^teams/manage/([^/]+)/?$',
			'index.php?clanspress_team_slug=$matches[1]&clanspress_team_action=manage',
			'top'
		);
	}

	/**
	 * Action slugs that receive a dedicated teams/{slug}/{action}/ rewrite rule.
	 *
	 * @return array<string, string> Slug => human label (label for filters/docs only).
	 */
	public function get_team_front_action_rewrite_slugs(): array {
		$actions = array(
			'manage' => __( 'Manage', 'clanspress' ),
			'events' => __( 'Events', 'clanspress' ),
		);

		/**
		 * Register additional team front actions (BuddyPress-style URLs).
		 *
		 * Each key becomes a rewrite segment: teams/{team_slug}/{key}/
		 * Handle routing in `clanspress_team_action_dispatch` or template_include.
		 *
		 * @param array $actions Slug => label.
		 * @param Teams $extension Teams extension.
		 */
		return (array) apply_filters( 'clanspress_team_front_action_rewrite_slugs', $actions, $this );
	}

	/**
	 * @param array<int|string, string> $vars Query vars.
	 * @return array<int|string, string>
	 */
	public function register_team_query_vars( array $vars ): array {
		$vars[] = 'clanspress_team_create';
		$vars[] = 'clanspress_team_action';
		$vars[] = 'clanspress_team_slug';
		$vars[] = 'clanspress_manage_team_id';
		$vars[] = 'clanspress_team_event_id';
		$vars[] = 'clanspress_team_events_sub';
		$vars[] = 'clanspress_events_team_id';
		$vars[] = 'cp_team_subpage';
		return $vars;
	}

	/**
	 * Request path after the home URL path (no leading/trailing slashes).
	 *
	 * @return string
	 */
	protected function get_canonical_request_path(): string {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return '';
		}

		$uri  = wp_unslash( $_SERVER['REQUEST_URI'] );
		$path = wp_parse_url( $uri, PHP_URL_PATH );
		if ( ! is_string( $path ) ) {
			return '';
		}

		$path = rawurldecode( $path );

		$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$home_path = is_string( $home_path ) ? $home_path : '';

		if ( '' !== $home_path && '/' !== $home_path ) {
			$home_trim = untrailingslashit( $home_path );
			if ( str_starts_with( $path, $home_trim ) ) {
				$path = substr( $path, strlen( $home_trim ) );
			}
		}

		$path = ltrim( $path, '/' );
		$path = preg_replace( '#^index\.php/?#i', '', $path );

		return trim( (string) $path, '/' );
	}

	/**
	 * Remove query vars that would resolve to a post/page and conflict with virtual team routes.
	 *
	 * @param array<string, mixed> $query_vars Query variables.
	 * @return array<string, mixed>
	 */
	protected function strip_conflicting_query_vars_for_team_virtual_routes( array $query_vars ): array {
		foreach ( array( 'pagename', 'name', 'page_id', 'p', 'attachment', 'attachment_id', 'year', 'monthnum', 'day', 'feed', 'post_type', 'cp_team', 'error' ) as $key ) {
			unset( $query_vars[ $key ] );
		}

		return $query_vars;
	}

	/**
	 * Force team virtual query vars from the URL when rewrite rules did not match (flush/order issues).
	 *
	 * Merges into existing `$query_vars` — replacing the entire array breaks core query resolution and can yield 404s.
	 *
	 * @param array<string, mixed> $query_vars Query variables.
	 * @return array<string, mixed>
	 */
	public function filter_request_for_team_virtual_pages( array $query_vars ): array {
		if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $query_vars;
		}

		if ( ! empty( $query_vars['clanspress_team_action'] ) || ! empty( $query_vars['clanspress_team_create'] ) ) {
			return $query_vars;
		}

		$path = $this->get_canonical_request_path();
		if ( '' === $path ) {
			return $query_vars;
		}

		if ( 'teams/create' === $path || str_starts_with( $path, 'teams/create/' ) ) {
			$query_vars['clanspress_team_create'] = '1';

			return $this->strip_conflicting_query_vars_for_team_virtual_routes( $query_vars );
		}

		if ( preg_match( '#^teams/manage/([^/]+)/?$#', $path, $m ) ) {
			$query_vars['clanspress_team_slug']   = $m[1];
			$query_vars['clanspress_team_action'] = 'manage';

			return $this->strip_conflicting_query_vars_for_team_virtual_routes( $query_vars );
		}

		if ( $this->events_extension_is_active() ) {
			if ( preg_match( '#^teams/([^/]+)/events/create/?$#', $path, $m ) ) {
				$query_vars['clanspress_team_slug']       = $m[1];
				$query_vars['clanspress_team_action']     = 'events';
				$query_vars['clanspress_team_events_sub'] = 'create';

				return $this->strip_conflicting_query_vars_for_team_virtual_routes( $query_vars );
			}

			if ( preg_match( '#^teams/([^/]+)/events/([0-9]+)/?$#', $path, $m ) ) {
				$query_vars['clanspress_team_slug']     = $m[1];
				$query_vars['clanspress_team_action']   = 'events';
				$query_vars['clanspress_team_event_id'] = $m[2];

				return $this->strip_conflicting_query_vars_for_team_virtual_routes( $query_vars );
			}
		}

		$actions = $this->get_team_front_action_slugs_for_rewrites();
		foreach ( $actions as $action_slug ) {
			$action_slug = sanitize_key( (string) $action_slug );
			if ( '' === $action_slug || 'create' === $action_slug ) {
				continue;
			}
			$quoted = preg_quote( $action_slug, '#' );
			if ( preg_match( '#^teams/([^/]+)/' . $quoted . '/?$#', $path, $m ) ) {
				$query_vars['clanspress_team_slug']   = $m[1];
				$query_vars['clanspress_team_action'] = $action_slug;

				return $this->strip_conflicting_query_vars_for_team_virtual_routes( $query_vars );
			}
		}

		return $query_vars;
	}

	/**
	 * If core still marked /teams/create as 404, recover before templates load (rewrite flush / ordering edge cases).
	 * Does not depend on team directory mode; create is always available when Teams is enabled.
	 *
	 * @return void
	 */
	public function maybe_fix_team_create_route_404(): void {
		if ( ! is_404() ) {
			return;
		}

		$path = $this->get_canonical_request_path();
		if ( 'teams/create' !== $path && ! str_starts_with( $path, 'teams/create/' ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( $this->get_team_create_url() ) );
			exit;
		}

		global $wp_query;

		status_header( 200 );
		nocache_headers();
		$wp_query->is_404              = false;
		$wp_query->is_home             = false;
		$wp_query->is_front_page       = false;
		$wp_query->is_posts_page       = false;
		$wp_query->is_page             = false;
		$wp_query->is_singular         = false;
		$wp_query->is_single           = false;
		$wp_query->is_archive         = false;
		$wp_query->is_post_type_archive = false;
		$wp_query->found_posts         = 0;
		$wp_query->max_num_pages       = 0;
		$wp_query->posts               = array();

		set_query_var( 'clanspress_team_create', '1' );
	}

	/**
	 * If core still marked /teams/{slug}/events/ as 404, recover before templates load (rewrite flush / ordering edge cases).
	 *
	 * Does not require team directory mode (unlike {@see maybe_fix_team_create_route_404}); events list URLs are public.
	 *
	 * @return void
	 */
	public function maybe_fix_team_events_route_404(): void {
		if ( ! is_404() ) {
			return;
		}

		if ( ! $this->events_extension_is_active() ) {
			return;
		}

		$path       = $this->get_canonical_request_path();
		$slug       = '';
		$event_id   = 0;
		$events_sub = '';

		if ( preg_match( '#^teams/([^/]+)/events/([0-9]+)/?$#', $path, $m ) ) {
			$slug     = $m[1];
			$event_id = (int) $m[2];
		} elseif ( preg_match( '#^teams/([^/]+)/events/create/?$#', $path, $m ) ) {
			$slug       = $m[1];
			$events_sub = 'create';
		} elseif ( preg_match( '#^teams/([^/]+)/events/?$#', $path, $m ) ) {
			$slug = $m[1];
		} else {
			return;
		}

		global $wp_query;

		status_header( 200 );
		nocache_headers();
		$wp_query->is_404              = false;
		$wp_query->is_home             = false;
		$wp_query->is_front_page       = false;
		$wp_query->is_posts_page       = false;
		$wp_query->is_page             = false;
		$wp_query->is_singular         = false;
		$wp_query->is_single           = false;
		$wp_query->is_archive          = false;
		$wp_query->is_post_type_archive = false;
		$wp_query->found_posts         = 0;
		$wp_query->max_num_pages       = 0;
		$wp_query->posts               = array();

		set_query_var( 'clanspress_team_slug', $slug );
		set_query_var( 'clanspress_team_action', 'events' );
		if ( $event_id > 0 ) {
			set_query_var( 'clanspress_team_event_id', $event_id );
		}
		if ( '' !== $events_sub ) {
			set_query_var( 'clanspress_team_events_sub', $events_sub );
		}
	}

	/**
	 * Stop the main query from behaving as the blog / front page for team virtual routes.
	 *
	 * @param \WP_Query $query Main query.
	 * @return void
	 */
	public function parse_query_for_team_virtual_pages( \WP_Query $query ): void {
		if ( ! $query->is_main_query() ) {
			return;
		}

		$action = $query->get( 'clanspress_team_action' );
		$create = $query->get( 'clanspress_team_create' );

		if ( '' === (string) $action && ! $create ) {
			return;
		}

		$query->is_home              = false;
		$query->is_front_page        = false;
		$query->is_posts_page        = false;
		$query->is_page              = false;
		$query->is_singular          = false;
		$query->is_single            = false;
		$query->is_archive           = false;
		$query->is_post_type_archive = false;
		$query->is_category          = false;
		$query->is_tag               = false;
		$query->is_author            = false;
		$query->is_date              = false;
		$query->is_404               = false;
	}

	/**
	 * Skip SQL for team virtual routes so the main loop is empty.
	 *
	 * @param mixed     $posts Posts or null.
	 * @param \WP_Query $query Query object.
	 * @return mixed
	 */
	public function posts_pre_query_team_virtual_pages( $posts, \WP_Query $query ) {
		if ( ! $query->is_main_query() ) {
			return $posts;
		}

		$action = $query->get( 'clanspress_team_action' );
		$create = $query->get( 'clanspress_team_create' );

		if ( '' !== (string) $action || $create ) {
			$query->found_posts   = 0;
			$query->max_num_pages = 0;
			return array();
		}

		return $posts;
	}

	/**
	 * Point the Site Editor admin-bar link at Clanspress team templates instead of the theme fallback.
	 *
	 * Virtual team URLs use PHP loaders while core may have resolved a generic theme template first.
	 *
	 * @return void
	 */
	public function set_plugin_block_template_id_for_site_editor(): void {
		if ( is_admin() || ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return;
		}

		global $_wp_current_template_id;

		if ( is_singular( 'cp_team' ) ) {
			$_wp_current_template_id = 'clanspress//single-cp_team';
			return;
		}

		if ( ! $this->is_team_directories_mode() ) {
			return;
		}

		if ( (int) get_query_var( 'clanspress_team_create' ) ) {
			$_wp_current_template_id = 'clanspress//teams-create';
			return;
		}

		$action = sanitize_key( (string) get_query_var( 'clanspress_team_action' ) );
		if ( '' === $action ) {
			return;
		}

		if ( 'manage' === $action ) {
			$_wp_current_template_id = 'clanspress//teams-manage';
			return;
		}

		if ( 'events' === $action && $this->events_extension_is_active() ) {
			$events_sub = sanitize_key( (string) get_query_var( 'clanspress_team_events_sub' ) );
			if ( 'create' === $events_sub ) {
				$_wp_current_template_id = 'clanspress//teams-events-create';
				return;
			}
			$_wp_current_template_id = ( (int) get_query_var( 'clanspress_team_event_id' ) > 0 )
				? 'clanspress//teams-events-single'
				: 'clanspress//teams-events';
		}
	}

	/**
	 * Load virtual team templates (create / manage).
	 *
	 * @param string $template Default template path.
	 * @return string
	 */
	public function maybe_load_team_virtual_templates( string $template ): string {
		if ( (int) get_query_var( 'clanspress_team_create' ) ) {
			if ( ! is_user_logged_in() ) {
				wp_safe_redirect( wp_login_url( $this->get_team_create_url() ) );
				exit;
			}

			$hierarchy = array( 'teams-create.php', 'index.php' );
			$located   = locate_template( array( 'teams-create.php' ) );
			if ( ! $located ) {
				$located = clanspress()->path . 'templates/teams/teams-create.php';
			}

			$resolved = function_exists( 'locate_block_template' )
				? locate_block_template( $located, 'teams-create', $hierarchy )
				: $located;
			if ( ! $resolved && is_readable( $located ) ) {
				$resolved = $located;
			}

			return (string) apply_filters(
				'clanspress_load_team_create_template',
				$resolved ? $resolved : $template
			);
		}

		$team_action = sanitize_key( (string) get_query_var( 'clanspress_team_action' ) );
		if ( '' !== $team_action ) {
			if ( 'events' !== $team_action && ! is_user_logged_in() ) {
				wp_safe_redirect( wp_login_url( $this->get_current_url() ) );
				exit;
			}

			$slug = sanitize_title( (string) get_query_var( 'clanspress_team_slug' ) );
			if ( '' === $slug ) {
				return $template;
			}

			$team = get_posts(
				array(
					'name'           => $slug,
					'post_type'      => 'cp_team',
					'post_status'    => array( 'publish', 'draft', 'pending' ),
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);

			if ( empty( $team ) ) {
				status_header( 404 );
				nocache_headers();
				$not_found = get_404_template();
				return $not_found ? $not_found : $template;
			}

			$team_id = (int) $team[0];

			/**
			 * Fires when a team front action URL is resolved (BuddyPress-style dispatch).
			 *
			 * Use to authorize, redirect, or enqueue for custom actions from
			 * `clanspress_team_front_action_rewrite_slugs`. Return a template path
			 * from a callback on `clanspress_load_team_action_template` if needed.
			 *
			 * @param string $team_action Action slug (e.g. manage).
			 * @param int    $team_id     Team post ID.
			 * @param Teams  $extension   Teams extension.
			 */
			do_action( 'clanspress_team_action_dispatch', $team_action, $team_id, $this );

			if ( 'events' === $team_action ) {
				if ( ! $this->events_extension_is_active() ) {
					status_header( 404 );
					nocache_headers();
					$not_found = get_404_template();
					return $not_found ? $not_found : $template;
				}
				if ( function_exists( 'clanspress_events_are_enabled_for_team' ) && ! clanspress_events_are_enabled_for_team( $team_id ) ) {
					status_header( 404 );
					nocache_headers();
					$not_found = get_404_template();
					return $not_found ? $not_found : $template;
				}

				set_query_var( 'clanspress_events_team_id', $team_id );

				$events_sub = sanitize_key( (string) get_query_var( 'clanspress_team_events_sub' ) );
				if ( 'create' === $events_sub ) {
					if ( ! is_user_logged_in() ) {
						wp_safe_redirect( wp_login_url( $this->get_current_url() ) );
						exit;
					}
					if ( ! $this->user_can_manage_team_on_frontend( $team_id ) ) {
						$back = $this->get_team_action_url( $team_id, 'events' );
						wp_safe_redirect( $back ? $back : home_url( '/' ) );
						exit;
					}

					$hierarchy = array( 'teams-events-create.php', 'index.php' );
					$located   = locate_template( array( 'teams-events-create.php' ) );
					if ( ! $located ) {
						$located = clanspress()->path . 'templates/teams/teams-events-create.php';
					}

					return (string) apply_filters(
						'clanspress_load_team_action_template',
						function_exists( 'locate_block_template' ) ? locate_block_template( $located, 'teams-events-create', $hierarchy ) : $located,
						$team_action,
						$team_id,
						$this
					);
				}

				$event_id = (int) get_query_var( 'clanspress_team_event_id' );
				if ( $event_id > 0 ) {
					$hierarchy = array( 'teams-events-single.php', 'index.php' );
					$located   = locate_template( array( 'teams-events-single.php' ) );
					if ( ! $located ) {
						$located = clanspress()->path . 'templates/teams/teams-events-single.php';
					}

					return (string) apply_filters(
						'clanspress_load_team_action_template',
						function_exists( 'locate_block_template' ) ? locate_block_template( $located, 'teams-events-single', $hierarchy ) : $located,
						$team_action,
						$team_id,
						$this
					);
				}

				$hierarchy = array( 'teams-events.php', 'index.php' );
				$located   = locate_template( array( 'teams-events.php' ) );
				if ( ! $located ) {
					$located = clanspress()->path . 'templates/teams/teams-events.php';
				}

				return (string) apply_filters(
					'clanspress_load_team_action_template',
					function_exists( 'locate_block_template' ) ? locate_block_template( $located, 'teams-events', $hierarchy ) : $located,
					$team_action,
					$team_id,
					$this
				);
			}

			if ( 'manage' === $team_action ) {
				if ( ! $this->user_can_manage_team_on_frontend( $team_id ) ) {
					wp_safe_redirect( home_url( '/' ) );
					exit;
				}

				set_query_var( 'clanspress_manage_team_id', $team_id );

				// Hierarchy slug must match register_block_template id segment (teams-manage), not team-manage.
				$templates = array( 'teams-manage.php', 'index.php' );
				$located   = locate_template( $templates );
				if ( ! $located ) {
					$located = clanspress()->path . 'templates/teams/teams-manage.php';
				}

				$loaded = apply_filters(
					'clanspress_load_team_manage_template',
					locate_block_template( $located, 'teams-manage', $templates ),
					$team_id,
					$this
				);

				return (string) apply_filters(
					'clanspress_load_team_action_template',
					$loaded,
					$team_action,
					$team_id,
					$this
				);
			}

			/**
			 * Filter template for custom team actions (when not handled above).
			 *
			 * @param string $template    Current template path.
			 * @param string $team_action Action slug.
			 * @param int    $team_id     Team post ID.
			 * @param Teams  $extension   Teams extension.
			 */
			return (string) apply_filters(
				'clanspress_load_team_action_template',
				$template,
				$team_action,
				$team_id,
				$this
			);
		}

		return $template;
	}

	/**
	 * Block banned members from viewing the team on the front end.
	 *
	 * @return void
	 */
	public function maybe_block_banned_team_access(): void {
		if ( ! is_singular( 'cp_team' ) ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		if ( $this->user_is_teams_site_admin( $user_id ) ) {
			return;
		}

		$team_id = (int) get_queried_object_id();
		if ( $team_id < 1 ) {
			return;
		}

		if ( self::TEAM_ROLE_BANNED !== $this->get_team_member_role( $team_id, $user_id ) ) {
			return;
		}

		wp_safe_redirect(
			add_query_arg(
				'clanspress_team_access',
				'banned',
				home_url( '/' )
			)
		);
		exit;
	}

	/**
	 * Public URL for the create-team screen.
	 *
	 * @return string
	 */
	public function get_team_create_url(): string {
		$url = home_url( '/teams/create/' );

		return (string) apply_filters( 'clanspress_team_create_url', $url, $this );
	}

	/**
	 * Front-end URL for a team action (e.g. manage): /teams/{slug}/{action}/.
	 *
	 * @param int    $team_id Team post ID.
	 * @param string $action  Action slug (must be registered in rewrite slugs).
	 * @return string
	 */
	public function get_team_action_url( int $team_id, string $action ): string {
		$slug = (string) get_post_field( 'post_name', $team_id );
		if ( '' === $slug ) {
			return '';
		}

		$action = sanitize_key( $action );
		if ( '' === $action ) {
			return '';
		}

		$path = user_trailingslashit( 'teams/' . $slug . '/' . $action );
		$url  = home_url( $path );

		/**
		 * Filter URL for a team front action.
		 *
		 * @param string $url      Full URL.
		 * @param int    $team_id  Team post ID.
		 * @param string $action   Action slug.
		 * @param Teams  $extension Teams extension.
		 */
		return (string) apply_filters( 'clanspress_team_action_url', $url, $team_id, $action, $this );
	}

	/**
	 * Front-end manage URL for a team.
	 *
	 * @param int $team_id Team post ID.
	 * @return string
	 */
	public function get_team_manage_url( int $team_id ): string {
		$url = $this->get_team_action_url( $team_id, 'manage' );

		return (string) apply_filters( 'clanspress_team_manage_url', $url, $team_id, $this );
	}

	/**
	 * Current request URL for redirects.
	 *
	 * @return string
	 */
	protected function get_current_url(): string {
		if ( empty( $_SERVER['HTTP_HOST'] ) ) {
			return home_url( '/' );
		}

		$scheme = is_ssl() ? 'https' : 'http';
		$host   = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';

		return esc_url_raw( $scheme . '://' . $host . $uri );
	}

	/**
	 * Sanitize a team member role slug.
	 *
	 * @param string $role Raw role.
	 * @return string
	 */
	public function sanitize_team_member_role( string $role ): string {
		$allowed = array(
			self::TEAM_ROLE_ADMIN,
			self::TEAM_ROLE_EDITOR,
			self::TEAM_ROLE_MEMBER,
			self::TEAM_ROLE_BANNED,
		);
		$role    = sanitize_key( $role );

		return in_array( $role, $allowed, true ) ? $role : self::TEAM_ROLE_MEMBER;
	}

	/**
	 * Map of user ID => role for a team (author defaults to admin when missing from meta).
	 *
	 * @param int $team_id Team post ID.
	 * @return array<int, string>
	 */
	public function get_team_member_roles_map( int $team_id ): array {
		$raw = get_post_meta( $team_id, 'cp_team_member_roles', true );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		$out = array();
		foreach ( $raw as $uid => $role ) {
			$uid = (int) $uid;
			if ( $uid < 1 ) {
				continue;
			}
			$out[ $uid ] = $this->sanitize_team_member_role( (string) $role );
		}

		$author = (int) get_post_field( 'post_author', $team_id );
		if ( $author && ! isset( $out[ $author ] ) ) {
			$out[ $author ] = self::TEAM_ROLE_ADMIN;
		}

		/**
		 * Filter resolved team roster map.
		 *
		 * @param array $out     User ID => role.
		 * @param int   $team_id Team post ID.
		 * @param Teams $extension Teams extension.
		 */
		return (array) apply_filters( 'clanspress_team_member_roles_map', $out, $team_id, $this );
	}

	/**
	 * Number of roster members (excluding banned by default).
	 *
	 * @param int  $team_id         Team post ID.
	 * @param bool $exclude_banned When true, do not count banned users.
	 * @return int
	 */
	public function get_team_member_count( int $team_id, bool $exclude_banned = true ): int {
		$map = $this->get_team_member_roles_map( $team_id );
		if ( empty( $map ) ) {
			return 0;
		}

		$n = 0;
		foreach ( $map as $role ) {
			if ( $exclude_banned && self::TEAM_ROLE_BANNED === $role ) {
				continue;
			}
			++$n;
		}

		return (int) apply_filters( 'clanspress_team_member_count', $n, $team_id, $exclude_banned, $this );
	}

	/**
	 * Role for a user on a team, or null if not on roster (and not author).
	 *
	 * @param int $team_id Team post ID.
	 * @param int $user_id User ID.
	 * @return string|null
	 */
	public function get_team_member_role( int $team_id, int $user_id ): ?string {
		$map = $this->get_team_member_roles_map( $team_id );

		return $map[ $user_id ] ?? null;
	}

	/**
	 * Whether the user bypasses roster-based team permissions (site admin or multisite super admin).
	 *
	 * On a multisite network, super admins can manage teams on any site they visit. Per-site
	 * administrators use the filtered capability (default `manage_options`).
	 *
	 * @param int|null $user_id User ID or null for the current user.
	 * @return bool
	 */
	public function user_is_teams_site_admin( ?int $user_id = null ): bool {
		$user_id = (int) ( $user_id ?? get_current_user_id() );
		if ( $user_id < 1 ) {
			return false;
		}

		if ( is_multisite() && is_super_admin( $user_id ) ) {
			return true;
		}

		/**
		 * Primitive capability required to manage any team as a site administrator.
		 *
		 * @param string $cap     Capability name. Default `manage_options`.
		 * @param int    $user_id User ID.
		 */
		$cap = (string) apply_filters( 'clanspress_teams_site_admin_capability', 'manage_options', $user_id );

		return user_can( $user_id, $cap );
	}

	/**
	 * Whether the user may open the front-end manage screen (admin or editor, not banned).
	 *
	 * @param int      $team_id Team post ID.
	 * @param int|null $user_id User ID or null for current user.
	 * @return bool
	 */
	public function user_can_manage_team_on_frontend( int $team_id, ?int $user_id = null ): bool {
		$user_id = $user_id ?? get_current_user_id();
		if ( $user_id < 1 ) {
			return false;
		}

		if ( $this->user_is_teams_site_admin( $user_id ) ) {
			return true;
		}

		if ( ! $this->can_edit_team_frontend( $team_id ) ) {
			return false;
		}

		$role = $this->get_team_member_role( $team_id, $user_id );
		if ( ! $role || self::TEAM_ROLE_BANNED === $role ) {
			return false;
		}

		return in_array( $role, array( self::TEAM_ROLE_ADMIN, self::TEAM_ROLE_EDITOR ), true );
	}

	/**
	 * Published teams the user may manage on the front end (admin or editor roster role, or site teams admin).
	 *
	 * @param int $user_id User ID.
	 * @return array<int, int> Unique `cp_team` post IDs.
	 */
	public function get_user_managed_team_ids( int $user_id ): array {
		if ( $user_id < 1 ) {
			return array();
		}

		$ids = array();

		$authored = get_posts(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'publish',
				'author'                 => $user_id,
				'posts_per_page'         => 200,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
			)
		);
		foreach ( $authored as $tid ) {
			$tid = (int) $tid;
			if ( $tid > 0 && $this->user_can_manage_team_on_frontend( $tid, $user_id ) ) {
				$ids[] = $tid;
			}
		}

		$indexed = get_user_meta( $user_id, 'cp_team_membership_ids', true );
		if ( is_array( $indexed ) ) {
			foreach ( array_map( 'intval', $indexed ) as $tid ) {
				if ( $tid < 1 || in_array( $tid, $ids, true ) ) {
					continue;
				}
				if ( $this->user_can_manage_team_on_frontend( $tid, $user_id ) ) {
					$ids[] = $tid;
				}
			}
		}

		/**
		 * Filter resolved managed team IDs for a user.
		 *
		 * @param array $ids     Unique team post IDs.
		 * @param int   $user_id User ID.
		 * @param Teams $extension Teams extension instance.
		 */
		$ids = (array) apply_filters( 'clanspress_user_managed_team_ids', $ids, $user_id, $this );

		return array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
	}

	/**
	 * Whether the user is a team admin on the front end.
	 *
	 * @param int      $team_id Team post ID.
	 * @param int|null $user_id User ID or null for current user.
	 * @return bool
	 */
	public function user_is_team_admin_on_frontend( int $team_id, ?int $user_id = null ): bool {
		$user_id = $user_id ?? get_current_user_id();
		if ( $user_id < 1 ) {
			return false;
		}

		if ( $this->user_is_teams_site_admin( $user_id ) ) {
			return true;
		}

		return self::TEAM_ROLE_ADMIN === $this->get_team_member_role( $team_id, $user_id )
			&& $this->can_edit_team_frontend( $team_id );
	}

	/**
	 * Whether the user may permanently delete the team from the front-end manage screen.
	 *
	 * Default: site/network teams admin, post author, or team roster admin.
	 *
	 * @param int      $team_id Team post ID.
	 * @param int|null $user_id User ID or null for current user.
	 * @return bool
	 */
	public function user_can_delete_team_on_frontend( int $team_id, ?int $user_id = null ): bool {
		$user_id = $user_id ?? get_current_user_id();
		if ( $user_id < 1 ) {
			return false;
		}

		$can = false;

		if ( $this->user_is_teams_site_admin( $user_id ) ) {
			$can = true;
		} else {
			$author = (int) get_post_field( 'post_author', $team_id );
			if ( $author > 0 && $author === $user_id ) {
				$can = true;
			} elseif ( $this->user_is_team_admin_on_frontend( $team_id, $user_id ) ) {
				$can = true;
			}
		}

		/**
		 * Filter whether the user may delete a team from the front-end manage UI.
		 *
		 * @param bool $can     Whether deletion is allowed.
		 * @param int  $team_id Team post ID.
		 * @param int  $user_id User ID.
		 * @param Teams $extension Teams extension instance.
		 */
		return (bool) apply_filters( 'clanspress_user_can_delete_team_on_frontend', $can, $team_id, $user_id, $this );
	}

	/**
	 * Persist roster and sync per-user membership index for invite limits.
	 *
	 * @param int                $team_id Team post ID.
	 * @param array<int, string> $new_map User ID => role.
	 * @return bool False when validation fails (e.g. no admin left).
	 */
	public function persist_team_roles_map( int $team_id, array $new_map ): bool {
		$author = (int) get_post_field( 'post_author', $team_id );

		$old_raw = get_post_meta( $team_id, 'cp_team_member_roles', true );
		if ( ! is_array( $old_raw ) ) {
			$old_raw = array();
		}
		$old_ids = array_map( 'intval', array_keys( $old_raw ) );
		if ( $author && ! in_array( $author, $old_ids, true ) ) {
			$old_ids[] = $author;
		}

		$clean = array();
		foreach ( $new_map as $uid => $role ) {
			$uid = (int) $uid;
			if ( $uid < 1 ) {
				continue;
			}
			$clean[ $uid ] = $this->sanitize_team_member_role( (string) $role );
		}

		if ( $author && ( ! isset( $clean[ $author ] ) || self::TEAM_ROLE_BANNED === $clean[ $author ] ) ) {
			$clean[ $author ] = self::TEAM_ROLE_ADMIN;
		}

		$admin_count = 0;
		foreach ( $clean as $role ) {
			if ( self::TEAM_ROLE_ADMIN === $role ) {
				++$admin_count;
			}
		}
		if ( $admin_count < 1 ) {
			return false;
		}

		$team_entity = $this->get_team( $team_id );
		if ( ! $team_entity ) {
			return false;
		}
		$team_entity->set_member_roles( $clean );
		$this->get_team_data_store()->update( $team_entity );

		$new_ids = array_map( 'intval', array_keys( $clean ) );
		foreach ( array_unique( array_merge( $old_ids, $new_ids ) ) as $uid ) {
			$role = $clean[ $uid ] ?? null;
			if ( null === $role ) {
				$this->remove_team_from_user_membership_index( $team_id, $uid );
			} else {
				$this->sync_membership_index_for_user_team( $team_id, $uid, $role );
			}
		}

		do_action( 'clanspress_team_roster_updated', $team_id, $clean, $this );

		return true;
	}

	/**
	 * @param int    $team_id Team post ID.
	 * @param int    $user_id User ID.
	 * @param string $role    Role slug.
	 * @return void
	 */
	protected function sync_membership_index_for_user_team( int $team_id, int $user_id, string $role ): void {
		$ids = get_user_meta( $user_id, 'cp_team_membership_ids', true );
		if ( ! is_array( $ids ) ) {
			$ids = array();
		}
		$ids     = array_map( 'intval', $ids );
		$team_id = (int) $team_id;
		$pos     = array_search( $team_id, $ids, true );

		if ( self::TEAM_ROLE_BANNED === $role ) {
			if ( false !== $pos ) {
				unset( $ids[ $pos ] );
			}
		} elseif ( false === $pos ) {
			$ids[] = $team_id;
		}

		$ids = array_values( array_unique( $ids ) );
		update_user_meta( $user_id, 'cp_team_membership_ids', $ids );
	}

	/**
	 * @param int $team_id Team post ID.
	 * @param int $user_id User ID.
	 * @return void
	 */
	protected function remove_team_from_user_membership_index( int $team_id, int $user_id ): void {
		$ids = get_user_meta( $user_id, 'cp_team_membership_ids', true );
		if ( ! is_array( $ids ) ) {
			return;
		}
		$ids     = array_map( 'intval', $ids );
		$team_id = (int) $team_id;
		$ids     = array_values(
			array_filter(
				$ids,
				static function ( int $id ) use ( $team_id ): bool {
					return $id !== $team_id;
				}
			)
		);
		update_user_meta( $user_id, 'cp_team_membership_ids', $ids );
	}

	/**
	 * Collect user IDs that still reference this team in their membership index (while the team post exists).
	 *
	 * @param int $team_id Team post ID.
	 * @return array<int, int>
	 */
	protected function get_user_ids_for_team_membership_cleanup( int $team_id ): array {
		$uids = array_map( 'intval', array_keys( $this->get_team_member_roles_map( $team_id ) ) );
		$author = (int) get_post_field( 'post_author', $team_id );
		if ( $author > 0 && ! in_array( $author, $uids, true ) ) {
			$uids[] = $author;
		}
		return array_values( array_unique( array_filter( array_map( 'intval', $uids ) ) ) );
	}

	/**
	 * Remove this team from every affected user’s membership index (call after the team post is deleted).
	 *
	 * @param int        $team_id  Team post ID (used as the key to remove from each user’s list).
	 * @param array<int> $user_ids Users to update (from {@see get_user_ids_for_team_membership_cleanup()} while the post existed).
	 * @return void
	 */
	protected function cleanup_team_membership_index_for_team_deletion( int $team_id, array $user_ids ): void {
		foreach ( $user_ids as $uid ) {
			if ( $uid > 0 ) {
				$this->remove_team_from_user_membership_index( $team_id, $uid );
			}
		}
	}

	/**
	 * Initial roster after team creation.
	 *
	 * @param int   $team_id         New team ID.
	 * @param int   $creator_id      Creator user ID.
	 * @param array $invite_user_ids Invited user IDs (added as members).
	 * @return void
	 */
	protected function initialize_team_roster( int $team_id, int $creator_id, array $invite_user_ids ): void {
		$map = array( $creator_id => self::TEAM_ROLE_ADMIN );

		// Send invites instead of directly adding members.
		foreach ( $invite_user_ids as $inv ) {
			$inv = (int) $inv;
			if ( $inv > 0 && $inv !== $creator_id ) {
				$this->send_team_invite( $team_id, $inv, $creator_id );
			}
		}

		$this->persist_team_roles_map( $team_id, $map );
	}

	/**
	 * Send a team invite notification to a user.
	 *
	 * @param int $team_id    Team ID.
	 * @param int $user_id    User to invite.
	 * @param int $inviter_id User sending the invite.
	 * @return int|\WP_Error Notification ID or error.
	 */
	public function send_team_invite( int $team_id, int $user_id, int $inviter_id ) {
		if ( ! function_exists( 'clanspress_notify' ) ) {
			return new \WP_Error( 'notifications_unavailable', __( 'Notifications system not available.', 'clanspress' ) );
		}

		if ( function_exists( 'clanspress_notifications_extension_active' ) && ! clanspress_notifications_extension_active() ) {
			return new \WP_Error(
				'notifications_unavailable',
				__( 'Notifications are not available. Enable the Notifications extension under Clanspress → Extensions.', 'clanspress' )
			);
		}

		$team      = get_post( $team_id );
		$inviter   = get_userdata( $inviter_id );
		$team_name = $team ? $team->post_title : __( 'a team', 'clanspress' );
		$team_url  = $team ? get_permalink( $team ) : '';

		$title = sprintf(
			/* translators: 1: inviter name, 2: team name */
			__( '%1$s invited you to join %2$s', 'clanspress' ),
			$inviter ? $inviter->display_name : __( 'Someone', 'clanspress' ),
			$team_name
		);

		/**
		 * Fires before a team invite notification is sent.
		 *
		 * @param int $team_id    Team ID.
		 * @param int $user_id    User being invited.
		 * @param int $inviter_id User sending the invite.
		 */
		do_action( 'clanspress_before_team_invite', $team_id, $user_id, $inviter_id );

		$result = clanspress_notify(
			$user_id,
			'team_invite',
			$title,
			array(
				'actor_id'    => $inviter_id,
				'object_type' => 'team',
				'object_id'   => $team_id,
				'url'         => $team_url,
				'actions'     => array(
					array(
						'key'             => 'accept',
						'label'           => __( 'Accept', 'clanspress' ),
						'style'           => 'primary',
						'handler'         => 'team_invite_accept',
						'status'          => 'accepted',
						'success_message' => __( 'You have joined the team!', 'clanspress' ),
					),
					array(
						'key'             => 'decline',
						'label'           => __( 'Decline', 'clanspress' ),
						'style'           => 'secondary',
						'handler'         => 'team_invite_decline',
						'status'          => 'declined',
						'success_message' => __( 'Invitation declined.', 'clanspress' ),
					),
				),
			)
		);

		if ( ! is_wp_error( $result ) ) {
			/**
			 * Fires after a team invite notification is sent.
			 *
			 * @param int $notification_id Notification ID.
			 * @param int $team_id         Team ID.
			 * @param int $user_id         User being invited.
			 * @param int $inviter_id      User sending the invite.
			 */
			do_action( 'clanspress_team_invite_sent', $result, $team_id, $user_id, $inviter_id );
		}

		return $result;
	}

	/**
	 * Handle notification actions for team-related notifications.
	 *
	 * @param array|null $result       Current result (null if not handled).
	 * @param string     $handler      Handler identifier.
	 * @param object     $notification Notification object.
	 * @param array      $action       Action configuration.
	 * @param int        $user_id      User executing the action.
	 * @return array|null Result array or null to pass to next handler.
	 */
	public function handle_notification_actions( $result, string $handler, object $notification, array $action, int $user_id ) {
		// Only handle our own handlers.
		if ( null !== $result ) {
			return $result;
		}

		switch ( $handler ) {
			case 'team_invite_accept':
				return $this->handle_team_invite_accept( $notification, $user_id );

			case 'team_invite_decline':
				return $this->handle_team_invite_decline( $notification, $user_id );

			case 'team_challenge_accept':
				return Team_Challenges::handle_notification_accept( $notification, $user_id );

			case 'team_challenge_decline':
				return Team_Challenges::handle_notification_decline( $notification, $user_id );

			default:
				return null;
		}
	}

	/**
	 * Handle team invite accept action.
	 *
	 * @param object $notification Notification object.
	 * @param int    $user_id      User accepting the invite.
	 * @return array{success: bool, message: string, redirect?: string}
	 */
	private function handle_team_invite_accept( object $notification, int $user_id ): array {
		$team_id = $notification->object_id ?? 0;

		if ( $team_id <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid team.', 'clanspress' ),
			);
		}

		$team = get_post( $team_id );
		if ( ! $team || self::POST_TYPE !== $team->post_type ) {
			return array(
				'success' => false,
				'message' => __( 'Team not found.', 'clanspress' ),
			);
		}

		// Check if user is already a member.
		$roles_map = $this->get_team_member_roles_map( $team_id );
		if ( isset( $roles_map[ $user_id ] ) ) {
			return array(
				'success' => false,
				'message' => __( 'You are already a member of this team.', 'clanspress' ),
			);
		}

		// Add user to team as member.
		$roles_map[ $user_id ] = self::TEAM_ROLE_MEMBER;
		$this->persist_team_roles_map( $team_id, $roles_map );

		/**
		 * Fires after a user accepts a team invite.
		 *
		 * @param int    $team_id Team ID.
		 * @param int    $user_id User who accepted.
		 * @param object $notification The notification object.
		 */
		do_action( 'clanspress_team_invite_accepted', $team_id, $user_id, $notification );

		$team_url = get_permalink( $team_id );

		return array(
			'success'  => true,
			'message'  => __( 'You have joined the team!', 'clanspress' ),
			'redirect' => $team_url ?: null,
		);
	}

	/**
	 * Handle team invite decline action.
	 *
	 * @param object $notification Notification object.
	 * @param int    $user_id      User declining the invite.
	 * @return array{success: bool, message: string}
	 */
	private function handle_team_invite_decline( object $notification, int $user_id ): array {
		$team_id = $notification->object_id ?? 0;

		/**
		 * Fires after a user declines a team invite.
		 *
		 * @param int    $team_id Team ID.
		 * @param int    $user_id User who declined.
		 * @param object $notification The notification object.
		 */
		do_action( 'clanspress_team_invite_declined', $team_id, $user_id, $notification );

		return array(
			'success' => true,
			'message' => __( 'Invitation declined.', 'clanspress' ),
		);
	}

	/**
	 * Default section definitions for the front-end team manage form.
	 *
	 * @return array<string, array{title: string, priority: int, callback: callable}>
	 */
	protected function get_default_team_manage_form_sections(): array {
		return array(
			'profile'      => array(
				'title'    => __( 'Team profile', 'clanspress' ),
				'priority' => 10,
				'callback' => array( $this, 'render_team_manage_section_profile' ),
			),
			'media'        => array(
				'title'    => __( 'Team images', 'clanspress' ),
				'priority' => 15,
				'callback' => array( $this, 'render_team_manage_section_media' ),
			),
			'team_options' => array(
				'title'    => __( 'Membership & options', 'clanspress' ),
				'priority' => 20,
				'callback' => array( $this, 'render_team_manage_section_team_options' ),
			),
			'roster'       => array(
				'title'    => __( 'Members & roles', 'clanspress' ),
				'priority' => 40,
				'callback' => array( $this, 'render_team_manage_section_roster' ),
			),
		);
	}

	/**
	 * Sections for the front-end team manage form (filterable).
	 *
	 * Each section: `title` (string), `priority` (int), `callback` (callable( int $team_id, Teams $extension ): void).
	 *
	 * @param int $team_id Team post ID.
	 * @return array<string, array{title: string, priority: int, callback: callable}>
	 */
	public function get_team_manage_form_sections( int $team_id ): array {
		$sections = $this->get_default_team_manage_form_sections();

		/**
		 * Filter registered sections for the front-end team manage form.
		 *
		 * Add, remove, or reorder sections. Third-party sections should use a unique string key.
		 *
		 * @param array<string, array{title: string, priority: int, callback: callable}> $sections Section key => config.
		 * @param int                                                                      $team_id  Team post ID.
		 * @param Teams                                                                    $extension Teams extension instance.
		 */
		$sections = (array) apply_filters( 'clanspress_team_manage_form_sections', $sections, $team_id, $this );

		uasort(
			$sections,
			static function ( $a, $b ) {
				return (int) ( $a['priority'] ?? 10 ) <=> (int) ( $b['priority'] ?? 10 );
			}
		);

		return $sections;
	}

	/**
	 * Team profile fields (name, code, motto, description, country, record).
	 *
	 * @param int   $team_id Team post ID.
	 * @param Teams $extension Teams extension instance.
	 * @return void
	 */
	public function render_team_manage_section_profile( int $team_id, Teams $extension ): void {
		unset( $extension );

		$post = get_post( $team_id );
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$team_profile = $this->get_team( $team_id );
		$country_val  = $team_profile ? $team_profile->get_country() : '';
		?>
		<div class="clanspress-team-manage-form__fields-inner">
			<p>
				<label for="clanspress-manage-team-name"><?php esc_html_e( 'Team name', 'clanspress' ); ?></label><br />
				<input type="text" id="clanspress-manage-team-name" name="team_name" class="widefat" required value="<?php echo esc_attr( get_the_title( $team_id ) ); ?>" />
			</p>
			<p>
				<label for="clanspress-manage-team-code"><?php esc_html_e( 'Team code', 'clanspress' ); ?></label><br />
				<input type="text" id="clanspress-manage-team-code" name="team_code" class="widefat" value="<?php echo esc_attr( (string) get_post_meta( $team_id, 'cp_team_code', true ) ); ?>" />
			</p>
			<p>
				<label for="clanspress-manage-team-motto"><?php esc_html_e( 'Team motto', 'clanspress' ); ?></label><br />
				<input type="text" id="clanspress-manage-team-motto" name="team_motto" class="widefat" value="<?php echo esc_attr( (string) get_post_meta( $team_id, 'cp_team_motto', true ) ); ?>" />
			</p>
			<p>
				<label for="clanspress-manage-team-description"><?php esc_html_e( 'Description', 'clanspress' ); ?></label><br />
				<textarea id="clanspress-manage-team-description" name="team_description" class="widefat" rows="6"><?php echo esc_textarea( (string) $post->post_content ); ?></textarea>
			</p>
			<p>
				<label for="clanspress-manage-team-country"><?php esc_html_e( 'Country', 'clanspress' ); ?></label><br />
				<select id="clanspress-manage-team-country" name="team_country" class="widefat">
					<option value=""><?php esc_html_e( '— Select —', 'clanspress' ); ?></option>
					<?php
					if ( function_exists( 'clanspress_players_get_countries' ) ) :
						foreach ( clanspress_players_get_countries() as $cc => $cname ) :
							?>
							<option value="<?php echo esc_attr( $cc ); ?>" <?php selected( $country_val, $cc ); ?>><?php echo esc_html( $cname ); ?></option>
							<?php
						endforeach;
					endif;
					?>
				</select>
			</p>
			<p class="clanspress-team-manage-form__record">
				<label for="clanspress-manage-team-wins"><?php esc_html_e( 'Record (wins / losses / draws)', 'clanspress' ); ?></label><br />
				<input type="number" min="0" step="1" id="clanspress-manage-team-wins" name="team_wins" class="small-text" value="<?php echo esc_attr( (string) ( $team_profile ? $team_profile->get_wins() : 0 ) ); ?>" />
				<span class="description" aria-hidden="true"> / </span>
				<input type="number" min="0" step="1" id="clanspress-manage-team-losses" name="team_losses" class="small-text" value="<?php echo esc_attr( (string) ( $team_profile ? $team_profile->get_losses() : 0 ) ); ?>" />
				<span class="description" aria-hidden="true"> / </span>
				<input type="number" min="0" step="1" id="clanspress-manage-team-draws" name="team_draws" class="small-text" value="<?php echo esc_attr( (string) ( $team_profile ? $team_profile->get_draws() : 0 ) ); ?>" />
			</p>
		</div>
		<?php
	}

	/**
	 * Team avatar and cover image uploads (same field names as the create-team flow).
	 *
	 * @param int   $team_id Team post ID.
	 * @param Teams $extension Teams extension instance.
	 * @return void
	 */
	public function render_team_manage_section_media( int $team_id, Teams $extension ): void {
		unset( $extension );

		$team = $this->get_team( $team_id );
		if ( ! $team ) {
			return;
		}

		$avatar_id = (int) $team->get_avatar_id();
		$cover_id  = (int) $team->get_cover_id();
		?>
		<div class="clanspress-team-manage-form__media-inner">
			<div class="clanspress-team-manage-form__media-field">
				<p>
					<strong><?php esc_html_e( 'Avatar', 'clanspress' ); ?></strong>
				</p>
				<?php if ( $avatar_id > 0 ) : ?>
					<p class="clanspress-team-manage-form__media-preview">
						<?php
						echo wp_get_attachment_image(
							$avatar_id,
							'thumbnail',
							false,
							array(
								'class' => 'clanspress-team-manage-form__media-thumb',
								'alt'   => '',
							)
						);
						?>
					</p>
					<p>
						<label for="clanspress-manage-team-avatar-remove">
							<input
								type="checkbox"
								id="clanspress-manage-team-avatar-remove"
								name="team_avatar_remove"
								value="1"
							/>
							<?php esc_html_e( 'Remove current avatar', 'clanspress' ); ?>
						</label>
					</p>
				<?php endif; ?>
				<p>
					<label for="clanspress-manage-team-avatar"><?php esc_html_e( 'Upload new avatar', 'clanspress' ); ?></label><br />
					<input
						type="file"
						id="clanspress-manage-team-avatar"
						name="team_avatar"
						accept="image/png,image/jpeg"
					/>
				</p>
				<p class="description"><?php esc_html_e( 'PNG or JPEG images only. Uploading replaces the current image.', 'clanspress' ); ?></p>
			</div>
			<div class="clanspress-team-manage-form__media-field">
				<p>
					<strong><?php esc_html_e( 'Cover image', 'clanspress' ); ?></strong>
				</p>
				<?php if ( $cover_id > 0 ) : ?>
					<p class="clanspress-team-manage-form__media-preview">
						<?php
						echo wp_get_attachment_image(
							$cover_id,
							'medium',
							false,
							array(
								'class' => 'clanspress-team-manage-form__media-cover-preview',
								'alt'   => '',
							)
						);
						?>
					</p>
					<p>
						<label for="clanspress-manage-team-cover-remove">
							<input
								type="checkbox"
								id="clanspress-manage-team-cover-remove"
								name="team_cover_remove"
								value="1"
							/>
							<?php esc_html_e( 'Remove current cover image', 'clanspress' ); ?>
						</label>
					</p>
				<?php endif; ?>
				<p>
					<label for="clanspress-manage-team-cover"><?php esc_html_e( 'Upload new cover image', 'clanspress' ); ?></label><br />
					<input
						type="file"
						id="clanspress-manage-team-cover"
						name="team_cover"
						accept="image/png,image/jpeg"
					/>
				</p>
				<p class="description"><?php esc_html_e( 'PNG or JPEG images only. Uploading replaces the current image.', 'clanspress' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Membership and team flags (join mode, invites, editing, bans, match challenges).
	 *
	 * @param int   $team_id Team post ID.
	 * @param Teams $extension Teams extension instance.
	 * @return void
	 */
	public function render_team_manage_section_team_options( int $team_id, Teams $extension ): void {
		unset( $extension );

		$team = $this->get_team( $team_id );
		if ( ! $team ) {
			return;
		}

		$join_modes = $this->get_team_join_modes();
		$join_value = $this->sanitize_team_join_mode( $team->get_join_mode() );
		?>
		<div class="clanspress-team-manage-form__team-options-inner">
			<p>
				<label for="clanspress-manage-team-join-mode"><?php esc_html_e( 'Join mode', 'clanspress' ); ?></label><br />
				<select id="clanspress-manage-team-join-mode" name="team_join_mode" class="widefat">
					<?php foreach ( $join_modes as $mode => $label ) : ?>
						<option value="<?php echo esc_attr( $mode ); ?>" <?php selected( $join_value, $mode ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="description"><?php esc_html_e( 'Controls how new members can join this team.', 'clanspress' ); ?></p>

			<p>
				<input type="hidden" name="team_allow_invites" value="0" />
				<label for="clanspress-manage-team-allow-invites">
					<input
						type="checkbox"
						id="clanspress-manage-team-allow-invites"
						name="team_allow_invites"
						value="1"
						<?php checked( $team->get_allow_invites() ); ?>
					/>
					<?php esc_html_e( 'Allow member invitations', 'clanspress' ); ?>
				</label>
			</p>

			<p>
				<input type="hidden" name="team_allow_frontend_edit" value="0" />
				<label for="clanspress-manage-team-allow-frontend-edit">
					<input
						type="checkbox"
						id="clanspress-manage-team-allow-frontend-edit"
						name="team_allow_frontend_edit"
						value="1"
						<?php checked( $team->get_allow_frontend_edit() ); ?>
					/>
					<?php esc_html_e( 'Allow editing this team from the front end', 'clanspress' ); ?>
				</label>
			</p>

			<p>
				<input type="hidden" name="team_allow_ban_players" value="0" />
				<label for="clanspress-manage-team-allow-ban-players">
					<input
						type="checkbox"
						id="clanspress-manage-team-allow-ban-players"
						name="team_allow_ban_players"
						value="1"
						<?php checked( $team->get_allow_ban_players() ); ?>
					/>
					<?php esc_html_e( 'Allow banning players from the roster', 'clanspress' ); ?>
				</label>
			</p>

			<p>
				<input type="hidden" name="team_accept_challenges" value="0" />
				<label for="clanspress-manage-team-accept-challenges">
					<input
						type="checkbox"
						id="clanspress-manage-team-accept-challenges"
						name="team_accept_challenges"
						value="1"
						<?php checked( $team->get_accept_challenges() ); ?>
					/>
					<?php esc_html_e( 'Allow other teams to challenge this team', 'clanspress' ); ?>
				</label>
			</p>
			<p class="description"><?php esc_html_e( 'When disabled, other teams should not be able to schedule matches against you (requires the Matches extension).', 'clanspress' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Roster table (team admins only).
	 *
	 * @param int   $team_id Team post ID.
	 * @param Teams $extension Teams extension instance.
	 * @return void
	 */
	public function render_team_manage_section_roster( int $team_id, Teams $extension ): void {
		unset( $extension );

		if ( ! $this->user_is_team_admin_on_frontend( $team_id ) ) {
			return;
		}

		$map   = $this->get_team_member_roles_map( $team_id );
		$roles = array(
			self::TEAM_ROLE_ADMIN  => __( 'Admin', 'clanspress' ),
			self::TEAM_ROLE_EDITOR => __( 'Editor', 'clanspress' ),
			self::TEAM_ROLE_MEMBER => __( 'Member', 'clanspress' ),
			self::TEAM_ROLE_BANNED => __( 'Banned', 'clanspress' ),
		);
		?>
		<div class="clanspress-team-manage-form__roster-inner">
			<table class="clanspress-team-manage-roster">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Player', 'clanspress' ); ?></th>
						<th><?php esc_html_e( 'Role', 'clanspress' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $map as $member_id => $member_role ) :
						$member = get_userdata( $member_id );
						if ( ! $member ) {
							continue;
						}
						?>
						<tr>
							<td><?php echo esc_html( $member->display_name ); ?> <span class="description">(<?php echo esc_html( $member->user_login ); ?>)</span></td>
							<td>
								<select name="member_roles[<?php echo esc_attr( (string) $member_id ); ?>]">
									<?php foreach ( $roles as $value => $label ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $member_role, $value ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description"><?php esc_html_e( 'Banned members cannot view this team. At least one admin is required.', 'clanspress' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Enqueue styles and the Interactivity module for the tabbed team manage screen.
	 *
	 * @return void
	 */
	public function maybe_enqueue_team_manage_form_assets(): void {
		if ( (int) get_query_var( 'clanspress_manage_team_id' ) < 1 ) {
			return;
		}

		$create_form_style_path = clanspress()->path . 'build/teams/team-create-form/style-index.css';
		$create_form_style_url  = clanspress()->url . 'build/teams/team-create-form/style-index.css';

		wp_enqueue_style(
			'clanspress-team-create-form-style',
			$create_form_style_url,
			array(),
			Main::VERSION
		);

		if ( is_readable( $create_form_style_path ) ) {
			wp_style_add_data( 'clanspress-team-create-form-style', 'path', $create_form_style_path );

			$rtl_path = str_replace( '.css', '-rtl.css', $create_form_style_path );
			if ( is_readable( $rtl_path ) ) {
				wp_style_add_data( 'clanspress-team-create-form-style', 'rtl', 'replace' );
				if ( is_rtl() ) {
					wp_style_add_data( 'clanspress-team-create-form-style', 'path', $rtl_path );
				}
			}
		}

		wp_enqueue_script_module(
			'clanspress-team-manage-form-view',
			clanspress()->url . 'build/teams/team-manage-form/view.js',
			array( '@wordpress/interactivity' ),
			Main::VERSION
		);
	}

	/**
	 * Short tab descriptions for the manage form (keyed by section id).
	 *
	 * @return array<string, string>
	 */
	protected function get_team_manage_tab_descriptions(): array {
		return array(
			'profile'      => __( 'Name, description, record', 'clanspress' ),
			'media'        => __( 'Avatar and cover images', 'clanspress' ),
			'team_options' => __( 'Join mode and permissions', 'clanspress' ),
			'roster'       => __( 'Member roles', 'clanspress' ),
		);
	}

	/**
	 * Render the front-end team manage UI (included from template).
	 *
	 * @param int $team_id Team post ID.
	 * @return void
	 */
	public function render_frontend_team_manage( int $team_id ): void {
		if ( $team_id < 1 ) {
			return;
		}

		$post = get_post( $team_id );
		if ( ! $post || 'cp_team' !== $post->post_type ) {
			return;
		}

		$can_edit = $this->user_can_manage_team_on_frontend( $team_id );
		if ( ! $can_edit ) {
			return;
		}

		$manage_status = isset( $_GET['clanspress_team_manage_status'] )
			? sanitize_key( wp_unslash( $_GET['clanspress_team_manage_status'] ) )
			: '';

		$action_url = admin_url( 'admin-post.php' );
		$sections   = $this->get_team_manage_form_sections( $team_id );

		$tab_descriptions = $this->get_team_manage_tab_descriptions();
		$tabs             = array();

		foreach ( $sections as $section_id => $section ) {
			if ( 'roster' === $section_id && ! $this->user_is_team_admin_on_frontend( $team_id ) ) {
				continue;
			}

			if ( ! apply_filters( 'clanspress_team_manage_should_render_section', true, (string) $section_id, $team_id, $this ) ) {
				continue;
			}

			if ( empty( $section['callback'] ) || ! is_callable( $section['callback'] ) ) {
				continue;
			}

			$title = isset( $section['title'] ) ? (string) $section['title'] : '';

			$tabs[] = array(
				'id'          => (string) $section_id,
				'title'       => $title,
				'description' => $tab_descriptions[ $section_id ] ?? '',
			);
		}

		/**
		 * Filter tab definitions for the manage form (order and labels).
		 *
		 * Each item: `id` (section id), `title`, `description` (optional).
		 *
		 * @param array<int, array{id: string, title: string, description: string}> $tabs
		 * @param int   $team_id
		 * @param Teams $extension
		 */
		$tabs = (array) apply_filters( 'clanspress_team_manage_form_tabs', $tabs, $team_id, $this );

		$valid_tabs = array();
		foreach ( $tabs as $tab ) {
			$sid = isset( $tab['id'] ) ? sanitize_key( (string) $tab['id'] ) : '';
			if ( '' === $sid || empty( $sections[ $sid ]['callback'] ) || ! is_callable( $sections[ $sid ]['callback'] ) ) {
				continue;
			}
			$valid_tabs[] = $tab;
		}
		$tabs = $valid_tabs;

		$step_count = max( 1, count( $tabs ) );
		$context    = wp_json_encode(
			array(
				'stepCount' => $step_count,
			)
		);

		/**
		 * Fires before the team manage form markup (after capability checks).
		 *
		 * @param int   $team_id Team post ID.
		 * @param Teams $extension Teams extension instance.
		 */
		do_action( 'clanspress_team_manage_form_before', $team_id, $this );
		?>
		<?php if ( 'saved' === $manage_status ) : ?>
			<p id="clanspress-team-manage-notice" class="clanspress-team-manage-form__notice is-success" role="status" tabindex="-1"><?php esc_html_e( 'Changes saved.', 'clanspress' ); ?></p>
		<?php elseif ( 'roster_invalid' === $manage_status ) : ?>
			<p id="clanspress-team-manage-notice" class="clanspress-team-manage-form__notice is-error" role="alert" tabindex="-1"><?php esc_html_e( 'Roster must include at least one admin.', 'clanspress' ); ?></p>
		<?php elseif ( 'delete_failed' === $manage_status ) : ?>
			<p id="clanspress-team-manage-notice" class="clanspress-team-manage-form__notice is-error" role="alert" tabindex="-1"><?php esc_html_e( 'Could not delete the team. Please try again.', 'clanspress' ); ?></p>
		<?php endif; ?>

		<div
			class="wp-block-clanspress-team-create-form clanspress-team-create-form clanspress-team-manage-form--tabbed"
			data-wp-interactive="clanspress-team-manage-form"
			data-wp-context="<?php echo esc_attr( $context ); ?>"
			data-wp-init="callbacks.init"
		>
		<form class="clanspress-team-create-form__form clanspress-team-manage-form__form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( $action_url ); ?>" data-active-step="1">
			<?php wp_nonce_field( 'clanspress_team_manage_' . $team_id, '_clanspress_team_manage_nonce' ); ?>
			<input type="hidden" name="action" value="clanspress_save_team_manage" />
			<input type="hidden" name="clanspress_team_id" value="<?php echo esc_attr( (string) $team_id ); ?>" />

			<div
				class="clanspress-team-create-form__tabs"
				role="tablist"
				aria-label="<?php esc_attr_e( 'Edit team sections', 'clanspress' ); ?>"
				aria-orientation="horizontal"
				data-wp-on--keydown="actions.onTabListKeydown"
			>
				<?php
				$tab_index = 1;
				foreach ( $tabs as $tab ) :
					$tab_id      = isset( $tab['id'] ) ? sanitize_key( (string) $tab['id'] ) : '';
					$tab_title   = isset( $tab['title'] ) && $tab['title'] !== ''
						? (string) $tab['title']
						: sprintf(
							/* translators: %d: tab index */
							__( 'Step %d', 'clanspress' ),
							$tab_index
						);
					$tab_desc    = isset( $tab['description'] ) ? (string) $tab['description'] : '';
					$is_first    = 1 === $tab_index;
					$tab_class   = 'clanspress-team-create-form__tab' . ( $is_first ? ' is-active' : ' is-upcoming' );
					?>
					<button
						type="button"
						class="<?php echo esc_attr( $tab_class ); ?>"
						role="tab"
						id="clanspress-team-manage-form-tab-<?php echo esc_attr( (string) $tab_index ); ?>"
						data-team-tab="<?php echo esc_attr( (string) $tab_index ); ?>"
						data-wp-on--click="actions.goToStepTab"
						aria-controls="clanspress-team-manage-form-panel-<?php echo esc_attr( (string) $tab_index ); ?>"
						aria-selected="<?php echo $is_first ? 'true' : 'false'; ?>"
						tabindex="<?php echo $is_first ? '0' : '-1'; ?>"
						data-team-section="<?php echo esc_attr( $tab_id ); ?>"
					>
						<span class="clanspress-team-create-form__tab-index" aria-hidden="true"><?php echo esc_html( (string) $tab_index ); ?></span>
						<span class="clanspress-team-create-form__tab-text">
							<span class="clanspress-team-create-form__tab-title"><?php echo esc_html( $tab_title ); ?></span>
							<?php if ( $tab_desc !== '' ) : ?>
								<span class="clanspress-team-create-form__tab-description"><?php echo esc_html( $tab_desc ); ?></span>
							<?php endif; ?>
						</span>
					</button>
					<?php
					++$tab_index;
				endforeach;
				?>
			</div>

			<?php
			$step_number = 1;
			foreach ( $tabs as $tab ) :
				$section_id = isset( $tab['id'] ) ? sanitize_key( (string) $tab['id'] ) : '';

				$section_classes = array(
					'clanspress-team-create-form__step',
					'clanspress-team-manage-form__section',
					'clanspress-team-manage-form__section--' . sanitize_html_class( $section_id ),
				);
				if ( 'profile' === $section_id ) {
					$section_classes[] = 'clanspress-team-manage-form__fields';
				}
				if ( 'roster' === $section_id ) {
					$section_classes[] = 'clanspress-team-manage-form__roster';
				}
				if ( 'team_options' === $section_id ) {
					$section_classes[] = 'clanspress-team-manage-form__matches';
				}
				if ( 'media' === $section_id ) {
					$section_classes[] = 'clanspress-team-manage-form__media';
				}
				?>
				<div
					class="<?php echo esc_attr( implode( ' ', $section_classes ) ); ?>"
					role="tabpanel"
					id="clanspress-team-manage-form-panel-<?php echo esc_attr( (string) $step_number ); ?>"
					aria-labelledby="clanspress-team-manage-form-tab-<?php echo esc_attr( (string) $step_number ); ?>"
					data-team-step="<?php echo esc_attr( (string) $step_number ); ?>"
					data-clanspress-section="<?php echo esc_attr( $section_id ); ?>"
					<?php echo $step_number > 1 ? ' hidden' : ''; ?>
				>
					<?php
					call_user_func( $sections[ $section_id ]['callback'], $team_id, $this );
					/**
					 * Fires after a manage form section’s inner markup.
					 *
					 * @param string $section_id Section key (e.g. profile, team_options).
					 * @param int    $team_id    Team post ID.
					 * @param Teams  $extension  Teams extension instance.
					 */
					do_action( 'clanspress_team_manage_after_section', $section_id, $team_id, $this );
					?>
				</div>
				<?php
				++$step_number;
			endforeach;
			?>

			<?php
			/**
			 * Fires after all default/filtered sections, before navigation and submit.
			 *
			 * @param int   $team_id Team post ID.
			 * @param Teams $extension Teams extension instance.
			 */
			do_action( 'clanspress_team_manage_form_after_sections', $team_id, $this );
			?>

			<div class="clanspress-team-create-form__actions clanspress-team-create-form__actions--split clanspress-team-manage-form__step-actions" role="navigation" aria-label="<?php esc_attr_e( 'Step navigation', 'clanspress' ); ?>">
				<button type="button" class="button clanspress-team-create-form__nav-btn" data-wp-on--click="actions.previousStep" data-wp-bind--hidden="!state.canGoBack()"><?php esc_html_e( 'Back', 'clanspress' ); ?></button>
				<div class="clanspress-team-create-form__actions-end">
					<button type="button" class="button clanspress-team-create-form__nav-btn" data-wp-on--click="actions.nextStep" data-wp-bind--hidden="!state.canGoNext()"><?php esc_html_e( 'Next', 'clanspress' ); ?></button>
					<button type="submit" class="button button-primary clanspress-team-create-form__nav-btn clanspress-team-create-form__nav-btn--primary"><?php esc_html_e( 'Save changes', 'clanspress' ); ?></button>
					<a class="button clanspress-team-create-form__nav-btn" href="<?php echo esc_url( get_permalink( $team_id ) ); ?>"><?php esc_html_e( 'View team', 'clanspress' ); ?></a>
				</div>
			</div>
		</form>
		</div>
		<?php
		$show_delete = (bool) apply_filters(
			'clanspress_team_manage_should_render_delete_form',
			$this->user_can_delete_team_on_frontend( $team_id ),
			$team_id,
			$this
		);
		if ( $show_delete ) {
			$this->render_team_manage_delete_form( $team_id );
		}

		/**
		 * Fires after the team manage form closes.
		 *
		 * @param int   $team_id Team post ID.
		 * @param Teams $extension Teams extension instance.
		 */
		do_action( 'clanspress_team_manage_form_after', $team_id, $this );
	}

	/**
	 * Separate form to delete the team (cannot nest inside the main save/upload form).
	 *
	 * @param int $team_id Team post ID.
	 * @return void
	 */
	protected function render_team_manage_delete_form( int $team_id ): void {
		$delete_action_url = admin_url( 'admin-post.php' );
		?>
		<div class="clanspress-team-manage-form__section clanspress-team-manage-form__section--delete_team clanspress-team-manage-form__danger-zone">
			<h3><?php esc_html_e( 'Delete team', 'clanspress' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Permanently delete this team and its settings. This cannot be undone.', 'clanspress' ); ?>
			</p>
			<form class="clanspress-team-manage-form clanspress-team-manage-form--delete" method="post" action="<?php echo esc_url( $delete_action_url ); ?>">
				<?php wp_nonce_field( 'clanspress_delete_team_' . $team_id, '_clanspress_delete_team_nonce' ); ?>
				<input type="hidden" name="action" value="clanspress_delete_team" />
				<input type="hidden" name="clanspress_team_id" value="<?php echo esc_attr( (string) $team_id ); ?>" />
				<p>
					<label for="clanspress-delete-team-confirm">
						<input
							type="checkbox"
							id="clanspress-delete-team-confirm"
							name="clanspress_delete_team_confirm"
							value="1"
							required
						/>
						<?php esc_html_e( 'I understand this team will be permanently deleted.', 'clanspress' ); ?>
					</label>
				</p>
				<p>
					<button type="submit" class="button button-secondary clanspress-team-manage-form__delete-submit">
						<?php esc_html_e( 'Delete team permanently', 'clanspress' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Logged-out save handler.
	 *
	 * @return void
	 */
	public function handle_save_team_manage_nopriv(): void {
		wp_safe_redirect( wp_login_url( $this->get_current_url() ) );
		exit;
	}

	/**
	 * Save team from front-end manage form.
	 *
	 * @return void
	 */
	public function handle_save_team_manage(): void {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url() );
			exit;
		}

		$team_id = isset( $_POST['clanspress_team_id'] ) ? absint( wp_unslash( $_POST['clanspress_team_id'] ) ) : 0;
		if ( $team_id < 1 ) {
			wp_die( esc_html__( 'Invalid team.', 'clanspress' ), '', array( 'response' => 400 ) );
		}

		check_admin_referer( 'clanspress_team_manage_' . $team_id, '_clanspress_team_manage_nonce' );

		$user_id = get_current_user_id();
		if ( ! $this->user_can_manage_team_on_frontend( $team_id, $user_id ) ) {
			wp_die( esc_html__( 'You cannot edit this team.', 'clanspress' ), '', array( 'response' => 403 ) );
		}

		$post = get_post( $team_id );
		if ( ! $post || 'cp_team' !== $post->post_type ) {
			wp_die( esc_html__( 'Invalid team.', 'clanspress' ), '', array( 'response' => 400 ) );
		}

		$team_name        = sanitize_text_field( wp_unslash( $_POST['team_name'] ?? '' ) );
		$team_code        = sanitize_text_field( wp_unslash( $_POST['team_code'] ?? '' ) );
		$team_motto       = sanitize_text_field( wp_unslash( $_POST['team_motto'] ?? '' ) );
		$team_description = wp_kses_post( wp_unslash( $_POST['team_description'] ?? '' ) );

		if ( '' === $team_name ) {
			wp_safe_redirect(
				add_query_arg(
					'clanspress_team_manage_status',
					'missing_name',
					$this->get_team_manage_url( $team_id )
				)
			);
			exit;
		}

		$team_entity = $this->get_team( $team_id );
		if ( ! $team_entity ) {
			wp_die( esc_html__( 'Invalid team.', 'clanspress' ), '', array( 'response' => 400 ) );
		}

		$team_entity->set_name( $team_name );
		$team_entity->set_description( $team_description );
		$team_entity->set_code( $team_code );
		$team_entity->set_motto( $team_motto );
		$team_entity->set_country( $this->sanitize_team_country_input( (string) wp_unslash( $_POST['team_country'] ?? '' ) ) );
		$team_entity->set_wins( absint( $_POST['team_wins'] ?? 0 ) );
		$team_entity->set_losses( absint( $_POST['team_losses'] ?? 0 ) );
		$team_entity->set_draws( absint( $_POST['team_draws'] ?? 0 ) );

		$team_entity->set_join_mode( $this->sanitize_team_join_mode( wp_unslash( $_POST['team_join_mode'] ?? 'open_join' ) ) );
		$team_entity->set_allow_invites(
			isset( $_POST['team_allow_invites'] ) && '1' === (string) wp_unslash( $_POST['team_allow_invites'] )
		);
		$team_entity->set_allow_frontend_edit(
			isset( $_POST['team_allow_frontend_edit'] ) && '1' === (string) wp_unslash( $_POST['team_allow_frontend_edit'] )
		);
		$team_entity->set_allow_ban_players(
			isset( $_POST['team_allow_ban_players'] ) && '1' === (string) wp_unslash( $_POST['team_allow_ban_players'] )
		);
		$team_entity->set_accept_challenges(
			isset( $_POST['team_accept_challenges'] ) && '1' === (string) wp_unslash( $_POST['team_accept_challenges'] )
		);

		/**
		 * Fires after the manage form has populated the team entity and before it is saved.
		 *
		 * @param int   $team_id     Team post ID.
		 * @param Team  $team_entity Mutable team entity.
		 * @param Teams $extension   Teams extension instance.
		 */
		do_action( 'clanspress_team_manage_before_save', $team_id, $team_entity, $this );

		$this->get_team_data_store()->update( $team_entity );

		$has_avatar_upload = $this->team_manage_form_has_image_upload( 'team_avatar' );
		$has_cover_upload    = $this->team_manage_form_has_image_upload( 'team_cover' );

		if ( ! $has_avatar_upload && ! empty( $_POST['team_avatar_remove'] ) ) {
			$this->maybe_remove_team_manage_image( $team_id, 'cp_team_avatar_id' );
		}
		if ( ! $has_cover_upload && ! empty( $_POST['team_cover_remove'] ) ) {
			$this->maybe_remove_team_manage_image( $team_id, 'cp_team_cover_id' );
		}

		$this->maybe_handle_team_media_upload( $team_id, 'team_avatar', 'cp_team_avatar_id' );
		$this->maybe_handle_team_media_upload( $team_id, 'team_cover', 'cp_team_cover_id' );

		if ( $this->user_is_team_admin_on_frontend( $team_id, $user_id )
			&& isset( $_POST['member_roles'] )
			&& is_array( $_POST['member_roles'] ) ) {
			$new_map = array();
			foreach ( wp_unslash( $_POST['member_roles'] ) as $mid => $role ) {
				$new_map[ (int) $mid ] = $this->sanitize_team_member_role( (string) $role );
			}
			if ( ! $this->persist_team_roles_map( $team_id, $new_map ) ) {
				wp_safe_redirect(
					add_query_arg(
						'clanspress_team_manage_status',
						'roster_invalid',
						$this->get_team_manage_url( $team_id )
					)
				);
				exit;
			}
		}

		wp_safe_redirect(
			add_query_arg(
				'clanspress_team_manage_status',
				'saved',
				$this->get_team_manage_url( $team_id )
			)
		);
		exit;
	}

	/**
	 * Logged-out delete handler.
	 *
	 * @return void
	 */
	public function handle_delete_team_nopriv(): void {
		wp_safe_redirect( wp_login_url( $this->get_current_url() ) );
		exit;
	}

	/**
	 * Permanently delete a team from the front-end manage form.
	 *
	 * @return void
	 */
	public function handle_delete_team(): void {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url() );
			exit;
		}

		$team_id = isset( $_POST['clanspress_team_id'] ) ? absint( wp_unslash( $_POST['clanspress_team_id'] ) ) : 0;
		if ( $team_id < 1 ) {
			wp_die( esc_html__( 'Invalid team.', 'clanspress' ), '', array( 'response' => 400 ) );
		}

		check_admin_referer( 'clanspress_delete_team_' . $team_id, '_clanspress_delete_team_nonce' );

		$user_id = get_current_user_id();
		if ( ! $this->user_can_delete_team_on_frontend( $team_id, $user_id ) ) {
			wp_die( esc_html__( 'You cannot delete this team.', 'clanspress' ), '', array( 'response' => 403 ) );
		}

		if ( empty( $_POST['clanspress_delete_team_confirm'] ) ) {
			wp_safe_redirect(
				add_query_arg(
					'clanspress_team_manage_status',
					'delete_confirm',
					$this->get_team_manage_url( $team_id )
				)
			);
			exit;
		}

		$post = get_post( $team_id );
		if ( ! $post instanceof \WP_Post || 'cp_team' !== $post->post_type ) {
			wp_die( esc_html__( 'Invalid team.', 'clanspress' ), '', array( 'response' => 400 ) );
		}

		$team = $this->get_team( $team_id );
		if ( ! $team ) {
			wp_die( esc_html__( 'Invalid team.', 'clanspress' ), '', array( 'response' => 400 ) );
		}

		$membership_cleanup_user_ids = $this->get_user_ids_for_team_membership_cleanup( $team_id );

		/**
		 * Fires before a team is permanently deleted from the front-end manage UI.
		 *
		 * @param int   $team_id Team post ID.
		 * @param Teams $extension Teams extension instance.
		 */
		do_action( 'clanspress_team_before_delete', $team_id, $this );

		$deleted = $this->get_team_data_store()->delete( $team, true );

		if ( ! $deleted ) {
			wp_safe_redirect(
				add_query_arg(
					'clanspress_team_manage_status',
					'delete_failed',
					$this->get_team_manage_url( $team_id )
				)
			);
			exit;
		}

		$this->cleanup_team_membership_index_for_team_deletion( $team_id, $membership_cleanup_user_ids );

		/**
		 * Fires after a team has been permanently deleted from the front-end manage UI.
		 *
		 * @param int   $team_id Team post ID (no longer exists as a post).
		 * @param int   $user_id User who performed the deletion.
		 * @param Teams $extension Teams extension instance.
		 */
		do_action( 'clanspress_team_deleted', $team_id, $user_id, $this );

		$archive = get_post_type_archive_link( 'cp_team' );
		$target  = is_string( $archive ) && '' !== $archive ? $archive : home_url( '/' );

		/**
		 * Redirect URL after a successful front-end team deletion.
		 *
		 * @param string $url     Destination URL.
		 * @param int    $team_id Deleted team post ID.
		 * @param Teams  $extension Teams extension instance.
		 */
		$redirect = (string) apply_filters( 'clanspress_team_manage_after_delete_redirect', $target, $team_id, $this );

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Labels for the `cp_team` post type (admin list, editor chrome, admin bar, REST).
	 *
	 * @return array<string, string>
	 */
	protected function get_team_post_type_labels(): array {
		return array(
			'name'                     => _x( 'Teams', 'post type general name', 'clanspress' ),
			'singular_name'            => _x( 'Team', 'post type singular name', 'clanspress' ),
			'menu_name'                => _x( 'Teams', 'admin menu', 'clanspress' ),
			'name_admin_bar'           => _x( 'Team', 'add new on admin bar', 'clanspress' ),
			'add_new'                  => _x( 'Add New', 'team', 'clanspress' ),
			'add_new_item'             => __( 'Add New Team', 'clanspress' ),
			'new_item'                 => __( 'New Team', 'clanspress' ),
			'edit_item'                => __( 'Edit Team', 'clanspress' ),
			'view_item'                => __( 'View Team', 'clanspress' ),
			'view_items'               => __( 'View Teams', 'clanspress' ),
			'all_items'                => __( 'All Teams', 'clanspress' ),
			'search_items'             => __( 'Search Teams', 'clanspress' ),
			'not_found'                => __( 'No teams found.', 'clanspress' ),
			'not_found_in_trash'       => __( 'No teams found in Trash.', 'clanspress' ),
			'parent_item_colon'        => __( 'Parent Team:', 'clanspress' ),
			'archives'                 => __( 'Team Archives', 'clanspress' ),
			'attributes'               => __( 'Team Attributes', 'clanspress' ),
			'insert_into_item'         => __( 'Insert into team', 'clanspress' ),
			'uploaded_to_this_item'    => __( 'Uploaded to this team', 'clanspress' ),
			'featured_image'           => __( 'Team image', 'clanspress' ),
			'set_featured_image'       => __( 'Set team image', 'clanspress' ),
			'remove_featured_image'    => __( 'Remove team image', 'clanspress' ),
			'use_featured_image'       => __( 'Use as team image', 'clanspress' ),
			'filter_items_list'        => __( 'Filter teams list', 'clanspress' ),
			'filter_by_date'           => __( 'Filter teams by date', 'clanspress' ),
			'items_list_navigation'    => __( 'Teams list navigation', 'clanspress' ),
			'items_list'               => __( 'Teams list', 'clanspress' ),
			'item_published'           => __( 'Team published.', 'clanspress' ),
			'item_published_privately' => __( 'Team published privately.', 'clanspress' ),
			'item_reverted_to_draft'   => __( 'Team reverted to draft.', 'clanspress' ),
			'item_trashed'             => __( 'Team trashed.', 'clanspress' ),
			'item_scheduled'           => __( 'Team scheduled.', 'clanspress' ),
			'item_updated'             => __( 'Team updated.', 'clanspress' ),
			'item_link'                => __( 'Team Link', 'clanspress' ),
			'item_link_description'    => __( 'A link to a team.', 'clanspress' ),
		);
	}

	/**
	 * Register team post type used by team modes.
	 *
	 * @return void
	 */
	public function register_team_post_type(): void {
		$labels = $this->get_team_post_type_labels();

		register_post_type(
			'cp_team',
			array(
				'label'           => $labels['name'],
				'labels'          => $labels,
				'description'     => __( 'Gaming or sports teams managed by Clanspress.', 'clanspress' ),
				'public'          => true,
				'show_in_rest'    => true,
				'show_in_menu'    => 'clanspress',
				'has_archive'     => true,
				'rewrite'         => array(
					'slug' => 'teams',
				),
				'supports'        => array( 'title', 'editor', 'thumbnail', 'author' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * Register team option meta keys.
	 *
	 * @return void
	 */
	public function register_team_meta(): void {
		$this->register_cp_team_meta_key(
			'cp_team_join_mode',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => 'open_join',
				'sanitize_callback' => array( $this, 'sanitize_team_join_mode' ),
				'show_in_rest'      => true,
			)
		);

		$this->register_cp_team_meta_key(
			'cp_team_allow_invites',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'show_in_rest'      => true,
			)
		);

		$this->register_cp_team_meta_key(
			'cp_team_allow_frontend_edit',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'show_in_rest'      => true,
			)
		);

		$this->register_cp_team_meta_key(
			'cp_team_allow_ban_players',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'show_in_rest'      => true,
			)
		);

		$this->register_cp_team_meta_key(
			'cp_team_code',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			)
		);

		$this->register_cp_team_meta_key(
			'cp_team_motto',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			)
		);

		$this->register_cp_team_meta_key(
			'cp_team_avatar_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
			)
		);

		$this->register_cp_team_meta_key(
			'cp_team_cover_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
			)
		);

		$this->register_cp_team_meta_key(
			'cp_team_member_roles',
			array(
				'type'         => 'array',
				'single'       => true,
				'default'      => array(),
				'show_in_rest' => false,
			)
		);

		$this->register_cp_team_meta_key(
			'cp_team_accept_challenges',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'show_in_rest'      => true,
			)
		);

		$this->register_cp_team_meta_key(
			'cp_team_country',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			)
		);

		$this->register_cp_team_meta_key(
			'cp_team_wins',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
			)
		);

		$this->register_cp_team_meta_key(
			'cp_team_losses',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
			)
		);

		$this->register_cp_team_meta_key(
			'cp_team_draws',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
			)
		);

		$this->register_cp_team_meta_key(
			'cp_team_events_enabled',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Meta box: per-team events toggle (requires global events enabled).
	 *
	 * @return void
	 */
	public function add_team_events_meta_box(): void {
		if ( ! function_exists( 'clanspress_events_extension_active' ) || ! clanspress_events_extension_active() ) {
			return;
		}
		if ( ! function_exists( 'clanspress_events_are_globally_enabled' ) || ! clanspress_events_are_globally_enabled() ) {
			return;
		}

		add_meta_box(
			'clanspress_team_events',
			__( 'Events', 'clanspress' ),
			array( $this, 'render_team_events_meta_box' ),
			'cp_team',
			'side',
			'default'
		);
	}

	/**
	 * Output the team events meta box.
	 *
	 * @param \WP_Post $post Team post.
	 * @return void
	 */
	public function render_team_events_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'clanspress_team_events_meta', 'clanspress_team_events_meta_nonce' );
		$raw = get_post_meta( $post->ID, 'cp_team_events_enabled', true );
		// Empty meta: enabled; explicit off stored as false/0.
		$checked = ! ( false === $raw || 0 === $raw || '0' === $raw );
		?>
		<p>
			<label>
				<input type="checkbox" name="cp_team_events_enabled" value="1" <?php checked( $checked ); ?> />
				<?php esc_html_e( 'Enable scheduled events for this team', 'clanspress' ); ?>
			</label>
		</p>
		<p class="description">
			<?php esc_html_e( 'Uncheck to hide team event routes, listings, and creation for this team.', 'clanspress' ); ?>
		</p>
		<?php
	}

	/**
	 * Save the team events meta box.
	 *
	 * @param int     $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function save_team_events_meta_box( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['clanspress_team_events_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['clanspress_team_events_meta_nonce'] ) ), 'clanspress_team_events_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( 'cp_team' !== $post->post_type ) {
			return;
		}

		$enabled_raw = isset( $_POST['cp_team_events_enabled'] ) ? wp_unslash( $_POST['cp_team_events_enabled'] ) : '';
		$enabled     = ( '1' === (string) $enabled_raw );
		update_post_meta( $post_id, 'cp_team_events_enabled', $enabled );
	}

	/**
	 * Register one `cp_team` meta key with a consistent auth callback for REST.
	 *
	 * @param string              $meta_key Meta key (e.g. cp_team_code).
	 * @param array<string,mixed> $args     Arguments for {@see register_post_meta()}.
	 * @return void
	 */
	protected function register_cp_team_meta_key( string $meta_key, array $args ): void {
		$args['auth_callback'] = array( $this, 'team_meta_auth_callback' );
		register_post_meta( 'cp_team', $meta_key, $args );
	}

	/**
	 * Allow reading/updating registered team meta when the user can edit the team post.
	 *
	 * {@see register_meta()} passes: `$allowed`, `$meta_key`, `$object_id`, `$user_id`, `$cap`, `$caps`.
	 *
	 * @param mixed ...$args Filter arguments from `auth_post_meta_*`.
	 * @return bool
	 */
	public function team_meta_auth_callback( ...$args ): bool {
		$object_id = isset( $args[2] ) ? (int) $args[2] : 0;

		if ( $object_id < 1 || 'cp_team' !== get_post_type( $object_id ) ) {
			return false;
		}

		// Full `register_meta` callback (6 args): check the target user when provided.
		if ( isset( $args[3] ) ) {
			$uid = (int) $args[3];
			if ( $uid > 0 ) {
				return user_can( $uid, 'edit_post', $object_id );
			}
		}

		return current_user_can( 'edit_post', $object_id );
	}

	/**
	 * Ensure team `meta` in REST responses includes all registered keys (helps block editor hydration).
	 *
	 * @param \WP_REST_Response $response Response.
	 * @param \WP_Post          $post     Post object.
	 * @param \WP_REST_Request    $request  Request.
	 * @return \WP_REST_Response
	 */
	public function rest_prepare_cp_team_merge_meta( $response, $post, $request ) {
		unset( $request );

		if ( ! $response instanceof \WP_REST_Response || ! $post instanceof \WP_Post || 'cp_team' !== $post->post_type ) {
			return $response;
		}

		$data = $response->get_data();
		if ( ! isset( $data['meta'] ) || ! is_array( $data['meta'] ) ) {
			$data['meta'] = array();
		}

		$rest_keys = array(
			'cp_team_join_mode',
			'cp_team_allow_invites',
			'cp_team_allow_frontend_edit',
			'cp_team_allow_ban_players',
			'cp_team_code',
			'cp_team_motto',
			'cp_team_avatar_id',
			'cp_team_cover_id',
			'cp_team_accept_challenges',
			'cp_team_country',
			'cp_team_wins',
			'cp_team_losses',
			'cp_team_draws',
		);

		foreach ( $rest_keys as $key ) {
			if ( array_key_exists( $key, $data['meta'] ) ) {
				continue;
			}
			$raw = get_post_meta( $post->ID, $key, true );
			if ( 'cp_team_join_mode' === $key ) {
				$data['meta'][ $key ] = $this->sanitize_team_join_mode( $raw );
				continue;
			}
			if ( in_array( $key, array( 'cp_team_allow_invites', 'cp_team_allow_frontend_edit', 'cp_team_allow_ban_players', 'cp_team_accept_challenges' ), true ) ) {
				$data['meta'][ $key ] = ( '' === $raw || null === $raw ) ? true : rest_sanitize_boolean( $raw );
				continue;
			}
			if ( in_array( $key, array( 'cp_team_avatar_id', 'cp_team_wins', 'cp_team_losses', 'cp_team_draws' ), true ) ) {
				$data['meta'][ $key ] = (int) $raw;
				continue;
			}
			$data['meta'][ $key ] = is_string( $raw ) ? $raw : (string) $raw;
		}

		$response->set_data( $data );

		return $response;
	}

	/**
	 * Re-write team meta after front-end create so DB + REST match the submitted wizard (and uploads).
	 *
	 * The data store skips empty strings; direct updates here guarantee keys exist for the editor.
	 *
	 * @param int   $team_id New team post ID.
	 * @param array $fields  Keys: code, motto, country (sanitized ISO or '').
	 * @return void
	 */
	protected function sync_team_meta_after_front_end_create( int $team_id, array $fields ): void {
		if ( $team_id < 1 ) {
			return;
		}

		update_post_meta( $team_id, 'cp_team_code', sanitize_text_field( (string) ( $fields['code'] ?? '' ) ) );
		update_post_meta( $team_id, 'cp_team_motto', sanitize_text_field( (string) ( $fields['motto'] ?? '' ) ) );

		$country = isset( $fields['country'] ) ? sanitize_text_field( (string) $fields['country'] ) : '';
		if ( '' === $country ) {
			delete_post_meta( $team_id, 'cp_team_country' );
		} else {
			update_post_meta( $team_id, 'cp_team_country', $country );
		}

		update_post_meta( $team_id, 'cp_team_wins', 0 );
		update_post_meta( $team_id, 'cp_team_losses', 0 );
		update_post_meta( $team_id, 'cp_team_draws', 0 );

		$team = $this->get_team( $team_id );
		if ( ! $team ) {
			return;
		}

		update_post_meta( $team_id, 'cp_team_join_mode', $this->sanitize_team_join_mode( $team->get_join_mode() ) );
		update_post_meta( $team_id, 'cp_team_allow_invites', $team->get_allow_invites() );
		update_post_meta( $team_id, 'cp_team_allow_frontend_edit', $team->get_allow_frontend_edit() );
		update_post_meta( $team_id, 'cp_team_allow_ban_players', $team->get_allow_ban_players() );
		update_post_meta( $team_id, 'cp_team_accept_challenges', $team->get_accept_challenges() );
		update_post_meta( $team_id, 'cp_team_avatar_id', $team->get_avatar_id() );
		update_post_meta( $team_id, 'cp_team_cover_id', $team->get_cover_id() );
	}

	/**
	 * Enqueue block editor assets for team options panel.
	 *
	 * @return void
	 */
	public function enqueue_team_editor_assets(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'cp_team' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'clanspress-team-options-editor',
			clanspress()->url . 'assets/js/admin/team-options-editor.js',
			array(
				'wp-plugins',
				'wp-edit-post',
				'wp-element',
				'wp-components',
				'wp-data',
				'wp-core-data',
				'wp-block-editor',
				'wp-i18n',
				'wp-hooks',
			),
			clanspress()->get_version(),
			true
		);

		$country_options = array(
			array(
				'value' => '',
				'label' => __( '— Select —', 'clanspress' ),
			),
		);
		if ( function_exists( 'clanspress_players_get_countries' ) ) {
			foreach ( clanspress_players_get_countries() as $code => $name ) {
				$country_options[] = array(
					'value' => (string) $code,
					'label' => (string) $name,
				);
			}
		}

		$defaults = array(
			'avatarUrl' => '',
			'coverUrl'  => '',
		);
		if ( function_exists( 'clanspress_teams_get_default_avatar_url' ) ) {
			$defaults['avatarUrl'] = clanspress_teams_get_default_avatar_url( 0 );
		}
		if ( function_exists( 'clanspress_teams_get_default_cover_url' ) ) {
			$defaults['coverUrl'] = clanspress_teams_get_default_cover_url( 0 );
		}

		wp_localize_script(
			'clanspress-team-options-editor',
			'clanspressTeamEditor',
			array(
				'countries' => $country_options,
				'defaults'  => $defaults,
			)
		);
	}

	/**
	 * Validate a country code against the Players country list.
	 *
	 * @param string $raw Submitted value.
	 * @return string ISO code or empty.
	 */
	protected function sanitize_team_country_input( string $raw ): string {
		$raw = sanitize_text_field( $raw );
		if ( '' === $raw ) {
			return '';
		}

		if ( ! function_exists( 'clanspress_players_get_countries' ) ) {
			return '';
		}

		$countries = clanspress_players_get_countries();

		return isset( $countries[ $raw ] ) ? $raw : '';
	}

	/**
	 * Get allowed team join modes.
	 *
	 * @return array<string, string>
	 */
	public function get_team_join_modes(): array {
		$modes = array(
			'open_join'            => __( 'Open join', 'clanspress' ),
			'join_with_permission' => __( 'Join with permission', 'clanspress' ),
			'invite_only'          => __( 'Invite only', 'clanspress' ),
		);

		/**
		 * Filter available team join modes.
		 *
		 * @param array $modes Team join modes.
		 * @param Teams $extension Teams extension instance.
		 */
		return (array) apply_filters( 'clanspress_team_join_modes', $modes, $this );
	}

	/**
	 * Sanitize team join mode.
	 *
	 * @param mixed $value Join mode value.
	 * @return string
	 */
	public function sanitize_team_join_mode( $value ): string {
		$value = sanitize_key( (string) $value );
		$modes = array_keys( $this->get_team_join_modes() );

		if ( ! in_array( $value, $modes, true ) ) {
			return 'open_join';
		}

		return $value;
	}

	/**
	 * Get per-team option state.
	 *
	 * @param int $team_id Team post ID.
	 * @return array<string, mixed>
	 */
	public function get_team_options( int $team_id ): array {
		$accept_raw = get_post_meta( $team_id, 'cp_team_accept_challenges', true );
		$accept_challenges = ( '' === $accept_raw ) ? true : rest_sanitize_boolean( $accept_raw );

		$team_obj = $this->get_team( $team_id );

		$options = array(
			'join_mode'           => $this->sanitize_team_join_mode( get_post_meta( $team_id, 'cp_team_join_mode', true ) ),
			'allow_invites'       => rest_sanitize_boolean( get_post_meta( $team_id, 'cp_team_allow_invites', true ) ),
			'allow_frontend_edit' => rest_sanitize_boolean( get_post_meta( $team_id, 'cp_team_allow_frontend_edit', true ) ),
			'allow_ban_players'   => rest_sanitize_boolean( get_post_meta( $team_id, 'cp_team_allow_ban_players', true ) ),
			'accept_challenges'   => $accept_challenges,
			'country'             => $team_obj ? $team_obj->get_country() : '',
			'wins'                => $team_obj ? $team_obj->get_wins() : 0,
			'losses'              => $team_obj ? $team_obj->get_losses() : 0,
			'draws'               => $team_obj ? $team_obj->get_draws() : 0,
		);

		/**
		 * Filter resolved team options.
		 *
		 * @param array $options Team options map.
		 * @param int   $team_id Team post ID.
		 * @param Teams $extension Teams extension instance.
		 */
		return (array) apply_filters( 'clanspress_team_options', $options, $team_id, $this );
	}

	/**
	 * Update per-team options.
	 *
	 * @param int   $team_id Team post ID.
	 * @param array $options Options map.
	 * @return void
	 */
	public function update_team_options( int $team_id, array $options ): void {
		$sanitized = array(
			'join_mode'           => $this->sanitize_team_join_mode( $options['join_mode'] ?? 'open_join' ),
			'allow_invites'       => rest_sanitize_boolean( $options['allow_invites'] ?? true ),
			'allow_frontend_edit' => rest_sanitize_boolean( $options['allow_frontend_edit'] ?? true ),
			'allow_ban_players'   => rest_sanitize_boolean( $options['allow_ban_players'] ?? true ),
			'accept_challenges'   => rest_sanitize_boolean( $options['accept_challenges'] ?? true ),
		);

		$team_entity = $this->get_team( $team_id );
		if ( $team_entity ) {
			$team_entity->set_join_mode( $sanitized['join_mode'] );
			$team_entity->set_allow_invites( $sanitized['allow_invites'] );
			$team_entity->set_allow_frontend_edit( $sanitized['allow_frontend_edit'] );
			$team_entity->set_allow_ban_players( $sanitized['allow_ban_players'] );
			$team_entity->set_accept_challenges( $sanitized['accept_challenges'] );
			$this->get_team_data_store()->update( $team_entity );
		}

		/**
		 * Fires after team options are updated.
		 *
		 * @param int   $team_id Team post ID.
		 * @param array $sanitized Sanitized options map.
		 * @param Teams $extension Teams extension instance.
		 */
		do_action( 'clanspress_team_options_updated', $team_id, $sanitized, $this );
	}

	/**
	 * Check whether a user can join a team based on join mode.
	 *
	 * @param int $team_id Team post ID.
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public function can_user_join_team( int $team_id, int $user_id ): bool {
		$options  = $this->get_team_options( $team_id );
		$can_join = 'open_join' === $options['join_mode'];

		/**
		 * Filter team join permission.
		 *
		 * @param bool  $can_join Whether user can join.
		 * @param int   $team_id Team post ID.
		 * @param int   $user_id User ID.
		 * @param array $options Team options.
		 * @param Teams $extension Teams extension instance.
		 */
		return (bool) apply_filters( 'clanspress_can_user_join_team', $can_join, $team_id, $user_id, $options, $this );
	}

	/**
	 * Check if invites are enabled for a team.
	 *
	 * @param int $team_id Team post ID.
	 * @return bool
	 */
	public function can_invite_players( int $team_id ): bool {
		$options = $this->get_team_options( $team_id );
		$allowed = (bool) $options['allow_invites'];

		return (bool) apply_filters( 'clanspress_team_can_invite_players', $allowed, $team_id, $options, $this );
	}

	/**
	 * Check if team can be edited from front-end.
	 *
	 * @param int $team_id Team post ID.
	 * @return bool
	 */
	public function can_edit_team_frontend( int $team_id ): bool {
		$options = $this->get_team_options( $team_id );
		$allowed = (bool) $options['allow_frontend_edit'];

		return (bool) apply_filters( 'clanspress_team_can_edit_frontend', $allowed, $team_id, $options, $this );
	}

	/**
	 * Check if banning players is enabled for a team.
	 *
	 * @param int $team_id Team post ID.
	 * @return bool
	 */
	public function can_ban_players( int $team_id ): bool {
		$options = $this->get_team_options( $team_id );
		$allowed = (bool) $options['allow_ban_players'];

		return (bool) apply_filters( 'clanspress_team_can_ban_players', $allowed, $team_id, $options, $this );
	}

	/**
	 * Handle front-end team creation post request.
	 *
	 * @return void
	 */
	public function handle_create_team(): void {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( wp_get_referer() ?: home_url( '/' ) ) );
			exit;
		}

		check_admin_referer( 'clanspress_create_team_action', '_clanspress_create_team_nonce' );

		$user_id          = get_current_user_id();
		$team_name        = sanitize_text_field( wp_unslash( $_POST['team_name'] ?? '' ) );
		$team_code        = sanitize_text_field( wp_unslash( $_POST['team_code'] ?? '' ) );
		$team_motto       = sanitize_text_field( wp_unslash( $_POST['team_motto'] ?? '' ) );
		$team_description = wp_kses_post( wp_unslash( $_POST['team_description'] ?? '' ) );
		$team_invites_raw = sanitize_text_field( wp_unslash( $_POST['team_invites'] ?? '' ) );
		$team_country     = $this->sanitize_team_country_input( (string) wp_unslash( $_POST['team_country'] ?? '' ) );

		if ( '' === $team_name ) {
			$this->redirect_after_team_create( false, 'missing_name' );
		}

		$team_slug = sanitize_title( $team_name );
		$team_slug = (string) apply_filters( 'clanspress_pre_insert_team_slug', $team_slug, $team_name, $user_id );

		$post_status = (string) apply_filters( 'clanspress_create_team_post_status', 'publish', $user_id );

		$team = new Team();
		$team->set_name( $team_name );
		$team->set_slug( $team_slug );
		$team->set_description( $team_description );
		$team->set_status( $post_status );
		$team->set_author_id( $user_id );
		$team->set_code( $team_code );
		$team->set_motto( $team_motto );
		$team->set_country( $team_country );
		$team->set_wins( 0 );
		$team->set_losses( 0 );
		$team->set_draws( 0 );

		if ( function_exists( 'clanspress_matches' ) && clanspress_matches() ) {
			if ( array_key_exists( 'team_accept_challenges', $_POST ) ) {
				$team->set_accept_challenges( '1' === (string) wp_unslash( $_POST['team_accept_challenges'] ) );
			} else {
				$team->set_accept_challenges( true );
			}
		} else {
			$team->set_accept_challenges( true );
		}

		$this->get_team_data_store()->create( $team );
		$new_team_id = $team->get_id();

		if ( $new_team_id < 1 ) {
			$this->redirect_after_team_create( false, 'insert_failed' );
		}

		$this->maybe_handle_team_media_upload( (int) $new_team_id, 'team_avatar', 'cp_team_avatar_id' );
		$this->maybe_handle_team_media_upload( (int) $new_team_id, 'team_cover', 'cp_team_cover_id' );

		$invite_tokens = array();

		if ( '' !== $team_invites_raw && $this->can_invite_players( (int) $new_team_id ) ) {
			$invite_tokens = array_filter(
				array_map(
					'absint',
					explode( ',', $team_invites_raw )
				)
			);

			/**
			 * Filter pending invite tokens parsed from create-team flow.
			 *
			 * @param array $invite_tokens Parsed invite values.
			 * @param int   $new_team_id Team post ID.
			 * @param int   $user_id Creator ID.
			 */
			$invite_tokens = (array) apply_filters( 'clanspress_team_create_invite_tokens', $invite_tokens, (int) $new_team_id, $user_id );

			$invite_tokens = array_values(
				array_filter(
					array_map( 'absint', $invite_tokens ),
					function ( int $uid ): bool {
						return $uid > 0 && $this->user_can_appear_in_team_invite_search( $uid );
					}
				)
			);
		}

		$this->initialize_team_roster( (int) $new_team_id, $user_id, $invite_tokens );
		delete_post_meta( (int) $new_team_id, 'cp_team_pending_invites' );

		$this->sync_team_meta_after_front_end_create(
			(int) $new_team_id,
			array(
				'code'    => $team_code,
				'motto'   => $team_motto,
				'country' => $team_country,
			)
		);

		/**
		 * Fires after a team is created via the block-based create team form.
		 *
		 * @param int   $new_team_id Team post ID.
		 * @param int   $user_id     Creator user ID.
		 * @param array $request     Raw request payload.
		 */
		do_action( 'clanspress_team_created', (int) $new_team_id, $user_id, $_POST );

		$this->redirect_after_team_create( true, 'created', (int) $new_team_id );
	}

	/**
	 * Ajax search endpoint for invite-able users.
	 *
	 * @return void
	 */
	public function ajax_team_invite_search(): void {
		check_ajax_referer( 'clanspress_team_invite_search', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array(), 403 );
		}

		$query = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) );
		if ( '' === $query || strlen( $query ) < 2 ) {
			wp_send_json_success( array() );
		}

		$current_id = get_current_user_id();

		$users = get_users(
			array(
				'number'         => 15,
				'search'         => '*' . esc_attr( $query ) . '*',
				'search_columns' => array( 'user_login', 'display_name', 'user_email' ),
				'fields'         => array( 'ID', 'display_name', 'user_login', 'user_email' ),
				'exclude'        => $current_id ? array( $current_id ) : array(),
			)
		);

		$site_admin_search = $this->user_is_teams_site_admin( $current_id );

		$users = array_values(
			array_filter(
				$users,
				function ( $user ) use ( $site_admin_search ): bool {
					if ( ! $user instanceof \WP_User ) {
						return false;
					}
					if ( $site_admin_search ) {
						return true;
					}
					return $this->user_can_appear_in_team_invite_search( (int) $user->ID );
				}
			)
		);

		$users = array_slice( $users, 0, 10 );

		$results = array_map(
			static function ( \WP_User $user ): array {
				return array(
					'id'    => (int) $user->ID,
					'label' => sprintf( '%1$s (%2$s)', $user->display_name, $user->user_login ),
				);
			},
			$users
		);

		wp_send_json_success( $results );
	}

	/**
	 * Whether the manage form submitted a new image file for a field.
	 *
	 * @param string $field_name `$_FILES` key (e.g. team_avatar).
	 * @return bool
	 */
	protected function team_manage_form_has_image_upload( string $field_name ): bool {
		if ( empty( $_FILES[ $field_name ] ) || ! is_array( $_FILES[ $field_name ] ) ) {
			return false;
		}

		$err = isset( $_FILES[ $field_name ]['error'] ) ? (int) $_FILES[ $field_name ]['error'] : UPLOAD_ERR_NO_FILE;

		return UPLOAD_ERR_OK === $err
			&& ! empty( $_FILES[ $field_name ]['size'] );
	}

	/**
	 * Remove a team avatar or cover attachment and meta (manage form).
	 *
	 * @param int    $team_id Team post ID.
	 * @param string $meta_key `cp_team_avatar_id` or `cp_team_cover_id`.
	 * @return void
	 */
	protected function maybe_remove_team_manage_image( int $team_id, string $meta_key ): void {
		if ( ! in_array( $meta_key, array( 'cp_team_avatar_id', 'cp_team_cover_id' ), true ) ) {
			return;
		}

		$old_id = (int) get_post_meta( $team_id, $meta_key, true );
		if ( $old_id > 0 ) {
			wp_delete_attachment( $old_id, true );
		}

		delete_post_meta( $team_id, $meta_key );
	}

	/**
	 * Handle optional media upload for team create flow.
	 *
	 * @param int    $team_id Team post ID.
	 * @param string $field_name Form file input name.
	 * @param string $meta_key Meta key to store attachment ID.
	 * @return void
	 */
	protected function maybe_handle_team_media_upload( int $team_id, string $field_name, string $meta_key ): void {
		if ( ! function_exists( 'clanspress_handle_isolated_image_upload' ) ) {
			return;
		}

		// Empty file inputs still populate $_FILES with UPLOAD_ERR_NO_FILE; never delete existing media in that case.
		if ( ! $this->team_manage_form_has_image_upload( $field_name ) ) {
			return;
		}

		if ( empty( $_FILES[ $field_name ] ) || ! is_array( $_FILES[ $field_name ] ) ) {
			return;
		}

		$subdir = 'clanspress/teams/' . $team_id;
		$base   = 'cp_team_avatar_id' === $meta_key ? 'avatar' : 'cover';

		$old_id = (int) get_post_meta( $team_id, $meta_key, true );

		$attachment_id = clanspress_handle_isolated_image_upload( $field_name, $team_id, $subdir, $base );
		if ( is_wp_error( $attachment_id ) ) {
			return;
		}

		$new_id = (int) $attachment_id;
		update_post_meta( $team_id, $meta_key, $new_id );

		if ( $old_id > 0 && $old_id !== $new_id ) {
			wp_delete_attachment( $old_id, true );
		}
	}

	/**
	 * Use the plugin Single Team block template when the theme also defines single-cp_team (often Post Content only).
	 *
	 * @param \WP_Block_Template[] $query_result Block templates.
	 * @param array<string, mixed> $query        Query args.
	 * @param string               $template_type Template type.
	 * @return \WP_Block_Template[]
	 */
	public function prefer_plugin_single_cp_team_block_template( $query_result, $query, $template_type ) {
		if ( 'wp_template' !== $template_type || empty( $query['slug__in'] ) || ! in_array( 'single-cp_team', $query['slug__in'], true ) ) {
			return $query_result;
		}

		if ( ! is_singular( 'cp_team' ) ) {
			return $query_result;
		}

		if ( ! is_array( $query_result ) ) {
			return $query_result;
		}

		$filtered = array();
		foreach ( $query_result as $t ) {
			if ( ! $t instanceof \WP_Block_Template ) {
				continue;
			}
			if ( 'single-cp_team' !== $t->slug ) {
				$filtered[] = $t;
				continue;
			}
			if ( ! empty( $t->is_custom ) ) {
				$filtered[] = $t;
				continue;
			}
			if ( isset( $t->id ) && 'clanspress//single-cp_team' === $t->id ) {
				$filtered[] = $t;
			}
		}

		foreach ( $filtered as $t ) {
			if ( $t instanceof \WP_Block_Template && 'single-cp_team' === $t->slug ) {
				return $filtered;
			}
		}

		$plugin = \get_block_template( 'clanspress//single-cp_team' );
		if ( $plugin instanceof \WP_Block_Template ) {
			$filtered[] = $plugin;
		}

		return $filtered;
	}

	/**
	 * Redirect helper for create team flow.
	 *
	 * @param bool   $success Whether request succeeded.
	 * @param string $code Status code.
	 * @param int    $team_id Optional team post ID.
	 * @return void
	 */
	protected function redirect_after_team_create( bool $success, string $code, int $team_id = 0 ): void {
		$fallback = wp_get_referer() ?: $this->get_team_create_url();

		if ( $success && $team_id > 0 ) {
			$post = get_post( $team_id );
			if ( ! $post instanceof \WP_Post || 'cp_team' !== $post->post_type ) {
				$success = false;
				$code    = 'missing_post';
				$team_id = 0;
			} else {
				$redirect = get_permalink( $post );
				if ( ! is_string( $redirect ) || '' === $redirect ) {
					$redirect = '';
				}

				$archive = get_post_type_archive_link( 'cp_team' );
				$slug    = (string) $post->post_name;

				// Avoid the teams archive or empty permalinks: single-team URL only.
				if ( '' === $redirect
					|| ( is_string( $archive ) && '' !== $archive
						&& untrailingslashit( $redirect ) === untrailingslashit( $archive ) ) ) {
					$redirect = '' !== $slug
						? home_url( user_trailingslashit( 'teams/' . $slug ) )
						: (string) get_permalink( $team_id );
				}

				if ( ! is_string( $redirect ) || '' === $redirect ) {
					$redirect = $fallback;
				}

				/**
				 * Filter redirect URL after successful team creation.
				 *
				 * Default: the new team’s permalink (`/teams/{slug}/`), not the archive with query args.
				 *
				 * @param string $redirect Redirect URL.
				 * @param int    $team_id  New team post ID.
				 * @param Teams  $extension Teams extension instance.
				 */
				$redirect = (string) apply_filters( 'clanspress_teams_after_create_redirect', $redirect, $team_id, $this );

				wp_safe_redirect( $redirect );
				exit;
			}
		}

		$args = array(
			'clanspress_team_status' => 'error',
			'clanspress_team_code'   => $code,
		);

		wp_safe_redirect( add_query_arg( $args, $fallback ) );
		exit;
	}

	/**
	 * Get a teams extension setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	public function get_setting( string $key, $fallback = null ) {
		return $this->admin->get( $key, $fallback );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_settings_admin(): ?Abstract_Settings {
		return isset( $this->admin ) ? $this->admin : null;
	}

	/**
	 * Get current teams mode.
	 *
	 * @return string
	 */
	public function get_team_mode(): string {
		$allowed_modes = array(
			'single_team',
			'multiple_teams',
			'team_directories',
		);

		$team_mode = sanitize_key( (string) $this->get_setting( 'team_mode', 'single_team' ) );

		if ( ! in_array( $team_mode, $allowed_modes, true ) ) {
			$team_mode = 'single_team';
		}

		/**
		 * Filter resolved teams mode.
		 *
		 * @param string $team_mode Resolved teams mode.
		 * @param Teams  $extension Teams extension instance.
		 */
		return (string) apply_filters( 'clanspress_teams_mode', $team_mode, $this );
	}

	/**
	 * Whether teams mode is "single_team".
	 *
	 * @return bool
	 */
	public function is_single_team_mode(): bool {
		return 'single_team' === $this->get_team_mode();
	}

	/**
	 * Whether teams mode is "multiple_teams".
	 *
	 * @return bool
	 */
	public function is_multiple_teams_mode(): bool {
		return 'multiple_teams' === $this->get_team_mode();
	}

	/**
	 * Whether teams mode is "team_directories".
	 *
	 * @return bool
	 */
	public function is_team_directories_mode(): bool {
		return 'team_directories' === $this->get_team_mode();
	}

	/**
	 * Register all blocks owned by the Teams extension.
	 *
	 * @return void
	 */
	public function register_team_blocks(): void {
		// Collection path must be the parent of each block folder (see WP_Block_Metadata_Registry::get_collection_block_metadata_files()).
		add_filter( 'register_block_type_args', array( $this, 'filter_team_create_form_block_args' ), 10, 2 );
		$this->register_extension_block_types_from_metadata_collection( 'build/teams' );

		register_block_type(
			'clanspress/team-manage',
			array(
				'api_version'     => '3',
				'render_callback' => array( $this, 'render_team_manage_block' ),
				'supports'        => array(
					'inserter' => false,
					'html'     => false,
				),
			)
		);
	}

	/**
	 * Hide the team create form from the block inserter when directory mode is off.
	 *
	 * @param array<string, mixed> $args Block type args.
	 * @param string               $name Block name.
	 * @return array<string, mixed>
	 */
	public function filter_team_create_form_block_args( array $args, string $name ): array {
		if ( 'clanspress/team-create-form' !== $name ) {
			return $args;
		}

		if ( ! $this->is_team_directories_mode() ) {
			$supports          = isset( $args['supports'] ) && is_array( $args['supports'] ) ? $args['supports'] : array();
			$args['supports']  = array_merge( $supports, array( 'inserter' => false ) );
		}

		return $args;
	}

	/**
	 * Plugin block templates never call the_post(); prime the main query so team blocks and get_the_ID() work on the front end.
	 *
	 * @param mixed|null           $pre_render   Short-circuit value.
	 * @param array<string, mixed> $parsed_block Parsed block.
	 * @param \WP_Block|null       $parent_block Parent block.
	 * @return mixed|null
	 */
	public function prime_cp_team_single_loop_for_plugin_template( $pre_render, $parsed_block, $parent_block ) {
		unset( $parsed_block, $parent_block );

		static $done = false;
		if ( $done || null !== $pre_render ) {
			return $pre_render;
		}

		global $wp_query, $_wp_current_template_id;

		if ( ! $wp_query instanceof \WP_Query || ! $wp_query->is_singular( 'cp_team' ) || in_the_loop() ) {
			return null;
		}

		$tpl_id = ( isset( $_wp_current_template_id ) && is_string( $_wp_current_template_id ) ) ? $_wp_current_template_id : '';
		if ( '' === $tpl_id || ! str_starts_with( $tpl_id, 'clanspress//' ) ) {
			return null;
		}

		if ( $wp_query->have_posts() ) {
			$wp_query->the_post();
			$done = true;
		}

		return null;
	}

	/**
	 * Pass the current team post into block context on singular templates (nested blocks often lack postId on the front end).
	 *
	 * @param array<string, mixed> $context      Block context.
	 * @param array<string, mixed> $parsed_block Parsed block.
	 * @param \WP_Block|null       $parent_block Parent block.
	 * @return array<string, mixed>
	 */
	public function filter_team_singular_block_context( $context, $parsed_block, $parent_block ) {
		unset( $parent_block );

		if ( ! is_array( $context ) ) {
			$context = array();
		}

		$block_name = isset( $parsed_block['blockName'] ) ? (string) $parsed_block['blockName'] : '';
		$is_team_block_family = ( '' !== $block_name && strpos( $block_name, 'clanspress/team-' ) === 0 );
		$is_player_query      = ( 'clanspress/player-query' === $block_name );
		$is_player_template   = ( 'clanspress/player-template' === $block_name );
		if ( ! $is_team_block_family && ! $is_player_query && ! $is_player_template ) {
			return $context;
		}

		// Preserve Query Loop / parent-provided context.
		if ( ! empty( $context['postId'] ) ) {
			$pid = (int) $context['postId'];
			if ( $pid > 0 ) {
				$ptype = isset( $context['postType'] ) ? (string) $context['postType'] : '';
				if ( 'cp_team' === $ptype || 'cp_team' === get_post_type( $pid ) ) {
					return $context;
				}
			}
		}

		$team_id = function_exists( 'clanspress_team_block_resolve_team_id' )
			? clanspress_team_block_resolve_team_id( $context )
			: 0;

		if ( $team_id > 0 ) {
			$context['postId']   = $team_id;
			$context['postType'] = 'cp_team';
		}

		return $context;
	}

	/**
	 * Server render for team manage FSE template (block templates do not run PHP from file content).
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $content    Inner content.
	 * @param \WP_Block            $block      Block instance.
	 * @return string
	 */
	public function render_team_manage_block( array $attributes, string $content, \WP_Block $block ): string {
		unset( $attributes, $content, $block );

		$team_id = (int) get_query_var( 'clanspress_manage_team_id' );

		// In Site Editor previews the manage route query var is usually missing; show a safe placeholder.
		if ( $team_id < 1 ) {
			return sprintf(
				'<div class="clanspress-team-manage--placeholder"><p>%s</p></div>',
				esc_html__( 'Select a team to manage.', 'clanspress' )
			);
		}

		ob_start();
		$this->render_frontend_team_manage( $team_id );

		return (string) ob_get_clean();
	}

	/**
	 * Register all FSE templates owned by the Teams extension.
	 *
	 * @return void
	 */
	public function register_team_templates(): void {
		$this->register_extension_templates( $this->get_team_templates() );
	}

	/**
	 * Load the team block template on classic themes (block markup is not in post_content).
	 *
	 * Block themes resolve `clanspress//single-cp_team` via {@see register_block_template()} when
	 * `post_types` includes `cp_team`. PHP themes need `do_blocks()` here.
	 *
	 * @param string $template Path from {@see locate_template()}.
	 * @return string
	 */
	public function maybe_single_team_template( string $template ): string {
		if ( ! is_singular( 'cp_team' ) ) {
			return $template;
		}

		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			return $template;
		}

		$plugin = clanspress()->path . 'templates/teams/single-cp_team-classic.php';

		return is_readable( $plugin ) ? $plugin : $template;
	}

	/**
	 * Get all FSE templates owned by the Teams extension.
	 *
	 * @return array<string, array<string, string>>
	 */
	protected function get_team_templates(): array {
		// Slug must be `single-cp_team` so it matches the singular template hierarchy for this CPT.
		$templates = array(
			'single-cp_team' => array(
				'title'       => __( 'Single Team', 'clanspress' ),
				'description' => __( 'Team profile with cover, avatar, record, motto, and description.', 'clanspress' ),
				'path'        => clanspress()->path . 'templates/teams/single-cp_team.html',
				// WP 6.7+: tie the plugin template to this CPT so singular views use it in the Site Editor hierarchy.
				'post_types'  => array( 'cp_team' ),
			),
			// Virtual routes (create / manage / events) stay gated by `is_team_directories_mode()` in rewrites
			// and template loaders; templates are always registered so they remain editable in the Site Editor.
			'teams-create'          => array(
				'title'       => __( 'Teams — Create', 'clanspress' ),
				'description' => __( 'Create team screen at /teams/create/ when team directory mode is enabled.', 'clanspress' ),
				'path'        => clanspress()->path . 'templates/teams/teams-create.html',
			),
			'teams-manage'          => array(
				'title'       => __( 'Teams — Manage', 'clanspress' ),
				'description' => __( 'Team management and settings at /teams/{slug}/manage/ when directory mode is enabled.', 'clanspress' ),
				'path'        => clanspress()->path . 'templates/teams/teams-manage.html',
			),
			'teams-events'          => array(
				'title'       => __( 'Teams — Events', 'clanspress' ),
				'description' => __( 'Team events list at /teams/{slug}/events/ (block theme).', 'clanspress' ),
				'path'        => clanspress()->path . 'templates/teams/teams-events.html',
			),
			'teams-events-single'   => array(
				'title'       => __( 'Teams — Event', 'clanspress' ),
				'description' => __( 'Single team event at /teams/{slug}/events/{id}/ (block theme).', 'clanspress' ),
				'path'        => clanspress()->path . 'templates/teams/teams-events-single.html',
			),
			'teams-events-create'   => array(
				'title'       => __( 'Teams — Create event', 'clanspress' ),
				'description' => __( 'Create team event at /teams/{slug}/events/create/ (managers only).', 'clanspress' ),
				'path'        => clanspress()->path . 'templates/teams/teams-events-create.html',
			),
		);

		return $templates;
	}

	/**
	 * Resolves the shared team profile header as a theme-scoped template part for `core/template-part`.
	 *
	 * @param \WP_Block_Template|null $block_template Short-circuit return value.
	 * @param string                  $id             Template id (`theme_slug//slug`).
	 * @param string                  $template_type  `wp_template` or `wp_template_part`.
	 * @return \WP_Block_Template|null
	 */
	public function filter_pre_get_block_file_template_team_profile_header( $block_template, string $id, string $template_type ) {
		if ( null !== $block_template || 'wp_template_part' !== $template_type ) {
			return $block_template;
		}

		$parts = explode( '//', $id, 2 );
		if ( count( $parts ) < 2 ) {
			return $block_template;
		}

		list( $theme, $slug ) = $parts;

		if ( get_stylesheet() !== $theme || self::TEAM_PROFILE_HEADER_TEMPLATE_PART_SLUG !== $slug ) {
			return $block_template;
		}

		return $this->get_team_profile_header_template_part();
	}

	/**
	 * Lists the virtual team profile header with other template parts (Site Editor, inserter).
	 *
	 * @param mixed  $query_result Found templates (expected array of {@see \WP_Block_Template}).
	 * @param mixed  $query        Query arguments (expected array).
	 * @param string $template_type Template type.
	 * @return mixed
	 */
	public function filter_get_block_templates_include_team_profile_header( $query_result, $query, $template_type ) {
		if ( 'wp_template_part' !== $template_type || ! is_array( $query_result ) ) {
			return $query_result;
		}

		$query = is_array( $query ) ? $query : array();

		$part = $this->get_team_profile_header_template_part();
		if ( null === $part ) {
			return $query_result;
		}

		$slug = $part->slug;

		if ( ! empty( $query['slug__in'] ) && ! in_array( $slug, (array) $query['slug__in'], true ) ) {
			return $query_result;
		}

		if ( ! empty( $query['slug__not_in'] ) && in_array( $slug, (array) $query['slug__not_in'], true ) ) {
			return $query_result;
		}

		foreach ( $query_result as $existing ) {
			if ( isset( $existing->slug ) && $slug === $existing->slug ) {
				return $query_result;
			}
		}

		$query_result[] = $part;

		return $query_result;
	}

	/**
	 * Returns a cached {@see \WP_Block_Template} for the team profile header markup file.
	 *
	 * @return \WP_Block_Template|null
	 */
	protected function get_team_profile_header_template_part(): ?\WP_Block_Template {
		if ( null !== self::$team_profile_header_template_part_cache ) {
			return self::$team_profile_header_template_part_cache;
		}

		self::$team_profile_header_template_part_cache = $this->create_team_profile_header_template_part_object();

		return self::$team_profile_header_template_part_cache;
	}

	/**
	 * Builds the virtual template part from `templates/teams/parts/team-profile-header.html`.
	 *
	 * Mirrors {@see _build_block_template_result_from_file()} for `wp_template_part` (hooked blocks).
	 *
	 * @return \WP_Block_Template|null
	 */
	protected function create_team_profile_header_template_part_object(): ?\WP_Block_Template {
		$path = clanspress()->path . 'templates/teams/parts/team-profile-header.html';

		if ( ! is_readable( $path ) ) {
			return null;
		}

		$raw = file_get_contents( $path );
		if ( false === $raw || '' === trim( $raw ) ) {
			return null;
		}

		$theme = get_stylesheet();
		$slug  = self::TEAM_PROFILE_HEADER_TEMPLATE_PART_SLUG;

		$template                 = new \WP_Block_Template();
		$template->id             = $theme . '//' . $slug;
		$template->theme          = $theme;
		$template->slug           = $slug;
		$template->type           = 'wp_template_part';
		$template->title          = __( 'Team profile header', 'clanspress' );
		$template->description    = __( 'Shared cover, stats row, and profile navigation for Clanspress team templates.', 'clanspress' );
		$template->content        = $raw;
		$template->source         = 'plugin';
		$template->origin         = 'plugin';
		$template->plugin         = 'clanspress';
		$template->status         = 'publish';
		$template->has_theme_file = true;
		$template->is_custom      = true;
		$template->area           = \WP_TEMPLATE_PART_AREA_UNCATEGORIZED;

		$content = get_comment_delimited_block_content(
			'core/template-part',
			array(),
			$template->content
		);
		$content           = apply_block_hooks_to_content(
			$content,
			$template,
			'insert_hooked_blocks_and_set_ignored_hooked_blocks_metadata'
		);
		$template->content = remove_serialized_parent_block( $content );

		return $template;
	}
}
