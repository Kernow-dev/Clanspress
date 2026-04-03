<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Renders the player display name for profile routes.
 *
 * @package clanspress
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

$user_id = function_exists( 'clanspress_player_blocks_resolve_subject_user_id' )
	? (int) clanspress_player_blocks_resolve_subject_user_id( $block )
	: 0;

if ( $user_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-player-display-name clanspress-player-display-name--placeholder',
		),
		$block
	);
	echo '<div ' . $wrapper . '><p class="clanspress-player-display-name__text">' . esc_html__( 'Player name', 'clanspress' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$title = function_exists( 'clanspress_players_get_display_name' )
	? (string) clanspress_players_get_display_name( $user_id )
	: '';

if ( '' === $title ) {
	$u = get_userdata( $user_id );
	$title = ( $u instanceof WP_User ) ? $u->display_name : __( 'Player', 'clanspress' );
}

$align = isset( $attributes['textAlign'] ) ? sanitize_key( (string) $attributes['textAlign'] ) : '';
$class = array( 'clanspress-player-display-name__text' );
if ( $align && in_array( $align, array( 'left', 'center', 'right', 'justify' ), true ) ) {
	$class[] = 'has-text-align-' . $align;
}

$inner = esc_html( $title );

if ( ! empty( $attributes['isLink'] ) && function_exists( 'clanspress_block_player_profile_url' ) ) {
	$href = clanspress_block_entity_link_url(
		clanspress_block_player_profile_url( $user_id ),
		'clanspress/player-display-name',
		$user_id,
		$block
	);
	if ( '' !== $href ) {
		$target = ( isset( $attributes['linkTarget'] ) && '_blank' === $attributes['linkTarget'] ) ? ' target="_blank"' : '';
		$rel    = function_exists( 'clanspress_block_entity_link_rel' ) ? clanspress_block_entity_link_rel( $attributes ) : '';
		$rel_at = '' !== $rel ? ' rel="' . esc_attr( $rel ) . '"' : '';
		$inner  = '<a class="clanspress-player-display-name__link" href="' . esc_url( $href ) . '"' . $target . $rel_at . '>' . $inner . '</a>';
	}
}

$wrapper_attributes = get_block_wrapper_attributes( array(), $block );

printf(
	'<div %1$s><p class="%2$s">%3$s</p></div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	esc_attr( implode( ' ', $class ) ),
	$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $inner built with esc_html and esc_url.
);
