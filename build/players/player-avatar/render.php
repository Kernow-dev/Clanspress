<?php
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

$user_id = null;

if ( is_author() ) {
	$user = get_queried_object();
	if ( $user instanceof WP_User ) {
		$user_id = $user->ID;
	}
}

// 2. Block context → post author
if ( ! $user_id && ! empty( $block->context['postId'] ) ) {
	$user_id = (int) get_post_field(
		'post_author',
		$block->context['postId']
	);
}

// Fallback
if ( ! $user_id && is_user_logged_in() ) {
	$user_id = get_current_user_id();
}

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

$wrapper_attributes = get_block_wrapper_attributes( array() );

?>
<div
	<?php echo wp_kses_post( $wrapper_attributes ); ?>
	data-wp-interactive="clanspress-player-avatar"
	data-wp-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>"
	data-wp-init="callbacks.init"
	data-wp-on--pointerenter="actions.enableControls"
	data-wp-on--pointerleave="actions.disableControls"
>
	<div class="player-avatar__frame">
		<img
			class="player-avatar__image-background"
			src="<?php echo esc_url( $avatar_image ); ?>"
			<?php /* translators: %s: Player display name. */ ?>
			alt="<?php echo esc_attr( sprintf( __( '%s player avatar', 'clanspress' ), $display_name ) ); ?>"
			loading="lazy"
		/>
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
						data-wp-on--click="actions.toggleControl"
						data-wp-args="edit-avatar"
					>
						<span class="screen-reader-text"><?php esc_html_e( 'Edit avatar', 'clanspress' ); ?></span>
					</button>
					<div class="control-panel edit-avatar">
						<div class="select-avatar">
							<button
								class="change-media avatar"
								data-wp-on--click="actions.selectAvatar"
							><?php esc_html_e( 'Set avatar', 'clanspress' ); ?></button>
							<input
								type="file"
								accept="image/png,image/jpeg"
								hidden
								data-wp-on--change="actions.updateAvatar"
								id="profile-avatar"
								name="profile_avatar"
							>
						</div>
						<div class="save">
							<?php wp_nonce_field( 'clanspress_profile_settings_save_action', '_clanspress_profile_settings_save_nonce', true, true ); ?>
							<button
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
