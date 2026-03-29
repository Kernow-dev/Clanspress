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

$cover_image = clanspress_players_get_display_cover( $user_id );

if ( ! $cover_image ) {
	return '';
}

$can_edit              = get_current_user_id() === $user_id;
$background_position_x = round( clanspress_players_get_display_cover_position_x( $user_id ) * 100 ) . '% ';
$background_position_y = round( clanspress_players_get_display_cover_position_y( $user_id ) * 100 ) . '% ';

$object_position = $background_position_x . ' ' . $background_position_y;

$context = array(
	'canEdit'         => $can_edit,
	'object_position' => $object_position,
);

$position = $attributes['contentPosition'] ?? 'center center';

// Map human-readable 'bottom center' -> 'is-position-bottom-center'
$position_class = 'is-position-' . str_replace( ' ', '-', strtolower( $position ) );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => $position_class,
	)
);

?>
<div
	<?php echo wp_kses_post( $wrapper_attributes ); ?>
	data-wp-interactive="clanspress-player-cover"
	data-wp-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>"
	data-wp-init="callbacks.init"
	data-wp-on--pointerenter="actions.enableControls"
	data-wp-on--pointerleave="actions.disableControls"
>
	<img
		class="player-cover__image-background"
		style="object-position: <?php echo esc_attr( $object_position ); ?>;"
		src="<?php echo esc_url( $cover_image ); ?>"
		<?php /* translators: %s: Player display name. */ ?>
		alt="<?php echo esc_html( sprintf( __( '%s\'s player cover', 'clanspress' ), clanspress_players_get_display_name( $user_id ) ) ); ?>"
		loading="lazy"
	/>
	<?php if ( $can_edit ) : ?>
	<div
		class="player-cover__controls"
		data-wp-class--active="state.isEditing"
	>
		<div
			class="player-cover__controls-container"
			aria-label="<?php esc_html__( 'Cover controls', 'clanspress' ); ?>"
		>
			<?php do_action( 'clanspress_player_cover_controls_before' ); ?>
			<div class="control edit-cover">
				<button
					data-wp-on--click="actions.toggleControl"
					data-wp-args="edit-cover"
					data-wp-bind--aria-expanded="state.isThisPanelActive"
					aria-controls="edit-cover-panel"
				>
					<span class="screen-reader-text"><?php esc_html_e( 'Edit cover', 'clanspress' ); ?></span>
				</button>
				<div
					id="edit-cover-panel"
					class="control-panel edit-cover"
					role="region"
					aria-label="<?php esc_html_e( 'Edit cover image', 'clanspress' ); ?>"
				>
					<div class="select-cover">
						<button
							class="change-media cover"
							data-wp-on--click="actions.selectCover"
						><?php esc_html_e( 'Set cover', 'clanspress' ); ?></button>
						<input
							type="file"
							accept="image/png,image/jpeg"
							hidden
							data-wp-on--change="actions.updateCover"
							id="profile-cover"
							name="profile_cover"
						>
					</div>
					<div class="position-cover">
						<div class="position-box" style="background-image: linear-gradient(to right, rgba( 255, 255, 255, 0.6) 1px, transparent 1px), linear-gradient(to bottom, rgba( 255, 255, 255, 0.6) 1px, transparent 1px), url('<?php echo esc_url( $cover_image ); ?>'); background-size: 33.33% 100%, 100% 33.33%, contain;">
							<div
								class="thumb"
								style="left: <?php echo esc_attr( $background_position_x ); ?>; top: <?php echo esc_attr( $background_position_y ); ?>;"
								data-wp-on--pointerdown="actions.startDrag"
								data-wp-on--pointerup="actions.stopDrag"
							></div>
						</div>
						<input type="hidden" id="profile-cover-position-x" name="profile_cover_position_x" value="<?php echo esc_attr( clanspress_players_get_display_cover_position_x( $user_id ) ); ?>" />
						<input type="hidden" id="profile-cover-position-y" name="profile_cover_position_y" value="<?php echo esc_attr( clanspress_players_get_display_cover_position_y( $user_id ) ); ?>" />
					</div>
					<div class="save">
						<?php wp_nonce_field( 'clanspress_profile_settings_save_action', '_clanspress_profile_settings_save_nonce', true, true ); ?>
						<button
							data-wp-on--click="actions.save"
						><?php esc_html_e( 'Save', 'clanspress' ); ?></button>
					</div>
				</div>
			</div>
			<?php do_action( 'clanspress_player_cover_controls_after' ); ?>
		</div>
	</div>
	<?php endif; ?>
	<div class="player-cover__content-container">
		<?php echo wp_kses_post( $content ); ?>
	</div>
</div>
