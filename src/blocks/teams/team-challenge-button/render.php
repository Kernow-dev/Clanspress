<?php
/**
 * Renders the Challenge team button and modal shell (interactivity in view.js).
 *
 * @package clanbite
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
$team_id = clanbite_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	return;
}

$team_post = get_post( $team_id );
if ( ! $team_post instanceof WP_Post || 'publish' !== $team_post->post_status ) {
	return;
}

if ( ! function_exists( 'clanbite_matches' ) || ! clanbite_matches() ) {
	return;
}

if ( ! function_exists( 'clanbite_team_accepts_challenges' ) || ! clanbite_team_accepts_challenges( $team_id ) ) {
	return;
}

$teams = clanbite_teams();
if ( ! $teams ) {
	return;
}

$uid = is_user_logged_in() ? (int) get_current_user_id() : 0;
if ( $uid > 0 && $teams->user_can_manage_team_on_frontend( $team_id, $uid ) ) {
	return;
}

if ( $uid > 0 && ! $teams->user_is_teams_site_admin( $uid ) && function_exists( 'clanbite_teams_user_manages_any_team' ) && ! clanbite_teams_user_manages_any_team( $uid ) ) {
	return;
}

/**
 * Whether to output the team challenge UI.
 *
 * @param bool     $visible Default visibility.
 * @param int      $team_id Team post ID.
 * @param WP_Block $block   Block instance.
 */
$visible = (bool) apply_filters( 'clanbite_team_challenge_button_visible', true, $team_id, $block );
if ( ! $visible ) {
	return;
}

$managed = array();
if ( $uid > 0 && function_exists( 'clanbite_teams_get_user_managed_team_ids' ) ) {
	foreach ( clanbite_teams_get_user_managed_team_ids( $uid ) as $mid ) {
		$mid = (int) $mid;
		if ( $mid < 1 || $mid === $team_id ) {
			continue;
		}
		$managed[] = array(
			'id'    => $mid,
			'title' => get_the_title( $mid ),
		);
	}
}

$rest_root       = esc_url_raw( rest_url( 'clanbite/v1' ) );
$rest_nonce      = wp_create_nonce( 'wp_rest' );
$challenge_nonce = wp_create_nonce( 'clanbite_team_challenge_' . $team_id );

$context = array(
	'teamId'          => $team_id,
	'restUrl'         => $rest_root,
	'restNonce'       => $rest_nonce,
	'challengeNonce'  => $challenge_nonce,
	'managedTeams'    => $managed,
	'isLoggedIn'      => $uid > 0,
	'open'            => false,
	'loading'         => false,
	'lookupLoading'   => false,
	'remoteError'     => '',
	'formError'       => '',
	'formSuccess'       => '',
	'previewTitle'      => '',
	'previewLogo'       => '',
	'previewPermalink'  => '',
	'logoAttachmentId'  => 0,
	'logoUploadMessage' => '',
);

$wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-team-challenge',
	),
	$block
);

$team_challenge_button_root_open = '<div '
	. trim( (string) $wrapper )
	. ' data-wp-interactive="clanbite-team-challenge-button"'
	. ' data-wp-context="' . esc_attr( wp_json_encode( $context ) ) . '"'
	. '>';
