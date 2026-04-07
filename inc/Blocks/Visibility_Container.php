<?php
/**
 * Visibility container block: role / login-state rules (server-side).
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Server-side visibility evaluation for the `clanspress/visibility-container` block.
 */
final class Visibility_Container {

	/**
	 * Sanitize role slug list from block attributes.
	 *
	 * @param mixed $roles Raw attribute.
	 * @return string[]
	 */
	public static function sanitize_role_slugs( $roles ): array {
		if ( ! is_array( $roles ) ) {
			return array();
		}
		$out = array();
		foreach ( $roles as $r ) {
			$s = sanitize_key( is_string( $r ) ? $r : '' );
			if ( '' !== $s ) {
				$out[] = $s;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Whether the current user has at least one of the given roles.
	 *
	 * @param string[] $needles    Role slugs.
	 * @param string[] $user_roles Roles from `WP_User`.
	 * @return bool
	 */
	public static function user_has_any_role( array $needles, array $user_roles ): bool {
		foreach ( $needles as $n ) {
			if ( in_array( $n, $user_roles, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether inner blocks should render for the current visitor.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param \WP_Block|null       $block      Block instance (optional, for filters).
	 * @return bool
	 */
	public static function should_show( array $attributes, $block = null ): bool {
		// Block.json uses camelCase; some serializers may emit snake_case.
		$show_to = 'all';
		if ( isset( $attributes['showTo'] ) ) {
			$show_to = (string) $attributes['showTo'];
		} elseif ( isset( $attributes['show_to'] ) ) {
			$show_to = (string) $attributes['show_to'];
		}

		$hide_from = 'none';
		if ( isset( $attributes['hideFrom'] ) ) {
			$hide_from = (string) $attributes['hideFrom'];
		} elseif ( isset( $attributes['hide_from'] ) ) {
			$hide_from = (string) $attributes['hide_from'];
		}

		$show_roles_raw = null;
		if ( isset( $attributes['showToRoles'] ) ) {
			$show_roles_raw = $attributes['showToRoles'];
		} elseif ( isset( $attributes['show_to_roles'] ) ) {
			$show_roles_raw = $attributes['show_to_roles'];
		}
		$show_roles = is_array( $show_roles_raw )
			? self::sanitize_role_slugs( $show_roles_raw )
			: array();

		$hide_roles_raw = null;
		if ( isset( $attributes['hideFromRoles'] ) ) {
			$hide_roles_raw = $attributes['hideFromRoles'];
		} elseif ( isset( $attributes['hide_from_roles'] ) ) {
			$hide_roles_raw = $attributes['hide_from_roles'];
		}
		$hide_roles = is_array( $hide_roles_raw )
			? self::sanitize_role_slugs( $hide_roles_raw )
			: array();

		$logged_in  = is_user_logged_in();
		$user_roles = array();
		if ( $logged_in ) {
			$user = wp_get_current_user();
			$user_roles = is_array( $user->roles ) ? $user->roles : array();
		}

		$show_match = true;
		switch ( $show_to ) {
			case 'guests':
				$show_match = ! $logged_in;
				break;
			case 'logged_in':
				$show_match = $logged_in;
				break;
			case 'roles':
				// Empty role list would hide the block for everyone; treat as unrestricted (same as "all").
				if ( empty( $show_roles ) ) {
					$show_match = true;
				} else {
					$show_match = $logged_in && self::user_has_any_role( $show_roles, $user_roles );
				}
				break;
			case 'all':
			default:
				$show_match = true;
		}

		$hide_match = false;
		switch ( $hide_from ) {
			case 'guests':
				$hide_match = ! $logged_in;
				break;
			case 'logged_in':
				$hide_match = $logged_in;
				break;
			case 'roles':
				$hide_match = $logged_in && self::user_has_any_role( $hide_roles, $user_roles );
				if ( empty( $hide_roles ) ) {
					$hide_match = false;
				}
				break;
			case 'none':
			default:
				$hide_match = false;
		}

		$visible = $show_match && ! $hide_match;

		/**
		 * Whether to render the visibility container’s inner blocks for this request.
		 *
		 * @param bool                 $visible    Resolved visibility before this filter.
		 * @param array<string, mixed> $attributes Block attributes.
		 * @param \WP_Block|null       $block      Block instance.
		 */
		return (bool) apply_filters( 'clanspress_visibility_container_should_show', $visible, $attributes, $block );
	}
}
