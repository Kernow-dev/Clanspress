<?php

namespace Kernowdev\Clanbite\Extensions\Teams;

defined( 'ABSPATH' ) || exit;


use Kernowdev\Clanbite\Extensions\Abstract_Settings;

/**
 * Teams admin settings and functionality.
 */
class Admin extends Abstract_Settings {
	protected string $option_key     = 'clanbite_teams_settings';
	protected string $settings_group = 'clanbite_teams';
	protected string $page_slug      = 'clanbite-teams';

	protected function get_page_title(): string {
		return __( 'Teams', 'clanbite' );
	}

	protected function get_menu_title(): string {
		return __( 'Teams', 'clanbite' );
	}

	protected function get_defaults(): array {
		return apply_filters(
			'clanbite_teams_defaults',
			array(
				'team_mode'                      => 'single_team',
				'player_team_membership'         => 'multiple',
				'default_team_avatar'            => '',
				'default_team_cover'             => '',
				'team_name_wordban_custom_list'  => '',
				'global_auto_join_team_ids'      => \clanbite_teams_global_auto_join_team_ids(),
				'events_profile_subpage'         => true,
				'team_avatar_image_size_large'   => 'clanbite-team-avatar-large',
				'team_avatar_image_size_medium'  => 'clanbite-team-avatar-medium',
				'team_avatar_image_size_small'   => 'clanbite-team-avatar-small',
				'team_avatar_shape'              => 'circle',
			)
		);
	}

