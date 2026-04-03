<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Render callback: team code.
 *
 * @package clanspress
 */

$team_id = clanspress_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-team-code clanspress-team-code--placeholder',
		)
	);
	echo '<div ' . $wrapper . '><span>' . esc_html__( 'Team code', 'clanspress' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$code = (string) get_post_meta( $team_id, 'cp_team_code', true );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-team-code' . ( '' === $code ? ' clanspress-team-code--empty' : '' ),
	)
);

$display = '' === $code ? __( '—', 'clanspress' ) : $code;

echo '<div ' . $wrapper_attributes . '><span class="clanspress-team-code__value">' . esc_html( $display ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
