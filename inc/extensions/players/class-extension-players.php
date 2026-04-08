<?php

namespace Kernowdev\Clanspress\Extensions;
defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanspress\Extensions\Abstract_Settings;
use Kernowdev\Clanspress\Extensions\Players\Admin;

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/functions-player-query.php';

/**
 * Extension: Players.
 *
 * This extension adds player functionality, including player profiles and
 * settings.
 */
class Players extends Skeleton {
	/**
	 * Slug for the virtual `wp_template_part` resolved from `templates/players/parts/player-profile-header.html`.
	 */
	public const PLAYER_PROFILE_HEADER_TEMPLATE_PART_SLUG = 'clanspress-player-profile-header';

	/**
	 * Cached {@see \WP_Block_Template} for {@see Players::PLAYER_PROFILE_HEADER_TEMPLATE_PART_SLUG} (per request).
	 *
	 * @var \WP_Block_Template|null
	 */
	protected static ?\WP_Block_Template $player_profile_header_template_part_cache = null;

	protected Admin $admin;

	/**
	 * Sets up our extension loader.
	 */
	public function __construct() {
		parent::__construct(
			'Players',
			'cp_players',
			__(
				'Extends user functionality to add support for players.',
				'clanspress'
			),
			'',
			'1.0.0',
			array()
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
		add_filter(
			'clanspress_official_registered_extensions',
			array( $this, 'register_extension' )
		);
	}

	public function run_installer(): void {
	}

	public function run_uninstaller(): void {
	}

	public function run_updater(): void {
	}

	public function run(): void {
		// Initiate admin functionality and settings.
		$this->admin = new Admin();

		// Maybe initiate player profile functionality.
		if ( $this->admin->get( 'enable_profiles' ) ) {
			$this->enable_profiles();
		}
	}

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
	 * Enable player profile functionality.
	 *
	 * The function adds various actions and filters to enable the following
	 * functionality: profile endpoints (players/username), player settings
	 * (player/settings), custom profile gutenberg templates and blocks, custom
	 *  player meta keys.
	 *
	 * @return void
	 */
	public function enable_profiles(): void {
		add_action( 'init', array( $this, 'register_profile_endpoints' ) );
		add_filter( 'author_link', array( $this, 'modify_author_links' ), 10, 3 );
		add_filter( 'query_vars', array( $this, 'register_profile_query_vars' ) );
		add_filter( 'request', array( $this, 'filter_request_for_players_virtual_routes' ), 9999 );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts_force_player_author_archive' ), 1 );
		add_action( 'parse_query', array( $this, 'parse_query_for_players_directory' ) );
		add_filter( 'posts_pre_query', array( $this, 'posts_pre_query_players_directory' ), 10, 2 );
		add_filter( 'template_include', array( $this, 'maybe_load_players_directory_template' ), 15 );
		add_filter( 'template_include', array( $this, 'maybe_load_player_subpage_template' ), 90 );
		add_action( 'template_redirect', array( $this, 'maybe_normalize_player_profile_stolen_query' ), -1 );
		add_action( 'template_redirect', array( $this, 'maybe_fix_players_directory_404' ), 0 );
		add_action( 'template_redirect', array( $this, 'maybe_fix_players_profile_404' ), 1 );
		add_action( 'template_redirect', array( $this, 'maybe_redirect_author_archives_to_players' ), 3 );
		add_action( 'template_redirect', array( $this, 'maybe_canonicalize_player_profile_subpage' ), 4 );
		add_filter( 'template_include', array( $this, 'maybe_load_player_profile_template' ), 100 );
		add_filter( 'get_block_templates', array( $this, 'prefer_plugin_players_directory_block_template' ), 100, 3 );
		add_filter( 'get_block_templates', array( $this, 'prefer_plugin_players_player_profile_block_template' ), 100, 3 );
		add_filter( 'get_block_templates', array( $this, 'prefer_plugin_player_subpage_block_template' ), 100, 3 );
		add_filter( 'pre_get_block_file_template', array( $this, 'filter_pre_get_block_file_template_player_profile_header' ), 10, 3 );
		add_filter( 'get_block_templates', array( $this, 'filter_get_block_templates_include_player_profile_header' ), 10, 3 );
		add_action( 'template_redirect', array( $this, 'maybe_canonicalize_player_settings_url' ), 5 );
		add_filter( 'template_include', array( $this, 'maybe_load_player_settings_template' ) );
		add_action( 'wp', array( $this, 'set_plugin_block_template_id_for_site_editor' ), 99 );
		add_action( 'init', array( $this, 'register_players_directory_shortcode' ) );
		add_action( 'init', array( $this, 'register_profile_templates' ) );
		add_action( 'after_setup_theme', array( $this, 'register_image_sizes' ) );

		// Blocks and assets.
		add_action( 'init', array( $this, 'register_profile_blocks' ) );
		add_filter( 'render_block_context', array( $this, 'filter_render_block_context_social_on_player_profile' ), 10, 3 );

		// Profile meta.
		add_action( 'init', array( $this, 'register_user_meta_keys' ) );

		// Profile settings.
		add_filter( 'clanspress_players_settings_nav_items', array( $this, 'register_player_settings_nav_items' ) );
		add_filter( 'clanspress_players_settings_nav_profile_sub_items', array( $this, 'register_profile_nav_items' ) );
		add_filter( 'clanspress_players_settings_nav_account_sub_items', array( $this, 'register_account_nav_items' ) );
		add_action( 'clanspress_player_settings_panel_profile-info', array( $this, 'do_profile_avatar_fields' ) );
		add_action( 'clanspress_player_settings_panel_profile-info', array( $this, 'do_profile_info_fields' ), 20 );
		add_action( 'clanspress_player_settings_panel_social-networks', array( $this, 'do_social_networks_fields' ) );
		add_action( 'clanspress_player_settings_panel_account-info', array( $this, 'do_account_info_fields' ) );

		// Save profile settings.
		add_action( 'clanspress_save_player_settings', array( $this, 'save_player_profile_settings' ), 10, 4 );
		add_action( 'clanspress_save_player_settings', array( $this, 'save_player_account_info_settings' ), 10, 4 );

		// Ajax handlers.
		add_action( 'wp_ajax_clanspress_save_player_settings', array( $this, 'ajax_save_player_settings' ) );
	}

	public function modify_author_links( $link, $author_id, $author_nicename ) {
		return home_url( '/players/' . $author_nicename );
	}

	/**
	 * Send `/author/{nicename}/` and plain `?author=` archives to `/players/{nicename}/`.
	 *
	 * Skips when the request is already under `/players/` (canonical player URLs still use author query internals).
	 *
	 * @return void
	 */
	public function maybe_redirect_author_archives_to_players(): void {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( ! is_author() ) {
			return;
		}

		$path = $this->get_canonical_request_path();
		if ( preg_match( '#^players(?:/|$)#', $path ) ) {
			return;
		}

		$user = get_queried_object();
		if ( ! $user instanceof \WP_User ) {
			return;
		}

		$nicename = $user->user_nicename;
		if ( '' === $nicename ) {
			return;
		}

		$paged = max( 0, (int) get_query_var( 'paged' ) );
		if ( $paged > 1 ) {
			$target = trailingslashit( home_url( '/players/' . $nicename . '/page/' . $paged ) );
		} else {
			$target = trailingslashit( home_url( '/players/' . $nicename ) );
		}

		/**
		 * Filter the 301 target when redirecting core author archives to player profile URLs.
		 *
		 * @param string   $target Full URL.
		 * @param \WP_User $user   Queried author.
		 */
		$target = (string) apply_filters( 'clanspress_redirect_author_archive_to_players_url', $target, $user );

		wp_safe_redirect( $target, 301 );
		exit;
	}

	/**
	 * @return void
	 */
	public function register_profile_endpoints() {
		// 1a. Players settings deep links: /players/settings/{nav}/{panel}/
		add_rewrite_rule(
			'^players/settings/([^/]+)/([^/]+)/?$',
			'index.php?players_settings=1&players_settings_nav=$matches[1]&players_settings_panel=$matches[2]',
			'top'
		);

		// 1b. /players/settings/{nav}/ → canonicalize to first panel in template_redirect
		add_rewrite_rule(
			'^players/settings/([^/]+)/?$',
			'index.php?players_settings=1&players_settings_nav=$matches[1]',
			'top'
		);

		// 1c. Players settings index
		add_rewrite_rule(
			'^players/settings/?$',
			'index.php?players_settings=1',
			'top'
		);

		// 2. Players directory pagination (must run before author rules so "page" is not treated as a username).
		add_rewrite_rule(
			'^players/page/([0-9]+)/?$',
			'index.php?cp_players_directory=1&paged=$matches[1]',
			'top'
		);

		// 3. Author pagination
		add_rewrite_rule(
			'^players/(?!settings/?$)([^/]+)/page/([0-9]+)/?$',
			'index.php?author_name=$matches[1]&paged=$matches[2]',
			'top'
		);

		// 4. Player profile subpages: /players/{nicename}/{subpage}/
		add_rewrite_rule(
			'^players/(?!settings/?$)([^/]+)/([^/]+)/?$',
			'index.php?author_name=$matches[1]&cp_player_subpage=$matches[2]',
			'top'
		);

		// 5. Author first page (default profile overview).
		add_rewrite_rule(
			'^players/(?!settings/?$)([^/]+)/?$',
			'index.php?author_name=$matches[1]',
			'top'
		);

		// 6. Players directory root (/players/).
		add_rewrite_rule(
			'^players/?$',
			'index.php?cp_players_directory=1',
			'top'
		);
	}

	public function register_profile_query_vars( $vars ) {
		$vars[] = 'players_settings';
		$vars[] = 'players_settings_nav';
		$vars[] = 'players_settings_panel';
		$vars[] = 'cp_player_subpage';
		$vars[] = 'cp_players_directory';
		return $vars;
	}

	/**
	 * Supplies `clanspress/playerId` to Social Kit blocks on player-profile routes.
	 *
	 * The `players-player-profile` template renders `post-content` without a `player-query` ancestor, so
	 * blocks that declare `usesContext: [ "clanspress/playerId" ]` would otherwise get `0` and render empty.
	 *
	 * - **Feed / composer:** still requires `feedContext` `profile` (same as before).
	 * - **Player stats / add friend:** always receive the profile owner when this resolves (no `feedContext` gate).
	 *
	 * Caches a positive profile owner ID only after the first successful resolution so an early `0` from
	 * this filter never blocks later blocks on the same request.
	 *
	 * @param array<string, mixed>      $context      Default block context.
	 * @param array<string, mixed>      $parsed_block Parsed block (core shape).
	 * @param \WP_Block|null|array|null $parent_block Parent block instance when available.
	 * @return array<string, mixed>
	 */
	public function filter_render_block_context_social_on_player_profile( array $context, array $parsed_block, $parent_block ): array {
		unset( $parent_block );

		static $cached_positive_profile_owner_id = null;

		$name = (string) ( $parsed_block['blockName'] ?? '' );

		$feed_blocks = array(
			'clanspress-social/social-feed',
			'clanspress-social/social-composer',
		);
		$profile_context_blocks = array(
			'clanspress-social/player-friends-count',
			'clanspress-social/player-post-count',
			'clanspress-social/player-add-friend',
		);

		if ( ! in_array( $name, array_merge( $feed_blocks, $profile_context_blocks ), true ) ) {
			return $context;
		}

		$attrs = $parsed_block['attrs'] ?? array();
		if ( ! is_array( $attrs ) ) {
			$attrs = array();
		}

		if ( in_array( $name, $feed_blocks, true ) ) {
			$feed = $attrs['feedContext'] ?? 'home';
			$feed = is_string( $feed ) ? $feed : 'home';
			if ( 'profile' !== $feed ) {
				return $context;
			}
		}

		if ( ! empty( $context['clanspress/playerId'] ) && (int) $context['clanspress/playerId'] > 0 ) {
			return $context;
		}

		$profile_owner_id = 0;
		if ( null !== $cached_positive_profile_owner_id && $cached_positive_profile_owner_id > 0 ) {
			$profile_owner_id = (int) $cached_positive_profile_owner_id;
		} else {
			$profile_owner_id = function_exists( 'clanspress_player_profile_context_user_id' )
				? (int) clanspress_player_profile_context_user_id()
				: 0;
			if ( $profile_owner_id > 0 ) {
				$cached_positive_profile_owner_id = $profile_owner_id;
			}
		}

		if ( $profile_owner_id <= 0 ) {
			return $context;
		}

		$context['clanspress/playerId'] = $profile_owner_id;

		return $context;
	}

	/**
	 * Request path after the home URL path (no leading/trailing slashes).
	 *
	 * @return string
	 */
	protected function get_canonical_request_path(): string {
		return clanspress_get_canonical_request_path();
	}

	/**
	 * Remove query vars that would resolve to a post/page and conflict with virtual player routes.
	 *
	 * @param array<string, mixed> $query_vars Query variables.
	 * @return array<string, mixed>
	 */
	protected function strip_conflicting_query_vars_for_players_virtual_routes( array $query_vars ): array {
		foreach ( array( 'pagename', 'name', 'page_id', 'p', 'attachment', 'attachment_id', 'year', 'monthnum', 'day', 'feed', 'post_type', 'error', 'cp_team' ) as $key ) {
			unset( $query_vars[ $key ] );
		}

		return $query_vars;
	}

	/**
	 * Recover /players/ directory query vars when rewrite rules are stale.
	 *
	 * @param array<string, mixed> $query_vars Query variables.
	 * @return array<string, mixed>
	 */
	public function filter_request_for_players_virtual_routes( array $query_vars ): array {
		if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $query_vars;
		}

		if ( ! empty( $query_vars['cp_players_directory'] ) ) {
			return $query_vars;
		}

		$path = $this->get_canonical_request_path();
		if ( '' === $path ) {
			return $query_vars;
		}

		if ( 'players' === $path ) {
			$query_vars['cp_players_directory'] = '1';

			return $this->strip_conflicting_query_vars_for_players_virtual_routes( $query_vars );
		}

		if ( preg_match( '#^players/page/([0-9]+)/?$#', $path, $m ) ) {
			$query_vars['cp_players_directory'] = '1';
			$query_vars['paged']                = max( 1, (int) $m[1] );

			return $this->strip_conflicting_query_vars_for_players_virtual_routes( $query_vars );
		}

		if ( str_starts_with( $path, 'players/settings' ) || 'players/settings' === $path ) {
			return $query_vars;
		}

		// Profile routes when rewrites are stale or a page/CPT stole the query (force author archive).
		if ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/page/([0-9]+)/?$#', $path, $m ) ) {
			$query_vars['author_name'] = $m[1];
			$query_vars['paged']       = max( 1, (int) $m[2] );
			unset( $query_vars['cp_player_subpage'] );

			return $this->strip_conflicting_query_vars_for_players_virtual_routes( $query_vars );
		}

