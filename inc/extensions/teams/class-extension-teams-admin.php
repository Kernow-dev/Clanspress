<?php

namespace Kernowdev\Clanspress\Extensions\Teams;

use Kernowdev\Clanspress\Extensions\Abstract_Settings;

/**
 * Teams admin settings and functionality.
 */
class Admin extends Abstract_Settings {
	protected string $option_key     = 'clanspress_teams_settings';
	protected string $settings_group = 'clanspress_teams';
	protected string $page_slug      = 'clanspress-teams';

	protected function get_page_title(): string {
		return __( 'Teams', 'clanspress' );
	}

	protected function get_menu_title(): string {
		return __( 'Teams', 'clanspress' );
	}

	protected function get_defaults(): array {
		return apply_filters(
			'clanspress_teams_defaults',
			array(
				'team_mode'              => 'single_team',
				'player_team_membership' => 'multiple',
				'default_team_avatar'    => '',
				'default_team_cover'     => '',
			)
		);
	}

	protected function get_sections(): array {
		return apply_filters(
			'clanspress_teams_sections',
			array(
				'general'  => array(
					'title'  => __( 'General', 'clanspress' ),
					'fields' => array(
						'team_mode'              => array(
							'label'       => __( 'Team mode', 'clanspress' ),
							'type'        => 'select',
							'description' => __( 'Choose how teams should behave for your community.', 'clanspress' ),
							'default'     => 'single_team',
							'options'     => $this->get_team_mode_options(),
							'sanitize'    => array( $this, 'sanitize_team_mode' ),
						),
						'player_team_membership' => array(
							'label'       => __( 'Player team membership', 'clanspress' ),
							'type'        => 'select',
							'description' => __( 'Single: a player may only belong to one team (invite search hides anyone who already leads a team). Multiple: no limit from this setting.', 'clanspress' ),
							'default'     => 'multiple',
							'options'     => $this->get_player_team_membership_options(),
							'sanitize'    => array( $this, 'sanitize_player_team_membership' ),
						),
					),
				),
				'branding' => array(
					'title'  => __( 'Branding defaults', 'clanspress' ),
					'fields' => array(
						'default_team_avatar' => array(
							'label'       => __( 'Default team avatar image URL', 'clanspress' ),
							'type'        => 'text',
							'description' => __( 'Used when a team has no avatar set. Leave empty to use the plugin bundled image.', 'clanspress' ),
							'default'     => '',
							'sanitize'    => 'esc_url_raw',
						),
						'default_team_cover'  => array(
							'label'       => __( 'Default team cover image URL', 'clanspress' ),
							'type'        => 'text',
							'description' => __( 'Used when a team has no cover image set. Leave empty to use the plugin bundled image.', 'clanspress' ),
							'default'     => '',
							'sanitize'    => 'esc_url_raw',
						),
					),
				),
			)
		);
	}

	/**
	 * Sanitize teams mode setting.
	 *
	 * @param mixed $value Raw setting value.
	 * @return string
	 */
	public function sanitize_team_mode( $value ): string {
		$allowed_modes = array_keys( $this->get_team_mode_options() );
		$value         = sanitize_key( (string) $value );

		if ( ! in_array( $value, $allowed_modes, true ) ) {
			return 'single_team';
		}

		return $value;
	}

	/**
	 * Sanitize player team membership setting.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_player_team_membership( $value ): string {
		$allowed = array_keys( $this->get_player_team_membership_options() );
		$value   = sanitize_key( (string) $value );

		if ( ! in_array( $value, $allowed, true ) ) {
			return 'multiple';
		}

		return $value;
	}

	/**
	 * Options for how many teams a player may belong to (global).
	 *
	 * @return array<string, string>
	 */
	public function get_player_team_membership_options(): array {
		$options = array(
			'multiple' => __( 'Multiple teams', 'clanspress' ),
			'single'   => __( 'Single team only', 'clanspress' ),
		);

		/**
		 * Filter global player team membership options (Teams settings UI).
		 *
		 * @param array $options Key-value map.
		 * @param Admin $admin   Teams admin instance.
		 */
		return (array) apply_filters( 'clanspress_teams_player_team_membership_options', $options, $this );
	}

	/**
	 * Get available teams mode options.
	 *
	 * @return array<string, string>
	 */
	public function get_team_mode_options(): array {
		$options = array(
			'single_team'      => __( 'Single team (sports team style)', 'clanspress' ),
			'multiple_teams'   => __( 'Multiple teams (clan style)', 'clanspress' ),
			'team_directories' => __( 'Team directories (users create teams)', 'clanspress' ),
		);

		/**
		 * Filter teams mode options.
		 *
		 * @param array $options Mode options keyed by mode slug.
		 * @param Admin $admin   Teams admin settings instance.
		 */
		return (array) apply_filters( 'clanspress_teams_mode_options', $options, $this );
	}

	public function render_page(): void {
		$this->render_settings_page( __( 'Teams', 'clanspress' ) );
	}
}
