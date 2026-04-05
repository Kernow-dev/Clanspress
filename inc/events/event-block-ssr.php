<?php
/**
 * Server-side first paint for event list and calendar blocks (matches REST list semantics).
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Run the same collection query as `GET clanspress/v1/event-posts` for block SSR.
 *
 * @param array<string, mixed> $params Request params: team_id, group_id, player_user_id, page, per_page, time_scope, order, starts_after, starts_before, search.
 * @return array{items: array<int, array<string, mixed>>, total: int}|WP_Error
 */
function clanspress_events_block_query_collection( array $params ) {
	if ( ! class_exists( \Kernowdev\Clanspress\Events\Event_Entity_Rest_Controller::class ) ) {
		return new \WP_Error(
			'clanspress_events_rest_unavailable',
			__( 'The events REST API is not available.', 'clanspress' ),
			array( 'status' => 503 )
		);
	}

	$request = new \WP_REST_Request( 'GET', '/clanspress/v1/event-posts' );
	foreach ( $params as $key => $value ) {
		$request->set_param( $key, $value );
	}

	$controller = new \Kernowdev\Clanspress\Events\Event_Entity_Rest_Controller();
	$response   = $controller->get_items( $request );

	if ( $response instanceof WP_Error ) {
		return $response;
	}

	if ( ! $response instanceof \WP_REST_Response ) {
		return array(
			'items' => array(),
			'total' => 0,
		);
	}

	$data  = $response->get_data();
	$items = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();

	return array(
		'items' => $items,
		'total' => (int) ( $data['total'] ?? 0 ),
	);
}

/**
 * UTC ISO-8601 bounds for the calendar range (matches client `rangeForView` / `fetchEvents` in `event-calendar/view.js`).
 *
 * List view uses the same anchored calendar month as month view (prev/next shift months) so SSR, hydration, and client refetch stay aligned.
 *
 * @param string $view       month|week|day|list.
 * @param string $anchor_ymd Y-m-d in site timezone.
 * @return array{starts_after: string, starts_before: string}
 */
function clanspress_events_calendar_range_iso_for_view( string $view, string $anchor_ymd ): array {
	$tz = wp_timezone();

	try {
		$anchor = new \DateTimeImmutable( $anchor_ymd . ' 12:00:00', $tz );
	} catch ( \Exception $e ) {
		$anchor = new \DateTimeImmutable( 'now', $tz );
	}

	$view = sanitize_key( $view );
	if ( 'day' === $view ) {
		$start = $anchor->setTime( 0, 0, 0 );
		$end   = $anchor->setTime( 23, 59, 59 );
	} elseif ( 'week' === $view ) {
		$dow   = (int) $anchor->format( 'w' );
		$start = $anchor->modify( '-' . $dow . ' days' )->setTime( 0, 0, 0 );
		$end   = $start->modify( '+6 days' )->setTime( 23, 59, 59 );
	} else {
		// month + list: full anchored month (see docblock; must match `rangeForView` in event-calendar view).
		$start = $anchor->modify( 'first day of this month' )->setTime( 0, 0, 0 );
		$end   = $anchor->modify( 'last day of this month' )->setTime( 23, 59, 59 );
	}

	$utc    = new \DateTimeZone( 'UTC' );
	$start_u = $start->setTimezone( $utc );
	$end_u   = $end->setTimezone( $utc );

	return array(
		'starts_after'  => $start_u->format( 'Y-m-d\TH:i:s.000\Z' ),
		'starts_before' => $end_u->format( 'Y-m-d\TH:i:s.000\Z' ),
	);
}

/**
 * Visible calendar title for the heading element (site-locale, matches client roughly).
 *
 * @param string $view       month|week|day|list.
 * @param string $anchor_ymd Y-m-d.
 * @return string
 */
