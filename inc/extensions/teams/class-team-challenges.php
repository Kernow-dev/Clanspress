<?php
/**
 * Team match challenges: persistence, REST, notifications, match/event creation.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Extensions\Teams;

defined( 'ABSPATH' ) || exit;


use Kernowdev\Clanspress\Events\Event_Post_Type;
use Kernowdev\Clanspress\Events\Event_Rsvp_Data_Access;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_User;

/**
 * Registers the internal challenge post type, public REST actions, and notification handlers.
 */
final class Team_Challenges {

	public const POST_TYPE = 'cp_team_challenge';

	public const STATUS_PENDING  = 'pending';
	public const STATUS_ACCEPTED = 'accepted';
	public const STATUS_DECLINED = 'declined';

	public const META_CHALLENGED_TEAM_ID    = 'cp_challenge_challenged_team_id';
	public const META_CHALLENGER_USER_ID     = 'cp_challenge_challenger_user_id';
	public const META_CHALLENGER_NAME        = 'cp_challenge_challenger_name';
	public const META_CHALLENGER_EMAIL       = 'cp_challenge_challenger_email';
	public const META_MESSAGE                = 'cp_challenge_message';
	public const META_PROPOSED_SCHEDULED_AT  = 'cp_challenge_proposed_scheduled_at';
	public const META_CHALLENGER_TEAM_ID     = 'cp_challenge_challenger_team_id';
	public const META_REMOTE_TEAM_URL        = 'cp_challenge_remote_team_url';
	public const META_REMOTE_SNAPSHOT        = 'cp_challenge_remote_snapshot';
	public const META_STATUS                 = 'cp_challenge_status';
	public const META_MATCH_ID               = 'cp_challenge_match_id';
	public const META_EVENT_ID               = 'cp_challenge_event_id';
	public const META_CHALLENGER_LOGO_ATTACHMENT_ID = 'cp_challenge_challenger_logo_attachment_id';

	/**
	 * Singleton.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Cached Teams extension reference.
	 *
	 * @var \Kernowdev\Clanspress\Extensions\Teams|null
	 */
	private ?\Kernowdev\Clanspress\Extensions\Teams $teams = null;

	/**
	 * Get singleton.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->teams = clanspress_teams();

		add_action( 'init', array( $this, 'register_post_type' ), 12 );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register the internal challenge post type (no public URLs).
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => array(
					'name' => _x( 'Team challenges', 'post type general name', 'clanspress' ),
				),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
				'show_in_menu'       => false,
				'show_in_rest'       => false,
				'query_var'          => false,
				'rewrite'            => false,
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
				'supports'           => array( 'title' ),
			)
		);
	}

	/**
	 * REST routes for challenge flow (same-origin proxy + create).
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		if ( ! $this->dependencies_available() ) {
			return;
		}

		register_rest_route(
			'clanspress/v1',
			'/challenge-remote-team',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_challenge_remote_team' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'team_id'         => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'url'             => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'esc_url_raw',
					),
					'challenge_nonce' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'clanspress/v1',
			'/team-challenges',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_create_challenge' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'clanspress/v1',
			'/team-challenge-media',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_upload_challenge_media' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Whether Teams and Matches extensions are available.
	 *
	 * @return bool
	 */
	private function dependencies_available(): bool {
		return $this->teams instanceof \Kernowdev\Clanspress\Extensions\Teams
			&& function_exists( 'clanspress_matches' )
			&& clanspress_matches() instanceof \Kernowdev\Clanspress\Extensions\Matches;
	}

