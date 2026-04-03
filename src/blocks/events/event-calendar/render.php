<?php
/**
 * Server render: Event calendar (Interactivity: month / week / day, REST range queries).
 *
 * @package clanspress
 */

$scope          = sanitize_key( (string) ( $attributes['scopeType'] ?? 'team' ) );
$team_id        = (int) ( $attributes['teamId'] ?? 0 );
$group_id       = (int) ( $attributes['groupId'] ?? 0 );
$player_user_id = (int) ( $attributes['playerUserId'] ?? 0 );
$default_view   = sanitize_key( (string) ( $attributes['defaultView'] ?? 'month' ) );

$resolved_player_id = 0;

if ( ! in_array( $default_view, array( 'month', 'week', 'day', 'list' ), true ) ) {
	$default_view = 'month';
}

if ( $team_id < 1 ) {
	$team_id = (int) get_query_var( 'clanspress_events_team_id' );
}
if ( $group_id < 1 ) {
	$group_id = (int) get_query_var( 'clanspress_events_group_id' );
}

if ( 'player' === $scope ) {
	if ( $player_user_id < 1 && function_exists( 'clanspress_player_profile_context_user_id' ) ) {
		$player_user_id = (int) clanspress_player_profile_context_user_id();
	}

	$resolved_player_id = $player_user_id;

	if ( ! is_user_logged_in() || (int) get_current_user_id() !== $player_user_id || $player_user_id < 1 ) {
		return '';
	}

	if ( ! function_exists( 'clanspress_events_are_globally_enabled' ) || ! clanspress_events_are_globally_enabled() ) {
		return '';
	}

	$scope_api = 'player';
} elseif ( 'team' === $scope ) {
	if ( $team_id < 1 ) {
		$wrapper = get_block_wrapper_attributes( array( 'class' => 'clanspress-event-calendar clanspress-event-calendar--placeholder' ) );
		echo '<div ' . $wrapper . '><p>' . esc_html__( 'No team context for events.', 'clanspress' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
		return;
	}

	if ( function_exists( 'clanspress_events_are_enabled_for_team' ) && ! clanspress_events_are_enabled_for_team( $team_id ) ) {
		return '';
	}

	$scope_api = 'team';
} elseif ( 'group' === $scope ) {
	if ( $group_id < 1 ) {
		$wrapper = get_block_wrapper_attributes( array( 'class' => 'clanspress-event-calendar clanspress-event-calendar--placeholder' ) );
		echo '<div ' . $wrapper . '><p>' . esc_html__( 'No group context for events.', 'clanspress' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
		return;
	}

	if ( function_exists( 'clanspress_events_are_enabled_for_group' ) && ! clanspress_events_are_enabled_for_group( $group_id ) ) {
		return '';
	}

	$scope_api = 'group';
} else {
	return '';
}

$create_url = '';
if ( 'team' === $scope_api && $team_id > 0 && is_user_logged_in() && function_exists( 'clanspress_teams_user_can_manage' ) && clanspress_teams_user_can_manage( $team_id ) && function_exists( 'clanspress_teams_get_team_events_create_url' ) ) {
	$create_url = clanspress_teams_get_team_events_create_url( $team_id );
}
if ( 'group' === $scope_api && $group_id > 0 && is_user_logged_in() && function_exists( 'clanspress_groups_user_can_manage' ) ) {
	$uid = (int) get_current_user_id();
	if ( clanspress_groups_user_can_manage( $group_id, $uid ) ) {
		/**
		 * Filter URL for “add event” from the group event calendar (extension-defined).
		 *
		 * @param string $url      URL or empty.
		 * @param int    $group_id Group post ID.
		 */
		$create_url = (string) apply_filters( 'clanspress_group_events_create_url', '', $group_id );
	}
}

/**
 * Filter calendar “add event” link (team/group).
 *
 * @param string $create_url URL or empty.
 * @param string $scope_api  `team`, `group`, or `player`.
 * @param int    $team_id    Team ID when applicable.
 * @param int    $group_id   Group ID when applicable.
 */
$create_url = (string) apply_filters( 'clanspress_event_calendar_create_url', $create_url, $scope_api, $team_id, $group_id );

$today_ymd = wp_date( 'Y-m-d' );

$config = array(
	'scope'       => $scope_api,
	'teamId'      => $team_id,
	'groupId'     => $group_id,
	'playerId'    => 'player' === $scope_api ? $resolved_player_id : 0,
	'view'        => $default_view,
	'restUrl'     => esc_url_raw( rest_url( 'clanspress/v1/event-posts' ) ),
	'nonce'       => wp_create_nonce( 'wp_rest' ),
	'anchor'      => wp_date( 'Y-m-d' ),
	'createUrl'   => $create_url ? esc_url_raw( $create_url ) : '',
	'i18n'        => array(
		'loading'     => __( 'Loading…', 'clanspress' ),
		'error'       => __( 'Could not load events.', 'clanspress' ),
		'noEvents'    => __( 'No events in this range.', 'clanspress' ),
		'month'       => __( 'Month', 'clanspress' ),
		'week'        => __( 'Week', 'clanspress' ),
		'day'         => __( 'Day', 'clanspress' ),
		'list'        => __( 'List', 'clanspress' ),
		'today'       => __( 'Today', 'clanspress' ),
		'prev'        => __( 'Previous', 'clanspress' ),
		'next'        => __( 'Next', 'clanspress' ),
		'addEvent'    => __( 'Add event', 'clanspress' ),
		'untitled'    => __( '(Untitled)', 'clanspress' ),
		'weekdays'    => array(
			__( 'Sun', 'clanspress' ),
			__( 'Mon', 'clanspress' ),
			__( 'Tue', 'clanspress' ),
			__( 'Wed', 'clanspress' ),
			__( 'Thu', 'clanspress' ),
			__( 'Fri', 'clanspress' ),
			__( 'Sat', 'clanspress' ),
		),
	),
);

$calendar_heading = '';
$calendar_surface = '';
if ( 'month' === $default_view && function_exists( 'clanspress_event_calendar_month_grid_markup' ) ) {
	$anchor_ts       = strtotime( $config['anchor'] . ' 12:00:00' );
	$calendar_heading = $anchor_ts ? wp_date( 'F Y', $anchor_ts ) : '';
	$calendar_surface = clanspress_event_calendar_month_grid_markup(
		$config['anchor'],
		$config['i18n']['weekdays'],
		$today_ymd
	);
}

$wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-event-calendar-wrap clanspress-event-calendar-wrap--interactive',
	)
);
?>
<div
	<?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes. ?>
	data-wp-interactive="clanspress-event-calendar"
	data-wp-context="<?php echo esc_attr( wp_json_encode( $config ) ); ?>"
	data-wp-init="callbacks.init"
