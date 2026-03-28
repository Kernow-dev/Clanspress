<?php
/**
 * Loads installed Clanspress extensions and runs their lifecycle hooks.
 *
 * Third-party extensions register on `clanspress_registered_extensions`. Official extensions
 * use `clanspress_official_registered_extensions` and a whitelist in `get_official_extensions()`.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Extensions;

/**
 * Extension loader (singleton).
 */
class Loader {
	/**
	 * Active extension instances keyed by slug.
	 *
	 * @var array<string, Skeleton>
	 */
	protected array $extensions = array();

	/**
	 * Singleton instance of the extension loader.
	 *
	 * @var Loader|null
	 */
	protected static ?Loader $instance = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return Loader A single instance of this class.
	 */
	public static function instance(): Loader {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Sets up our extension loader.
	 */
	protected function __construct() {
		new Players();
		new Teams();
		new Matches();
		$this->setup_extensions();
	}

	/**
	 * Returns official plus third-party extensions (validated).
	 *
	 * @return array<string, Skeleton>
	 */
	public function get_extensions(): array {
		// Get all official extensions.
		$official_extensions = $this->get_official_extensions();

		/**
		 * Allow developers to add their own extensions via this hook.
		 *
		 * @param array<string, Skeleton> $extensions Registered extension objects keyed by slug.
		 */
		$extensions = (array) apply_filters( 'clanspress_registered_extensions', array() );

		foreach ( $extensions as $slug => $extension ) {
			// Must not have the same slug as an official extension.
			if ( isset( $official_extensions[ $slug ] ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						__(
							'Clanspress: Extension "%1$s" attempted to use a prohibited slug.',
							'clanspress'
						),
						$slug
					),
					'1.0.0'
				);

				unset( $extensions[ $slug ] );
				continue;
			}

			if ( ! is_object( $extension ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						__(
							'Clanspress: Extension "%1$s" must register an extension object, got "%2$s".',
							'clanspress'
						),
						$slug,
						gettype( $extension )
					),
					'1.0.0'
				);

				unset( $extensions[ $slug ] );
				continue;
			}

			// Must extend the skeleton extension class.
			if ( ! $extension instanceof Skeleton ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						__(
							'Clanspress: Extension "%1$s" class "%2$s" must extend %3$s.',
							'clanspress'
						),
						$slug,
						get_class( $extension ),
						Skeleton::class
					),
					'1.0.0'
				);