function clanspress_events_calendar_heading_for_view( string $view, string $anchor_ymd ): string {
	$tz = wp_timezone();

	try {
		$anchor = new \DateTimeImmutable( $anchor_ymd . ' 12:00:00', $tz );
	} catch ( \Exception $e ) {
		$anchor = new \DateTimeImmutable( 'now', $tz );
	}

	$view = sanitize_key( $view );
	if ( 'month' === $view || 'list' === $view ) {
		return wp_date( 'F Y', $anchor->getTimestamp() );
	}
	if ( 'week' === $view ) {
		$dow = (int) $anchor->format( 'w' );
		$s   = $anchor->modify( '-' . $dow . ' days' );
		$e   = $s->modify( '+6 days' );

		return wp_date( 'M j', $s->getTimestamp() ) . ' – ' . wp_date( 'M j, Y', $e->getTimestamp() );
	}
	if ( 'day' === $view ) {
		return wp_date( 'l, F j, Y', $anchor->getTimestamp() );
	}

	return '';
}

/**
 * @param string               $starts_at Event starts_at meta (MySQL / ISO fragment).
 * @return \DateTimeImmutable|null UTC.
 */
function clanspress_events_parse_starts_at_utc( string $starts_at ): ?\DateTimeImmutable {
	$starts_at = trim( $starts_at );
	if ( '' === $starts_at ) {
		return null;
	}
	try {
		return new \DateTimeImmutable( str_replace( ' ', 'T', $starts_at ) . 'Z' );
	} catch ( \Exception $e ) {
		return null;
	}
}

/**
 * Y-m-d range for an event in the site timezone (for calendar cell overlap).
 *
 * @param array<string, mixed> $ev REST item.
 * @return array{startYmd: string, endYmd: string, ev: array<string, mixed>}|null
 */
function clanspress_events_event_ymd_range_site( array $ev ): ?array {
	$s = clanspress_events_parse_starts_at_utc( (string) ( $ev['startsAt'] ?? '' ) );
	if ( ! $s ) {
		return null;
	}

	$tz       = wp_timezone();
	$s_local  = $s->setTimezone( $tz );
	$start_ymd = $s_local->format( 'Y-m-d' );

	$ends_raw = isset( $ev['endsAt'] ) ? (string) $ev['endsAt'] : '';
	$end_ymd  = $start_ymd;
	if ( '' !== trim( $ends_raw ) ) {
		try {
			$e       = new \DateTimeImmutable( str_replace( ' ', 'T', $ends_raw ) . 'Z' );
			$e_local = $e->setTimezone( $tz );
			$end_ymd = $e_local->format( 'Y-m-d' );
		} catch ( \Exception $e ) {
			$end_ymd = $start_ymd;
		}
	}
	if ( $end_ymd < $start_ymd ) {
		$end_ymd = $start_ymd;
	}

	return array(
		'startYmd' => $start_ymd,
		'endYmd'   => $end_ymd,
		'ev'       => $ev,
	);
}

/**
 * @param array<int, array<string, mixed>> $items Event REST rows.
 * @param string                           $ymd   Cell date Y-m-d (site TZ).
 * @return array<int, array{startYmd: string, endYmd: string, ev: array<string, mixed>}>
 */
function clanspress_events_touching_ymd( array $items, string $ymd ): array {
	$out = array();
	foreach ( $items as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$r = clanspress_events_event_ymd_range_site( $row );
		if ( ! $r || $ymd < $r['startYmd'] || $ymd > $r['endYmd'] ) {
			continue;
		}
		$out[] = $r;
	}

	usort(
		$out,
		static function ( array $a, array $b ): int {
			$ta = clanspress_events_parse_starts_at_utc( (string) ( $a['ev']['startsAt'] ?? '' ) );
			$tb = clanspress_events_parse_starts_at_utc( (string) ( $b['ev']['startsAt'] ?? '' ) );
			$ua = $ta ? $ta->getTimestamp() : 0;
			$ub = $tb ? $tb->getTimestamp() : 0;

			return $ua <=> $ub;
		}
	);

	return $out;
}

/**
 * @param array{startYmd: string, endYmd: string, ev: array<string, mixed>} $range Range row.
 * @param string                                                            $ymd   Cell Y-m-d.
 * @return string start|end|mid|single
 */
function clanspress_events_segment_kind( array $range, string $ymd ): string {
	if ( $range['startYmd'] === $range['endYmd'] ) {
		return 'single';
	}
	if ( $ymd === $range['startYmd'] ) {
		return 'start';
	}
	if ( $ymd === $range['endYmd'] ) {
		return 'end';
	}

	return 'mid';
}

