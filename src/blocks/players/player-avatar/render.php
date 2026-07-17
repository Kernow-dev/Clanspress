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

$user_id = function_exists( 'clanbite_player_blocks_resolve_subject_user_id' )
	? (int) clanbite_player_blocks_resolve_subject_user_id( $block )
	: 0;

if ( ! $user_id ) {
	return '';
}

// Get avatar preset/size from block attributes
$avatar_preset = isset( $attributes['avatarPreset'] ) ? sanitize_key( (string) $attributes['avatarPreset'] ) : 'large';
if ( ! in_array( $avatar_preset, array( 'large', 'medium', 'small' ), true ) ) {
	$avatar_preset = 'large';
}

// Check if block should link to profile
$should_link   = ! empty( $attributes['isLink'] );
$link_target   = isset( $attributes['linkTarget'] ) && '_blank' === $attributes['linkTarget'] ? '_blank' : '';

// Build wrapper attributes
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-player-avatar-block clanbite-avatar-block',
	),
	$block
);

// Use the unified avatar rendering system
if ( function_exists( 'clanbite_render_avatar' ) ) {
	$avatar_html = clanbite_render_avatar(
		array(
			'type'          => 'player',
			'id'            => $user_id,
			'size'          => $avatar_preset,
			'context'       => 'profile',
			'link'          => $should_link,
			'link_target'   => $link_target,
			'show_rank'     => true,
			'show_progress' => true,
		)
	);
	
	?>
	<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php echo wp_kses( $avatar_html, clanbite_block_fragment_allowed_html() ); ?>
	</div>
	<?php
} else {
	// Fallback if unified system isn't loaded
	echo '<!-- Player avatar block: unified avatar system not available -->';
}

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
