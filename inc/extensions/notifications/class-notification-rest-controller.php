<?php
/**
 * REST API controller for notifications.
 *
 * @package Clanspress
 */

namespace Kernowdev\Clanspress\Extensions\Notification;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- Notifications table from schema; dynamic SQL fragments built with `$wpdb->prepare()`.

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * REST endpoints for notifications with long polling support.
 */
final class Notification_Rest_Controller extends WP_REST_Controller {

	/**
	 * Namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'clanspress/v1';

	/**
	 * Resource type.
	 *
	 * @var string
	 */
	protected $rest_base = 'notifications';

	/**
	 * Default polling interval in milliseconds.
	 */
	public const DEFAULT_POLL_INTERVAL = 4000;

	/**
	 * Active polling interval (when there are recent notifications).
	 */
	public const ACTIVE_POLL_INTERVAL = 1000;

	/**
	 * Maximum long poll wait time in seconds (worker holds a PHP process for up to this duration when blocking wait is on).
	 */
	public const MAX_POLL_WAIT = 25;

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Get notifications list.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
					'args'                => array(
						'page'        => array(
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'per_page'    => array(
							'type'              => 'integer',
							'default'           => 20,
							'sanitize_callback' => 'absint',
						),
						'unread_only' => array(
							'type'              => 'boolean',
							'default'           => false,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
				),
			)
		);

