<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Server render: Create or edit event form (team/group managers).
 *
 * @package clanbite
 */

use Kernowdev\Clanbite\Events\Event_Permissions;
use Kernowdev\Clanbite\Events\Event_Post_Type;

$viewer_id = is_user_logged_in() ? (int) get_current_user_id() : 0;

$edit_event_id = (int) ( $attributes['eventId'] ?? 0 );
$is_edit       = $edit_event_id > 0;

$scope       = sanitize_key( (string) ( $attributes['scopeType'] ?? 'team' ) );
$team_id     = (int) ( $attributes['teamId'] ?? 0 );
$group_id    = (int) ( $attributes['groupId'] ?? 0 );
$id_suffix   = '';
$edit_post   = null;

$pf_title    = '';
$pf_content  = '';
$pf_mode     = Event_Post_Type::MODE_IN_PERSON;
$pf_starts   = '';
$pf_ends     = '';
$pf_vurl     = '';
$pf_line1    = '';
$pf_line2    = '';
$pf_locality = '';
$pf_region   = '';
$pf_postcode = '';
$pf_country  = '';
$pf_vis      = Event_Post_Type::VISIBILITY_PUBLIC;
$pf_attvis   = 'hidden';

if ( $is_edit ) {
	$edit_post = get_post( $edit_event_id );
	if ( ! ( $edit_post instanceof \WP_Post ) || Event_Post_Type::POST_TYPE !== $edit_post->post_type ) {
		return;
	}
	if ( ! Event_Permissions::user_can_manage_event( $edit_event_id, $viewer_id ) ) {
		$wrapper = get_block_wrapper_attributes( array( 'class' => 'clanbite-event-create-form clanbite-event-create-form--locked' ), $block );
		echo wp_kses( '<div ' . $wrapper . '><p>' . esc_html__( 'You cannot edit this event.', 'clanbite' ) . '</p></div>', clanbite_block_fragment_allowed_html());
		return;
	}

	$scope_meta = sanitize_key( (string) get_post_meta( $edit_event_id, 'cp_event_scope', true ) );
	if ( Event_Post_Type::SCOPE_TEAM === $scope_meta ) {
		$scope   = 'team';
		$team_id = (int) get_post_meta( $edit_event_id, 'cp_event_team_id', true );
		$group_id = 0;
		if ( function_exists( 'clanbite_events_are_enabled_for_team' ) && ! clanbite_events_are_enabled_for_team( $team_id ) ) {
			return '';
		}
	} elseif ( Event_Post_Type::SCOPE_GROUP === $scope_meta ) {
		$scope    = 'group';
		$group_id = (int) get_post_meta( $edit_event_id, 'cp_event_group_id', true );
		$team_id  = 0;
		if ( function_exists( 'clanbite_events_are_enabled_for_group' ) && ! clanbite_events_are_enabled_for_group( $group_id ) ) {
			return '';
		}
	} else {
		return '';
	}

	$id_suffix   = '-' . $edit_event_id;
	$pf_title    = get_the_title( $edit_post );
	$pf_content  = (string) $edit_post->post_content;
	$pf_mode     = sanitize_key( (string) get_post_meta( $edit_event_id, 'cp_event_mode', true ) );
	if ( '' === $pf_mode ) {
		$pf_mode = Event_Post_Type::MODE_IN_PERSON;
	}
	$pf_starts = '';
	$raw_start = (string) get_post_meta( $edit_event_id, 'cp_event_starts_at', true );
	if ( '' !== $raw_start ) {
		$ts_start = strtotime( $raw_start . ' UTC' );
		if ( $ts_start ) {
			$pf_starts = wp_date( 'Y-m-d\TH:i', $ts_start );
		}
	}
	$pf_ends = '';
	$raw_end = (string) get_post_meta( $edit_event_id, 'cp_event_ends_at', true );
	if ( '' !== $raw_end ) {
		$ts_end = strtotime( $raw_end . ' UTC' );
		if ( $ts_end ) {
			$pf_ends = wp_date( 'Y-m-d\TH:i', $ts_end );
		}
	}
	$pf_vurl     = (string) get_post_meta( $edit_event_id, 'cp_event_virtual_url', true );
	$pf_line1    = (string) get_post_meta( $edit_event_id, 'cp_event_address_line1', true );
	$pf_line2    = (string) get_post_meta( $edit_event_id, 'cp_event_address_line2', true );
	$pf_locality = (string) get_post_meta( $edit_event_id, 'cp_event_locality', true );
	$pf_region   = (string) get_post_meta( $edit_event_id, 'cp_event_region', true );
	$pf_postcode = (string) get_post_meta( $edit_event_id, 'cp_event_postcode', true );
	$pf_country  = (string) get_post_meta( $edit_event_id, 'cp_event_country', true );
	$pf_vis      = (string) get_post_meta( $edit_event_id, 'cp_event_visibility', true );
	if ( '' === $pf_vis ) {
		$pf_vis = Event_Post_Type::VISIBILITY_PUBLIC;
	}
	$pf_attvis = (string) get_post_meta( $edit_event_id, 'cp_event_attendees_visibility', true );
	if ( '' === $pf_attvis ) {
		$pf_attvis = 'hidden';
	}
} else {
	if ( $team_id < 1 ) {
		$team_id = (int) get_query_var( 'clanbite_events_team_id' );
	}
	if ( $group_id < 1 ) {
		$group_id = (int) get_query_var( 'clanbite_events_group_id' );
	}

	if ( 'team' === $scope && function_exists( 'clanbite_events_are_enabled_for_team' ) && ! clanbite_events_are_enabled_for_team( $team_id ) ) {
		return '';
	}

	if ( 'group' === $scope && function_exists( 'clanbite_events_are_enabled_for_group' ) && ! clanbite_events_are_enabled_for_group( $group_id ) ) {
		return '';
	}

	$can = false;
	if ( 'team' === $scope && $team_id > 0 && function_exists( 'clanbite_teams_user_can_manage' ) ) {
		$can = clanbite_teams_user_can_manage( $team_id, $viewer_id );
	} elseif ( 'group' === $scope && $group_id > 0 && function_exists( 'clanbite_groups_user_can_manage' ) ) {
		$can = clanbite_groups_user_can_manage( $group_id, $viewer_id );
	}

	if ( ! $can ) {
		$wrapper = get_block_wrapper_attributes( array( 'class' => 'clanbite-event-create-form clanbite-event-create-form--locked' ), $block );
		echo wp_kses( '<div ' . $wrapper . '><p>' . esc_html__( 'Only team or group managers can create events.', 'clanbite' ) . '</p></div>', clanbite_block_fragment_allowed_html());
		return;
	}
}

