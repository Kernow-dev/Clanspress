<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Render callback: team cover image and inner blocks.
 *
 * @package clanspress
 */

$team_id = clanspress_team_single_block_team_id( $block );

$position       = isset( $attributes['contentPosition'] ) ? (string) $attributes['contentPosition'] : 'bottom center';
$position_class = 'is-position-' . str_replace( ' ', '-', strtolower( $position ) );
$min_height_raw = isset( $attributes['minHeight'] ) ? trim( (string) $attributes['minHeight'] ) : '220px';
$min_height     = preg_match( '/^\d+(\.\d+)?(px|em|rem|vh|vw|%)$/', $min_height_raw ) ? $min_height_raw : '220px';

$wrapper_classes = 'clanspress-team-cover clanspress-team-cover-block ' . $position_class;

$cover_placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

$cover_alt = sprintf(
	/* translators: %s: team name */
	__( 'Cover for %s', 'clanspress' ),
	$team_id >= 1 ? get_the_title( $team_id ) : ''
);

if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => $wrapper_classes . ' clanspress-team-cover--placeholder',
			'style' => sprintf( 'min-height:%s;', $min_height ),
		),
		$block
	);
	echo '<div ' . $wrapper . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	echo '<div class="clanspress-team-cover__media clanspress-team-cover__media--placeholder" aria-hidden="true"></div>';
	echo '<div class="team-cover__content-container">';
	echo wp_kses_post( $content );
	echo '</div>';
	echo '</div>';
	return;
}

$cover_id = (int) get_post_meta( $team_id, 'cp_team_cover_id', true );
$url      = $cover_id ? wp_get_attachment_image_url( $cover_id, 'full' ) : '';
if ( ! $url ) {
	$url = clanspress_teams_get_default_cover_url( $team_id );
}
$url = trim( (string) $url );

if ( ! $url ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => $wrapper_classes . ' clanspress-team-cover--placeholder',
			'style' => sprintf( 'min-height:%s;', $min_height ),
		),
		$block
	);
	echo '<div ' . $wrapper . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	echo '<img class="clanspress-team-cover__media clanspress-team-cover__media--empty" src="' . esc_url( $cover_placeholder ) . '" alt="' . esc_attr( $cover_alt ) . '" loading="lazy" decoding="async" />';
	echo '<div class="team-cover__content-container">';
	echo wp_kses_post( $content );
	echo '</div>';
	echo '</div>';
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => $wrapper_classes,
		'style' => sprintf( 'min-height:%s;', $min_height ),
	),
	$block
);

echo '<div ' . $wrapper_attributes . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
echo '<img class="clanspress-team-cover__media" src="' . esc_url( $url ) . '" alt="' . esc_attr( $cover_alt ) . '" loading="lazy" decoding="async" />';
echo '<div class="team-cover__content-container">';
echo wp_kses_post( $content );
echo '</div>';
echo '</div>';
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
