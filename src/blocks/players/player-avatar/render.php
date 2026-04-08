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

$can_edit       = get_current_user_id() === $user_id;
$allow_inline   = ! empty( $attributes['allowFrontEndMediaEdit'] );
$show_controls  = $can_edit && $allow_inline;
$display_name   = clanspress_players_get_display_name( $user_id );

$inner_classes = 'clanspress-player-avatar__img';

$avatar_preset = isset( $attributes['avatarPreset'] ) ? sanitize_key( (string) $attributes['avatarPreset'] ) : 'large';
if ( ! in_array( $avatar_preset, array( 'large', 'medium', 'small' ), true ) ) {
	$avatar_preset = 'large';
}

$avatar_display_args = array(
	'context' => 'player_avatar_block',
	'preset'  => $avatar_preset,
);
// Transparent GIF: valid src when the profile has no avatar yet but inline upload is allowed.
$placeholder_src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-player-avatar-block',
	),
	$block
);

$interactive_attrs = '';
if ( $show_controls ) {
	$context = array(
		'canEdit'   => true,
		'strings'   => array(
			'invalidFileType' => __( 'Only PNG or JPEG images are allowed.', 'clanspress' ),
			'saveSuccess'     => __( 'Your changes were saved successfully.', 'clanspress' ),
			'saveError'       => __( 'There was an error while saving changes.', 'clanspress' ),
		),
	);
	$interactive_attrs = sprintf(
		' data-wp-interactive="clanspress-player-avatar" data-wp-context="%1$s" data-wp-init="callbacks.init"',
		esc_attr( wp_json_encode( $context ) )
	);
}

$avatar_file_input_id = wp_unique_id( 'clanspress-profile-avatar-' );
$panel_id             = wp_unique_id( 'clanspress-player-avatar-panel-' );

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
} elseif ( $show_controls ) {
	ob_start();
	printf(
		'<img class="%1$s clanspress-player-avatar__img--empty" src="%2$s" alt="%3$s" loading="lazy" decoding="async" />',
		esc_attr( $inner_classes ),
		esc_url( $placeholder_src ),
		esc_attr( sprintf( /* translators: %s: Player display name. */ __( '%s — no avatar yet', 'clanspress' ), $display_name ) )
	);
	$img_inner = ob_get_clean();
	$img_inner = (string) apply_filters( 'clanspress_players_player_avatar_empty_img_markup', $img_inner, $user_id, $avatar_display_args );
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
	<?php echo $interactive_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- data-wp-* built with esc_attr( wp_json_encode() ). ?>
>
	<div class="clanspress-player-avatar">
		<?php echo $avatar_media; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_url/esc_attr/esc_html. ?>
		<?php if ( $show_controls ) : ?>
		<div class="clanspress-player-avatar__toolbar">
			<div class="clanspress-player-avatar__toolbar-inner">
				<?php do_action( 'clanspress_player_avatar_controls_before' ); ?>
				<button
					type="button"
					class="clanspress-player-avatar__toggle"
					data-wp-on--click="actions.togglePanel"
					data-cp-panel="edit-avatar"
					data-wp-bind--aria-expanded="state.isThisPanelActive"
					aria-controls="<?php echo esc_attr( $panel_id ); ?>"
				>
					<?php esc_html_e( 'Edit', 'clanspress' ); ?>
				</button>
				<div
					id="<?php echo esc_attr( $panel_id ); ?>"
					class="clanspress-player-avatar__panel clanspress-player-avatar__panel--edit-avatar"
					role="region"
					aria-label="<?php esc_attr_e( 'Avatar image', 'clanspress' ); ?>"
				>
					<button
						type="button"
						class="clanspress-player-avatar__panel-action"
						data-wp-on--click="actions.selectAvatar"
					>
						<?php esc_html_e( 'Choose image…', 'clanspress' ); ?>
					</button>
					<input
						type="file"
						accept="image/png,image/jpeg"
						class="clanspress-inline-media-file-input"
						aria-hidden="true"
						tabindex="-1"
						data-wp-on--change="actions.updateAvatar"
						id="<?php echo esc_attr( $avatar_file_input_id ); ?>"
						name="profile_avatar"
					>
					<?php wp_nonce_field( 'clanspress_profile_settings_save_action', '_clanspress_profile_settings_save_nonce', true, true ); ?>
					<button
						type="button"
						class="clanspress-player-avatar__panel-action clanspress-player-avatar__panel-action--primary"
						data-wp-on--click="actions.save"
					>
						<?php esc_html_e( 'Save', 'clanspress' ); ?>
					</button>
				</div>
				<?php do_action( 'clanspress_player_avatar_controls_after' ); ?>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<?php if ( $show_controls ) : ?>
	<div
		class="toast-box clanspress-player-avatar__toast"
		role="status"
		aria-live="polite"
		aria-atomic="true"
		data-wp-bind--hidden="!state.toast.visible"
		data-wp-class--success="state.isToastSuccess"
		data-wp-class--error="state.isToastError"
	>
		<div class="toast-box-icon"></div>
		<div class="toast-box-text">
			<p class="toast-heading" data-wp-text="state.toast.heading"></p>
			<p class="toast-description" data-wp-text="state.toast.message"></p>
		</div>
	</div>
	<?php endif; ?>
</div>
