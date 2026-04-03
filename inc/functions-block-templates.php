<?php
/**
 * Helpers for Clanspress block-based front templates (FSE + classic themes).
 *
 * Block markup lives in `templates/**.html` for {@see register_block_template()}.
 * Matching `.php` files in the same folder wrap that markup with `get_header()` /
 * `get_footer()` and {@see do_blocks()} so classic themes do not print raw serialised blocks.
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load a file of block-serialised markup and echo it through {@see do_blocks()}.
 *
 * @param string $markup_file Absolute path to a readable `.html` (or other) file.
 * @return void
 */
function clanspress_render_block_markup_file( string $markup_file ): void {
	if ( ! is_readable( $markup_file ) ) {
		return;
	}

	$markup = file_get_contents( $markup_file );
	if ( false === $markup || '' === trim( $markup ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo do_blocks( $markup );
}
