<?php

defined( 'ABSPATH' ) || exit;

/**
 * Notification helper functions for developers.
 *
 * @package Clanspress
 */

use Kernowdev\Clanspress\Extensions\Notification\Notification_Data_Access;

/**
 * Whether the Clanspress Notifications extension (`cp_notifications`) is installed and enabled.
 *
 * Other extensions and plugins should use this (or handle a `WP_Error` from `clanspress_notify()`)
 * before relying on in-site notifications, REST routes, or the bell block — those only load when
 * the extension is active.
 *
 * @return bool
 */
function clanspress_notifications_extension_active(): bool {
	if ( ! class_exists( \Kernowdev\Clanspress\Extensions\Loader::class ) ) {
		return false;
	}

	$active = \Kernowdev\Clanspress\Extensions\Loader::instance()->is_extension_installed( 'cp_notifications' );

	/**
	 * Filter whether notifications are considered active for theme and third-party checks.
	 *
	 * @param bool $active True when `cp_notifications` is in the installed-extensions option.
	 */
	return (bool) apply_filters( 'clanspress_notifications_extension_active', $active );
}

/**
 * Stored Notifications extension settings merged with defaults (`clanspress_notifications_settings`).
 *
 * @return array<string, mixed> {
 *     @type bool $subpage_player    Player profile notifications subpage enabled.
 *     @type bool $poll_long_polling Notification bell uses blocking long-polling when true.
 * }
 */