?>
<?php ob_start(); ?>
<?php clanbite_echo_block_fragment_html( $team_challenge_button_root_open ); ?>
	<div class="wp-block-button clanbite-button clanbite-button--challenge">
		<button
			type="button"
			class="wp-block-button__link wp-element-button clanbite-button__element"
			data-wp-on--click="actions.open"
		>
			<?php esc_html_e( 'Challenge team', 'clanbite' ); ?>
		</button>
	</div>

	<div
		class="clanbite-team-challenge__backdrop"
		hidden
		data-wp-bind--hidden="!context.open"
		data-wp-on--click="actions.closeBackdrop"
	>
		<div
			class="clanbite-team-challenge__dialog"
			role="dialog"
			aria-modal="true"
			aria-labelledby="clanbite-team-challenge-title-<?php echo esc_attr( (string) $team_id ); ?>"
			tabindex="-1"
			data-wp-on--click="actions.stop"
		>
			<h2 id="clanbite-team-challenge-title-<?php echo esc_attr( (string) $team_id ); ?>">
				<?php esc_html_e( 'Challenge this team', 'clanbite' ); ?>
			</h2>
			<p class="description">
				<?php esc_html_e( 'Send a match challenge. Team admins will be notified and can accept or decline.', 'clanbite' ); ?>
			</p>

			<form data-wp-on--submit="actions.submit">
				<?php if ( $uid < 1 ) : ?>
					<div class="clanbite-team-challenge__field">
						<label for="cp-ch-name-<?php echo esc_attr( (string) $team_id ); ?>"><?php esc_html_e( 'Your name', 'clanbite' ); ?></label>
						<input id="cp-ch-name-<?php echo esc_attr( (string) $team_id ); ?>" name="challenger_name" type="text" required autocomplete="name" />
					</div>
					<div class="clanbite-team-challenge__field">
						<label for="cp-ch-email-<?php echo esc_attr( (string) $team_id ); ?>"><?php esc_html_e( 'Email', 'clanbite' ); ?></label>
						<input id="cp-ch-email-<?php echo esc_attr( (string) $team_id ); ?>" name="challenger_email" type="email" required autocomplete="email" />
					</div>
				<?php endif; ?>

				<?php if ( $uid > 0 && array() !== $managed ) : ?>
					<div class="clanbite-team-challenge__field">
						<label for="cp-ch-team-<?php echo esc_attr( (string) $team_id ); ?>"><?php esc_html_e( 'Your team', 'clanbite' ); ?></label>
						<select id="cp-ch-team-<?php echo esc_attr( (string) $team_id ); ?>" name="challenger_team_id">
							<option value="0"><?php esc_html_e( '— External / guest style —', 'clanbite' ); ?></option>
							<?php foreach ( $managed as $row ) : ?>
								<option value="<?php echo esc_attr( (string) (int) $row['id'] ); ?>"><?php echo esc_html( (string) $row['title'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>

				<div class="clanbite-team-challenge__field">
					<label for="cp-ch-url-<?php echo esc_attr( (string) $team_id ); ?>"><?php esc_html_e( 'Opponent team URL (optional)', 'clanbite' ); ?></label>
					<input id="cp-ch-url-<?php echo esc_attr( (string) $team_id ); ?>" name="opponent_team_url" type="url" inputmode="url" placeholder="https://example.com/teams/their-slug/" data-wp-on--blur="actions.lookupRemote" />
					<p class="description"><?php esc_html_e( 'If they use Clanbite, we load their public team details for the match listing.', 'clanbite' ); ?></p>
				</div>

				<div class="clanbite-team-challenge__preview" hidden data-wp-bind--hidden="!context.previewTitle">
					<img data-wp-bind--src="context.previewLogo" data-wp-bind--hidden="!context.previewLogo" alt="" width="48" height="48" aria-hidden="true" hidden />
					<div>
						<strong data-wp-text="context.previewTitle"></strong>
						<div class="clanbite-team-challenge__preview-link" hidden data-wp-bind--hidden="!context.previewPermalink">
							<a data-wp-bind--href="context.previewPermalink"><span data-wp-text="context.previewPermalink"></span></a>
						</div>
					</div>
				</div>
				<p class="clanbite-team-challenge__notice is-error" data-wp-bind--hidden="!context.remoteError" data-wp-text="context.remoteError" hidden></p>

				<div class="clanbite-team-challenge__field clanbite-team-challenge__manual-brand">
					<p class="description">
						<?php esc_html_e( 'If the challenger site does not run Clanbite, add how your team should appear on the match (optional).', 'clanbite' ); ?>
					</p>
					<label for="cp-ch-brand-<?php echo esc_attr( (string) $team_id ); ?>"><?php esc_html_e( 'Your team or org name (optional)', 'clanbite' ); ?></label>
					<input id="cp-ch-brand-<?php echo esc_attr( (string) $team_id ); ?>" name="challenger_team_name" type="text" autocomplete="organization" />
				</div>
				<div class="clanbite-team-challenge__field">
					<label for="cp-ch-logo-<?php echo esc_attr( (string) $team_id ); ?>"><?php esc_html_e( 'Team logo image (optional, max 2MB)', 'clanbite' ); ?></label>
					<input id="cp-ch-logo-<?php echo esc_attr( (string) $team_id ); ?>" type="file" accept="image/jpeg,image/png,image/gif,image/webp" data-wp-on--change="actions.uploadLogo" />
					<p class="clanbite-team-challenge__notice" data-wp-bind--hidden="!context.logoUploadMessage" data-wp-text="context.logoUploadMessage" hidden></p>
				</div>

				<div class="clanbite-team-challenge__field">
					<label for="cp-ch-when-<?php echo esc_attr( (string) $team_id ); ?>"><?php esc_html_e( 'Proposed date & time (optional)', 'clanbite' ); ?></label>
					<input id="cp-ch-when-<?php echo esc_attr( (string) $team_id ); ?>" name="proposed_scheduled_at" type="datetime-local" />
				</div>

				<div class="clanbite-team-challenge__field">
					<label for="cp-ch-msg-<?php echo esc_attr( (string) $team_id ); ?>"><?php esc_html_e( 'Message (optional)', 'clanbite' ); ?></label>
					<textarea id="cp-ch-msg-<?php echo esc_attr( (string) $team_id ); ?>" name="message" rows="3"></textarea>
				</div>

				<p class="clanbite-team-challenge__notice is-error" data-wp-bind--hidden="!context.formError" data-wp-text="context.formError" hidden></p>
				<p class="clanbite-team-challenge__notice is-success" data-wp-bind--hidden="!context.formSuccess" data-wp-text="context.formSuccess" hidden></p>

			<div class="clanbite-team-challenge__actions">
				<div class="wp-block-button clanbite-button clanbite-button--primary clanbite-button--submit">
					<button type="submit" class="wp-block-button__link wp-element-button clanbite-button__element" data-wp-bind--disabled="context.loading">
						<?php esc_html_e( 'Send challenge', 'clanbite' ); ?>
					</button>
				</div>
				<div class="wp-block-button clanbite-button clanbite-button--secondary clanbite-button--cancel">
					<button type="button" class="wp-block-button__link wp-element-button clanbite-button__element" data-wp-on--click="actions.close">
						<?php esc_html_e( 'Cancel', 'clanbite' ); ?>
					</button>
				</div>
			</div>
			</form>
		</div>
	</div>
</div>
<?php echo wp_kses( (string) ob_get_clean(), clanbite_block_fragment_allowed_html()); ?>
