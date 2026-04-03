<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Renders a link to player settings for the profile owner.
 *
 * @package clanspress
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

$player_id = function_exists( 'clanspress_player_blocks_resolve_subject_user_id' )
	? (int) clanspress_player_blocks_resolve_subject_user_id( $block )
	: 0;

if ( $player_id < 1 ) {
	return;
}

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
 * @param bool $show    Whether to show the link.
 * @param int  $user_id Player user ID.
 */
$show = (bool) apply_filters(
	'clanspress_player_profile_nav_show_settings_link',
	get_current_user_id() === $player_id && $player_id > 0,
	$player_id
);

if ( ! $show || '' === $settings_url ) {
	return;
}

$label = isset( $attributes['label'] ) ? trim( (string) $attributes['label'] ) : '';
if ( '' === $label ) {
	$label = (string) apply_filters(
		'clanspress_player_profile_settings_nav_label',
		__( 'Settings', 'clanspress' ),
		$player_id
	);
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-player-settings-link',
	),
	$block
);

echo '<div ' . $wrapper_attributes . '><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $settings_url ) . '">' . esc_html( $label ) . '</a></div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
