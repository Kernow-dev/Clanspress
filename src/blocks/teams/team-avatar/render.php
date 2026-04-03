<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Render callback: team avatar image.
 *
 * WordPress loads this via `ob_start(); require; return ob_get_clean();` — output must be echoed, not returned.
 *
 * @package clanspress
 */

$team_id = clanspress_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-team-avatar clanspress-team-avatar--placeholder',
		)
	);
	echo '<div ' . $wrapper . '><span>' . esc_html__( 'Team avatar', 'clanspress' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$avatar_id = (int) get_post_meta( $team_id, 'cp_team_avatar_id', true );
$url       = $avatar_id ? wp_get_attachment_image_url( $avatar_id, 'medium' ) : '';
if ( ! $url && function_exists( 'clanspress_teams_get_default_avatar_url' ) ) {
	$url = clanspress_teams_get_default_avatar_url( $team_id );
}
$url = trim( (string) $url );
if ( ! $url && function_exists( 'clanspress' ) ) {
	$url = clanspress()->url . 'assets/img/avatars/default-avatar.png';
}
if ( ! $url ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-team-avatar clanspress-team-avatar--placeholder',
		)
	);
	echo '<div ' . $wrapper . '><span>' . esc_html__( 'Team avatar', 'clanspress' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$width = isset( $attributes['width'] ) ? (int) $attributes['width'] : 120;
$width = min( 512, max( 32, $width ) );

$style = sprintf( 'width:%dpx;height:%dpx;', $width, $width );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-team-avatar',
		'style' => $style,
	),
	$block
);

$alt = sprintf(
	/* translators: %s: team name */
	__( 'Avatar for %s', 'clanspress' ),
	get_the_title( $team_id )
);

$img = sprintf(
	'<img class="clanspress-team-avatar__img" src="%1$s" alt="%2$s" width="%3$d" height="%3$d" loading="lazy" decoding="async" />',
	esc_url( $url ),
	esc_attr( $alt ),
	(int) $width
);

if ( ! empty( $attributes['isLink'] ) && function_exists( 'clanspress_block_entity_link_url' ) ) {
	$href = clanspress_block_entity_link_url(
		(string) get_permalink( $team_id ),
		'clanspress/team-avatar',
		$team_id,
		$block
	);
	if ( '' !== $href ) {
		$target = ( isset( $attributes['linkTarget'] ) && '_blank' === $attributes['linkTarget'] ) ? ' target="_blank"' : '';
		$rel    = function_exists( 'clanspress_block_entity_link_rel' ) ? clanspress_block_entity_link_rel( $attributes ) : '';
		$rel_at = '' !== $rel ? ' rel="' . esc_attr( $rel ) . '"' : '';
		$img    = '<a class="clanspress-team-avatar__link" href="' . esc_url( $href ) . '"' . $target . $rel_at . '>' . $img . '</a>';
	}
}

printf(
	'<div %1$s>%2$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	$img // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_url/esc_attr.
);