	protected function get_sections(): array {
		return apply_filters(
			'clanbite_teams_sections',
			array(
				'general'  => array(
					'title'  => __( 'General', 'clanbite' ),
					'fields' => array(
						'team_mode'              => array(
							'label'       => __( 'Team mode', 'clanbite' ),
							'type'        => 'select',
							'description' => __( 'Choose how teams should behave for your community.', 'clanbite' ),
							'default'     => 'single_team',
							'options'     => $this->get_team_mode_options(),
							'sanitize'    => array( $this, 'sanitize_team_mode' ),
						),
						'player_team_membership' => array(
							'label'       => __( 'Player team membership', 'clanbite' ),
							'type'        => 'select',
							'description' => __( 'Single: a player may only belong to one team (invite search hides anyone who already leads a team). Multiple: no limit from this setting.', 'clanbite' ),
							'default'     => 'multiple',
							'options'     => $this->get_player_team_membership_options(),
							'sanitize'    => array( $this, 'sanitize_player_team_membership' ),
						),
						'global_auto_join_team_ids' => array(
							'label'              => __( 'Default team joins', 'clanbite' ),
							'type'               => 'post_id_list',
							'post_search_path'   => 'wp/v2/clanbite_team',
							'description'        => __( 'Every player is added to these team rosters as a member when they register or log in.', 'clanbite' ),
							'default'            => array(),
							'sanitize'           => static function ( $value ): array {
								if ( ! is_array( $value ) ) {
									return array();
								}
								return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
							},
						),
						'team_name_wordban_custom_list' => array(
							'label'       => __( 'Additional banned words for team names', 'clanbite' ),
							'type'        => 'textarea',
							'description' => __( 'Comma- or line-separated. These are enforced only for team names. When the global word filter is enabled, this list is added on top of it; when global is off, this list still applies to team names.', 'clanbite' ),
							'default'     => '',
							'sanitize'    => 'sanitize_textarea_field',
						),
					),
				),
				'integrations' => array(
					'title'  => __( 'Extension integrations', 'clanbite' ),
					'fields' => array(
						'events_profile_subpage' => array(
							'label'       => __( 'Team profile: Events tab', 'clanbite' ),
							'type'        => 'checkbox',
							'description' => __( 'When the Events extension is enabled, show the team Events tab and /teams/{slug}/events/. When off, those routes redirect to the public team profile.', 'clanbite' ),
							'default'     => true,
							'sanitize'    => 'rest_sanitize_boolean',
						),
				),
			),
			'team_avatar_appearance' => array(
				'title'  => __( 'Team avatar appearance', 'clanbite' ),
				'fields' => array(
					'team_avatar_shape' => array(
						'label'       => __( 'Avatar shape', 'clanbite' ),
						'type'        => 'select',
						'description' => __( 'Controls the shape of team avatars site-wide. The progress bar and rank icon will adapt to the selected shape.', 'clanbite' ),
						'default'     => 'circle',
						'options'     => array(
							'circle'  => __( 'Circle', 'clanbite' ),
							'square'  => __( 'Square', 'clanbite' ),
							'hexagon' => __( 'Hexagon', 'clanbite' ),
						),
						'sanitize'    => array( $this, 'sanitize_team_avatar_shape' ),
					),
				),
			),
			'team_avatar_sizes' => array(
					'title'  => __( 'Team avatar image sizes', 'clanbite' ),
					'fields' => array(
						'team_avatar_image_size_large' => array(
							'label'       => __( 'Large — team profiles', 'clanbite' ),
							'type'        => 'select',
							'description' => __( 'Team profile pages and the team avatar block. Preset “large” in code.', 'clanbite' ),
							'default'     => 'clanbite-team-avatar-large',
							'options'     => $this->get_team_avatar_image_size_options(),
							'sanitize'    => array( $this, 'sanitize_team_avatar_size_large' ),
						),
						'team_avatar_image_size_medium' => array(
							'label'       => __( 'Medium — forums and social-style feeds', 'clanbite' ),
							'type'        => 'select',
							'description' => __( 'Lists, cards, and feed-style team logos. Preset “medium”.', 'clanbite' ),
							'default'     => 'clanbite-team-avatar-medium',
							'options'     => $this->get_team_avatar_image_size_options(),
							'sanitize'    => array( $this, 'sanitize_team_avatar_size_medium' ),
						),
						'team_avatar_image_size_small' => array(
							'label'       => __( 'Small — compact UI', 'clanbite' ),
							'type'        => 'select',
							'description' => __( 'Small team marks in tight layouts. Preset “small”.', 'clanbite' ),
							'default'     => 'clanbite-team-avatar-small',
							'options'     => $this->get_team_avatar_image_size_options(),
							'sanitize'    => array( $this, 'sanitize_team_avatar_size_small' ),
						),
					),
				),
				'branding' => array(
					'title'  => __( 'Branding defaults', 'clanbite' ),
					'fields' => array(
						'default_team_avatar' => array(
							'label'       => __( 'Default team avatar image', 'clanbite' ),
							'type'        => 'image',
							'description' => __( 'Shown when a team has no avatar set. Leave empty to use the plugin bundled image.', 'clanbite' ),
							'default'     => '',
							'fallback_url' => \clanbite_teams_get_default_avatar_url( 0 ),
							'sanitize'    => 'esc_url_raw',
						),
						'default_team_cover'  => array(
							'label'       => __( 'Default team cover image', 'clanbite' ),
							'type'        => 'image',
							'description' => __( 'Shown when a team has no cover image set. Leave empty to use the plugin bundled image.', 'clanbite' ),
							'default'     => '',
							'fallback_url' => \clanbite_teams_get_default_cover_url( 0 ),
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
			'multiple' => __( 'Multiple teams', 'clanbite' ),
			'single'   => __( 'Single team only', 'clanbite' ),
		);

		/**
		 * Filter global player team membership options (Teams settings UI).
		 *
		 * @param array $options Key-value map.
		 * @param Admin $admin   Teams admin instance.
		 */
		return (array) apply_filters( 'clanbite_teams_player_team_membership_options', $options, $this );
	}

	/**
	 * Get available teams mode options.
	 *
	 * @return array<string, string>
	 */
	public function get_team_mode_options(): array {
		$options = array(
			'single_team'      => __( 'Single team (sports team style)', 'clanbite' ),
			'multiple_teams'   => __( 'Multiple teams (clan style)', 'clanbite' ),
			'team_directories' => __( 'Team directories (users create teams)', 'clanbite' ),
		);

		/**
		 * Filter teams mode options.
		 *
		 * @param array $options Mode options keyed by mode slug.
		 * @param Admin $admin   Teams admin settings instance.
		 */
		return (array) apply_filters( 'clanbite_teams_mode_options', $options, $this );
	}

	public function render_page(): void {
		$this->render_settings_page( __( 'Teams', 'clanbite' ) );
	}

	/**
	 * Options for team avatar image size dropdowns.
	 *
	 * @return array<string, string>
	 */
	protected function get_team_avatar_image_size_options(): array {
		return function_exists( 'clanbite_players_get_image_size_choices_for_settings' )
			? clanbite_players_get_image_size_choices_for_settings()
			: array(
				'thumbnail' => __( 'Thumbnail', 'clanbite' ),
				'medium'    => __( 'Medium', 'clanbite' ),
				'large'     => __( 'Large', 'clanbite' ),
				'full'      => __( 'Full size', 'clanbite' ),
			);
	}

	/**
	 * @param mixed $value Raw setting.
	 * @return string
	 */
	public function sanitize_team_avatar_size_large( $value ): string {
		return function_exists( 'clanbite_players_sanitize_image_size_setting_value' )
			? clanbite_players_sanitize_image_size_setting_value( (string) $value, 'clanbite-team-avatar-large' )
			: 'clanbite-team-avatar-large';
	}

	/**
	 * @param mixed $value Raw setting.
	 * @return string
	 */
	public function sanitize_team_avatar_size_medium( $value ): string {
		return function_exists( 'clanbite_players_sanitize_image_size_setting_value' )
			? clanbite_players_sanitize_image_size_setting_value( (string) $value, 'clanbite-team-avatar-medium' )
			: 'clanbite-team-avatar-medium';
	}

	/**
	 * @param mixed $value Raw setting.
	 * @return string
	 */
	public function sanitize_team_avatar_size_small( $value ): string {
		return function_exists( 'clanbite_players_sanitize_image_size_setting_value' )
			? clanbite_players_sanitize_image_size_setting_value( (string) $value, 'clanbite-team-avatar-small' )
			: 'clanbite-team-avatar-small';
	}

	/**
	 * Sanitize team avatar shape setting.
	 *
	 * @param mixed $value Raw setting value.
	 * @return string
	 */
	public function sanitize_team_avatar_shape( $value ): string {
		$value = sanitize_key( (string) $value );
		if ( ! in_array( $value, array( 'circle', 'square', 'hexagon' ), true ) ) {
			return 'circle';
		}
		return $value;
	}
}
