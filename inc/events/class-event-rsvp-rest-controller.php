<?php
/**
 * REST API controller for event RSVPs.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Events;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST endpoints for generic events RSVPs (matches, teams events, groups events, etc).
 */
final class Event_Rsvp_Rest_Controller extends WP_REST_Controller {
	/**
	 * Namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'clanspress/v1';

	/**
	 * REST base.
	 *
	 * @var string
	 */
	protected $rest_base = 'events';

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<event_type>[a-z0-9_-]+)/(?P<event_id>\\d+)/rsvp',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_my_rsvp' ),
					'permission_callback' => array( $this, 'check_logged_in_and_visible' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'set_my_rsvp' ),
					'permission_callback' => array( $this, 'check_logged_in_and_visible' ),
					'args'                => array(
						'status' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<event_type>[a-z0-9_-]+)/(?P<event_id>\\d+)/attendees',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_attendees' ),
					'permission_callback' => array( $this, 'check_event_visible' ),
					'args'                => array(
						'status' => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_key',
						),
						'limit'  => array(
							'type'              => 'integer',
							'default'           => 100,
							'sanitize_callback' => 'absint',
						),
						'offset' => array(
							'type'              => 'integer',
							'default'           => 0,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);
	}

	/**
	 * Permission: logged-in and can view event.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function check_logged_in_and_visible( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'clanspress_not_logged_in',
				__( 'You must be logged in.', 'clanspress' ),
				array( 'status' => 401 )
			);
		}

		return $this->check_event_visible( $request );
	}

	/**
	 * Permission: can view event (anon allowed for public events).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function check_event_visible( WP_REST_Request $request ) {
		$event_type = sanitize_key( (string) $request['event_type'] );
		$event_id   = absint( $request['event_id'] );
		$viewer_id  = is_user_logged_in() ? (int) get_current_user_id() : 0;

		if ( '' === $event_type || $event_id < 1 ) {
			return new WP_Error(
				'clanspress_invalid_event',
				__( 'Invalid event.', 'clanspress' ),
				array( 'status' => 400 )
			);
		}

		if ( ! Events::viewer_can_see_event( $event_type, $event_id, $viewer_id ) ) {
			return new WP_Error(
				'clanspress_event_forbidden',
				__( 'You do not have access to this event.', 'clanspress' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get current user's RSVP for an event.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_my_rsvp( WP_REST_Request $request ): WP_REST_Response {
		$event_type = sanitize_key( (string) $request['event_type'] );
		$event_id   = absint( $request['event_id'] );
		$user_id    = (int) get_current_user_id();

		$row    = Event_Rsvp_Data_Access::get_user_rsvp( $event_type, $event_id, $user_id );
		$status = is_array( $row ) ? (string) ( $row['status'] ?? '' ) : '';

		return new WP_REST_Response(
			array(
				'event_type' => $event_type,
				'event_id'   => $event_id,
				'user_id'    => $user_id,
				'status'     => $status,
			),
			200
		);
	}

	/**
	 * Set current user's RSVP for an event.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function set_my_rsvp( WP_REST_Request $request ): WP_REST_Response {
		$event_type = sanitize_key( (string) $request['event_type'] );
		$event_id   = absint( $request['event_id'] );
		$user_id    = (int) get_current_user_id();
		$status     = Event_Rsvp_Data_Access::sanitize_status( (string) $request->get_param( 'status' ) );

		$result = Event_Rsvp_Data_Access::set_user_rsvp( $event_type, $event_id, $user_id, $status );

		return new WP_REST_Response(
			array(
				'event_type' => $event_type,
				'event_id'   => $event_id,
				'user_id'    => $user_id,
				'status'     => (string) ( $result['status'] ?? '' ),
				'old_status' => $result['old_status'] ?? null,
				'changed'    => (bool) ( $result['changed'] ?? false ),
			),
			200
		);
	}

	/**
	 * Get attendee user IDs for an event (if allowed by settings).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_attendees( WP_REST_Request $request ) {
		$event_type = sanitize_key( (string) $request['event_type'] );
		$event_id   = absint( $request['event_id'] );
		$status     = sanitize_key( (string) $request->get_param( 'status' ) );
		$limit      = absint( $request->get_param( 'limit' ) );
		$offset     = absint( $request->get_param( 'offset' ) );

		$viewer_id = is_user_logged_in() ? (int) get_current_user_id() : 0;
		$vis       = Events::get_attendees_visibility( $event_type, $event_id );

		$can_view_attendees = ( 'public' === $vis ) && Events::viewer_can_see_event( $event_type, $event_id, $viewer_id );

		/**
		 * Filter whether attendee list can be viewed.
		 *
		 * Use this to allow team members/admins to see attendee lists even when
		 * the event is set to `hidden`.
		 *
		 * @param bool   $can_view_attendees Whether attendee list can be viewed.
		 * @param string $event_type         Event type slug.
		 * @param int    $event_id           Event ID.
		 * @param int    $viewer_id          Viewer user ID (0 for anon).
		 */
		$can_view_attendees = (bool) apply_filters(
			'clanspress_event_can_view_attendees',
			$can_view_attendees,
			$event_type,
			$event_id,
			$viewer_id
		);

		if ( ! $can_view_attendees ) {
			return new WP_Error(
				'clanspress_attendees_hidden',
				__( 'Attendee list is hidden.', 'clanspress' ),
				array( 'status' => 403 )
			);
		}

		$status_or_null = ( '' === $status ) ? null : $status;

		$rows = Event_Rsvp_Data_Access::get_attendee_rows(
			$event_type,
			$event_id,
			$status_or_null,
			$limit,
			$offset
		);

		$user_ids   = array();
		$attendees  = array();
		foreach ( $rows as $row ) {
			$uid = (int) $row['user_id'];
			if ( $uid < 1 ) {
				continue;
			}
			$user_ids[] = $uid;
			$name       = '';
			$u          = get_userdata( $uid );
			if ( $u instanceof \WP_User ) {
				$name = (string) $u->display_name;
			}
			$attendees[] = array(
				'user_id' => $uid,
				'status'  => (string) $row['status'],
				'name'    => $name,
			);
		}

		return new WP_REST_Response(
			array(
				'event_type' => $event_type,
				'event_id'   => $event_id,
				'visibility' => $vis,
				'status'     => ( null === $status_or_null ) ? '' : $status_or_null,
				'user_ids'   => $user_ids,
				'attendees'  => $attendees,
			),
			200
		);
	}
}

