<?php
/**
 * Extension data store contract.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Extensions;

interface Extension_Data_Store {
	/**
	 * Read the persisted payload for one extension slug.
	 *
	 * @param string $extension_slug Extension slug (e.g. `cp_players`).
	 * @return array<string, mixed> Empty array when nothing is stored.
	 */
	public function read( string $extension_slug ): array;

	/**
	 * Create the initial record for a slug. Implementations should fail if the slug already exists.
	 *
	 * @param string               $extension_slug Extension slug.
	 * @param array<string, mixed> $data            Initial data.
	 * @return bool True on success.
	 */
	public function create( string $extension_slug, array $data ): bool;

	/**
	 * Replace or merge the stored record for a slug (implementation-defined).
	 *
	 * @param string               $extension_slug Extension slug.
	 * @param array<string, mixed> $data            Full or partial payload to persist.
	 * @return bool True on success.
	 */
	public function update( string $extension_slug, array $data ): bool;

	/**
	 * Remove stored data for a slug.
	 *
	 * @param string $extension_slug Extension slug.
	 * @return bool True if data was deleted or already absent; false on failure.
	 */
	public function delete( string $extension_slug ): bool;
}
