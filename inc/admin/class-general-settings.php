<?php
/**
 * Core plugin settings (General tab in Clanspress admin).
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Admin;

defined( 'ABSPATH' ) || exit;


use Kernowdev\Clanspress\Extensions\Abstract_Settings;

/**
 * General Clanspress options stored in {@see General_Settings::OPTION_KEY}.
 */
class General_Settings extends Abstract_Settings {
	public const OPTION_KEY = 'clanspress_general_settings';

	protected string $option_key     = self::OPTION_KEY;
	protected string $settings_group = 'clanspress_general';
	protected string $page_slug      = 'clanspress-general';

	/**
	 * Unified React admin: do not add a separate submenu.
	 *
	 * @var bool
	 */
	protected bool $register_standalone_submenu = false;

	public function hooks(): void {
		parent::hooks();
		add_filter( 'show_admin_bar', array( $this, 'filter_show_admin_bar' ), 99 );
	}

	/**
	 * Hide the front-end WordPress toolbar for users without `manage_options` when the option is enabled.
	 *
	 * Super admins on multisite always keep the bar. `is_admin()` requests are unchanged.
	 *
	 * @param bool $show Whether WordPress would show the admin bar.
	 * @return bool
	 */
	public function filter_show_admin_bar( bool $show ): bool {
		if ( is_admin() ) {
			return $show;
		}

		if ( ! $this->get( 'hide_wp_admin_bar_for_non_admins', false ) ) {
			return $show;
		}

		if ( ! is_user_logged_in() ) {
			return $show;
		}

		$uid = get_current_user_id();
		if ( $uid > 0 && is_multisite() && is_super_admin( $uid ) ) {
			return $show;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return $show;
		}

		return false;
	}

	protected function get_page_title(): string {
		return __( 'Clanspress', 'clanspress' );
	}

	protected function get_menu_title(): string {
		return __( 'General', 'clanspress' );
	}

	protected function get_defaults(): array {
		// Filters run once in {@see Abstract_Settings::register_settings()} as `clanspress_general_settings_defaults`.
		return array(
			'admin_notes'                      => '',
			'events_enabled'                   => true,
			'wordban_enabled'                  => false,
			'wordban_custom_list'              => '',
			'hide_wp_admin_bar_for_non_admins' => false,
		);
	}

	protected function get_sections(): array {
		// Filters run once in {@see Abstract_Settings::register_settings()} as `clanspress_general_settings_sections`.
		return array(
			'overview' => array(
				'title'  => __( 'Overview', 'clanspress' ),
				'fields' => array(
					'admin_notes'    => array(
						'label'       => __( 'Internal notes', 'clanspress' ),
						'type'        => 'textarea',
						'description' => __( 'Optional notes for other site administrators (not shown on the front end).', 'clanspress' ),
						'default'     => '',
						'sanitize'    => 'sanitize_textarea_field',
					),
					'events_enabled' => array(
						'label'       => __( 'Enable scheduled events', 'clanspress' ),
						'type'        => 'checkbox',
						'description' => __( 'When off, team and group events, REST endpoints, and front-end event routes are disabled site-wide. Individual teams and groups can still turn events off when this is on.', 'clanspress' ),
						'default'     => true,
					),
					'hide_wp_admin_bar_for_non_admins' => array(
						'label'       => __( 'Hide WordPress toolbar on the front end for non-administrators', 'clanspress' ),
						'type'        => 'checkbox',
						'description' => __( 'When on, only users who can manage options (and super admins on multisite) see the admin bar while viewing the site. The dashboard and block editor are unchanged.', 'clanspress' ),
						'default'     => false,
					),
				),
			),
			'moderation' => array(
				'title'  => __( 'Moderation', 'clanspress' ),
				'fields' => array(
					'wordban_enabled'     => array(
						'label'       => __( 'Enable word filter', 'clanspress' ),
						'type'        => 'checkbox',
						'description' => __( 'When on, blocked words cannot be used in team names, group names, usernames, and similar short fields. The same list is masked in longer user-written content (for example social posts, comments, and forums): the first character stays visible and the rest is replaced with asterisks. A built-in list applies; you can add more below.', 'clanspress' ),
						'default'     => false,
					),
					'wordban_custom_list' => array(
						'label'       => __( 'Additional banned words', 'clanspress' ),
						'type'        => 'textarea',
						'description' => __( 'Comma- or line-separated. Multi-word phrases use each word only when it appears as a whole word. Common number or symbol substitutions (such as 1 for i or 3 for e) are treated like letters for matching.', 'clanspress' ),
						'default'     => '',
						'sanitize'    => 'sanitize_textarea_field',
						'depends_on'  => array(
							'field' => 'wordban_enabled',
							'value' => true,
						),
					),
				),
			),
		);
	}

	public function render_page(): void {
		$this->render_settings_page( __( 'Clanspress', 'clanspress' ) );
	}
}
