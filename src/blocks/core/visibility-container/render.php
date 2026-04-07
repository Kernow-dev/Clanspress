<?php
/**
 * Server output: hide inner blocks when visibility rules fail (not CSS-only).
 *
 * WordPress loads this via `ob_start(); require; return ob_get_clean();` — output must be echoed
 * (or printed), not returned from this file; a top-level `return` value is discarded.
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanspress\Blocks\Visibility_Container;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render scope: $attributes, $content, $block.
if ( ! class_exists( Visibility_Container::class ) ) {
	return '';
}

if ( ! Visibility_Container::should_show( $attributes, $block ) ) {
	return '';
}

$inner_html = is_string( $content ) ? $content : '';
// Dynamic container: inner HTML is usually pre-rendered into $content; if empty, render inner blocks from the tree.
if ( '' === trim( $inner_html ) && $block instanceof \WP_Block && is_countable( $block->inner_blocks ) && count( $block->inner_blocks ) > 0 ) {
	$inner_html = '';
	foreach ( $block->inner_blocks as $inner_block ) {
		$inner_html .= $inner_block->render();
	}
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-visibility-container',
	),
	$block
);

printf(
	'<div %1$s>%2$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped attributes.
	$inner_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inner blocks HTML from core serializer or WP_Block::render().
);
