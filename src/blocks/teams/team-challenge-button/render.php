<?php
/**
 * Renders the Challenge team button and modal shell (interactivity in view.js).
 *
 * @package clanspress
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
$team_id = clanspress_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	return;
}

$team_post = get_post( $team_id );
if ( ! $team_post instanceof WP_Post || 'publish' !== $team_post->post_status ) {
	return;
}

if ( ! function_exists( 'clanspress_matches' ) || ! clanspress_matches() ) {
	return;
}

if ( ! function_exists( 'clanspress_team_accepts_challenges' ) || ! clanspress_team_accepts_challenges( $team_id ) ) {
	return;
}

$teams = clanspress_teams();
if ( ! $teams ) {
	return;
}

$uid = is_user_logged_in() ? (int) get_current_user_id() : 0;
if ( $uid > 0 && $teams->user_can_manage_team_on_frontend( $team_id, $uid ) ) {
	return;
}

if ( $uid > 0 && ! $teams->user_is_teams_site_admin( $uid ) && function_exists( 'clanspress_teams_user_manages_any_team' ) && ! clanspress_teams_user_manages_any_team( $uid ) ) {
	return;
}

/**
 * Whether to output the team challenge UI.
 *
 * @param bool     $visible Default visibility.
 * @param int      $team_id Team post ID.
 * @param WP_Block $block   Block instance.
 */
$visible = (bool) apply_filters( 'clanspress_team_challenge_button_visible', true, $team_id, $block );
if ( ! $visible ) {
	return;
}

$managed = array();
if ( $uid > 0 && function_exists( 'clanspress_teams_get_user_managed_team_ids' ) ) {
	foreach ( clanspress_teams_get_user_managed_team_ids( $uid ) as $mid ) {
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

$rest_root       = esc_url_raw( rest_url( 'clanspress/v1' ) );
$rest_nonce      = wp_create_nonce( 'wp_rest' );
$challenge_nonce = wp_create_nonce( 'clanspress_team_challenge_' . $team_id );

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
		'class' => 'clanspress-team-challenge',
	)
);
?>
<div
	<?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes. ?>
	data-wp-interactive="clanspress-team-challenge-button"
	data-wp-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>"
