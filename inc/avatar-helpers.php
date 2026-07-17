<?php
/**
 * Centralized avatar rendering helpers.
 *
 * All avatar markup should go through these functions so third-party developers
 * can customize avatar rendering in one place with consistent filters.
 *
 * ## Context Support
 *
 * All filters and actions support context-specific targeting. When you call
 * clanbite_render_avatar() with a 'context' argument (e.g., 'forum', 'social_feed'),
 * additional context-specific filters/actions are fired:
 *
 * ### Context Examples:
 * - 'profile'      - Player/team profile pages
 * - 'forum'        - Forums plugin (topics, replies)
 * - 'social_feed'  - Social Kit posts and comments
 * - 'user_nav'     - User navigation menu
 * - 'notification' - Notification bell/list
 * - 'event'        - Event cards and lists
 * - 'match'        - Match cards
 *
 * ### Available Context Filters:
 * - clanbite_avatar_html_{context}           - Modify complete HTML for specific context
 * - clanbite_avatar_classes_{context}        - Modify CSS classes for specific context
 * - clanbite_avatar_img_attributes_{context} - Modify <img> attributes for specific context
 *
 * ### Available Context Actions:
 * - clanbite_avatar_overlays_{context}       - Add custom overlays for specific context
 *
 * ### Example Usage:
 *
 * ```php
 * // Only customize forum avatars
 * add_filter( 'clanbite_avatar_html_forum', 'my_forum_avatars', 10, 2 );
 * function my_forum_avatars( $html, $args ) {
 *     // $args['type'] = 'player'
 *     // $args['id'] = user ID
 *     // $args['context'] = 'forum'
 *     return $html; // Return modified markup
 * }
 *
 * // Only add badges to social feed avatars
 * add_action( 'clanbite_avatar_overlays_social_feed', 'my_social_badges' );
 * function my_social_badges( $args ) {
 *     if ( $args['type'] === 'player' ) {
 *         echo '<span class="my-verified-badge">✓</span>';
 *     }
 * }
 * ```
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render a complete avatar with all wrappers, shape classes, and optional overlays.
 *
 * This is the main function that should be used for ALL avatar rendering across
 * the plugin and extensions. It provides a consistent structure and filter points.
 *
 * @param array $args {
 *     Avatar rendering arguments.
 *
 *     @type string $type           Avatar type: 'player', 'team', 'group'. Required.
 *     @type int    $id             Entity ID (user_id, team_id, or group_id). Required.
 *     @type string $size           Size preset: 'large', 'medium', 'small', or custom size slug.
 *     @type string $context        Context identifier for filtering (e.g., 'profile', 'social_feed', 'forums').
 *     @type string $shape          Override shape: 'circle', 'square', 'hexagon'. If empty, uses setting.
 *     @type bool   $link           Whether to wrap in a profile link. Default false.
 *     @type string $link_target    Link target attribute. Default empty.
 *     @type array  $extra_classes  Additional CSS classes for the avatar container.
 *     @type bool   $show_rank      Whether to show rank icon overlay. Default false.
 *     @type bool   $show_progress  Whether to show progress ring. Default false.
 * }
 * @return string HTML markup.
 */
