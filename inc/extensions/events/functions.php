<?php

defined( 'ABSPATH' ) || exit;

/**
 * Events extension helpers for themes and third-party code.
 *
 * @package clanspress
 */

/**
 * Whether the Events extension (`cp_events`) is installed and enabled.
 *
 * `cp_event` CPT, RSVP tables, REST routes, and event blocks load only when this is true.
 *
 * @return bool
 */
function clanspress_events_extension_active(): bool {
	if ( ! class_exists( \Kernowdev\Clanspress\Extensions\Loader::class ) ) {
		return false;
	}

	$active = \Kernowdev\Clanspress\Extensions\Loader::instance()->is_extension_installed( 'cp_events' );

	/**
	 * Filter whether the Events extension is considered active.
	 *
	 * @param bool $active True when `cp_events` is in the installed-extensions option.
	 */
	return (bool) apply_filters( 'clanspress_events_extension_active', $active );
}

/**
 * Whether the Events extension should register the player profile Events subpage and template.
 *
 * Controlled from Clanspress → Players → Player profile: Events tab (`clanspress_players_settings`).
 * Falls back to legacy `clanspress_events_settings.subpage_player` when the Players field was never saved.
 *
 * @return bool
 */
function clanspress_events_subpage_player_enabled(): bool {
	if ( ! clanspress_events_extension_active() ) {
		return false;
	}

	$stored = get_option( 'clanspress_players_settings', array() );
	if ( is_array( $stored ) && array_key_exists( 'events_profile_subpage', $stored ) ) {
		return ! empty( $stored['events_profile_subpage'] );
	}

	$legacy = get_option( 'clanspress_events_settings', array() );
	if ( is_array( $legacy ) && array_key_exists( 'subpage_player', $legacy ) ) {
		return ! empty( $legacy['subpage_player'] );
	}

	return true;
}

/**
 * Whether the Events extension should register the team profile Events subpage and routes.
 *
 * Controlled from Clanspress → Teams → Team profile: Events tab (`clanspress_teams_settings`).
 * Falls back to legacy `clanspress_events_settings.subpage_team` when the Teams field was never saved.
 *
 * @return bool
 */
function clanspress_events_subpage_team_enabled(): bool {
	if ( ! clanspress_events_extension_active() ) {
		return false;
	}

	$stored = get_option( 'clanspress_teams_settings', array() );
	if ( is_array( $stored ) && array_key_exists( 'events_profile_subpage', $stored ) ) {
		return ! empty( $stored['events_profile_subpage'] );
	}

	$legacy = get_option( 'clanspress_events_settings', array() );
	if ( is_array( $legacy ) && array_key_exists( 'subpage_team', $legacy ) ) {
		return ! empty( $legacy['subpage_team'] );
	}

	return true;
}

/**
 * Whether the Events extension should register the group profile Events subpage and block template.
 *
 * Controlled from Clanspress → Groups → Group profile: Events tab (`clanspress_groups_settings`).
 * Falls back to legacy `clanspress_events_settings.subpage_group` when the Groups field was never saved.
 *
 * @return bool
 */
function clanspress_events_subpage_group_enabled(): bool {
	if ( ! clanspress_events_extension_active() ) {
		return false;
	}

	$stored = get_option( 'clanspress_groups_settings', array() );
	if ( is_array( $stored ) && array_key_exists( 'events_profile_subpage', $stored ) ) {
		return ! empty( $stored['events_profile_subpage'] );
	}

	$legacy = get_option( 'clanspress_events_settings', array() );
	if ( is_array( $legacy ) && array_key_exists( 'subpage_group', $legacy ) ) {
		return ! empty( $legacy['subpage_group'] );
	}

	return true;
}
