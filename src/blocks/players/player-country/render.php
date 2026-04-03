<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Render callback: player country.
 *
 * @package clanspress
 */

$user_id = function_exists( 'clanspress_player_blocks_resolve_subject_user_id' )
	? (int) clanspress_player_blocks_resolve_subject_user_id( $block )
	: 0;

if ( $user_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-player-country clanspress-country-display clanspress-country-display--placeholder',
		)
	);
	echo '<div ' . $wrapper . '><span class="clanspress-country-display__label">' . esc_html__( 'Player country', 'clanspress' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$code = (string) get_user_meta( $user_id, 'cp_player_country', true );

$label = '' !== $code && function_exists( 'clanspress_team_country_label' )
	? clanspress_team_country_label( $code )
	: '';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-player-country clanspress-country-display' . ( '' === $code ? ' clanspress-country-display--empty' : '' ),
	),
	$block
);

if ( ! function_exists( 'clanspress_country_block_inner_html' ) ) {
	if ( '' === $code ) {
		$inner = esc_html__( '—', 'clanspress' );
	} else {
		$inner = esc_html( $label );
	}
	echo '<div ' . $wrapper_attributes . '><span class="clanspress-country-display__label">' . $inner . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $wrapper_attributes from get_block_wrapper_attributes(); $inner escaped.
	return;
}

if ( '' === $code ) {
	$inner = '<span class="clanspress-country-display__label">' . esc_html__( '—', 'clanspress' ) . '</span>';
} else {
	$inner = clanspress_country_block_inner_html(
		$attributes,
		$code,
		$label,
		'player',
		'clanspress/player-country',
		$block
	);
}

if ( '' === $inner ) {
	$inner = '<span class="clanspress-country-display__label">' . esc_html__( '—', 'clanspress' ) . '</span>';
}

echo '<div ' . $wrapper_attributes . '>' . $inner . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $wrapper_attributes from get_block_wrapper_attributes(); $inner built via esc_html / filters.
