<?php
/**
 * Render callback: “Manage team” link (only when the viewer may manage the team).
 *
 * @package clanspress
 */

$team_id = clanspress_team_single_block_team_id( $block );

if ( $team_id < 1 || ! function_exists( 'clanspress_teams_user_can_manage' ) || ! clanspress_teams_user_can_manage( $team_id ) ) {
	return;
}

$url = function_exists( 'clanspress_teams_get_team_manage_url' ) ? clanspress_teams_get_team_manage_url( $team_id ) : '';
if ( '' === $url ) {
	return;
}

$label = isset( $attributes['label'] ) ? trim( (string) $attributes['label'] ) : '';
if ( '' === $label ) {
	$label = __( 'Manage team', 'clanspress' );
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-team-manage-link',
	),
	$block
);

echo '<div ' . $wrapper_attributes . '><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
