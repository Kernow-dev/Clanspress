<?php
/**
 * Render callback: team motto.
 *
 * @package clanspress
 */

$team_id = clanspress_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-team-motto clanspress-team-motto--placeholder',
		)
	);
	echo '<div ' . $wrapper . '><span>' . esc_html__( 'Team motto', 'clanspress' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$motto = (string) get_post_meta( $team_id, 'cp_team_motto', true );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-team-motto' . ( '' === $motto ? ' clanspress-team-motto--empty' : '' ),
	)
);

$display = '' === $motto ? __( 'No motto set.', 'clanspress' ) : $motto;

echo '<div ' . $wrapper_attributes . '><p class="clanspress-team-motto__text">' . esc_html( $display ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
