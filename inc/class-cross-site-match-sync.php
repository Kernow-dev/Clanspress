<?php
/**
 * Peer match sync: when a challenge is accepted on site A, mirror the match onto site B (challenger’s Clanspress install).
 *
 * Uses per-install Ed25519 keys (libsodium). The sending site signs the payload; the receiving site fetches the sender’s
 * public key from `GET …/wp-json/clanspress/v1/site-sync-public-key` and verifies the signature. No shared manual secret.
 * Optional legacy HMAC remains available via the {@see 'clanspress_cross_site_sync_key'} filter or a stored
 * `cross_site_sync_key` value in general settings (not exposed in the UI).
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress;
defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanspress\Admin\General_Settings;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Registers {@see Cross_Site_Match_Sync::handle_incoming()} and pushes outbound mirrors after accept.
 */
final class Cross_Site_Match_Sync {

	public const META_SYNC_SOURCE_SITE     = 'cp_match_sync_source_site';
	public const META_SYNC_SOURCE_MATCH_ID = 'cp_match_sync_source_match_id';

	/**
	 * Option holding base64-encoded Ed25519 keypair (auto-generated).
	 */
	private const OPTION_SITE_KEYS = 'clanspress_match_sync_site_keys';

	/**
	 * Maximum clock skew for sync signatures (seconds).
	 */
	private const SIGNATURE_MAX_AGE = 600;

