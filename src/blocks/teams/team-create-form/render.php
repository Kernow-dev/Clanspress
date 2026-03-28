<?php
/**
 * Render callback for the Team Create Form block.
 *
 * @package clanspress
 */

if ( ! is_user_logged_in() ) {
	echo '<p>' . esc_html__( 'You must be logged in to create a team.', 'clanspress' ) . '</p>';
	return;
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'clanspress-team-create-form' ) );
$status      = sanitize_key( (string) ( $_GET['clanspress_team_status'] ?? '' ) );
$status_code = sanitize_key( (string) ( $_GET['clanspress_team_code'] ?? '' ) );
$steps = array(
	'basic_details' => array(
		'label' => __( 'Step 1: Team Details', 'clanspress' ),
	),
	'branding'      => array(
		'label' => __( 'Step 2: Team Avatar', 'clanspress' ),
	),
);

if ( (bool) apply_filters( 'clanspress_enable_team_create_invites_step', true ) ) {
	$steps['invites'] = array(
		'label' => __( 'Step 3: Player invites', 'clanspress' ),
	);
}

/**
 * Filter create-team form steps.
 *
 * Third parties can add custom steps by appending a new keyed array item.
 *
 * @param array $steps Step map.
 */
$steps = (array) apply_filters( 'clanspress_team_create_form_steps', $steps );

$context             = array(
	'stepCount'         => count( $steps ),
	'inviteSearchUrl'   => admin_url( 'admin-ajax.php' ),
	'inviteSearchNonce' => wp_create_nonce( 'clanspress_team_invite_search' ),
);
?>
<div
	<?php echo wp_kses_post( $wrapper_attributes ); ?>
	data-wp-interactive="clanspress-team-create-form"
	data-wp-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>"