function clanbite_render_avatar( array $args ): string {
	$defaults = array(
		'type'          => 'player',
		'id'            => 0,
		'size'          => 'medium',
		'context'       => '',
		'shape'         => '',
		'link'          => false,
		'link_target'   => '',
		'extra_classes' => array(),
		'show_rank'     => false,
		'show_progress' => false,
	);

	$args = wp_parse_args( $args, $defaults );

	// Validate type
	if ( ! in_array( $args['type'], array( 'player', 'team', 'group' ), true ) ) {
		return '';
	}

	// Validate ID
	$id = absint( $args['id'] );
	if ( $id <= 0 ) {
		return '';
	}

	// Get avatar shape (from args override, or from settings)
	$shape = $args['shape'];
	if ( empty( $shape ) ) {
		switch ( $args['type'] ) {
			case 'player':
				$shape = function_exists( 'clanbite_players_get_avatar_shape' ) ? clanbite_players_get_avatar_shape() : 'circle';
				break;
			case 'team':
				$shape = function_exists( 'clanbite_teams_get_avatar_shape' ) ? clanbite_teams_get_avatar_shape() : 'circle';
				break;
			case 'group':
				$shape = function_exists( 'clanbite_groups_get_avatar_shape' ) ? clanbite_groups_get_avatar_shape() : 'circle';
				break;
			default:
				$shape = 'circle';
		}
	}

	// Get avatar URL
	$avatar_url = clanbite_get_avatar_url( $args['type'], $id, $args['size'], $args['context'] );
	if ( empty( $avatar_url ) ) {
		$avatar_url = clanbite_get_default_avatar_url( $args['type'] );
	}

	// Build CSS classes
	$classes = array(
		'clanbite-avatar',
		'clanbite-avatar--' . $args['type'],
		'clanbite-avatar--shape-' . $shape,
		'clanbite-avatar--' . sanitize_html_class( $args['size'] ),
	);

	if ( ! empty( $args['extra_classes'] ) ) {
		$classes = array_merge( $classes, (array) $args['extra_classes'] );
	}

	/**
	 * Filter avatar container classes.
	 *
	 * @param array  $classes CSS classes.
	 * @param array  $args    Avatar arguments (includes 'context' key).
	 */
	$classes = apply_filters( 'clanbite_avatar_classes', $classes, $args );
	$classes = apply_filters( "clanbite_{$args['type']}_avatar_classes", $classes, $args );
	
	// Context-specific filter (e.g., 'clanbite_avatar_classes_forum', 'clanbite_avatar_classes_social_feed')
	if ( ! empty( $args['context'] ) ) {
		$classes = apply_filters( "clanbite_avatar_classes_{$args['context']}", $classes, $args );
	}

	// Build the avatar markup
	ob_start();
	?>
	<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $classes ) ) ); ?>">
		<?php
		/**
		 * Action: Before avatar inner content.
		 *
		 * @param array $args Avatar arguments.
		 */
		do_action( 'clanbite_before_avatar_inner', $args );
		do_action( "clanbite_before_{$args['type']}_avatar_inner", $args );
		?>
		
		<div class="clanbite-avatar__media">
			<?php if ( $args['link'] ) : ?>
				<a href="<?php echo esc_url( clanbite_get_avatar_profile_url( $args['type'], $id ) ); ?>"
				   class="clanbite-avatar__link"
				   <?php if ( ! empty( $args['link_target'] ) ) : ?>
					   target="<?php echo esc_attr( $args['link_target'] ); ?>"
				   <?php endif; ?>>
			<?php endif; ?>
			
			<div class="clanbite-avatar__clip">
				<?php
				/**
				 * Filter avatar image attributes.
				 *
				 * @param array  $img_attrs Image attributes.
				 * @param array  $args      Avatar arguments (includes 'context' key).
				 */
				$img_attrs = apply_filters( 'clanbite_avatar_img_attributes', array(
					'src'      => $avatar_url,
					'class'    => 'clanbite-avatar__img',
					'alt'      => clanbite_get_avatar_alt_text( $args['type'], $id ),
					'loading'  => 'lazy',
					'decoding' => 'async',
				), $args );
				
				// Context-specific filter (e.g., 'clanbite_avatar_img_attributes_forum')
				if ( ! empty( $args['context'] ) ) {
					$img_attrs = apply_filters( "clanbite_avatar_img_attributes_{$args['context']}", $img_attrs, $args );
				}
				?>
				<img <?php echo clanbite_build_html_attributes( $img_attrs ); ?> />
			</div>
			
			<?php if ( $args['link'] ) : ?>
				</a>
			<?php endif; ?>
			
			<?php
			// Render overlays (progress, rank, etc.)
			if ( $args['show_progress'] && function_exists( 'clanbite_render_avatar_progress_bar' ) ) {
				echo clanbite_render_avatar_progress_bar( $id, $shape, $args['size'] );
			}
			
			if ( $args['show_rank'] && function_exists( 'clanbite_render_avatar_rank_icon' ) ) {
				echo clanbite_render_avatar_rank_icon( $id, $shape );
			}
			
			/**
			 * Action: After avatar overlays.
			 *
			 * Use this to add custom overlays (badges, status indicators, etc.).
			 *
			 * @param array $args Avatar arguments (includes 'context' key).
			 */
			do_action( 'clanbite_avatar_overlays', $args );
			do_action( "clanbite_{$args['type']}_avatar_overlays", $args );
			
			// Context-specific action (e.g., 'clanbite_avatar_overlays_forum')
			if ( ! empty( $args['context'] ) ) {
				do_action( "clanbite_avatar_overlays_{$args['context']}", $args );
			}
			?>
		</div>
		
		<?php
		/**
		 * Action: After avatar inner content.
		 *
		 * @param array $args Avatar arguments.
		 */
		do_action( 'clanbite_after_avatar_inner', $args );
		do_action( "clanbite_after_{$args['type']}_avatar_inner", $args );
		?>
	</div>
	<?php
	$output = ob_get_clean();

	/**
	 * Filter the complete avatar HTML output.
	 *
	 * This is the main filter point for customizing avatar rendering.
	 * Third-party developers should use this filter to customize ALL avatars
	 * across the site in one place.
	 *
	 * @param string $output Avatar HTML markup.
	 * @param array  $args   Avatar arguments (includes 'context' key for targeting specific locations).
	 */
	$output = apply_filters( 'clanbite_avatar_html', $output, $args );
	$output = apply_filters( "clanbite_{$args['type']}_avatar_html", $output, $args );
	
	// Context-specific filter (e.g., 'clanbite_avatar_html_forum', 'clanbite_avatar_html_social_feed')
	if ( ! empty( $args['context'] ) ) {
		$output = apply_filters( "clanbite_avatar_html_{$args['context']}", $output, $args );
	}

	return $output;
}

