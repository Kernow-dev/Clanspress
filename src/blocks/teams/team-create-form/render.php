<?php
/**
 * Render callback for the Team Create Form block.
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.

if ( 'team_directories' !== clanspress_teams_get_team_mode() ) {
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-team-create-form clanspress-team-create-form--unavailable',
		),
		$block
	);
	echo '<div ' . $wrapper_attributes . '><p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	echo esc_html__( 'Team creation is only available when Teams is set to "team directories" mode.', 'clanspress' );
	echo '</p></div>';
	return;
}

if ( ! is_user_logged_in() ) {
	echo '<p>' . esc_html__( 'You must be logged in to create a team.', 'clanspress' ) . '</p>';
	return;
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'clanspress-team-create-form' ), $block );
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only query args after create redirect; values are `sanitize_key()`-ed.
$status      = sanitize_key( (string) ( $_GET['clanspress_team_status'] ?? '' ) );
$status_code = sanitize_key( (string) ( $_GET['clanspress_team_code'] ?? '' ) );
// phpcs:enable WordPress.Security.NonceVerification.Recommended
$steps = array(
	'basic_details' => array(
		'label'       => __( 'Step 1: Team Details', 'clanspress' ),
		'title'       => __( 'Team details', 'clanspress' ),
		'description' => __( 'Name your team and add optional text.', 'clanspress' ),
	),
	'branding'      => array(
		'label'       => __( 'Step 2: Team Avatar', 'clanspress' ),
		'title'       => __( 'Branding', 'clanspress' ),
		'description' => __( 'Upload an avatar and cover image.', 'clanspress' ),
	),
);

if ( (bool) apply_filters( 'clanspress_enable_team_create_invites_step', true ) ) {
	$steps['invites'] = array(
		'label'       => __( 'Step 3: Player invites', 'clanspress' ),
		'title'       => __( 'Invites', 'clanspress' ),
		'description' => __( 'Optionally invite players by search.', 'clanspress' ),
	);
}

/**
 * Filter create-team form steps.
 *
 * Third parties can add custom steps by appending a new keyed array item.
 * Each step may include:
 * - label (string) — default in-step heading fallback.
 * - title (string) — short tab title; falls back to label.
 * - description (string) — short tab subtitle (optional).
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
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes. ?>
	data-wp-interactive="clanspress-team-create-form"
	data-wp-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>"
	data-wp-init="callbacks.init"
>
	<?php do_action( 'clanspress_before_team_create_form' ); ?>
	<?php if ( 'success' === $status ) : ?>
		<p id="clanspress-team-create-notice" class="clanspress-team-create-form__notice is-success" role="status" tabindex="-1"><?php esc_html_e( 'Team created successfully.', 'clanspress' ); ?></p>
	<?php elseif ( 'error' === $status ) : ?>
		<p id="clanspress-team-create-notice" class="clanspress-team-create-form__notice is-error" role="alert" tabindex="-1"><?php echo esc_html( 'missing_name' === $status_code ? __( 'Team name is required.', 'clanspress' ) : ( 'wordban' === $status_code ? __( 'That text is not allowed.', 'clanspress' ) : __( 'Could not create team. Please try again.', 'clanspress' ) ) ); ?></p>
	<?php endif; ?>
	<form
		method="post"
		class="clanspress-team-create-form__form"
		action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
		enctype="multipart/form-data"
		data-active-step="1"
		data-wp-on--submit="actions.onSubmit"
	>
		<div class="clanspress-team-create-form__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Create team steps', 'clanspress' ); ?>" aria-orientation="horizontal">
			<?php
			$tab_index = 1;
			foreach ( $steps as $tab_step ) :
				$tab_title = isset( $tab_step['title'] ) && $tab_step['title'] !== ''
					? $tab_step['title']
					: ( $tab_step['label'] ?? sprintf(
						/* translators: %d: Step number (1-based) in the create-team flow. */
						__( 'Step %d', 'clanspress' ),
						$tab_index
					) );
				$tab_description = isset( $tab_step['description'] ) ? (string) $tab_step['description'] : '';
				$is_first_tab    = 1 === $tab_index;
				$tab_class       = 'clanspress-team-create-form__tab' . ( $is_first_tab ? ' is-active' : ' is-upcoming' );
				?>
				<button
					type="button"
					class="<?php echo esc_attr( $tab_class ); ?>"
					role="tab"
					id="clanspress-team-create-form-tab-<?php echo esc_attr( (string) $tab_index ); ?>"
					data-team-tab="<?php echo esc_attr( (string) $tab_index ); ?>"
					data-wp-on--click="actions.goToStepTab"
					aria-controls="clanspress-team-create-form-panel-<?php echo esc_attr( (string) $tab_index ); ?>"
					aria-selected="<?php echo $is_first_tab ? 'true' : 'false'; ?>"
					tabindex="<?php echo $is_first_tab ? '0' : '-1'; ?>"
					<?php echo $is_first_tab ? '' : ' disabled'; ?>
				>
					<span class="clanspress-team-create-form__tab-index" aria-hidden="true"><?php echo esc_html( (string) $tab_index ); ?></span>
					<span class="clanspress-team-create-form__tab-text">
						<span class="clanspress-team-create-form__tab-title"><?php echo esc_html( $tab_title ); ?></span>
						<?php if ( $tab_description !== '' ) : ?>
							<span class="clanspress-team-create-form__tab-description"><?php echo esc_html( $tab_description ); ?></span>
						<?php endif; ?>
					</span>
				</button>
				<?php
				++$tab_index;
			endforeach;
			?>
		</div>
		<?php
		$step_number = 1;
		foreach ( $steps as $step_key => $step ) :
			?>
			<div
				class="clanspress-team-create-form__step"
				role="tabpanel"
				id="clanspress-team-create-form-panel-<?php echo esc_attr( (string) $step_number ); ?>"
				aria-labelledby="clanspress-team-create-form-tab-<?php echo esc_attr( (string) $step_number ); ?>"
				data-team-step="<?php echo esc_attr( $step_number ); ?>"
				<?php echo $step_number > 1 ? ' hidden' : ''; ?>
			>
				<?php if ( 'basic_details' === $step_key ) : ?>
					<p>
						<label for="clanspress-team-name"><?php esc_html_e( 'Team Name', 'clanspress' ); ?></label>
						<input type="text" id="clanspress-team-name" name="team_name" required />
					</p>
					<p class="description"><?php esc_html_e( 'Team slug will be generated from your team name.', 'clanspress' ); ?></p>
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
					<p>
						<label for="clanspress-team-country"><?php esc_html_e( 'Country', 'clanspress' ); ?></label>
						<select id="clanspress-team-country" name="team_country" class="widefat">
							<option value=""><?php esc_html_e( '— Select —', 'clanspress' ); ?></option>
							<?php
							if ( function_exists( 'clanspress_players_get_countries' ) ) :
								foreach ( clanspress_players_get_countries() as $cc => $cname ) :
									?>
									<option value="<?php echo esc_attr( $cc ); ?>"><?php echo esc_html( $cname ); ?></option>
									<?php
								endforeach;
							endif;
							?>
						</select>
					</p>
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
				<?php elseif ( 'matches' === $step_key ) : ?>
					<div class="clanspress-team-create-form__matches-field">
						<p>
							<input type="hidden" name="team_accept_challenges" value="0" />
							<label for="clanspress-team-accept-challenges">
								<input
									type="checkbox"
									id="clanspress-team-accept-challenges"
									name="team_accept_challenges"
									value="1"
									checked
								/>
								<?php esc_html_e( 'Allow other teams to challenge this team', 'clanspress' ); ?>
							</label>
						</p>
						<p class="description"><?php esc_html_e( 'You can change this later from your team settings.', 'clanspress' ); ?></p>
					</div>
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

		<div class="clanspress-team-create-form__actions clanspress-team-create-form__actions--split" role="navigation" aria-label="<?php esc_attr_e( 'Step navigation', 'clanspress' ); ?>">
			<button type="button" class="clanspress-team-create-form__nav-btn" data-wp-on--click="actions.previousStep" data-wp-bind--hidden="!state.canGoBack()"><?php esc_html_e( 'Back', 'clanspress' ); ?></button>
			<div class="clanspress-team-create-form__actions-end">
				<button type="button" class="clanspress-team-create-form__nav-btn" data-wp-on--click="actions.nextStep" data-wp-bind--hidden="!state.canGoNext()"><?php esc_html_e( 'Next', 'clanspress' ); ?></button>
				<button type="submit" class="clanspress-team-create-form__nav-btn clanspress-team-create-form__nav-btn--primary" data-wp-bind--hidden="state.canGoNext()"><?php esc_html_e( 'Create team', 'clanspress' ); ?></button>
			</div>
		</div>

		<?php wp_nonce_field( 'clanspress_create_team_action', '_clanspress_create_team_nonce' ); ?>
		<input type="hidden" name="action" value="clanspress_create_team" />
	</form>
	<?php do_action( 'clanspress_after_team_create_form' ); ?>
</div>
