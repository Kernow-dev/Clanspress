<?php

defined( 'ABSPATH' ) || exit;

/**
 * Integration API for group profiles (no `cp_group` CPT and no group templates or blocks in core).
 *
 * A separate plugin may register `cp_group`, group blocks, templates, and virtual template parts;
 * it should use these helpers and filters so the Events extension and shared front-end patterns stay aligned.
 *
 * Core loads this file on every request so `clanspress_register_group_subpage()` and related helpers exist
 * even when no groups plugin is active (they no-op or return safe defaults).
 *
 * @package clanspress
 */

/**
 * Register a group profile subpage (tab) for front-end group profiles.
 *
 * Call on `init` (priority ≤ 20) so subpages exist before block rendering.
 *
 * @param string $slug Unique slug (URL segment after the group base URL).
 * @param array  $args {
 *     Optional. Subpage arguments.
 *
 *     @type string $label        Human-readable nav label.
 *     @type string $template_id  FSE template id (default `clanspress-group-{slug}`).
 *     @type string $default_blocks Optional default block markup for the template.
 *     @type string $capability   Capability required to see the tab in nav (default `read`).
 *     @type int    $position     Sort order (lower first).
 * }
 * @return void
 */
function clanspress_register_group_subpage( string $slug, array $args = array() ): void {
	if ( function_exists( 'clanspress_register_profile_subpage' ) ) {
		clanspress_register_profile_subpage( 'group', $slug, $args );
	}
}

/**
 * All registered group profile subpages.
 *
 * @return array<string, array<string, mixed>>
 */
function clanspress_get_group_subpages(): array {
	return function_exists( 'clanspress_get_profile_subpages' ) ? clanspress_get_profile_subpages( 'group' ) : array();
}

/**
 * Resolve a single group profile subpage config by slug.
 *
 * @param string $slug Subpage slug.
 * @return array<string, mixed>|null
 */
function clanspress_get_group_subpage( string $slug ): ?array {
	return function_exists( 'clanspress_get_profile_subpage' ) ? clanspress_get_profile_subpage( 'group', $slug ) : null;
}

/**
 * Front-end URL for group settings / manage (extension-defined).
 *
 * @param int $group_id Group object ID (meaning defined by the extension).
 * @return string Empty string when no extension provides a URL.
 */
function clanspress_groups_get_manage_url( int $group_id ): string {
	if ( $group_id < 1 ) {
		return '';
	}

	/**
	 * Filter the group “settings” / manage URL used in group profile navigation.
	 *
	 * @param string $url      URL or empty string.
	 * @param int    $group_id Group ID.
	 */
	return (string) apply_filters( 'clanspress_groups_manage_url', '', $group_id );
}

/**
 * Whether the user may see the Settings link in group profile navigation.
 *
 * Extensions implementing group roles should filter this (e.g. group admin/editor).
 *
 * @param int      $group_id Group ID.
 * @param int|null $user_id  User ID or null for the current user.
 * @return bool
 */
function clanspress_groups_user_can_manage( int $group_id, ?int $user_id = null ): bool {
	if ( $group_id < 1 ) {
		return false;
	}

	$user_id = $user_id ?? get_current_user_id();
	if ( $user_id < 1 ) {
		return false;
	}

	/**
	 * Filter whether the user may open group settings from the profile nav.
	 *
	 * @param bool $can      Whether the link should show. Default false.
	 * @param int  $group_id Group ID.
	 * @param int  $user_id  User ID.
	 */
	return (bool) apply_filters( 'clanspress_groups_user_can_manage', false, $group_id, $user_id );
}

/**
 * Whether the user is a member of the group (visibility checks, e.g. team_members–style event access).
 *
 * The plugin that registers `cp_group` should filter this; core defaults to false.
 *
 * @param int      $group_id Group object ID.
 * @param int|null $user_id  User ID or null for the current user.
 * @return bool
 */
function clanspress_groups_user_is_member( int $group_id, ?int $user_id = null ): bool {
	if ( $group_id < 1 ) {
		return false;
	}

	$user_id = $user_id ?? get_current_user_id();
	if ( $user_id < 1 ) {
		return false;
	}

	/**
	 * Filter whether a user counts as a group member for core features (events, etc.).
	 *
	 * @param bool $is_member Whether the user is a member.
	 * @param int  $group_id  Group ID.
	 * @param int  $user_id   User ID.
	 */
	return (bool) apply_filters( 'clanspress_groups_user_is_member', false, $group_id, $user_id );
}

/**
 * Group ID for profile header/nav (singular `cp_group` or `clanspress_group_profile_nav_context`).
 *
 * @return int
 */
function clanspress_group_profile_context_group_id(): int {
	/**
	 * Virtual group profile context when not on a singular `cp_group` post.
	 *
	 * @param array<string, mixed>|null $context Context or null.
	 */
	$virtual = apply_filters( 'clanspress_group_profile_nav_context', null );
	if ( is_array( $virtual ) && isset( $virtual['group_id'] ) && (int) $virtual['group_id'] > 0 ) {
		return (int) $virtual['group_id'];
	}

	if ( is_singular( 'cp_group' ) ) {
		$qid = (int) get_queried_object_id();
		if ( $qid > 0 ) {
			return $qid;
		}
	}

	return 0;
}

/**
 * Active group profile sub-route slug (extension filter or `cp_group_subpage`).
 *
 * @return string
 */
function clanspress_group_profile_route_current_slug(): string {
	/**
	 * Virtual group profile context when not on a singular `cp_group` post.
	 *
	 * @param array<string, mixed>|null $context Context or null.
	 */
	$virtual = apply_filters( 'clanspress_group_profile_nav_context', null );
	if ( is_array( $virtual ) && isset( $virtual['current_slug'] ) ) {
		return sanitize_key( (string) $virtual['current_slug'] );
	}

	if ( is_singular( 'cp_group' ) ) {
		return sanitize_key( (string) get_query_var( 'cp_group_subpage' ) );
	}

	return '';
}