/**
 * Get avatar URL for an entity.
 *
 * @param string $type    Entity type: 'player', 'team', 'group'.
 * @param int    $id      Entity ID.
 * @param string $size    Size preset or slug.
 * @param string $context Context identifier.
 * @return string Avatar URL or empty string.
 */
function clanbite_get_avatar_url( string $type, int $id, string $size = 'medium', string $context = '' ): string {
	$url = '';

	switch ( $type ) {
		case 'player':
			if ( function_exists( 'clanbite_players_get_display_avatar' ) ) {
				$url = clanbite_players_get_display_avatar( $id, false, $size, $context, $size );
			}
			break;
		case 'team':
			if ( function_exists( 'clanbite_teams_get_display_team_avatar' ) ) {
				$url = clanbite_teams_get_display_team_avatar( $id, false, $size, $context, $size );
			}
			break;
		case 'group':
			// Groups to be implemented by group plugin
			$url = apply_filters( 'clanbite_groups_get_display_avatar', '', $id, $size, $context );
			break;
	}

	/**
	 * Filter avatar URL.
	 *
	 * @param string $url     Avatar URL.
	 * @param string $type    Entity type.
	 * @param int    $id      Entity ID.
	 * @param string $size    Size.
	 * @param string $context Context.
	 */
	return apply_filters( 'clanbite_avatar_url', $url, $type, $id, $size, $context );
}

/**
 * Get profile URL for an entity.
 *
 * @param string $type Entity type: 'player', 'team', 'group'.
 * @param int    $id   Entity ID.
 * @return string Profile URL.
 */
function clanbite_get_avatar_profile_url( string $type, int $id ): string {
	$url = '';

	switch ( $type ) {
		case 'player':
			if ( function_exists( 'clanbite_get_player_profile_url' ) ) {
				$url = clanbite_get_player_profile_url( $id );
			}
			break;
		case 'team':
			if ( function_exists( 'clanbite_get_team_profile_url' ) ) {
				$url = clanbite_get_team_profile_url( $id );
			}
			break;
		case 'group':
			$url = apply_filters( 'clanbite_groups_get_profile_url', '', $id );
			break;
	}

	/**
	 * Filter profile URL.
	 *
	 * @param string $url  Profile URL.
	 * @param string $type Entity type.
	 * @param int    $id   Entity ID.
	 */
	return apply_filters( 'clanbite_avatar_profile_url', $url, $type, $id );
}

/**
 * Get alt text for avatar image.
 *
 * @param string $type Entity type.
 * @param int    $id   Entity ID.
 * @return string Alt text.
 */
