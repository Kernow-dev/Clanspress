<?php

defined( 'ABSPATH' ) || exit;

// Load progress bar and rank icon components
require_once CLANBITE_PATH . 'src/blocks/shared/components/avatar-progress-bar.php';
require_once CLANBITE_PATH . 'src/blocks/shared/components/avatar-rank-icon.php';

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Render callback: team avatar image.
 *
 * WordPress loads this via `ob_start(); require; return ob_get_clean();` — output must be echoed, not returned.
 *
 * @package clanbite
 */

$team_id = clanbite_team_single_block_team_id( $block );

// Get avatar shape from settings
$avatar_shape = 'circle';
if ( function_exists( 'clanbite_teams_get_avatar_shape' ) ) {
	$avatar_shape = clanbite_teams_get_avatar_shape();
}

$avatar_preset = isset( $attributes['avatarPreset'] ) ? sanitize_key( (string) $attributes['avatarPreset'] ) : 'large';
if ( ! in_array( $avatar_preset, array( 'large', 'medium', 'small' ), true ) ) {
	$avatar_preset = 'large';
}

$width = isset( $attributes['width'] ) ? (int) $attributes['width'] : 120;
$width = min( 512, max( 32, $width ) );

$style = sprintf( 'width:%dpx;height:%dpx;', $width, $width );

if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanbite-avatar clanbite-avatar--team clanbite-avatar--placeholder',
			'style' => $style,
		),
		$block
	);
	echo wp_kses( '<div ' . $wrapper . '><span>' . esc_html__( 'Team avatar', 'clanbite' ) . '</span></div>', clanbite_block_fragment_allowed_html());
	return;
}

$url = function_exists( 'clanbite_teams_get_display_team_avatar' )
	? clanbite_teams_get_display_team_avatar( $team_id, false, '', 'team_avatar_block', $avatar_preset )
	: '';
if ( '' === $url ) {
	$url = clanbite_teams_get_bundled_default_avatar_url();
}
$url = trim( (string) $url );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-team-avatar-block clanbite-avatar-block',
		'style' => $style,
	),
	$block
);

$alt = sprintf(
	/* translators: %s: team name */
	__( 'Avatar for %s', 'clanbite' ),
	get_the_title( $team_id )
);

ob_start();
if ( $url ) {
	printf(
		'<img class="clanbite-avatar__img" src="%1$s" alt="%2$s" width="%3$d" height="%3$d" loading="lazy" decoding="async" />',
		esc_url( $url ),
		esc_attr( $alt ),
		(int) $width
	);
} else {
	echo '<span class="clanbite-avatar__img clanbite-avatar__img--placeholder" role="img" aria-label="' . esc_attr( $alt ) . '">' . esc_html__( 'No avatar', 'clanbite' ) . '</span>';
}
$img_inner = ob_get_clean();

if ( ! empty( $attributes['isLink'] ) && function_exists( 'clanbite_block_entity_link_url' ) ) {
	$href = clanbite_block_entity_link_url(
		(string) get_permalink( $team_id ),
		'clanbite/team-avatar',
		$team_id,
		$block
	);
	if ( '' !== $href ) {
		$target = ( isset( $attributes['linkTarget'] ) && '_blank' === $attributes['linkTarget'] ) ? ' target="_blank"' : '';
		$rel    = function_exists( 'clanbite_block_entity_link_rel' ) ? clanbite_block_entity_link_rel( $attributes ) : '';
		$rel_at = '' !== $rel ? ' rel="' . esc_attr( $rel ) . '"' : '';
		$img_inner = '<a class="clanbite-avatar__link" href="' . esc_url( $href ) . '"' . $target . $rel_at . '>' . $img_inner . '</a>';
	}
}

$avatar_clip_open  = '<div class="clanbite-avatar__clip">';
$avatar_clip_close = '</div>';

// Generate progress bar HTML (for teams, use team leader's progress if applicable)
$progress_bar_html = '';
$rank_icon_html = '';
if ( 'large' === $avatar_preset ) {
	// For teams, we could show the team leader's rank progress
	// Get team leader/creator user ID
	$team_post = get_post( $team_id );
	if ( $team_post ) {
		$team_leader_id = (int) $team_post->post_author;
		if ( $team_leader_id > 0 ) {
			$progress_bar_html = clanbite_render_avatar_progress_bar( $team_leader_id, $avatar_shape, $avatar_preset );
			$rank_icon_html = clanbite_render_avatar_rank_icon( $team_leader_id, $avatar_shape );
		}
	}
}

// Build avatar media with progress and rank icon
if ( '' !== $progress_bar_html || '' !== $rank_icon_html ) {
	// Use media wrapper for overlays
	$avatar_media = '<div class="clanbite-avatar__media">';
	$avatar_media .= $avatar_clip_open . $img_inner . $avatar_clip_close;
	$avatar_media .= $progress_bar_html;
	$avatar_media .= $rank_icon_html;
	$avatar_media .= '</div>';
} else {
	$avatar_media = $avatar_clip_open . $img_inner . $avatar_clip_close;
}

// Add shape class to avatar container
$avatar_classes = 'clanbite-avatar clanbite-avatar--team clanbite-avatar--shape-' . $avatar_shape;

echo wp_kses( '<div ' . $wrapper_attributes . '><div class="' . esc_attr( $avatar_classes ) . '">' . $avatar_media . '</div></div>', clanbite_block_fragment_allowed_html());
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
