<?php

namespace Kernowdev\Clanspress\Extensions\Players;
defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanspress\Extensions\Abstract_Settings;

/**
 * Players admin settings and functionality.
 */
class Admin extends Abstract_Settings {
	protected string $option_key     = 'clanspress_players_settings';
	protected string $settings_group = 'clanspress_players';
	protected string $page_slug      = 'clanspress-players';

	protected function get_page_title(): string {
		return __( 'Players', 'clanspress' );
	}

	protected function get_menu_title(): string {
		return __( 'Players', 'clanspress' );
	}

	protected function get_defaults(): array {
		return apply_filters(
			'clanspress_players_defaults',
			array(
				'enable_profiles'                 => true,
				'enable_avatars'                  => true,
				'enable_covers'                   => true,
				'default_avatar'                  => '',
				'default_cover'                   => '',
				'events_profile_subpage'          => true,
				'rank_status_updates_enabled'     => true,
				'player_avatar_image_size_large'  => 'clanspress-avatar-large',
				'player_avatar_image_size_medium' => 'clanspress-avatar-medium',
				'player_avatar_image_size_small'  => 'clanspress-avatar-small',
			)
		);
	}

	protected function get_sections(): array {
		$sections = array(
				'general'  => array(
					'title'  => __( 'General', 'clanspress' ),
					'fields' => array(
						'enable_profiles' => array(
							'label'       => __( 'Enable profiles', 'clanspress' ),
							'type'        => 'checkbox',
							'description' => __( 'Enable public player pages.', 'clanspress' ),
							'default'     => true,
							'sanitize'    => 'rest_sanitize_boolean',
						),
						'enable_avatars'  => array(
							'label'       => __( 'Enable avatars', 'clanspress' ),
							'type'        => 'checkbox',
							'description' => __( 'Enable player custom avatars.', 'clanspress' ),
							'default'     => true,
							'sanitize'    => 'rest_sanitize_boolean',
						),
						'enable_covers'   => array(
							'label'       => __( 'Enable cover images', 'clanspress' ),
							'type'        => 'checkbox',
							'description' => __( 'Enable player custom cover images.', 'clanspress' ),
							'default'     => true,
							'sanitize'    => 'rest_sanitize_boolean',
						),
					),
				),
				'integrations' => array(
					'title'  => __( 'Extension integrations', 'clanspress' ),
					'fields' => array(
						'events_profile_subpage' => array(
							'label'       => __( 'Player profile: Events tab', 'clanspress' ),
							'type'        => 'checkbox',
							'description' => __( 'When the Events extension is enabled, show the Events tab and /players/{user}/events/. When off, that URL redirects to the profile root.', 'clanspress' ),
							'default'     => true,
							'sanitize'    => 'rest_sanitize_boolean',
						),
					),
				),
				'avatar_sizes' => array(
					'title'  => __( 'Player avatar image sizes', 'clanspress' ),
					'fields' => array(
						'player_avatar_image_size_large'  => array(
							'label'       => __( 'Large — player profiles', 'clanspress' ),
							'type'        => 'select',
							'description' => __( 'Used for profile pages, the player avatar block, and other prominent displays. Code may request this with preset “large”.', 'clanspress' ),
							'default'     => 'clanspress-avatar-large',
							'options'     => $this->get_avatar_image_size_options(),
							'sanitize'    => array( $this, 'sanitize_player_avatar_size_large' ),
						),
						'player_avatar_image_size_medium' => array(
							'label'       => __( 'Medium — forums and social-style feeds', 'clanspress' ),
							'type'        => 'select',
							'description' => __( 'Typical size for post authors in forums, activity feeds, and similar layouts. Preset “medium”.', 'clanspress' ),
							'default'     => 'clanspress-avatar-medium',
							'options'     => $this->get_avatar_image_size_options(),
							'sanitize'    => array( $this, 'sanitize_player_avatar_size_medium' ),
						),
						'player_avatar_image_size_small'  => array(
							'label'       => __( 'Small — comments and replies', 'clanspress' ),
							'type'        => 'select',
							'description' => __( 'Compact UI such as comment threads, notifications, and nav. Preset “small”.', 'clanspress' ),
							'default'     => 'clanspress-avatar-small',
							'options'     => $this->get_avatar_image_size_options(),
							'sanitize'    => array( $this, 'sanitize_player_avatar_size_small' ),
						),
					),
				),
				'branding' => array(
					'title'  => __( 'Branding defaults', 'clanspress' ),
					'fields' => array(
						'default_avatar' => array(
							'label'       => __( 'Default player avatar image', 'clanspress' ),
							'type'        => 'image',
							'description' => __( 'Shown when a player has no custom avatar. Leave empty to use the plugin bundled image.', 'clanspress' ),
							'default'     => '',
							'fallback_url' => \clanspress_players_get_bundled_default_avatar_url(),
							'sanitize'    => 'esc_url_raw',
						),
						'default_cover'  => array(
							'label'       => __( 'Default player cover image', 'clanspress' ),
							'type'        => 'image',
							'description' => __( 'Shown when a player has no custom cover. Leave empty to use the plugin bundled image.', 'clanspress' ),
							'default'     => '',
							'fallback_url' => \clanspress_players_get_bundled_default_cover_url(),
							'sanitize'    => 'esc_url_raw',
						),
					),
				),
			);

		if ( $this->is_ranks_extension_available() ) {
			$sections['integrations']['fields']['rank_status_updates_enabled'] = array(
				'label'       => __( 'Player profile: rank achievement status updates', 'clanspress' ),
				'type'        => 'checkbox',
				'description' => __( 'When the Ranks extension is enabled, allow rank promotions and manual assignments to publish a social status post for the player.', 'clanspress' ),
				'default'     => true,
				'sanitize'    => 'rest_sanitize_boolean',
			);
		}

		return apply_filters( 'clanspress_players_sections', $sections );
	}

