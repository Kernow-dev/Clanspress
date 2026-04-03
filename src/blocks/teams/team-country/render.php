<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Render callback: team country.
 *
 * @package clanspress
 */

$team_id = clanspress_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-team-country clanspress-country-display clanspress-country-display--placeholder',
		),
		$block
	);
	echo '<div ' . $wrapper . '><span class="clanspress-country-display__label">' . esc_html__( 'Team country', 'clanspress' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$code = (string) get_post_meta( $team_id, 'cp_team_country', true );

$label = '' !== $code && function_exists( 'clanspress_team_country_label' )
	? clanspress_team_country_label( $code )
	: '';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-team-country clanspress-country-display' . ( '' === $code ? ' clanspress-country-display--empty' : '' ),
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
		'team',
		'clanspress/team-country',
		$block
	);
}

if ( '' === $inner ) {
	$inner = '<span class="clanspress-country-display__label">' . esc_html__( '—', 'clanspress' ) . '</span>';
}

echo '<div ' . $wrapper_attributes . '>' . $inner . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $wrapper_attributes from get_block_wrapper_attributes(); $inner built via esc_html / filters.