$rest_url = $is_edit
	? rest_url( 'clanbite/v1/event-posts/' . $edit_event_id )
	: rest_url( 'clanbite/v1/event-posts' );

$config = array(
	'scope'     => 'team' === $scope ? Event_Post_Type::SCOPE_TEAM : Event_Post_Type::SCOPE_GROUP,
	'teamId'    => $team_id,
	'groupId'   => $group_id,
	'eventId'   => $is_edit ? $edit_event_id : 0,
	'restUrl'   => esc_url_raw( $rest_url ),
	'nonce'     => wp_create_nonce( 'wp_rest' ),
	'stepCount' => 4,
	'i18n'      => array(
		'next'          => __( 'Next', 'clanbite' ),
		'submit'        => $is_edit ? __( 'Save changes', 'clanbite' ) : __( 'Create event', 'clanbite' ),
		'success'       => $is_edit ? __( 'Event updated.', 'clanbite' ) : __( 'Event created.', 'clanbite' ),
		'error'         => $is_edit ? __( 'Could not update event.', 'clanbite' ) : __( 'Could not create event.', 'clanbite' ),
		'titleRequired' => __( 'Title required.', 'clanbite' ),
		'viewEvent'     => __( 'View event', 'clanbite' ),
		'createAnother' => __( 'Create another', 'clanbite' ),
		'stepsLabel'    => $is_edit ? __( 'Edit event steps', 'clanbite' ) : __( 'Create event steps', 'clanbite' ),
		'memberOutreachLabel'    => __( 'Roster outreach', 'clanbite' ),
		'memberOutreachHelpTeam' => __( 'Optionally notify everyone on this team (in-app) or add them as tentative attendees so they can confirm in RSVP.', 'clanbite' ),
		'memberOutreachHelpGroup' => __( 'Recipients come from integrations that filter clanbite_group_event_member_user_ids. Without that, outreach has no members to target.', 'clanbite' ),
		'memberOutreachNone'     => __( 'None', 'clanbite' ),
		'memberOutreachNotify'  => __( 'Notify all roster members', 'clanbite' ),
		'memberOutreachRsvp'    => __( 'Tentative RSVP + notify (full invite)', 'clanbite' ),
	),
);

