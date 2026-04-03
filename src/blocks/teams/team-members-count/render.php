<?php
/**
 * Render callback: team member count.
 *
 * @package clanspress
 */

$team_id = clanspress_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	$prefix_raw  = isset( $attributes['prefix'] ) ? (string) $attributes['prefix'] : '';
	$postfix_raw = isset( $attributes['postfix'] ) ? (string) $attributes['postfix'] : '';
	$label       = isset( $attributes['label'] ) ? (string) $attributes['label'] : '';

	// Backward compatibility: pre-existing blocks used `label` as the prefix text.
	if ( '' === trim( wp_strip_all_tags( $prefix_raw ) ) && '' !== trim( wp_strip_all_tags( $label ) ) ) {
		$prefix_raw = $label;
	}

	$prefix_plain  = trim( wp_strip_all_tags( $prefix_raw ) );
	$postfix_plain = trim( wp_strip_all_tags( $postfix_raw ) );

	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-team-members-count clanspress-team-members-count--placeholder',
		),
		$block
	);

	$parts = array();
	if ( '' !== $prefix_plain ) {
		$parts[] = '<span class="clanspress-team-members-count__prefix">' . wp_kses_post( $prefix_raw ) . '</span>';
	}
	$parts[] = '<span class="clanspress-team-members-count__value">0</span>';
	if ( '' !== $postfix_plain ) {
		$parts[] = '<span class="clanspress-team-members-count__postfix">' . wp_kses_post( $postfix_raw ) . '</span>';
	}

	echo '<div ' . $wrapper . '>' . implode( '', $parts ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$count = function_exists( 'clanspress_team_get_member_count' )
	? clanspress_team_get_member_count( $team_id )
	: 0;

$prefix_raw  = isset( $attributes['prefix'] ) ? (string) $attributes['prefix'] : '';
$postfix_raw = isset( $attributes['postfix'] ) ? (string) $attributes['postfix'] : '';
$label       = isset( $attributes['label'] ) ? (string) $attributes['label'] : '';

if ( '' === trim( wp_strip_all_tags( $prefix_raw ) ) && '' !== trim( wp_strip_all_tags( $label ) ) ) {
	$prefix_raw = $label;
}

$prefix_plain  = trim( wp_strip_all_tags( $prefix_raw ) );
$postfix_plain = trim( wp_strip_all_tags( $postfix_raw ) );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-team-members-count',
	),
	$block
);

$parts = array();
if ( '' !== $prefix_plain ) {
	$parts[] = '<span class="clanspress-team-members-count__prefix">' . wp_kses_post( $prefix_raw ) . '</span>';
}
$parts[] = '<span class="clanspress-team-members-count__value">' . esc_html( (string) (int) $count ) . '</span>';
if ( '' !== $postfix_plain ) {
	$parts[] = '<span class="clanspress-team-members-count__postfix">' . wp_kses_post( $postfix_raw ) . '</span>';
}

echo '<div ' . $wrapper_attributes . '>' . implode( '', $parts ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
