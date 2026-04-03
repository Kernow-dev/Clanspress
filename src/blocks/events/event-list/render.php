<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Server render: Event list block (Interactivity: time filter, pagination).
 *
 * @package clanspress
 */

use Kernowdev\Clanspress\Events\Event_Post_Type;

$scope    = sanitize_key( (string) ( $attributes['scopeType'] ?? 'team' ) );
$team_id  = (int) ( $attributes['teamId'] ?? 0 );
$group_id = (int) ( $attributes['groupId'] ?? 0 );
$limit    = max( 1, min( 50, (int) ( $attributes['limit'] ?? 20 ) ) );

if ( $team_id < 1 ) {
	$team_id = (int) get_query_var( 'clanspress_events_team_id' );
}
if ( $group_id < 1 ) {
	$group_id = (int) get_query_var( 'clanspress_events_group_id' );
}

if ( 'team' === $scope && $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'clanspress-event-list clanspress-event-list--placeholder' ) );
	echo '<div ' . $wrapper . '><p>' . esc_html__( 'No team context for events.', 'clanspress' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

if ( 'group' === $scope && $group_id < 1 ) {
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'clanspress-event-list clanspress-event-list--placeholder' ) );
	echo '<div ' . $wrapper . '><p>' . esc_html__( 'No group context for events.', 'clanspress' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

if ( 'team' === $scope && function_exists( 'clanspress_events_are_enabled_for_team' ) && ! clanspress_events_are_enabled_for_team( $team_id ) ) {
	return '';
}

if ( 'group' === $scope && function_exists( 'clanspress_events_are_enabled_for_group' ) && ! clanspress_events_are_enabled_for_group( $group_id ) ) {
	return '';
}

$config = array(
	'scope'    => 'team' === $scope ? Event_Post_Type::SCOPE_TEAM : Event_Post_Type::SCOPE_GROUP,
	'teamId'   => $team_id,
	'groupId'  => $group_id,
	'restUrl'  => esc_url_raw( rest_url( 'clanspress/v1/event-posts' ) ),
	'nonce'    => wp_create_nonce( 'wp_rest' ),
	'perPage'  => $limit,
	'i18n'     => array(
		'timeAll'       => __( 'All', 'clanspress' ),
		'timeUpcoming'  => __( 'Upcoming', 'clanspress' ),
		'timePast'      => __( 'Past', 'clanspress' ),
		'loading'       => __( 'Loading…', 'clanspress' ),
		'noEvents'      => __( 'No events yet.', 'clanspress' ),
		'prev'          => __( 'Previous', 'clanspress' ),
		'next'          => __( 'Next', 'clanspress' ),
		'pageLabel'     => __( 'Page', 'clanspress' ),
		'error'         => __( 'Could not load events.', 'clanspress' ),
		'untitled'      => __( '(Untitled)', 'clanspress' ),
	),
);

$wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-event-list-wrap clanspress-event-list-wrap--interactive',
	)
);
?>
<div
	<?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes. ?>
	data-wp-interactive="clanspress-event-list"
	data-wp-context="<?php echo esc_attr( wp_json_encode( $config ) ); ?>"
	data-wp-init="callbacks.init"
>
	<div class="clanspress-event-list__toolbar">
		<label class="screen-reader-text" for="clanspress-event-list-time"><?php esc_html_e( 'Filter by time', 'clanspress' ); ?></label>
		<select id="clanspress-event-list-time" class="clanspress-event-list__time" data-wp-on--change="actions.onTimeScopeChange">
			<option value="all"><?php echo esc_html( $config['i18n']['timeAll'] ); ?></option>
			<option value="upcoming"><?php echo esc_html( $config['i18n']['timeUpcoming'] ); ?></option>
			<option value="past"><?php echo esc_html( $config['i18n']['timePast'] ); ?></option>
		</select>
	</div>
	<p class="clanspress-event-list__loading" data-wp-bind--hidden="!state.isLoading()" aria-live="polite"><?php echo esc_html( $config['i18n']['loading'] ); ?></p>
	<p class="clanspress-event-list__error" data-wp-bind--hidden="!state.errorMessage" data-wp-text="state.errorMessage" role="alert"></p>
	<ul class="clanspress-event-list"></ul>
	<nav class="clanspress-event-list__pagination" data-wp-bind--hidden="!state.showPagination()" aria-label="<?php esc_attr_e( 'Events pagination', 'clanspress' ); ?>">
		<button type="button" class="clanspress-event-list__page-btn" data-wp-on--click="actions.prevPage" data-wp-bind--disabled="state.isFirstPage()">
			<?php echo esc_html( $config['i18n']['prev'] ); ?>
		</button>
		<span class="clanspress-event-list__page-status">
			<span data-wp-text="state.pageLabel"></span>
		</span>
		<button type="button" class="clanspress-event-list__page-btn" data-wp-on--click="actions.nextPage" data-wp-bind--disabled="state.isLastPage()">
			<?php echo esc_html( $config['i18n']['next'] ); ?>
		</button>
	</nav>
</div>
