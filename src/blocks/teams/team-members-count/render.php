<?php
/**
 * Render callback: team member count.
 *
 * @package clanspress
 */

$team_id = clanspress_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-team-members-count clanspress-team-members-count--placeholder',
		)
	);
	echo '<div ' . $wrapper . '><span>' . esc_html__( 'Members', 'clanspress' ) . '</span></div>';
	return;
}

$count = function_exists( 'clanspress_team_get_member_count' )
	? clanspress_team_get_member_count( $team_id )
	: 0;

$label = isset( $attributes['label'] ) ? (string) $attributes['label'] : '';
if ( '' === $label ) {
	$label = __( 'Members', 'clanspress' );
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-team-members-count',
	)
);

$inner = '<span class="clanspress-team-members-count__label">' . esc_html( $label ) . '</span> '
	. '<span class="clanspress-team-members-count__value">' . esc_html( (string) (int) $count ) . '</span>';

echo '<div ' . $wrapper_attributes . '>' . $inner . '</div>';