	/**
	 * Whether the Ranks extension is currently installed in Clanspress.
	 *
	 * @return bool
	 */
	protected function is_ranks_extension_available(): bool {
		if ( ! class_exists( \Kernowdev\Clanspress\Extensions\Loader::class ) ) {
			return false;
		}

		$installed = \Kernowdev\Clanspress\Extensions\Loader::instance()->get_installed_extensions();

		return is_array( $installed ) && array_key_exists( 'cp_ranks', $installed );
	}

	public function render_page(): void {
		$this->render_settings_page( __( 'Players', 'clanspress' ) );
	}

	/**
	 * Options for avatar image size dropdowns (registered sizes + full).
	 *
	 * @return array<string, string>
	 */
	protected function get_avatar_image_size_options(): array {
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
	public function sanitize_player_avatar_size_large( $value ): string {
		return function_exists( 'clanspress_players_sanitize_image_size_setting_value' )
			? clanspress_players_sanitize_image_size_setting_value( (string) $value, 'clanspress-avatar-large' )
			: 'clanspress-avatar-large';
	}

	/**
	 * @param mixed $value Raw setting.
	 * @return string
	 */
	public function sanitize_player_avatar_size_medium( $value ): string {
		return function_exists( 'clanspress_players_sanitize_image_size_setting_value' )
			? clanspress_players_sanitize_image_size_setting_value( (string) $value, 'clanspress-avatar-medium' )
			: 'clanspress-avatar-medium';
	}

	/**
	 * @param mixed $value Raw setting.
	 * @return string
	 */
	public function sanitize_player_avatar_size_small( $value ): string {
		return function_exists( 'clanspress_players_sanitize_image_size_setting_value' )
			? clanspress_players_sanitize_image_size_setting_value( (string) $value, 'clanspress-avatar-small' )
			: 'clanspress-avatar-small';
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
