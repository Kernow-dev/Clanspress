<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Renders a link to player settings for the profile owner.
 *
 * @package clanbite
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

$player_id = function_exists( 'clanbite_player_blocks_resolve_subject_user_id' )
	? (int) clanbite_player_blocks_resolve_subject_user_id( $block )
	: 0;

if ( $player_id < 1 ) {
	return;
}

/**
 * Filter: `clanbite_player_profile_settings_url` — URL for the Settings item (player account settings).
 *
 * @param string $url     Default URL.
 * @param int    $user_id Player user ID (profile being viewed / settings owner).
 */
$settings_url = (string) apply_filters(
	'clanbite_player_profile_settings_url',
	trailingslashit( home_url( '/players/settings/' ) ),
	$player_id
);

/**
 * Filter: `clanbite_player_profile_nav_show_settings_link` — show Settings on this profile.
 *
 * @param bool $show    Whether to show the link.
 * @param int  $user_id Player user ID.
 */
$show = (bool) apply_filters(
	'clanbite_player_profile_nav_show_settings_link',
	get_current_user_id() === $player_id && $player_id > 0,
	$player_id
);

if ( ! $show || '' === $settings_url ) {
	return;
}

$label = isset( $attributes['label'] ) ? trim( (string) $attributes['label'] ) : '';
if ( '' === $label ) {
	$label = (string) apply_filters(
		'clanbite_player_profile_settings_nav_label',
		__( 'Settings', 'clanbite' ),
		$player_id
	);
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-player-settings-link',
	),
	$block
);

echo wp_kses( '<div ' . $wrapper_attributes . '><div class="wp-block-button clanbite-button clanbite-button--settings"><a class="wp-block-button__link wp-element-button clanbite-button__element" href="' . esc_url( $settings_url ) . '">' . esc_html( $label ) . '</a></div></div>', clanbite_block_fragment_allowed_html());
