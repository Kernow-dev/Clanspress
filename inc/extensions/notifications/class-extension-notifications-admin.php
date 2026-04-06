<?php
/**
 * Option-backed settings for the Notifications extension (unified React admin tab).
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Extensions\Notifications;

defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanspress\Extensions\Abstract_Settings;

/**
 * Notifications extension settings for the unified React admin (`clanspress_notifications_settings`).
 */
class Admin extends Abstract_Settings {
	protected string $option_key     = 'clanspress_notifications_settings';
	protected string $settings_group = 'clanspress_notifications';
	protected string $page_slug      = 'clanspress-notifications';

	/**
	 * Browser title for the legacy standalone settings page (if enabled).
	 *
	 * @return string
	 */
	protected function get_page_title(): string {
		return __( 'Notifications', 'clanspress' );
	}

	/**
	 * Menu title for the legacy standalone settings page (if enabled).
	 *
	 * @return string
	 */
	protected function get_menu_title(): string {
		return __( 'Notifications', 'clanspress' );
	}

	/**
	 * Default option values before registration and filters run.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_defaults(): array {
		return array(
			'subpage_player'    => true,
			'poll_long_polling' => false,
		);
	}

	/**
	 * Section and field definitions for the settings API / REST schema export.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function get_sections(): array {
		return array(
			'profile_subpages' => array(
				'title'  => __( 'Profile subpages', 'clanspress' ),
				'fields' => array(
					'subpage_player' => array(
						'label'       => __( 'Player profile: Notifications tab', 'clanspress' ),
						'type'        => 'checkbox',
						'description' => __( 'When off, the notifications template and tab are omitted and that URL redirects to the profile root.', 'clanspress' ),
						'default'     => true,
						'sanitize'    => 'rest_sanitize_boolean',
					),
				),
			),
			'notification_bell' => array(
				'title'  => __( 'Notification bell', 'clanspress' ),
				'fields' => array(
					'poll_long_polling' => array(
						'label'       => __( 'Use long-polling', 'clanspress' ),
						'type'        => 'checkbox',
						'description' => __( 'When enabled, poll requests may stay open until new notifications arrive or a timeout is reached (fewer HTTP round-trips). When off, each poll checks once and returns immediately (better for busy sites and limited PHP workers). Developers can still override behavior with the clanspress_notification_poll_blocking_wait filter.', 'clanspress' ),
						'default'     => false,
						'sanitize'    => 'rest_sanitize_boolean',
					),
				),
			),
		);
	}

	/**
	 * Render the classic PHP settings page shell (submenu mode only).
	 *
	 * @return void
	 */
	public function render_page(): void {
		$this->render_settings_page( __( 'Notifications', 'clanspress' ) );
	}
}
