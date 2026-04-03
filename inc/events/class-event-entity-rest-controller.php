<?php
/**
 * REST API for `cp_event` posts (create/list/update from the front-end).
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Events;
defined( 'ABSPATH' ) || exit;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST endpoints for scheduled events (`cp_event`).
 */
final class Event_Entity_Rest_Controller extends WP_REST_Controller {

	/**
	 * Namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'clanspress/v1';

	/**
	 * Base.
	 *
	 * @var string
	 */
	protected $rest_base = 'event-posts';

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Collection query args.
	 *
	 * @return array<string, mixed>
	 */
	public function get_collection_params(): array {
		return array(
			'team_id'    => array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'group_id'   => array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'per_page'   => array(
				'type'              => 'integer',
				'default'           => 20,
				'sanitize_callback' => 'absint',
			),
			'page'       => array(
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'search'     => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'time_scope' => array(
				'type'              => 'string',
				'default'           => 'all',
				'sanitize_callback' => 'sanitize_key',
			),
			'order'          => array(
				'type'              => 'string',
				'default'           => 'asc',
				'sanitize_callback' => 'sanitize_key',
			),
			'player_user_id' => array(
				'description'       => __( 'Merged calendar for this user’s team/group memberships (viewer must be that user, or a user editor).', 'clanspress' ),
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'starts_after'   => array(
				'description'       => __( 'Only events starting at or after this instant (ISO 8601 UTC).', 'clanspress' ),
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'starts_before'  => array(
				'description'       => __( 'Only events starting at or before this instant (ISO 8601 UTC).', 'clanspress' ),
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * List events for a team, group, or merged player calendar (filtered by visibility per item).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {
		$request = rest_ensure_request( $request );

		$team_id        = (int) $request->get_param( 'team_id' );
		$group_id       = (int) $request->get_param( 'group_id' );
		$player_user_id = (int) $request->get_param( 'player_user_id' );

		if ( $player_user_id > 0 && ( $team_id > 0 || $group_id > 0 ) ) {
			return new WP_Error(
				'clanspress_event_invalid_query',
				__( 'Use either player_user_id or team_id/group_id, not both.', 'clanspress' ),
				array( 'status' => 400 )
			);
		}

		if ( $player_user_id > 0 ) {
			return $this->get_items_for_profile_owner( $request, $player_user_id );
		}

		if ( $team_id <= 0 && $group_id <= 0 ) {
			return new WP_REST_Response(
				array(
					'items' => array(),
					'total' => 0,
				),
				200
			);
		}

		if ( $team_id > 0 ) {
			$err = $this->assert_team_events_allowed( $team_id );
			if ( $err instanceof WP_Error ) {
				return $err;
			}
			$meta_query = array(
				'relation' => 'AND',
				array(
					'key'   => 'cp_event_scope',
					'value' => Event_Post_Type::SCOPE_TEAM,
				),
				array(
					'key'   => 'cp_event_team_id',
					'value' => $team_id,
				),
			);
		} else {
			$err = $this->assert_group_events_allowed( $group_id );
			if ( $err instanceof WP_Error ) {
				return $err;
			}
			$meta_query = array(
				'relation' => 'AND',
				array(
					'key'   => 'cp_event_scope',
					'value' => Event_Post_Type::SCOPE_GROUP,
				),
				array(
					'key'   => 'cp_event_group_id',
					'value' => $group_id,
				),
			);
		}

		return $this->query_events_with_request_filters( $request, $meta_query );
	}

	/**
	 * Merged list of team + group events visible to a profile owner (or privileged editors).
	 *
	 * @param WP_REST_Request $request        Request.
	 * @param int             $player_user_id User whose memberships drive the query.
	 * @return WP_REST_Response|WP_Error
	 */
	private function get_items_for_profile_owner( WP_REST_Request $request, int $player_user_id ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'clanspress_not_logged_in',
				__( 'You must be logged in.', 'clanspress' ),
				array( 'status' => 401 )
			);
		}

		$viewer = (int) get_current_user_id();
		if ( $player_user_id !== $viewer && ! current_user_can( 'edit_users' ) ) {
			return new WP_Error(
				'clanspress_event_forbidden',
				__( 'You can only load your own event calendar.', 'clanspress' ),
				array( 'status' => 403 )
			);
		}

		if ( ! function_exists( 'clanspress_events_are_globally_enabled' ) || ! clanspress_events_are_globally_enabled() ) {
			return new WP_Error(
				'clanspress_events_disabled',
				__( 'Events are disabled on this site.', 'clanspress' ),
				array( 'status' => 403 )
			);
		}

		$team_ids  = function_exists( 'clanspress_events_get_user_team_ids_for_calendar' )
			? clanspress_events_get_user_team_ids_for_calendar( $player_user_id )
			: array();
		$group_ids = function_exists( 'clanspress_events_get_user_group_ids_for_calendar' )
			? clanspress_events_get_user_group_ids_for_calendar( $player_user_id )
			: array();

		$meta_or = array( 'relation' => 'OR' );

		foreach ( $team_ids as $tid ) {
			$tid = (int) $tid;
			if ( $tid < 1 || ! function_exists( 'clanspress_events_are_enabled_for_team' ) || ! clanspress_events_are_enabled_for_team( $tid ) ) {
				continue;
			}
			$meta_or[] = array(
				'relation' => 'AND',
				array(
					'key'   => 'cp_event_scope',
					'value' => Event_Post_Type::SCOPE_TEAM,
				),
				array(
					'key'   => 'cp_event_team_id',
					'value' => $tid,
				),
			);
		}

		foreach ( $group_ids as $gid ) {
			$gid = (int) $gid;
			if ( $gid < 1 || ! function_exists( 'clanspress_events_are_enabled_for_group' ) || ! clanspress_events_are_enabled_for_group( $gid ) ) {
				continue;
			}
			$meta_or[] = array(
				'relation' => 'AND',
				array(
					'key'   => 'cp_event_scope',
					'value' => Event_Post_Type::SCOPE_GROUP,
				),
				array(
					'key'   => 'cp_event_group_id',
					'value' => $gid,
				),
			);
		}

		if ( count( $meta_or ) <= 1 ) {
			return new WP_REST_Response(
				array(
					'items' => array(),
					'total' => 0,
				),
				200
			);
		}

		$meta_query = array(
			'relation' => 'AND',
		);
		$meta_query[] = $meta_or;

		return $this->query_events_with_request_filters( $request, $meta_query );
	}

	/**
	 * Parse an ISO 8601 / RFC3339 datetime as UTC and return `Y-m-d H:i:s` for meta comparison.
	 *
	 * @param string $raw Raw string from the request.
	 * @return string Empty when invalid.
	 */
	private function parse_request_datetime_utc( string $raw ): string {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return '';
		}

		try {
			$dt = new \DateTimeImmutable( $raw, new \DateTimeZone( 'UTC' ) );

			return $dt->format( 'Y-m-d H:i:s' );
		} catch ( \Exception $e ) {
			return '';
		}
	}

	/**
	 * Run the list query with shared time / range / pagination rules.
	 *
	 * @param WP_REST_Request        $request    Request.
	 * @param array<string, mixed>   $meta_query Base meta query (AND).
	 * @return WP_REST_Response
	 */
	private function query_events_with_request_filters( WP_REST_Request $request, array $meta_query ): WP_REST_Response {
		$search = (string) $request->get_param( 'search' );

		$time_scope = sanitize_key( (string) $request->get_param( 'time_scope' ) );
		if ( ! in_array( $time_scope, array( 'all', 'upcoming', 'past' ), true ) ) {
			$time_scope = 'all';
		}

		$order_param = strtolower( (string) $request->get_param( 'order' ) );
		$order       = 'desc' === $order_param ? 'DESC' : 'ASC';

		$starts_after  = $this->parse_request_datetime_utc( (string) $request->get_param( 'starts_after' ) );
		$starts_before = $this->parse_request_datetime_utc( (string) $request->get_param( 'starts_before' ) );

		$has_range = ( '' !== $starts_after || '' !== $starts_before );
		if ( $has_range ) {
			$time_scope = 'all';
		}

		if ( '' !== $starts_after && '' !== $starts_before ) {
			/*
			 * Overlap with [starts_after, starts_before]: multi-day events are included when they
			 * intersect the window (starts_at <= end AND ends_at >= start), or point events when
			 * starts_at falls inside the window and there is no usable end date.
			 */
			$meta_query['clanspress_range_overlap'] = array(
				'relation' => 'OR',
				'span'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'cp_event_starts_at',
						'value'   => $starts_before,
						'compare' => '<=',
						'type'    => 'CHAR',
					),
					array(
						'key'     => 'cp_event_ends_at',
						'value'   => $starts_after,
						'compare' => '>=',
						'type'    => 'CHAR',
					),
				),
				'point'    => array(
					'relation' => 'AND',
					array(
						'key'     => 'cp_event_starts_at',
						'value'   => $starts_after,
						'compare' => '>=',
						'type'    => 'CHAR',
					),
					array(
						'key'     => 'cp_event_starts_at',
						'value'   => $starts_before,
						'compare' => '<=',
						'type'    => 'CHAR',
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => 'cp_event_ends_at',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'cp_event_ends_at',
							'value'   => '',
							'compare' => '=',
							'type'    => 'CHAR',
						),
					),
				),
			);
		} else {
			if ( '' !== $starts_after ) {
				$meta_query['cp_range_after'] = array(
					'key'     => 'cp_event_starts_at',
					'value'   => $starts_after,
					'compare' => '>=',
					'type'    => 'CHAR',
				);
			}

			if ( '' !== $starts_before ) {
				$meta_query['cp_range_before'] = array(
					'key'     => 'cp_event_starts_at',
					'value'   => $starts_before,
					'compare' => '<=',
					'type'    => 'CHAR',
				);
			}
		}

		$now_gmt = current_time( 'mysql', true );
		if ( 'upcoming' === $time_scope ) {
			$meta_query['starts_filter'] = array(
				'key'     => 'cp_event_starts_at',
				'value'   => $now_gmt,
				'compare' => '>=',
				'type'    => 'CHAR',
			);
		} elseif ( 'past' === $time_scope ) {
			$meta_query['starts_filter'] = array(
				'key'     => 'cp_event_starts_at',
				'value'   => $now_gmt,
				'compare' => '<',
				'type'    => 'CHAR',
			);
		}

		$per_page_req = (int) $request->get_param( 'per_page' );
		if ( $has_range ) {
			$per_page = max( 1, min( 500, $per_page_req > 0 ? $per_page_req : 200 ) );
			$page     = 1;
		} else {
			$per_page = max( 1, min( 50, $per_page_req > 0 ? $per_page_req : 20 ) );
			$page     = max( 1, (int) $request->get_param( 'page' ) );
		}

		// phpcs:disable WordPress.DB.SlowDBQuery -- Event list requires `meta_query` / `meta_key` for scope, range, and start-time ordering.
		$query_args = array(
			'post_type'      => Event_Post_Type::POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending' ),
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'meta_query'     => $meta_query,
		);

		if ( isset( $meta_query['starts_filter'] ) && ! isset( $meta_query['cp_range_after'] ) && ! isset( $meta_query['cp_range_before'] ) ) {
			$query_args['orderby'] = array(
				'starts_filter' => $order,
			);
		} else {
			$query_args['orderby']  = 'meta_value';
			$query_args['meta_key'] = 'cp_event_starts_at';
			$query_args['order']    = $order;
		}

		if ( '' !== $search ) {
			$query_args['s'] = $search;
		}

		$q = new \WP_Query( $query_args );
		// phpcs:enable WordPress.DB.SlowDBQuery

		$viewer_id = is_user_logged_in() ? (int) get_current_user_id() : 0;
		$items     = array();

		foreach ( $q->posts as $post ) {
			if ( ! ( $post instanceof \WP_Post ) ) {
				continue;
			}
			if ( ! Event_Permissions::viewer_can_see( $post, $viewer_id ) ) {
				continue;
			}
			$items[] = $this->post_to_response( $post );
		}

		return new WP_REST_Response(
			array(
				'items' => $items,
				'total' => (int) $q->found_posts,
			),
			200
		);
	}

