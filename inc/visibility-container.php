<?php
/**
 * Visibility container: backward-compatible global wrappers around {@see \Kernowdev\Clanspress\Blocks\Visibility_Container}.
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

use Kernowdev\Clanspress\Blocks\Visibility_Container as Visibility_Container_Block;

/**
 * Sanitize role slug list from block attributes.
 *
 * @param mixed $roles Raw attribute.
 * @return string[]
 */
function clanspress_visibility_container_sanitize_role_slugs( $roles ): array {
	return Visibility_Container_Block::sanitize_role_slugs( $roles );
}

/**
 * Whether the current user has at least one of the given roles.
 *
 * @param string[] $needles    Role slugs.
 * @param string[] $user_roles Roles from `WP_User`.
 * @return bool
 */
function clanspress_visibility_container_user_has_any_role( array $needles, array $user_roles ): bool {
	return Visibility_Container_Block::user_has_any_role( $needles, $user_roles );
}

/**
 * Whether inner blocks should render for the current visitor.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param \WP_Block|null       $block      Block instance (optional, for filters).
 * @return bool
 */
function clanspress_visibility_container_should_show( array $attributes, $block = null ): bool {
	return Visibility_Container_Block::should_show( $attributes, $block );
}
