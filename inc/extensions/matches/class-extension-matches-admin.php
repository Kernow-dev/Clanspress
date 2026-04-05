<?php
/**
 * Option-backed settings for the Matches extension (unified React admin tab).
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Extensions\Matches;

defined( 'ABSPATH' ) || exit;


use Kernowdev\Clanspress\Extensions\Abstract_Settings;

/**
 * Persists display-related options under `clanspress_matches_settings`.
 */
class Admin extends Abstract_Settings {
	protected string $option_key     = 'clanspress_matches_settings';
	protected string $settings_group = 'clanspress_matches';
	protected string $page_slug      = 'clanspress-matches';

	/**
	 * Browser title for the legacy standalone settings page (if enabled).
	 *
	 * @return string
	 */
	protected function get_page_title(): string {
		return __( 'Matches', 'clanspress' );
	}

	/**
	 * Menu title for the legacy standalone settings page (if enabled).
	 *
	 * @return string
	 */
	protected function get_menu_title(): string {
		return __( 'Matches', 'clanspress' );
	}

	/**
	 * Default option values before registration and filters run.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_defaults(): array {
		/**
		 * Filter default Matches extension settings before they are merged with stored options.
		 *
		 * @param array<string, mixed> $defaults Keyed by option field id.
		 */
		return apply_filters(
			'clanspress_matches_defaults',
			array(
				'list_per_page'      => 10,
				'datetime_format'    => 'M j, Y g:i a',
				'show_scores'        => true,
				'default_list_limit' => 10,
				'subpage_team'       => true,
			)
		);
	}

	/**
	 * Section and field definitions for the settings API / REST schema export.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function get_sections(): array {
		/**
		 * Filter Matches settings sections (tabs/sections in React or classic UI).
		 *
		 * @param array<string, array<string, mixed>> $sections Section id => config with `title` and `fields`.
		 */
		return apply_filters(
			'clanspress_matches_sections',
			array(
				'profile_subpages' => array(
					'title'  => __( 'Profile subpages', 'clanspress' ),
					'fields' => array(
						'subpage_team' => array(
							'label'       => __( 'Team profile: Matches tab', 'clanspress' ),
							'type'        => 'checkbox',
							'description' => __( 'When off, the team matches list URL is omitted and redirects to the public team profile. Flush permalinks after changing this if URLs do not update immediately.', 'clanspress' ),
							'default'     => true,
							'sanitize'    => 'rest_sanitize_boolean',
						),
					),
				),
				'display'          => array(
					'title'  => __( 'Display', 'clanspress' ),
					'fields' => array(
						'list_per_page'        => array(
							'label'       => __( 'REST / admin list size', 'clanspress' ),
							'type'        => 'text',
							'description' => __( 'Maximum matches returned per page in the REST API and admin helpers.', 'clanspress' ),
							'default'     => 10,
							'sanitize'    => 'absint',
						),
						'default_list_limit'   => array(
							'label'       => __( 'Default block list limit', 'clanspress' ),
							'type'        => 'text',
							'description' => __( 'Default number of matches for the Match list block when the block attribute is unset.', 'clanspress' ),
							'default'     => 10,
							'sanitize'    => 'absint',
						),
						'datetime_format'      => array(
							'label'       => __( 'Date/time format', 'clanspress' ),
							'type'        => 'text',
							'description' => __( 'PHP date format used on the front end (see wp_date).', 'clanspress' ),
							'default'     => 'M j, Y g:i a',
							'sanitize'    => 'sanitize_text_field',
						),
						'show_scores'          => array(
							'label'       => __( 'Show scores in blocks', 'clanspress' ),
							'type'        => 'checkbox',
							'description' => __( 'When unchecked, blocks hide scores until you turn this back on.', 'clanspress' ),
							'default'     => true,
							'sanitize'    => 'rest_sanitize_boolean',
						),
					),
				),
			)
		);
	}

	/**
	 * Render the classic PHP settings page shell (submenu mode only).
	 *
	 * @return void
	 */
	public function render_page(): void {
		$this->render_settings_page( __( 'Matches', 'clanspress' ) );
	}
}
