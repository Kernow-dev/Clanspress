<?php
/**
 * Server render for the Match list block.
 *
 * @package clanspress
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content (unused for dynamic blocks).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
$extension = function_exists( 'clanspress_matches' ) ? clanspress_matches() : null;

if ( ! $extension instanceof \Kernowdev\Clanspress\Extensions\Matches ) {
	echo '';
	return;
}

$attributes = is_array( $attributes ) ? $attributes : array();
$team_qv    = (int) get_query_var( 'clanspress_matches_team_id' );
if ( 0 === (int) ( $attributes['teamId'] ?? 0 ) && $team_qv > 0 ) {
	$attributes['teamId'] = $team_qv;
}

$markup  = $extension->render_list_block_markup( $attributes );
$wrapper = get_block_wrapper_attributes( array(), $block );
echo '<div ' . $wrapper . '>' . $markup . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $wrapper from get_block_wrapper_attributes(); inner HTML escaped in Matches::render_list_block_markup().