$wrapper_classes = array( 'clanbite-event-create-form' );
if ( $is_edit ) {
	$wrapper_classes[] = 'clanbite-event-create-form--edit';
}
$wrapper = get_block_wrapper_attributes( array( 'class' => implode( ' ', $wrapper_classes ) ), $block );

$event_create_form_root_open = '<div '
	. trim( (string) $wrapper )
	. ' data-wp-interactive="clanbite-event-create-form"'
	. ' data-wp-context="' . esc_attr( wp_json_encode( $config ) ) . '"'
	. ' data-wp-init="callbacks.init"'
	. '>';
?>
<?php ob_start(); ?>
<?php clanbite_echo_block_fragment_html( $event_create_form_root_open ); ?>
	<?php
	if ( ! $is_edit && 'team' === $scope && 'create' === sanitize_key( (string) get_query_var( 'clanbite_team_events_sub' ) ) && $team_id > 0 && function_exists( 'clanbite_teams_get_team_action_url' ) ) {
		$cp_back_events = clanbite_teams_get_team_action_url( $team_id, 'events' );
		if ( is_string( $cp_back_events ) && '' !== $cp_back_events ) {
			echo '<p class="clanbite-event-create-form__back"><a href="' . esc_url( $cp_back_events ) . '">' . esc_html__( '← Back to events', 'clanbite' ) . '</a></p>';
		}
	}
	?>
	<form class="clanbite-event-create-form__form" data-active-step="1" novalidate data-wp-on--submit="actions.onSubmit" data-wp-bind--hidden="state.showSuccessScreen()">
		<div class="clanbite-event-create-form__tabs" role="tablist" aria-label="<?php echo esc_attr( $config['i18n']['stepsLabel'] ); ?>">
			<button type="button" class="clanbite-event-create-form__tab is-active" role="tab" aria-selected="true" tabindex="0" data-event-tab="1" data-wp-on--click="actions.goToStepTab">
				<span class="clanbite-event-create-form__tab-index" aria-hidden="true">1</span>
				<span class="clanbite-event-create-form__tab-text">
					<span class="clanbite-event-create-form__tab-title"><?php esc_html_e( 'Basics', 'clanbite' ); ?></span>
					<span class="clanbite-event-create-form__tab-description"><?php esc_html_e( 'Name and format', 'clanbite' ); ?></span>
				</span>
			</button>
			<button type="button" class="clanbite-event-create-form__tab is-upcoming" role="tab" aria-selected="false" tabindex="-1" data-event-tab="2" data-wp-on--click="actions.goToStepTab" disabled>
				<span class="clanbite-event-create-form__tab-index" aria-hidden="true">2</span>
				<span class="clanbite-event-create-form__tab-text">
					<span class="clanbite-event-create-form__tab-title"><?php esc_html_e( 'When', 'clanbite' ); ?></span>
					<span class="clanbite-event-create-form__tab-description"><?php esc_html_e( 'Start and end times', 'clanbite' ); ?></span>
				</span>
			</button>
			<button type="button" class="clanbite-event-create-form__tab is-upcoming" role="tab" aria-selected="false" tabindex="-1" data-event-tab="3" data-wp-on--click="actions.goToStepTab" disabled>
				<span class="clanbite-event-create-form__tab-index" aria-hidden="true">3</span>
				<span class="clanbite-event-create-form__tab-text">
					<span class="clanbite-event-create-form__tab-title"><?php esc_html_e( 'Where', 'clanbite' ); ?></span>
					<span class="clanbite-event-create-form__tab-description"><?php esc_html_e( 'Venue or link', 'clanbite' ); ?></span>
				</span>
			</button>
			<button type="button" class="clanbite-event-create-form__tab is-upcoming" role="tab" aria-selected="false" tabindex="-1" data-event-tab="4" data-wp-on--click="actions.goToStepTab" disabled>
				<span class="clanbite-event-create-form__tab-index" aria-hidden="true">4</span>
				<span class="clanbite-event-create-form__tab-text">
					<span class="clanbite-event-create-form__tab-title"><?php esc_html_e( 'Visibility', 'clanbite' ); ?></span>
					<span class="clanbite-event-create-form__tab-description"><?php esc_html_e( 'Who can see it', 'clanbite' ); ?></span>
				</span>
			</button>
		</div>

		<div class="clanbite-event-create-form__step" role="tabpanel" data-event-step="1">
			<p>
				<label for="cp-event-title<?php echo esc_attr( $id_suffix ); ?>"><?php esc_html_e( 'Title', 'clanbite' ); ?></label>
				<input type="text" id="cp-event-title<?php echo esc_attr( $id_suffix ); ?>" name="title" required class="clanbite-event-create-form__input-title" value="<?php echo esc_attr( $pf_title ); ?>" />
			</p>
			<p>
				<label for="cp-event-content<?php echo esc_attr( $id_suffix ); ?>"><?php esc_html_e( 'Description', 'clanbite' ); ?></label>
				<textarea id="cp-event-content<?php echo esc_attr( $id_suffix ); ?>" name="content" rows="4" class="clanbite-event-create-form__input-content"><?php echo esc_textarea( $pf_content ); ?></textarea>
			</p>
			<p>
				<label for="cp-event-mode<?php echo esc_attr( $id_suffix ); ?>"><?php esc_html_e( 'Format', 'clanbite' ); ?></label>
				<select id="cp-event-mode<?php echo esc_attr( $id_suffix ); ?>" name="mode" class="clanbite-event-create-form__input-mode" data-wp-on--change="actions.onModeChange">
					<option value="<?php echo esc_attr( Event_Post_Type::MODE_IN_PERSON ); ?>" <?php selected( $pf_mode, Event_Post_Type::MODE_IN_PERSON ); ?>><?php esc_html_e( 'In person', 'clanbite' ); ?></option>
					<option value="<?php echo esc_attr( Event_Post_Type::MODE_VIRTUAL ); ?>" <?php selected( $pf_mode, Event_Post_Type::MODE_VIRTUAL ); ?>><?php esc_html_e( 'Virtual', 'clanbite' ); ?></option>
				</select>
			</p>
		</div>

		<div class="clanbite-event-create-form__step" role="tabpanel" data-event-step="2" hidden>
			<p>
				<label for="cp-event-starts<?php echo esc_attr( $id_suffix ); ?>"><?php esc_html_e( 'Starts (local time)', 'clanbite' ); ?></label>
				<input type="datetime-local" id="cp-event-starts<?php echo esc_attr( $id_suffix ); ?>" name="starts_at_local" class="clanbite-event-create-form__input-starts" value="<?php echo esc_attr( $pf_starts ); ?>" />
			</p>
			<p>
				<label for="cp-event-ends<?php echo esc_attr( $id_suffix ); ?>"><?php esc_html_e( 'Ends (local time)', 'clanbite' ); ?></label>
				<input type="datetime-local" id="cp-event-ends<?php echo esc_attr( $id_suffix ); ?>" name="ends_at_local" class="clanbite-event-create-form__input-ends" value="<?php echo esc_attr( $pf_ends ); ?>" />
			</p>
		</div>

		<div class="clanbite-event-create-form__step" role="tabpanel" data-event-step="3" hidden>
			<p class="clanbite-event-create-form__field clanbite-event-create-form__field--virtual" hidden>
				<label for="cp-event-vurl<?php echo esc_attr( $id_suffix ); ?>"><?php esc_html_e( 'Virtual URL', 'clanbite' ); ?></label>
				<input type="url" id="cp-event-vurl<?php echo esc_attr( $id_suffix ); ?>" name="virtual_url" class="clanbite-event-create-form__input-vurl" value="<?php echo esc_attr( $pf_vurl ); ?>" />
			</p>
			<p class="clanbite-event-create-form__field clanbite-event-create-form__field--address">
				<label for="cp-event-line1<?php echo esc_attr( $id_suffix ); ?>"><?php esc_html_e( 'Address line 1', 'clanbite' ); ?></label>
				<input type="text" id="cp-event-line1<?php echo esc_attr( $id_suffix ); ?>" name="address_line1" class="clanbite-event-create-form__input-line1" value="<?php echo esc_attr( $pf_line1 ); ?>" />
			</p>
			<p class="clanbite-event-create-form__field clanbite-event-create-form__field--address">
				<label for="cp-event-line2<?php echo esc_attr( $id_suffix ); ?>"><?php esc_html_e( 'Address line 2', 'clanbite' ); ?></label>
				<input type="text" id="cp-event-line2<?php echo esc_attr( $id_suffix ); ?>" name="address_line2" class="clanbite-event-create-form__input-line2" value="<?php echo esc_attr( $pf_line2 ); ?>" />
			</p>
			<div class="clanbite-event-create-form__grid">
				<p class="clanbite-event-create-form__field clanbite-event-create-form__field--address">
					<label for="cp-event-locality<?php echo esc_attr( $id_suffix ); ?>"><?php esc_html_e( 'City / locality', 'clanbite' ); ?></label>
					<input type="text" id="cp-event-locality<?php echo esc_attr( $id_suffix ); ?>" name="locality" class="clanbite-event-create-form__input-locality" value="<?php echo esc_attr( $pf_locality ); ?>" />
				</p>
				<p class="clanbite-event-create-form__field clanbite-event-create-form__field--address">
					<label for="cp-event-region<?php echo esc_attr( $id_suffix ); ?>"><?php esc_html_e( 'Region / state', 'clanbite' ); ?></label>
					<input type="text" id="cp-event-region<?php echo esc_attr( $id_suffix ); ?>" name="region" class="clanbite-event-create-form__input-region" value="<?php echo esc_attr( $pf_region ); ?>" />
				</p>
				<p class="clanbite-event-create-form__field clanbite-event-create-form__field--address">
					<label for="cp-event-postcode<?php echo esc_attr( $id_suffix ); ?>"><?php esc_html_e( 'Postal code', 'clanbite' ); ?></label>
					<input type="text" id="cp-event-postcode<?php echo esc_attr( $id_suffix ); ?>" name="postcode" class="clanbite-event-create-form__input-postcode" value="<?php echo esc_attr( $pf_postcode ); ?>" />
				</p>
				<p class="clanbite-event-create-form__field clanbite-event-create-form__field--address">
					<label for="cp-event-country<?php echo esc_attr( $id_suffix ); ?>"><?php esc_html_e( 'Country', 'clanbite' ); ?></label>
					<input type="text" id="cp-event-country<?php echo esc_attr( $id_suffix ); ?>" name="country" class="clanbite-event-create-form__input-country" value="<?php echo esc_attr( $pf_country ); ?>" />
				</p>
			</div>
		</div>

		<div class="clanbite-event-create-form__step" role="tabpanel" data-event-step="4" hidden>
			<p>
				<label for="cp-event-vis<?php echo esc_attr( $id_suffix ); ?>"><?php esc_html_e( 'Who can see this event', 'clanbite' ); ?></label>
				<select id="cp-event-vis<?php echo esc_attr( $id_suffix ); ?>" name="visibility" class="clanbite-event-create-form__input-vis">
					<option value="public" <?php selected( $pf_vis, 'public' ); ?>><?php esc_html_e( 'Public', 'clanbite' ); ?></option>
					<option value="members" <?php selected( $pf_vis, 'members' ); ?>><?php esc_html_e( 'Logged-in members', 'clanbite' ); ?></option>
					<option value="team_members" <?php selected( $pf_vis, 'team_members' ); ?>><?php esc_html_e( 'Team/group members only', 'clanbite' ); ?></option>
					<option value="team_admins" <?php selected( $pf_vis, 'team_admins' ); ?>><?php esc_html_e( 'Team/group admins only', 'clanbite' ); ?></option>
				</select>
			</p>
			<p>
				<label for="cp-event-attvis<?php echo esc_attr( $id_suffix ); ?>"><?php esc_html_e( 'Attendee list', 'clanbite' ); ?></label>
				<select id="cp-event-attvis<?php echo esc_attr( $id_suffix ); ?>" name="attendees_visibility" class="clanbite-event-create-form__input-attvis">
					<option value="hidden" <?php selected( $pf_attvis, 'hidden' ); ?>><?php esc_html_e( 'Hidden', 'clanbite' ); ?></option>
					<option value="public" <?php selected( $pf_attvis, 'public' ); ?>><?php esc_html_e( 'Public', 'clanbite' ); ?></option>
				</select>
			</p>
			<fieldset class="clanbite-event-create-form__member-outreach">
				<legend class="clanbite-event-create-form__member-outreach-legend"><?php echo esc_html( $config['i18n']['memberOutreachLabel'] ); ?></legend>
				<p class="clanbite-event-create-form__member-outreach-help description">
					<?php echo esc_html( 'team' === $scope ? $config['i18n']['memberOutreachHelpTeam'] : $config['i18n']['memberOutreachHelpGroup'] ); ?>
				</p>
				<p>
					<label for="cp-event-member-outreach<?php echo esc_attr( $id_suffix ); ?>" class="screen-reader-text"><?php echo esc_html( $config['i18n']['memberOutreachLabel'] ); ?></label>
					<select id="cp-event-member-outreach<?php echo esc_attr( $id_suffix ); ?>" name="member_outreach" class="clanbite-event-create-form__input-member-outreach">
						<option value="none"><?php echo esc_html( $config['i18n']['memberOutreachNone'] ); ?></option>
						<option value="notify"><?php echo esc_html( $config['i18n']['memberOutreachNotify'] ); ?></option>
						<option value="rsvp_tentative"><?php echo esc_html( $config['i18n']['memberOutreachRsvp'] ); ?></option>
					</select>
				</p>
			</fieldset>
		</div>

		<div class="clanbite-event-create-form__actions clanbite-event-create-form__actions--split">
			<div class="wp-block-button clanbite-button clanbite-button--secondary">
				<button type="button" class="wp-block-button__link wp-element-button clanbite-button__element" data-wp-on--click="actions.previousStep" data-wp-bind--hidden="!state.canGoBack()" hidden>
					<?php esc_html_e( 'Back', 'clanbite' ); ?>
				</button>
			</div>
			<div class="clanbite-event-create-form__actions-end">
				<div class="wp-block-button clanbite-button clanbite-button--secondary">
					<button type="button" class="wp-block-button__link wp-element-button clanbite-button__element" data-wp-on--click="actions.nextStep" data-wp-bind--hidden="!state.canGoNext()">
						<?php echo esc_html( $config['i18n']['next'] ); ?>
					</button>
				</div>
				<div class="wp-block-button clanbite-button clanbite-button--primary clanbite-button--submit">
					<button type="submit" class="wp-block-button__link wp-element-button clanbite-button__element" data-wp-bind--hidden="state.canGoNext()" hidden>
						<?php echo esc_html( $config['i18n']['submit'] ); ?>
					</button>
				</div>
			</div>
		</div>

		<p class="clanbite-event-create-form__msg" aria-live="polite" data-wp-bind--hidden="!state.hasMessage()" hidden>
			<span data-wp-text="state.message"></span>
		</p>
	</form>

	<div
		class="clanbite-event-create-form__success"
		role="status"
		tabindex="-1"
		hidden
		data-wp-bind--hidden="!state.showSuccessScreen()"
	>
		<p class="clanbite-event-create-form__success-title">
			<?php echo esc_html( $config['i18n']['success'] ); ?>
		</p>
		<p>
			<a
				class="clanbite-event-create-form__success-link"
				data-wp-bind--href="state.successEventUrl"
				data-wp-bind--hidden="!state.hasSuccessEventUrl()"
				hidden
			><?php echo esc_html( $config['i18n']['viewEvent'] ); ?></a>
		</p>
		<div>
			<div class="wp-block-button clanbite-button clanbite-button--secondary">
				<button type="button" class="wp-block-button__link wp-element-button clanbite-button__element" data-wp-on--click="actions.resetAfterSuccess">
					<?php echo esc_html( $config['i18n']['createAnother'] ); ?>
				</button>
			</div>
		</div>
	</div>
</div>
<?php echo wp_kses( (string) ob_get_clean(), clanbite_block_fragment_allowed_html()); ?>