function clanspress_notifications_settings_values(): array {
	$defaults = array(
		'subpage_player'   => true,
		'poll_long_polling' => false,
	);
	$stored   = get_option( 'clanspress_notifications_settings', array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	return wp_parse_args( $stored, $defaults );
}

/**
 * Whether the Notifications extension should register the player profile Notifications subpage and template.
 *
 * @return bool
 */
function clanspress_notifications_subpage_player_enabled(): bool {
	if ( ! clanspress_notifications_extension_active() ) {
		return false;
	}

	$values = clanspress_notifications_settings_values();

	return ! empty( $values['subpage_player'] );
}

/**
 * Whether the notification bell should use blocking long-polling on `/notifications/poll`.
 *
 * When false (default), each poll returns after a single database read. When true, the request may
 * block up to the configured timeout. The filter {@see 'clanspress_notification_poll_blocking_wait'}
 * still runs after this value and can override it.
 *
 * @return bool
 */
function clanspress_notifications_poll_long_polling_enabled(): bool {
	if ( ! clanspress_notifications_extension_active() ) {
		return false;
	}

	$values = clanspress_notifications_settings_values();

	return ! empty( $values['poll_long_polling'] );
}

/**
 * Send a notification to a user.
 *
 * @param int                  $user_id User to notify.
 * @param string               $type    Notification type slug (e.g., 'team_invite', 'friend_request').
 * @param string               $title   Short notification title.
 * @param array<string, mixed> $args    {
 *     Optional arguments.
 *
 *     @type string $message     Longer message text.
 *     @type string $url         Link URL when notification is clicked.
 *     @type int    $actor_id    User who triggered the notification.
 *     @type string $object_type Related object type (e.g., 'team', 'post', 'comment').
 *     @type int    $object_id   Related object ID.
 *     @type array  $data        Additional data to store (will be JSON encoded).
 *     @type array  $actions     Action buttons. Each action is an array with:
 *                               - key: (string) Unique action identifier (e.g., 'accept', 'decline').
 *                               - label: (string) Button label.
 *                               - style: (string) 'primary', 'secondary', or 'danger'. Default 'secondary'.
 *                               - handler: (string) Handler identifier for the action.
 *                               - status: (string) Status to set after action ('accepted', 'declined', 'dismissed').
 *                               - success_message: (string) Message to show on success.
 *                               - confirm: (string|false) Confirmation message, or false for no confirm.
 *     @type bool   $dedupe      If true, won't create if similar notification exists. Default true.
 * }
 * @return int|\WP_Error Notification ID or error.
 *
 * @example
 * // Simple notification (no actions)
 * clanspress_notify( $user_id, 'mention', 'You were mentioned in a post', [
 *     'url' => $post_url,
 *     'actor_id' => $mentioner_id,
 * ] );
 *
 * @example
 * // Interactive notification with actions
 * clanspress_notify( $user_id, 'team_invite', sprintf( '%s invited you to join %s', $inviter_name, $team_name ), [
 *     'actor_id' => $inviter_id,
 *     'object_type' => 'team',
 *     'object_id' => $team_id,
 *     'url' => $team_url,
 *     'actions' => [
 *         [
 *             'key' => 'accept',
 *             'label' => __( 'Accept', 'clanspress' ),
 *             'style' => 'primary',
 *             'handler' => 'team_invite_accept',
 *             'status' => 'accepted',
 *             'success_message' => __( 'You have joined the team!', 'clanspress' ),
 *         ],
 *         [
 *             'key' => 'decline',
 *             'label' => __( 'Decline', 'clanspress' ),
 *             'style' => 'secondary',
 *             'handler' => 'team_invite_decline',
 *             'status' => 'declined',
 *             'success_message' => __( 'Invitation declined.', 'clanspress' ),
 *         ],
 *     ],
 * ] );
 */
function clanspress_notify( int $user_id, string $type, string $title, array $args = array() ) {
	if ( ! clanspress_notifications_extension_active() ) {
		return new \WP_Error(
			'notifications_inactive',
			__( 'The Clanspress Notifications extension is not enabled.', 'clanspress' )
		);
	}

	$dedupe = $args['dedupe'] ?? true;

	if ( $dedupe ) {
		$object_type = $args['object_type'] ?? '';
		$object_id   = $args['object_id'] ?? 0;
		$actor_id    = $args['actor_id'] ?? 0;

		if ( Notification_Data_Access::exists( $user_id, $type, $object_type, $object_id, $actor_id ) ) {
			return new \WP_Error( 'duplicate', __( 'Notification already exists.', 'clanspress' ) );
		}
	}

	$data = array_merge(
		$args,
		array(
			'user_id' => $user_id,
			'type'    => $type,
			'title'   => $title,
		)
	);

	unset( $data['dedupe'] );

	return Notification_Data_Access::insert( $data );
}

/**
 * Get notifications for a user.
 *
 * @param int  $user_id     User ID.
 * @param int  $page        Page number.
 * @param int  $per_page    Per page.
 * @param bool $unread_only Only unread notifications.
 * @return array{notifications: object[], total: int, unread_count: int}
 */
function clanspress_get_notifications( int $user_id, int $page = 1, int $per_page = 20, bool $unread_only = false ): array {
	if ( ! clanspress_notifications_extension_active() ) {
		return array(
			'notifications' => array(),
			'total'         => 0,
			'unread_count'  => 0,
		);
	}

	return Notification_Data_Access::get_for_user( $user_id, $page, $per_page, $unread_only );
}

/**
 * Get a single notification.
 *
 * @param int $notification_id Notification ID.
 * @return object|null
 */
function clanspress_get_notification( int $notification_id ): ?object {
	if ( ! clanspress_notifications_extension_active() ) {
		return null;
	}

	return Notification_Data_Access::get( $notification_id );
}

/**
 * Get unread notification count for a user.
 *
 * @param int $user_id User ID.
 * @return int
 */
function clanspress_get_unread_notification_count( int $user_id ): int {
	if ( ! clanspress_notifications_extension_active() ) {
		return 0;
	}

	return Notification_Data_Access::get_unread_count( $user_id );
}

/**
 * Mark a notification as read.
 *
 * @param int $notification_id Notification ID.
 * @param int $user_id         User ID (for permission check).
 * @return bool
 */
function clanspress_mark_notification_read( int $notification_id, int $user_id ): bool {
	if ( ! clanspress_notifications_extension_active() ) {
		return false;
	}

	return Notification_Data_Access::mark_read( $notification_id, $user_id );
}

/**
 * Mark all notifications as read for a user.
 *
 * @param int $user_id User ID.
 * @return int Number marked read.
 */
function clanspress_mark_all_notifications_read( int $user_id ): int {
	if ( ! clanspress_notifications_extension_active() ) {
		return 0;
	}

	return Notification_Data_Access::mark_all_read( $user_id );
}

/**
 * Delete a notification.
 *
 * @param int $notification_id Notification ID.
 * @param int $user_id         User ID (for permission check).
 * @return bool
 */
function clanspress_delete_notification( int $notification_id, int $user_id ): bool {
	if ( ! clanspress_notifications_extension_active() ) {
		return false;
	}

	return Notification_Data_Access::delete( $notification_id, $user_id );
}

/**
 * Delete all notifications for a user.
 *
 * @param int $user_id User ID.
 * @return int Number deleted.
 */
function clanspress_delete_all_notifications( int $user_id ): int {
	if ( ! clanspress_notifications_extension_active() ) {
		return 0;
	}

	return Notification_Data_Access::delete_all_for_user( $user_id );
}

/**
 * Delete notifications related to an object.
 *
 * Useful when deleting a team, post, etc.
 *
 * @param string $object_type Object type.
 * @param int    $object_id   Object ID.
 * @return int Number deleted.
 */
function clanspress_delete_notifications_for_object( string $object_type, int $object_id ): int {
	if ( ! clanspress_notifications_extension_active() ) {
		return 0;
	}

	return Notification_Data_Access::delete_by_object( $object_type, $object_id );
}

/**
 * Execute an action on a notification.
 *
 * @param int    $notification_id Notification ID.
 * @param string $action_key      Action key to execute.
 * @param int    $user_id         User ID (defaults to current user).
 * @return array{success: bool, message: string, redirect?: string}|\WP_Error
 */
function clanspress_execute_notification_action( int $notification_id, string $action_key, int $user_id = 0 ) {
	if ( ! clanspress_notifications_extension_active() ) {
		return new \WP_Error(
			'notifications_inactive',
			__( 'The Clanspress Notifications extension is not enabled.', 'clanspress' )
		);
	}

	if ( $user_id <= 0 ) {
		$user_id = get_current_user_id();
	}
	return Notification_Data_Access::execute_action( $notification_id, $user_id, $action_key );
}

/**
 * Dismiss a notification (mark as actioned without accepting/declining).
 *
 * @param int $notification_id Notification ID.
 * @param int $user_id         User ID (defaults to current user).
 * @return bool
 */
function clanspress_dismiss_notification( int $notification_id, int $user_id = 0 ): bool {
	if ( ! clanspress_notifications_extension_active() ) {
		return false;
	}

	if ( $user_id <= 0 ) {
		$user_id = get_current_user_id();
	}

	$notification = Notification_Data_Access::get( $notification_id );
	if ( ! $notification || (int) $notification->user_id !== $user_id ) {
		return false;
	}

	return Notification_Data_Access::update_status( $notification_id, Notification_Data_Access::STATUS_DISMISSED );
}

/**
 * Get the URL for the notifications page.
 *
 * @param int|null $user_id User ID (defaults to current user).
 * @return string
 */
function clanspress_get_notifications_url( ?int $user_id = null ): string {
	if ( ! clanspress_notifications_extension_active() ) {
		return '';
	}

	if ( null === $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( $user_id <= 0 ) {
		return '';
	}

	$profile_url = '';
	if ( function_exists( 'clanspress_get_player_profile_url' ) ) {
		$profile_url = (string) clanspress_get_player_profile_url( $user_id );
	}
	if ( '' === $profile_url ) {
		// Same canonical base as {@see clanspress_get_user_nav_menu_items()}: author URLs are rewritten to /players/{nicename}/.
		$profile_url = (string) get_author_posts_url( $user_id );
	}
	if ( '' === $profile_url ) {
		$user = get_userdata( $user_id );
		if ( $user instanceof \WP_User && is_string( $user->user_nicename ) && $user->user_nicename !== '' ) {
			$profile_url = home_url( '/players/' . $user->user_nicename );
		}
	}

	if ( '' !== $profile_url ) {
		return trailingslashit( $profile_url ) . 'notifications/';
	}

	return '';
}

/**
 * Get registered notification types with their labels and icons.
 *
 * @return array<string, array{label: string, icon: string}>
 */
function clanspress_get_notification_types(): array {
	$types = array(
		'team_invite'     => array(
			'label' => __( 'Team Invite', 'clanspress' ),
			'icon'  => 'groups',
		),
		'team_join'       => array(
			'label' => __( 'Team Join', 'clanspress' ),
			'icon'  => 'groups',
		),
		'team_role'       => array(
			'label' => __( 'Team Role Change', 'clanspress' ),
			'icon'  => 'admin-users',
		),
		'team_removed'    => array(
			'label' => __( 'Removed from Team', 'clanspress' ),
			'icon'  => 'dismiss',
		),
		'team_challenge'  => array(
			'label' => __( 'Team Challenge', 'clanspress' ),
			'icon'  => 'flag',
		),
		'team_match_event' => array(
			'label' => __( 'Team Match Event', 'clanspress' ),
			'icon'  => 'calendar-alt',
		),
		'team_event'       => array(
			'label' => __( 'Team Event', 'clanspress' ),
			'icon'  => 'calendar-alt',
		),
		'group_event'      => array(
			'label' => __( 'Group Event', 'clanspress' ),
			'icon'  => 'calendar-alt',
		),
		'mention'         => array(
			'label' => __( 'Mention', 'clanspress' ),
			'icon'  => 'format-status',
		),
		'system'          => array(
			'label' => __( 'System', 'clanspress' ),
			'icon'  => 'info',
		),
	);

	/**
	 * Filter registered notification types.
	 *
	 * Third-party developers can add their own types here.
	 *
	 * @param array<string, array{label: string, icon: string}> $types Notification types.
	 */
	return (array) apply_filters( 'clanspress_notification_types', $types );
}

/**
 * Render a notification for display.
 *
 * @param object $notification Notification object.
 * @param bool   $compact      Compact mode (for dropdown). Default false.
 * @return string HTML.
 */
function clanspress_render_notification( object $notification, bool $compact = false ): string {
	$types = clanspress_get_notification_types();
	$type_info = $types[ $notification->type ] ?? array(
		'label' => $notification->type,
		'icon'  => 'bell',
	);

	$time_ago = human_time_diff( strtotime( $notification->created_at ), time() );

	$classes = array( 'clanspress-notification' );
	$classes[] = $notification->is_read ? 'is-read' : 'is-unread';
	if ( $notification->is_actionable ) {
		$classes[] = 'is-actionable';
	}
	if ( $compact ) {
		$classes[] = 'is-compact';
	}
	if ( isset( $notification->status ) && 'pending' !== $notification->status ) {
		$classes[] = 'is-' . sanitize_key( (string) $notification->status );
	}

	$icon_slug = isset( $type_info['icon'] ) ? sanitize_key( (string) $type_info['icon'] ) : 'bell';
	if ( '' === $icon_slug ) {
		$icon_slug = 'bell';
	}

	$avatar_alt = __( 'User avatar', 'clanspress' );
	if ( isset( $notification->actor->name ) && is_string( $notification->actor->name ) && '' !== $notification->actor->name ) {
		$avatar_alt = sprintf(
			/* translators: %s: User display name. */
			__( 'Avatar for %s', 'clanspress' ),
			$notification->actor->name
		);
	}

	ob_start();
	?>
	<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-notification-id="<?php echo esc_attr( $notification->id ); ?>">
		<?php if ( isset( $notification->actor ) ) : ?>
			<div class="clanspress-notification__avatar">
				<img src="<?php echo esc_url( $notification->actor->avatar_url ); ?>" alt="<?php echo esc_attr( $avatar_alt ); ?>" loading="lazy" decoding="async" />
			</div>
		<?php else : ?>
			<div class="clanspress-notification__icon">
				<span class="dashicons dashicons-<?php echo esc_attr( $icon_slug ); ?>"></span>
			</div>
		<?php endif; ?>
		<div class="clanspress-notification__content">
			<div class="clanspress-notification__header">
				<?php if ( $notification->url && ! $notification->is_actionable ) : ?>
					<a href="<?php echo esc_url( $notification->url ); ?>" class="clanspress-notification__link">
						<span class="clanspress-notification__title"><?php echo esc_html( $notification->title ); ?></span>
					</a>
				<?php else : ?>
					<span class="clanspress-notification__title"><?php echo esc_html( $notification->title ); ?></span>
				<?php endif; ?>
				<span class="clanspress-notification__time"><?php echo esc_html( $time_ago ); ?></span>
			</div>
			<?php if ( $notification->message ) : ?>
				<p class="clanspress-notification__message"><?php echo esc_html( $notification->message ); ?></p>
			<?php endif; ?>
			<?php if ( $notification->is_actionable && is_array( $notification->actions ) ) : ?>
				<div class="clanspress-notification__actions">
					<?php foreach ( $notification->actions as $action ) : ?>
						<?php
						$style = $action['style'] ?? 'secondary';
						$confirm = $action['confirm'] ?? false;
						?>
						<button
							type="button"
							class="clanspress-notification__action clanspress-notification__action--<?php echo esc_attr( $style ); ?>"
							data-action="<?php echo esc_attr( $action['key'] ); ?>"
							data-notification-id="<?php echo esc_attr( $notification->id ); ?>"
							<?php if ( $confirm ) : ?>
								data-confirm="<?php echo esc_attr( $confirm ); ?>"
							<?php endif; ?>
						>
							<?php echo esc_html( $action['label'] ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php elseif ( isset( $notification->status ) && 'pending' !== $notification->status ) : ?>
				<div class="clanspress-notification__status">
					<?php
					$status_labels = array(
						'accepted'  => __( 'Accepted', 'clanspress' ),
						'declined'  => __( 'Declined', 'clanspress' ),
						'dismissed' => __( 'Dismissed', 'clanspress' ),
						'expired'   => __( 'Expired', 'clanspress' ),
					);
					echo esc_html( $status_labels[ $notification->status ] ?? $notification->status );
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( ! $notification->is_read && ! $notification->is_actionable ) : ?>
			<div class="clanspress-notification__unread-dot"></div>
		<?php endif; ?>
	</div>
	<?php
	$html = ob_get_clean();

	/**
	 * Filter the rendered notification HTML.
	 *
	 * @param string $html         Rendered HTML.
	 * @param object $notification Notification object.
	 * @param bool   $compact      Compact mode.
	 */
	return (string) apply_filters( 'clanspress_render_notification', $html, $notification, $compact );
}

/**
 * Render the full notifications list UI for the player notifications subpage (shortcode / block template).
 *
 * @return string HTML (empty when the extension is inactive).
 */
function clanspress_render_player_notifications_page_markup(): string {
	if ( ! clanspress_notifications_extension_active() ) {
		return '';
	}

	if ( ! is_user_logged_in() ) {
		return '';
	}

	$user_id      = get_current_user_id();
	$current_page = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$per_page     = 20;

	$result        = clanspress_get_notifications( $user_id, $current_page, $per_page );
	$notifications = $result['notifications'];
	$total         = $result['total'];
	$unread_count  = $result['unread_count'];
	$total_pages   = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;

	ob_start();
	?>
	<div class="clanspress-notifications-page">
		<div class="clanspress-notifications-page__header">
			<h1><?php esc_html_e( 'Notifications', 'clanspress' ); ?></h1>
			<?php if ( $unread_count > 0 ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="clanspress-notifications-page__mark-all">
					<?php wp_nonce_field( 'clanspress_mark_all_read', '_cpnonce' ); ?>
					<input type="hidden" name="action" value="clanspress_mark_all_notifications_read" />
					<button type="submit" class="clanspress-notifications-page__mark-all-btn">
						<?php esc_html_e( 'Mark all as read', 'clanspress' ); ?>
					</button>
				</form>
			<?php endif; ?>
		</div>

		<?php if ( empty( $notifications ) ) : ?>
			<div class="clanspress-notifications-page__empty">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="48" height="48" fill="currentColor" opacity="0.3">
					<path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z" />
				</svg>
				<p><?php esc_html_e( 'No notifications yet.', 'clanspress' ); ?></p>
			</div>
		<?php else : ?>
			<div class="clanspress-notifications-page__list">
				<?php foreach ( $notifications as $notification ) : ?>
					<?php echo clanspress_render_notification( $notification ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			</div>

			<?php if ( $total_pages > 1 ) : ?>
				<nav class="clanspress-notifications-page__pagination">
					<?php if ( $current_page > 1 ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1 ) ); ?>" class="clanspress-notifications-page__pagination-prev">
							&laquo; <?php esc_html_e( 'Previous', 'clanspress' ); ?>
						</a>
					<?php endif; ?>

					<span class="clanspress-notifications-page__pagination-info">
						<?php
						printf(
							/* translators: 1: current page, 2: total pages */
							esc_html__( 'Page %1$d of %2$d', 'clanspress' ),
							absint( $current_page ),
							absint( $total_pages )
						);
						?>
					</span>

					<?php if ( $current_page < $total_pages ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1 ) ); ?>" class="clanspress-notifications-page__pagination-next">
							<?php esc_html_e( 'Next', 'clanspress' ); ?> &raquo;
						</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<style>
		.clanspress-notifications-page {
			max-width: 700px;
			margin: 2rem auto;
			padding: 0 1rem;
		}
		.clanspress-notifications-page__header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin-bottom: 1.5rem;
			padding-bottom: 1rem;
			border-bottom: 1px solid rgba(0, 0, 0, 0.1);
		}
		.clanspress-notifications-page__header h1 {
			margin: 0;
			font-size: 1.5rem;
		}
		.clanspress-notifications-page__mark-all-btn {
			padding: 0.5rem 1rem;
			font-size: 0.875rem;
			color: var(--wp--preset--color--primary, #0073aa);
			background: transparent;
			border: 1px solid currentColor;
			border-radius: 4px;
			cursor: pointer;
		}
		.clanspress-notifications-page__mark-all-btn:hover {
			background: var(--wp--preset--color--primary, #0073aa);
			color: #fff;
		}
		.clanspress-notifications-page__empty {
			text-align: center;
			padding: 4rem 2rem;
			color: rgba(0, 0, 0, 0.5);
		}
		.clanspress-notifications-page__empty p {
			margin-top: 1rem;
			font-size: 1rem;
		}
		.clanspress-notifications-page__list {
			display: flex;
			flex-direction: column;
			border: 1px solid rgba(0, 0, 0, 0.1);
			border-radius: 8px;
			overflow: hidden;
		}
		.clanspress-notifications-page__list .clanspress-notification {
			padding: 1rem 1.25rem;
		}
		.clanspress-notifications-page__pagination {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 1.5rem;
			margin-top: 1.5rem;
			padding-top: 1rem;
		}
		.clanspress-notifications-page__pagination a {
			color: var(--wp--preset--color--primary, #0073aa);
			text-decoration: none;
		}
		.clanspress-notifications-page__pagination a:hover {
			text-decoration: underline;
		}
		.clanspress-notifications-page__pagination-info {
			color: rgba(0, 0, 0, 0.5);
			font-size: 0.875rem;
		}
	</style>
	<?php
	$html = (string) ob_get_clean();

	/**
	 * Filter the full notifications subpage markup.
	 *
	 * @param string $html HTML output.
	 */
	return (string) apply_filters( 'clanspress_player_notifications_page_markup', $html );
}

/**
 * Shortcode: notifications list for the player notifications template (`[clanspress_player_notifications]`).
 *
 * @param array<string, string> $atts Shortcode attributes (unused).
 * @return string
 */
function clanspress_player_notifications_shortcode( $atts = array() ): string {
	return clanspress_render_player_notifications_page_markup();
}
