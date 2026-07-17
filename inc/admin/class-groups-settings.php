<?php
/**
 * Group profile integration options (unified React admin tab).
 *
 * Core does not ship a `cp_groups` extension; group CPTs and templates come from add-ons.
 * These settings control how Clanbite features attach to group profiles.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Admin;

defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanbite\Extensions\Abstract_Settings;

/**
 * Options stored in {@see Groups_Settings::OPTION_KEY}.
 */
class Groups_Settings extends Abstract_Settings {
	public const OPTION_KEY = 'clanbite_groups_settings';

	protected string $option_key     = self::OPTION_KEY;
	protected string $settings_group = 'clanbite_groups';
	protected string $page_slug      = 'clanbite-groups';

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
		return __( 'Groups', 'clanbite' );
	}

	/**
	 * Menu title for the legacy standalone settings page (if enabled).
	 *
	 * @return string
	 */
	protected function get_menu_title(): string {
		return __( 'Groups', 'clanbite' );
	}

	/**
	 * Default option values before registration and filters run.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_defaults(): array {
		return array(
			'events_profile_subpage'         => true,
			'group_name_wordban_custom_list' => '',
			'group_avatar_shape'             => 'circle',
		);
	}

	/**
	 * Section and field definitions for the settings API / REST schema export.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function get_sections(): array {
		return array(
			'avatar_appearance' => array(
				'title'  => __( 'Group avatar appearance', 'clanbite' ),
				'fields' => array(
					'group_avatar_shape' => array(
						'label'       => __( 'Avatar shape', 'clanbite' ),
						'type'        => 'avatar_shape_radio',
						'description' => __( 'Controls the shape of group avatars site-wide. The progress bar and rank icon will adapt to the selected shape.', 'clanbite' ),
						'default'     => 'circle',
						'options'     => array(
							array(
								'label' => __( 'Circle', 'clanbite' ),
								'value' => 'circle',
							),
							array(
								'label' => __( 'Square', 'clanbite' ),
								'value' => 'square',
							),
							array(
								'label' => __( 'Hexagon', 'clanbite' ),
								'value' => 'hexagon',
							),
						),
						'sanitize'    => array( $this, 'sanitize_group_avatar_shape' ),
					),
				),
			),
			'integrations' => array(
				'title'  => __( 'Extension integrations', 'clanbite' ),
				'fields' => array(
					'events_profile_subpage' => array(
						'label'       => __( 'Group profile: Events tab', 'clanbite' ),
						'type'        => 'checkbox',
						'description' => __( 'When the Events extension is enabled, register the group Events subpage and block template. When off, those integrations are not loaded.', 'clanbite' ),
						'default'     => true,
						'sanitize'    => 'rest_sanitize_boolean',
					),
					'group_name_wordban_custom_list' => array(
						'label'       => __( 'Additional banned words for group names', 'clanbite' ),
						'type'        => 'textarea',
						'description' => __( 'Comma- or line-separated. These are enforced only for group names. When the global word filter is enabled, this list is added on top of it; when global is off, this list still applies to group names.', 'clanbite' ),
						'default'     => '',
						'sanitize'    => 'sanitize_textarea_field',
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
		$this->render_settings_page( __( 'Groups', 'clanbite' ) );
	}

	/**
	 * Sanitize group avatar shape setting.
	 *
	 * @param mixed $value User input.
	 * @return string Valid shape or default.
	 */
	public function sanitize_group_avatar_shape( $value ): string {
		$value = sanitize_key( (string) $value );
		if ( ! in_array( $value, array( 'circle', 'square', 'hexagon' ), true ) ) {
			return 'circle';
		}
		return $value;
	}
}
