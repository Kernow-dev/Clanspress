<?php
/**
 * Server output: hide inner blocks when visibility rules fail (not CSS-only).
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

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-visibility-container',
	),
	$block
);

return sprintf(
	'<div %1$s>%2$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped attributes.
	$content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inner blocks HTML from core serializer.
);