function clanbite_get_avatar_alt_text( string $type, int $id ): string {
	$alt = '';

	switch ( $type ) {
		case 'player':
			if ( function_exists( 'clanbite_players_get_display_name' ) ) {
				$name = clanbite_players_get_display_name( $id );
				/* translators: %s: player name */
				$alt = sprintf( __( '%s avatar', 'clanbite' ), $name );
			}
			break;
		case 'team':
			if ( function_exists( 'clanbite_teams_get_team_name' ) ) {
				$name = clanbite_teams_get_team_name( $id );
				/* translators: %s: team name */
				$alt = sprintf( __( '%s avatar', 'clanbite' ), $name );
			}
			break;
		case 'group':
			$alt = apply_filters( 'clanbite_groups_get_avatar_alt', '', $id );
			break;
	}

	/**
	 * Filter avatar alt text.
	 *
	 * @param string $alt  Alt text.
	 * @param string $type Entity type.
	 * @param int    $id   Entity ID.
	 */
	return apply_filters( 'clanbite_avatar_alt_text', $alt, $type, $id );
}

/**
 * Get default avatar URL for entity type.
 *
 * @param string $type Entity type.
 * @return string Default avatar URL.
 */
function clanbite_get_default_avatar_url( string $type ): string {
	$url = '';

	// Get default from settings or use plugin defaults
	switch ( $type ) {
		case 'player':
			$url = includes_url( 'images/blank.gif' );
			break;
		case 'team':
			$url = includes_url( 'images/blank.gif' );
			break;
		case 'group':
			$url = includes_url( 'images/blank.gif' );
			break;
	}

	/**
	 * Filter default avatar URL.
	 *
	 * @param string $url  Default URL.
	 * @param string $type Entity type.
	 */
	return apply_filters( 'clanbite_default_avatar_url', $url, $type );
}

/**
 * Build HTML attributes string from array.
 *
 * @param array $attrs Attributes array.
 * @return string HTML attributes string.
 */
function clanbite_build_html_attributes( array $attrs ): string {
	$parts = array();
	foreach ( $attrs as $name => $value ) {
		$name = sanitize_key( $name );
		if ( '' === $name ) {
			continue;
		}
		if ( is_bool( $value ) ) {
			if ( $value ) {
				$parts[] = esc_attr( $name );
			}
		} else {
			$parts[] = esc_attr( $name ) . '="' . esc_attr( (string) $value ) . '"';
		}
	}
	return implode( ' ', $parts );
}

/**
 * Render avatar structure for Interactivity API contexts.
 *
 * This creates the avatar HTML structure but leaves the src attribute
 * for dynamic binding via data-wp-bind--src. Used by Forums, Social Feed, etc.
 *
 * @param array $args {
 *     Avatar rendering arguments.
 *
 *     @type string $type            Avatar type: 'player', 'team', 'group'. Required.
 *     @type string $context         Context identifier (e.g., 'forum', 'social_feed'). Required.
 *     @type string $size            Size preset or slug. Default 'medium'.
 *     @type string $shape           Override shape. If empty, uses setting.
 *     @type array  $extra_classes   Additional CSS classes for the avatar container.
 *     @type array  $img_attributes  Additional attributes for the <img> tag (e.g., data-wp-bind--src).
 *     @type string $wrapper_tag     Wrapper element tag. Default 'span'.
 *     @type array  $wrapper_attrs   Additional attributes for wrapper element.
 *     @type bool   $show_rank_icons Whether to include rank icon template. Default false.
 *     @type string $rank_icons_path data-wp-each path for rank icons.
 *     @type string $rank_icons_hide data-wp-bind--hidden for rank icons.
 * }
 * @return string HTML markup with Interactivity API directives.
 */
