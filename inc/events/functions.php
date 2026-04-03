<?php
/**
 * Procedural helpers for scheduled events (feature flags, team/group scope).
 *
 * @package clanspress
 */

use Kernowdev\Clanspress\Admin\General_Settings;

/**
 * Whether events are enabled site-wide (Clanspress → Settings → General).
 *
 * @return bool
 */
function clanspress_events_are_globally_enabled(): bool {
	$defaults = array( 'events_enabled' => true );
	$stored   = get_option( General_Settings::OPTION_KEY, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	$merged = wp_parse_args( $stored, $defaults );

	return ! empty( $merged['events_enabled'] );
}

/**
 * Read a per-post "events enabled" flag: empty meta inherits enabled when global is on; explicit 0 disables.
 *
 * @param mixed $raw Raw meta value.
 * @return bool True when the entity allows events (subject to global).
 */
function clanspress_events_parse_entity_enabled_meta( $raw ): bool {
	if ( '' === $raw || null === $raw ) {
		return true;
	}
	if ( false === $raw || 0 === $raw || '0' === $raw ) {
		return false;
	}
	if ( true === $raw || 1 === $raw || '1' === $raw ) {
		return true;
	}
	if ( is_string( $raw ) ) {
		return in_array( strtolower( trim( $raw ) ), array( 'yes', 'on', 'true' ), true );
	}

	return (bool) $raw;
}

/**
 * Whether events are available for a team (global + per-team meta).
 *
 * @param int $team_id Team post ID (`cp_team`).
 * @return bool
 */
function clanspress_events_are_enabled_for_team( int $team_id ): bool {
	if ( $team_id < 1 ) {
		return false;
	}
	if ( ! clanspress_events_are_globally_enabled() ) {
		return false;
	}

	$raw = get_post_meta( $team_id, 'cp_team_events_enabled', true );

	return clanspress_events_parse_entity_enabled_meta( $raw );
}

/**
 * Whether events are available for a group (global + per-group meta).
 *
 * @param int $group_id Group post ID (`cp_group`).
 * @return bool
 */
function clanspress_events_are_enabled_for_group( int $group_id ): bool {
	if ( $group_id < 1 ) {
		return false;
	}
	if ( ! clanspress_events_are_globally_enabled() ) {
		return false;
	}

	$raw = get_post_meta( $group_id, 'cp_group_events_enabled', true );

	return clanspress_events_parse_entity_enabled_meta( $raw );
}

/**
 * Team post IDs used when building a player’s merged “my events” calendar.
 *
 * Defaults to `cp_team_membership_ids` user meta. Extensions may add IDs via
 * {@see clanspress_events_player_calendar_team_ids}.
 *
 * @param int $user_id WordPress user ID.
 * @return int[] Unique team IDs.
 */
function clanspress_events_get_user_team_ids_for_calendar( int $user_id ): array {
	if ( $user_id < 1 ) {
		return array();
	}
	$raw = get_user_meta( $user_id, 'cp_team_membership_ids', true );
	if ( ! is_array( $raw ) ) {
		$raw = array();
	}
	$ids = array_values(
		array_unique(
			array_filter(
				array_map( 'intval', $raw ),
				static function ( int $id ): bool {
					return $id > 0;
				}
			)
		)
	);

	/**
	 * Filter team IDs included in a player’s merged event calendar REST query.
	 *
	 * @param int[] $team_ids Team post IDs.
	 * @param int   $user_id  Profile owner user ID.
	 */
	$filtered = apply_filters( 'clanspress_events_player_calendar_team_ids', $ids, $user_id );

	return array_values(
		array_unique(
			array_filter(
				array_map( 'intval', is_array( $filtered ) ? $filtered : array() ),
				static function ( int $id ): bool {
					return $id > 0;
				}
			)
		)
	);
}

/**
 * Group post IDs used when building a player’s merged calendar (extensions add IDs via filter).
 *
 * Core returns an empty list; extensions should populate via
 * {@see clanspress_events_player_calendar_group_ids}.
 *
 * @param int $user_id WordPress user ID.
 * @return int[] Unique group IDs.
 */
function clanspress_events_get_user_group_ids_for_calendar( int $user_id ): array {
	if ( $user_id < 1 ) {
		return array();
	}

	/**
	 * Filter group IDs included in a player’s merged event calendar REST query.
	 *
	 * @param int[] $group_ids Group post IDs (empty by default).
	 * @param int   $user_id   Profile owner user ID.
	 */
	$filtered = apply_filters( 'clanspress_events_player_calendar_group_ids', array(), $user_id );

	return array_values(
		array_unique(
			array_filter(
				array_map( 'intval', is_array( $filtered ) ? $filtered : array() ),
				static function ( int $id ): bool {
					return $id > 0;
				}
			)
		)
	);
}

/**
 * Build static month grid markup for the event calendar block (empty cells; matches client grid classes).
 *
 * @param string $anchor_ymd     Any date in the target month (`Y-m-d`, site timezone).
 * @param array  $weekday_labels Seven short labels (Sunday-first), same order as JS `getDay()`.
 * @param string $today_ymd      Site-local today as `Y-m-d` (e.g. from `wp_date( 'Y-m-d' )`).
 * @return string Safe HTML fragment (no outer wrapper).
 */
function clanspress_event_calendar_month_grid_markup( string $anchor_ymd, array $weekday_labels, string $today_ymd ): string {
	$tz = wp_timezone();

	try {
		$anchor = new \DateTimeImmutable( $anchor_ymd . ' 12:00:00', $tz );
	} catch ( \Exception $e ) {
		$anchor = new \DateTimeImmutable( 'now', $tz );
	}

	$first_of_month = $anchor->modify( 'first day of this month' )->setTime( 0, 0, 0 );
	$dow            = (int) $first_of_month->format( 'w' );
	$start          = $first_of_month->modify( '-' . $dow . ' days' );

	$html  = '<div class="clanspress-event-calendar__month">';
	$html .= '<div class="clanspress-event-calendar__dow">';
	for ( $i = 0; $i < 7; $i++ ) {
		$lab = isset( $weekday_labels[ $i ] ) ? (string) $weekday_labels[ $i ] : '';
		$html .= '<div class="clanspress-event-calendar__dow-cell">' . esc_html( $lab ) . '</div>';
	}
	$html .= '</div><div class="clanspress-event-calendar__grid">';

	$cursor = clone $start;
	for ( $w = 0; $w < 6; $w++ ) {
		for ( $c = 0; $c < 7; $c++ ) {
			$ymd      = $cursor->format( 'Y-m-d' );
			$in_month = (int) $cursor->format( 'n' ) === (int) $anchor->format( 'n' );
			$is_today = ( $ymd === $today_ymd );

			$cell_cls = 'clanspress-event-calendar__cell';
			if ( ! $in_month ) {
				$cell_cls .= ' is-muted';
			}
			if ( $is_today ) {
				$cell_cls .= ' is-today';
			}

			$day_num = (int) $cursor->format( 'j' );
			$html   .= '<div class="' . esc_attr( $cell_cls ) . '"><div class="clanspress-event-calendar__cell-num">' . esc_html( (string) $day_num ) . '</div><ul class="clanspress-event-calendar__cell-events"></ul></div>';
			$cursor  = $cursor->modify( '+1 day' );
		}
	}

	$html .= '</div></div>';

	return $html;
}
