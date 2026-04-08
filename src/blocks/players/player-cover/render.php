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

$cover_image = clanspress_players_get_display_cover( $user_id );
$has_cover   = (bool) $cover_image;

$background_position_x = round( clanspress_players_get_display_cover_position_x( $user_id ) * 100 ) . '%';
$background_position_y = round( clanspress_players_get_display_cover_position_y( $user_id ) * 100 ) . '%';

$object_position = $background_position_x . ' ' . $background_position_y;

$position = $attributes['contentPosition'] ?? 'center center';

// Map human-readable 'bottom center' -> 'is-position-bottom-center'
$position_class = 'is-position-' . str_replace( ' ', '-', strtolower( (string) $position ) );

$min_h = '220px';
if ( isset( $attributes['minHeight'] ) && (int) $attributes['minHeight'] > 0 ) {
	$unit = isset( $attributes['minHeightUnit'] ) ? (string) $attributes['minHeightUnit'] : 'px';
	$unit = preg_match( '/^(px|em|rem|vh|vw|%)$/', $unit ) ? $unit : 'px';
	$min_h = (int) $attributes['minHeight'] . $unit;
}

$cover_alt = sprintf(
	/* translators: %s: Player display name. */
	__( '%s\'s player cover', 'clanspress' ),
	clanspress_players_get_display_name( $user_id )
);

if ( ! $has_cover ) {
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => $position_class . ' clanspress-player-cover-block clanspress-player-cover--placeholder',
			'style' => sprintf( 'min-height:%s;', $min_h ),
		),
		$block
	);
	$cover_placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
	?>
<div
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes. ?>
>
	<div class="clanspress-player-cover__media-clip">
		<img
			class="clanspress-player-cover__media clanspress-player-cover__media--empty"
			src="<?php echo esc_url( $cover_placeholder ); ?>"
			alt="<?php echo esc_attr( $cover_alt ); ?>"
			loading="lazy"
			decoding="async"
		/>
	</div>
	<div class="player-cover__content-container">
		<?php echo wp_kses_post( $content ); ?>
	</div>
</div>
	<?php
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => $position_class . ' clanspress-player-cover-block',
	),
	$block
);

?>
<div
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes. ?>
>
	<div class="clanspress-player-cover__media-clip">
		<img
			class="clanspress-player-cover__media"
			style="object-position: <?php echo esc_attr( $object_position ); ?>;"
			src="<?php echo esc_url( $cover_image ); ?>"
			alt="<?php echo esc_attr( $cover_alt ); ?>"
			loading="lazy"
			decoding="async"
		/>
	</div>
	<div class="player-cover__content-container">
		<?php echo wp_kses_post( $content ); ?>
	</div>
</div>
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
