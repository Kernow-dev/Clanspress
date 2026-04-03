<?php
/**
 * REST API collection for match resources (`clanspress/v1/matches`).
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Extensions\Matches;

use WP_Post;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Registers read-only routes for theme and headless consumers.
 */
class Rest_Controller {

	/**
	 * Owning extension (for shaping response payloads).
	 *
	 * @var \Kernowdev\Clanspress\Extensions\Matches
	 */
	protected \Kernowdev\Clanspress\Extensions\Matches $extension;

	/**
	 * @param \Kernowdev\Clanspress\Extensions\Matches $extension Active Matches extension.
	 */
	public function __construct( \Kernowdev\Clanspress\Extensions\Matches $extension ) {
		$this->extension = $extension;
	}

	/**
	 * Register REST routes on `rest_api_init`.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'clanspress/v1',
			'/matches',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_matches' ),
				'permission_callback' => array( $this, 'read_permission' ),
				'args'                => array(
					'team'     => array(
						'description'       => __( 'Filter matches involving this team (post ID).', 'clanspress' ),
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
					'status'   => array(
						'description'       => __( 'Filter by match status slug.', 'clanspress' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
					),
					'per_page' => array(
						'type'    => 'integer',
						'default' => 10,
						'minimum' => 1,
						'maximum' => 100,
					),
					'page'     => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
					),
					'order'    => array(
						'description' => __( 'Sort scheduled time ascending or descending.', 'clanspress' ),
						'type'        => 'string',
						'enum'        => array( 'asc', 'desc' ),
						'default'     => 'asc',
					),
				),
			)
		);

		register_rest_route(
			'clanspress/v1',
			'/matches/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_match' ),
				'permission_callback' => array( $this, 'read_permission' ),
				'args'                => array(
					'id' => array(
						'type'     => 'integer',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Public read access for route registration.
	 *
	 * The list callback only queries non-published `cp_match` posts for logged-in users and
	 * applies `WP_Query` `perm` => `readable` so results respect `read_post` capabilities.
	 * Each row is still filtered by `Matches::viewer_can_see_match()`. Single-item responses
	 * return 403 when visibility rules deny the viewer.
	 *
	 * @return bool
	 */
	public function read_permission(): bool {
		return true;
	}

	/**
	 * List matches with optional filters and pagination.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_matches( WP_REST_Request $request ) {
		$team_id  = (int) $request->get_param( 'team' );
		$status   = sanitize_key( (string) $request->get_param( 'status' ) );
		$per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$order    = strtolower( (string) $request->get_param( 'order' ) ) === 'desc' ? 'DESC' : 'ASC';

		$allowed_status = array( 'scheduled', 'live', 'finished', 'cancelled' );
		if ( '' !== $status && ! in_array( $status, $allowed_status, true ) ) {
			$status = '';
		}

		$args = array(
			'post_type'      => 'cp_match',
			'post_status'    => array( 'publish' ),
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'meta_value',
			'meta_key'       => 'cp_match_scheduled_at',
			'meta_type'      => 'DATETIME',
			'order'          => $order,
		);

		if ( is_user_logged_in() ) {
			$args['post_status'] = array(
				'publish',
				'future',
				'draft',
				'pending',
				'private',
			);
			$args['perm']       = 'readable';
		}

		$meta_query = $this->build_list_meta_query( $team_id, $status );
		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		$query = new \WP_Query( $args );
		$items = array();
		$viewer_id = is_user_logged_in() ? (int) get_current_user_id() : 0;
		foreach ( $query->posts as $post ) {
			if ( $post instanceof WP_Post ) {
				if ( ! $this->extension->viewer_can_see_match( $post, $viewer_id ) ) {
					continue;
				}
				$items[] = $this->extension->match_to_rest_array( $post );
			}
		}

		return rest_ensure_response(
			array(
				'matches' => $items,
				'total'   => (int) $query->found_posts,
				'pages'   => (int) $query->max_num_pages,
			)
		);
	}

	/**
	 * Combine team and status constraints for `WP_Query::meta_query`.
	 *
	 * @param int    $team_id Team post ID or 0.
	 * @param string $status  Status slug or empty.
	 * @return array<int|string, mixed>
	 */
	protected function build_list_meta_query( int $team_id, string $status ): array {
		$parts = array();

		if ( $team_id > 0 ) {
			$parts[] = array(
				'relation' => 'OR',
				array(
					'key'   => 'cp_match_home_team_id',
					'value' => $team_id,
				),
				array(
					'key'   => 'cp_match_away_team_id',
					'value' => $team_id,
				),
			);
		}

		if ( '' !== $status ) {
			$parts[] = array(
				'key'   => 'cp_match_status',
				'value' => $status,
			);
		}

		if ( count( $parts ) === 0 ) {
			return array();
		}

		if ( count( $parts ) === 1 ) {
			return $parts[0];
		}

		return array_merge( array( 'relation' => 'AND' ), $parts );
	}

	/**
	 * Fetch a single match by post ID.
	 *
	 * @param WP_REST_Request $request Request with `id` param.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_match( WP_REST_Request $request ) {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );
		if ( ! $post || 'cp_match' !== $post->post_type ) {
			return new \WP_Error(
				'clanspress_match_not_found',
				__( 'Match not found.', 'clanspress' ),
				array( 'status' => 404 )
			);
		}

		$viewer_id = is_user_logged_in() ? (int) get_current_user_id() : 0;
		if ( ! $this->extension->viewer_can_see_match( $post, $viewer_id ) ) {
			return new \WP_Error(
				'clanspress_match_forbidden',
				__( 'You cannot view this match.', 'clanspress' ),
				array( 'status' => 403 )
			);
		}

		return rest_ensure_response( $this->extension->match_to_rest_array( $post ) );
	}
}
