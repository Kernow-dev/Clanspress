<?php
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
