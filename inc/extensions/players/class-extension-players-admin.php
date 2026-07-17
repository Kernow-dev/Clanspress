<?php

namespace Kernowdev\Clanbite\Extensions\Players;
defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanbite\Extensions\Abstract_Settings;

/**
 * Players admin settings and functionality.
 */
class Admin extends Abstract_Settings {
	protected string $option_key     = 'clanbite_players_settings';
	protected string $settings_group = 'clanbite_players';
	protected string $page_slug      = 'clanbite-players';

	protected function get_page_title(): string {
		return __( 'Players', 'clanbite' );
	}

	protected function get_menu_title(): string {
		return __( 'Players', 'clanbite' );
	}

	protected function get_defaults(): array {
		return apply_filters(
			'clanbite_players_defaults',
			array(
				'enable_profiles'                 => true,
				'enable_avatars'                  => true,
				'enable_covers'                   => true,
				'default_avatar'                  => '',
				'default_cover'                   => '',
				'events_profile_subpage'          => true,
				'rank_status_updates_enabled'     => true,
				'player_avatar_image_size_large'  => 'clanbite-avatar-large',
				'player_avatar_image_size_medium' => 'clanbite-avatar-medium',
				'player_avatar_image_size_small'  => 'clanbite-avatar-small',
				'player_avatar_shape'             => 'circle',
			)
		);
	}

	protected function get_sections(): array {
		$sections = array(
				'general'  => array(
					'title'  => __( 'General', 'clanbite' ),
					'fields' => array(
						'enable_profiles' => array(
							'label'       => __( 'Enable profiles', 'clanbite' ),
							'type'        => 'checkbox',
							'description' => __( 'Enable public player pages.', 'clanbite' ),
							'default'     => true,
							'sanitize'    => 'rest_sanitize_boolean',
						),
						'enable_avatars'  => array(
							'label'       => __( 'Enable avatars', 'clanbite' ),
							'type'        => 'checkbox',
							'description' => __( 'Enable player custom avatars.', 'clanbite' ),
							'default'     => true,
							'sanitize'    => 'rest_sanitize_boolean',
						),
						'enable_covers'   => array(
							'label'       => __( 'Enable cover images', 'clanbite' ),
							'type'        => 'checkbox',
							'description' => __( 'Enable player custom cover images.', 'clanbite' ),
							'default'     => true,
							'sanitize'    => 'rest_sanitize_boolean',
						),
					),
				),
				'integrations' => array(
					'title'  => __( 'Extension integrations', 'clanbite' ),
					'fields' => array(
						'events_profile_subpage' => array(
							'label'       => __( 'Player profile: Events tab', 'clanbite' ),
							'type'        => 'checkbox',
							'description' => __( 'When the Events extension is enabled, show the Events tab and /players/{user}/events/. When off, that URL redirects to the profile root.', 'clanbite' ),
							'default'     => true,
							'sanitize'    => 'rest_sanitize_boolean',
						),
				),
			),
			'avatar_appearance' => array(
				'title'  => __( 'Player avatar appearance', 'clanbite' ),
				'fields' => array(
					'player_avatar_shape' => array(
						'label'       => __( 'Avatar shape', 'clanbite' ),
						'type'        => 'select',
						'description' => __( 'Controls the shape of player avatars site-wide. The progress bar and rank icon will adapt to the selected shape.', 'clanbite' ),
						'default'     => 'circle',
						'options'     => array(
							'circle'  => __( 'Circle', 'clanbite' ),
							'square'  => __( 'Square', 'clanbite' ),
							'hexagon' => __( 'Hexagon', 'clanbite' ),
						),
						'sanitize'    => array( $this, 'sanitize_player_avatar_shape' ),
					),
				),
			),
			'avatar_sizes' => array(
					'title'  => __( 'Player avatar image sizes', 'clanbite' ),
					'fields' => array(
						'player_avatar_image_size_large'  => array(
							'label'       => __( 'Large — player profiles', 'clanbite' ),
							'type'        => 'select',
							'description' => __( 'Used for profile pages, the player avatar block, and other prominent displays. Code may request this with preset “large”.', 'clanbite' ),
							'default'     => 'clanbite-avatar-large',
							'options'     => $this->get_avatar_image_size_options(),
							'sanitize'    => array( $this, 'sanitize_player_avatar_size_large' ),
						),
						'player_avatar_image_size_medium' => array(
							'label'       => __( 'Medium — forums and social-style feeds', 'clanbite' ),
							'type'        => 'select',
							'description' => __( 'Typical size for post authors in forums, activity feeds, and similar layouts. Preset “medium”.', 'clanbite' ),
							'default'     => 'clanbite-avatar-medium',
							'options'     => $this->get_avatar_image_size_options(),
							'sanitize'    => array( $this, 'sanitize_player_avatar_size_medium' ),
						),
						'player_avatar_image_size_small'  => array(
							'label'       => __( 'Small — comments and replies', 'clanbite' ),
							'type'        => 'select',
							'description' => __( 'Compact UI such as comment threads, notifications, and nav. Preset “small”.', 'clanbite' ),
							'default'     => 'clanbite-avatar-small',
							'options'     => $this->get_avatar_image_size_options(),
							'sanitize'    => array( $this, 'sanitize_player_avatar_size_small' ),
						),
					),
				),
				'branding' => array(
					'title'  => __( 'Branding defaults', 'clanbite' ),
					'fields' => array(
						'default_avatar' => array(
							'label'       => __( 'Default player avatar image', 'clanbite' ),
							'type'        => 'image',
							'description' => __( 'Shown when a player has no custom avatar. Leave empty to use the plugin bundled image.', 'clanbite' ),
							'default'     => '',
							'fallback_url' => \clanbite_players_get_bundled_default_avatar_url(),
							'sanitize'    => 'esc_url_raw',
						),
						'default_cover'  => array(
							'label'       => __( 'Default player cover image', 'clanbite' ),
							'type'        => 'image',
							'description' => __( 'Shown when a player has no custom cover. Leave empty to use the plugin bundled image.', 'clanbite' ),
							'default'     => '',
							'fallback_url' => \clanbite_players_get_bundled_default_cover_url(),
							'sanitize'    => 'esc_url_raw',
						),
					),
				),
			);

		if ( $this->is_ranks_extension_available() ) {
			$sections['integrations']['fields']['rank_status_updates_enabled'] = array(
				'label'       => __( 'Player profile: rank achievement status updates', 'clanbite' ),
				'type'        => 'checkbox',
				'description' => __( 'When the Ranks extension is enabled, allow rank promotions and manual assignments to publish a social status post for the player.', 'clanbite' ),
				'default'     => true,
				'sanitize'    => 'rest_sanitize_boolean',
			);
		}

		return apply_filters( 'clanbite_players_sections', $sections );
	}

