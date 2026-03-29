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

$extension = function_exists( 'clanspress_matches' ) ? clanspress_matches() : null;

if ( ! $extension instanceof \Kernowdev\Clanspress\Extensions\Matches ) {
	echo '';
	return;
}

echo wp_kses_post(
	$extension->render_list_block_markup( is_array( $attributes ) ? $attributes : array() )
);