	/**
	 * Transient TTL for cached peer public keys (seconds).
	 */
	private const PEER_KEY_CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * Register REST route for incoming mirrored matches.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		register_rest_route(
			'clanspress/v1',
			'/sync-peer-match',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'handle_incoming' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Public key for verifying inbound sync signatures from this install (read-only, unauthenticated).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_site_sync_public_key( WP_REST_Request $request ) {
		unset( $request );

		if ( ! self::sodium_available() ) {
			return new WP_Error(
				'clanspress_sync_no_crypto',
				__( 'Match sync signing is not available (PHP sodium extension missing).', 'clanspress' ),
				array( 'status' => 503 )
			);
		}

		if ( ! self::ensure_site_signing_keys() ) {
			return new WP_Error(
				'clanspress_sync_keys',
				__( 'Could not initialize match sync keys.', 'clanspress' ),
				array( 'status' => 500 )
			);
		}

		$pk = self::get_site_public_key_binary();
		if ( '' === $pk ) {
			return new WP_Error(
				'clanspress_sync_keys',
				__( 'Could not read match sync public key.', 'clanspress' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array(
				'v'           => 1,
				'algorithm'   => 'ed25519',
				'public_key'  => base64_encode( $pk ),
				'clanspress'  => true,
			),
			200
		);
	}

	/**
	 * Whether libsodium signing primitives are available.
	 *
	 * @return bool
	 */
	private static function sodium_available(): bool {
		return function_exists( 'sodium_crypto_sign_keypair' )
			&& function_exists( 'sodium_crypto_sign_detached' )
			&& function_exists( 'sodium_crypto_sign_verify_detached' );
	}

	/**
	 * Legacy optional shared HMAC secret (UI removed; filter or leftover option value).
	 *
	 * @return string Non-empty enables legacy HMAC mode for outbound + inbound.
	 */
	public static function get_legacy_hmac_key(): string {
		$stored = get_option( General_Settings::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		$key = isset( $stored['cross_site_sync_key'] ) ? (string) $stored['cross_site_sync_key'] : '';
		$key = trim( $key );

		/**
		 * Filter the legacy HMAC key for cross-site match sync (optional).
		 *
		 * When non-empty, requests use the older `timestamp:hex_hmac` header instead of Ed25519 `v1:…`.
		 * Prefer the default automatic Ed25519 handshake; use this only for backward compatibility.
		 *
		 * @param string $key Legacy shared secret; empty uses automatic per-site keys.
		 */
		return (string) apply_filters( 'clanspress_cross_site_sync_key', $key );
	}

	/**
	 * @return bool True when site Ed25519 keys exist or were created.
	 */
	private static function ensure_site_signing_keys(): bool {
		if ( ! self::sodium_available() ) {
			return false;
		}

		$raw = get_option( self::OPTION_SITE_KEYS, null );
		if ( is_array( $raw ) && ! empty( $raw['secret_key'] ) && ! empty( $raw['public_key'] ) ) {
			$sk = base64_decode( (string) $raw['secret_key'], true );
			$pk = base64_decode( (string) $raw['public_key'], true );
			if ( false !== $sk && false !== $pk
				&& SODIUM_CRYPTO_SIGN_SECRETKEYBYTES === strlen( $sk )
				&& SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES === strlen( $pk ) ) {
				return true;
			}
		}

		$keypair = sodium_crypto_sign_keypair();
		$sk      = sodium_crypto_sign_secretkey( $keypair );
		$pk      = sodium_crypto_sign_publickey( $keypair );

		$save = array(
			'v'          => 1,
			'secret_key' => base64_encode( $sk ),
			'public_key' => base64_encode( $pk ),
		);

		return update_option( self::OPTION_SITE_KEYS, $save, false );
	}

	/**
	 * @return string Binary secret key or empty.
	 */
	private static function get_site_secret_key_binary(): string {
		if ( ! self::ensure_site_signing_keys() ) {
			return '';
		}
		$raw = get_option( self::OPTION_SITE_KEYS, array() );
		if ( ! is_array( $raw ) || empty( $raw['secret_key'] ) ) {
			return '';
		}
		$sk = base64_decode( (string) $raw['secret_key'], true );

		return ( false !== $sk && SODIUM_CRYPTO_SIGN_SECRETKEYBYTES === strlen( $sk ) ) ? $sk : '';
	}

	/**
	 * @return string Binary public key or empty.
	 */
	private static function get_site_public_key_binary(): string {
		if ( ! self::ensure_site_signing_keys() ) {
			return '';
		}
		$raw = get_option( self::OPTION_SITE_KEYS, array() );
		if ( ! is_array( $raw ) || empty( $raw['public_key'] ) ) {
			return '';
		}
		$pk = base64_decode( (string) $raw['public_key'], true );

		return ( false !== $pk && SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES === strlen( $pk ) ) ? $pk : '';
	}

	/**
	 * @param string $source_site Normalized site URL (trailing slash).
	 * @return string Binary public key or empty on failure.
	 */
	private static function fetch_peer_public_key( string $source_site ): string {
		$source_site = trailingslashit( esc_url_raw( $source_site ) );
		if ( '' === $source_site || ! wp_http_validate_url( $source_site ) ) {
			return '';
		}

		$cache_key = 'clanspress_sync_pk_v1_' . md5( $source_site );
		$cached    = get_transient( $cache_key );
		if ( is_string( $cached ) && SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES === strlen( $cached ) ) {
			return $cached;
		}

		$url = $source_site . 'wp-json/clanspress/v1/site-sync-public-key';
		$res = wp_remote_get(
			$url,
			array(
				'timeout'     => 10,
				'redirection' => 2,
				'sslverify'   => true,
				'headers'     => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $res ) ) {
			return '';
		}

		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( 200 !== $code ) {
			return '';
		}

		$json = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $json ) || empty( $json['public_key'] ) || empty( $json['clanspress'] ) ) {
			return '';
		}

		$pk = base64_decode( (string) $json['public_key'], true );
		if ( false === $pk || SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES !== strlen( $pk ) ) {
			return '';
		}

		set_transient( $cache_key, $pk, self::PEER_KEY_CACHE_TTL );

		return $pk;
	}

	/**
	 * @param string $bin Raw signature bytes.
	 * @return string URL-safe base64 (no padding).
	 */
	private static function base64url_encode( string $bin ): string {
		return rtrim( strtr( base64_encode( $bin ), '+/', '-_' ), '=' );
	}

	/**
	 * @param string $data URL-safe base64 (padding optional).
	 * @return string Binary or empty.
	 */
	private static function base64url_decode( string $data ): string {
		$data = strtr( $data, '-_', '+/' );
		$pad  = strlen( $data ) % 4;
		if ( $pad > 0 ) {
			$data .= str_repeat( '=', 4 - $pad );
		}
		$out = base64_decode( $data, true );

		return is_string( $out ) ? $out : '';
	}

	/**
	 * After a challenge is accepted on the challenged site, create the same match on the challenger’s Clanspress site when the snapshot came from a remote install.
	 *
	 * @param int    $challenge_id       `cp_team_challenge` post ID.
	 * @param int    $match_id           New `cp_match` ID on this site.
	 * @param int    $challenged_team_id Home team on this site.
	 * @param array  $snapshot           Decoded remote snapshot JSON from the challenge record.
	 * @param string $scheduled_gmt      Match schedule (GMT MySQL).
	 * @return void
	 */
	public static function maybe_push_mirror_match( int $challenge_id, int $match_id, int $challenged_team_id, array $snapshot, string $scheduled_gmt ): void {
		$legacy_hmac = '' !== self::get_legacy_hmac_key();
		if ( ! $legacy_hmac && ! self::sodium_available() ) {
			return;
		}

		if ( ( $snapshot['source'] ?? '' ) !== 'remote' ) {
			return;
		}

		$origin         = isset( $snapshot['origin'] ) ? esc_url_raw( (string) $snapshot['origin'] ) : '';
		$remote_team_id = isset( $snapshot['remoteTeamId'] ) ? (int) $snapshot['remoteTeamId'] : 0;
		if ( '' === $origin || $remote_team_id < 1 ) {
			return;
		}

		if ( ! function_exists( 'clanspress_matches' ) || ! clanspress_matches() ) {
			return;
		}

		$matches = clanspress_matches();

		$home_team_post = get_post( $challenged_team_id );
		if ( ! ( $home_team_post instanceof \WP_Post ) || 'cp_team' !== $home_team_post->post_type ) {
			return;
		}

		$opponent_label = get_the_title( $challenged_team_id );
		$opponent_url   = get_permalink( $challenged_team_id ) ?: home_url( '/' );
		$opponent_logo  = '';
		$aid            = (int) get_post_meta( $challenged_team_id, 'cp_team_avatar_id', true );
		if ( $aid ) {
			$opponent_logo = (string) wp_get_attachment_image_url( $aid, 'medium' );
		}
		if ( '' === $opponent_logo && has_post_thumbnail( $challenged_team_id ) ) {
			$opponent_logo = (string) get_the_post_thumbnail_url( $challenged_team_id, 'medium' );
		}

		$ts          = time();
		$source_site = trailingslashit( home_url( '/' ) );

		$body = array(
			'v'                         => 1,
			'ts'                        => $ts,
			'source_site'               => $source_site,
			'source_match_id'           => $match_id,
			'source_challenge_id'       => $challenge_id,
			'home_team_id'              => $remote_team_id,
			'away_external_label'       => $opponent_label,
			'away_external_logo_url'    => $opponent_logo,
			'away_external_profile_url' => $opponent_url,
			'scheduled_at'              => $scheduled_gmt,
			'visibility'                => $matches::VISIBILITY_PUBLIC,
		);

		/**
		 * Filter outbound peer-sync payload before signing and POST.
		 *
		 * @param array $body               Payload (do not remove required keys without replacing behavior).
		 * @param int   $challenge_id       Local challenge ID.
		 * @param int   $match_id           Local match ID.
		 * @param int   $challenged_team_id Challenged team ID on this site.
		 * @param array $snapshot           Remote snapshot from the challenge form.
		 */
		$body = (array) apply_filters( 'clanspress_cross_site_sync_outbound_payload', $body, $challenge_id, $match_id, $challenged_team_id, $snapshot );

		$canonical = wp_json_encode( $body );
		if ( ! is_string( $canonical ) ) {
			return;
		}

		$message = (string) $ts . "\n" . $canonical;

		if ( $legacy_hmac ) {
			$key       = self::get_legacy_hmac_key();
			$signature = hash_hmac( 'sha256', $message, $key );
			$header    = (string) $ts . ':' . $signature;
		} else {
			if ( ! self::ensure_site_signing_keys() ) {
				return;
			}
			$sk = self::get_site_secret_key_binary();
			if ( '' === $sk ) {
				return;
			}
			$sig    = sodium_crypto_sign_detached( $message, $sk );
			$header = 'v1:' . (string) $ts . ':' . self::base64url_encode( $sig );
		}

		$url      = trailingslashit( $origin ) . 'wp-json/clanspress/v1/sync-peer-match';
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'headers' => array(
					'Content-Type'      => 'application/json',
					'X-Clanspress-Sync' => $header,
					'User-Agent'        => 'Clanspress/' . Main::VERSION . '; ' . home_url( '/' ),
				),
				'body'    => $canonical,
			)
		);

		if ( is_wp_error( $response ) ) {
			/**
			 * Fires when outbound peer match sync fails (HTTP transport or WP_Error).
			 *
			 * @param \WP_Error $response Error object.
			 * @param string    $url      Remote REST URL.
			 * @param array     $body     Payload that was sent.
			 */
			do_action( 'clanspress_cross_site_sync_push_failed', $response, $url, $body );
			return;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( 200 !== $code && 201 !== $code || ! is_array( $data ) || empty( $data['match_id'] ) ) {
			/**
			 * Fires when the peer returns a non-success sync response.
			 *
			 * @param int    $code HTTP status.
			 * @param array  $data Decoded body (may be empty).
			 * @param string $url  Remote REST URL.
			 * @param array  $body Outbound payload.
			 */
			do_action( 'clanspress_cross_site_sync_push_rejected', $code, is_array( $data ) ? $data : array(), $url, $body );
			return;
		}

		$peer_match_id = (int) $data['match_id'];
		if ( $peer_match_id > 0 ) {
			update_post_meta( $challenge_id, 'cp_challenge_peer_mirror_match_id', $peer_match_id );
			update_post_meta( $challenge_id, 'cp_challenge_peer_mirror_site', $origin );
		}

		/**
		 * Fires after a peer site successfully stored the mirrored match.
		 *
		 * @param int    $challenge_id   Local challenge ID.
		 * @param int    $local_match_id Local match ID.
		 * @param int    $peer_match_id  Match ID on the peer site.
		 * @param string $peer_origin    Peer site origin URL.
		 * @param array  $body           Outbound payload.
		 */
		do_action( 'clanspress_cross_site_sync_push_succeeded', $challenge_id, $match_id, $peer_match_id, $origin, $body );
	}

	/**
	 * Create or return an existing mirrored `cp_match` from a signed POST.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_incoming( WP_REST_Request $request ) {
		$raw  = $request->get_body();
		$body = json_decode( $raw, true );
		if ( ! is_array( $body ) ) {
			return new WP_Error(
				'clanspress_sync_bad_json',
				__( 'Invalid JSON body.', 'clanspress' ),
				array( 'status' => 400 )
			);
		}

		$header = $request->get_header( 'x-clanspress-sync' );
		if ( ! is_string( $header ) || '' === trim( $header ) ) {
			return new WP_Error(
				'clanspress_sync_bad_header',
				__( 'Missing sync signature header.', 'clanspress' ),
				array( 'status' => 403 )
			);
		}

		$canonical = wp_json_encode( $body );
		if ( ! is_string( $canonical ) ) {
			return new WP_Error(
				'clanspress_sync_encode',
				__( 'Could not canonicalize payload.', 'clanspress' ),
				array( 'status' => 500 )
			);
		}

		$legacy_hmac = '' !== self::get_legacy_hmac_key();
		$v1_header   = str_starts_with( trim( $header ), 'v1:' );

		if ( $v1_header ) {
			if ( ! self::sodium_available() ) {
				return new WP_Error(
					'clanspress_sync_no_crypto',
					__( 'Match sync signing is not available (PHP sodium extension missing).', 'clanspress' ),
					array( 'status' => 503 )
				);
			}

			$parts = explode( ':', trim( $header ), 3 );
			if ( 3 !== count( $parts ) || 'v1' !== $parts[0] ) {
				return new WP_Error(
					'clanspress_sync_bad_header',
					__( 'Invalid sync signature header.', 'clanspress' ),
					array( 'status' => 403 )
				);
			}

			$ts     = (int) $parts[1];
			$sig_in = self::base64url_decode( $parts[2] );
			if ( $ts < 1 || '' === $sig_in ) {
				return new WP_Error(
					'clanspress_sync_ts',
					__( 'Invalid sync timestamp.', 'clanspress' ),
					array( 'status' => 403 )
				);
			}

			if ( ! isset( $body['ts'] ) || (int) $body['ts'] !== $ts ) {
				return new WP_Error(
					'clanspress_sync_ts',
					__( 'Invalid sync timestamp.', 'clanspress' ),
					array( 'status' => 403 )
				);
			}

			if ( abs( time() - $ts ) > self::SIGNATURE_MAX_AGE ) {
				return new WP_Error(
					'clanspress_sync_expired',
					__( 'Sync signature expired.', 'clanspress' ),
					array( 'status' => 403 )
				);
			}

			$source_site_pre = isset( $body['source_site'] ) ? esc_url_raw( (string) $body['source_site'] ) : '';
			$peer_pk         = self::fetch_peer_public_key( $source_site_pre );
			if ( '' === $peer_pk ) {
				return new WP_Error(
					'clanspress_sync_peer_key',
					__( 'Could not retrieve the sending site’s public key. Ensure the other site runs Clanspress and exposes the sync public key endpoint.', 'clanspress' ),
					array( 'status' => 403 )
				);
			}

			$message  = (string) $ts . "\n" . $canonical;
			$verified = sodium_crypto_sign_verify_detached( $sig_in, $message, $peer_pk );
			if ( ! $verified ) {
				return new WP_Error(
					'clanspress_sync_sig',
					__( 'Invalid sync signature.', 'clanspress' ),
					array( 'status' => 403 )
				);
			}
		} else {
			if ( ! $legacy_hmac ) {
				return new WP_Error(
					'clanspress_sync_bad_header',
					__( 'Unsupported sync signature format. Expected a v1 Ed25519 signature from Clanspress.', 'clanspress' ),
					array( 'status' => 403 )
				);
			}

			$key = self::get_legacy_hmac_key();
			if ( '' === $key ) {
				return new WP_Error(
					'clanspress_sync_disabled',
					__( 'Cross-site match sync is not configured on this site.', 'clanspress' ),
					array( 'status' => 403 )
				);
			}

			if ( ! str_contains( $header, ':' ) ) {
				return new WP_Error(
					'clanspress_sync_bad_header',
					__( 'Missing sync signature header.', 'clanspress' ),
					array( 'status' => 403 )
				);
			}

			list( $ts_raw, $sig_in ) = array_map( 'trim', explode( ':', $header, 2 ) );
			$ts = (int) $ts_raw;
			if ( $ts < 1 || ! isset( $body['ts'] ) || (int) $body['ts'] !== $ts ) {
				return new WP_Error(
					'clanspress_sync_ts',
					__( 'Invalid sync timestamp.', 'clanspress' ),
					array( 'status' => 403 )
				);
			}

			if ( abs( time() - $ts ) > self::SIGNATURE_MAX_AGE ) {
				return new WP_Error(
					'clanspress_sync_expired',
					__( 'Sync signature expired.', 'clanspress' ),
					array( 'status' => 403 )
				);
			}

			$message  = (string) $ts . "\n" . $canonical;
			$expected = hash_hmac( 'sha256', $message, $key );
			if ( ! hash_equals( $expected, $sig_in ) ) {
				return new WP_Error(
					'clanspress_sync_sig',
					__( 'Invalid sync signature.', 'clanspress' ),
					array( 'status' => 403 )
				);
			}
		}

		$source_site_for_filter = isset( $body['source_site'] ) ? esc_url_raw( (string) $body['source_site'] ) : '';
		$source_host_for_filter = wp_parse_url( $source_site_for_filter, PHP_URL_HOST );
		$source_host_for_filter = is_string( $source_host_for_filter ) ? strtolower( $source_host_for_filter ) : '';

		/**
		 * Filter incoming peer-sync body after signature verification (mutate with care).
		 *
		 * @param array  $body         Decoded JSON body.
		 * @param string $source_host Normalized host from `source_site` (may be empty before you fix `source_site`).
		 */
		$body = (array) apply_filters( 'clanspress_cross_site_sync_incoming_payload', $body, $source_host_for_filter );

		$source_site     = isset( $body['source_site'] ) ? esc_url_raw( (string) $body['source_site'] ) : '';
		$source_match_id = isset( $body['source_match_id'] ) ? (int) $body['source_match_id'] : 0;
		$home_team_id    = isset( $body['home_team_id'] ) ? (int) $body['home_team_id'] : 0;

		$source_host = wp_parse_url( $source_site, PHP_URL_HOST );
		$source_host = is_string( $source_host ) ? strtolower( $source_host ) : '';

		/**
		 * Whether to accept an incoming sync from this source host after the signature is valid.
		 *
		 * @param bool   $allow       Default true.
		 * @param string $source_host Normalized host from `source_site`.
		 * @param array  $body        Full decoded body.
		 */
		$allowed = (bool) apply_filters( 'clanspress_cross_site_sync_verify_source', true, $source_host, $body );
		if ( ! $allowed ) {
			return new WP_Error(
				'clanspress_sync_source',
				__( 'This source is not allowed to sync matches.', 'clanspress' ),
				array( 'status' => 403 )
			);
		}

		if ( $source_site === '' || $source_match_id < 1 || $home_team_id < 1 ) {
			return new WP_Error(
				'clanspress_sync_fields',
				__( 'Missing required sync fields.', 'clanspress' ),
				array( 'status' => 400 )
			);
		}

		if ( ! function_exists( 'clanspress_matches' ) || ! clanspress_matches() ) {
			return new WP_Error(
				'clanspress_sync_no_matches',
				__( 'Matches are not available.', 'clanspress' ),
				array( 'status' => 503 )
			);
		}

		$matches = clanspress_matches();

		$team_post = get_post( $home_team_id );
		if ( ! ( $team_post instanceof \WP_Post ) || 'cp_team' !== $team_post->post_type || 'publish' !== $team_post->post_status ) {
			return new WP_Error(
				'clanspress_sync_team',
				__( 'Home team not found.', 'clanspress' ),
				array( 'status' => 404 )
			);
		}

		$existing = get_posts(
			array(
				'post_type'              => 'cp_match',
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Single `posts_per_page => 1` duplicate check on indexed sync meta keys.
				'meta_query'             => array(
					'relation' => 'AND',
					array(
						'key'   => self::META_SYNC_SOURCE_SITE,
						'value' => $source_site,
					),
					array(
						'key'   => self::META_SYNC_SOURCE_MATCH_ID,
						'value' => $source_match_id,
					),
				),
			)
		);

		if ( ! empty( $existing[0] ) ) {
			return new WP_REST_Response(
				array(
					'success'   => true,
					'match_id'  => (int) $existing[0],
					'duplicate' => true,
				),
				200
			);
		}

		$label = isset( $body['away_external_label'] ) ? sanitize_text_field( (string) $body['away_external_label'] ) : __( 'Opponent', 'clanspress' );
		$logo  = isset( $body['away_external_logo_url'] ) ? esc_url_raw( (string) $body['away_external_logo_url'] ) : '';
		$prof  = isset( $body['away_external_profile_url'] ) ? esc_url_raw( (string) $body['away_external_profile_url'] ) : '';
		$sched = isset( $body['scheduled_at'] ) ? sanitize_text_field( (string) $body['scheduled_at'] ) : '';
		$sched = $matches->sanitize_scheduled_at( $sched );
		if ( '' === $sched ) {
			$sched = gmdate( 'Y-m-d H:i:s' );
		}

		$vis = isset( $body['visibility'] ) ? (string) $body['visibility'] : $matches::VISIBILITY_PUBLIC;
		$vis = $matches->sanitize_match_visibility( $vis );

		$home_title = get_the_title( $home_team_id );
		$title      = sprintf( '%s vs %s', $home_title, $label );

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
		$match_id = wp_insert_post(
			array(
				'post_type'    => 'cp_match',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_author'  => (int) $team_post->post_author,
				'post_content' => '',
			),
			true
		);
		remove_filter( 'user_has_cap', $filter, 999 );

		if ( is_wp_error( $match_id ) ) {
			return $match_id;
		}

		$match_id = (int) $match_id;
		update_post_meta( $match_id, 'cp_match_home_team_id', $home_team_id );
		update_post_meta( $match_id, 'cp_match_away_team_id', 0 );
		update_post_meta( $match_id, 'cp_match_scheduled_at', $sched );
		update_post_meta( $match_id, 'cp_match_status', $matches::STATUS_SCHEDULED );
		update_post_meta( $match_id, 'cp_match_visibility', $vis );
		update_post_meta( $match_id, 'cp_match_away_external_label', $label );
		update_post_meta( $match_id, 'cp_match_away_external_logo_url', $logo );
		update_post_meta( $match_id, 'cp_match_away_external_profile_url', $prof );
		update_post_meta( $match_id, self::META_SYNC_SOURCE_SITE, $source_site );
		update_post_meta( $match_id, self::META_SYNC_SOURCE_MATCH_ID, $source_match_id );

		/**
		 * Fires after this site created a match from a peer sync request.
		 *
		 * @param int   $match_id     New local `cp_match` ID.
		 * @param array $body         Request body.
		 * @param int   $home_team_id Local home team ID.
		 */
		do_action( 'clanspress_cross_site_sync_incoming_created', $match_id, $body, $home_team_id );

		return new WP_REST_Response(
			array(
				'success'  => true,
				'match_id' => $match_id,
			),
			201
		);
	}
}
