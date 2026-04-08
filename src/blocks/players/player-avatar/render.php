<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

$user_id = function_exists( 'clanspress_player_blocks_resolve_subject_user_id' )
	? (int) clanspress_player_blocks_resolve_subject_user_id( $block )
	: 0;

if ( ! $user_id ) {
	return '';
}

$display_name = clanspress_players_get_display_name( $user_id );

$inner_classes = 'clanspress-player-avatar__img';

$avatar_preset = isset( $attributes['avatarPreset'] ) ? sanitize_key( (string) $attributes['avatarPreset'] ) : 'large';
if ( ! in_array( $avatar_preset, array( 'large', 'medium', 'small' ), true ) ) {
	$avatar_preset = 'large';
}

$avatar_display_args = array(
	'context' => 'player_avatar_block',
	'preset'  => $avatar_preset,
);

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-player-avatar-block',
	),
	$block
);

$img_html = function_exists( 'clanspress_players_get_player_avatar_img_html' )
	? clanspress_players_get_player_avatar_img_html(
		$user_id,
		array_merge(
			$avatar_display_args,
			array( 'class' => $inner_classes )
		)
	)
	: '';

if ( '' !== $img_html ) {
	$img_inner = $img_html;
} else {
	ob_start();
	printf(
		'<span class="%1$s clanspress-player-avatar__img--placeholder" role="img" aria-label="%2$s">%3$s</span>',
		esc_attr( $inner_classes ),
		esc_attr( sprintf( /* translators: %s: Player display name. */ __( '%s — no avatar yet', 'clanspress' ), $display_name ) ),
		esc_html__( 'No avatar', 'clanspress' )
	);
	$img_inner = ob_get_clean();
	$img_inner = (string) apply_filters( 'clanspress_players_player_avatar_placeholder_markup', $img_inner, $user_id, $avatar_display_args );
}

if ( function_exists( 'clanspress_players_apply_player_avatar_display_markup' ) ) {
	$img_inner = clanspress_players_apply_player_avatar_display_markup( $img_inner, $user_id, $avatar_display_args );
}

if ( ! empty( $attributes['isLink'] ) && function_exists( 'clanspress_block_player_profile_url' ) && function_exists( 'clanspress_block_entity_link_url' ) ) {
	$href = clanspress_block_entity_link_url(
		clanspress_block_player_profile_url( $user_id ),
		'clanspress/player-avatar',
		$user_id,
		$block
	);
	if ( '' !== $href ) {
		$target = ( isset( $attributes['linkTarget'] ) && '_blank' === $attributes['linkTarget'] ) ? ' target="_blank"' : '';
		$rel    = function_exists( 'clanspress_block_entity_link_rel' ) ? clanspress_block_entity_link_rel( $attributes ) : '';
		$rel_at = '' !== $rel ? ' rel="' . esc_attr( $rel ) . '"' : '';
		$img_inner = '<a class="clanspress-player-avatar__link" href="' . esc_url( $href ) . '"' . $target . $rel_at . '>' . $img_inner . '</a>';
	}
}

$avatar_clip_open  = '<div class="clanspress-player-avatar__clip">';
$avatar_clip_close = '</div>';
$avatar_media      = $avatar_clip_open . $img_inner . $avatar_clip_close;

?>
<div
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes. ?>
>
	<div class="clanspress-player-avatar">
		<?php echo $avatar_media; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_url/esc_attr/esc_html. ?>
	</div>
</div>
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