>
	<div class="clanspress-event-calendar__toolbar">
		<div class="clanspress-event-calendar__views" role="group" aria-label="<?php esc_attr_e( 'Calendar view', 'clanspress' ); ?>">
			<button type="button" class="clanspress-event-calendar__view-btn" data-cal-view="month" data-wp-on--click="actions.setView"><?php echo esc_html( $config['i18n']['month'] ); ?></button>
			<button type="button" class="clanspress-event-calendar__view-btn" data-cal-view="week" data-wp-on--click="actions.setView"><?php echo esc_html( $config['i18n']['week'] ); ?></button>
			<button type="button" class="clanspress-event-calendar__view-btn" data-cal-view="day" data-wp-on--click="actions.setView"><?php echo esc_html( $config['i18n']['day'] ); ?></button>
			<button type="button" class="clanspress-event-calendar__view-btn" data-cal-view="list" data-wp-on--click="actions.setView"><?php echo esc_html( $config['i18n']['list'] ); ?></button>
		</div>
		<div class="clanspress-event-calendar__nav">
			<button type="button" class="clanspress-event-calendar__nav-btn" data-wp-on--click="actions.prevPeriod" aria-label="<?php echo esc_attr( $config['i18n']['prev'] ); ?>">‹</button>
			<button type="button" class="clanspress-event-calendar__today" data-wp-on--click="actions.goToday"><?php echo esc_html( $config['i18n']['today'] ); ?></button>
			<button type="button" class="clanspress-event-calendar__nav-btn" data-wp-on--click="actions.nextPeriod" aria-label="<?php echo esc_attr( $config['i18n']['next'] ); ?>">›</button>
		</div>
		<?php if ( '' !== $config['createUrl'] ) : ?>
			<div class="wp-block-button">
				<a class="wp-block-button__link wp-element-button clanspress-event-calendar__add" href="<?php echo esc_url( $config['createUrl'] ); ?>"><?php echo esc_html( $config['i18n']['addEvent'] ); ?></a>
			</div>
		<?php endif; ?>
	</div>
	<h2 class="clanspress-event-calendar__heading"><?php echo '' !== $calendar_heading ? esc_html( $calendar_heading ) : ''; ?></h2>
	<p class="clanspress-event-calendar__sr-only" data-wp-bind--hidden="!context.calLoading" aria-live="polite"><?php echo esc_html( $config['i18n']['loading'] ); ?></p>
	<p class="clanspress-event-calendar__error" data-wp-bind--hidden="!context.fetchError" data-wp-text="context.fetchError" role="alert"></p>
	<div class="clanspress-event-calendar__surface"><?php echo $calendar_surface ? $calendar_surface : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup built with esc_html/esc_attr inside helper. ?></div>
</div>
