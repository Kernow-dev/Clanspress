<?php
/**
 * Group profile integration options (unified React admin tab).
 *
 * Core does not ship a `cp_groups` extension; group CPTs and templates come from add-ons.
 * These settings control how Clanspress features attach to group profiles.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Admin;

defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanspress\Extensions\Abstract_Settings;

/**
 * Options stored in {@see Groups_Settings::OPTION_KEY}.
 */
class Groups_Settings extends Abstract_Settings {
	public const OPTION_KEY = 'clanspress_groups_settings';

	protected string $option_key     = self::OPTION_KEY;
	protected string $settings_group = 'clanspress_groups';
	protected string $page_slug      = 'clanspress-groups';

	/**
	 * Unified React admin: do not add a separate submenu.
	 *
	 * @var bool
	 */
	protected bool $register_standalone_submenu = false;

	/**
	 * Browser title for the legacy standalone settings page (if enabled).
	 *
	 * @return string
	 */
	protected function get_page_title(): string {
		return __( 'Groups', 'clanspress' );
	}

	/**
	 * Menu title for the legacy standalone settings page (if enabled).
	 *
	 * @return string
	 */
	protected function get_menu_title(): string {
		return __( 'Groups', 'clanspress' );
	}

	/**
	 * Default option values before registration and filters run.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_defaults(): array {
		return array(
			'events_profile_subpage' => true,
		);
	}

	/**
	 * Section and field definitions for the settings API / REST schema export.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function get_sections(): array {
		return array(
			'integrations' => array(
				'title'  => __( 'Extension integrations', 'clanspress' ),
				'fields' => array(
					'events_profile_subpage' => array(
						'label'       => __( 'Group profile: Events tab', 'clanspress' ),
						'type'        => 'checkbox',
						'description' => __( 'When the Events extension is enabled, register the group Events subpage and block template. When off, those integrations are not loaded.', 'clanspress' ),
						'default'     => true,
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
		$this->render_settings_page( __( 'Groups', 'clanspress' ) );
	}
}
