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

$avatar_image = clanspress_players_get_display_avatar( $user_id );

if ( ! $avatar_image ) {
	//return '';
}

$can_edit     = get_current_user_id() === $user_id;
$display_name = clanspress_players_get_display_name( $user_id );

$context = array(
	'canEdit' => $can_edit,
);

$wrapper_attributes = get_block_wrapper_attributes( array(), $block );

$avatar_file_input_id = wp_unique_id( 'clanspress-profile-avatar-' );

$img_inner = sprintf(
	'<img class="player-avatar__image-background" src="%1$s" alt="%2$s" loading="lazy" decoding="async" />',
	esc_url( $avatar_image ),
	esc_attr( sprintf( /* translators: %s: Player display name. */ __( '%s player avatar', 'clanspress' ), $display_name ) )
);

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
		$img_inner = '<a class="player-avatar__link" href="' . esc_url( $href ) . '"' . $target . $rel_at . '>' . $img_inner . '</a>';
	}
}

?>
<div
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes. ?>
	data-wp-interactive="clanspress-player-avatar"
	data-wp-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>"
	data-wp-init="callbacks.init"
	data-wp-on--pointerenter="actions.enableControls"
	data-wp-on--pointerleave="actions.disableControls"
>
	<div class="player-avatar__frame">
		<?php echo $img_inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_url/esc_attr. ?>
		<?php if ( $can_edit ) : ?>
		<div
			class="player-avatar__controls"
			data-wp-class--active="state.isEditing"
		>
			<div class="player-avatar__controls-container">
				<?php do_action( 'clanspress_player_avatar_controls_before' ); ?>
				<div
					class="control edit-avatar"
				>
					<button
						type="button"
						data-wp-on--click="actions.toggleControl"
						data-wp-args="edit-avatar"
					>
						<span class="screen-reader-text"><?php esc_html_e( 'Edit avatar', 'clanspress' ); ?></span>
					</button>
					<div class="control-panel edit-avatar">
						<div class="select-avatar">
							<button
								type="button"
								class="change-media avatar"
								data-wp-on--click="actions.selectAvatar"
							><?php esc_html_e( 'Set avatar', 'clanspress' ); ?></button>
							<input
								type="file"
								accept="image/png,image/jpeg"
								hidden
								data-wp-on--change="actions.updateAvatar"
								id="<?php echo esc_attr( $avatar_file_input_id ); ?>"
								name="profile_avatar"
							>
						</div>
						<div class="save">
							<?php wp_nonce_field( 'clanspress_profile_settings_save_action', '_clanspress_profile_settings_save_nonce', true, true ); ?>
							<button
								type="button"
								data-wp-on--click="actions.save"
							><?php esc_html_e( 'Save', 'clanspress' ); ?></button>
						</div>
					</div>
				</div>
				<?php do_action( 'clanspress_player_avatar_controls_after' ); ?>
				<?php do_action( 'clanspress_player_cover_controls_after' ); ?>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>
