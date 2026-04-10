<?php

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Renders the player birthday when set.
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
			'class' => 'clanspress-player-birthday clanspress-player-birthday--placeholder',
		),
		$block
	);
	echo '<div ' . $wrapper . '><p class="clanspress-player-birthday__text">' . esc_html__( 'Player birthday', 'clanspress' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$birthday = function_exists( 'clanspress_players_get_display_birthday' )
	? trim( (string) clanspress_players_get_display_birthday( $user_id ) )
	: '';

if ( '' === $birthday ) {
	return '';
}

$align = isset( $attributes['textAlign'] ) ? sanitize_key( (string) $attributes['textAlign'] ) : '';
$class = array( 'clanspress-player-birthday__text' );
if ( $align && in_array( $align, array( 'left', 'center', 'right', 'justify' ), true ) ) {
	$class[] = 'has-text-align-' . $align;
}

$wrapper_attributes = get_block_wrapper_attributes( array(), $block );

printf(
	'<div %1$s><p class="%2$s">%3$s</p></div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	esc_attr( implode( ' ', $class ) ),
	esc_html( $birthday )
);
