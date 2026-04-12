<?php
/**
 * Front-end SEO: document titles, robots, Open Graph, Twitter Card tags, and JSON-LD for Clanspress routes.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress;

defined( 'ABSPATH' ) || exit;

/**
 * Registers head output for player profiles, team profiles, players directory, and public match pages.
 *
 * Extensions (for example Clanspress Forums) may attach additional filters and head callbacks.
 * Use {@see 'clanspress_seo_enabled'} to disable all core output, or granular filters below.
 *
 * Filters:
 * - `clanspress_seo_enabled` (bool) — master switch.
 * - `clanspress_seo_output_open_graph` (bool) — OG / Twitter meta tags.
 * - `clanspress_seo_output_json_ld` (bool) — JSON-LD script blocks.
 * - `clanspress_seo_output_site_graph` (bool) — WebSite + Organization on the home/front page when no major SEO plugin is detected.
 * - `clanspress_seo_player_graph` (array) — merge extra `@graph` nodes for player profiles.
 * - `clanspress_seo_team_graph` (array) — merge extra `@graph` nodes for team profiles.
 * - `clanspress_seo_match_graph` (array) — merge extra `@graph` nodes for single matches.
 * - `clanspress_seo_group_graph` (array) — merge extra `@graph` nodes for Social Kit `cp_group` singular views.
 */
final class Front_Seo {

	/**
	 * JSON encode flags for inline `application/ld+json` (mitigate `</script>` breakouts).
	 */
	private const JSON_LD_FLAGS = \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( wp_installing() ) {
			return;
		}

