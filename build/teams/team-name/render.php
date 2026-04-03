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
	echo '<div ' . $wrapper . '><span>' . esc_html__( 'Team name', 'clanspress' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
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

$inner = esc_html( $title );

if ( ! empty( $attributes['isLink'] ) && function_exists( 'clanspress_block_entity_link_url' ) ) {
	$href = clanspress_block_entity_link_url(
		(string) get_permalink( $team_id ),
		'clanspress/team-name',
		$team_id,
		$block
	);
	if ( '' !== $href ) {
		$target = ( isset( $attributes['linkTarget'] ) && '_blank' === $attributes['linkTarget'] ) ? ' target="_blank"' : '';
		$rel    = function_exists( 'clanspress_block_entity_link_rel' ) ? clanspress_block_entity_link_rel( $attributes ) : '';
		$rel_at = '' !== $rel ? ' rel="' . esc_attr( $rel ) . '"' : '';
		$inner  = '<a class="clanspress-team-name__link" href="' . esc_url( $href ) . '"' . $target . $rel_at . '>' . $inner . '</a>';
	}
}

$wrapper_attributes = get_block_wrapper_attributes( array(), $block );

printf(
	'<div %1$s><%2$s class="%3$s">%4$s</%2$s></div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	esc_attr( $tag ),
	esc_attr( implode( ' ', $class ) ),
	$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $inner built with esc_html/esc_url.
);
