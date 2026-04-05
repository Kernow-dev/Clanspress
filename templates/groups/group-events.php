<?php
/**
 * Front template: group profile events subpage (classic / hybrid themes).
 *
 * Block markup: `group-events.html` (registered for FSE as `clanspress//clanspress-group-events`).
 * On singular `cp_group`, event blocks resolve the group from {@see clanspress_group_profile_context_group_id()}.
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'clanspress_render_block_markup_file' ) ) {
	clanspress_render_block_markup_file( __DIR__ . '/group-events.html' );
}

get_footer();
