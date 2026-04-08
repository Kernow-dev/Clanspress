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
			'class' => 'clanspress-team-avatar clanspress-team-avatar--placeholder',
			'style' => $style,
		),
		$block
	);
	echo '<div ' . $wrapper . '><span>' . esc_html__( 'Team avatar', 'clanspress' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$url = function_exists( 'clanspress_teams_get_display_team_avatar' )
	? clanspress_teams_get_display_team_avatar( $team_id, false, '', 'team_avatar_block', $avatar_preset )
	: '';
if ( '' === $url ) {
	$url = clanspress_teams_get_bundled_default_avatar_url();
}
$url = trim( (string) $url );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-team-avatar-block',
		'style' => $style,
	),
	$block
);

$alt = sprintf(
	/* translators: %s: team name */
	__( 'Avatar for %s', 'clanspress' ),
	get_the_title( $team_id )
);

ob_start();
if ( $url ) {
	printf(
		'<img class="clanspress-team-avatar__img" src="%1$s" alt="%2$s" width="%3$d" height="%3$d" loading="lazy" decoding="async" />',
		esc_url( $url ),
		esc_attr( $alt ),
		(int) $width
	);
} else {
	echo '<span class="clanspress-team-avatar__img clanspress-team-avatar__img--placeholder" role="img" aria-label="' . esc_attr( $alt ) . '">' . esc_html__( 'No avatar', 'clanspress' ) . '</span>';
}
$img_inner = ob_get_clean();

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
		$img_inner = '<a class="clanspress-team-avatar__link" href="' . esc_url( $href ) . '"' . $target . $rel_at . '>' . $img_inner . '</a>';
	}
}

$avatar_clip_open  = '<div class="clanspress-team-avatar__clip">';
$avatar_clip_close = '</div>';
$avatar_media      = $avatar_clip_open . $img_inner . $avatar_clip_close;

?>
<div
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes. ?>
>
	<div class="clanspress-team-avatar">
		<?php echo $avatar_media; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_url/esc_attr/esc_html. ?>
	</div>
</div>
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
