<?php
/**
 * Shared helpers for {@see Extension_Data_Store} implementations.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Extensions;

/**
 * Abstract base: slug normalization and configurable option key.
 */
abstract class Abstract_Extension_Data_Store implements Extension_Data_Store {
	/**
	 * WordPress option (or site option) key holding all extension blobs.
	 *
	 * @var string
	 */
	protected string $option_key = 'clanspress_extension_data';

	/**
	 * Sanitize an extension slug before using it as an array key.
	 *
	 * @param string $extension_slug Raw slug from extension code.
	 * @return string Sanitized slug.
	 */
	protected function normalize_extension_slug( string $extension_slug ): string {
		return sanitize_key( $extension_slug );
	}
}