	/**
	 * Proxy: fetch discovery + public team from a remote Clanspress site (server-side).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_challenge_remote_team( WP_REST_Request $request ) {
		$team_id = (int) $request->get_param( 'team_id' );
		$url     = (string) $request->get_param( 'url' );
		$nonce   = (string) $request->get_param( 'challenge_nonce' );

		$nonce_ok = wp_verify_nonce( $nonce, 'clanspress_team_challenge_' . $team_id );
		if ( ! $nonce_ok ) {
			return new WP_Error( 'clanspress_challenge_bad_nonce', __( 'Invalid security token.', 'clanspress' ), array( 'status' => 403 ) );
		}

		$result = $this->fetch_remote_team_bundle( $url );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Accept a small team logo image for manual (non–Clanspress-remote) challenges.
	 *
	 * @param WP_REST_Request $request Request (`multipart/form-data`: challenge_nonce, team_id, file).
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_upload_challenge_media( WP_REST_Request $request ) {
		if ( ! $this->dependencies_available() ) {
			return new WP_Error( 'clanspress_challenge_unavailable', __( 'Challenges are not available.', 'clanspress' ), array( 'status' => 503 ) );
		}

		$team_id = (int) $request->get_param( 'team_id' );
		$nonce   = (string) $request->get_param( 'challenge_nonce' );

		if ( ! wp_verify_nonce( $nonce, 'clanspress_team_challenge_' . $team_id ) ) {
			return new WP_Error( 'clanspress_challenge_bad_nonce', __( 'Invalid security token.', 'clanspress' ), array( 'status' => 403 ) );
		}

		if ( ! $this->rate_limit_challenge_submission() ) {
			return new WP_Error( 'clanspress_challenge_rate_limited', __( 'Too many requests. Please try again later.', 'clanspress' ), array( 'status' => 429 ) );
		}

		$team_post = get_post( $team_id );
		if ( ! ( $team_post instanceof WP_Post ) || 'cp_team' !== $team_post->post_type || 'publish' !== $team_post->post_status ) {
			return new WP_Error( 'clanspress_challenge_bad_team', __( 'Team not found.', 'clanspress' ), array( 'status' => 404 ) );
		}

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing -- `$_FILES` after REST nonce check; validated by `wp_handle_upload()`.
		try {
		if ( empty( $_FILES['file'] ) || ! is_array( $_FILES['file'] ) ) {
			return new WP_Error( 'clanspress_challenge_no_file', __( 'No file uploaded.', 'clanspress' ), array( 'status' => 400 ) );
		}

		$file = $_FILES['file'];
		if ( ! empty( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new WP_Error( 'clanspress_challenge_upload', __( 'Upload failed.', 'clanspress' ), array( 'status' => 400 ) );
		}

		if ( ! empty( $file['size'] ) && (int) $file['size'] > 2 * MB_IN_BYTES ) {
			return new WP_Error( 'clanspress_challenge_upload', __( 'Image must be 2MB or smaller.', 'clanspress' ), array( 'status' => 400 ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$overrides = array(
			'test_form' => false,
			'mimes'     => array(
				'jpg|jpeg|jpe' => 'image/jpeg',
				'gif'          => 'image/gif',
				'png'          => 'image/png',
				'webp'         => 'image/webp',
			),
		);

		$staging_relative = function_exists( 'clanspress_team_challenge_logo_staging_relative_dir' )
			? clanspress_team_challenge_logo_staging_relative_dir( $team_id )
			: 'clanspress/teams/' . $team_id . '/matches/staging';

		$move = function_exists( 'clanspress_with_upload_subdir' )
			? clanspress_with_upload_subdir(
				$staging_relative,
				static function () use ( $file, $overrides ) {
					return wp_handle_upload( $file, $overrides );
				}
			)
			: wp_handle_upload( $file, $overrides );
		if ( isset( $move['error'] ) ) {
			return new WP_Error( 'clanspress_challenge_upload', sanitize_text_field( (string) $move['error'] ), array( 'status' => 400 ) );
		}

		$type = wp_check_filetype( $move['file'], null );
		if ( empty( $type['type'] ) || 0 !== strpos( (string) $type['type'], 'image/' ) ) {
			wp_delete_file( $move['file'] );
			return new WP_Error( 'clanspress_challenge_upload', __( 'Only image uploads are allowed.', 'clanspress' ), array( 'status' => 400 ) );
		}

		$att_id = wp_insert_attachment(
			array(
				'post_mime_type' => $type['type'],
				'post_title'     => sanitize_file_name( basename( $move['file'] ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
				'post_author'    => is_user_logged_in() ? (int) get_current_user_id() : 0,
			),
			$move['file']
		);

		if ( ! is_int( $att_id ) || $att_id < 1 ) {
			wp_delete_file( $move['file'] );
			return new WP_Error( 'clanspress_challenge_upload', __( 'Could not save attachment.', 'clanspress' ), array( 'status' => 500 ) );
		}

		wp_update_attachment_metadata( (int) $att_id, wp_generate_attachment_metadata( (int) $att_id, $move['file'] ) );

		if ( defined( 'CLANSPRESS_TEAM_CHALLENGE_LOGO_TEAM_META' ) ) {
			update_post_meta( (int) $att_id, \CLANSPRESS_TEAM_CHALLENGE_LOGO_TEAM_META, (string) $team_id );
		}
		if ( defined( 'CLANSPRESS_ATTACHMENT_HIDE_FROM_LIBRARY' ) ) {
			update_post_meta( (int) $att_id, \CLANSPRESS_ATTACHMENT_HIDE_FROM_LIBRARY, '1' );
		}

		$url = wp_get_attachment_image_url( (int) $att_id, 'medium' );

		return new WP_REST_Response(
			array(
				'id'  => (int) $att_id,
				'url' => $url ? (string) $url : '',
			),
			201
		);
		} finally {
			// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
		}
	}

	/**
	 * Whether an attachment is an image suitable for challenge branding.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return bool
	 */
	private function is_valid_challenge_logo_attachment( int $attachment_id ): bool {
		if ( $attachment_id < 1 ) {
			return false;
		}
		$post = get_post( $attachment_id );
		if ( ! ( $post instanceof WP_Post ) || 'attachment' !== $post->post_type ) {
			return false;
		}
		$mime = get_post_mime_type( $post );
		return is_string( $mime ) && str_starts_with( $mime, 'image/' );
	}