function clanbite_render_avatar_for_interactivity( array $args ): string {
	$defaults = array(
		'type'            => 'player',
		'context'         => '',
		'size'            => 'medium',
		'shape'           => '',
		'extra_classes'   => array(),
		'img_attributes'  => array(),
		'wrapper_tag'     => 'span',
		'wrapper_attrs'   => array(),
		'show_rank_icons' => false,
		'rank_icons_path' => '',
		'rank_icons_hide' => '',
	);

	$args = wp_parse_args( $args, $defaults );

	// Get avatar shape
	$shape = $args['shape'];
	if ( empty( $shape ) ) {
		switch ( $args['type'] ) {
			case 'player':
				$shape = function_exists( 'clanbite_players_get_avatar_shape' ) ? clanbite_players_get_avatar_shape() : 'circle';
				break;
			case 'team':
				$shape = function_exists( 'clanbite_teams_get_avatar_shape' ) ? clanbite_teams_get_avatar_shape() : 'circle';
				break;
			case 'group':
				$shape = function_exists( 'clanbite_groups_get_avatar_shape' ) ? clanbite_groups_get_avatar_shape() : 'circle';
				break;
			default:
				$shape = 'circle';
		}
	}

	// Build CSS classes
	$classes = array(
		'clanbite-avatar',
		'clanbite-avatar--' . $args['type'],
		'clanbite-avatar--shape-' . $shape,
	);

	if ( ! empty( $args['extra_classes'] ) ) {
		$classes = array_merge( $classes, (array) $args['extra_classes'] );
	}

	/**
	 * Filter avatar classes for Interactivity API contexts.
	 *
	 * @param array  $classes CSS classes.
	 * @param array  $args    Avatar arguments.
	 */
	$classes = apply_filters( 'clanbite_avatar_interactivity_classes', $classes, $args );
	if ( ! empty( $args['context'] ) ) {
		$classes = apply_filters( "clanbite_avatar_interactivity_classes_{$args['context']}", $classes, $args );
	}

	// Build image attributes
	$img_attrs = array_merge(
		array(
			'class'    => 'clanbite-avatar__img',
			'alt'      => '',
			'loading'  => 'lazy',
			'decoding' => 'async',
		),
		$args['img_attributes']
	);

	/**
	 * Filter image attributes for Interactivity API contexts.
	 *
	 * @param array  $img_attrs Image attributes.
	 * @param array  $args      Avatar arguments.
	 */
	$img_attrs = apply_filters( 'clanbite_avatar_interactivity_img_attributes', $img_attrs, $args );
	if ( ! empty( $args['context'] ) ) {
		$img_attrs = apply_filters( "clanbite_avatar_interactivity_img_attributes_{$args['context']}", $img_attrs, $args );
	}

	ob_start();
	?>
	<<?php echo esc_attr( $args['wrapper_tag'] ); ?> <?php echo clanbite_build_html_attributes( $args['wrapper_attrs'] ); ?>>
		<span class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $classes ) ) ); ?>">
			<span class="clanbite-avatar__clip">
				<img <?php echo clanbite_build_html_attributes( $img_attrs ); ?> />
			</span>
			<?php if ( $args['show_rank_icons'] && ! empty( $args['rank_icons_path'] ) ) : ?>
				<span class="clanbite-avatar__rank-icons" aria-hidden="true" data-wp-bind--hidden="<?php echo esc_attr( $args['rank_icons_hide'] ); ?>">
					<template data-wp-each--icon="<?php echo esc_attr( $args['rank_icons_path'] ); ?>">
						<img class="clanbite-avatar__rank-icon" data-wp-bind--src="context.icon.url" alt="" decoding="async" data-wp-bind--data-rank-type-key="context.icon.key" loading="lazy" />
					</template>
				</span>
			<?php endif; ?>
			
			<?php
			/**
			 * Action: Add custom content to Interactivity API avatars.
			 *
			 * @param array $args Avatar arguments.
			 */
			do_action( 'clanbite_avatar_interactivity_content', $args );
			if ( ! empty( $args['context'] ) ) {
				do_action( "clanbite_avatar_interactivity_content_{$args['context']}", $args );
			}
			?>
		</span>
	</<?php echo esc_attr( $args['wrapper_tag'] ); ?>>
	<?php
	$output = ob_get_clean();

	/**
	 * Filter the complete Interactivity API avatar HTML.
	 *
	 * @param string $output Avatar HTML markup.
	 * @param array  $args   Avatar arguments.
	 */
	$output = apply_filters( 'clanbite_avatar_interactivity_html', $output, $args );
	if ( ! empty( $args['context'] ) ) {
		$output = apply_filters( "clanbite_avatar_interactivity_html_{$args['context']}", $output, $args );
	}

	return $output;
}
