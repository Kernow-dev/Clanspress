<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Server render: Event RSVP block.
 *
 * @package clanspress
 */

use Kernowdev\Clanspress\Events\Events;

$event_type = sanitize_key( (string) ( $attributes['eventType'] ?? 'match' ) );
$event_id   = (int) ( $attributes['eventId'] ?? 0 );
$show_attendees = ! empty( $attributes['showAttendees'] );

if ( $event_id < 1 && isset( $block->context['postId'] ) ) {
	$pt = (string) ( $block->context['postType'] ?? '' );
	if ( 'match' === $event_type && 'cp_match' === $pt ) {
		$event_id = (int) $block->context['postId'];
	} elseif ( 'group' === $event_type && 'cp_group' === $pt ) {
		$event_id = (int) $block->context['postId'];
	} elseif ( 'clanspress_event' === $event_type && 'cp_event' === $pt ) {
		$event_id = (int) $block->context['postId'];
	}
}

if ( $event_id < 1 && 'clanspress_event' === $event_type ) {
	$tid = (int) get_query_var( 'clanspress_team_event_id' );
	$gid = (int) get_query_var( 'cp_group_event_id' );
	$event_id = $tid > 0 ? $tid : $gid;
}

$viewer_id = is_user_logged_in() ? (int) get_current_user_id() : 0;

if ( $event_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-event-rsvp clanspress-event-rsvp--placeholder',
		)
	);
	echo '<div ' . $wrapper . '><p>' . esc_html__( 'Select an event or place this block on a match or group template.', 'clanspress' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$can_view = Events::viewer_can_see_event( $event_type, $event_id, $viewer_id );

if ( ! $can_view ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-event-rsvp clanspress-event-rsvp--forbidden',
		)
	);
	echo '<div ' . $wrapper . '><p>' . esc_html__( 'This event is not available.', 'clanspress' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$config = array(
	'eventType'      => $event_type,
	'eventId'        => $event_id,
	'showAttendees'  => (bool) $show_attendees,
	'restUrl'        => esc_url_raw( rest_url( 'clanspress/v1' ) ),
	'nonce'          => wp_create_nonce( 'wp_rest' ),
	'loggedIn'       => is_user_logged_in(),
	'loginUrl'       => wp_login_url( get_permalink() ),
	'canView'        => true,
	'i18n'           => array(
		'currentPrefix'  => __( 'Your response: ', 'clanspress' ),
		'logInToRsvp'    => __( 'Log in to respond', 'clanspress' ),
		'noAttendees'    => __( 'No responses yet.', 'clanspress' ),
		'attendeesHidden' => __( 'Attendee list is hidden.', 'clanspress' ),
		'statusLabels'   => array(
			'accepted'  => __( 'Accepted', 'clanspress' ),
			'declined'  => __( 'Declined', 'clanspress' ),
			'tentative' => __( 'Tentative', 'clanspress' ),
		),
		'buttonLabels' => array(
			'accepted'  => __( 'Accepted', 'clanspress' ),
			'declined'  => __( 'Declined', 'clanspress' ),
			'tentative' => __( 'Tentative', 'clanspress' ),
		),
	),
);

$wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-event-rsvp',
	)
);
?>
<div
	<?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes. ?>
	data-wp-interactive="clanspress-event-rsvp"
	data-wp-context="<?php echo esc_attr( wp_json_encode( $config ) ); ?>"
	data-wp-init="callbacks.init"
>
	<div class="clanspress-event-rsvp__actions">
		<?php if ( is_user_logged_in() ) : ?>
			<div class="clanspress-event-rsvp__buttons" role="group" aria-label="<?php esc_attr_e( 'RSVP', 'clanspress' ); ?>">
				<button type="button" class="clanspress-event-rsvp__btn" data-cp-rsvp-status="accepted" data-wp-on--click="actions.postRsvp"><?php echo esc_html( $config['i18n']['buttonLabels']['accepted'] ); ?></button>
				<button type="button" class="clanspress-event-rsvp__btn" data-cp-rsvp-status="tentative" data-wp-on--click="actions.postRsvp"><?php echo esc_html( $config['i18n']['buttonLabels']['tentative'] ); ?></button>
				<button type="button" class="clanspress-event-rsvp__btn" data-cp-rsvp-status="declined" data-wp-on--click="actions.postRsvp"><?php echo esc_html( $config['i18n']['buttonLabels']['declined'] ); ?></button>
			</div>
		<?php endif; ?>
		<p class="clanspress-event-rsvp__status" aria-live="polite">
			<?php if ( ! is_user_logged_in() ) : ?>
				<a href="<?php echo esc_url( $config['loginUrl'] ); ?>"><?php echo esc_html( $config['i18n']['logInToRsvp'] ); ?></a>
			<?php endif; ?>
		</p>
	</div>
	<?php if ( $show_attendees ) : ?>
		<div class="clanspress-event-rsvp__attendees">
			<div class="clanspress-event-rsvp__attendees-head">
				<button type="button" class="clanspress-event-rsvp__toggle" data-wp-on--click="actions.toggleAttendees" data-wp-bind--aria-expanded="state.attendeesOpen()">
					<span class="clanspress-event-rsvp__toggle-text" data-wp-bind--hidden="!state.attendeesOpen()"><?php esc_html_e( 'Hide responses', 'clanspress' ); ?></span>
					<span class="clanspress-event-rsvp__toggle-text" data-wp-bind--hidden="state.attendeesOpen()"><?php esc_html_e( 'Show responses', 'clanspress' ); ?></span>
				</button>
				<h3 class="clanspress-event-rsvp__attendees-heading"><?php esc_html_e( 'Responses', 'clanspress' ); ?></h3>
			</div>
			<div class="clanspress-event-rsvp__attendees-body" data-wp-bind--hidden="!state.attendeesOpen()">
				<p class="clanspress-event-rsvp__attendees-note" hidden></p>
				<ul class="clanspress-event-rsvp__list"></ul>
			</div>
		</div>
	<?php endif; ?>
</div>
