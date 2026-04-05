<?php

defined( 'ABSPATH' ) || exit;

/**
 * Procedural API for the Teams extension (themes and third-party plugins).
 *
 * These functions wrap {@see \Kernowdev\Clanspress\Extensions\Teams} so callers do not need
 * to resolve the loader. When the Teams extension is not active, helpers return safe
 * defaults (`null`, empty arrays/strings, or `false`).
 *
 * @package clanspress
 */

use Kernowdev\Clanspress\Extensions\Teams;

/**
 * Active Teams extension instance from the loader.
 *
 * @return Teams|null Null when Teams is not registered or not the expected class.
 */
function clanspress_teams(): ?Teams {
	$loader = clanspress()->extensions;
	if ( null === $loader ) {
		return null;
	}

	$ext = $loader->get( 'cp_teams' );

	return $ext instanceof Teams ? $ext : null;
}

/**
 * Load a team entity by post ID (uses the active {@see Team_Data_Store}).
 *
 * @param int $id Team post ID.
 * @return \Kernowdev\Clanspress\Extensions\Teams\Team|null
 */
function clanspress_get_team( int $id ): ?\Kernowdev\Clanspress\Extensions\Teams\Team {
	$t = clanspress_teams();

	return $t ? $t->get_team( $id ) : null;
}

/**
 * Read a Teams admin setting (from the extension settings screen).
 *
 * @param string     $key      Settings key defined in Teams admin sections.
 * @param mixed|null $fallback Value when the extension is inactive or key is missing.
 * @return mixed
 */
function clanspress_teams_get_setting( string $key, $fallback = null ) {
	$t = clanspress_teams();

	return $t ? $t->get_setting( $key, $fallback ) : $fallback;
}

/**
 * Resolved global team mode (`single_team`, `multiple_teams`, `team_directories`).
 *
 * @return string
 */
function clanspress_teams_get_team_mode(): string {
	$t = clanspress_teams();

	return $t ? $t->get_team_mode() : 'single_team';
}

/**
 * Front-end URL for the “create team” flow.
 *
 * @return string Absolute URL; falls back to `/teams/create/` on the home URL when the extension is off.
 */
function clanspress_teams_get_team_create_url(): string {
	$t = clanspress_teams();

	return $t ? $t->get_team_create_url() : home_url( '/teams/create/' );
}

/**
 * Canonical front-end URL for a team profile given its `post_name` slug.
 *
 * @param string $slug Team slug (`post_name`).
 * @return string Empty when Teams is inactive or the slug is invalid.
 */
function clanspress_teams_get_team_profile_url_for_slug( string $slug ): string {
	$t = clanspress_teams();

	return $t ? $t->get_team_profile_url_for_slug( $slug ) : '';
}

/**
 * Front-end URL for the team manage screen.
 *
 * @param int $team_id Team post ID.
 * @return string Empty string when the extension is inactive.
 */
function clanspress_teams_get_team_manage_url( int $team_id ): string {
	$t = clanspress_teams();

	return $t ? $t->get_team_manage_url( $team_id ) : '';
}

/**
 * Front-end URL for a registered team action (e.g. `manage`).
 *
 * @param int    $team_id Team post ID.
 * @param string $action  Action slug registered with the Teams extension.
 * @return string Empty string when the extension is inactive.
 */
function clanspress_teams_get_team_action_url( int $team_id, string $action ): string {
	$t = clanspress_teams();

	return $t ? $t->get_team_action_url( $team_id, $action ) : '';
}

/**
 * Front-end URL to create a team event (`/teams/{slug}/events/create/`).
 *
 * @param int $team_id Team post ID (`cp_team`).
 * @return string Empty when the team has no slug.
 */
function clanspress_teams_get_team_events_create_url( int $team_id ): string {
	$slug = (string) get_post_field( 'post_name', $team_id );
	if ( '' === $slug ) {
		return '';
	}

	return home_url( user_trailingslashit( 'teams/' . $slug . '/events/create' ) );
}