/**
 * Time label for list / calendar (site timezone).
 *
 * @param string $starts_at Raw startsAt.
 * @return string
 */
function clanspress_events_format_time_for_calendar( string $starts_at ): string {
	$dt = clanspress_events_parse_starts_at_utc( $starts_at );
	if ( ! $dt ) {
		return '';
	}

	return wp_date( get_option( 'time_format' ), $dt->getTimestamp() );
}

/**
 * @param array<int, array{startYmd: string, endYmd: string, ev: array<string, mixed>}> $ranges .
 * @param string                                                                        $cell_ymd .
 * @param array<string, string>                                                         $i18n     untitled.
 * @param int                                                                           $max_items Max segments in cell.
 * @return string HTML fragment (cells inside ul).
 */
function clanspress_events_render_month_cell_events_html( array $ranges, string $cell_ymd, array $i18n, int $max_items = 3 ): string {
	$untitled = isset( $i18n['untitled'] ) ? (string) $i18n['untitled'] : '';
	$html     = '';
	$n        = 0;

	foreach ( $ranges as $r ) {
		if ( $n >= $max_items ) {
			break;
		}
		$kind = clanspress_events_segment_kind( $r, $cell_ymd );
		$ev   = $r['ev'];
		$t    = isset( $ev['title'] ) && is_string( $ev['title'] ) && '' !== $ev['title'] ? $ev['title'] : $untitled;
		$u    = isset( $ev['permalink'] ) ? (string) $ev['permalink'] : '';
		$st   = isset( $ev['startsAt'] ) ? (string) $ev['startsAt'] : '';
		$time = ( 'start' === $kind || 'single' === $kind ) ? clanspress_events_format_time_for_calendar( $st ) : '';

		if ( 'mid' === $kind || 'end' === $kind ) {
			$html .= '<li class="clanspress-event-calendar__ev-seg clanspress-event-calendar__ev-seg--span" title="' . esc_attr( $t ) . '"><span class="clanspress-event-calendar__ev-bar" aria-hidden="true"></span><span class="clanspress-event-calendar__sr-only">' . esc_html( $t ) . '</span></li>';
		} elseif ( '' !== $u ) {
			$html .= '<li class="clanspress-event-calendar__ev-seg"><a href="' . esc_url( $u ) . '"><span class="clanspress-event-calendar__ev-time">' . esc_html( $time ) . '</span> ' . esc_html( $t ) . '</a></li>';
		} else {
			$html .= '<li class="clanspress-event-calendar__ev-seg"><span class="clanspress-event-calendar__ev-time">' . esc_html( $time ) . '</span> ' . esc_html( $t ) . '</li>';
		}
		++$n;
	}

	$extra = count( $ranges ) - $max_items;
	if ( $extra > 0 ) {
		$html .= '<li class="clanspress-event-calendar__more">+' . esc_html( (string) $extra ) . '</li>';
	}

	return $html;
}

/**
 * Full month surface inner HTML (matches client `renderSurface` month branch).
 *
 * @param string                              $anchor_ymd     Y-m-d in month.
 * @param array<int, array<string, mixed>>    $items          REST rows.
 * @param array<int, string>                  $weekday_labels Seven labels, Sunday first.
 * @param string                              $today_ymd      Site today.
 * @param array<string, string>               $i18n           untitled, noEvents.
 * @return string
 */
