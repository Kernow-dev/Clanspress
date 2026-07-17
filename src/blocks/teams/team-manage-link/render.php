<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Render callback: “Manage team” link (only when the viewer may manage the team).
 *
 * @package clanbite
 */

$team_id = clanbite_team_single_block_team_id( $block );

if ( $team_id < 1 || ! function_exists( 'clanbite_teams_user_can_manage' ) || ! clanbite_teams_user_can_manage( $team_id ) ) {
	return;
}

$url = function_exists( 'clanbite_teams_get_team_manage_url' ) ? clanbite_teams_get_team_manage_url( $team_id ) : '';
if ( '' === $url ) {
	return;
}

$label = isset( $attributes['label'] ) ? trim( (string) $attributes['label'] ) : '';
if ( '' === $label ) {
	$label = __( 'Manage team', 'clanbite' );
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-team-manage-link',
	),
	$block
);

echo wp_kses( '<div ' . $wrapper_attributes . '><div class="wp-block-button clanbite-button clanbite-button--manage"><a class="wp-block-button__link wp-element-button clanbite-button__element" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></div></div>', clanbite_block_fragment_allowed_html());
