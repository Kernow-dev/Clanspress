<?php
/**
 * Render callback: team name (title).
 *
 * @package clanspress
 */

$team_id = clanspress_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-team-name clanspress-team-name--placeholder',
		)
	);
	echo '<div ' . $wrapper . '><span>' . esc_html__( 'Team name', 'clanspress' ) . '</span></div>';
	return;
}

$title = get_the_title( $team_id );
if ( '' === $title ) {
	$title = __( 'Untitled team', 'clanspress' );
}

$level = isset( $attributes['level'] ) ? (int) $attributes['level'] : 1;
$level = min( 6, max( 1, $level ) );
$tag   = 'h' . $level;

$align = isset( $attributes['textAlign'] ) ? sanitize_key( (string) $attributes['textAlign'] ) : '';
$class = array( 'clanspress-team-name__heading' );
if ( $align && in_array( $align, array( 'left', 'center', 'right', 'justify' ), true ) ) {
	$class[] = 'has-text-align-' . $align;
}

$wrapper_attributes = get_block_wrapper_attributes();

echo sprintf(
	'<div %s><%s class="%s">%s</%s></div>',
	$wrapper_attributes,
	esc_attr( $tag ),
	esc_attr( implode( ' ', $class ) ),
	esc_html( $title ),
	esc_attr( $tag )
);