/**
 * User ID of the team owner (`post_author` of the `cp_team` post).
 *
 * @param int $team_id Team post ID.
 * @return int `0` if the post has no author.
 */
function clanspress_teams_get_owner_id( int $team_id ): int {
	return (int) get_post_field( 'post_author', $team_id );
}

/**
 * Whether the user is the team owner (post author).
 *
 * @param int $team_id Team post ID.
 * @param int $user_id User ID.
 * @return bool
 */
function clanspress_teams_user_is_owner( int $team_id, int $user_id ): bool {
	$owner = clanspress_teams_get_owner_id( $team_id );

	return $owner > 0 && $owner === $user_id;
}

/**
 * Whether the user bypasses roster checks as a site/network administrator.
 *
 * @param int|null $user_id User ID, or `null` for the current user.
 * @return bool False when Teams is inactive.
 */
function clanspress_teams_user_is_site_admin( ?int $user_id = null ): bool {
	$t = clanspress_teams();

	return $t ? $t->user_is_teams_site_admin( $user_id ) : false;
}

/**
 * Whether the user may use the front-end manage UI (editor/admin roster, or site admin).
 *
 * @param int      $team_id Team post ID.
 * @param int|null $user_id User ID, or `null` for the current user.
 * @return bool
 */
function clanspress_teams_user_can_manage( int $team_id, ?int $user_id = null ): bool {
	$t = clanspress_teams();

	return $t ? $t->user_can_manage_team_on_frontend( $team_id, $user_id ) : false;
}

/**
 * Whether the user may permanently delete a team from the front-end manage screen.
 *
 * @param int      $team_id Team post ID.
 * @param int|null $user_id User ID, or null for the current user.
 * @return bool False when Teams is inactive.
 */
function clanspress_teams_user_can_delete_team( int $team_id, ?int $user_id = null ): bool {
	$t = clanspress_teams();

	return $t ? $t->user_can_delete_team_on_frontend( $team_id, $user_id ) : false;
}

/**
 * Whether the user may edit the roster (team admin or site admin).
 *
 * @param int      $team_id Team post ID.
 * @param int|null $user_id User ID, or `null` for the current user.
 * @return bool
 */
function clanspress_teams_user_is_team_admin( int $team_id, ?int $user_id = null ): bool {
	$t = clanspress_teams();

	return $t ? $t->user_is_team_admin_on_frontend( $team_id, $user_id ) : false;
}

/**
 * Role slug for a member, or `null` if they are not on the roster (and not the owner fallback).
 *
 * @param int $team_id Team post ID.
 * @param int $user_id User ID.
 * @return string|null
 */
function clanspress_teams_get_member_role( int $team_id, int $user_id ): ?string {
	$t = clanspress_teams();

	return $t ? $t->get_team_member_role( $team_id, $user_id ) : null;
}

/**
 * Full roster map after extension rules (author as admin when missing from meta, filters, etc.).
 *
 * @param int $team_id Team post ID.
 * @return array<int, string> User ID => role slug.
 */
function clanspress_teams_get_member_roles_map( int $team_id ): array {
	$t = clanspress_teams();

	return $t ? $t->get_team_member_roles_map( $team_id ) : array();
}

/**
 * User IDs on a team roster for templating (e.g. Player Query loop), optionally omitting banned members.
 *
 * @param int  $team_id               Team post ID.
 * @param bool $exclude_banned        When true, skip members with the `banned` role.
 * @param bool $preserve_roster_order When true, keep member map iteration order; when false, sort by user ID (default).
 * @return list<int>
 */