>
	<div class="wp-block-button">
		<button
			type="button"
			class="wp-block-button__link wp-element-button clanspress-team-challenge__toggle"
			data-wp-on--click="actions.open"
		>
			<?php esc_html_e( 'Challenge team', 'clanspress' ); ?>
		</button>
	</div>

	<div
		class="clanspress-team-challenge__backdrop"
		data-wp-bind--hidden="!context.open"
		data-wp-on--click="actions.closeBackdrop"
	>
		<div
			class="clanspress-team-challenge__dialog"
			role="dialog"
			aria-modal="true"
			aria-labelledby="clanspress-team-challenge-title-<?php echo esc_attr( (string) $team_id ); ?>"
			tabindex="-1"
			data-wp-on--click="actions.stop"
		>
			<h2 id="clanspress-team-challenge-title-<?php echo esc_attr( (string) $team_id ); ?>">
				<?php esc_html_e( 'Challenge this team', 'clanspress' ); ?>
			</h2>
			<p class="description">
				<?php esc_html_e( 'Send a match challenge. Team admins will be notified and can accept or decline.', 'clanspress' ); ?>
			</p>

			<form data-wp-on--submit="actions.submit">
				<?php if ( $uid < 1 ) : ?>
					<div class="clanspress-team-challenge__field">
						<label for="cp-ch-name-<?php echo esc_attr( (string) $team_id ); ?>"><?php esc_html_e( 'Your name', 'clanspress' ); ?></label>
						<input id="cp-ch-name-<?php echo esc_attr( (string) $team_id ); ?>" name="challenger_name" type="text" required autocomplete="name" />
					</div>
					<div class="clanspress-team-challenge__field">
						<label for="cp-ch-email-<?php echo esc_attr( (string) $team_id ); ?>"><?php esc_html_e( 'Email', 'clanspress' ); ?></label>
						<input id="cp-ch-email-<?php echo esc_attr( (string) $team_id ); ?>" name="challenger_email" type="email" required autocomplete="email" />
					</div>
				<?php endif; ?>

				<?php if ( $uid > 0 && array() !== $managed ) : ?>
					<div class="clanspress-team-challenge__field">
						<label for="cp-ch-team-<?php echo esc_attr( (string) $team_id ); ?>"><?php esc_html_e( 'Your team', 'clanspress' ); ?></label>
						<select id="cp-ch-team-<?php echo esc_attr( (string) $team_id ); ?>" name="challenger_team_id">
							<option value="0"><?php esc_html_e( '— External / guest style —', 'clanspress' ); ?></option>
							<?php foreach ( $managed as $row ) : ?>
								<option value="<?php echo esc_attr( (string) (int) $row['id'] ); ?>"><?php echo esc_html( (string) $row['title'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>

				<div class="clanspress-team-challenge__field">
					<label for="cp-ch-url-<?php echo esc_attr( (string) $team_id ); ?>"><?php esc_html_e( 'Opponent team URL (optional)', 'clanspress' ); ?></label>
					<input id="cp-ch-url-<?php echo esc_attr( (string) $team_id ); ?>" name="opponent_team_url" type="url" inputmode="url" placeholder="https://example.com/teams/their-slug/" data-wp-on--blur="actions.lookupRemote" />
					<p class="description"><?php esc_html_e( 'If they use Clanspress, we load their public team details for the match listing.', 'clanspress' ); ?></p>
				</div>

				<div class="clanspress-team-challenge__preview" data-wp-bind--hidden="!context.previewTitle">
					<img data-wp-bind--src="context.previewLogo" data-wp-bind--hidden="!context.previewLogo" alt="" width="48" height="48" hidden />
					<div>
						<strong data-wp-text="context.previewTitle"></strong>
						<div class="clanspress-team-challenge__preview-link" data-wp-bind--hidden="!context.previewPermalink">
							<a data-wp-bind--href="context.previewPermalink"><span data-wp-text="context.previewPermalink"></span></a>
						</div>
					</div>
				</div>
				<p class="clanspress-team-challenge__notice is-error" data-wp-bind--hidden="!context.remoteError" data-wp-text="context.remoteError" hidden></p>

				<div class="clanspress-team-challenge__field clanspress-team-challenge__manual-brand">
					<p class="description">
						<?php esc_html_e( 'If the challenger site does not run Clanspress, add how your team should appear on the match (optional).', 'clanspress' ); ?>
					</p>
					<label for="cp-ch-brand-<?php echo esc_attr( (string) $team_id ); ?>"><?php esc_html_e( 'Your team or org name (optional)', 'clanspress' ); ?></label>
					<input id="cp-ch-brand-<?php echo esc_attr( (string) $team_id ); ?>" name="challenger_team_name" type="text" autocomplete="organization" />
				</div>
				<div class="clanspress-team-challenge__field">
					<label for="cp-ch-logo-<?php echo esc_attr( (string) $team_id ); ?>"><?php esc_html_e( 'Team logo image (optional, max 2MB)', 'clanspress' ); ?></label>
					<input id="cp-ch-logo-<?php echo esc_attr( (string) $team_id ); ?>" type="file" accept="image/jpeg,image/png,image/gif,image/webp" data-wp-on--change="actions.uploadLogo" />
					<p class="clanspress-team-challenge__notice" data-wp-bind--hidden="!context.logoUploadMessage" data-wp-text="context.logoUploadMessage" hidden></p>
				</div>

				<div class="clanspress-team-challenge__field">
					<label for="cp-ch-when-<?php echo esc_attr( (string) $team_id ); ?>"><?php esc_html_e( 'Proposed date & time (optional)', 'clanspress' ); ?></label>
					<input id="cp-ch-when-<?php echo esc_attr( (string) $team_id ); ?>" name="proposed_scheduled_at" type="datetime-local" />
				</div>

				<div class="clanspress-team-challenge__field">
					<label for="cp-ch-msg-<?php echo esc_attr( (string) $team_id ); ?>"><?php esc_html_e( 'Message (optional)', 'clanspress' ); ?></label>
					<textarea id="cp-ch-msg-<?php echo esc_attr( (string) $team_id ); ?>" name="message" rows="3"></textarea>
				</div>

				<p class="clanspress-team-challenge__notice is-error" data-wp-bind--hidden="!context.formError" data-wp-text="context.formError" hidden></p>
				<p class="clanspress-team-challenge__notice is-success" data-wp-bind--hidden="!context.formSuccess" data-wp-text="context.formSuccess" hidden></p>

				<div class="clanspress-team-challenge__actions">
					<button type="submit" class="wp-element-button" data-wp-bind--disabled="context.loading">
						<?php esc_html_e( 'Send challenge', 'clanspress' ); ?>
					</button>
					<button type="button" class="wp-element-button" data-wp-on--click="actions.close">
						<?php esc_html_e( 'Cancel', 'clanspress' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