	/**
	 * Whether the Ranks extension is currently installed in Clanbite.
	 *
	 * @return bool
	 */
	protected function is_ranks_extension_available(): bool {
		if ( ! class_exists( \Kernowdev\Clanbite\Extensions\Loader::class ) ) {
			return false;
		}

		$installed = \Kernowdev\Clanbite\Extensions\Loader::instance()->get_installed_extensions();

		return is_array( $installed ) && array_key_exists( 'cp_ranks', $installed );
	}

	public function render_page(): void {
		$this->render_settings_page( __( 'Players', 'clanbite' ) );
	}

	/**
	 * Options for avatar image size dropdowns (registered sizes + full).
	 *
	 * @return array<string, string>
	 */
	protected function get_avatar_image_size_options(): array {
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
	public function sanitize_player_avatar_size_large( $value ): string {
		return function_exists( 'clanbite_players_sanitize_image_size_setting_value' )
			? clanbite_players_sanitize_image_size_setting_value( (string) $value, 'clanbite-avatar-large' )
			: 'clanbite-avatar-large';
	}

	/**
	 * @param mixed $value Raw setting.
	 * @return string
	 */
	public function sanitize_player_avatar_size_medium( $value ): string {
		return function_exists( 'clanbite_players_sanitize_image_size_setting_value' )
			? clanbite_players_sanitize_image_size_setting_value( (string) $value, 'clanbite-avatar-medium' )
			: 'clanbite-avatar-medium';
	}

	/**
	 * @param mixed $value Raw setting.
	 * @return string
	 */
	public function sanitize_player_avatar_size_small( $value ): string {
		return function_exists( 'clanbite_players_sanitize_image_size_setting_value' )
			? clanbite_players_sanitize_image_size_setting_value( (string) $value, 'clanbite-avatar-small' )
			: 'clanbite-avatar-small';
	}

	/**
	 * Sanitize player avatar shape setting.
	 *
	 * @param mixed $value Raw setting value.
	 * @return string
	 */
	public function sanitize_player_avatar_shape( $value ): string {
		$value = sanitize_key( (string) $value );
		if ( ! in_array( $value, array( 'circle', 'square', 'hexagon' ), true ) ) {
			return 'circle';
		}
		return $value;
	}

	/**
	 * If player profiles setting is on, enable them.
	 *
	 * @return void
	 */
	public function maybe_enable_profiles(): void {
		if ( ! $this->get( 'enable_profiles' ) ) {
			return;
		}

		// Filter author links.
		add_filter(
			'author_link',
			function ( $link, $author_id, $author_nicename ) {
				return home_url( '/players/' . $author_nicename );
			},
			10,
			3
		);

		// Add rewrite rules.
		add_action(
			'init',
			function () {
				// Author pagination.
				add_rewrite_rule(
					'^players/([^/]+)/page/([0-9]+)/?$',
					'index.php?author_name=$matches[1]&paged=$matches[2]',
					'top'
				);

				// Author first page.
				add_rewrite_rule(
					'^players/([^/]+)/?$',
					'index.php?author_name=$matches[1]',
					'top'
				);

				// All players listing.
				add_rewrite_rule(
					'^players/?$',
					'index.php?post_type=player_list',
					// dummy query var we'll handle.
						'top'
				);
			}
		);

		// Handle /players to show all users.
		add_action(
			'pre_get_posts',
			function ( $query ) {
				if ( ! is_admin() && $query->is_main_query()
					&& get_query_var( 'post_type' ) === 'player_list'
				) {
					// Modify main query to return all users
					$query->set( 'author', '' ); // all authors
					$query->set(
						'post_type',
						'post'
					); // or a custom post type if you want
					$query->set( 'orderby', 'display_name' );
					$query->set( 'order', 'ASC' );
					$query->set( 'posts_per_page', 20 ); // pagination
				}
			}
		);

		// Register custom query var for /players.
		add_filter(
			'query_vars',
			function ( $vars ) {
				$vars[] = 'post_type'; // required for dummy var

				return $vars;
			}
		);
	}
}
