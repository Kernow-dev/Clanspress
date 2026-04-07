<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Render callback: team cover image and inner blocks.
 *
 * @package clanspress
 */

$team_id = clanspress_team_single_block_team_id( $block );

$position       = isset( $attributes['contentPosition'] ) ? (string) $attributes['contentPosition'] : 'bottom center';
$position_class = 'is-position-' . str_replace( ' ', '-', strtolower( $position ) );
$min_height_raw = isset( $attributes['minHeight'] ) ? trim( (string) $attributes['minHeight'] ) : '220px';
$min_height     = preg_match( '/^\d+(\.\d+)?(px|em|rem|vh|vw|%)$/', $min_height_raw ) ? $min_height_raw : '220px';

$allow_inline  = ! empty( $attributes['allowFrontEndMediaEdit'] );
$can_manage    = $team_id >= 1
	&& is_user_logged_in()
	&& function_exists( 'clanspress_teams_user_can_manage' )
	&& clanspress_teams_user_can_manage( $team_id, get_current_user_id() );
$show_controls = $can_manage && $allow_inline;

$interactive_attrs = '';
$wrapper_classes   = 'clanspress-team-cover clanspress-team-cover-block ' . $position_class;

$cover_placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

$cover_alt = sprintf(
	/* translators: %s: team name */
	__( 'Cover for %s', 'clanspress' ),
	$team_id >= 1 ? get_the_title( $team_id ) : ''
);

if ( $show_controls ) {
	$context = array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'teamId'  => $team_id,
		'strings' => array(
			'invalidFileType' => __( 'Only PNG or JPEG images are allowed.', 'clanspress' ),
			'saveSuccess'     => __( 'Your changes were saved successfully.', 'clanspress' ),
			'saveError'       => __( 'There was an error while saving changes.', 'clanspress' ),
		),
	);
	$interactive_attrs = sprintf(
		' data-wp-interactive="clanspress-team-cover" data-wp-context="%1$s" data-wp-init="callbacks.init"',
		esc_attr( wp_json_encode( $context ) )
	);
}

$render_toolbar = static function ( int $tid, string $panel_id, string $file_id ) : void {
	?>
	<div class="clanspress-team-cover__toolbar">
		<div
			class="clanspress-team-cover__toolbar-inner"
			aria-label="<?php esc_attr_e( 'Cover controls', 'clanspress' ); ?>"
		>
			<?php do_action( 'clanspress_team_cover_controls_before', $tid ); ?>
			<button
				type="button"
				class="clanspress-team-cover__toggle"
				data-wp-on--click="actions.togglePanel"
				data-cp-panel="edit-cover"
				data-wp-bind--aria-expanded="state.isThisPanelActive"
				aria-controls="<?php echo esc_attr( $panel_id ); ?>"
			>
				<?php esc_html_e( 'Edit', 'clanspress' ); ?>
			</button>
			<div
				id="<?php echo esc_attr( $panel_id ); ?>"
				class="clanspress-team-cover__panel clanspress-team-cover__panel--edit-cover"
				role="region"
				aria-label="<?php esc_attr_e( 'Team cover image', 'clanspress' ); ?>"
			>
				<button
					type="button"
					class="clanspress-team-cover__panel-action"
					data-wp-on--click="actions.selectFile"
				>
					<?php esc_html_e( 'Choose image…', 'clanspress' ); ?>
				</button>
				<input
					type="file"
					accept="image/png,image/jpeg"
					hidden
					data-wp-on--change="actions.updateImage"
					id="<?php echo esc_attr( $file_id ); ?>"
					name="team_cover"
				>
				<input type="hidden" name="_clanspress_team_media_nonce" value="<?php echo esc_attr( wp_create_nonce( 'clanspress_team_media_' . $tid ) ); ?>" />
				<input type="hidden" name="clanspress_team_id" value="<?php echo esc_attr( (string) $tid ); ?>" />
				<button
					type="button"
					class="clanspress-team-cover__panel-action clanspress-team-cover__panel-action--primary"
					data-wp-on--click="actions.save"
				>
					<?php esc_html_e( 'Save', 'clanspress' ); ?>
				</button>
			</div>
			<?php do_action( 'clanspress_team_cover_controls_after', $tid ); ?>
		</div>
	</div>
	<?php
};

$render_cover_toast = static function (): void {
	?>
	<div
		class="toast-box clanspress-team-cover__toast"
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
			<p class="toast-description" data-wp-text="state.toast.message"></p>
		</div>
	</div>
	<?php
};

if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => $wrapper_classes . ' clanspress-team-cover--placeholder',
			'style' => sprintf( 'min-height:%s;', $min_height ),
		),
		$block
	);
	echo '<div ' . $wrapper . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	echo '<div class="clanspress-team-cover__media clanspress-team-cover__media--placeholder" aria-hidden="true"></div>';
	echo '<div class="team-cover__content-container">';
	echo wp_kses_post( $content );
	echo '</div>';
	echo '</div>';
	return;
}

$cover_id = (int) get_post_meta( $team_id, 'cp_team_cover_id', true );
$url      = $cover_id ? wp_get_attachment_image_url( $cover_id, 'full' ) : '';
if ( ! $url ) {
	$url = clanspress_teams_get_default_cover_url( $team_id );
}
$url = trim( (string) $url );

if ( ! $url ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => $wrapper_classes . ' clanspress-team-cover--placeholder',
			'style' => sprintf( 'min-height:%s;', $min_height ),
		),
		$block
	);
	$panel_id = wp_unique_id( 'clanspress-team-cover-panel-' );
	$file_id  = wp_unique_id( 'clanspress-team-cover-file-' );
	echo '<div ' . $wrapper . $interactive_attrs . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $interactive_attrs is escaped JSON in attribute strings.
	echo '<img class="clanspress-team-cover__media clanspress-team-cover__media--empty" src="' . esc_url( $cover_placeholder ) . '" alt="' . esc_attr( $cover_alt ) . '" loading="lazy" decoding="async" />';
	if ( $show_controls ) {
		$render_toolbar( $team_id, $panel_id, $file_id );
	}
	echo '<div class="team-cover__content-container">';
	echo wp_kses_post( $content );
	echo '</div>';
	if ( $show_controls ) {
		$render_cover_toast();
	}
	echo '</div>';
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => $wrapper_classes,
		'style' => sprintf( 'min-height:%s;', $min_height ),
	),
	$block
);

$panel_id = wp_unique_id( 'clanspress-team-cover-panel-' );
$file_id  = wp_unique_id( 'clanspress-team-cover-file-' );

echo '<div ' . $wrapper_attributes . $interactive_attrs . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes + interactive attrs.
echo '<img class="clanspress-team-cover__media" src="' . esc_url( $url ) . '" alt="' . esc_attr( $cover_alt ) . '" loading="lazy" decoding="async" />';
if ( $show_controls ) {
	$render_toolbar( $team_id, $panel_id, $file_id );
}
echo '<div class="team-cover__content-container">';
echo wp_kses_post( $content );
echo '</div>';
if ( $show_controls ) {
	$render_cover_toast();
}
echo '</div>';
