<?php

namespace Kernowdev\Clanspress\Extensions;

use Kernowdev\Clanspress\Extensions\Abstract_Settings;
use Kernowdev\Clanspress\Extensions\Teams\Admin;
use Kernowdev\Clanspress\Extensions\Teams\Team;
use Kernowdev\Clanspress\Extensions\Teams\Team_Data_Store;
use Kernowdev\Clanspress\Extensions\Teams\Team_Data_Store_CPT;

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../data-stores/class-wp-post-meta-data-store.php';
require_once __DIR__ . '/class-team-data-store-cpt.php';

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
			'0.0.1',
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

		add_action( 'init', array( $this, 'register_team_front_routes' ), 5 );
		add_action( 'init', array( $this, 'register_team_post_type' ), 10 );
		add_action( 'init', array( $this, 'register_team_meta' ), 10 );
		add_action( 'init', array( $this, 'register_team_blocks' ), 10 );
		add_action( 'init', array( $this, 'register_team_templates' ), 10 );
		add_action( 'admin_post_clanspress_create_team', array( $this, 'handle_create_team' ) );
		add_action( 'admin_post_nopriv_clanspress_create_team', array( $this, 'handle_create_team' ) );
		add_action( 'admin_post_clanspress_save_team_manage', array( $this, 'handle_save_team_manage' ) );
		add_action( 'admin_post_nopriv_clanspress_save_team_manage', array( $this, 'handle_save_team_manage_nopriv' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_team_editor_assets' ) );
		add_action( 'wp_ajax_clanspress_team_invite_search', array( $this, 'ajax_team_invite_search' ) );
		add_action( 'init', array( $this, 'integrate_team_preferences' ), 5 );
		add_filter( 'query_vars', array( $this, 'register_team_query_vars' ) );
		add_filter( 'request', array( $this, 'filter_request_for_team_virtual_pages' ), 99 );
		add_action( 'parse_query', array( $this, 'parse_query_for_team_virtual_pages' ) );
		add_filter( 'posts_pre_query', array( $this, 'posts_pre_query_team_virtual_pages' ), 10, 2 );
		add_filter( 'template_include', array( $this, 'maybe_load_team_virtual_templates' ), 100 );
		add_action( 'template_redirect', array( $this, 'maybe_block_banned_team_access' ), 5 );
		add_filter( 'the_content', array( $this, 'maybe_append_team_manage_link' ), 15 );
		add_filter( 'map_meta_cap', array( $this, 'map_team_front_edit_meta_cap' ), 10, 4 );
		add_filter( 'wp_unique_post_slug', array( $this, 'reserve_team_route_slugs' ), 10, 6 );
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
			<p class="description">
				<?php esc_html_e( 'If you turn this off, you will not appear in team invite search when captains add players to a new team.', 'clanspress' ); ?>
			</p>
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
	 * Front routes (BuddyPress-style: component / item / action).
	 *
	 * - /teams/create/ — global create (no team context).
	 * - /teams/{slug}/manage/ — team-scoped action (more actions can be registered later).
	 *
	 * @return void
	 */
	public function register_team_front_routes(): void {
		add_rewrite_rule( '^teams/create/?$', 'index.php?clanspress_team_create=1', 'top' );

		$actions = array_keys( $this->get_team_front_action_rewrite_slugs() );
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
	 * Force team virtual query vars from the URL when rewrite rules did not match (flush/order issues).
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
			return array(
				'clanspress_team_create' => '1',
			);
		}

		if ( preg_match( '#^teams/manage/([^/]+)/?$#', $path, $m ) ) {
			return array(
				'clanspress_team_slug'   => $m[1],
				'clanspress_team_action' => 'manage',
			);
		}

		$actions = array_keys( $this->get_team_front_action_rewrite_slugs() );
		foreach ( $actions as $action_slug ) {
			$action_slug = sanitize_key( (string) $action_slug );
			if ( '' === $action_slug || 'create' === $action_slug ) {
				continue;
			}
			$quoted = preg_quote( $action_slug, '#' );
			if ( preg_match( '#^teams/([^/]+)/' . $quoted . '/?$#', $path, $m ) ) {
				return array(
					'clanspress_team_slug'   => $m[1],
					'clanspress_team_action' => $action_slug,
				);
			}
		}

		return $query_vars;
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
	 * Load virtual team templates (create / manage).
	 *
	 * @param string $template Default template path.
	 * @return string
	 */
	public function maybe_load_team_virtual_templates( string $template ): string {
		if ( (int) get_query_var( 'clanspress_team_create' ) ) {
			if ( ! $this->is_team_directories_mode() ) {
				return $template;
			}

			if ( ! is_user_logged_in() ) {
				wp_safe_redirect( wp_login_url( $this->get_team_create_url() ) );
				exit;
			}

			$templates = array( 'teams-create.php', 'index.php' );
			$located   = locate_template( $templates );

			return apply_filters(
				'clanspress_load_team_create_template',
				locate_block_template( $located, 'teams-create', $templates )
			);
		}

		$team_action = sanitize_key( (string) get_query_var( 'clanspress_team_action' ) );
		if ( '' !== $team_action ) {
			if ( ! is_user_logged_in() ) {
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

			if ( 'manage' === $team_action ) {
				if ( ! $this->user_can_manage_team_on_frontend( $team_id ) ) {
					wp_safe_redirect( home_url( '/' ) );
					exit;
				}

				set_query_var( 'clanspress_manage_team_id', $team_id );

				// Hierarchy slug must match register_block_template id segment (teams-manage), not team-manage.
				$templates = array( 'teams-manage.php', 'index.php' );
				$located   = locate_template( $templates );

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
	 * Append manage link for admins/editors on single team views.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function maybe_append_team_manage_link( string $content ): string {
		if ( ! is_singular( 'cp_team' ) || ! is_main_query() ) {
			return $content;
		}

		$team_id = (int) get_queried_object_id();
		if ( $team_id < 1 || ! $this->user_can_manage_team_on_frontend( $team_id ) ) {
			return $content;
		}

		$url  = esc_url( $this->get_team_manage_url( $team_id ) );
		$link = sprintf(
			'<p class="clanspress-team-manage-link"><a href="%1$s">%2$s</a></p>',
			$url,
			esc_html__( 'Manage team', 'clanspress' )
		);

		return $link . $content;
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
	 * Initial roster after team creation.
	 *
	 * @param int   $team_id         New team ID.
	 * @param int   $creator_id      Creator user ID.
	 * @param array $invite_user_ids Invited user IDs (added as members).
	 * @return void
	 */
	protected function initialize_team_roster( int $team_id, int $creator_id, array $invite_user_ids ): void {
		$map = array( $creator_id => self::TEAM_ROLE_ADMIN );
		foreach ( $invite_user_ids as $inv ) {
			$inv = (int) $inv;
			if ( $inv > 0 && $inv !== $creator_id ) {
				$map[ $inv ] = self::TEAM_ROLE_MEMBER;
			}
		}
		$this->persist_team_roles_map( $team_id, $map );
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

		$is_admin = $this->user_is_team_admin_on_frontend( $team_id );
		$can_edit = $this->user_can_manage_team_on_frontend( $team_id );
		if ( ! $can_edit ) {
			return;
		}

		$map   = $this->get_team_member_roles_map( $team_id );
		$roles = array(
			self::TEAM_ROLE_ADMIN  => __( 'Admin', 'clanspress' ),
			self::TEAM_ROLE_EDITOR => __( 'Editor', 'clanspress' ),
			self::TEAM_ROLE_MEMBER => __( 'Member', 'clanspress' ),
			self::TEAM_ROLE_BANNED => __( 'Banned', 'clanspress' ),
		);

		$manage_status = isset( $_GET['clanspress_team_manage_status'] )
			? sanitize_key( wp_unslash( $_GET['clanspress_team_manage_status'] ) )
			: '';

		$action_url = admin_url( 'admin-post.php' );
		?>
		<?php if ( 'saved' === $manage_status ) : ?>
			<p class="clanspress-team-manage-form__notice is-success"><?php esc_html_e( 'Changes saved.', 'clanspress' ); ?></p>
		<?php elseif ( 'roster_invalid' === $manage_status ) : ?>
			<p class="clanspress-team-manage-form__notice is-error"><?php esc_html_e( 'Roster must include at least one admin.', 'clanspress' ); ?></p>
		<?php endif; ?>

		<form class="clanspress-team-manage-form" method="post" action="<?php echo esc_url( $action_url ); ?>">
			<?php wp_nonce_field( 'clanspress_team_manage_' . $team_id, '_clanspress_team_manage_nonce' ); ?>
			<input type="hidden" name="action" value="clanspress_save_team_manage" />
			<input type="hidden" name="clanspress_team_id" value="<?php echo esc_attr( (string) $team_id ); ?>" />

			<div class="clanspress-team-manage-form__fields">
				<h3><?php esc_html_e( 'Team profile', 'clanspress' ); ?></h3>
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
			</div>

			<?php if ( $is_admin ) : ?>
			<div class="clanspress-team-manage-form__roster">
				<h3><?php esc_html_e( 'Members & roles', 'clanspress' ); ?></h3>
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
			<?php endif; ?>

			<p>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save changes', 'clanspress' ); ?></button>
				<a class="button" href="<?php echo esc_url( get_permalink( $team_id ) ); ?>"><?php esc_html_e( 'View team', 'clanspress' ); ?></a>
			</p>
		</form>
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
		$this->get_team_data_store()->update( $team_entity );

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
		register_post_meta(
			'cp_team',
			'cp_team_join_mode',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => 'open_join',
				'sanitize_callback' => array( $this, 'sanitize_team_join_mode' ),
				'show_in_rest'      => true,
			)
		);

		register_post_meta(
			'cp_team',
			'cp_team_allow_invites',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'show_in_rest'      => true,
			)
		);

		register_post_meta(
			'cp_team',
			'cp_team_allow_frontend_edit',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'show_in_rest'      => true,
			)
		);

		register_post_meta(
			'cp_team',
			'cp_team_allow_ban_players',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'show_in_rest'      => true,
			)
		);

		register_post_meta(
			'cp_team',
			'cp_team_code',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			)
		);

		register_post_meta(
			'cp_team',
			'cp_team_motto',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			)
		);

		register_post_meta(
			'cp_team',
			'cp_team_avatar_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
			)
		);

		register_post_meta(
			'cp_team',
			'cp_team_cover_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
			)
		);

		register_post_meta(
			'cp_team',
			'cp_team_member_roles',
			array(
				'type'         => 'array',
				'single'       => true,
				'default'      => array(),
				'show_in_rest' => false,
			)
		);
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
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n', 'wp-hooks' ),
			clanspress()->get_version(),
			true
		);
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
		$options = array(
			'join_mode'           => $this->sanitize_team_join_mode( get_post_meta( $team_id, 'cp_team_join_mode', true ) ),
			'allow_invites'       => rest_sanitize_boolean( get_post_meta( $team_id, 'cp_team_allow_invites', true ) ),
			'allow_frontend_edit' => rest_sanitize_boolean( get_post_meta( $team_id, 'cp_team_allow_frontend_edit', true ) ),
			'allow_ban_players'   => rest_sanitize_boolean( get_post_meta( $team_id, 'cp_team_allow_ban_players', true ) ),
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
		);

		$team_entity = $this->get_team( $team_id );
		if ( $team_entity ) {
			$team_entity->set_join_mode( $sanitized['join_mode'] );
			$team_entity->set_allow_invites( $sanitized['allow_invites'] );
			$team_entity->set_allow_frontend_edit( $sanitized['allow_frontend_edit'] );
			$team_entity->set_allow_ban_players( $sanitized['allow_ban_players'] );
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
	 * Handle optional media upload for team create flow.
	 *
	 * @param int    $team_id Team post ID.
	 * @param string $field_name Form file input name.
	 * @param string $meta_key Meta key to store attachment ID.
	 * @return void
	 */
	protected function maybe_handle_team_media_upload( int $team_id, string $field_name, string $meta_key ): void {
		if ( empty( $_FILES[ $field_name ] ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_handle_upload( $field_name, $team_id );
		if ( is_wp_error( $attachment_id ) ) {
			return;
		}

		update_post_meta( $team_id, $meta_key, (int) $attachment_id );
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
		$redirect = wp_get_referer() ?: $this->get_team_create_url();

		if ( $success && $team_id > 0 ) {
			$team_permalink = get_permalink( $team_id );
			if ( $team_permalink ) {
				$redirect = $team_permalink;
			}
		}

		$args = array(
			'clanspress_team_status' => $success ? 'success' : 'error',
			'clanspress_team_code'   => $code,
		);

		if ( $team_id > 0 ) {
			$args['clanspress_team_id'] = $team_id;
		}

		wp_safe_redirect( add_query_arg( $args, $redirect ) );
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
		$this->register_extension_block_types_from_metadata_collection( 'build/teams/team-card' );

		if ( $this->is_team_directories_mode() ) {
			$this->register_extension_block_types_from_metadata_collection( 'build/teams/team-create-form' );
		}

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
	 * Get all FSE templates owned by the Teams extension.
	 *
	 * @return array<string, array<string, string>>
	 */
	protected function get_team_templates(): array {
		$templates = array();

		if ( $this->is_team_directories_mode() ) {
			$templates['teams-create'] = array(
				'title' => __( 'Teams — Create', 'clanspress' ),
				'path'  => clanspress()->path . '/templates/teams/teams-create.php',
			);
			$templates['teams-manage'] = array(
				'title' => __( 'Teams — Manage', 'clanspress' ),
				'path'  => clanspress()->path . '/templates/teams/teams-manage.php',
			);
		}

		return $templates;
	}
}
