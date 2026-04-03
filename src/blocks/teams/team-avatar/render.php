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

$width = isset( $attributes['width'] ) ? (int) $attributes['width'] : 120;
$width = min( 512, max( 32, $width ) );

$allow_inline  = ! empty( $attributes['allowFrontEndMediaEdit'] );
$can_manage    = $team_id >= 1
	&& is_user_logged_in()
	&& function_exists( 'clanspress_teams_user_can_manage' )
	&& clanspress_teams_user_can_manage( $team_id, get_current_user_id() );
$show_controls = $can_manage && $allow_inline;

$style = sprintf( 'width:%dpx;height:%dpx;', $width, $width );

$placeholder_src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

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

$avatar_id = (int) get_post_meta( $team_id, 'cp_team_avatar_id', true );
$url       = $avatar_id ? wp_get_attachment_image_url( $avatar_id, 'medium' ) : '';
if ( ! $url && function_exists( 'clanspress_teams_get_default_avatar_url' ) ) {
	$url = clanspress_teams_get_default_avatar_url( $team_id );
}
$url = trim( (string) $url );
if ( ! $url && function_exists( 'clanspress' ) ) {
	$url = clanspress()->url . 'assets/img/avatars/default-avatar.png';
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-team-avatar-block',
		'style' => $style,
	),
	$block
);

$interactive_attrs = '';
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
		' data-wp-interactive="clanspress-team-avatar" data-wp-context="%1$s" data-wp-init="callbacks.init"',
		esc_attr( wp_json_encode( $context ) )
	);
}

$alt = sprintf(
	/* translators: %s: team name */
	__( 'Avatar for %s', 'clanspress' ),
	get_the_title( $team_id )
);

$panel_id   = wp_unique_id( 'clanspress-team-avatar-panel-' );
$file_input = wp_unique_id( 'clanspress-team-avatar-file-' );

ob_start();
if ( $url ) {
	printf(
		'<img class="clanspress-team-avatar__img" src="%1$s" alt="%2$s" width="%3$d" height="%3$d" loading="lazy" decoding="async" />',
		esc_url( $url ),
		esc_attr( $alt ),
		(int) $width
	);
} elseif ( $show_controls ) {
	printf(
		'<img class="clanspress-team-avatar__img clanspress-team-avatar__img--empty" src="%1$s" alt="%2$s" width="%3$d" height="%3$d" loading="lazy" decoding="async" />',
		esc_url( $placeholder_src ),
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

?>
<div
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes. ?>
	<?php echo $interactive_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- data-wp-* built with esc_attr( wp_json_encode() ). ?>
>
	<div class="clanspress-team-avatar">
		<?php echo $img_inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_url/esc_attr/esc_html. ?>
		<?php if ( $show_controls ) : ?>
		<div class="clanspress-team-avatar__toolbar">
			<div class="clanspress-team-avatar__toolbar-inner">
				<?php do_action( 'clanspress_team_avatar_controls_before', $team_id ); ?>
				<button
					type="button"
					class="clanspress-team-avatar__toggle"
					data-wp-on--click="actions.togglePanel"
					data-wp-args="edit-avatar"
					data-wp-bind--aria-expanded="state.isThisPanelActive"
					aria-controls="<?php echo esc_attr( $panel_id ); ?>"
				>
					<?php esc_html_e( 'Edit', 'clanspress' ); ?>
				</button>
				<div
					id="<?php echo esc_attr( $panel_id ); ?>"
					class="clanspress-team-avatar__panel clanspress-team-avatar__panel--edit-avatar"
					role="region"
					aria-label="<?php esc_attr_e( 'Team avatar image', 'clanspress' ); ?>"
				>
					<button
						type="button"
						class="clanspress-team-avatar__panel-action"
						data-wp-on--click="actions.selectFile"
					>
						<?php esc_html_e( 'Choose image…', 'clanspress' ); ?>
					</button>
					<input
						type="file"
						accept="image/png,image/jpeg"
						hidden
						data-wp-on--change="actions.updateImage"
						id="<?php echo esc_attr( $file_input ); ?>"
						name="team_avatar"
					>
					<input type="hidden" name="_clanspress_team_media_nonce" value="<?php echo esc_attr( wp_create_nonce( 'clanspress_team_media_' . $team_id ) ); ?>" />
					<input type="hidden" name="clanspress_team_id" value="<?php echo esc_attr( (string) $team_id ); ?>" />
					<button
						type="button"
						class="clanspress-team-avatar__panel-action clanspress-team-avatar__panel-action--primary"
						data-wp-on--click="actions.save"
					>
						<?php esc_html_e( 'Save', 'clanspress' ); ?>
					</button>
				</div>
				<?php do_action( 'clanspress_team_avatar_controls_after', $team_id ); ?>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>
