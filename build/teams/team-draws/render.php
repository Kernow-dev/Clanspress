<?php
/**
 * Render callback: team draws count.
 *
 * @package clanspress
 */

$team_id = clanspress_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-team-stat clanspress-team-stat--draws clanspress-team-stat--placeholder',
		),
		$block
	);
	echo '<div ' . $wrapper . '><span>' . esc_html__( 'Draws', 'clanspress' ) . '</span></div>';
	return;
}

$val = (int) get_post_meta( $team_id, 'cp_team_draws', true );
$val = max( 0, $val );

$prefix = isset( $attributes['prefix'] ) ? trim( (string) $attributes['prefix'] ) : '';
if ( '' === $prefix ) {
	$prefix = __( 'Draws', 'clanspress' );
}

$postfix = isset( $attributes['postfix'] ) ? (string) $attributes['postfix'] : '';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-team-stat clanspress-team-stat--draws',
	),
	$block
);

echo '<div ' . $wrapper_attributes . '>';
echo '<span class="clanspress-team-stat__prefix">' . esc_html( $prefix ) . '</span>';
if ( '' !== $prefix ) {
	echo ' ';
}
echo '<span class="clanspress-team-stat__value">' . esc_html( (string) $val ) . '</span>';
if ( '' !== trim( $postfix ) ) {
	echo ' ';
	echo '<span class="clanspress-team-stat__postfix">' . esc_html( $postfix ) . '</span>';
}
echo '</div>';
