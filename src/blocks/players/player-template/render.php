<?php

defined( 'ABSPATH' ) || exit;


// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Server-side render: repeat inner blocks for each roster user with `clanspress/playerId` context.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Saved inner HTML (unused).
 * @var WP_Block $block      Block instance.
 *
 * @package clanspress
 */

$inherit = isset( $block->context['clanspress/inheritTeamContext'] )
	? (bool) $block->context['clanspress/inheritTeamContext']
	: true;

$attr_team_id = (int) ( $block->context['clanspress/teamId'] ?? 0 );

$exclude_banned = ! isset( $block->context['clanspress/excludeBannedMembers'] )
	|| (bool) $block->context['clanspress/excludeBannedMembers'];

$team_id = $attr_team_id;
if ( $team_id < 1 && $inherit && function_exists( 'clanspress_team_block_resolve_team_id' ) ) {
	$team_id = (int) clanspress_team_block_resolve_team_id(
		isset( $block->context ) && is_array( $block->context ) ? $block->context : array()
	);
}

if ( $team_id < 1 || ! function_exists( 'clanspress_player_query_resolve_member_user_ids' ) ) {
	return '';
}

$query_options = function_exists( 'clanspress_player_query_options_from_block_context' )
	? clanspress_player_query_options_from_block_context(
		isset( $block->context ) && is_array( $block->context ) ? $block->context : array()
	)
	: array();

$user_ids = clanspress_player_query_resolve_member_user_ids( $team_id, $exclude_banned, $query_options, $block );
if ( array() === $user_ids ) {
	return '';
}

$base_context = isset( $block->context ) && is_array( $block->context ) ? $block->context : array();

$items_html = '';
foreach ( $user_ids as $member_id ) {
	$member_id = (int) $member_id;
	if ( $member_id < 1 ) {
		continue;
	}

	$merged_context = array_merge(
		$base_context,
		array( 'clanspress/playerId' => $member_id )
	);

	$row_html = '';
	foreach ( $block->inner_blocks as $inner_block ) {
		$inner = new WP_Block( $inner_block->parsed_block, $merged_context );
		$row_html .= $inner->render();
	}

	$items_html .= '<li class="clanspress-player-template__item">' . $row_html . '</li>';
}

if ( '' === $items_html ) {
	return '';
}

$wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-player-template__entries',
	),
	$block
);

echo '<ul ' . $wrapper . '>' . $items_html . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $wrapper from get_block_wrapper_attributes(); $items_html built from block render output.