		// Long polling endpoint for real-time updates.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/poll',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'poll_updates' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
					'args'                => array(
						'since'    => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'last_id'  => array(
							'type'              => 'integer',
							'default'           => 0,
							'sanitize_callback' => 'absint',
						),
						'timeout'  => array(
							'type'              => 'integer',
							'default'           => self::MAX_POLL_WAIT,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// Get unread count.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/count',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_count' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
				),
			)
		);

		// Get single notification.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
					'args'                => array(
						'id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
					'args'                => array(
						'id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// Mark notification as read.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/read',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'mark_read' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
					'args'                => array(
						'id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// Execute notification action.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/action',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'execute_action' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
					'args'                => array(
						'id'     => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'action' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
			)
		);

		// Mark all as read.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/read-all',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'mark_all_read' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
				),
			)
		);

		// Get transport config (for client to know polling intervals, etc.).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/transport',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_transport_config' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
				),
			)
		);
	}

	/**
	 * Check if user is logged in.
	 *
	 * @return bool|WP_Error
	 */
	public function check_logged_in() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'rest_forbidden', __( 'You must be logged in.', 'clanspress' ), array( 'status' => 401 ) );
		}
		return true;
	}

	/**
	 * Get notifications list.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {
		$user_id     = get_current_user_id();
		$page        = max( 1, absint( $request->get_param( 'page' ) ) );
		$per_page    = min( 50, max( 1, absint( $request->get_param( 'per_page' ) ) ) );
		$unread_only = $request->get_param( 'unread_only' );

		$result = Notification_Data_Access::get_for_user( $user_id, $page, $per_page, $unread_only );

		return new WP_REST_Response(
			array(
				'notifications' => array_map( array( $this, 'format_notification' ), $result['notifications'] ),
				'total'         => $result['total'],
				'unread_count'  => $result['unread_count'],
				'page'          => $page,
				'pages'         => ceil( $result['total'] / max( 1, $per_page ) ),
			)
		);
	}

	/**
	 * Long polling endpoint for real-time updates.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function poll_updates( $request ): WP_REST_Response {
		$user_id = get_current_user_id();
		$since   = $request->get_param( 'since' );
		$last_id = $request->get_param( 'last_id' );
		$timeout = min( self::MAX_POLL_WAIT, max( 1, $request->get_param( 'timeout' ) ) );

		/**
		 * Filter the polling timeout.
		 *
		 * @param int $timeout Timeout in seconds.
		 * @param int $user_id User ID.
		 */
		$timeout = (int) apply_filters( 'clanspress_notification_poll_timeout', $timeout, $user_id );

		/**
		 * Filter to use alternative transport (e.g., WebSockets).
		 *
		 * Return a WP_REST_Response to bypass polling entirely.
		 *
		 * @param WP_REST_Response|null $response  Response or null to continue with polling.
		 * @param int                   $user_id   User ID.
		 * @param string                $since     Since timestamp.
		 * @param int                   $last_id   Last notification ID.
		 * @param WP_REST_Request       $request   Original request.
		 */
		$alt_response = apply_filters( 'clanspress_notification_poll_transport', null, $user_id, $since, $last_id, $request );
		if ( $alt_response instanceof WP_REST_Response ) {
			return $alt_response;
		}

		$start_time = time();
		$end_time   = $start_time + $timeout;
		$interval   = 1; // Check every second.

		/**
		 * Filter the polling check interval.
		 *
		 * @param int $interval Interval in seconds.
		 * @param int $user_id  User ID.
		 */
		$interval = (int) apply_filters( 'clanspress_notification_poll_interval', $interval, $user_id );

		$new_notifications = array();
		$unread_count      = 0;

		/**
		 * Whether the poll endpoint may block with a sleep loop until `timeout` or new notifications.
		 *
		 * When false (default), performs a single DB read and returns immediately (avoids exhausting PHP-FPM
		 * workers when many users have the bell open). The client still respects `next_poll` between requests.
		 * When true, preserves long-poll behaviour (lower request count, higher per-request worker hold time).
		 *
		 * @param bool $blocking Default false.
		 * @param int  $user_id  User ID.
		 */
		$blocking_wait = (bool) apply_filters( 'clanspress_notification_poll_blocking_wait', false, $user_id );

		if ( ! $blocking_wait ) {
			$new_notifications = $this->get_new_notifications( $user_id, $since, $last_id );
			$unread_count      = Notification_Data_Access::get_unread_count( $user_id );
		} else {
			// Long poll loop (do not call wp_cache_flush() here — it clears the entire object cache site-wide).
			while ( time() < $end_time ) {
				$new_notifications = $this->get_new_notifications( $user_id, $since, $last_id );
				$unread_count      = Notification_Data_Access::get_unread_count( $user_id );

				if ( ! empty( $new_notifications ) ) {
					break;
				}

				/**
				 * Action fired during each poll iteration.
				 *
				 * Can be used to check for other real-time events.
				 *
				 * @param int    $user_id User ID.
				 * @param string $since   Since timestamp.
				 * @param int    $last_id Last notification ID.
				 */
				do_action( 'clanspress_notification_poll_tick', $user_id, $since, $last_id );

				// Sleep before next check.
				sleep( $interval );
			}
		}

		$next_poll = empty( $new_notifications ) ? self::DEFAULT_POLL_INTERVAL : self::ACTIVE_POLL_INTERVAL;

		/**
		 * Filter the next poll interval.
		 *
		 * @param int   $next_poll         Next poll interval in milliseconds.
		 * @param array $new_notifications New notifications found.
		 * @param int   $user_id           User ID.
		 */
		$next_poll = (int) apply_filters( 'clanspress_notification_next_poll_interval', $next_poll, $new_notifications, $user_id );

		return new WP_REST_Response(
			array(
				'notifications' => array_map( array( $this, 'format_notification' ), $new_notifications ),
				'unread_count'  => $unread_count,
				'timestamp'     => gmdate( 'Y-m-d H:i:s' ),
				'next_poll'     => $next_poll,
			)
		);
	}

	/**
	 * Get new notifications since a timestamp or ID.
	 *
	 * @param int    $user_id User ID.
	 * @param string $since   Since timestamp.
	 * @param int    $last_id Last notification ID.
	 * @return object[]
	 */
	private function get_new_notifications( int $user_id, string $since, int $last_id ): array {
		global $wpdb;

		Notification_Schema::ensure_table_exists();

		$table = Notification_Schema::table_name();

		$where = $wpdb->prepare( 'WHERE user_id = %d', $user_id );

		if ( $last_id > 0 ) {
			$where .= $wpdb->prepare( ' AND id > %d', $last_id );
		} elseif ( '' !== $since ) {
			$where .= $wpdb->prepare( ' AND created_at > %s', $since );
		} else {
			// No reference point, return nothing (client should provide since or last_id).
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT 50"
		);

		if ( ! $rows ) {
			return array();
		}

		// Hydrate rows.
		return array_map(
			function ( $row ) {
				$row->id        = (int) $row->id;
				$row->user_id   = (int) $row->user_id;
				$row->actor_id  = $row->actor_id ? (int) $row->actor_id : null;
				$row->object_id = $row->object_id ? (int) $row->object_id : null;
				$row->is_read   = (bool) $row->is_read;
				$row->data      = $row->data ? json_decode( $row->data, true ) : null;
				$row->actions   = $row->actions ? json_decode( $row->actions, true ) : null;
				$row->status    = $row->status ?? 'pending';
				$row->is_actionable = is_array( $row->actions ) && ! empty( $row->actions ) && 'pending' === $row->status;

				if ( $row->actor_id ) {
					$actor = get_userdata( $row->actor_id );
					if ( $actor ) {
						$row->actor = (object) array(
							'id'         => $actor->ID,
							'name'       => $actor->display_name,
							'avatar_url' => function_exists( 'clanspress_players_get_display_avatar' )
								? clanspress_players_get_display_avatar( (int) $actor->ID, false, '', 'notifications', 'small' )
								: get_avatar_url( $actor->ID, array( 'size' => 48 ) ),
						);
					}
				}

				return $row;
			},
			$rows
		);
	}

	/**
	 * Get unread count.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_count( $request ): WP_REST_Response {
		$user_id = get_current_user_id();
		$count   = Notification_Data_Access::get_unread_count( $user_id );

		return new WP_REST_Response(
			array(
				'unread_count' => $count,
				'timestamp'    => gmdate( 'Y-m-d H:i:s' ),
			)
		);
	}

	/**
	 * Get single notification.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$notification_id = $request->get_param( 'id' );
		$notification    = Notification_Data_Access::get( $notification_id );

		if ( ! $notification ) {
			return new WP_Error( 'not_found', __( 'Notification not found.', 'clanspress' ), array( 'status' => 404 ) );
		}

		if ( (int) $notification->user_id !== get_current_user_id() ) {
			return new WP_Error( 'forbidden', __( 'You cannot view this notification.', 'clanspress' ), array( 'status' => 403 ) );
		}

		return new WP_REST_Response( $this->format_notification( $notification ) );
	}

	/**
	 * Delete notification.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$notification_id = $request->get_param( 'id' );
		$user_id         = get_current_user_id();

		$result = Notification_Data_Access::delete( $notification_id, $user_id );

		if ( ! $result ) {
			return new WP_Error( 'delete_failed', __( 'Could not delete notification.', 'clanspress' ), array( 'status' => 400 ) );
		}

		return new WP_REST_Response( array( 'deleted' => true ) );
	}

	/**
	 * Mark notification as read.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function mark_read( $request ) {
		$notification_id = $request->get_param( 'id' );
		$user_id         = get_current_user_id();

		$result = Notification_Data_Access::mark_read( $notification_id, $user_id );

		if ( ! $result ) {
			return new WP_Error( 'mark_read_failed', __( 'Could not mark notification as read.', 'clanspress' ), array( 'status' => 400 ) );
		}

		return new WP_REST_Response(
			array(
				'success'      => true,
				'unread_count' => Notification_Data_Access::get_unread_count( $user_id ),
			)
		);
	}

	/**
	 * Execute notification action.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function execute_action( $request ) {
		$notification_id = $request->get_param( 'id' );
		$action_key      = $request->get_param( 'action' );
		$user_id         = get_current_user_id();

		$result = Notification_Data_Access::execute_action( $notification_id, $user_id, $action_key );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$result['unread_count'] = Notification_Data_Access::get_unread_count( $user_id );

		return new WP_REST_Response( $result );
	}

	/**
	 * Mark all notifications as read.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function mark_all_read( $request ): WP_REST_Response {
		$user_id = get_current_user_id();
		$count   = Notification_Data_Access::mark_all_read( $user_id );

		return new WP_REST_Response(
			array(
				'marked_read'  => $count,
				'unread_count' => 0,
			)
		);
	}

	/**
	 * Get transport configuration.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_transport_config( $request ): WP_REST_Response {
		$config = array(
			'type'            => 'polling',
			'poll_interval'   => self::DEFAULT_POLL_INTERVAL,
			'active_interval' => self::ACTIVE_POLL_INTERVAL,
			'max_wait'        => self::MAX_POLL_WAIT * 1000,
			'endpoints'       => array(
				'list'    => rest_url( $this->namespace . '/' . $this->rest_base ),
				'poll'    => rest_url( $this->namespace . '/' . $this->rest_base . '/poll' ),
				'count'   => rest_url( $this->namespace . '/' . $this->rest_base . '/count' ),
				'readAll' => rest_url( $this->namespace . '/' . $this->rest_base . '/read-all' ),
			),
		);

		/**
		 * Filter the notification transport configuration.
		 *
		 * Use this to provide WebSocket configuration when available.
		 *
		 * @param array $config  Transport configuration.
		 * @param int   $user_id Current user ID.
		 */
		$config = (array) apply_filters( 'clanspress_notification_transport_config', $config, get_current_user_id() );

		return new WP_REST_Response( $config );
	}

	/**
	 * Format notification for API response.
	 *
	 * @param object $notification Notification object.
	 * @return array<string, mixed>
	 */
	private function format_notification( object $notification ): array {
		$formatted = array(
			'id'           => $notification->id,
			'type'         => $notification->type,
			'title'        => $notification->title,
			'message'      => $notification->message,
			'url'          => $notification->url,
			'actor_id'     => $notification->actor_id,
			'actor'        => isset( $notification->actor ) ? (array) $notification->actor : null,
			'object_type'  => $notification->object_type,
			'object_id'    => $notification->object_id,
			'data'         => $notification->data,
			'actions'      => $notification->actions,
			'status'       => $notification->status,
			'is_read'      => $notification->is_read,
			'is_actionable' => $notification->is_actionable,
			'created_at'   => $notification->created_at,
			'read_at'      => $notification->read_at,
			'time_ago'     => human_time_diff( strtotime( $notification->created_at ), time() ),
		);

		/**
		 * Filter formatted notification for API response.
		 *
		 * @param array  $formatted    Formatted notification.
		 * @param object $notification Original notification object.
		 */
		return (array) apply_filters( 'clanspress_format_notification_response', $formatted, $notification );
	}
}

// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
