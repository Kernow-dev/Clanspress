<?php

namespace Kernowdev\Clanspress\Extensions\Players;

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
				'enable_profiles' => true,
				'enable_avatars'  => true,
				'enable_covers'   => true,
			)
		);
	}

	protected function get_sections(): array {
		return apply_filters(
			'clanspress_players_sections',
			array(
				'general' => array(
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
			)
		);
	}

	public function render_page(): void {
		$this->render_settings_page( __( 'Players', 'clanspress' ) );
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