function clanspress_event_calendar_render_month_surface_html( string $anchor_ymd, array $items, array $weekday_labels, string $today_ymd, array $i18n ): string {
	$tz = wp_timezone();

	try {
		$anchor = new \DateTimeImmutable( $anchor_ymd . ' 12:00:00', $tz );
	} catch ( \Exception $e ) {
		$anchor = new \DateTimeImmutable( 'now', $tz );
	}

	$first_of_month = $anchor->modify( 'first day of this month' )->setTime( 0, 0, 0 );
	$dow            = (int) $first_of_month->format( 'w' );
	$start_grid     = $first_of_month->modify( '-' . $dow . ' days' );

	$html  = '<div class="clanspress-event-calendar__month">';
	$html .= '<div class="clanspress-event-calendar__dow">';
	for ( $i = 0; $i < 7; $i++ ) {
		$lab = isset( $weekday_labels[ $i ] ) ? (string) $weekday_labels[ $i ] : '';
		$html .= '<div class="clanspress-event-calendar__dow-cell">' . esc_html( $lab ) . '</div>';
	}
	$html .= '</div><div class="clanspress-event-calendar__grid">';

	$cursor = clone $start_grid;
	for ( $w = 0; $w < 6; $w++ ) {
		for ( $c = 0; $c < 7; $c++ ) {
			$ymd       = $cursor->format( 'Y-m-d' );
			$in_month  = (int) $cursor->format( 'n' ) === (int) $anchor->format( 'n' );
			$is_today  = ( $ymd === $today_ymd );
			$ranges    = clanspress_events_touching_ymd( $items, $ymd );
			$cell_cls  = 'clanspress-event-calendar__cell';
			if ( ! $in_month ) {
				$cell_cls .= ' is-muted';
			}
			if ( $is_today ) {
				$cell_cls .= ' is-today';
			}
			$day_num = (int) $cursor->format( 'j' );
			$html   .= '<div class="' . esc_attr( $cell_cls ) . '"><div class="clanspress-event-calendar__cell-num">' . esc_html( (string) $day_num ) . '</div><ul class="clanspress-event-calendar__cell-events">';
			$html   .= clanspress_events_render_month_cell_events_html( $ranges, $ymd, $i18n, 3 );
			$html   .= '</ul></div>';
			$cursor  = $cursor->modify( '+1 day' );
		}
	}

	$html .= '</div></div>';

	return $html;
}

/**
 * @param array<int, array<string, mixed>> $items .
 * @param array<string, string>          $i18n  untitled, noEvents.
 * @return string
 */
function clanspress_event_calendar_render_week_surface_html( string $anchor_ymd, array $items, array $i18n ): string {
	$tz = wp_timezone();

	try {
		$anchor = new \DateTimeImmutable( $anchor_ymd . ' 12:00:00', $tz );
	} catch ( \Exception $e ) {
		$anchor = new \DateTimeImmutable( 'now', $tz );
	}

	$dow   = (int) $anchor->format( 'w' );
	$start = $anchor->modify( '-' . $dow . ' days' )->setTime( 0, 0, 0 );

	$no_events = isset( $i18n['noEvents'] ) ? (string) $i18n['noEvents'] : '';
	$untitled  = isset( $i18n['untitled'] ) ? (string) $i18n['untitled'] : '';

	$html = '<div class="clanspress-event-calendar__week">';
	for ( $i = 0; $i < 7; $i++ ) {
		$day = $start->modify( '+' . $i . ' days' );
		$ymd = $day->format( 'Y-m-d' );
		$html .= '<div class="clanspress-event-calendar__week-day">';
		$html .= '<div class="clanspress-event-calendar__week-day-head">' . esc_html( wp_date( 'D, M j', $day->getTimestamp() ) ) . '</div><ul>';
		$ranges = clanspress_events_touching_ymd( $items, $ymd );
		if ( array() === $ranges ) {
			$html .= '<li class="clanspress-event-calendar__empty">' . esc_html( $no_events ) . '</li>';
		} else {
			foreach ( $ranges as $r ) {
				$ev      = $r['ev'];
				$kind    = clanspress_events_segment_kind( $r, $ymd );
				$t       = isset( $ev['title'] ) && '' !== (string) $ev['title'] ? (string) $ev['title'] : $untitled;
				$u       = isset( $ev['permalink'] ) ? (string) $ev['permalink'] : '';
				$st      = isset( $ev['startsAt'] ) ? (string) $ev['startsAt'] : '';
				$time_lbl = ( 'start' === $kind || 'single' === $kind ) ? clanspress_events_format_time_for_calendar( $st ) : '···';
				if ( '' !== $u ) {
					$html .= '<li><a href="' . esc_url( $u ) . '">' . esc_html( $time_lbl ) . ' — ' . esc_html( $t ) . '</a></li>';
				} else {
					$html .= '<li>' . esc_html( $time_lbl ) . ' — ' . esc_html( $t ) . '</li>';
				}
			}
		}
		$html .= '</ul></div>';
	}
	$html .= '</div>';

	return $html;
}