function clanspress_teams_get_roster_user_ids( int $team_id, bool $exclude_banned = true, bool $preserve_roster_order = false ): array {
	if ( $team_id < 1 ) {
		return array();
	}

	$map = clanspress_teams_get_member_roles_map( $team_id );
	$ids = array();

	foreach ( $map as $uid => $role ) {
		$uid = (int) $uid;
		if ( $uid < 1 ) {
			continue;
		}
		if ( $exclude_banned && 'banned' === (string) $role ) {
			continue;
		}
		$ids[] = $uid;
	}

	if ( ! $preserve_roster_order ) {
		sort( $ids, SORT_NUMERIC );
	}

	/**
	 * Filter roster user IDs before rendering a team player loop.
	 *
	 * @param list<int> $ids             Ordered user IDs.
	 * @param int       $team_id         Team post ID.
	 * @param bool      $exclude_banned  Whether banned members were omitted.
	 */
	return array_values( array_map( 'intval', (array) apply_filters( 'clanspress_team_roster_user_ids', $ids, $team_id, $exclude_banned ) ) );
}

/**
 * Per-team options (join mode, invites, front-end edit, ban capability, match challenges).
 *
 * @param int $team_id Team post ID.
 * @return array<string, mixed> Shape matches {@see \Kernowdev\Clanspress\Extensions\Teams::get_team_options()}.
 */
function clanspress_teams_get_options( int $team_id ): array {
	$t = clanspress_teams();

	return $t ? $t->get_team_options( $team_id ) : array();
}

/**
 * Whether a team accepts match challenges from other teams (defaults true when unknown).
 *
 * @param int $team_id Team post ID.
 * @return bool
 */
function clanspress_team_accepts_challenges( int $team_id ): bool {
	if ( $team_id < 1 ) {
		return true;
	}

	$team = clanspress_get_team( $team_id );

	return $team ? $team->get_accept_challenges() : true;
}

/**
 * Parse a team profile URL into origin + slug when it matches `/teams/{slug}/` (with optional subpaths).
 *
 * @param string $url Full HTTP(S) URL.
 * @return array{origin: string, slug: string}|null Null when the pattern does not match.
 */
function clanspress_parse_team_profile_url( string $url ): ?array {
	$url = trim( $url );
	if ( '' === $url ) {
		return null;
	}

	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
		return null;
	}

	$scheme = isset( $parts['scheme'] ) && in_array( strtolower( (string) $parts['scheme'] ), array( 'http', 'https' ), true )
		? strtolower( (string) $parts['scheme'] )
		: 'https';

	$host = strtolower( (string) $parts['host'] );
	$port = isset( $parts['port'] ) ? ':' . (int) $parts['port'] : '';
	$path = isset( $parts['path'] ) ? '/' . ltrim( (string) $parts['path'], '/' ) : '/';

	if ( ! preg_match( '#/teams/([^/]+)#', $path, $m ) ) {
		return null;
	}

	$slug = sanitize_title( (string) $m[1] );
	if ( '' === $slug || in_array( $slug, array( 'create', 'manage' ), true ) ) {
		return null;
	}

	$origin = $scheme . '://' . $host . $port;

	return array(
		'origin' => $origin,
		'slug'   => $slug,
	);
}

/**
 * Team post IDs the user may manage on the front end (admin or editor, not banned).
 *
 * @param int $user_id User ID.
 * @return array<int, int> Unique team post IDs.
 */
function clanspress_teams_get_user_managed_team_ids( int $user_id ): array {
	$t = clanspress_teams();

	return $t ? $t->get_user_managed_team_ids( $user_id ) : array();
}

/**
 * Whether the user manages at least one published team from the front end.
 *
 * @param int|null $user_id User ID or null for the current user.
 * @return bool
 */
function clanspress_teams_user_manages_any_team( ?int $user_id = null ): bool {
	$uid = (int) ( $user_id ?? get_current_user_id() );
	if ( $uid < 1 ) {
		return false;
	}

	$t = clanspress_teams();
	if ( $t && $t->user_is_teams_site_admin( $uid ) ) {
		return true;
	}

	return array() !== clanspress_teams_get_user_managed_team_ids( $uid );
}

/**
 * Register a team profile subpage (tab) for the front-end team profile.
 *
 * @param string $slug Unique slug (used in the URL).
 * @param array  $args {
 *     @type string $label          Human-readable label.
 *     @type string $template_id    FSE template identifier (defaults to "clanspress-team-{$slug}").
 *     @type string $default_blocks Optional default block markup.
 *     @type string $capability     Capability required to view (default "read").
 *     @type int    $position       Sort order (lower first).
 * }
 * @return void
 */