		add_action( 'wp_head', array( self::class, 'output_head' ), 4 );
		add_filter( 'document_title_parts', array( self::class, 'filter_document_title_parts' ), 15, 1 );
		add_filter( 'wp_robots', array( self::class, 'filter_wp_robots' ), 10, 1 );
	}

	/**
	 * Whether core Clanspress SEO runs on this request.
	 *
	 * @return bool
	 */
	private static function is_request_supported(): bool {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
			return false;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}
		if ( is_feed() || is_embed() ) {
			return false;
		}

		/**
		 * Master switch for Clanspress front SEO (titles, robots, OG, JSON-LD).
		 *
		 * @param bool $enabled Default true.
		 */
		return (bool) apply_filters( 'clanspress_seo_enabled', true );
	}

	/**
	 * @return bool
	 */
	private static function output_open_graph(): bool {
		/**
		 * Whether to print Open Graph and Twitter Card meta tags.
		 *
		 * @param bool $enabled Default true.
		 */
		return (bool) apply_filters( 'clanspress_seo_output_open_graph', true );
	}

	/**
	 * @return bool
	 */
	private static function output_json_ld(): bool {
		/**
		 * Whether to print JSON-LD script tags.
		 *
		 * @param bool $enabled Default true.
		 */
		return (bool) apply_filters( 'clanspress_seo_output_json_ld', true );
	}

	/**
	 * @return bool
	 */
	private static function output_site_graph(): bool {
		if ( self::major_seo_plugin_active() ) {
			return false;
		}
		/**
		 * Whether to print WebSite + Organization JSON-LD on the front page.
		 *
		 * @param bool $enabled Default true when no major SEO plugin is active.
		 */
		return (bool) apply_filters( 'clanspress_seo_output_site_graph', true );
	}

	/**
	 * @return bool
	 */
	private static function major_seo_plugin_active(): bool {
		return defined( 'WPSEO_VERSION' )
			|| defined( 'RANK_MATH_VERSION' )
			|| defined( 'AIOSEO_VERSION' )
			|| class_exists( '\The_SEO_Framework\Load', false );
	}

	/**
	 * @param array<string, string> $robots Robots directives.
	 * @return array<string, string>
	 */
	public static function filter_wp_robots( array $robots ): array {
		if ( ! self::is_request_supported() ) {
			return $robots;
		}

		if ( (int) get_query_var( 'players_settings' ) ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
		}

		if ( function_exists( 'clanspress_team_profile_route_current_slug' ) && 'settings' === clanspress_team_profile_route_current_slug() ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
		}

		return $robots;
	}

	/**
	 * @param array<string, string> $title Document title parts.
	 * @return array<string, string>
	 */
	public static function filter_document_title_parts( array $title ): array {
		if ( ! self::is_request_supported() ) {
			return $title;
		}

		if ( (int) get_query_var( 'cp_players_directory' ) ) {
			$title['title'] = __( 'Players', 'clanspress' );
			return $title;
		}

		$player_id = function_exists( 'clanspress_player_profile_context_user_id' ) ? (int) clanspress_player_profile_context_user_id() : 0;
		if ( $player_id > 0 && ! (int) get_query_var( 'players_settings' ) && get_queried_object() instanceof \WP_User ) {
			$user  = get_queried_object();
			$label = self::player_subpage_title_fragment( $player_id );
			$base  = $user->display_name;
			if ( '' !== $label ) {
				$base = $label . ' &mdash; ' . $base;
			}
			$title['title'] = $base;
			return $title;
		}

		$team_id = function_exists( 'clanspress_team_profile_context_team_id' ) ? (int) clanspress_team_profile_context_team_id() : 0;
		if ( $team_id > 0 ) {
			$post = get_post( $team_id );
			if ( $post instanceof \WP_Post && 'cp_team' === $post->post_type ) {
				$base = get_the_title( $post );
				$sub  = function_exists( 'clanspress_team_profile_route_current_slug' ) ? clanspress_team_profile_route_current_slug() : '';
				if ( 'events' === $sub ) {
					$base = __( 'Events', 'clanspress' ) . ' &mdash; ' . $base;
				}
				$title['title'] = $base;
			}
			return $title;
		}

		if ( is_singular( 'cp_group' ) ) {
			$post = get_queried_object();
			if ( $post instanceof \WP_Post ) {
				$base = get_the_title( $post );
				$sub  = sanitize_key( (string) get_query_var( 'cp_group_subpage' ) );
				if ( '' !== $sub && function_exists( 'clanspress_social_kit_get_group_subpages' ) ) {
					$subs = clanspress_social_kit_get_group_subpages();
					if ( isset( $subs[ $sub ] ) && is_array( $subs[ $sub ] ) && isset( $subs[ $sub ]['label'] ) ) {
						$base = (string) $subs[ $sub ]['label'] . ' &mdash; ' . $base;
					}
				}
				$title['title'] = $base;
			}
			return $title;
		}

		return $title;
	}

	/**
	 * @param int $player_id Profile owner user ID.
	 * @return string Fragment or empty when on main profile.
	 */
	private static function player_subpage_title_fragment( int $player_id ): string {
		$slug = function_exists( 'clanspress_player_profile_route_current_slug' ) ? clanspress_player_profile_route_current_slug() : '';
		if ( '' === $slug ) {
			return '';
		}

		$cfg = function_exists( 'clanspress_get_profile_subpage' ) ? clanspress_get_profile_subpage( 'player', $slug ) : null;
		if ( is_array( $cfg ) && isset( $cfg['label'] ) && '' !== (string) $cfg['label'] ) {
			return (string) $cfg['label'];
		}

		return ucwords( str_replace( '-', ' ', $slug ) );
	}

	/**
	 * @return void
	 */
	public static function output_head(): void {
		if ( ! self::is_request_supported() ) {
			return;
		}

		if ( self::output_site_graph() && ( is_front_page() || is_home() ) ) {
			self::print_site_json_ld();
		}

		if ( (int) get_query_var( 'cp_players_directory' ) ) {
			self::print_players_directory_seo();
			return;
		}

		if ( (int) get_query_var( 'players_settings' ) ) {
			return;
		}

		$player_id = function_exists( 'clanspress_player_profile_context_user_id' ) ? (int) clanspress_player_profile_context_user_id() : 0;
		if ( $player_id > 0 && get_queried_object() instanceof \WP_User ) {
			self::print_player_seo( $player_id );
			return;
		}

		$team_id = function_exists( 'clanspress_team_profile_context_team_id' ) ? (int) clanspress_team_profile_context_team_id() : 0;
		if ( $team_id > 0 ) {
			$post = get_post( $team_id );
			if ( $post instanceof \WP_Post && 'cp_team' === $post->post_type ) {
				if ( 'settings' === ( function_exists( 'clanspress_team_profile_route_current_slug' ) ? clanspress_team_profile_route_current_slug() : '' ) ) {
					return;
				}
				self::print_team_seo( $post );
			}
			return;
		}

		if ( is_singular( 'cp_match' ) ) {
			self::print_match_seo();
			return;
		}

		if ( is_singular( 'cp_group' ) ) {
			$post = get_queried_object();
			if ( $post instanceof \WP_Post ) {
				self::print_group_seo( $post );
			}
		}
	}

	/**
	 * @return void
	 */
	private static function print_site_json_ld(): void {
		if ( ! self::output_json_ld() ) {
			return;
		}

		$url  = home_url( '/' );
		$name = get_bloginfo( 'name', 'display' );
		$desc = get_bloginfo( 'description', 'display' );

		$graph = array(
			array(
				'@type' => 'WebSite',
				'@id'   => trailingslashit( $url ) . '#website',
				'url'   => $url,
				'name'  => $name,
			),
			array(
				'@type'     => 'Organization',
				'@id'       => trailingslashit( $url ) . '#organization',
				'name'      => $name,
				'url'       => $url,
				'description' => '' !== $desc ? wp_strip_all_tags( $desc ) : null,
			),
		);

		$graph[0]['publisher'] = array( '@id' => trailingslashit( $url ) . '#organization' );
		if ( has_custom_logo() ) {
			$logo_id = (int) get_theme_mod( 'custom_logo' );
			if ( $logo_id > 0 ) {
				$logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
				if ( is_string( $logo_url ) && '' !== $logo_url ) {
					$graph[1]['logo'] = array(
						'@type' => 'ImageObject',
						'url'   => $logo_url,
					);
				}
			}
		}

		$graph = array_values(
			array_filter(
				$graph,
				static function ( $node ) {
					return is_array( $node );
				}
			)
		);

		self::print_json_ld_script(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => self::strip_nulls_deep( $graph ),
			)
		);
	}

	/**
	 * @return void
	 */
	private static function print_players_directory_seo(): void {
		$url = home_url( '/players/' );

		if ( self::output_open_graph() ) {
			$title = __( 'Players', 'clanspress' ) . ' &mdash; ' . get_bloginfo( 'name', 'display' );
			self::print_og_and_twitter( $title, __( 'Member directory', 'clanspress' ), $url, 'website', '' );
		}

		if ( self::output_json_ld() ) {
			$graph = array(
				array(
					'@type' => 'CollectionPage',
					'@id'   => trailingslashit( $url ) . '#webpage',
					'url'   => $url,
					'name'  => __( 'Players', 'clanspress' ),
					'description' => __( 'Community member profiles.', 'clanspress' ),
					'isPartOf'    => array( '@id' => trailingslashit( home_url( '/' ) ) . '#website' ),
				),
			);
			self::print_json_ld_script(
				array(
					'@context' => 'https://schema.org',
					'@graph'   => self::strip_nulls_deep( $graph ),
				)
			);
		}
	}

	/**
	 * @param int $user_id User ID.
	 * @return void
	 */
	private static function print_player_seo( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user instanceof \WP_User ) {
			return;
		}

		$url         = get_author_posts_url( $user_id );
		$display     = $user->display_name;
		$description = '';
		if ( function_exists( 'clanspress_players_get_display_bio' ) ) {
			$description = wp_strip_all_tags( (string) clanspress_players_get_display_bio( $user_id, true ) );
		}
		if ( '' === $description && function_exists( 'clanspress_players_get_display_tagline' ) ) {
			$description = wp_strip_all_tags( (string) clanspress_players_get_display_tagline( $user_id, true ) );
		}
		$description = self::trim_description( $description );

		$image = '';
		if ( function_exists( 'clanspress_players_get_display_cover' ) ) {
			$image = (string) clanspress_players_get_display_cover( $user_id, true );
		}
		if ( '' === $image && function_exists( 'clanspress_players_get_display_avatar' ) ) {
			$image = (string) clanspress_players_get_display_avatar( $user_id, true, '', 'clanspress_seo', 'large' );
		}

		if ( self::output_open_graph() ) {
			self::print_og_and_twitter( $display, $description, $url, 'profile', $image );
		}

		if ( ! self::output_json_ld() ) {
			return;
		}

		$same_as = self::collect_player_same_as( $user_id );

		$person = array(
			'@type'       => 'Person',
			'@id'         => trailingslashit( $url ) . '#person',
			'name'        => $display,
			'url'         => $url,
			'description' => '' !== $description ? $description : null,
			'image'       => '' !== $image ? $image : null,
			'sameAs'      => ! empty( $same_as ) ? array_values( $same_as ) : null,
		);

		$profile_page = array(
			'@type'       => 'ProfilePage',
			'@id'         => trailingslashit( $url ) . '#profile',
			'url'         => $url,
			'name'        => $display,
			'description' => '' !== $description ? $description : null,
			'isPartOf'    => array( '@id' => trailingslashit( home_url( '/' ) ) . '#website' ),
			'mainEntity'  => array( '@id' => trailingslashit( $url ) . '#person' ),
		);

		$webpage = array(
			'@type'      => 'WebPage',
			'@id'        => trailingslashit( $url ) . '#webpage',
			'url'        => $url,
			'name'       => $display,
			'isPartOf'   => array( '@id' => trailingslashit( home_url( '/' ) ) . '#website' ),
			'about'      => array( '@id' => trailingslashit( $url ) . '#person' ),
			'primaryImageOfPage' => '' !== $image ? array( '@type' => 'ImageObject', 'url' => $image ) : null,
		);

		$graph = array( $webpage, $profile_page, $person );

		/**
		 * Extra JSON-LD @graph nodes for a player profile (merged after core nodes).
		 *
		 * @param array<int, array<string, mixed>> $extra_graph Nodes.
		 * @param int                               $user_id     User ID.
		 */
		$extra = (array) apply_filters( 'clanspress_seo_player_graph', array(), $user_id );
		if ( ! empty( $extra ) ) {
			$graph = array_merge( $graph, $extra );
		}

		self::print_json_ld_script(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => self::strip_nulls_deep( $graph ),
			)
		);
	}

	/**
	 * @param int $user_id User ID.
	 * @return list<string>
	 */
	private static function collect_player_same_as( int $user_id ): array {
		$out = array();
		if ( ! function_exists( 'clanspress_players_get_social_profile_field_definitions' )
			|| ! function_exists( 'clanspress_players_get_social_profile_link_url' ) ) {
			return $out;
		}

		foreach ( array_keys( clanspress_players_get_social_profile_field_definitions() ) as $slug ) {
			$slug = sanitize_key( (string) $slug );
			if ( '' === $slug ) {
				continue;
			}
			$link = clanspress_players_get_social_profile_link_url( $slug, $user_id );
			$link = esc_url_raw( $link );
			if ( '' !== $link && str_starts_with( $link, 'http' ) ) {
				$out[] = $link;
			}
		}

		$website = '';
		if ( function_exists( 'clanspress_players_get_display_website' ) ) {
			$website = esc_url_raw( (string) clanspress_players_get_display_website( $user_id, true ) );
		}
		if ( '' !== $website && str_starts_with( $website, 'http' ) ) {
			$out[] = $website;
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * @param \WP_Post $post Team post.
	 * @return void
	 */
	private static function print_team_seo( \WP_Post $post ): void {
		$url         = get_permalink( $post );
		$title       = get_the_title( $post );
		$description = self::trim_description( wp_strip_all_tags( get_the_excerpt( $post ) ) );
		if ( '' === $description ) {
			$description = self::trim_description( wp_strip_all_tags( (string) $post->post_content ) );
		}

		$image = '';
		$cover = (int) get_post_meta( $post->ID, 'cp_team_cover_id', true );
		if ( $cover > 0 ) {
			$image = (string) wp_get_attachment_image_url( $cover, 'large' );
		}
		if ( '' === $image && has_post_thumbnail( $post ) ) {
			$image = (string) get_the_post_thumbnail_url( $post, 'large' );
		}
		if ( '' === $image ) {
			$avatar = (int) get_post_meta( $post->ID, 'cp_team_avatar_id', true );
			if ( $avatar > 0 ) {
				$image = (string) wp_get_attachment_image_url( $avatar, 'large' );
			}
		}

		if ( ! is_string( $url ) || '' === $url ) {
			return;
		}

		if ( self::output_open_graph() ) {
			self::print_og_and_twitter( $title, $description, $url, 'article', $image );
		}

		if ( ! self::output_json_ld() ) {
			return;
		}

		$team_node = array(
			'@type'       => 'SportsTeam',
			'@id'         => trailingslashit( $url ) . '#team',
			'name'        => $title,
			'url'         => $url,
			'description' => '' !== $description ? $description : null,
			'image'       => '' !== $image ? $image : null,
		);

		$webpage = array(
			'@type'      => 'WebPage',
			'@id'        => trailingslashit( $url ) . '#webpage',
			'url'        => $url,
			'name'       => $title,
			'isPartOf'   => array( '@id' => trailingslashit( home_url( '/' ) ) . '#website' ),
			'about'      => array( '@id' => trailingslashit( $url ) . '#team' ),
			'primaryImageOfPage' => '' !== $image ? array( '@type' => 'ImageObject', 'url' => $image ) : null,
		);

		$graph = array( $webpage, $team_node );

		/**
		 * Extra JSON-LD @graph nodes for a team profile.
		 *
		 * @param array<int, array<string, mixed>> $extra_graph Nodes.
		 * @param int                                 $team_id     Team post ID.
		 */
		$extra = (array) apply_filters( 'clanspress_seo_team_graph', array(), (int) $post->ID );
		if ( ! empty( $extra ) ) {
			$graph = array_merge( $graph, $extra );
		}

		self::print_json_ld_script(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => self::strip_nulls_deep( $graph ),
			)
		);
	}

	/**
	 * @param \WP_Post $post Group post (`cp_group`).
	 * @return void
	 */
	private static function print_group_seo( \WP_Post $post ): void {
		if ( 'cp_group' !== $post->post_type ) {
			return;
		}

		$url = get_permalink( $post );
		if ( ! is_string( $url ) || '' === $url ) {
			return;
		}

		$title       = get_the_title( $post );
		$description = self::trim_description( wp_strip_all_tags( get_the_excerpt( $post ) ) );
		if ( '' === $description ) {
			$description = self::trim_description( wp_strip_all_tags( (string) $post->post_content ) );
		}

		$image = '';
		if ( function_exists( 'clanspress_social_kit_get_group_cover_url' ) ) {
			$image = (string) clanspress_social_kit_get_group_cover_url( $post );
		}
		if ( '' === $image && function_exists( 'clanspress_social_kit_get_group_avatar_url' ) ) {
			$image = (string) clanspress_social_kit_get_group_avatar_url( $post );
		}
		if ( '' === $image && has_post_thumbnail( $post ) ) {
			$image = (string) get_the_post_thumbnail_url( $post, 'large' );
		}

		if ( self::output_open_graph() ) {
			self::print_og_and_twitter( $title, $description, $url, 'article', $image );
		}

		if ( ! self::output_json_ld() ) {
			return;
		}

		$org = array(
			'@type'       => 'Organization',
			'@id'         => trailingslashit( $url ) . '#group',
			'name'        => $title,
			'url'         => $url,
			'description' => '' !== $description ? $description : null,
		);
		if ( '' !== $image ) {
			$org['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $image,
			);
		}

		$webpage = array(
			'@type'              => 'WebPage',
			'@id'                => trailingslashit( $url ) . '#webpage',
			'url'                => $url,
			'name'               => $title,
			'isPartOf'           => array( '@id' => trailingslashit( home_url( '/' ) ) . '#website' ),
			'about'              => array( '@id' => trailingslashit( $url ) . '#group' ),
			'primaryImageOfPage' => '' !== $image ? array( '@type' => 'ImageObject', 'url' => $image ) : null,
		);

		$graph = array(
			self::strip_nulls_deep( $webpage ),
			self::strip_nulls_deep( $org ),
		);

		/**
		 * Extra JSON-LD @graph nodes for a Social Kit group (`cp_group`).
		 *
		 * @param array<int, array<string, mixed>> $extra_graph Nodes.
		 * @param int                               $post_id     Group post ID.
		 */
		$extra = (array) apply_filters( 'clanspress_seo_group_graph', array(), (int) $post->ID );
		if ( ! empty( $extra ) ) {
			$graph = array_merge( $graph, $extra );
		}

		self::print_json_ld_script(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => $graph,
			)
		);
	}

	private static function print_match_seo(): void {
		if ( ! is_singular( 'cp_match' ) ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$url         = get_permalink( $post );
		$title       = get_the_title( $post );
		$description = self::trim_description( wp_strip_all_tags( get_the_excerpt( $post ) ) );
		if ( '' === $description ) {
			$description = self::trim_description( wp_strip_all_tags( (string) $post->post_content ) );
		}
		$image = has_post_thumbnail( $post ) ? (string) get_the_post_thumbnail_url( $post, 'large' ) : '';

		if ( ! is_string( $url ) || '' === $url ) {
			return;
		}

		if ( self::output_open_graph() ) {
			self::print_og_and_twitter( $title, $description, $url, 'article', $image );
		}

		if ( ! self::output_json_ld() ) {
			return;
		}

		$start = (string) get_post_meta( $post->ID, 'cp_match_scheduled_at', true );
		$iso   = '';
		if ( '' !== $start ) {
			$ts = strtotime( $start . ' GMT' );
			if ( false !== $ts ) {
				$iso = gmdate( 'c', $ts );
			}
		}

		$match_status = sanitize_key( (string) get_post_meta( $post->ID, 'cp_match_status', true ) );
		$event_status = null;
		if ( 'cancelled' === $match_status ) {
			$event_status = 'https://schema.org/EventCancelled';
		} elseif ( in_array( $match_status, array( 'scheduled', 'live', 'finished' ), true ) ) {
			$event_status = 'https://schema.org/EventScheduled';
		}

		$event = array(
			'@type'         => 'SportsEvent',
			'@id'           => trailingslashit( $url ) . '#match',
			'name'          => $title,
			'url'           => $url,
			'description'   => '' !== $description ? $description : null,
			'image'         => '' !== $image ? $image : null,
			'startDate'     => '' !== $iso ? $iso : null,
			'eventStatus'   => $event_status,
			'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
		);

		$webpage = array(
			'@type'      => 'WebPage',
			'@id'        => trailingslashit( $url ) . '#webpage',
			'url'        => $url,
			'name'       => $title,
			'isPartOf'   => array( '@id' => trailingslashit( home_url( '/' ) ) . '#website' ),
			'about'      => array( '@id' => trailingslashit( $url ) . '#match' ),
			'primaryImageOfPage' => '' !== $image ? array( '@type' => 'ImageObject', 'url' => $image ) : null,
		);

		$graph = array( $webpage, $event );

		/**
		 * Extra JSON-LD @graph nodes for a match (single `cp_match`).
		 *
		 * @param array<int, array<string, mixed>> $extra_graph Nodes.
		 * @param int                               $post_id     Post ID.
		 */
		$extra = (array) apply_filters( 'clanspress_seo_match_graph', array(), (int) $post->ID );
		if ( ! empty( $extra ) ) {
			$graph = array_merge( $graph, $extra );
		}

		self::print_json_ld_script(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => self::strip_nulls_deep( $graph ),
			)
		);
	}

	/**
	 * @param string $title OG title.
	 * @param string $description Plain text description.
	 * @param string $url Canonical URL.
	 * @param string $type OG type (profile, article, website).
	 * @param string $image Image URL or empty.
	 * @return void
	 */
	private static function print_og_and_twitter( string $title, string $description, string $url, string $type, string $image ): void {
		$site_name = get_bloginfo( 'name', 'display' );

		if ( '' !== $description && ! self::major_seo_plugin_active() ) {
			echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
		}

		echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
		if ( '' !== $description ) {
			echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
		}
		echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
		echo '<meta property="og:type" content="' . esc_attr( $type ) . '" />' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '" />' . "\n";
		if ( '' !== $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
		}

		echo '<meta name="twitter:card" content="' . esc_attr( '' !== $image ? 'summary_large_image' : 'summary' ) . '" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
		if ( '' !== $description ) {
			echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '" />' . "\n";
		}
		if ( '' !== $image ) {
			echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
		}
	}

	/**
	 * @param array<string, mixed> $data Schema payload.
	 * @return void
	 */
	private static function print_json_ld_script( array $data ): void {
		$json = wp_json_encode( $data, self::JSON_LD_FLAGS );
		if ( false === $json ) {
			return;
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD from wp_json_encode() with JSON_HEX_* flags; HTML escapers would corrupt the payload.
		echo '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
	}

	/**
	 * @param string $text Plain text.
	 * @return string
	 */
	private static function trim_description( string $text ): string {
		$t = trim( preg_replace( '/\s+/', ' ', $text ) );
		if ( '' === $t ) {
			return '';
		}
		return wp_html_excerpt( $t, 300, '…' );
	}

	/**
	 * @param array<int|string, mixed> $data Data.
	 * @return array<int|string, mixed>
	 */
	private static function strip_nulls_deep( array $data ): array {
		foreach ( $data as $k => $v ) {
			if ( is_array( $v ) ) {
				$data[ $k ] = self::strip_nulls_deep( $v );
				if ( array() === $data[ $k ] ) {
					unset( $data[ $k ] );
				}
				continue;
			}
			if ( null === $v || '' === $v ) {
				unset( $data[ $k ] );
			}
		}
		return $data;
	}
}