/**
 * @param array<int, array<string, mixed>> $items .
 * @param array<string, string>          $i18n  untitled, noEvents.
 * @return string
 */
function clanspress_event_calendar_render_day_surface_html( string $anchor_ymd, array $items, array $i18n ): string {
	$no_events = isset( $i18n['noEvents'] ) ? (string) $i18n['noEvents'] : '';
	$untitled  = isset( $i18n['untitled'] ) ? (string) $i18n['untitled'] : '';

	$ranges = clanspress_events_touching_ymd( $items, $anchor_ymd );
	$html   = '<div class="clanspress-event-calendar__day"><ul>';
	if ( array() === $ranges ) {
		$html .= '<li class="clanspress-event-calendar__empty">' . esc_html( $no_events ) . '</li>';
	} else {
		foreach ( $ranges as $r ) {
			$ev       = $r['ev'];
			$kind     = clanspress_events_segment_kind( $r, $anchor_ymd );
			$t        = isset( $ev['title'] ) && '' !== (string) $ev['title'] ? (string) $ev['title'] : $untitled;
			$u        = isset( $ev['permalink'] ) ? (string) $ev['permalink'] : '';
			$st       = isset( $ev['startsAt'] ) ? (string) $ev['startsAt'] : '';
			$time_lbl = ( 'start' === $kind || 'single' === $kind ) ? clanspress_events_format_time_for_calendar( $st ) : '···';
			if ( '' !== $u ) {
				$html .= '<li><a href="' . esc_url( $u ) . '">' . esc_html( $time_lbl ) . ' — ' . esc_html( $t ) . '</a></li>';
			} else {
				$html .= '<li>' . esc_html( $time_lbl ) . ' — ' . esc_html( $t ) . '</li>';
			}
		}
	}
	$html .= '</ul></div>';

	return $html;
}

/**
 * @param array<int, array<string, mixed>> $items .
 * @param array<string, string>          $i18n  untitled, noEvents.
 * @return string
 */
function clanspress_event_calendar_render_list_surface_html( array $items, array $i18n ): string {
	$no_events = isset( $i18n['noEvents'] ) ? (string) $i18n['noEvents'] : '';
	$untitled  = isset( $i18n['untitled'] ) ? (string) $i18n['untitled'] : '';

	$sorted = array();
	foreach ( $items as $row ) {
		if ( is_array( $row ) && ! empty( $row['startsAt'] ) ) {
			$sorted[] = $row;
		}
	}

	usort(
		$sorted,
		static function ( array $a, array $b ): int {
			$ta = clanspress_events_parse_starts_at_utc( (string) ( $a['startsAt'] ?? '' ) );
			$tb = clanspress_events_parse_starts_at_utc( (string) ( $b['startsAt'] ?? '' ) );
			$ua = $ta ? $ta->getTimestamp() : 0;
			$ub = $tb ? $tb->getTimestamp() : 0;

			return $ua <=> $ub;
		}
	);

	$html = '<div class="clanspress-event-calendar__list-view"><ul class="clanspress-event-calendar__list">';
	if ( array() === $sorted ) {
		$html .= '<li class="clanspress-event-calendar__empty">' . esc_html( $no_events ) . '</li>';
	} else {
		foreach ( $sorted as $ev ) {
			$t = isset( $ev['title'] ) && '' !== (string) $ev['title'] ? (string) $ev['title'] : $untitled;
			$u = isset( $ev['permalink'] ) ? (string) $ev['permalink'] : '';
			$s = clanspress_events_parse_starts_at_utc( (string) ( $ev['startsAt'] ?? '' ) );
			$e = clanspress_events_parse_starts_at_utc( (string) ( $ev['endsAt'] ?? '' ) );

			$when = '';
			if ( $s ) {
				$when = wp_date(
					get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
					$s->getTimestamp()
				);
			}
			if ( $e && $s && $e->getTimestamp() > $s->getTimestamp() ) {
				$s_ymd = $s->setTimezone( wp_timezone() )->format( 'Y-m-d' );
				$e_ymd = $e->setTimezone( wp_timezone() )->format( 'Y-m-d' );
				if ( $s_ymd !== $e_ymd ) {
					$when .= ' – ' . wp_date(
						get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
						$e->getTimestamp()
					);
				}
			}

			$html .= '<li class="clanspress-event-calendar__list-item">';
			$html .= '<span class="clanspress-event-calendar__list-when">' . esc_html( $when ) . '</span>';
			if ( '' !== $u ) {
				$html .= '<a class="clanspress-event-calendar__list-title" href="' . esc_url( $u ) . '">' . esc_html( $t ) . '</a>';
			} else {
				$html .= '<span class="clanspress-event-calendar__list-title">' . esc_html( $t ) . '</span>';
			}
			$html .= '</li>';
		}
	}
	$html .= '</ul></div>';

	return $html;
}