	/**
	 * Single event.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {
		$request = rest_ensure_request( $request );

		$id   = (int) $request['id'];
		$post = get_post( $id );
		if ( ! ( $post instanceof \WP_Post ) || Event_Post_Type::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'clanspress_event_not_found', __( 'Event not found.', 'clanspress' ), array( 'status' => 404 ) );
		}

		$scope_err = $this->assert_events_allowed_for_event_post( $post );
		if ( $scope_err instanceof WP_Error ) {
			return $scope_err;
		}

		$viewer_id = is_user_logged_in() ? (int) get_current_user_id() : 0;
		if ( ! Event_Permissions::viewer_can_see( $post, $viewer_id ) ) {
			return new WP_Error( 'clanspress_event_forbidden', __( 'You cannot view this event.', 'clanspress' ), array( 'status' => 403 ) );
		}

		return new WP_REST_Response( $this->post_to_response( $post ), 200 );
	}

	/**
	 * Create event.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_item( $request ) {
		$request = rest_ensure_request( $request );

		$user_id = (int) get_current_user_id();
		$scope   = sanitize_key( (string) $request->get_param( 'scope' ) );
		$team_id = (int) $request->get_param( 'team_id' );
		$group_id = (int) $request->get_param( 'group_id' );

		if ( Event_Post_Type::SCOPE_TEAM === $scope ) {
			if ( $team_id <= 0 || ! function_exists( 'clanspress_teams_user_can_manage' ) || ! clanspress_teams_user_can_manage( $team_id, $user_id ) ) {
				return new WP_Error( 'clanspress_event_forbidden', __( 'You cannot create events for this team.', 'clanspress' ), array( 'status' => 403 ) );
			}
		} elseif ( Event_Post_Type::SCOPE_GROUP === $scope ) {
			if ( $group_id <= 0 || ! function_exists( 'clanspress_groups_user_can_manage' )
				|| ! clanspress_groups_user_can_manage( $group_id, $user_id ) ) {
				return new WP_Error( 'clanspress_event_forbidden', __( 'You cannot create events for this group.', 'clanspress' ), array( 'status' => 403 ) );
			}
		} else {
			return new WP_Error( 'clanspress_event_invalid_scope', __( 'Invalid scope.', 'clanspress' ), array( 'status' => 400 ) );
		}

		if ( Event_Post_Type::SCOPE_TEAM === $scope ) {
			$err = $this->assert_team_events_allowed( $team_id );
			if ( $err instanceof WP_Error ) {
				return $err;
			}
		} else {
			$err = $this->assert_group_events_allowed( $group_id );
			if ( $err instanceof WP_Error ) {
				return $err;
			}
		}

		$title   = sanitize_text_field( (string) $request->get_param( 'title' ) );
		$content = isset( $request['content'] ) ? wp_kses_post( (string) $request->get_param( 'content' ) ) : '';
		$status  = sanitize_key( (string) $request->get_param( 'status' ) );
		if ( ! in_array( $status, array( 'draft', 'publish' ), true ) ) {
			$status = 'publish';
		}

		$post_id = $this->insert_event_post(
			array(
				'post_type'    => Event_Post_Type::POST_TYPE,
				'post_status'  => $status,
				'post_title'   => $title ? $title : __( 'Event', 'clanspress' ),
				'post_content' => $content,
				'post_author'  => $user_id,
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$post_id = (int) $post_id;
		update_post_meta( $post_id, 'cp_event_scope', $scope );
		if ( Event_Post_Type::SCOPE_TEAM === $scope ) {
			update_post_meta( $post_id, 'cp_event_team_id', $team_id );
			update_post_meta( $post_id, 'cp_event_group_id', 0 );
		} else {
			update_post_meta( $post_id, 'cp_event_group_id', $group_id );
			update_post_meta( $post_id, 'cp_event_team_id', 0 );
		}

		$this->persist_meta_from_request( $post_id, $request, false );

		$post = get_post( $post_id );
		if ( ! ( $post instanceof \WP_Post ) ) {
			return new WP_Error( 'clanspress_event_create_failed', __( 'Could not create event.', 'clanspress' ), array( 'status' => 500 ) );
		}

		$response_data = $this->post_to_response( $post );
		$outreach_mode = Event_Member_Outreach::sanitize_mode( $request->get_param( 'member_outreach' ) );
		if ( Event_Member_Outreach::MODE_NONE !== $outreach_mode ) {
			$response_data['memberOutreach'] = Event_Member_Outreach::run( $post_id, $outreach_mode );
		}

		return new WP_REST_Response( $response_data, 201 );
	}

	/**
	 * Update event.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_item( $request ) {
		$request = rest_ensure_request( $request );

		$id   = (int) $request['id'];
		$post = get_post( $id );
		if ( ! ( $post instanceof \WP_Post ) || Event_Post_Type::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'clanspress_event_not_found', __( 'Event not found.', 'clanspress' ), array( 'status' => 404 ) );
		}

		$user_id = (int) get_current_user_id();
		if ( ! Event_Permissions::user_can_manage_event( $id, $user_id ) ) {
			return new WP_Error( 'clanspress_event_forbidden', __( 'You cannot edit this event.', 'clanspress' ), array( 'status' => 403 ) );
		}

		$scope_err = $this->assert_events_allowed_for_event_post( $post );
		if ( $scope_err instanceof WP_Error ) {
			return $scope_err;
		}

		$update = array( 'ID' => $id );
		if ( null !== $request->get_param( 'title' ) ) {
			$update['post_title'] = sanitize_text_field( (string) $request->get_param( 'title' ) );
		}
		if ( null !== $request->get_param( 'content' ) ) {
			$update['post_content'] = wp_kses_post( (string) $request->get_param( 'content' ) );
		}
		if ( null !== $request->get_param( 'status' ) ) {
			$st = sanitize_key( (string) $request->get_param( 'status' ) );
			if ( in_array( $st, array( 'draft', 'publish', 'pending' ), true ) ) {
				$update['post_status'] = $st;
			}
		}

		if ( count( $update ) > 1 ) {
			$updated = $this->update_event_post( $update, $user_id );
			if ( is_wp_error( $updated ) ) {
				return $updated;
			}
		}

		$this->persist_meta_from_request( $id, $request, true );

		$post = get_post( $id );

		$response_data = $this->post_to_response( $post );
		if ( null !== $request->get_param( 'member_outreach' ) ) {
			$outreach_mode = Event_Member_Outreach::sanitize_mode( $request->get_param( 'member_outreach' ) );
			if ( Event_Member_Outreach::MODE_NONE !== $outreach_mode ) {
				$response_data['memberOutreach'] = Event_Member_Outreach::run( $id, $outreach_mode );
			}
		}

		return new WP_REST_Response( $response_data, 200 );
	}

	/**
	 * Trash a scheduled event (managers / author / site admin).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {
		$request = rest_ensure_request( $request );

		$id   = (int) $request['id'];
		$post = get_post( $id );
		if ( ! ( $post instanceof \WP_Post ) || Event_Post_Type::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'clanspress_event_not_found', __( 'Event not found.', 'clanspress' ), array( 'status' => 404 ) );
		}

		$user_id = (int) get_current_user_id();
		if ( ! Event_Permissions::user_can_manage_event( $id, $user_id ) ) {
			return new WP_Error( 'clanspress_event_forbidden', __( 'You cannot delete this event.', 'clanspress' ), array( 'status' => 403 ) );
		}

		$scope_err = $this->assert_events_allowed_for_event_post( $post );
		if ( $scope_err instanceof WP_Error ) {
			return $scope_err;
		}

		$result = wp_trash_post( $id );
		if ( ! $result ) {
			return new WP_Error( 'clanspress_event_delete_failed', __( 'Could not delete this event.', 'clanspress' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response(
			array(
				'deleted' => true,
				'id'      => $id,
			),
			200
		);
	}

	/**
	 * @return bool|WP_Error
	 */
	public function create_permissions_check() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'clanspress_not_logged_in', __( 'You must be logged in.', 'clanspress' ), array( 'status' => 401 ) );
		}
		return true;
	}

	/**
	 * @return bool|WP_Error
	 */
	public function update_permissions_check() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'clanspress_not_logged_in', __( 'You must be logged in.', 'clanspress' ), array( 'status' => 401 ) );
		}
		return true;
	}

	/**
	 * @return bool|WP_Error
	 */
	public function delete_permissions_check() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'clanspress_not_logged_in', __( 'You must be logged in.', 'clanspress' ), array( 'status' => 401 ) );
		}
		return true;
	}

	/**
	 * Persist meta from REST request.
	 *
	 * @param int             $post_id Post ID.
	 * @param WP_REST_Request $request Request.
	 * @param bool            $partial Only set provided keys (updates).
	 * @return void
	 */
	private function persist_meta_from_request( int $post_id, WP_REST_Request $request, bool $partial = false ): void {
		$pt = new Event_Post_Type();

		$keys = array(
			'mode'                   => 'cp_event_mode',
			'virtual_url'            => 'cp_event_virtual_url',
			'address_line1'          => 'cp_event_address_line1',
			'address_line2'          => 'cp_event_address_line2',
			'locality'               => 'cp_event_locality',
			'region'                 => 'cp_event_region',
			'postcode'               => 'cp_event_postcode',
			'country'                => 'cp_event_country',
			'starts_at'              => 'cp_event_starts_at',
			'ends_at'                => 'cp_event_ends_at',
			'visibility'             => 'cp_event_visibility',
			'attendees_visibility'   => 'cp_event_attendees_visibility',
		);

		foreach ( $keys as $param => $meta_key ) {
			if ( $partial && null === $request->get_param( $param ) ) {
				continue;
			}
			$raw = $request->get_param( $param );
			switch ( $meta_key ) {
				case 'cp_event_mode':
					update_post_meta( $post_id, $meta_key, $pt->sanitize_mode( $raw ) );
					break;
				case 'cp_event_virtual_url':
					update_post_meta( $post_id, $meta_key, esc_url_raw( (string) $raw ) );
					break;
				case 'cp_event_visibility':
					update_post_meta( $post_id, $meta_key, $pt->sanitize_visibility( $raw ) );
					break;
				case 'cp_event_attendees_visibility':
					update_post_meta( $post_id, $meta_key, $pt->sanitize_attendees_visibility( $raw ) );
					break;
				case 'cp_event_starts_at':
				case 'cp_event_ends_at':
					update_post_meta( $post_id, $meta_key, $pt->sanitize_datetime_gmt( $raw ) );
					break;
				default:
					update_post_meta( $post_id, $meta_key, sanitize_text_field( (string) $raw ) );
			}
		}
	}

	/**
	 * Insert a `cp_event` after capability checks (may not have `create_posts`).
	 *
	 * @param array<string, mixed> $postarr Post data for {@see wp_insert_post()}.
	 * @return int|\WP_Error
	 */
	private function insert_event_post( array $postarr ) {
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
		$post_id = wp_insert_post( $postarr, true );
		remove_filter( 'user_has_cap', $filter, 999 );
		return $post_id;
	}

	/**
	 * Update a `cp_event` post (capability shim).
	 *
	 * @param array<string, mixed> $postarr Post data for {@see wp_update_post()}.
	 * @param int                    $user_id Acting user ID.
	 * @return int|\WP_Error
	 */
	private function update_event_post( array $postarr, int $user_id ) {
		unset( $user_id );
		$filter = static function ( $allcaps, $caps ) {
			if ( ! isset( $caps[0] ) ) {
				return $allcaps;
			}
			if ( in_array( $caps[0], array( 'edit_post', 'edit_posts', 'publish_posts' ), true ) ) {
				$allcaps['edit_posts']    = true;
				$allcaps['publish_posts'] = true;
			}
			return $allcaps;
		};
		add_filter( 'user_has_cap', $filter, 999, 2 );
		$result = wp_update_post( $postarr, true );
		remove_filter( 'user_has_cap', $filter, 999 );
		return $result;
	}

	/**
	 * Shape API response.
	 *
	 * @param \WP_Post $post Post.
	 * @return array<string, mixed>
	 */
	private function post_to_response( \WP_Post $post ): array {
		$pid = $post->ID;
		return array(
			'id'                   => $pid,
			'title'                => get_the_title( $post ),
			'content'              => $post->post_content,
			'status'               => $post->post_status,
			'scope'                => (string) get_post_meta( $pid, 'cp_event_scope', true ),
			'teamId'               => (int) get_post_meta( $pid, 'cp_event_team_id', true ),
			'groupId'              => (int) get_post_meta( $pid, 'cp_event_group_id', true ),
			'mode'                 => (string) get_post_meta( $pid, 'cp_event_mode', true ),
			'virtualUrl'           => (string) get_post_meta( $pid, 'cp_event_virtual_url', true ),
			'addressLine1'         => (string) get_post_meta( $pid, 'cp_event_address_line1', true ),
			'addressLine2'         => (string) get_post_meta( $pid, 'cp_event_address_line2', true ),
			'locality'             => (string) get_post_meta( $pid, 'cp_event_locality', true ),
			'region'               => (string) get_post_meta( $pid, 'cp_event_region', true ),
			'postcode'             => (string) get_post_meta( $pid, 'cp_event_postcode', true ),
			'country'              => (string) get_post_meta( $pid, 'cp_event_country', true ),
			'startsAt'             => (string) get_post_meta( $pid, 'cp_event_starts_at', true ),
			'endsAt'               => (string) get_post_meta( $pid, 'cp_event_ends_at', true ),
			'visibility'           => (string) get_post_meta( $pid, 'cp_event_visibility', true ),
			'attendeesVisibility'  => (string) get_post_meta( $pid, 'cp_event_attendees_visibility', true ),
			'permalink'            => self::public_permalink_for_event_post( $post ),
			'permalinkTeam'        => '', // Deprecated; use permalink.
		);
	}

	/**
	 * Public URL for a team or group event (matches block/theme routing).
	 *
	 * Used by REST responses, notifications, and {@see Event_Member_Outreach}.
	 *
	 * @param \WP_Post $post Event post.
	 * @return string Empty string when the URL cannot be resolved.
	 */
	public static function public_permalink_for_event_post( \WP_Post $post ): string {
		$pid   = $post->ID;
		$scope = sanitize_key( (string) get_post_meta( $pid, 'cp_event_scope', true ) );
		if ( Event_Post_Type::SCOPE_TEAM === $scope ) {
			$team_id = (int) get_post_meta( $pid, 'cp_event_team_id', true );
			if ( $team_id < 1 ) {
				return '';
			}
			$team_post = get_post( $team_id );
			if ( ! ( $team_post instanceof \WP_Post ) || 'cp_team' !== $team_post->post_type ) {
				return '';
			}
			$slug = $team_post->post_name;
			if ( is_string( $slug ) && '' !== $slug ) {
				return trailingslashit( home_url( '/teams/' . rawurlencode( $slug ) . '/events/' . $pid ) );
			}
		} elseif ( Event_Post_Type::SCOPE_GROUP === $scope ) {
			$group_id = (int) get_post_meta( $pid, 'cp_event_group_id', true );
			if ( $group_id < 1 ) {
				return '';
			}
			$group_url = get_permalink( $group_id );
			if ( is_string( $group_url ) && '' !== $group_url ) {
				return trailingslashit( $group_url ) . 'events/' . $pid . '/';
			}
		}

		return '';
	}

	/**
	 * Enforce global + per-team event feature flags.
	 *
	 * @param int $team_id Team post ID.
	 * @return WP_Error|null
	 */
	private function assert_team_events_allowed( int $team_id ): ?WP_Error {
		if ( $team_id < 1 ) {
			return new WP_Error( 'clanspress_events_disabled', __( 'Events are not available for this record.', 'clanspress' ), array( 'status' => 403 ) );
		}
		if ( ! function_exists( 'clanspress_events_are_globally_enabled' ) || ! clanspress_events_are_globally_enabled() ) {
			return new WP_Error( 'clanspress_events_disabled', __( 'Events are disabled on this site.', 'clanspress' ), array( 'status' => 403 ) );
		}
		if ( ! function_exists( 'clanspress_events_are_enabled_for_team' ) || ! clanspress_events_are_enabled_for_team( $team_id ) ) {
			return new WP_Error( 'clanspress_events_disabled', __( 'Events are disabled for this team.', 'clanspress' ), array( 'status' => 403 ) );
		}

		return null;
	}

	/**
	 * Enforce global + per-group event feature flags.
	 *
	 * @param int $group_id Group post ID.
	 * @return WP_Error|null
	 */
	private function assert_group_events_allowed( int $group_id ): ?WP_Error {
		if ( $group_id < 1 ) {
			return new WP_Error( 'clanspress_events_disabled', __( 'Events are not available for this record.', 'clanspress' ), array( 'status' => 403 ) );
		}
		if ( ! function_exists( 'clanspress_events_are_globally_enabled' ) || ! clanspress_events_are_globally_enabled() ) {
			return new WP_Error( 'clanspress_events_disabled', __( 'Events are disabled on this site.', 'clanspress' ), array( 'status' => 403 ) );
		}
		if ( ! function_exists( 'clanspress_events_are_enabled_for_group' ) || ! clanspress_events_are_enabled_for_group( $group_id ) ) {
			return new WP_Error( 'clanspress_events_disabled', __( 'Events are disabled for this group.', 'clanspress' ), array( 'status' => 403 ) );
		}

		return null;
	}

	/**
	 * Enforce feature flags for an existing event post.
	 *
	 * @param \WP_Post $post Event post.
	 * @return WP_Error|null
	 */
	private function assert_events_allowed_for_event_post( \WP_Post $post ): ?WP_Error {
		$scope = sanitize_key( (string) get_post_meta( $post->ID, 'cp_event_scope', true ) );
		if ( Event_Post_Type::SCOPE_TEAM === $scope ) {
			$team_id = (int) get_post_meta( $post->ID, 'cp_event_team_id', true );

			return $this->assert_team_events_allowed( $team_id );
		}
		if ( Event_Post_Type::SCOPE_GROUP === $scope ) {
			$group_id = (int) get_post_meta( $post->ID, 'cp_event_group_id', true );

			return $this->assert_group_events_allowed( $group_id );
		}

		return null;
	}
}
