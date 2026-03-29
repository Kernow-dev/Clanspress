<?php
/**
 * Default {@see Extension_Data_Store}: one WordPress option (or site option) containing all extension records.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Extensions;

/**
 * Option-backed persistence for `Skeleton::get_data()` / `set_data()`.
 */
class Data_Store_WP extends Abstract_Extension_Data_Store {

	/**
	 * {@inheritDoc}
	 */
	public function read( string $extension_slug ): array {
		$extension_slug = $this->normalize_extension_slug( $extension_slug );
		$data           = $this->get_data_bucket();

		return isset( $data[ $extension_slug ] ) && is_array( $data[ $extension_slug ] )
			? $data[ $extension_slug ]
			: array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function create( string $extension_slug, array $data ): bool {
		$extension_slug = $this->normalize_extension_slug( $extension_slug );
		$all_data       = $this->get_data_bucket();

		if ( isset( $all_data[ $extension_slug ] ) ) {
			return false;
		}

		$all_data[ $extension_slug ] = $data;

		return $this->update_data_bucket( $all_data );
	}

	/**
	 * {@inheritDoc}
	 */
	public function update( string $extension_slug, array $data ): bool {
		$extension_slug              = $this->normalize_extension_slug( $extension_slug );
		$all_data                    = $this->get_data_bucket();
		$all_data[ $extension_slug ] = $data;

		return $this->update_data_bucket( $all_data );
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete( string $extension_slug ): bool {
		$extension_slug = $this->normalize_extension_slug( $extension_slug );
		$all_data       = $this->get_data_bucket();

		if ( ! isset( $all_data[ $extension_slug ] ) ) {
			return false;
		}

		unset( $all_data[ $extension_slug ] );

		return $this->update_data_bucket( $all_data );
	}

	/**
	 * Read the full option blob from the database.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function get_data_bucket(): array {
		if ( is_multisite() && is_network_admin() ) {
			return (array) get_site_option( $this->option_key, array() );
		}

		return (array) get_option( $this->option_key, array() );
	}

	/**
	 * Write the full option blob.
	 *
	 * @param array<string, array<string, mixed>> $data Map of extension slug => payload.
	 * @return bool True on success.
	 */
	protected function update_data_bucket( array $data ): bool {
		if ( is_multisite() && is_network_admin() ) {
			return update_site_option( $this->option_key, $data );
		}

		return update_option( $this->option_key, $data );
	}
}