function clanspress_register_team_subpage( string $slug, array $args = array() ): void {
	if ( function_exists( 'clanspress_register_profile_subpage' ) ) {
		clanspress_register_profile_subpage( 'team', $slug, $args );
	}
}

/**
 * All registered team profile subpages.
 *
 * @return array<string,array>
 */
function clanspress_get_team_subpages(): array {
	return function_exists( 'clanspress_get_profile_subpages' ) ? clanspress_get_profile_subpages( 'team' ) : array();
}

/**
 * Resolve a single team subpage config by slug.
 *
 * @param string $slug Subpage slug.
 * @return array<string,mixed>|null
 */
function clanspress_get_team_subpage( string $slug ): ?array {
	return function_exists( 'clanspress_get_profile_subpage' ) ? clanspress_get_profile_subpage( 'team', $slug ) : null;
}

/**
 * Global default team avatar image URL (Teams settings, then bundled asset).
 *
 * @param int $team_id Team post ID for filters.
 * @return string
 */
function clanspress_teams_get_default_avatar_url( int $team_id = 0 ): string {
	$t   = clanspress_teams();
	$url = $t ? (string) $t->get_setting( 'default_team_avatar', '' ) : '';
	$url = trim( $url );

	if ( '' === $url ) {
		$url = clanspress()->url . 'assets/img/avatars/default-avatar.png';
	}

	/**
	 * Filter default team avatar URL when the team has no custom avatar.
	 *
	 * @param string $url     Resolved URL.
	 * @param int    $team_id Team post ID (0 if unknown).
	 */
	return (string) apply_filters( 'clanspress_teams_default_avatar_url', $url, $team_id );
}

/**
 * Global default team cover image URL (Teams settings, then bundled asset).
 *
 * @param int $team_id Team post ID for filters.
 * @return string
 */
function clanspress_teams_get_default_cover_url( int $team_id = 0 ): string {
	$t   = clanspress_teams();
	$url = $t ? (string) $t->get_setting( 'default_team_cover', '' ) : '';
	$url = trim( $url );

	if ( '' === $url ) {
		$url = clanspress()->url . 'assets/img/covers/default-cover.png';
	}

	/**
	 * Filter default team cover URL when the team has no custom cover.
	 *
	 * @param string $url     Resolved URL.
	 * @param int    $team_id Team post ID (0 if unknown).
	 */
	return (string) apply_filters( 'clanspress_teams_default_cover_url', $url, $team_id );
}

/**
 * Resolve the current `cp_team` post ID for team blocks.
 *
 * Order: block context (`postId` / `postType`, e.g. Query Loop) → main-query singular `cp_team`
 * (covers plugin block templates before `the_post()`) → `get_the_ID()` → global `$post` → queried object.
 *
 * @param array<string, mixed> $block_context Block context (`$block->context`).
 * @return int Team post ID or 0 when none applies.
 */
