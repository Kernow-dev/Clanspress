<?php
/**
 * Render callback: team wins count.
 *
 * @package clanspress
 */

$team_id = clanspress_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-team-stat clanspress-team-stat--wins clanspress-team-stat--placeholder',
		),
		$block
	);
	echo '<div ' . $wrapper . '><span>' . esc_html__( 'Wins', 'clanspress' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$val = (int) get_post_meta( $team_id, 'cp_team_wins', true );
$val = max( 0, $val );

$prefix_raw    = isset( $attributes['prefix'] ) ? (string) $attributes['prefix'] : '';
$prefix_plain  = trim( wp_strip_all_tags( $prefix_raw ) );
$postfix_raw   = isset( $attributes['postfix'] ) ? (string) $attributes['postfix'] : '';
$postfix_plain = trim( wp_strip_all_tags( $postfix_raw ) );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-team-stat clanspress-team-stat--wins',
	),
	$block
);

$parts = array();
if ( '' !== $prefix_plain ) {
	$parts[] = '<span class="clanspress-team-stat__prefix">' . wp_kses_post( $prefix_raw ) . '</span>';
}
$parts[] = '<span class="clanspress-team-stat__value">' . esc_html( (string) $val ) . '</span>';
if ( '' !== $postfix_plain ) {
	$parts[] = '<span class="clanspress-team-stat__postfix">' . wp_kses_post( $postfix_raw ) . '</span>';
}

echo '<div ' . $wrapper_attributes . '>' . implode( '', $parts ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
