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
 * Per-team options (join mode, invites, front-end edit, ban capability).
 *
 * @param int $team_id Team post ID.
 * @return array<string, mixed> Shape matches {@see \Kernowdev\Clanspress\Extensions\Teams::get_team_options()}.
 */
function clanspress_teams_get_options( int $team_id ): array {
	$t = clanspress_teams();

	return $t ? $t->get_team_options( $team_id ) : array();
}
