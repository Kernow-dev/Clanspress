<?php
/**
 * Server render for the Match card block.
 *
 * @package clanspress
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused.
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

echo $extension->render_card_block_markup( is_array( $attributes ) ? $attributes : array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is escaped in Matches::render_card_block_markup().
