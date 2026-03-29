<?php
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

	return 0;
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
