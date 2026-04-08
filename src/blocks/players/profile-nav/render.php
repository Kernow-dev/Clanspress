<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Server-side render for the Player Profile Navigation block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$player_id = function_exists( 'clanspress_player_profile_context_user_id' ) ? clanspress_player_profile_context_user_id() : 0;
if ( $player_id < 1 ) {
	return;
}

$user = get_userdata( $player_id );
if ( ! ( $user instanceof WP_User ) ) {
	return;
}

$current_slug = function_exists( 'clanspress_player_profile_route_current_slug' ) ? clanspress_player_profile_route_current_slug() : '';

$subpages = function_exists( 'clanspress_get_player_subpages' ) ? clanspress_get_player_subpages() : array();
$base_url = trailingslashit( home_url( '/players/' . $user->user_nicename ) );

/**
 * Filter the label for the player profile home link.
 *
 * @param string $label   Default label.
 * @param int    $user_id Player user ID.
 */
$home_label = (string) apply_filters( 'clanspress_player_profile_home_label', __( 'Home', 'clanspress' ), $player_id );

/**
 * Filter: `clanspress_player_profile_settings_url` — URL for the Settings item (player account settings).
 *
 * @param string $url     Default URL.
 * @param int    $user_id Player user ID (profile being viewed / settings owner).
 */
$settings_url = (string) apply_filters(
	'clanspress_player_profile_settings_url',
	trailingslashit( home_url( '/players/settings/' ) ),
	$player_id
);

/**
 * Filter: `clanspress_player_profile_nav_show_settings_link` — show Settings on this profile.
 *
 * Default: viewer is the profile owner (their own public profile or the player settings screen).
 *
 * @param bool $show    Whether to show the link.
 * @param int  $user_id Player user ID.
 */
$show_settings_link = (bool) apply_filters(
	'clanspress_player_profile_nav_show_settings_link',
	get_current_user_id() === $player_id && $player_id > 0,
	$player_id
);

$show_settings_link = $show_settings_link && '' !== $settings_url;

/**
 * Filter: `clanspress_player_profile_settings_nav_label` — label for the Settings item.
 *
 * @param string $label   Default label.
 * @param int    $user_id Player user ID.
 */
$settings_label = (string) apply_filters(
	'clanspress_player_profile_settings_nav_label',
	__( 'Settings', 'clanspress' ),
	$player_id
);

$settings_active = ( 'settings' === $current_slug );

$visible_subpages = function_exists( 'clanspress_profile_subpages_visible_for_nav' )
	? clanspress_profile_subpages_visible_for_nav( 'player', $player_id, $subpages )
	: array();

// Omit the nav when only the home link would appear (matches team profile nav).
if ( array() === $visible_subpages && ! $show_settings_link ) {
	return;
}

$wrapper = get_block_wrapper_attributes(
	array(
		'class'       => 'clanspress-player-profile-nav',
		'role'        => 'navigation',
		'aria-label'  => __( 'Player sections', 'clanspress' ),
	),
	$block
);
?>
<nav <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes. ?>>
	<ul class="clanspress-player-profile-nav__list">
		<li class="clanspress-player-profile-nav__item<?php echo empty( $current_slug ) ? ' is-active' : ''; ?>">
			<a
				class="clanspress-player-profile-nav__link"
				href="<?php echo esc_url( $base_url ); ?>"
				<?php echo empty( $current_slug ) ? ' aria-current="page"' : ''; ?>
			>
				<?php echo esc_html( $home_label ); ?>
			</a>
		</li>
		<?php foreach ( $visible_subpages as $slug => $config ) :
			$label = $config['label'] ?? ucfirst( $slug );

			$is_active = ( $slug === $current_slug );
			$url       = trailingslashit( $base_url . $slug );
			?>
			<li class="clanspress-player-profile-nav__item<?php echo $is_active ? ' is-active' : ''; ?>">
				<a
					class="clanspress-player-profile-nav__link"
					href="<?php echo esc_url( $url ); ?>"
					<?php echo $is_active ? ' aria-current="page"' : ''; ?>
				>
					<?php echo esc_html( $label ); ?>
				</a>
			</li>
		<?php endforeach; ?>
		<?php if ( $show_settings_link ) : ?>
			<li class="clanspress-player-profile-nav__item clanspress-player-profile-nav__item--settings<?php echo $settings_active ? ' is-active' : ''; ?>">
				<a
					class="clanspress-player-profile-nav__link"
					href="<?php echo esc_url( $settings_url ); ?>"
					<?php echo $settings_active ? ' aria-current="page"' : ''; ?>
				>
					<?php echo esc_html( $settings_label ); ?>
				</a>
			</li>
		<?php endif; ?>
	</ul>
</nav>
