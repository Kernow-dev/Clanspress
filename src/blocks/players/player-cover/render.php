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

$can_edit      = get_current_user_id() === $user_id;
$allow_inline  = ! empty( $attributes['allowFrontEndMediaEdit'] );
$show_controls = $can_edit && $allow_inline;

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

$interactive_attrs = '';
if ( $show_controls ) {
	$context = array(
		'canEdit' => true,
		'strings' => array(
			'invalidFileType' => __( 'Only PNG or JPEG images are allowed.', 'clanspress' ),
			'saveSuccess'     => __( 'Your changes were saved successfully.', 'clanspress' ),
			'saveError'       => __( 'There was an error while saving changes.', 'clanspress' ),
		),
	);
	$interactive_attrs = sprintf(
		' data-wp-interactive="clanspress-player-cover" data-wp-context="%1$s" data-wp-init="callbacks.init"' .
		' data-wp-on--mouseenter="actions.showToolbar" data-wp-on--mouseleave="actions.hideToolbar"' .
		' data-wp-on--focusin="actions.showToolbar" data-wp-on--focusout="actions.handleToolbarFocusOut"',
		esc_attr( wp_json_encode( $context ) )
	);
}

/**
 * Shared toolbar markup when inline editing is enabled.
 *
 * @param string $panel_id      Panel element id.
 * @param string $file_input_id File input id.
 * @param string $cover_url     Current cover URL for position preview.
 */
$render_cover_toolbar = static function ( string $panel_id, string $file_input_id, string $cover_url ) use ( $has_cover, $background_position_x, $background_position_y, $user_id ): void {
	?>
	<div
		class="clanspress-player-cover__toolbar"
		aria-hidden="true"
		data-wp-class--is-toolbar-visible="state.toolbarVisible"
		data-wp-bind--aria-hidden="state.isToolbarHidden"
	>
		<div
			class="clanspress-player-cover__toolbar-inner"
			aria-label="<?php esc_attr_e( 'Cover controls', 'clanspress' ); ?>"
		>
			<?php do_action( 'clanspress_player_cover_controls_before' ); ?>
			<button
				type="button"
				class="clanspress-player-cover__toggle"
				data-wp-on--click="actions.togglePanel"
				data-cp-panel="edit-cover"
				data-wp-bind--aria-expanded="state.isThisPanelActive"
				aria-controls="<?php echo esc_attr( $panel_id ); ?>"
			>
				<?php esc_html_e( 'Edit', 'clanspress' ); ?>
			</button>
			<div
				id="<?php echo esc_attr( $panel_id ); ?>"
				class="clanspress-player-cover__panel clanspress-player-cover__panel--edit-cover"
				role="region"
				aria-label="<?php esc_attr_e( 'Cover image', 'clanspress' ); ?>"
			>
				<button
					type="button"
					class="clanspress-player-cover__panel-action"
					data-wp-on--click="actions.selectCover"
				>
					<?php esc_html_e( 'Choose image…', 'clanspress' ); ?>
				</button>
				<input
					type="file"
					accept="image/png,image/jpeg"
					class="clanspress-inline-media-file-input"
					aria-hidden="true"
					tabindex="-1"
					data-wp-on--change="actions.updateCover"
					id="<?php echo esc_attr( $file_input_id ); ?>"
					name="profile_cover"
				>
				<?php if ( $has_cover && '' !== $cover_url ) : ?>
				<div class="clanspress-player-cover__position">
					<p class="clanspress-player-cover__position-label"><?php esc_html_e( 'Focal point', 'clanspress' ); ?></p>
					<div
						class="clanspress-player-cover__position-box"
						style="background-image: url('<?php echo esc_url( $cover_url ); ?>');"
					>
						<div
							class="clanspress-player-cover__position-thumb"
							style="left: <?php echo esc_attr( $background_position_x ); ?>; top: <?php echo esc_attr( $background_position_y ); ?>;"
							data-wp-on--pointerdown="actions.startDrag"
						></div>
					</div>
					<input type="hidden" name="profile_cover_position_x" value="<?php echo esc_attr( (string) clanspress_players_get_display_cover_position_x( $user_id ) ); ?>" />
					<input type="hidden" name="profile_cover_position_y" value="<?php echo esc_attr( (string) clanspress_players_get_display_cover_position_y( $user_id ) ); ?>" />
				</div>
				<?php endif; ?>
				<?php wp_nonce_field( 'clanspress_profile_settings_save_action', '_clanspress_profile_settings_save_nonce', true, true ); ?>
				<button
					type="button"
					class="clanspress-player-cover__panel-action clanspress-player-cover__panel-action--primary"
					data-wp-on--click="actions.save"
				>
					<?php esc_html_e( 'Save', 'clanspress' ); ?>
				</button>
			</div>
			<?php do_action( 'clanspress_player_cover_controls_after' ); ?>
		</div>
	</div>
	<?php
};

if ( ! $has_cover ) {
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => $position_class . ' clanspress-player-cover-block clanspress-player-cover--placeholder',
			'style' => sprintf( 'min-height:%s;', $min_h ),
		),
		$block
	);
	$panel_empty_id   = wp_unique_id( 'clanspress-edit-cover-empty-' );
	$file_empty_id    = wp_unique_id( 'clanspress-profile-cover-empty-' );
	$cover_placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
	?>
<div
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes. ?>
	<?php echo $interactive_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- data-wp-* built with esc_attr( wp_json_encode() ). ?>
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
	<?php if ( $show_controls ) : ?>
		<?php $render_cover_toolbar( $panel_empty_id, $file_empty_id, '' ); ?>
	<?php endif; ?>
	<div class="player-cover__content-container">
		<?php echo wp_kses_post( $content ); ?>
	</div>
	<?php if ( $show_controls ) : ?>
	<div
		class="toast-box clanspress-player-cover__toast"
		role="status"
		aria-live="polite"
		aria-atomic="true"
		hidden
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
	<?php
	return;
}

$panel_id = wp_unique_id( 'clanspress-edit-cover-panel-' );
$file_id  = wp_unique_id( 'clanspress-profile-cover-' );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => $position_class . ' clanspress-player-cover-block',
	),
	$block
);

?>
<div
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes. ?>
	<?php echo $interactive_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- data-wp-* built with esc_attr( wp_json_encode() ). ?>
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
	<?php if ( $show_controls ) : ?>
		<?php $render_cover_toolbar( $panel_id, $file_id, (string) $cover_image ); ?>
	<?php endif; ?>
	<div class="player-cover__content-container">
		<?php echo wp_kses_post( $content ); ?>
	</div>
	<?php if ( $show_controls ) : ?>
	<div
		class="toast-box clanspress-player-cover__toast"
		role="status"
		aria-live="polite"
		aria-atomic="true"
		hidden
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
