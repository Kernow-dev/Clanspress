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
	 * Extension slugs that are always enabled and cannot be removed (core dependency).
	 *
	 * @var list<string>
	 */
	private const REQUIRED_EXTENSION_SLUGS = array( 'cp_players' );

	/**
	 * Extension slugs whose code ships inside the main Clanspress plugin package.
	 *
	 * Add-ons distributed as separate plugins are not listed here.
	 *
	 * @var list<string>
	 */
	private const CORE_BUNDLED_EXTENSION_SLUGS = array(
		'cp_players',
		'cp_notifications',
		'cp_teams',
		'cp_matches',
		'cp_events',
	);

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
		new Notifications();
		new Teams();
		new Matches();
		new Events();
		$this->setup_extensions();
	}

	/**
	 * Slugs that must stay in the installed-extensions option (cannot be toggled off).
	 *
	 * @return list<string>
	 */
	public static function get_required_extension_slugs(): array {
		/**
		 * Filter extension slugs treated as required core extensions.
		 *
		 * @param list<string> $slugs Required slugs (e.g. `cp_players`).
		 */
		$slugs = (array) apply_filters( 'clanspress_required_extension_slugs', self::REQUIRED_EXTENSION_SLUGS );

		return array_values( array_unique( array_map( 'sanitize_key', $slugs ) ) );
	}

	/**
	 * Slugs for extensions bundled in the main Clanspress plugin (admin UI "Core" badge, etc.).
	 *
	 * @return list<string>
	 */
	public static function get_core_bundled_extension_slugs(): array {
		/**
		 * Filter slugs treated as shipped with the core Clanspress plugin (not separate first-party add-ons).
		 *
		 * @param list<string> $slugs Default: `cp_players`, `cp_notifications`, `cp_teams`, `cp_matches`, `cp_events`.
		 */
		$slugs = (array) apply_filters( 'clanspress_core_bundled_extension_slugs', self::CORE_BUNDLED_EXTENSION_SLUGS );

		return array_values( array_unique( array_map( 'sanitize_key', $slugs ) ) );
	}

	/**
	 * Ensure required extensions exist in stored install state (new sites, migrations).
	 *
	 * @param array<string, Skeleton> $available Registered extensions keyed by slug.
	 */
	public static function persist_missing_required_extensions( array $available ): void {
		$installed = self::read_installed_extensions_from_options();
		$changed   = false;

		foreach ( self::get_required_extension_slugs() as $slug ) {
			if ( isset( $installed[ $slug ] ) ) {
				continue;
			}

			$ext = $available[ $slug ] ?? null;
			if ( ! $ext instanceof Skeleton ) {
				continue;
			}

			$installed[ $slug ] = array(
				'version' => (string) ( $ext->version ?? '1.0.0' ),
			);
			$changed            = true;
		}

		if ( ! $changed ) {
			return;
		}

		if ( is_multisite() && is_network_admin() ) {
			update_site_option( 'clanspress_installed_extensions', $installed );
		} else {
			update_option( 'clanspress_installed_extensions', $installed );
		}
	}

	/**
	 * On plugin activation: persist required extensions using known versions (loader may not have run).
	 */
	public static function persist_required_extensions_on_activation(): void {
		$installed = self::read_installed_extensions_from_options();
		$changed   = false;
		$versions  = array(
			'cp_players' => '1.0.0',
		);

		foreach ( self::get_required_extension_slugs() as $slug ) {
			if ( isset( $installed[ $slug ] ) ) {
				continue;
			}
			$ver               = isset( $versions[ $slug ] ) ? (string) $versions[ $slug ] : '1.0.0';
			$installed[ $slug ] = array( 'version' => $ver );
			$changed           = true;
		}

		if ( ! $changed ) {
			return;
		}

		update_option( 'clanspress_installed_extensions', $installed );
	}

	/**
	 * One-time default: enable `cp_notifications` for sites that never chose an install state for it.
	 *
	 * Keeps prior “always loaded” behavior on upgrade while allowing admins to disable the extension later.
	 *
	 * @param array<string, Skeleton> $available Registered extensions keyed by slug.
	 */
	public static function maybe_install_default_notifications_extension( array $available ): void {
		if ( self::is_notifications_default_migration_done() ) {
			return;
		}

		/**
		 * Whether to auto-enable the Notifications extension the first time the loader runs on a site.
		 *
		 * @param bool $enable Default true.
		 */
		if ( ! (bool) apply_filters( 'clanspress_install_notifications_extension_by_default', true ) ) {
			self::mark_notifications_default_migration_done();
			return;
		}

		$installed = self::read_installed_extensions_from_options();

		if ( isset( $installed['cp_notifications'] ) ) {
			self::mark_notifications_default_migration_done();
			return;
		}

		$ext = $available['cp_notifications'] ?? null;
		if ( ! $ext instanceof Skeleton ) {
			self::mark_notifications_default_migration_done();
			return;
		}

		$installed['cp_notifications'] = array(
			'version' => (string) ( $ext->version ?? '1.0.0' ),
		);

		if ( is_multisite() && is_network_admin() ) {
			update_site_option( 'clanspress_installed_extensions', $installed );
		} else {
			update_option( 'clanspress_installed_extensions', $installed );
		}

		self::mark_notifications_default_migration_done();
	}

	/**
	 * Whether the one-time notifications default-install migration has finished.
	 *
	 * @return bool
	 */
	private static function is_notifications_default_migration_done(): bool {
		if ( is_multisite() && is_network_admin() ) {
			return get_site_option( 'clanspress_notifications_extension_install_default_done', '' ) === '1';
		}

		return get_option( 'clanspress_notifications_extension_install_default_done', '' ) === '1';
	}

	/**
	 * Mark the notifications default-install migration complete (per site / network admin context).
	 *
	 * @return void
	 */
	private static function mark_notifications_default_migration_done(): void {
		if ( is_multisite() && is_network_admin() ) {
			update_site_option( 'clanspress_notifications_extension_install_default_done', '1' );
			return;
		}

		update_option( 'clanspress_notifications_extension_install_default_done', '1' );
	}

	/**
	 * One-time default: enable `cp_events` for sites that never chose an install state for it.
	 *
	 * Preserves prior “always loaded” behavior on upgrade while allowing admins to disable later.
	 *
	 * @param array<string, Skeleton> $available Registered extensions keyed by slug.
	 */
	public static function maybe_install_default_events_extension( array $available ): void {
		if ( self::is_events_default_migration_done() ) {
			return;
		}

		/**
		 * Whether to auto-enable the Events extension the first time the loader runs on a site.
		 *
		 * @param bool $enable Default true.
		 */
		if ( ! (bool) apply_filters( 'clanspress_install_events_extension_by_default', true ) ) {
			self::mark_events_default_migration_done();
			return;
		}

		$installed = self::read_installed_extensions_from_options();

		if ( isset( $installed['cp_events'] ) ) {
			self::mark_events_default_migration_done();
			return;
		}

		$ext = $available['cp_events'] ?? null;
		if ( ! $ext instanceof Skeleton ) {
			self::mark_events_default_migration_done();
			return;
		}

		$installed['cp_events'] = array(
			'version' => (string) ( $ext->version ?? '1.0.0' ),
		);

		if ( is_multisite() && is_network_admin() ) {
			update_site_option( 'clanspress_installed_extensions', $installed );
		} else {
			update_option( 'clanspress_installed_extensions', $installed );
		}

		self::mark_events_default_migration_done();
	}

	/**
	 * Whether the one-time Events default-install migration has finished.
	 *
	 * @return bool
	 */
	private static function is_events_default_migration_done(): bool {
		if ( is_multisite() && is_network_admin() ) {
			return get_site_option( 'clanspress_events_extension_install_default_done', '' ) === '1';
		}

		return get_option( 'clanspress_events_extension_install_default_done', '' ) === '1';
	}

	/**
	 * Mark the Events default-install migration complete (per site / network admin context).
	 *
	 * @return void
	 */
	private static function mark_events_default_migration_done(): void {
		if ( is_multisite() && is_network_admin() ) {
			update_site_option( 'clanspress_events_extension_install_default_done', '1' );
			return;
		}

		update_option( 'clanspress_events_extension_install_default_done', '1' );
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
			'cp_players'       => 'Kernowdev\Clanspress\Extensions\Players',
			'cp_notifications' => 'Kernowdev\Clanspress\Extensions\Notifications',
			'cp_teams'         => 'Kernowdev\Clanspress\Extensions\Teams',
			'cp_matches'       => 'Kernowdev\Clanspress\Extensions\Matches',
			'cp_events'        => 'Kernowdev\Clanspress\Extensions\Events',
			// Official first-party extension shipped in the separate Clanspress Social Kit plugin (not core-bundled).
			'cp_social_kit'    => 'Kernowdev\ClanspressSocialKit\Extension\Social_Kit',
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

		self::persist_missing_required_extensions( $extensions );
		self::maybe_install_default_notifications_extension( $extensions );
		self::maybe_install_default_events_extension( $extensions );

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
		if ( in_array( $slug, self::get_required_extension_slugs(), true ) ) {
			return false;
		}

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
