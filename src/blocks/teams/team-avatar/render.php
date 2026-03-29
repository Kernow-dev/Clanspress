<?php
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
	echo '<div ' . $wrapper . '><span>' . esc_html__( 'Team avatar', 'clanspress' ) . '</span></div>';
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
	echo '<div ' . $wrapper . '><span>' . esc_html__( 'Team avatar', 'clanspress' ) . '</span></div>';
	return;
}

$width = isset( $attributes['width'] ) ? (int) $attributes['width'] : 120;
$width = min( 512, max( 32, $width ) );

$style = sprintf( 'width:%dpx;height:%dpx;', $width, $width );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-team-avatar',
		'style' => $style,
	)
);

$alt = sprintf(
	/* translators: %s: team name */
	__( 'Avatar for %s', 'clanspress' ),
	get_the_title( $team_id )
);

echo sprintf(
	'<div %s><img src="%s" alt="%s" width="%d" height="%d" loading="lazy" decoding="async" /></div>',
	$wrapper_attributes,
	esc_url( $url ),
	esc_attr( $alt ),
	(int) $width,
	(int) $width
);