				unset( $extensions[ $slug ] );
				continue;
			}

			// Require a matching slug for consistency.
			if ( $extension->slug !== $slug ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						__(
							'Clanspress: Extension registry key "%1$s" does not match extension slug "%2$s".',
							'clanspress'
						),
						$slug,
						$extension->slug
					),
					'1.0.0'
				);

				unset( $extensions[ $slug ] );
			}
		}

		// Finally return an array of available, validated extensions.
		return array_merge( $official_extensions, $extensions );
	}

	/**
	 * Returns verified first-party extensions (whitelist + matching class names).
	 *
	 * Third-party code must not use this path; use `clanspress_registered_extensions` instead.
	 *
	 * @return array<string, Skeleton>
	 */
	public function get_official_extensions(): array {
		// All known official extensions.
		$whitelist_extensions = array(
			'cp_players' => 'Kernowdev\Clanspress\Extensions\Players',
			'cp_teams'   => 'Kernowdev\Clanspress\Extensions\Teams',
			'cp_matches' => 'Kernowdev\Clanspress\Extensions\Matches',
		);

		/**
		 * Allows our extensions to self register when installed.
		 *
		 * @param array<string, Skeleton> $extensions Registered first-party extensions keyed by slug.
		 */
		$registered_extensions
			= (array) apply_filters(
				'clanspress_official_registered_extensions',
				array()
			);

		// Verified official extension buffer.
		$official_extensions = array();

		foreach ( $whitelist_extensions as $slug => $expected_class ) {
			// Must be registered via the filter.
			if ( ! isset( $registered_extensions[ $slug ] ) ) {
				continue;
			}

			$registered_class = get_class( $registered_extensions[ $slug ] );

			// Class must match EXACTLY.
			if ( $registered_class !== $expected_class ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						'Clanspress: Official extension "%1$s" registered an unexpected class. Expected "%2$s", got "%3$s".',
						$slug,
						$expected_class,
						$registered_class
					),
					'1.0.0'
				);

				continue;
			}

			// Class must exist.
			if ( ! class_exists( $registered_class ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						'Clanspress: Official extension "%1$s" attempted to register class "%2$s", but the class does not exist.',
						$slug,
						$registered_class
					),
					'1.0.0'
				);

				continue;
			}

			$official_extensions[ $slug ] = $registered_extensions[ $slug ];
		}

		return $official_extensions;
	}

	/**
	 * Instantiates each installed extension, runs updates, then `run()`.
	 *
	 * @return void
	 */
	public function setup_extensions(): void {
		// Get all available extensions.
		$extensions = $this->get_extensions();

		// Get all installed extensions.
		$installed_extensions = $this->get_installed_extensions();

		// Return early if no extensions are available.
		if ( empty( $extensions ) ) {
			return;
		}

		foreach ( $extensions as $slug => $class ) {
			// Skip extensions that are not installed.
			if ( ! $this->is_extension_installed( $slug ) ) {
				continue;
			}

			$this->register( $slug, $class );

			// Sanity check, can the extension still run?
			if ( ! $this->extensions[ $slug ]->can_install() ) {
				// No, uninstall the extension.
				$this->uninstall_extension( $slug );

				continue;
			}

			// Maybe update the extension first.
			if ( isset( $installed_extensions[ $slug ]['version'] )
				&& version_compare( (string) $installed_extensions[ $slug ]['version'], (string) $this->extensions[ $slug ]->version, '<' )
			) {
				$this->extensions[ $slug ]->run_updater();
			}

			// Run extension.
			$this->extensions[ $slug ]->run();
		}
	}

	/**
	 * Check if an extension is installed.
	 *
	 * @param string $slug The extension slug to check.
	 *
	 * @return bool Returns true if extension is installed, otherwise false.
	 */
	public function is_extension_installed( string $slug ): bool {
		// Early return for empty slugs.
		if ( empty( $slug ) ) {
			return false;
		}

		// Get installed extensions.
		$installed_extensions = $this->get_installed_extensions();

		return array_key_exists( $slug, $installed_extensions );
	}

	/**
	 * Read installed extension records from options (safe before `Main::$extensions` is assigned).
	 *
	 * On multisite, uses site options when `is_network_admin()` is true.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function read_installed_extensions_from_options(): array {
		if ( is_multisite() && is_network_admin() ) {
			return (array) get_site_option( 'clanspress_installed_extensions', array() );
		}

		return (array) get_option( 'clanspress_installed_extensions', array() );
	}

	/**
	 * Installed extension metadata (e.g. version), keyed by slug.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_installed_extensions(): array {
		return self::read_installed_extensions_from_options();
	}

	/**
	 * Runs uninstaller, removes slug from installed list, persists options.
	 *
	 * @param string $slug Extension slug.
	 * @return bool True on success.
	 */
	public function uninstall_extension( string $slug ): bool {
		if ( ! $this->is_extension_installed( $slug ) ) {
			return false;
		}

		$extension = $this->get( $slug );

		if ( ! $extension instanceof Skeleton ) {
			return false;
		}

		$extension->run_uninstaller();

		$installed_extensions = $this->get_installed_extensions();

		unset( $installed_extensions[ $slug ] );

		if ( is_multisite() && is_network_admin() ) {
			update_site_option( 'clanspress_installed_extensions', $installed_extensions );
		} else {
			update_option( 'clanspress_installed_extensions', $installed_extensions );
		}

		return true;
	}

	/**
	 * Stores a resolved extension instance during bootstrap.
	 *
	 * @param string $key        Extension slug.
	 * @param object $extension  Must be a {@see Skeleton} in normal operation.
	 * @return void
	 */
	public function register( string $key, object $extension ): void {
		$this->extensions[ $key ] = $extension;
	}

	/**
	 * Returns a running extension instance, or null if not loaded.
	 *
	 * @param string $key Extension slug (e.g. `cp_teams`).
	 * @return Skeleton|null
	 */
	public function get( string $key ): ?object {
		return $this->extensions[ $key ] ?? null;
	}
}