/**
 * Inner HTML for `.clanspress-event-calendar__surface`.
 *
 * @param string                           $view       month|week|day|list.
 * @param string                           $anchor_ymd Y-m-d.
 * @param array<int, array<string, mixed>> $items      REST rows.
 * @param array<int, string>               $weekdays   Weekday labels (month only).
 * @param string                           $today_ymd  Site today.
 * @param array<string, string>            $i18n       Block i18n subset.
 * @return string
 */
function clanspress_event_calendar_render_surface_html( string $view, string $anchor_ymd, array $items, array $weekdays, string $today_ymd, array $i18n ): string {
	$view = sanitize_key( $view );
	if ( 'month' === $view ) {
		return clanspress_event_calendar_render_month_surface_html( $anchor_ymd, $items, $weekdays, $today_ymd, $i18n );
	}
	if ( 'week' === $view ) {
		return clanspress_event_calendar_render_week_surface_html( $anchor_ymd, $items, $i18n );
	}
	if ( 'day' === $view ) {
		return clanspress_event_calendar_render_day_surface_html( $anchor_ymd, $items, $i18n );
	}
	if ( 'list' === $view ) {
		return clanspress_event_calendar_render_list_surface_html( $items, $i18n );
	}

	return '';
}

/**
 * Format `startsAt` for the event list block (matches client list row).
 *
 * @param string $starts_at Raw meta / REST value.
 * @return string
 */
function clanspress_events_format_list_row_starts_at( string $starts_at ): string {
	$dt = clanspress_events_parse_starts_at_utc( $starts_at );
	if ( ! $dt ) {
		return '';
	}

	return wp_date(
		get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
		$dt->getTimestamp()
	);
}

/**
 * Build `<li>` rows for the event list `<ul>`.
 *
 * @param array<int, array<string, mixed>> $items REST rows.
 * @param array<string, string>          $i18n  untitled, noEvents.
 * @return string HTML (no `<ul>` wrapper).
 */
function clanspress_events_render_event_list_rows_html( array $items, array $i18n ): string {
	$untitled  = isset( $i18n['untitled'] ) ? (string) $i18n['untitled'] : '';
	$no_events = isset( $i18n['noEvents'] ) ? (string) $i18n['noEvents'] : '';

	if ( array() === $items ) {
		return '<li class="clanspress-event-list__empty">' . esc_html( $no_events ) . '</li>';
	}

	$html = '';
	foreach ( $items as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$title = isset( $row['title'] ) && is_string( $row['title'] ) && '' !== $row['title'] ? $row['title'] : $untitled;
		$url   = isset( $row['permalink'] ) ? (string) $row['permalink'] : '';
		$label = isset( $row['startsAt'] ) ? clanspress_events_format_list_row_starts_at( (string) $row['startsAt'] ) : '';

		$html .= '<li class="clanspress-event-list__item">';
		if ( '' !== $url ) {
			$html .= '<a class="clanspress-event-list__title" href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>';
		} else {
			$html .= '<span class="clanspress-event-list__title">' . esc_html( $title ) . '</span>';
		}
		if ( '' !== $label ) {
			$html .= '<p class="clanspress-event-list__meta">' . esc_html( $label ) . '</p>';
		}
		$html .= '</li>';
	}

	return $html;
}
