<?php

namespace Kernowdev\Clanspress\Extensions\Teams;

defined( 'ABSPATH' ) || exit;


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
				'team_mode'                      => 'single_team',
				'player_team_membership'         => 'multiple',
				'default_team_avatar'            => '',
				'default_team_cover'             => '',
				'team_name_wordban_custom_list'  => '',
				'events_profile_subpage'         => true,
				'team_avatar_image_size_large'   => 'clanspress-team-avatar-large',
				'team_avatar_image_size_medium'  => 'clanspress-team-avatar-medium',
				'team_avatar_image_size_small'   => 'clanspress-team-avatar-small',
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
						'team_name_wordban_custom_list' => array(
							'label'       => __( 'Additional banned words for team names', 'clanspress' ),
							'type'        => 'textarea',
							'description' => __( 'Comma- or line-separated. These are enforced only for team names. When the global word filter is enabled, this list is added on top of it; when global is off, this list still applies to team names.', 'clanspress' ),
							'default'     => '',
							'sanitize'    => 'sanitize_textarea_field',
						),
					),
				),
				'integrations' => array(
					'title'  => __( 'Extension integrations', 'clanspress' ),
					'fields' => array(
						'events_profile_subpage' => array(
							'label'       => __( 'Team profile: Events tab', 'clanspress' ),
							'type'        => 'checkbox',
							'description' => __( 'When the Events extension is enabled, show the team Events tab and /teams/{slug}/events/. When off, those routes redirect to the public team profile.', 'clanspress' ),
							'default'     => true,
							'sanitize'    => 'rest_sanitize_boolean',
						),
					),
				),
				'team_avatar_sizes' => array(
					'title'  => __( 'Team avatar image sizes', 'clanspress' ),
					'fields' => array(
						'team_avatar_image_size_large' => array(
							'label'       => __( 'Large — team profiles', 'clanspress' ),
							'type'        => 'select',
							'description' => __( 'Team profile pages and the team avatar block. Preset “large” in code.', 'clanspress' ),
							'default'     => 'clanspress-team-avatar-large',
							'options'     => $this->get_team_avatar_image_size_options(),
							'sanitize'    => array( $this, 'sanitize_team_avatar_size_large' ),
						),
						'team_avatar_image_size_medium' => array(
							'label'       => __( 'Medium — forums and social-style feeds', 'clanspress' ),
							'type'        => 'select',
							'description' => __( 'Lists, cards, and feed-style team logos. Preset “medium”.', 'clanspress' ),
							'default'     => 'clanspress-team-avatar-medium',
							'options'     => $this->get_team_avatar_image_size_options(),
							'sanitize'    => array( $this, 'sanitize_team_avatar_size_medium' ),
						),
						'team_avatar_image_size_small' => array(
							'label'       => __( 'Small — compact UI', 'clanspress' ),
							'type'        => 'select',
							'description' => __( 'Small team marks in tight layouts. Preset “small”.', 'clanspress' ),
							'default'     => 'clanspress-team-avatar-small',
							'options'     => $this->get_team_avatar_image_size_options(),
							'sanitize'    => array( $this, 'sanitize_team_avatar_size_small' ),
						),
					),
				),
				'branding' => array(
					'title'  => __( 'Branding defaults', 'clanspress' ),
					'fields' => array(
						'default_team_avatar' => array(
							'label'       => __( 'Default team avatar image', 'clanspress' ),
							'type'        => 'image',
							'description' => __( 'Shown when a team has no avatar set. Leave empty to use the plugin bundled image.', 'clanspress' ),
							'default'     => '',
							'fallback_url' => \clanspress_teams_get_default_avatar_url( 0 ),
							'sanitize'    => 'esc_url_raw',
						),
						'default_team_cover'  => array(
							'label'       => __( 'Default team cover image', 'clanspress' ),
							'type'        => 'image',
							'description' => __( 'Shown when a team has no cover image set. Leave empty to use the plugin bundled image.', 'clanspress' ),
							'default'     => '',
							'fallback_url' => \clanspress_teams_get_default_cover_url( 0 ),
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

	/**
	 * Options for team avatar image size dropdowns.
	 *
	 * @return array<string, string>
	 */
	protected function get_team_avatar_image_size_options(): array {
		return function_exists( 'clanspress_players_get_image_size_choices_for_settings' )
			? clanspress_players_get_image_size_choices_for_settings()
			: array(
				'thumbnail' => __( 'Thumbnail', 'clanspress' ),
				'medium'    => __( 'Medium', 'clanspress' ),
				'large'     => __( 'Large', 'clanspress' ),
				'full'      => __( 'Full size', 'clanspress' ),
			);
	}

	/**
	 * @param mixed $value Raw setting.
	 * @return string
	 */
	public function sanitize_team_avatar_size_large( $value ): string {
		return function_exists( 'clanspress_players_sanitize_image_size_setting_value' )
			? clanspress_players_sanitize_image_size_setting_value( (string) $value, 'clanspress-team-avatar-large' )
			: 'clanspress-team-avatar-large';
	}

	/**
	 * @param mixed $value Raw setting.
	 * @return string
	 */
	public function sanitize_team_avatar_size_medium( $value ): string {
		return function_exists( 'clanspress_players_sanitize_image_size_setting_value' )
			? clanspress_players_sanitize_image_size_setting_value( (string) $value, 'clanspress-team-avatar-medium' )
			: 'clanspress-team-avatar-medium';
	}

	/**
	 * @param mixed $value Raw setting.
	 * @return string
	 */
	public function sanitize_team_avatar_size_small( $value ): string {
		return function_exists( 'clanspress_players_sanitize_image_size_setting_value' )
			? clanspress_players_sanitize_image_size_setting_value( (string) $value, 'clanspress-team-avatar-small' )
			: 'clanspress-team-avatar-small';
	}
}