>
	<?php do_action( 'clanspress_before_team_create_form' ); ?>
	<?php if ( 'success' === $status ) : ?>
		<p class="clanspress-team-create-form__notice is-success"><?php esc_html_e( 'Team created successfully.', 'clanspress' ); ?></p>
	<?php elseif ( 'error' === $status ) : ?>
		<p class="clanspress-team-create-form__notice is-error"><?php echo esc_html( 'missing_name' === $status_code ? __( 'Team name is required.', 'clanspress' ) : __( 'Could not create team. Please try again.', 'clanspress' ) ); ?></p>
	<?php endif; ?>
	<form method="post" class="clanspress-team-create-form__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
		<?php
		$step_number = 1;
		foreach ( $steps as $step_key => $step ) :
			?>
			<div data-team-step="<?php echo esc_attr( $step_number ); ?>" data-wp-bind--hidden="!state.isCurrentStep">
				<p><strong><?php echo esc_html( $step['label'] ?? sprintf( __( 'Step %d', 'clanspress' ), $step_number ) ); ?></strong></p>
				<?php if ( 'basic_details' === $step_key ) : ?>
					<p>
						<label for="clanspress-team-name"><?php esc_html_e( 'Team Name', 'clanspress' ); ?></label>
						<input type="text" id="clanspress-team-name" name="team_name" required />
					</p>
					<p>
						<label for="clanspress-team-code"><?php esc_html_e( 'Team Code', 'clanspress' ); ?></label>
						<input type="text" id="clanspress-team-code" name="team_code" />
					</p>
					<p>
						<label for="clanspress-team-motto"><?php esc_html_e( 'Team Motto', 'clanspress' ); ?></label>
						<input type="text" id="clanspress-team-motto" name="team_motto" />
					</p>
					<p>
						<label for="clanspress-team-description"><?php esc_html_e( 'Description', 'clanspress' ); ?></label>
						<textarea id="clanspress-team-description" name="team_description" rows="4"></textarea>
					</p>
					<p class="description"><?php esc_html_e( 'Team slug will be generated from your team name.', 'clanspress' ); ?></p>
				<?php elseif ( 'branding' === $step_key ) : ?>
					<div class="clanspress-team-create-form__media-row" aria-label="<?php esc_attr_e( 'Team avatar and cover', 'clanspress' ); ?>">
						<div class="clanspress-team-create-form__avatar-cover-preview">
							<div class="clanspress-team-create-form__cover-preview"></div>
							<div class="clanspress-team-create-form__avatar-preview"></div>
						</div>
						<div class="clanspress-team-create-form__media-buttons">
							<button
								type="button"
								class="clanspress-team-create-form__change-media clanspress-team-create-form__change-media--avatar"
								data-wp-on--click="actions.selectTeamAvatar"
							><?php esc_html_e( 'Set team avatar', 'clanspress' ); ?></button>
							<input
								type="file"
								id="clanspress-team-avatar"
								name="team_avatar"
								accept="image/png,image/jpeg"
								hidden
								data-wp-on--change="actions.updateTeamAvatar"
							/>
							<button
								type="button"
								class="clanspress-team-create-form__change-media clanspress-team-create-form__change-media--cover"
								data-wp-on--click="actions.selectTeamCover"
							><?php esc_html_e( 'Set cover image', 'clanspress' ); ?></button>
							<input
								type="file"
								id="clanspress-team-cover"
								name="team_cover"
								accept="image/png,image/jpeg"
								hidden
								data-wp-on--change="actions.updateTeamCover"
							/>
						</div>
					</div>
					<p class="description"><?php esc_html_e( 'PNG or JPEG images only.', 'clanspress' ); ?></p>
				<?php elseif ( 'invites' === $step_key ) : ?>
					<p>
						<label for="clanspress-team-invite-search"><?php esc_html_e( 'Invite players', 'clanspress' ); ?></label>
						<input
							type="text"
							id="clanspress-team-invite-search"
							autocomplete="off"
							role="combobox"
							aria-autocomplete="list"
							aria-expanded="false"
							aria-controls="clanspress-team-invite-suggestions-list"
							placeholder="<?php esc_attr_e( 'Search by username, display name, or email', 'clanspress' ); ?>"
							data-wp-on--input="actions.onInviteInput"
							data-wp-on--keydown="actions.onInviteKeydown"
						/>
					</p>
					<ul
						id="clanspress-team-invite-suggestions-list"
						class="clanspress-team-create-form__invite-suggestions"
						role="listbox"
						data-team-invite-suggestions
						data-wp-on--click="actions.onSuggestionClick"
					></ul>
					<div class="clanspress-team-create-form__invite-list" data-team-invite-list data-wp-on--click="actions.onInviteListClick"></div>
					<input type="hidden" name="team_invites" value="" data-team-invite-hidden />
				<?php else : ?>
					<?php
					/**
					 * Render a custom create-team step.
					 *
					 * @param string $step_key Step key.
					 * @param array  $step Step config.
					 * @param int    $step_number Step number.
					 */
					do_action( 'clanspress_team_create_form_step', $step_key, $step, $step_number );
					do_action( "clanspress_team_create_form_step_{$step_key}", $step, $step_number );
					?>
				<?php endif; ?>
			</div>
			<?php
			$step_number++;
		endforeach;
		?>

		<div class="clanspress-team-create-form__actions">
			<button type="button" data-wp-on--click="actions.previousStep" data-wp-bind--hidden="!state.canGoBack"><?php esc_html_e( 'Back', 'clanspress' ); ?></button>
			<button type="button" data-wp-on--click="actions.nextStep" data-wp-bind--hidden="!state.canGoNext"><?php esc_html_e( 'Next', 'clanspress' ); ?></button>
			<button type="submit" data-wp-bind--hidden="state.canGoNext"><?php esc_html_e( 'Create Team', 'clanspress' ); ?></button>
		</div>

		<?php wp_nonce_field( 'clanspress_create_team_action', '_clanspress_create_team_nonce' ); ?>
		<input type="hidden" name="action" value="clanspress_create_team" />
	</form>
	<?php do_action( 'clanspress_after_team_create_form' ); ?>
</div>