function clanspress_team_block_resolve_team_id( array $block_context = array() ): int {
	// 1. Block context: Query Loop and parents pass `postId` (and often `postType`).
	if ( ! empty( $block_context['postId'] ) ) {
		$pid = (int) $block_context['postId'];
		if ( $pid > 0 ) {
			$ptype = isset( $block_context['postType'] ) ? (string) $block_context['postType'] : '';
			if ( 'cp_team' === $ptype || 'cp_team' === get_post_type( $pid ) ) {
				return $pid;
			}
		}
	}

	// 2. Main query singular team (plugin block templates may skip the_post(); queried object / posts[0] still valid).
	global $wp_query;
	if ( empty( $block_context['postId'] ) && $wp_query instanceof \WP_Query && $wp_query->is_singular( 'cp_team' ) ) {
		$qid = (int) $wp_query->get_queried_object_id();
		if ( $qid > 0 ) {
			return $qid;
		}
		if ( isset( $wp_query->posts[0] ) && $wp_query->posts[0] instanceof \WP_Post && 'cp_team' === $wp_query->posts[0]->post_type ) {
			return (int) $wp_query->posts[0]->ID;
		}
	}

	// 3. Current post in the main loop or a Query Loop (`the_post` / iteration).
	$current_id = (int) get_the_ID();
	if ( $current_id > 0 && 'cp_team' === get_post_type( $current_id ) ) {
		return $current_id;
	}

	// 4. Global post (set during template / before inner blocks run).
	global $post;
	if ( $post instanceof \WP_Post && 'cp_team' === $post->post_type ) {
		return (int) $post->ID;
	}

	// 5. Singular team views (block themes may render before the loop in edge cases).
	if ( is_singular( 'cp_team' ) ) {
		$qid = (int) get_queried_object_id();
		if ( $qid > 0 ) {
			return $qid;
		}
	}

	$qo = get_queried_object();
	if ( $qo instanceof \WP_Post && 'cp_team' === $qo->post_type ) {
		return (int) $qo->ID;
	}

	$virtual_id = clanspress_team_virtual_route_team_id();
	if ( $virtual_id > 0 ) {
		return $virtual_id;
	}

	return 0;
}

/**
 * Team post ID from virtual team URLs (`/teams/{slug}/events/`, `/teams/{slug}/manage/`), or 0.
 *
 * @return int
 */
function clanspress_team_virtual_route_team_id(): int {
	$action = sanitize_key( (string) get_query_var( 'clanspress_team_action' ) );
	if ( (int) get_query_var( 'clanspress_events_team_id' ) > 0 && 'events' === $action ) {
		return (int) get_query_var( 'clanspress_events_team_id' );
	}
	if ( (int) get_query_var( 'clanspress_manage_team_id' ) > 0 && 'manage' === $action ) {
		return (int) get_query_var( 'clanspress_manage_team_id' );
	}

	return 0;
}

/**
 * Team ID for profile header/nav: singular `cp_team` or virtual team routes.
 *
 * @return int
 */
function clanspress_team_profile_context_team_id(): int {
	if ( is_singular( 'cp_team' ) ) {
		$qid = (int) get_queried_object_id();
		if ( $qid > 0 ) {
			return $qid;
		}
	}

	return clanspress_team_virtual_route_team_id();
}

/**
 * Active profile sub-route slug for team nav (e.g. `events`, `settings` for manage, or registered subpage).
 *
 * @return string
 */
function clanspress_team_profile_route_current_slug(): string {
	if ( is_singular( 'cp_team' ) ) {
		return sanitize_key( (string) get_query_var( 'cp_team_subpage' ) );
	}

	$action = sanitize_key( (string) get_query_var( 'clanspress_team_action' ) );
	if ( 'events' === $action ) {
		return 'events';
	}
	if ( 'manage' === $action ) {
		return 'settings';
	}

	return '';
}

/**
 * Resolve the team post ID for team block `render.php` callbacks.
 *
 * @param \WP_Block $block Current block instance.
 * @return int Team post ID or 0 when unknown.
 */
function clanspress_team_single_block_team_id( \WP_Block $block ): int {
	return clanspress_team_block_resolve_team_id( isset( $block->context ) ? (array) $block->context : array() );
}

/**
 * Human-readable country name for a stored ISO code (uses Players country list when available).
 *
 * @param string $code ISO code or empty.
 * @return string
 */
function clanspress_team_country_label( string $code ): string {
	$code = sanitize_text_field( $code );
	if ( '' === $code ) {
		return '';
	}

	if ( function_exists( 'clanspress_players_get_countries' ) ) {
		$countries = clanspress_players_get_countries();
		if ( isset( $countries[ $code ] ) ) {
			return (string) $countries[ $code ];
		}
	}

	return $code;
}

/**
 * Member count for a team (non-banned roster by default).
 *
 * @param int $team_id Team post ID.
 * @return int
 */
function clanspress_team_get_member_count( int $team_id ): int {
	$t = clanspress_teams();

	return $t ? $t->get_team_member_count( $team_id ) : 0;
}
