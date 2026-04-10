<?php

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Renders the player public website when set.
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
			'class' => 'clanspress-player-website clanspress-player-website--placeholder',
		),
		$block
	);
	echo '<div ' . $wrapper . '><p class="clanspress-player-website__text">' . esc_html__( 'Player website', 'clanspress' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$raw = function_exists( 'clanspress_players_get_display_website' )
	? trim( (string) clanspress_players_get_display_website( $user_id ) )
	: '';

if ( '' === $raw ) {
	return '';
}

$align = isset( $attributes['textAlign'] ) ? sanitize_key( (string) $attributes['textAlign'] ) : '';
$p_class = array( 'clanspress-player-website__text' );
if ( $align && in_array( $align, array( 'left', 'center', 'right', 'justify' ), true ) ) {
	$p_class[] = 'has-text-align-' . $align;
}

$candidate = preg_match( '#^https?://#i', $raw ) ? $raw : 'https://' . $raw;
$href_raw  = esc_url( $candidate );
$href      = ( '' !== $href_raw && wp_http_validate_url( $href_raw ) ) ? $href_raw : '';

$inner = esc_html( $raw );

if ( ! empty( $attributes['isLink'] ) && '' !== $href && function_exists( 'clanspress_block_entity_link_url' ) ) {
	$href_filtered = clanspress_block_entity_link_url( $href, 'clanspress/player-website', $user_id, $block );
	if ( '' !== $href_filtered ) {
		$target = ( isset( $attributes['linkTarget'] ) && '_blank' === $attributes['linkTarget'] ) ? ' target="_blank"' : '';
		$rel    = function_exists( 'clanspress_block_entity_link_rel' ) ? clanspress_block_entity_link_rel( $attributes ) : '';
		$rel_at = '' !== $rel ? ' rel="' . esc_attr( $rel ) . '"' : '';
		$inner  = '<a class="clanspress-player-website__link" href="' . esc_url( $href_filtered ) . '"' . $target . $rel_at . '>' . $inner . '</a>';
	}
}

$wrapper_attributes = get_block_wrapper_attributes( array(), $block );

printf(
	'<div %1$s><p class="%2$s">%3$s</p></div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	esc_attr( implode( ' ', $p_class ) ),
	$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $inner built with esc_html/esc_url.
);
