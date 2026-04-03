<?php
/**
 * Core plugin settings (General tab in Clanspress admin).
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Admin;

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

	protected function get_page_title(): string {
		return __( 'Clanspress', 'clanspress' );
	}

	protected function get_menu_title(): string {
		return __( 'General', 'clanspress' );
	}

	protected function get_defaults(): array {
		// Filters run once in {@see Abstract_Settings::register_settings()} as `clanspress_general_settings_defaults`.
		return array(
			'admin_notes'    => '',
			'events_enabled' => true,
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
				),
			),
		);
	}

	public function render_page(): void {
		$this->render_settings_page( __( 'Clanspress', 'clanspress' ) );
	}
}
