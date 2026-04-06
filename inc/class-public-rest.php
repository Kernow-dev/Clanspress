<?php
/**
 * Unauthenticated REST endpoints for cross-site discovery and public team metadata.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress;

defined( 'ABSPATH' ) || exit;


use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Registers read-only routes under `clanspress/v1` that do not require a logged-in user.
 */
final class Public_Rest {

	/**
	 * Register routes on `rest_api_init`.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		register_rest_route(
			'clanspress/v1',
			'/discovery',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'handle_discovery' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'clanspress/v1',
			'/site-sync-public-key',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( Cross_Site_Match_Sync::class, 'rest_site_sync_public_key' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'clanspress/v1',
			'/public-team',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'handle_public_team' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'slug' => array(
						'description'       => __( 'Team post slug (`cp_team` post_name).', 'clanspress' ),
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_title',
					),
					'url'  => array(
						'description'       => __( 'Full URL to a team profile page on a Clanspress site.', 'clanspress' ),
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'esc_url_raw',
					),
				),
			)
		);
	}

	/**
	 * Discovery payload so remote sites can detect Clanspress and a compatible API version.
	 *
	 * @param WP_REST_Request $request Request (unused).
	 * @return WP_REST_Response
	 */
	public static function handle_discovery( WP_REST_Request $request ) {
		unset( $request );

		$discovery = array(
			'clanspress' => true,
			'name'       => 'Clanspress',
			'version'    => Main::VERSION,
		);

		if ( function_exists( 'sodium_crypto_sign_keypair' ) ) {
			$discovery['match_sync'] = array(
				'ed25519' => true,
				'public_key_route' => 'clanspress/v1/site-sync-public-key',
			);
		}

		return new WP_REST_Response( $discovery, 200 );
	}

	/**
	 * Return basic public data for a published team by slug or by parsing a profile URL.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_public_team( WP_REST_Request $request ) {
		$slug = (string) $request->get_param( 'slug' );
		$url  = (string) $request->get_param( 'url' );

		if ( '' === $slug && '' !== $url ) {
			$parsed = function_exists( 'clanspress_parse_team_profile_url' ) ? clanspress_parse_team_profile_url( $url ) : null;
			if ( is_array( $parsed ) && ! empty( $parsed['slug'] ) ) {
				$slug = (string) $parsed['slug'];
			}
		}

		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return new WP_Error(
				'clanspress_public_team_missing_slug',
				__( 'A team slug or profile URL is required.', 'clanspress' ),
				array( 'status' => 400 )
			);
		}

		$posts = get_posts(
			array(
				'name'                   => $slug,
				'post_type'              => 'cp_team',
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);

		$post = isset( $posts[0] ) && $posts[0] instanceof \WP_Post ? $posts[0] : null;
		if ( ! $post ) {
			return new WP_Error(
				'clanspress_public_team_not_found',
				__( 'Team not found.', 'clanspress' ),
				array( 'status' => 404 )
			);
		}

		$team_id  = (int) $post->ID;
		$logo_url = function_exists( 'clanspress_teams_get_display_team_avatar' )
			? clanspress_teams_get_display_team_avatar( $team_id, false, '', 'public_rest', 'medium' )
			: '';
		if ( '' === $logo_url && has_post_thumbnail( $team_id ) ) {
			$logo_url = (string) get_the_post_thumbnail_url( $team_id, 'medium' );
		}
		if ( '' === $logo_url && function_exists( 'clanspress_teams_get_default_avatar_url' ) ) {
			$logo_url = clanspress_teams_get_default_avatar_url( $team_id );
		}

		$motto   = '';
		$team_en = function_exists( 'clanspress_get_team' ) ? clanspress_get_team( $team_id ) : null;
		if ( $team_en && method_exists( $team_en, 'get_motto' ) ) {
			$motto = (string) $team_en->get_motto();
		}

		$country = '';
		if ( $team_en && method_exists( $team_en, 'get_country' ) ) {
			$country = (string) $team_en->get_country();
		} elseif ( '' !== (string) get_post_meta( $team_id, 'cp_team_country', true ) ) {
			$country = (string) get_post_meta( $team_id, 'cp_team_country', true );
		}

		$data = array(
			'id'          => $team_id,
			'title'       => get_the_title( $post ),
			'slug'        => $post->post_name,
			'permalink'   => get_permalink( $post ),
			'logoUrl'     => $logo_url,
			'motto'       => $motto,
			'country'     => $country,
			'description' => wp_strip_all_tags( (string) $post->post_excerpt ),
		);

		/**
		 * Filter the public team payload exposed to other Clanspress sites.
		 *
		 * @param array   $data    Response data.
		 * @param WP_Post $post    Team post.
		 */
		return new WP_REST_Response( (array) apply_filters( 'clanspress_public_team_response', $data, $post ), 200 );
	}
}