	/**
	 * Create a pending challenge and notify challenged team admins.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_create_challenge( WP_REST_Request $request ) {
		if ( ! $this->dependencies_available() ) {
			return new WP_Error( 'clanspress_challenge_unavailable', __( 'Challenges are not available.', 'clanspress' ), array( 'status' => 503 ) );
		}

		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = $request->get_body_params();
		}
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		$team_id = isset( $params['team_id'] ) ? (int) $params['team_id'] : 0;
		$nonce   = isset( $params['challenge_nonce'] ) ? sanitize_text_field( (string) $params['challenge_nonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, 'clanspress_team_challenge_' . $team_id ) ) {
			return new WP_Error( 'clanspress_challenge_bad_nonce', __( 'Invalid security token.', 'clanspress' ), array( 'status' => 403 ) );
		}

		if ( ! $this->rate_limit_challenge_submission() ) {
			return new WP_Error( 'clanspress_challenge_rate_limited', __( 'Too many requests. Please try again later.', 'clanspress' ), array( 'status' => 429 ) );
		}

		$team_post = get_post( $team_id );
		if ( ! ( $team_post instanceof WP_Post ) || 'cp_team' !== $team_post->post_type || 'publish' !== $team_post->post_status ) {
			return new WP_Error( 'clanspress_challenge_bad_team', __( 'Team not found.', 'clanspress' ), array( 'status' => 404 ) );
		}

		if ( ! function_exists( 'clanspress_team_accepts_challenges' ) || ! clanspress_team_accepts_challenges( $team_id ) ) {
			return new WP_Error( 'clanspress_challenge_not_accepting', __( 'This team is not accepting challenges.', 'clanspress' ), array( 'status' => 400 ) );
		}

		$uid = is_user_logged_in() ? (int) get_current_user_id() : 0;

		if ( $uid > 0 && $this->teams && $this->teams->user_can_manage_team_on_frontend( $team_id, $uid ) ) {
			return new WP_Error( 'clanspress_challenge_own_team', __( 'You cannot challenge a team you manage.', 'clanspress' ), array( 'status' => 400 ) );
		}

		if ( $uid > 0 ) {
			if ( ! function_exists( 'clanspress_teams_user_manages_any_team' ) || ! clanspress_teams_user_manages_any_team( $uid ) ) {
				return new WP_Error( 'clanspress_challenge_forbidden', __( 'You must manage a team to send a challenge.', 'clanspress' ), array( 'status' => 403 ) );
			}
		}

		$name  = isset( $params['challenger_name'] ) ? sanitize_text_field( (string) $params['challenger_name'] ) : '';
		$email = isset( $params['challenger_email'] ) ? sanitize_email( (string) $params['challenger_email'] ) : '';
		$msg   = isset( $params['message'] ) ? sanitize_textarea_field( (string) $params['message'] ) : '';

		if ( $uid > 0 ) {
			$user = get_userdata( $uid );
			if ( $user instanceof WP_User ) {
				if ( '' === $name ) {
					$name = $user->display_name ? (string) $user->display_name : (string) $user->user_login;
				}
				if ( '' === $email ) {
					$email = (string) $user->user_email;
				}
			}
		}

		if ( '' === $name ) {
			return new WP_Error( 'clanspress_challenge_name', __( 'Please enter your name.', 'clanspress' ), array( 'status' => 400 ) );
		}
		if ( '' === $email || ! is_email( $email ) ) {
			return new WP_Error( 'clanspress_challenge_email', __( 'Please enter a valid email address.', 'clanspress' ), array( 'status' => 400 ) );
		}

		$challenger_team_id = isset( $params['challenger_team_id'] ) ? (int) $params['challenger_team_id'] : 0;
		if ( $challenger_team_id > 0 ) {
			if ( $uid < 1 ) {
				return new WP_Error( 'clanspress_challenge_team', __( 'Invalid challenger team.', 'clanspress' ), array( 'status' => 400 ) );
			}
			$managed = function_exists( 'clanspress_teams_get_user_managed_team_ids' ) ? clanspress_teams_get_user_managed_team_ids( $uid ) : array();
			if ( ! in_array( $challenger_team_id, $managed, true ) || $challenger_team_id === $team_id ) {
				return new WP_Error( 'clanspress_challenge_team', __( 'Invalid challenger team.', 'clanspress' ), array( 'status' => 400 ) );
			}
		}

		$opponent_url = isset( $params['opponent_team_url'] ) ? esc_url_raw( (string) $params['opponent_team_url'] ) : '';
		$proposed     = isset( $params['proposed_scheduled_at'] ) ? sanitize_text_field( (string) $params['proposed_scheduled_at'] ) : '';
		$team_brand   = isset( $params['challenger_team_name'] ) ? sanitize_text_field( (string) $params['challenger_team_name'] ) : '';
		$logo_att_id  = isset( $params['challenger_team_logo_id'] ) ? (int) $params['challenger_team_logo_id'] : 0;

		$snapshot = array(
			'source'             => 'manual',
			'title'              => $name,
			'logoUrl'            => '',
			'profileUrl'         => $opponent_url,
			'origin'             => '',
			'description'        => '',
			'remoteTeamId'       => 0,
			'challengedTeamSlug' => sanitize_title( (string) $team_post->post_name ),
			'challengedSiteUrl'  => trailingslashit( home_url( '/' ) ),
		);

		if ( '' !== $opponent_url ) {
			$remote = $this->fetch_remote_team_bundle( $opponent_url );
			if ( ! is_wp_error( $remote ) && ! empty( $remote['team'] ) && is_array( $remote['team'] ) ) {
				$t = $remote['team'];
				$snapshot = array(
					'source'             => 'remote',
					'title'              => isset( $t['title'] ) ? sanitize_text_field( (string) $t['title'] ) : $name,
					'logoUrl'            => isset( $t['logoUrl'] ) ? esc_url_raw( (string) $t['logoUrl'] ) : '',
					'profileUrl'         => isset( $t['permalink'] ) ? esc_url_raw( (string) $t['permalink'] ) : $opponent_url,
					'origin'             => isset( $remote['origin'] ) ? esc_url_raw( (string) $remote['origin'] ) : '',
					'description'        => isset( $t['description'] ) ? sanitize_text_field( (string) $t['description'] ) : '',
					'remoteTeamId'       => isset( $t['id'] ) ? (int) $t['id'] : 0,
					'challengedTeamSlug' => sanitize_title( (string) $team_post->post_name ),
					'challengedSiteUrl'  => trailingslashit( home_url( '/' ) ),
				);
			}
		} elseif ( $challenger_team_id > 0 ) {
			$snapshot['source']     = 'local_team';
			$snapshot['title']      = get_the_title( $challenger_team_id );
			$snapshot['profileUrl'] = get_permalink( $challenger_team_id ) ?: '';
			if ( function_exists( 'clanspress_teams_get_display_team_avatar' ) ) {
				$snapshot['logoUrl'] = clanspress_teams_get_display_team_avatar( $challenger_team_id, false, '', 'team_challenge', 'medium' );
			} else {
				$aid = (int) get_post_meta( $challenger_team_id, 'cp_team_avatar_id', true );
				if ( $aid ) {
					$snapshot['logoUrl'] = (string) wp_get_attachment_image_url( $aid, 'medium' );
				}
			}
		}

		if ( 'remote' !== ( $snapshot['source'] ?? '' ) && '' !== $team_brand ) {
			$snapshot['title'] = $team_brand;
		}

		$stored_logo_attachment_id = 0;
		if ( 'remote' !== ( $snapshot['source'] ?? '' ) && $logo_att_id > 0 && $this->is_valid_challenge_logo_attachment( $logo_att_id ) ) {
			$logo_ok = function_exists( 'clanspress_team_challenge_logo_attachment_matches_team' )
				&& clanspress_team_challenge_logo_attachment_matches_team( $logo_att_id, $team_id );
			if ( $logo_ok ) {
				$logo_src = wp_get_attachment_image_url( $logo_att_id, 'medium' );
				if ( $logo_src ) {
					$snapshot['logoUrl'] = (string) $logo_src;
					$stored_logo_attachment_id = $logo_att_id;
				}
			}
		}

		$scheduled_gmt = '';
		if ( '' !== $proposed ) {
			$matches = clanspress_matches();
			if ( $matches ) {
				$scheduled_gmt = $matches->sanitize_scheduled_at( $proposed );
			}
		}

		$title = sprintf(
			/* translators: %s: challenger name */
			__( 'Challenge from %s', 'clanspress' ),
			$name
		);

		$challenge_id = $this->insert_challenge_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $title,
				'post_author' => $uid > 0 ? $uid : 0,
			)
		);

		if ( is_wp_error( $challenge_id ) ) {
			return $challenge_id;
		}

		$challenge_id = (int) $challenge_id;
		update_post_meta( $challenge_id, self::META_CHALLENGED_TEAM_ID, $team_id );
		update_post_meta( $challenge_id, self::META_CHALLENGER_USER_ID, $uid );
		update_post_meta( $challenge_id, self::META_CHALLENGER_NAME, $name );
		update_post_meta( $challenge_id, self::META_CHALLENGER_EMAIL, $email );
		update_post_meta( $challenge_id, self::META_MESSAGE, $msg );
		update_post_meta( $challenge_id, self::META_PROPOSED_SCHEDULED_AT, $scheduled_gmt );
		update_post_meta( $challenge_id, self::META_CHALLENGER_TEAM_ID, $challenger_team_id );
		update_post_meta( $challenge_id, self::META_REMOTE_TEAM_URL, $opponent_url );
		update_post_meta( $challenge_id, self::META_REMOTE_SNAPSHOT, wp_json_encode( $snapshot ) );
		update_post_meta( $challenge_id, self::META_STATUS, self::STATUS_PENDING );
		update_post_meta( $challenge_id, self::META_MATCH_ID, 0 );
		update_post_meta( $challenge_id, self::META_EVENT_ID, 0 );
		update_post_meta( $challenge_id, self::META_CHALLENGER_LOGO_ATTACHMENT_ID, $stored_logo_attachment_id );

		$this->notify_team_admins_of_challenge( $challenge_id, $team_id, $uid, $name );

		/**
		 * Fires after a team challenge is stored and notifications are queued.
		 *
		 * @param int $challenge_id Challenge post ID (`cp_team_challenge`).
		 * @param int $challenged_team_id Challenged `cp_team` ID.
		 */
		do_action( 'clanspress_team_challenge_created', $challenge_id, $team_id );

		return new WP_REST_Response(
			array(
				'success'      => true,
				'challenge_id' => $challenge_id,
				'message'      => __( 'Challenge sent. The team admins will be notified.', 'clanspress' ),
			),
			201
		);
	}

	/**
	 * Notify every challenged team admin (and site teams admins) about a new challenge.
	 *
	 * @param int    $challenge_id       Challenge post ID.
	 * @param int    $challenged_team_id Team ID.
	 * @param int    $actor_id           Challenger user or 0.
	 * @param string $challenger_name    Display name for the title.
	 * @return void
	 */
	private function notify_team_admins_of_challenge( int $challenge_id, int $challenged_team_id, int $actor_id, string $challenger_name ): void {
		if ( ! function_exists( 'clanspress_notify' ) || ! clanspress_notifications_extension_active() ) {
			return;
		}

		$team_title = get_the_title( $challenged_team_id );
		$title      = sprintf(
			/* translators: 1: challenger name, 2: team name */
			__( '%1$s challenged %2$s', 'clanspress' ),
			$challenger_name,
			$team_title
		);

		$url = get_permalink( $challenged_team_id );

		$recipients = $this->get_team_admin_user_ids( $challenged_team_id );
		foreach ( $recipients as $user_id ) {
			if ( $user_id < 1 ) {
				continue;
			}
			clanspress_notify(
				$user_id,
				'team_challenge',
				$title,
				array(
					'message'     => __( 'Review the challenge and accept or decline.', 'clanspress' ),
					'actor_id'    => $actor_id,
					'object_type' => 'team_challenge',
					'object_id'   => $challenge_id,
					'url'         => $url ? $url : home_url( '/' ),
					'dedupe'      => true,
					'actions'     => array(
						array(
							'key'             => 'accept',
							'label'           => __( 'Accept', 'clanspress' ),
							'style'           => 'primary',
							'handler'         => 'team_challenge_accept',
							'status'          => 'accepted',
							'success_message' => __( 'Challenge accepted. Match and event were created.', 'clanspress' ),
						),
						array(
							'key'             => 'decline',
							'label'           => __( 'Decline', 'clanspress' ),
							'style'           => 'secondary',
							'handler'         => 'team_challenge_decline',
							'status'          => 'declined',
							'success_message' => __( 'Challenge declined.', 'clanspress' ),
						),
					),
				)
			);
		}
	}

	/**
	 * User IDs that may accept or decline a challenge for a team.
	 *
	 * @param int $team_id Team post ID.
	 * @return array<int, int>
	 */
	private function get_team_admin_user_ids( int $team_id ): array {
		if ( ! $this->teams || $team_id < 1 ) {
			return array();
		}

		$out = array();
		$map = $this->teams->get_team_member_roles_map( $team_id );

		foreach ( array_keys( $map ) as $uid ) {
			$uid = (int) $uid;
			if ( $uid < 1 ) {
				continue;
			}
			if ( $this->teams->user_is_team_admin_on_frontend( $team_id, $uid ) ) {
				$out[] = $uid;
			}
		}

		/**
		 * Filter who receives team challenge notifications for a team.
		 *
		 * @param array $out     User IDs.
		 * @param int   $team_id Team ID.
		 */
		return array_values( array_unique( array_map( 'intval', (array) apply_filters( 'clanspress_team_challenge_notify_user_ids', $out, $team_id ) ) ) );
	}

	/**
	 * Handle notification primary action: create match (+ optional event + RSVPs).
	 *
	 * @param object $notification Row object.
	 * @param int    $user_id      Acting user.
	 * @return array{success: bool, message: string, redirect?: string|null}
	 */
	public static function handle_notification_accept( object $notification, int $user_id ): array {
		$inst = self::instance();
		return $inst->process_accept( $notification, $user_id );
	}

	/**
	 * Handle decline: mark challenge declined.
	 *
	 * @param object $notification Row object.
	 * @param int    $user_id      Acting user.
	 * @return array{success: bool, message: string}
	 */
	public static function handle_notification_decline( object $notification, int $user_id ): array {
		$inst = self::instance();
		return $inst->process_decline( $notification, $user_id );
	}

	/**
	 * @param object $notification Notification row.
	 * @param int    $user_id      User ID.
	 * @return array{success: bool, message: string, redirect?: string|null}
	 */
	private function process_accept( object $notification, int $user_id ): array {
		if ( ! $this->teams ) {
			$this->teams = clanspress_teams();
		}

		$challenge_id = (int) ( $notification->object_id ?? 0 );
		if ( $challenge_id < 1 ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid challenge.', 'clanspress' ),
			);
		}

		$post = get_post( $challenge_id );
		if ( ! ( $post instanceof WP_Post ) || self::POST_TYPE !== $post->post_type ) {
			return array(
				'success' => false,
				'message' => __( 'Challenge not found.', 'clanspress' ),
			);
		}

		$challenged_team_id = (int) get_post_meta( $challenge_id, self::META_CHALLENGED_TEAM_ID, true );
		if ( $challenged_team_id < 1 || ! $this->user_can_resolve_challenge( $challenged_team_id, $user_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'You cannot manage this challenge.', 'clanspress' ),
			);
		}

		$status = (string) get_post_meta( $challenge_id, self::META_STATUS, true );
		if ( self::STATUS_PENDING !== $status ) {
			return array(
				'success' => false,
				'message' => __( 'This challenge is no longer pending.', 'clanspress' ),
			);
		}

		$existing_match = (int) get_post_meta( $challenge_id, self::META_MATCH_ID, true );
		if ( $existing_match > 0 ) {
			return array(
				'success'  => true,
				'message'  => __( 'Challenge was already accepted.', 'clanspress' ),
				'redirect' => get_permalink( $existing_match ),
			);
		}

		$matches = clanspress_matches();
		if ( ! $matches ) {
			return array(
				'success' => false,
				'message' => __( 'Matches extension is not available.', 'clanspress' ),
			);
		}

		$challenger_team_id = (int) get_post_meta( $challenge_id, self::META_CHALLENGER_TEAM_ID, true );
		$snapshot_raw       = (string) get_post_meta( $challenge_id, self::META_REMOTE_SNAPSHOT, true );
		$snapshot           = json_decode( $snapshot_raw, true );
		if ( ! is_array( $snapshot ) ) {
			$snapshot = array();
		}

		$away_id = $challenger_team_id;
		$ext_label = isset( $snapshot['title'] ) ? sanitize_text_field( (string) $snapshot['title'] ) : '';
		$ext_logo  = isset( $snapshot['logoUrl'] ) ? esc_url_raw( (string) $snapshot['logoUrl'] ) : '';
		$ext_url   = isset( $snapshot['profileUrl'] ) ? esc_url_raw( (string) $snapshot['profileUrl'] ) : '';

		if ( $away_id < 1 && '' === $ext_label ) {
			$ext_label = (string) get_post_meta( $challenge_id, self::META_CHALLENGER_NAME, true );
		}

		$home_title = get_the_title( $challenged_team_id );
		$away_title = $away_id > 0 ? get_the_title( $away_id ) : $ext_label;
		$title      = sprintf( '%s vs %s', $home_title, $away_title );

		$scheduled = (string) get_post_meta( $challenge_id, self::META_PROPOSED_SCHEDULED_AT, true );
		if ( '' === $scheduled ) {
			$scheduled = gmdate( 'Y-m-d H:i:s' );
		}

		$match_id = $this->insert_match_post_for_user(
			$user_id,
			array(
				'post_type'    => 'cp_match',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_author'  => $user_id,
				'post_content' => '',
			)
		);

		if ( is_wp_error( $match_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'Could not create the match.', 'clanspress' ),
			);
		}

		$match_id = (int) $match_id;
		update_post_meta( $match_id, 'cp_match_home_team_id', $challenged_team_id );
		update_post_meta( $match_id, 'cp_match_away_team_id', max( 0, $away_id ) );
		update_post_meta( $match_id, 'cp_match_scheduled_at', $scheduled );
		update_post_meta( $match_id, 'cp_match_status', $matches::STATUS_SCHEDULED );
		update_post_meta( $match_id, 'cp_match_visibility', $matches::VISIBILITY_PUBLIC );

		if ( $away_id < 1 ) {
			update_post_meta( $match_id, 'cp_match_away_external_label', $ext_label );
			update_post_meta( $match_id, 'cp_match_away_external_logo_url', $ext_logo );
			update_post_meta( $match_id, 'cp_match_away_external_profile_url', $ext_url );
		}

		$challenge_logo_id = (int) get_post_meta( $challenge_id, self::META_CHALLENGER_LOGO_ATTACHMENT_ID, true );
		if ( $away_id < 1 && $challenge_logo_id > 0 && function_exists( 'clanspress_relocate_team_challenge_logo_to_match_dir' ) ) {
			if ( clanspress_relocate_team_challenge_logo_to_match_dir( $challenge_logo_id, $challenged_team_id, $match_id ) ) {
				$new_logo = wp_get_attachment_image_url( $challenge_logo_id, 'medium' );
				if ( $new_logo ) {
					$new_logo = esc_url_raw( (string) $new_logo );
					update_post_meta( $match_id, 'cp_match_away_external_logo_url', $new_logo );
					$snapshot['logoUrl'] = $new_logo;
					update_post_meta( $challenge_id, self::META_REMOTE_SNAPSHOT, wp_json_encode( $snapshot ) );
				}
			}
		}

		update_post_meta( $challenge_id, self::META_STATUS, self::STATUS_ACCEPTED );
		update_post_meta( $challenge_id, self::META_MATCH_ID, $match_id );

		$event_id = $this->maybe_create_event_and_invites( $challenged_team_id, $match_id, $title, $scheduled, $user_id );
		if ( $event_id > 0 ) {
			update_post_meta( $challenge_id, self::META_EVENT_ID, $event_id );
		}

		/**
		 * Fires after a team challenge is accepted and the match exists.
		 *
		 * @param int $challenge_id Challenge post ID.
		 * @param int $match_id     New `cp_match` ID.
		 * @param int $challenged_team_id Home team ID.
		 */
		do_action( 'clanspress_team_challenge_accepted', $challenge_id, $match_id, $challenged_team_id );

		if ( class_exists( \Kernowdev\Clanspress\Cross_Site_Match_Sync::class ) ) {
			\Kernowdev\Clanspress\Cross_Site_Match_Sync::maybe_push_mirror_match( $challenge_id, $match_id, $challenged_team_id, $snapshot, $scheduled );
		}

		return array(
			'success'  => true,
			'message'  => __( 'Challenge accepted. Match and event were created.', 'clanspress' ),
			'redirect' => get_permalink( $match_id ),
		);
	}

	/**
	 * @param object $notification Notification row.
	 * @param int    $user_id      User ID.
	 * @return array{success: bool, message: string}
	 */
	private function process_decline( object $notification, int $user_id ): array {
		if ( ! $this->teams ) {
			$this->teams = clanspress_teams();
		}

		$challenge_id = (int) ( $notification->object_id ?? 0 );
		$post         = get_post( $challenge_id );
		if ( ! ( $post instanceof WP_Post ) || self::POST_TYPE !== $post->post_type ) {
			return array(
				'success' => false,
				'message' => __( 'Challenge not found.', 'clanspress' ),
			);
		}

		$challenged_team_id = (int) get_post_meta( $challenge_id, self::META_CHALLENGED_TEAM_ID, true );
		if ( $challenged_team_id < 1 || ! $this->user_can_resolve_challenge( $challenged_team_id, $user_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'You cannot manage this challenge.', 'clanspress' ),
			);
		}

		$logo_aid = (int) get_post_meta( $challenge_id, self::META_CHALLENGER_LOGO_ATTACHMENT_ID, true );
		if ( $logo_aid > 0 && function_exists( 'clanspress_team_challenge_logo_attachment_matches_team' ) && clanspress_team_challenge_logo_attachment_matches_team( $logo_aid, $challenged_team_id ) ) {
			wp_delete_attachment( $logo_aid, true );
		}
		delete_post_meta( $challenge_id, self::META_CHALLENGER_LOGO_ATTACHMENT_ID );

		update_post_meta( $challenge_id, self::META_STATUS, self::STATUS_DECLINED );

		return array(
			'success' => true,
			'message' => __( 'Challenge declined.', 'clanspress' ),
		);
	}

	/**
	 * Whether the user may accept or decline for this team.
	 *
	 * @param int $team_id Team ID.
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private function user_can_resolve_challenge( int $team_id, int $user_id ): bool {
		if ( ! $this->teams || $user_id < 1 ) {
			return false;
		}
		if ( $this->teams->user_is_teams_site_admin( $user_id ) ) {
			return true;
		}

		return $this->teams->user_is_team_admin_on_frontend( $team_id, $user_id );
	}

	/**
	 * Optionally create a team-scoped event and tentative RSVPs for roster members.
	 *
	 * @param int    $team_id    Home team ID.
	 * @param int    $match_id   Match post ID.
	 * @param string $title      Event title.
	 * @param string $scheduled  GMT mysql datetime for match.
	 * @param int    $author_id  User creating the event.
	 * @return int Event post ID or 0.
	 */
	private function maybe_create_event_and_invites( int $team_id, int $match_id, string $title, string $scheduled, int $author_id ): int {
		if ( ! function_exists( 'clanspress_events_extension_active' ) || ! clanspress_events_extension_active() ) {
			return 0;
		}
		if ( ! function_exists( 'clanspress_events_are_globally_enabled' ) || ! clanspress_events_are_globally_enabled() ) {
			return 0;
		}
		if ( ! function_exists( 'clanspress_events_are_enabled_for_team' ) || ! clanspress_events_are_enabled_for_team( $team_id ) ) {
			return 0;
		}

		$event_id = $this->insert_event_post_for_user(
			$author_id,
			array(
				'post_type'    => Event_Post_Type::POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => '',
				'post_author'  => $author_id,
			)
		);

		if ( is_wp_error( $event_id ) || $event_id < 1 ) {
			return 0;
		}

		$event_id = (int) $event_id;
		update_post_meta( $event_id, 'cp_event_scope', Event_Post_Type::SCOPE_TEAM );
		update_post_meta( $event_id, 'cp_event_team_id', $team_id );
		update_post_meta( $event_id, 'cp_event_group_id', 0 );
		update_post_meta( $event_id, 'cp_event_mode', Event_Post_Type::MODE_IN_PERSON );
		update_post_meta( $event_id, 'cp_event_visibility', Event_Post_Type::VISIBILITY_TEAM_MEMBERS );
		update_post_meta( $event_id, 'cp_event_attendees_visibility', 'hidden' );
		update_post_meta( $event_id, 'cp_event_starts_at', $scheduled );

		if ( $this->teams ) {
			$map = $this->teams->get_team_member_roles_map( $team_id );
			foreach ( array_keys( $map ) as $member_id ) {
				$member_id = (int) $member_id;
				if ( $member_id < 1 ) {
					continue;
				}
				$role = $map[ $member_id ] ?? '';
				if ( $this->teams::TEAM_ROLE_BANNED === $role ) {
					continue;
				}
				Event_Rsvp_Data_Access::set_user_rsvp( 'clanspress_event', $event_id, $member_id, Event_Rsvp_Data_Access::STATUS_TENTATIVE );

				if ( function_exists( 'clanspress_notify' ) && clanspress_notifications_extension_active() ) {
					$event_url = $this->build_team_event_permalink( $team_id, $event_id );
					clanspress_notify(
						$member_id,
						'team_match_event',
						__( 'New match scheduled for your team', 'clanspress' ),
						array(
							'message' => __( 'You have been added to the match event. Please confirm your attendance.', 'clanspress' ),
							'url'     => $event_url ? $event_url : get_permalink( $match_id ),
							'dedupe'  => false,
						)
					);
				}
			}
		}

		return $event_id;
	}

	/**
	 * Build `/teams/{slug}/events/{id}/` permalink for a team-scoped event.
	 *
	 * @param int $team_id  Team post ID.
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	private function build_team_event_permalink( int $team_id, int $event_id ): string {
		$team_post = get_post( $team_id );
		if ( ! ( $team_post instanceof WP_Post ) || 'cp_team' !== $team_post->post_type ) {
			return '';
		}
		$slug = (string) $team_post->post_name;
		if ( '' === $slug ) {
			return '';
		}

		return trailingslashit( home_url( '/teams/' . rawurlencode( $slug ) . '/events/' . $event_id ) );
	}

	/**
	 * HTTP GET discovery + public-team from a remote install.
	 *
	 * @param string $profile_url Team profile or site URL.
	 * @return array{clanspress: bool, origin?: string, team?: array<string, mixed>}|WP_Error
	 */
	private function fetch_remote_team_bundle( string $profile_url ): array|WP_Error {
		$profile_url = esc_url_raw( $profile_url );
		if ( '' === $profile_url ) {
			return new WP_Error( 'clanspress_remote_url', __( 'Enter a valid team URL.', 'clanspress' ), array( 'status' => 400 ) );
		}

		$parsed = function_exists( 'clanspress_parse_team_profile_url' ) ? clanspress_parse_team_profile_url( $profile_url ) : null;
		$origin = is_array( $parsed ) && ! empty( $parsed['origin'] ) ? $parsed['origin'] : '';
		$slug   = is_array( $parsed ) && ! empty( $parsed['slug'] ) ? (string) $parsed['slug'] : '';

		if ( '' === $origin ) {
			$parts = wp_parse_url( $profile_url );
			if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
				return new WP_Error( 'clanspress_remote_url', __( 'Could not read that URL.', 'clanspress' ), array( 'status' => 400 ) );
			}
			$scheme = isset( $parts['scheme'] ) && 'http' === strtolower( (string) $parts['scheme'] ) ? 'http' : 'https';
			$host   = strtolower( (string) $parts['host'] );
			$port   = isset( $parts['port'] ) ? ':' . (int) $parts['port'] : '';
			$origin = $scheme . '://' . $host . $port;
		}

		$discovery = wp_remote_get(
			trailingslashit( $origin ) . 'wp-json/clanspress/v1/discovery',
			array(
				'timeout' => 8,
			)
		);
		if ( is_wp_error( $discovery ) ) {
			return new WP_Error( 'clanspress_remote_unreachable', __( 'Could not reach the remote site.', 'clanspress' ), array( 'status' => 502 ) );
		}
		$code = (int) wp_remote_retrieve_response_code( $discovery );
		if ( 200 !== $code ) {
			return new WP_Error( 'clanspress_remote_no_clanspress', __( 'That site does not appear to run Clanspress.', 'clanspress' ), array( 'status' => 404 ) );
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $discovery ), true );
		if ( ! is_array( $body ) || empty( $body['clanspress'] ) ) {
			return new WP_Error( 'clanspress_remote_no_clanspress', __( 'That site does not appear to run Clanspress.', 'clanspress' ), array( 'status' => 404 ) );
		}

		if ( '' === $slug ) {
			return array(
				'clanspress' => true,
				'origin'     => $origin,
			);
		}

		$team_url = trailingslashit( $origin ) . 'wp-json/clanspress/v1/public-team?slug=' . rawurlencode( $slug );
		$team_res = wp_remote_get( $team_url, array( 'timeout' => 8 ) );
		if ( is_wp_error( $team_res ) ) {
			return new WP_Error( 'clanspress_remote_team', __( 'Could not load the remote team.', 'clanspress' ), array( 'status' => 502 ) );
		}
		if ( 200 !== (int) wp_remote_retrieve_response_code( $team_res ) ) {
			return new WP_Error( 'clanspress_remote_team', __( 'Remote team was not found.', 'clanspress' ), array( 'status' => 404 ) );
		}
		$team_body = json_decode( (string) wp_remote_retrieve_body( $team_res ), true );

		return array(
			'clanspress' => true,
			'origin'     => $origin,
			'team'       => is_array( $team_body ) ? $team_body : array(),
		);
	}

	/**
	 * Simple per-IP rate limit for anonymous challenge spam.
	 *
	 * @return bool True when allowed.
	 */
	private function rate_limit_challenge_submission(): bool {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'clanspress_ch_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n > 20 ) {
			return false;
		}
		set_transient( $key, $n + 1, HOUR_IN_SECONDS );

		return true;
	}

	/**
	 * Insert challenge CPT bypassing default caps (guest-friendly).
	 *
	 * @param array<string, mixed> $postarr Post args.
	 * @return int|WP_Error
	 */
	private function insert_challenge_post( array $postarr ): int|WP_Error {
		return $this->insert_post_with_grant( $postarr );
	}

	/**
	 * Insert match as the accepting user with a temporary capability grant.
	 *
	 * @param int                    $user_id Acting user.
	 * @param array<string, mixed>   $postarr Post args.
	 * @return int|WP_Error
	 */
	private function insert_match_post_for_user( int $user_id, array $postarr ): int|WP_Error {
		return $this->insert_post_with_grant( $postarr, $user_id );
	}

	/**
	 * Insert event as the accepting user with a temporary capability grant.
	 *
	 * @param int                    $user_id Acting user.
	 * @param array<string, mixed>   $postarr Post args.
	 * @return int|WP_Error
	 */
	private function insert_event_post_for_user( int $user_id, array $postarr ): int|WP_Error {
		return $this->insert_post_with_grant( $postarr, $user_id );
	}

	/**
	 * @param array<string, mixed> $postarr  Post array for {@see wp_insert_post()}.
	 * @param int                  $user_id  User to impersonate for caps (0 = current / guest).
	 * @return int|WP_Error
	 */
	private function insert_post_with_grant( array $postarr, int $user_id = 0 ): int|WP_Error {
		$prev = (int) get_current_user_id();
		if ( $user_id > 0 ) {
			wp_set_current_user( $user_id );
		}

		$filter = static function ( $allcaps, $caps ) {
			if ( ! isset( $caps[0] ) ) {
				return $allcaps;
			}
			if ( in_array( $caps[0], array( 'create_posts', 'edit_posts', 'publish_posts' ), true ) ) {
				$allcaps['create_posts']  = true;
				$allcaps['edit_posts']    = true;
				$allcaps['publish_posts'] = true;
			}
			return $allcaps;
		};

		add_filter( 'user_has_cap', $filter, 999, 2 );
		$result = wp_insert_post( $postarr, true );
		remove_filter( 'user_has_cap', $filter, 999 );

		wp_set_current_user( $prev );

		return $result;
	}
}