		if ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/([^/]+)/?$#', $path, $m ) ) {
			$query_vars['author_name']       = $m[1];
			$query_vars['cp_player_subpage'] = $m[2];

			return $this->strip_conflicting_query_vars_for_players_virtual_routes( $query_vars );
		}

		if ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/?$#', $path, $m ) ) {
			$query_vars['author_name'] = $m[1];
			unset( $query_vars['cp_player_subpage'] );

			return $this->strip_conflicting_query_vars_for_players_virtual_routes( $query_vars );
		}

		return $query_vars;
	}

	/**
	 * Clear query vars that would resolve to a singular post/page instead of a player author archive.
	 *
	 * @param \WP_Query $query Main query.
	 * @return void
	 */
	protected function scrub_main_query_conflicting_vars_for_player_routes( \WP_Query $query ): void {
		$query->set( 'pagename', '' );
		$query->set( 'name', '' );
		$query->set( 'page_id', 0 );
		$query->set( 'p', 0 );
		$query->set( 'attachment', '' );
		$query->set( 'attachment_id', 0 );
		$query->set( 'year', '' );
		$query->set( 'monthnum', 0 );
		$query->set( 'day', 0 );
		$query->set( 'feed', '' );
		$query->set( 'post_type', '' );
		$query->set( 'error', '' );
		$query->set( 'cp_team', '' );
	}

	/**
	 * Force the main query to treat `/players/{nicename}/` as an author archive when a page/CPT stole early parsing.
	 *
	 * Runs on `pre_get_posts` so WordPress re-parses query flags after vars are normalized.
	 *
	 * @param \WP_Query $query Query instance.
	 * @return void
	 */
	public function pre_get_posts_force_player_author_archive( \WP_Query $query ): void {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( (int) $query->get( 'cp_players_directory' ) ) {
			return;
		}

		if ( (int) $query->get( 'players_settings' ) ) {
			return;
		}

		$path = $this->get_canonical_request_path();
		if ( '' === $path ) {
			return;
		}

		if ( 'players' === $path || preg_match( '#^players/page/[0-9]+/?$#', $path ) ) {
			return;
		}

		if ( str_starts_with( $path, 'players/settings' ) || 'players/settings' === $path ) {
			return;
		}

		if ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/page/([0-9]+)/?$#', $path, $m ) ) {
			$query->set( 'author_name', $m[1] );
			$query->set( 'paged', max( 1, (int) $m[2] ) );
			$query->set( 'cp_player_subpage', '' );
			$this->scrub_main_query_conflicting_vars_for_player_routes( $query );
			return;
		}

		if ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/([^/]+)/?$#', $path, $m ) ) {
			$query->set( 'author_name', $m[1] );
			$query->set( 'cp_player_subpage', $m[2] );
			$query->set( 'paged', 0 );
			$this->scrub_main_query_conflicting_vars_for_player_routes( $query );
			return;
		}

		if ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/?$#', $path, $m ) ) {
			$query->set( 'author_name', $m[1] );
			$query->set( 'cp_player_subpage', '' );
			$query->set( 'paged', 0 );
			$this->scrub_main_query_conflicting_vars_for_player_routes( $query );
		}
	}

	/**
	 * Whether a registered player subpage has a dedicated front template (`player-{slug}.php`).
	 *
	 * @param string $slug Sanitized subpage slug.
	 * @return bool
	 */
	protected function player_subpage_has_dedicated_template( string $slug ): bool {
		if ( '' === $slug ) {
			return false;
		}

		if ( function_exists( '\clanspress_get_player_subpage' ) ) {
			$config = \clanspress_get_player_subpage( $slug );
			if ( null === $config ) {
				return false;
			}
		}

		$candidates = array(
			"players/player-{$slug}.php",
			"player-{$slug}.php",
			"players/subpage-{$slug}.php",
			"subpage-{$slug}.php",
		);

		if ( locate_template( $candidates ) ) {
			return true;
		}

		$plugin = clanspress()->path . "templates/players/player-{$slug}.php";

		return is_readable( $plugin );
	}

	/**
	 * Whether the front request should use the default player profile block template (overview).
	 *
	 * Subpages with a dedicated template (e.g. notifications) use {@see maybe_load_player_subpage_template()} instead.
	 *
	 * @return bool
	 */
	protected function should_use_player_profile_template(): bool {
		if ( ! is_author() ) {
			return false;
		}

		$sub = sanitize_key( (string) get_query_var( 'cp_player_subpage' ) );
		if ( '' !== $sub && $this->player_subpage_has_dedicated_template( $sub ) ) {
			return false;
		}

		$path = $this->get_canonical_request_path();
		return '' === $path || (bool) preg_match( '#^players/(?!settings(?:/|$))#', $path );
	}

	/**
	 * Normalize main query flags for the virtual players directory.
	 *
	 * @param \WP_Query $query Main query.
	 * @return void
	 */
	public function parse_query_for_players_directory( \WP_Query $query ): void {
		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( ! (int) $query->get( 'cp_players_directory' ) ) {
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
	 * Skip SQL for the players directory virtual route.
	 *
	 * @param mixed     $posts Posts or null.
	 * @param \WP_Query $query Query object.
	 * @return mixed
	 */
	public function posts_pre_query_players_directory( $posts, \WP_Query $query ) {
		if ( ! $query->is_main_query() ) {
			return $posts;
		}

		if ( (int) $query->get( 'cp_players_directory' ) ) {
			$query->found_posts   = 0;
			$query->max_num_pages = 0;
			return array();
		}

		return $posts;
	}

	/**
	 * If /players/ was still a 404, recover before templates load.
	 *
	 * @return void
	 */
	public function maybe_fix_players_directory_404(): void {
		if ( ! is_404() ) {
			return;
		}

		$path = $this->get_canonical_request_path();
		if ( 'players' !== $path && ! preg_match( '#^players/page/[0-9]+/?$#', $path ) ) {
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

		set_query_var( 'cp_players_directory', '1' );
		if ( preg_match( '#^players/page/([0-9]+)/?$#', $path, $m ) ) {
			set_query_var( 'paged', max( 1, (int) $m[1] ) );
		}
	}

	/**
	 * Force the main query to an author archive for a resolved player (shared 404 + stolen-query recovery).
	 *
	 * @param \WP_User $user    Player user.
	 * @param int      $paged   Author archive page (1 = first page).
	 * @param string   $subpage Optional virtual subpage slug (empty string if none).
	 * @param bool     $recover_404 When true, send 200 and no-cache headers (404 recovery).
	 * @return void
	 */
	protected function hydrate_main_query_as_player_profile( \WP_User $user, int $paged, string $subpage, bool $recover_404 = false ): void {
		if ( $recover_404 ) {
			status_header( 200 );
			nocache_headers();
		}

		global $wp_query;

		$wp_query->is_404                = false;
		$wp_query->is_home               = false;
		$wp_query->is_front_page         = false;
		$wp_query->is_posts_page         = false;
		$wp_query->is_page               = false;
		$wp_query->is_singular           = false;
		$wp_query->is_single             = false;
		$wp_query->is_category           = false;
		$wp_query->is_tag                = false;
		$wp_query->is_date               = false;
		$wp_query->is_post_type_archive  = false;
		$wp_query->is_attachment         = false;
		$wp_query->is_author             = true;
		$wp_query->is_archive            = true;
		$wp_query->queried_object        = $user;
		$wp_query->queried_object_id     = (int) $user->ID;
		$wp_query->posts                 = array();
		$wp_query->post_count            = 0;
		$wp_query->found_posts           = 0;
		$wp_query->max_num_pages         = 0;

		set_query_var( 'author_name', $user->user_nicename );
		if ( $paged > 1 ) {
			set_query_var( 'paged', $paged );
		} else {
			set_query_var( 'paged', 0 );
		}
		if ( '' !== $subpage ) {
			set_query_var( 'cp_player_subpage', $subpage );
		} else {
			set_query_var( 'cp_player_subpage', '' );
		}
	}

	/**
	 * When a hierarchical Page (or other singular) steals `/players/{nicename}/`, fix flags before the template loader.
	 *
	 * Core evaluates `is_page` / `is_singular` before `is_author` in {@see wp-includes/template-loader.php}, so a wrong
	 * main query never reaches author templates. Teams avoid this by using the `cp_team` post type.
	 *
	 * @return void
	 */
	public function maybe_normalize_player_profile_stolen_query(): void {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( is_404() ) {
			return;
		}

		if ( (int) get_query_var( 'cp_players_directory' ) || get_query_var( 'players_settings' ) ) {
			return;
		}

		$path = $this->get_canonical_request_path();
		if ( '' === $path || 'players' === $path || preg_match( '#^players/page/[0-9]+/?$#', $path ) ) {
			return;
		}

		if ( str_starts_with( $path, 'players/settings' ) || 'players/settings' === $path ) {
			return;
		}

		$nicename = '';
		$paged    = 0;
		$subpage  = '';

		if ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/page/([0-9]+)/?$#', $path, $m ) ) {
			$nicename = $m[1];
			$paged    = max( 1, (int) $m[2] );
		} elseif ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/([^/]+)/?$#', $path, $m ) ) {
			$nicename = $m[1];
			$subpage  = $m[2];
		} elseif ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/?$#', $path, $m ) ) {
			$nicename = $m[1];
		} else {
			return;
		}

		$user = get_user_by( 'slug', $nicename );
		if ( ! $user instanceof \WP_User ) {
			return;
		}

		if ( is_author() && (int) get_queried_object_id() === (int) $user->ID ) {
			return;
		}

		$this->hydrate_main_query_as_player_profile( $user, $paged, $subpage, false );
	}

	/**
	 * If /players/{nicename}/ is still 404, recover before templates load (rewrite / query conflict edge cases).
	 *
	 * @return void
	 */
	public function maybe_fix_players_profile_404(): void {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( ! is_404() ) {
			return;
		}

		$path = $this->get_canonical_request_path();
		if ( '' === $path || 'players' === $path || preg_match( '#^players/page/[0-9]+/?$#', $path ) ) {
			return;
		}

		$nicename = '';
		$paged    = 0;
		$subpage  = '';

		if ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/page/([0-9]+)/?$#', $path, $m ) ) {
			$nicename = $m[1];
			$paged    = max( 1, (int) $m[2] );
		} elseif ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/([^/]+)/?$#', $path, $m ) ) {
			$nicename = $m[1];
			$subpage  = $m[2];
		} elseif ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/?$#', $path, $m ) ) {
			$nicename = $m[1];
		} else {
			return;
		}

		$user = get_user_by( 'slug', $nicename );
		if ( ! $user instanceof \WP_User ) {
			return;
		}

		$this->hydrate_main_query_as_player_profile( $user, $paged, $subpage, true );
	}

	/**
	 * Load the plugin players directory template (FSE or classic).
	 *
	 * @param string $template Default template path.
	 * @return string
	 */
	public function maybe_load_players_directory_template( string $template ): string {
		if ( ! (int) get_query_var( 'cp_players_directory' ) ) {
			return $template;
		}

		$templates = array( 'players/players-directory.php', 'players-directory.php' );
		$located   = locate_template( $templates );
		if ( ! $located ) {
			$located = clanspress()->path . 'templates/players/players-directory.php';
		}

		/**
		 * Filter the resolved players directory template path.
		 *
		 * @param string $path    Resolved template path.
		 * @param string $located Theme `locate_template` result (may be empty).
		 */
		$resolved = locate_block_template( $located, 'players-directory', $templates );
		if ( ! $resolved && is_readable( $located ) ) {
			$resolved = $located;
		}

		$resolved = (string) apply_filters(
			'clanspress_load_players_directory_template',
			$resolved,
			$located
		);

		return $resolved !== '' ? $resolved : $template;
	}

	/**
	 * Register shortcode used by the players directory block template.
	 *
	 * @return void
	 */
	public function register_players_directory_shortcode(): void {
		add_shortcode( 'clanspress_players_directory', array( $this, 'render_players_directory_shortcode' ) );
	}

	/**
	 * Renders a simple paginated list of users linked to /players/{nicename}/.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes (unused).
	 * @return string
	 */
	public function render_players_directory_shortcode( $atts = array() ): string {
		$per_page = (int) apply_filters( 'clanspress_players_directory_per_page', 20 );
		$per_page = max( 1, min( 100, $per_page ) );

		$paged = max( 1, (int) get_query_var( 'paged' ) );
		if ( $paged < 1 ) {
			$paged = 1;
		}

		$offset = ( $paged - 1 ) * $per_page;

		$user_query = new \WP_User_Query(
			array(
				'orderby'     => 'display_name',
				'order'       => 'ASC',
				'number'      => $per_page,
				'offset'      => $offset,
				'count_total' => true,
				'fields'      => 'all',
			)
		);

		$users = $user_query->get_results();
		$total = (int) $user_query->get_total();
		$pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;

		ob_start();

		echo '<div class="clanspress-players-directory">';

		if ( empty( $users ) ) {
			echo '<p class="clanspress-players-directory__empty">' . esc_html__( 'No players found.', 'clanspress' ) . '</p>';
		} else {
			echo '<ul class="clanspress-players-directory__list">';
			foreach ( $users as $user ) {
				if ( ! $user instanceof \WP_User ) {
					continue;
				}
				$url = trailingslashit( home_url( '/players/' . $user->user_nicename ) );
				echo '<li class="clanspress-players-directory__item"><a href="' . esc_url( $url ) . '">' . esc_html( $user->display_name ) . '</a></li>';
			}
			echo '</ul>';
		}

		if ( $pages > 1 ) {
			$base   = trailingslashit( home_url( '/players' ) ) . 'page/%#%/';
			$paging = paginate_links(
				array(
					'base'      => $base,
					'format'    => '',
					'current'   => $paged,
					'total'     => max( 1, $pages ),
					'type'      => 'list',
					'prev_text' => __( 'Previous', 'clanspress' ),
					'next_text' => __( 'Next', 'clanspress' ),
				)
			);
			if ( is_string( $paging ) && $paging !== '' ) {
				echo '<nav class="clanspress-players-directory__pagination" aria-label="' . esc_attr__( 'Players list pagination', 'clanspress' ) . '">' . wp_kses_post( $paging ) . '</nav>';
			}
		}

		echo '</div>';

		return (string) ob_get_clean();
	}

	/**
	 * Redirect invalid or inaccessible player subpages to the profile root.
	 *
	 * The root URL (/players/{username}/) is the default profile view.
	 * Redirects when the slug is unknown or when the current viewer may not view that tab (owner-only by default).
	 *
	 * @return void
	 */
	public function maybe_canonicalize_player_profile_subpage(): void {
		if ( ! is_author() ) {
			return;
		}

		$requested = sanitize_key( (string) get_query_var( 'cp_player_subpage' ) );

		// No subpage requested - this is the profile root, which is valid.
		if ( empty( $requested ) ) {
			return;
		}

		$user = get_queried_object();
		if ( ! $user instanceof \WP_User ) {
			return;
		}

		$subpages = function_exists( '\clanspress_get_player_subpages' ) ? \clanspress_get_player_subpages() : array();

		// Unknown subpage slug — redirect to profile root.
		if ( ! isset( $subpages[ $requested ] ) ) {
			$url = trailingslashit( home_url( '/players/' . $user->user_nicename ) );
			wp_safe_redirect( $url, 301 );
			exit;
		}

		$visible = function_exists( '\clanspress_profile_subpages_visible_for_nav' )
			? \clanspress_profile_subpages_visible_for_nav( 'player', (int) $user->ID, $subpages )
			: array();

		if ( isset( $visible[ $requested ] ) ) {
			return;
		}

		$url = trailingslashit( home_url( '/players/' . $user->user_nicename ) );
		wp_safe_redirect( $url, 302 );
		exit;
	}

	/**
	 * Point the Site Editor admin-bar link at Clanspress plugin templates, not the theme fallback (e.g. Archive).
	 *
	 * Block themes set the global `$_wp_current_template_id` during template resolution. Author archives often
	 * resolve to `archive` while we render `clanspress//players-player-profile` via `template_include`.
	 *
	 * @return void
	 */
	public function set_plugin_block_template_id_for_site_editor(): void {
		if ( is_admin() || ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return;
		}

		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Core global `$_wp_current_template_id` for block theme / Site Editor resolution.
		global $_wp_current_template_id;

		$path = $this->get_canonical_request_path();
		$is_players_directory = (int) get_query_var( 'cp_players_directory' )
			|| 'players' === $path
			|| ( $path !== '' && preg_match( '#^players/page/[0-9]+/?$#', $path ) );

		if ( $is_players_directory ) {
			$_wp_current_template_id = 'clanspress//players-directory';
		} elseif ( get_query_var( 'players_settings' ) ) {
			$_wp_current_template_id = 'clanspress//player-settings';
		} else {
			$sub = sanitize_key( (string) get_query_var( 'cp_player_subpage' ) );
			if ( '' !== $sub && is_author() && $this->player_subpage_has_dedicated_template( $sub ) ) {
				$_wp_current_template_id = 'clanspress//player-' . $sub;
			} elseif ( $this->should_use_player_profile_template() ) {
				$_wp_current_template_id = 'clanspress//players-player-profile';
			}
		}

		// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
	}

	/**
	 * Prefer the plugin `players-directory` block template when resolving that slug (e.g. Site Editor preview).
	 *
	 * @param \WP_Block_Template[] $query_result  Block templates.
	 * @param array<string, mixed> $query         Query args.
	 * @param string               $template_type Template type.
	 * @return \WP_Block_Template[]
	 */
	public function prefer_plugin_players_directory_block_template( $query_result, $query, $template_type ) {
		if ( 'wp_template' !== $template_type || empty( $query['slug__in'] ) || ! in_array( 'players-directory', $query['slug__in'], true ) ) {
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
			if ( 'players-directory' !== $t->slug ) {
				$filtered[] = $t;
				continue;
			}
			if ( ! empty( $t->is_custom ) ) {
				$filtered[] = $t;
				continue;
			}
			if ( isset( $t->id ) && 'clanspress//players-directory' === $t->id ) {
				$filtered[] = $t;
			}
		}

		foreach ( $filtered as $t ) {
			if ( $t instanceof \WP_Block_Template && 'players-directory' === $t->slug ) {
				return $filtered;
			}
		}

		$plugin = \get_block_template( 'clanspress//players-directory' );
		if ( $plugin instanceof \WP_Block_Template ) {
			$filtered[] = $plugin;
		}

		return $filtered;
	}

	/**
	 * Prefer the plugin `players-player-profile` block template when resolving that slug (matches {@see register_block_template} id).
	 *
	 * @param \WP_Block_Template[] $query_result  Block templates.
	 * @param array<string, mixed> $query         Query args.
	 * @param string               $template_type Template type.
	 * @return \WP_Block_Template[]
	 */
	public function prefer_plugin_players_player_profile_block_template( $query_result, $query, $template_type ) {
		if ( 'wp_template' !== $template_type || empty( $query['slug__in'] ) || ! in_array( 'players-player-profile', $query['slug__in'], true ) ) {
			return $query_result;
		}

		if ( ! is_author() ) {
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
			if ( 'players-player-profile' !== $t->slug ) {
				$filtered[] = $t;
				continue;
			}
			if ( ! empty( $t->is_custom ) ) {
				$filtered[] = $t;
				continue;
			}
			if ( isset( $t->id ) && 'clanspress//players-player-profile' === $t->id ) {
				$filtered[] = $t;
			}
		}

		foreach ( $filtered as $t ) {
			if ( $t instanceof \WP_Block_Template && 'players-player-profile' === $t->slug ) {
				return $filtered;
			}
		}

		$plugin = \get_block_template( 'clanspress//players-player-profile' );
		if ( $plugin instanceof \WP_Block_Template ) {
			$filtered[] = $plugin;
		}

		return $filtered;
	}

	/**
	 * Prefer the plugin `player-{subpage}` block template when resolving that slug on a matching author sub-route.
	 *
	 * @param \WP_Block_Template[] $query_result  Block templates.
	 * @param array<string, mixed> $query         Query args.
	 * @param string               $template_type Template type.
	 * @return \WP_Block_Template[]
	 */
	public function prefer_plugin_player_subpage_block_template( $query_result, $query, $template_type ) {
		if ( 'wp_template' !== $template_type || empty( $query['slug__in'] ) || ! is_author() ) {
			return $query_result;
		}

		$sub = sanitize_key( (string) get_query_var( 'cp_player_subpage' ) );
		if ( '' === $sub || ! $this->player_subpage_has_dedicated_template( $sub ) ) {
			return $query_result;
		}

		$slug = 'player-' . $sub;
		if ( ! in_array( $slug, $query['slug__in'], true ) ) {
			return $query_result;
		}

		if ( ! is_array( $query_result ) ) {
			return $query_result;
		}

		$plugin_id = 'clanspress//' . $slug;

		$filtered = array();
		foreach ( $query_result as $t ) {
			if ( ! $t instanceof \WP_Block_Template ) {
				continue;
			}
			if ( $slug !== $t->slug ) {
				$filtered[] = $t;
				continue;
			}
			if ( ! empty( $t->is_custom ) ) {
				$filtered[] = $t;
				continue;
			}
			if ( isset( $t->id ) && $plugin_id === $t->id ) {
				$filtered[] = $t;
			}
		}

		foreach ( $filtered as $t ) {
			if ( $t instanceof \WP_Block_Template && $slug === $t->slug ) {
				return $filtered;
			}
		}

		$plugin = \get_block_template( $plugin_id );
		if ( $plugin instanceof \WP_Block_Template ) {
			$filtered[] = $plugin;
		}

		return $filtered;
	}

	/**
	 * Load a dedicated template for registered player subpages (e.g. `player-notifications.php`).
	 *
	 * @param string $template Resolved template path.
	 * @return string
	 */
	public function maybe_load_player_subpage_template( string $template ): string {
		if ( ! is_author() ) {
			return $template;
		}

		$sub = sanitize_key( (string) get_query_var( 'cp_player_subpage' ) );
		if ( '' === $sub || ! $this->player_subpage_has_dedicated_template( $sub ) ) {
			return $template;
		}

		$candidates = array(
			"players/player-{$sub}.php",
			"player-{$sub}.php",
			"players/subpage-{$sub}.php",
			"subpage-{$sub}.php",
		);

		$located = locate_template( $candidates );
		if ( ! $located ) {
			$located = clanspress()->path . "templates/players/player-{$sub}.php";
		}

		if ( ! is_readable( $located ) ) {
			return $template;
		}

		$slug      = 'player-' . $sub;
		$hierarchy = array( "{$slug}.php", 'index.php' );

		$found = function_exists( 'locate_block_template' )
			? locate_block_template( $located, $slug, $hierarchy )
			: $located;
		if ( ! $found && is_readable( $located ) ) {
			$found = $located;
		}

		/**
		 * Filter the resolved player subpage template path.
		 *
		 * @param string $resolved Resolved path (may be empty).
		 * @param string $sub      Subpage slug.
		 * @param string $located  Theme or plugin PHP path.
		 */
		$found = (string) apply_filters( 'clanspress_load_player_subpage_template', $found, $sub, $located );

		return '' !== $found ? $found : $template;
	}

	/**
	 * Prefer the Clanspress player profile template for player author archives.
	 *
	 * @param string $template Resolved template path.
	 * @return string
	 */
	public function maybe_load_player_profile_template( string $template ): string {
		if ( ! $this->should_use_player_profile_template() ) {
			return $template;
		}

		$templates = array(
			'players-player-profile.php',
			'players/player-profile.php',
			'player-profile.php',
		);
		$located   = locate_template( $templates );
		if ( ! $located ) {
			$located = clanspress()->path . 'templates/players/player-profile.php';
		}

		$found = locate_block_template( $located, 'players-player-profile', $templates );
		if ( ! $found && is_readable( $located ) ) {
			$found = $located;
		}

		return $found ? $found : $template;
	}

	/**
	 * Resolve nav/panel slugs from query vars to registered settings sections.
	 *
	 * @return array{0: string, 1: string} Nav key and panel sub-key (may be empty if nothing registered).
	 */
	public function get_resolved_player_settings_route(): array {
		$nav   = sanitize_key( (string) get_query_var( 'players_settings_nav' ) );
		$panel = sanitize_key( (string) get_query_var( 'players_settings_panel' ) );

		$nav_items = (array) apply_filters( 'clanspress_players_settings_nav_items', array() );
		if ( empty( $nav_items ) ) {
			return array( '', '' );
		}

		if ( $nav && ! isset( $nav_items[ $nav ] ) ) {
			$nav   = '';
			$panel = '';
		}

		if ( $nav && $panel ) {
			$sub = (array) apply_filters( "clanspress_players_settings_nav_{$nav}_sub_items", array() );
			if ( ! isset( $sub[ $panel ] ) ) {
				$panel = '';
			}
		}

		if ( $nav && ! $panel ) {
			$sub = (array) apply_filters( "clanspress_players_settings_nav_{$nav}_sub_items", array() );
			$panel = (string) array_key_first( $sub );
		}

		if ( ! $nav || ! $panel ) {
			$nav = (string) array_key_first( $nav_items );
			$sub = (array) apply_filters( "clanspress_players_settings_nav_{$nav}_sub_items", array() );
			$panel = (string) array_key_first( $sub );
		}

		return array( $nav, $panel );
	}

	/**
	 * Redirect single-segment settings URLs to /players/settings/{nav}/{first-panel}/.
	 *
	 * @return void
	 */
	public function maybe_canonicalize_player_settings_url(): void {
		if ( ! get_query_var( 'players_settings' ) ) {
			return;
		}

		$nav_in   = sanitize_key( (string) get_query_var( 'players_settings_nav' ) );
		$panel_in = sanitize_key( (string) get_query_var( 'players_settings_panel' ) );

		list( $canonical_nav, $canonical_panel ) = $this->get_resolved_player_settings_route();

		if ( ! $canonical_nav || ! $canonical_panel ) {
			return;
		}

		$target = trailingslashit( home_url( "/players/settings/{$canonical_nav}/{$canonical_panel}/" ) );

		// /players/settings/ — keep as-is (JS defaults to first tab).
		if ( ! $nav_in ) {
			return;
		}

		// /players/settings/{nav}/ → /players/settings/{nav}/{first-panel}/
		if ( $nav_in && ! $panel_in ) {
			wp_safe_redirect( $target, 301 );
			exit;
		}

		// Invalid or outdated slugs in the URL.
		if ( $nav_in !== $canonical_nav || $panel_in !== $canonical_panel ) {
			wp_safe_redirect( $target, 302 );
			exit;
		}
	}

	public function maybe_load_player_settings_template( $template ) {
		if ( ! get_query_var( 'players_settings' ) ) {
			return $template;
		}

		// Redirect to login if user is not logged in
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( get_permalink() ) ); // Redirect back after login
			exit;
		}

		// Create a hierarchy of templates.
		$templates = array(
			'player-settings.php',
			'index.php',
		);

		// First, search for PHP templates, which block themes can also use.
		$located = locate_template( $templates );
		if ( ! $located ) {
			$located = clanspress()->path . 'templates/players/player-settings.php';
		}

		// Pass the result into the block template locator and let it figure
		// out whether block templates are supported and this template exists.
		$resolved = locate_block_template( $located, 'player-settings', $templates );
		if ( ! $resolved && is_readable( $located ) ) {
			$resolved = $located;
		}

		return apply_filters( 'clanspress_load_player_settings_template', $resolved ? $resolved : $template );
	}

	public function register_profile_templates() {
		$this->register_extension_templates( $this->get_profile_templates() );
	}

	/**
	 * Get FSE templates owned by the Players extension.
	 *
	 * @return array<string, array<string, string>>
	 */
	protected function get_profile_templates(): array {
		return array(
			'player-settings' => array(
				'title' => __( 'Player Settings', 'clanspress' ),
				'path'  => clanspress()->path . 'templates/players/player-settings.html',
			),
			'players-player-profile' => array(
				'title' => __( 'Player Profile', 'clanspress' ),
				'path'  => clanspress()->path . 'templates/players/player-profile.html',
			),
			'players-directory' => array(
				'title' => __( 'Players Directory', 'clanspress' ),
				'path'  => clanspress()->path . 'templates/players/players-directory.html',
			),
		);
	}

	public function register_image_sizes() {
		add_image_size(
			'clanspress-cover',
			1184,
			300,
			true
		);
		add_image_size( 'clanspress-avatar-large', 512, 512, true );
		add_image_size( 'clanspress-avatar-medium', 256, 256, true );
		add_image_size( 'clanspress-avatar-small', 96, 96, true );
	}

	public function register_profile_blocks() {
		$this->register_extension_block_types_from_metadata_collection( 'build/players' );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Whether to print `CLANSPRESSPLAYERSETTINGS` for this front request.
	 *
	 * Defaults avoid per-request nonce generation and inline script output on pages that
	 * never mount player settings (most cached views).
	 *
	 * @return bool
	 */
	protected function should_enqueue_player_settings_frontend_assets(): bool {
		if ( is_admin() ) {
			return false;
		}

		$enqueue = false;

		if ( (int) get_query_var( 'players_settings' ) ) {
			$enqueue = true;
		} elseif ( (int) get_query_var( 'cp_players_directory' ) ) {
			$enqueue = false;
		} elseif ( is_user_logged_in() ) {
			// Author archives include `/players/{nicename}/` after the main query runs on `wp`.
			if ( is_author() ) {
				$enqueue = true;
			} elseif ( is_singular() ) {
				$post = get_queried_object();
				if ( $post instanceof \WP_Post ) {
					$player_blocks = array(
						'clanspress/player-settings',
					);
					foreach ( $player_blocks as $block_name ) {
						if ( has_block( $block_name, $post ) ) {
							$enqueue = true;
							break;
						}
					}
				}
			}
		}

		/**
		 * Whether to enqueue the inline script that defines `CLANSPRESSPLAYERSETTINGS`.
		 *
		 * Return true if a custom template or block needs REST/ajax nonces outside the
		 * default routes (player settings URL, author profile, or singular posts that
		 * contain the player-settings block).
		 *
		 * @param bool $enqueue Default decision from core heuristics.
		 */
		return (bool) apply_filters( 'clanspress_should_enqueue_player_settings_frontend_assets', $enqueue );
	}

	public function enqueue_scripts() {
		if ( ! $this->should_enqueue_player_settings_frontend_assets() ) {
			return;
		}

		wp_register_script(
			'clanspress-player-settings-localize',
			'',
			array(),
			\Kernowdev\Clanspress\Main::VERSION,
			true
		);

		$config = array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'clanspress_profile_settings_save_action' ),
			'rest_url'   => esc_url_raw( rest_url() ),
			'rest_nonce' => wp_create_nonce( 'wp_rest' ),
		);

		if ( get_query_var( 'players_settings' ) ) {
			list( $settings_nav, $settings_panel ) = $this->get_resolved_player_settings_route();
			$config['settings_url_base']      = trailingslashit( home_url( '/players/settings' ) );
			$config['settings_initial_nav']   = $settings_nav;
			$config['settings_initial_panel'] = $settings_panel;
		}

		wp_localize_script(
			'clanspress-player-settings-localize',
			'CLANSPRESSPLAYERSETTINGS',
			apply_filters(
				'clanspress_player_settings_frontend_config',
				$config
			)
		);

		wp_enqueue_script( 'clanspress-player-settings-localize' );
	}

	public function register_player_settings_nav_items( array $items ) {
		$items['profile'] = array(
			'label'       => __( 'Profile', 'clanspress' ),
			'description' => __( 'Public profile data', 'clanspress' ),
		);

		$items['account'] = array(
			'label'       => __( 'Account', 'clanspress' ),
			'description' => __( 'Account settings', 'clanspress' ),
		);

		return $items;
	}

	public function register_user_meta_keys() {
		register_meta(
			'user',
			'cp_player_avatar_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
				'default'           => 0,
			)
		);

		register_meta(
			'user',
			'cp_player_avatar',
			array(
				'type'              => 'string',
				'description'       => 'Player avatar url.',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_cover_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
				'default'           => 0,
			)
		);

		register_meta(
			'user',
			'cp_player_cover',
			array(
				'type'              => 'string',
				'description'       => 'Player cover url.',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_cover_position_x',
			array(
				'type'              => 'number',
				'single'            => true,
				'sanitize_callback' => function ( $value ) {
					return min( 1, max( 0, (float) $value ) );
				},
				'show_in_rest'      => true,
				'default'           => 0.5,
			)
		);

		register_meta(
			'user',
			'cp_player_cover_position_y',
			array(
				'type'              => 'number',
				'single'            => true,
				'sanitize_callback' => function ( $value ) {
					return min( 1, max( 0, (float) $value ) );
				},
				'show_in_rest'      => true,
				'default'           => 0.5,
			)
		);

		register_meta(
			'user',
			'cp_player_tagline',
			array(
				'type'              => 'string',
				'description'       => 'Player tagline',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_website',
			array(
				'type'              => 'string',
				'description'       => 'Player website',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_bio',
			array(
				'type'              => 'string',
				'description'       => 'Player biography',
				'single'            => true,
				'sanitize_callback' => 'sanitize_textarea_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_country',
			array(
				'type'              => 'string',
				'description'       => 'Player country',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_city',
			array(
				'type'              => 'string',
				'description'       => 'Player city',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_birthday',
			array(
				'type'              => 'string',
				'description'       => 'Player birthday',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_first_name',
			array(
				'type'              => 'string',
				'description'       => 'Player first name',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_meta(
			'user',
			'cp_player_last_name',
			array(
				'type'              => 'string',
				'description'       => 'Player last name',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
				'auth_callback'     => function () {
					return current_user_can( 'read' );
				},
			)
		);

		if ( function_exists( 'clanspress_players_get_social_profile_field_definitions' ) ) {
			foreach ( array_keys( clanspress_players_get_social_profile_field_definitions() ) as $social_slug ) {
				$meta_key = clanspress_players_social_profile_meta_key( $social_slug );
				register_meta(
					'user',
					$meta_key,
					array(
						'type'              => 'string',
						'description'       => 'Player social profile: ' . $social_slug,
						'single'            => true,
						'sanitize_callback' => 'clanspress_players_sanitize_social_profile_value',
						'show_in_rest'      => true,
						'default'           => '',
						'auth_callback'     => function () {
							return current_user_can( 'read' );
						},
					)
				);
			}
		}

		// register_meta(
		// 'user',
		// 'clanspress_player_avatar_id',
		// array(
		// 'type'              => 'integer',
		// 'single'            => true,
		// 'sanitize_callback' => 'absint',
		// 'show_in_rest'      => true,
		// 'default'           => 0,
		// )
		// );
	}

	public function register_profile_nav_items( array $items ) {
		$items['profile-info'] = array(
			'label'       => __( 'Profile Info', 'clanspress' ),
			'description' => __( 'General account data', 'clanspress' ),
		);

		$items['social-networks'] = array(
			'label'       => __( 'Social Networks', 'clanspress' ),
			'description' => __( 'Public social links', 'clanspress' ),
		);

		return $items;
	}

	public function register_account_nav_items( array $items ) {
		$items['account-info'] = array(
			'label'       => __( 'Account Info', 'clanspress' ),
			'description' => __( 'General account data', 'clanspress' ),
		);

		return $items;
	}

	public function do_profile_avatar_fields() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return null;
		}

		$user_avatar           = clanspress_players_get_display_avatar( $user_id, true, '', '', 'large' );
		$user_cover            = clanspress_players_get_display_cover( $user_id, true );
		$background_position_x = round( clanspress_players_get_display_cover_position_x( $user_id ) * 100 ) . '% ';
		$background_position_y = round( clanspress_players_get_display_cover_position_y( $user_id ) * 100 ) . '% ';

		$background_position = $background_position_x . ' ' . $background_position_y;

		do_action( 'clanspress_before_profile_avatar_fields', $user_id );

		$avatars_enabled = $this->admin->get( 'enable_avatars' );
		$covers_enabled  = $this->admin->get( 'enable_covers' );

		if ( $avatars_enabled || $covers_enabled ) :
			?>
		<div class="settings-row">
			<div class="avatar-cover-preview">
				<?php if ( $covers_enabled ) : ?>
					<div class="cover-preview" style="background-image: url(<?php echo esc_url( $user_cover ); ?>); background-position: <?php echo esc_attr( $background_position ); ?>;"></div>
				<?php endif; ?>
				<?php if ( $avatars_enabled ) : ?>
					<div class="avatar-preview" style="background-image: url(<?php echo esc_url( $user_avatar ); ?>);"></div>
				<?php endif; ?>
			</div>
			<?php if ( $avatars_enabled ) : ?>
				<button
						class="change-media avatar"
						data-wp-on--click="actions.selectAvatar"
				><?php esc_html_e( 'Set avatar', 'clanspress' ); ?></button>
				<input
						type="file"
						accept="image/png,image/jpeg"
						hidden
						data-wp-on--change="actions.updateAvatar"
						id="profile-avatar"
						name="profile_avatar"
				>
			<?php endif; ?>
			<?php if ( $covers_enabled ) : ?>
				<button
						class="change-media cover"
						data-wp-on--click="actions.selectCover"
				><?php esc_html_e( 'Set cover image', 'clanspress' ); ?></button>
				<input
						type="file"
						accept="image/png,image/jpeg"
						hidden
						data-wp-on--change="actions.updateCover"
						id="profile-cover"
						name="profile_cover"
				>
			<?php endif; ?>
		</div>
		<?php endif; ?>
			<?php
	}

	public function do_profile_info_fields() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return null;
		}

		$user          = get_userdata( $user_id );
		$user_tagline  = clanspress_players_get_display_tagline( $user_id, true );
		$user_bio      = clanspress_players_get_display_bio( $user_id, true );
		$user_website  = clanspress_players_get_display_website( $user_id, true );
		$user_country  = clanspress_players_get_display_country( $user_id, 'code', true );
		$user_city     = clanspress_players_get_display_city( $user_id, true );
		$user_birthday = clanspress_players_get_display_birthday( $user_id, true );

		do_action( 'clanspress_before_profile_info_fields', $user_id, $user );
		?>
		<div class="settings-section">
			<h2 class="settings-section-title"><?php esc_html_e( 'About You', 'clanspress' ); ?></h2>
			<div class="settings-section-row">
				<div class="form-item">
					<div class="form-input">
						<label for="display-name"><?php esc_html_e( 'Profile Name', 'clanspress' ); ?></label>
						<input type="text" id="display-name" name="display_name" value="<?php echo esc_attr( $user->display_name ); ?>" data-wp-class--error="state.isError">
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="display_name" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
				<div class="form-item">
					<div class="form-input">
						<label for="profile-tagline"><?php esc_html_e( 'Tagline', 'clanspress' ); ?></label>
						<input type="text" id="profile-tagline" name="profile_tagline" value="<?php echo esc_attr( $user_tagline ); ?>" data-wp-class--error="state.isError">
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="profile_tagline" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
			</div>
			<div class="settings-section-row">
				<div class="form-item">
					<div class="form-input">
						<label for="profile-description"><?php esc_html_e( 'Description', 'clanspress' ); ?></label>
						<textarea id="profile-description" name="profile_description" data-wp-class--error="state.isError" placeholder="<?php esc_html_e( 'Write a little description about you...', 'clanspress' ); ?>"><?php echo wp_kses_post( $user_bio ); ?></textarea>
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="profile_description" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
				<div class="form-item">
					<div class="form-input">
						<label for="profile-website"><?php esc_html_e( 'Public website', 'clanspress' ); ?></label>
						<input type="text" id="profile-website" name="profile_website" value="<?php echo esc_attr( $user_website ); ?>" data-wp-class--error="state.isError">
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="profile_website" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
			</div>
			<div class="settings-section-row">
				<div class="form-item">
					<div class="form-input">
						<label for="profile-country"><?php esc_html_e( 'Country', 'clanspress' ); ?></label>
						<select id="profile-country" name="profile_country" data-wp-class--error="state.isError">
							<option value="" <?php selected( $user_country, '', true ); ?>><?php esc_html_e( 'Select Country', 'clanspress' ); ?></option>
							<?php
							$countries = clanspress_players_get_countries();

							if ( $countries ) :
								?>
								<?php foreach ( $countries as $country_code => $country ) : ?>
									<option value="<?php echo esc_attr( $country_code ); ?>" <?php selected( $user_country, $country_code, true ); ?>><?php echo esc_html( $country ); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="display_name" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
				<div class="form-item">
					<div class="form-input">
						<label for="profile-city"><?php esc_html_e( 'City', 'clanspress' ); ?></label>
						<input type="text" id="profile-city" name="profile_city" value="<?php echo esc_attr( $user_city ); ?>" data-wp-class--error="state.isError">
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="profile_city" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
			</div>
			<div class="settings-section-row">
				<div class="form-item">
					<div class="form-input">
						<label for="profile-birthday"><?php esc_html_e( 'Birthday', 'clanspress' ); ?></label>
						<input type="date" id="profile-birthday" name="profile_birthday" value="<?php echo esc_attr( $user_birthday ); ?>" data-wp-class--error="state.isError">
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="profile_birthday" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
			</div>
		</div>
		<?php

		do_action( 'clanspress_after_profile_info_fields', $user_id, $user );
	}

	public function do_account_info_fields() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return null;
		}

		$user            = get_userdata( $user_id );
		$user_first_name = clanspress_players_get_account_firstname( $user_id, true );
		$user_last_name  = clanspress_players_get_account_lastname( $user_id, true );

		do_action( 'clanspress_before_account_info_fields', $user_id, $user );
		?>
		<div class="settings-section">
			<h2 class="settings-section-title"><?php esc_html_e( 'Personal Info', 'clanspress' ); ?></h2>
			<div class="settings-section-row">
				<div class="form-item">
					<div class="form-input">
						<label for="account-first-name"><?php esc_html_e( 'First Name', 'clanspress' ); ?></label>
						<input type="text" id="account-first-name" name="account_first_name" value="<?php echo esc_attr( $user_first_name ); ?>" data-wp-class--error="state.isError">
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="account_first_name" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
				<div class="form-item">
					<div class="form-input">
						<label for="account-last-name"><?php esc_html_e( 'Surname', 'clanspress' ); ?></label>
						<input type="text" id="account-last-name" name="account_last_name" value="<?php echo esc_attr( $user_last_name ); ?>" data-wp-class--error="state.isError">
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="account_last_name" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
			</div>
			<div class="settings-section-row">
				<div class="form-item">
					<div class="form-input">
						<label for="account-email"><?php esc_html_e( 'Email Address', 'clanspress' ); ?></label>
						<input type="text" id="account-email" name="account_email" value="<?php echo esc_attr( $user->user_email ); ?>" data-wp-class--error="state.isError" disabled>
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="account_email" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
				<div class="form-item">
					<div class="form-input">
						<label for="account-url"><?php esc_html_e( 'Profile URL', 'clanspress' ); ?></label>
						<p class="description"><?php echo esc_html( trailingslashit( home_url( '/players/' ) ) ); ?></p>
						<input type="text" id="account-url" name="account_url" value="<?php echo esc_attr( $user->user_nicename ); ?>" data-wp-class--error="state.isError" disabled>
						<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="account_url" data-wp-text="state.errorMessage"></div>
					</div>
				</div>
			</div>
		</div>
		<?php

		do_action( 'clanspress_after_account_info_fields', $user_id, $user );
	}

	/**
	 * Renders Profile → Social Networks fields on the front-end player settings screen.
	 *
	 * @return void
	 */
	public function do_social_networks_fields() {
		$user_id = get_current_user_id();

		if ( ! $user_id || ! function_exists( 'clanspress_players_get_social_profile_field_definitions' ) ) {
			return;
		}

		$definitions = clanspress_players_get_social_profile_field_definitions();
		if ( array() === $definitions ) {
			return;
		}

		do_action( 'clanspress_before_social_networks_fields', $user_id );
		?>
		<div class="settings-section">
			<h2 class="settings-section-title"><?php esc_html_e( 'Social profiles', 'clanspress' ); ?></h2>
			<p class="description"><?php esc_html_e( 'These may be shown on your public player profile. Use a full URL, @handle, or username depending on the network.', 'clanspress' ); ?></p>
			<?php
			$row_open = false;
			$index    = 0;
			foreach ( $definitions as $slug => $field ) {
				$label       = isset( $field['label'] ) ? (string) $field['label'] : $slug;
				$placeholder = isset( $field['placeholder'] ) ? (string) $field['placeholder'] : '';
				$post_name   = 'profile_social_' . sanitize_key( $slug );
				$input_id    = 'profile-social-' . sanitize_key( $slug );
				$value       = clanspress_players_get_display_social( (string) $slug, $user_id, true );

				if ( 0 === $index % 2 ) {
					if ( $row_open ) {
						echo '</div>';
					}
					echo '<div class="settings-section-row">';
					$row_open = true;
				}
				?>
			<div class="form-item">
				<div class="form-input">
					<label for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $label ); ?></label>
					<input
						type="text"
						id="<?php echo esc_attr( $input_id ); ?>"
						name="<?php echo esc_attr( $post_name ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						autocomplete="off"
						data-wp-class--error="state.isError"
					>
					<div class="error-message" data-wp-bind--hidden="state.showError" data-wp-args="<?php echo esc_attr( $post_name ); ?>" data-wp-text="state.errorMessage"></div>
				</div>
			</div>
				<?php
				++$index;
			}
			if ( $row_open ) {
				echo '</div>';
			}
			?>
		</div>
		<?php
		do_action( 'clanspress_after_social_networks_fields', $user_id );
	}

	public function save_player_profile_settings( $filtered_data, $data, $files, $user_id ) {
		$errors = array();

		// Handle avatar and cover image first (isolated under uploads/clanspress/players/{id}/).
		if ( isset( $files['profile_avatar'] ) ) {
			$_FILES['profile_avatar'] = $files['profile_avatar'];

			$old_avatar = absint( get_user_meta( $user_id, 'cp_player_avatar_id', true ) );
			if ( $old_avatar ) {
				wp_delete_attachment( $old_avatar, true );
			}

			if ( function_exists( 'clanspress_handle_isolated_image_upload' ) ) {
				$attachment_id = clanspress_handle_isolated_image_upload(
					'profile_avatar',
					0,
					'clanspress/players/' . $user_id,
					'avatar'
				);
			} else {
				$attachment_id = new \WP_Error( 'clanspress_upload_missing', __( 'Upload handler unavailable.', 'clanspress' ) );
			}

			if ( ! is_wp_error( $attachment_id ) ) {
				update_user_meta( $user_id, 'cp_player_avatar_id', $attachment_id );
				update_user_meta( $user_id, 'cp_player_avatar', wp_get_attachment_url( (int) $attachment_id ) );
				/**
				 * Fires after the player successfully uploads a new profile avatar attachment.
				 *
				 * @param int $user_id        Profile owner user ID.
				 * @param int $attachment_id New attachment ID.
				 */
				do_action( 'clanspress_player_avatar_updated', $user_id, (int) $attachment_id );
			} else {
				$errors['profile_avatar'] = $attachment_id->get_error_message();
			}
		}

		if ( isset( $files['profile_cover'] ) ) {
			$_FILES['profile_cover'] = $files['profile_cover'];

			$old_cover = absint( get_user_meta( $user_id, 'cp_player_cover_id', true ) );
			if ( $old_cover ) {
				wp_delete_attachment( $old_cover, true );
			}

			if ( function_exists( 'clanspress_handle_isolated_image_upload' ) ) {
				$attachment_id = clanspress_handle_isolated_image_upload(
					'profile_cover',
					0,
					'clanspress/players/' . $user_id,
					'cover'
				);
			} else {
				$attachment_id = new \WP_Error( 'clanspress_upload_missing', __( 'Upload handler unavailable.', 'clanspress' ) );
			}

			if ( ! is_wp_error( $attachment_id ) ) {
				update_user_meta( $user_id, 'cp_player_cover_id', $attachment_id );
				update_user_meta( $user_id, 'cp_player_cover', wp_get_attachment_image_url( (int) $attachment_id, 'clanspress-cover' ) );
				/**
				 * Fires after the player successfully uploads a new profile cover attachment.
				 *
				 * @param int $user_id        Profile owner user ID.
				 * @param int $attachment_id New attachment ID.
				 */
				do_action( 'clanspress_player_cover_updated', $user_id, (int) $attachment_id );
			} else {
				$errors['profile_cover'] = $attachment_id->get_error_message();
			}
		}

		if ( isset( $filtered_data['profile_cover_position_x'] ) ) {
			$profile_cover_position_x = apply_filters( 'clanspress_player_settings_update_display_cover_position_x', $filtered_data['profile_cover_position_x'], $user_id );

			if ( ! is_wp_error( $profile_cover_position_x ) ) {
				update_user_meta( $user_id, 'cp_player_cover_position_x', $profile_cover_position_x );
			} else {
				$errors['profile_cover_position_x'] = $profile_cover_position_x->get_error_message();
			}
		}

		if ( isset( $filtered_data['profile_cover_position_y'] ) ) {
			$profile_cover_position_y = apply_filters( 'clanspress_player_settings_update_display_cover_position_y', $filtered_data['profile_cover_position_y'], $user_id );

			if ( ! is_wp_error( $profile_cover_position_y ) ) {
				update_user_meta( $user_id, 'cp_player_cover_position_y', $profile_cover_position_y );
			} else {
				$errors['profile_cover_position_y'] = $profile_cover_position_y->get_error_message();
			}
		}

		if ( isset( $filtered_data['display_name'] ) ) {
			$display_name = apply_filters( 'clanspress_player_settings_update_display_name', sanitize_user( $filtered_data['display_name'] ), $user_id );

			if ( ! is_wp_error( $display_name ) ) {
				$result = wp_update_user(
					array(
						'ID'           => $user_id,
						'display_name' => $display_name,
					)
				);
			} else {
				$errors['display_name'] = $display_name->get_error_message();
			}
		}

		if ( isset( $filtered_data['profile_tagline'] ) ) {
			$display_tagline = apply_filters( 'clanspress_player_settings_update_tagline', sanitize_text_field( $filtered_data['profile_tagline'] ), $user_id );

			if ( ! is_wp_error( $display_tagline ) ) {
				update_user_meta( $user_id, 'cp_player_tagline', $display_tagline );
			} else {
				$errors['profile_tagline'] = $display_tagline->get_error_message();
			}
		}

		if ( isset( $filtered_data['profile_description'] ) ) {
			$display_description = apply_filters( 'clanspress_player_settings_update_description', wp_kses_post( $filtered_data['profile_description'] ), $user_id );

			if ( ! is_wp_error( $display_description ) ) {
				update_user_meta( $user_id, 'cp_player_bio', $display_description );
			} else {
				$errors['profile_description'] = $display_description->get_error_message();
			}
		}

		if ( isset( $filtered_data['profile_website'] ) ) {
			$display_website = apply_filters( 'clanspress_player_settings_update_website', sanitize_text_field( $filtered_data['profile_website'] ), $user_id );

			if ( ! is_wp_error( $display_website ) ) {
				update_user_meta( $user_id, 'cp_player_website', $display_website );
			} else {
				$errors['profile_website'] = $display_website->get_error_message();
			}
		}

		if ( isset( $filtered_data['profile_country'] ) ) {
			$display_country = apply_filters( 'clanspress_player_settings_update_country', sanitize_text_field( $filtered_data['profile_country'] ), $user_id );

			if ( ! is_wp_error( $display_country ) ) {
				update_user_meta( $user_id, 'cp_player_country', $display_country );
			} else {
				$errors['profile_country'] = $display_country->get_error_message();
			}
		}

		if ( isset( $filtered_data['profile_city'] ) ) {
			$display_city = apply_filters( 'clanspress_player_settings_update_city', sanitize_text_field( $filtered_data['profile_city'] ), $user_id );

			if ( ! is_wp_error( $display_city ) ) {
				update_user_meta( $user_id, 'cp_player_city', $display_city );
			} else {
				$errors['profile_city'] = $display_city->get_error_message();
			}
		}

		if ( isset( $filtered_data['profile_birthday'] ) ) {
			$display_birthday = apply_filters( 'clanspress_player_settings_update_birthday', sanitize_text_field( $filtered_data['profile_birthday'] ), $user_id );

			if ( ! is_wp_error( $display_birthday ) ) {
				update_user_meta( $user_id, 'cp_player_birthday', $display_birthday );
			} else {
				$errors['profile_birthday'] = $display_birthday->get_error_message();
			}
		}

		if ( function_exists( 'clanspress_players_get_social_profile_field_definitions' ) ) {
			foreach ( array_keys( clanspress_players_get_social_profile_field_definitions() ) as $social_slug ) {
				$social_slug = sanitize_key( (string) $social_slug );
				if ( '' === $social_slug ) {
					continue;
				}
				$post_key = 'profile_social_' . $social_slug;
				if ( ! array_key_exists( $post_key, $filtered_data ) ) {
					continue;
				}
				$raw = clanspress_players_sanitize_social_profile_value( (string) $filtered_data[ $post_key ] );
				/**
				 * Filters a social profile value before it is saved from player settings.
				 *
				 * @param string|WP_Error $value     Sanitized string, or WP_Error to reject.
				 * @param string          $slug      Field slug (e.g. `facebook`).
				 * @param int             $user_id   User ID.
				 */
				$stored = apply_filters( 'clanspress_player_settings_update_social_profile_value', $raw, $social_slug, $user_id );
				if ( is_wp_error( $stored ) ) {
					$errors[ $post_key ] = $stored->get_error_message();
					continue;
				}
				$stored = clanspress_players_sanitize_social_profile_value( (string) $stored );
				update_user_meta( $user_id, clanspress_players_social_profile_meta_key( $social_slug ), $stored );
			}
		}

		if ( ! empty( $errors ) ) {
			add_filter(
				'clanspress_save_player_settings_save_status',
				function ( $saved ) {
					return false;
				}
			);

			add_filter(
				'clanspress_save_player_settings_errors',
				function ( $known_errors ) use ( $errors ) {
					return array_merge( $errors, $known_errors );
				}
			);
		}
	}

	public function save_player_account_info_settings( $filtered_data, $data, $files, $user_id ) {
		$errors = array();

		if ( isset( $filtered_data['account_first_name'] ) ) {
			$account_first_name = apply_filters( 'clanspress_player_settings_update_account_firstname', sanitize_text_field( $filtered_data['account_first_name'] ), $user_id );

			if ( ! is_wp_error( $account_first_name ) ) {
				update_user_meta( $user_id, 'cp_player_first_name', $account_first_name );
			} else {
				$errors['account_fist_name'] = $account_first_name->get_error_message();
			}
		}

		if ( isset( $filtered_data['account_last_name'] ) ) {
			$account_last_name = apply_filters( 'clanspress_player_settings_update_account_lastname', sanitize_text_field( $filtered_data['account_last_name'] ), $user_id );

			if ( ! is_wp_error( $account_last_name ) ) {
				update_user_meta( $user_id, 'cp_player_last_name', $account_last_name );
			} else {
				$errors['account_last_name'] = $account_last_name->get_error_message();
			}
		}

		if ( ! empty( $errors ) ) {
			add_filter(
				'clanspress_save_player_settings_save_status',
				function ( $saved ) {
					return false;
				}
			);

			add_filter(
				'clanspress_save_player_settings_errors',
				function ( $known_errors ) use ( $errors ) {
					return array_merge( $errors, $known_errors );
				}
			);
		}
	}

	/**
	 * Ajax: save player settings.
	 *
	 * This function doesn't save any data, it is only the entry point for
	 * other functions to hook in and save the data. This function returns a
	 * json response to the front-end player settings block.
	 *
	 * Successful responses include `avatarUrl` and `coverUrl` (resolved display URLs)
	 * so inline media blocks can swap previews to server URLs and revoke blob URLs.
	 *
	 * @return void
	 */
	public function ajax_save_player_settings() {
		check_ajax_referer( 'clanspress_profile_settings_save_action', 'nonce' );

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			wp_send_json_error( 'Not logged in' );
		}

		/**
		 * Let third-party developers hook into saving fields.
		 * $data = $_POST, sanitized below.
		 * $files = $_FILES.
		 */
		$data  = wp_unslash( $_POST );
		$files = wp_unslash( $_FILES );

		$filtered_data = apply_filters( 'clanspress_save_player_settings_filtered_data', $data, $user_id );

		// general hook for 3rd parties
		do_action( 'clanspress_save_player_settings', $filtered_data, $data, $files, $user_id );

		$saved  = apply_filters( 'clanspress_save_player_settings_save_status', true );
		$errors = apply_filters( 'clanspress_save_player_settings_errors', array() );

		if ( ! empty( $errors ) || ! $saved ) {
			wp_send_json_error(
				array(
					'errors' => $errors,
				)
			);
		}

		$success_data = array();
		if ( function_exists( 'clanspress_players_get_display_avatar' ) ) {
			$success_data['avatarUrl'] = clanspress_players_get_display_avatar( $user_id, false, '', 'profile_settings_rest', 'large' );
		}
		if ( function_exists( 'clanspress_players_get_display_cover' ) ) {
			$success_data['coverUrl'] = clanspress_players_get_display_cover( $user_id );
		}

		wp_send_json_success( $success_data );
	}

	/**
	 * Resolves the shared player profile header as a theme-scoped template part for `core/template-part`.
	 *
	 * @param \WP_Block_Template|null $block_template Short-circuit return value.
	 * @param string                  $id             Template id (`theme_slug//slug`).
	 * @param string                  $template_type  `wp_template` or `wp_template_part`.
	 * @return \WP_Block_Template|null
	 */
	public function filter_pre_get_block_file_template_player_profile_header( $block_template, string $id, string $template_type ) {
		if ( null !== $block_template || 'wp_template_part' !== $template_type ) {
			return $block_template;
		}

		$parts = explode( '//', $id, 2 );
		if ( count( $parts ) < 2 ) {
			return $block_template;
		}

		list( $theme, $slug ) = $parts;

		if ( get_stylesheet() !== $theme || self::PLAYER_PROFILE_HEADER_TEMPLATE_PART_SLUG !== $slug ) {
			return $block_template;
		}

		return $this->get_player_profile_header_template_part();
	}

	/**
	 * Lists the virtual player profile header with other template parts (Site Editor, inserter).
	 *
	 * @param mixed  $query_result Found templates (expected array of {@see \WP_Block_Template}).
	 * @param mixed  $query        Query arguments (expected array).
	 * @param string $template_type Template type.
	 * @return mixed
	 */
	public function filter_get_block_templates_include_player_profile_header( $query_result, $query, $template_type ) {
		if ( 'wp_template_part' !== $template_type || ! is_array( $query_result ) ) {
			return $query_result;
		}

		$query = is_array( $query ) ? $query : array();

		$part = $this->get_player_profile_header_template_part();
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
	 * Returns a cached {@see \WP_Block_Template} for the player profile header markup file.
	 *
	 * @return \WP_Block_Template|null
	 */
	protected function get_player_profile_header_template_part(): ?\WP_Block_Template {
		if ( null !== self::$player_profile_header_template_part_cache ) {
			return self::$player_profile_header_template_part_cache;
		}

		self::$player_profile_header_template_part_cache = $this->create_player_profile_header_template_part_object();

		return self::$player_profile_header_template_part_cache;
	}

	/**
	 * Builds the virtual template part from `templates/players/parts/player-profile-header.html`.
	 *
	 * Mirrors {@see _build_block_template_result_from_file()} for `wp_template_part` (hooked blocks).
	 *
	 * @return \WP_Block_Template|null
	 */
	protected function create_player_profile_header_template_part_object(): ?\WP_Block_Template {
		$path = clanspress()->path . 'templates/players/parts/player-profile-header.html';

		if ( ! is_readable( $path ) ) {
			return null;
		}

		$raw = file_get_contents( $path );
		if ( false === $raw || '' === trim( $raw ) ) {
			return null;
		}

		$theme = get_stylesheet();
		$slug  = self::PLAYER_PROFILE_HEADER_TEMPLATE_PART_SLUG;

		$template                 = new \WP_Block_Template();
		$template->id             = $theme . '//' . $slug;
		$template->theme          = $theme;
		$template->slug           = $slug;
		$template->type           = 'wp_template_part';
		$template->title          = __( 'Player profile header', 'clanspress' );
		$template->description    = __( 'Shared cover, identity row, and profile navigation for Clanspress player templates.', 'clanspress' );
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
