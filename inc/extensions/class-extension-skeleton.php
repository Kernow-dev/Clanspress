<?php

namespace Kernowdev\Clanspress\Extensions;

/**
 * Extension skeleton.
 *
 * This skeleton outlines the requirements for an extension for the Clanspress plugin.
 * All extensions will need to extend this class.
 *
 * When validation fails, this class throws \InvalidArgumentException with translatable strings
 * (text domain `clanspress`) so messages can be localized if misconfiguration surfaces in admin or
 * other user-visible error UIs; the primary audience remains extension authors constructing subclasses in PHP.
 */
class Skeleton {
	/**
	 * Extension name.
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * Extension description.
	 *
	 * @var string
	 */
	public string $description;

	/**
	 * Extension slug.
	 *
	 * @var string
	 */
	public string $slug;

	/**
	 * Extension type.
	 *
	 * @var string
	 */
	public string $type;

	/**
	 * Extension parent slug.
	 *
	 * @var string
	 */
	public string $parent_slug;

	/**
	 * Extension version.
	 *
	 * @var string
	 */
	public string $version;

	/**
	 * Array of extension requirements.
	 *
	 * @var array
	 */
	public array $requires;

	/**
	 * Data store implementation for extension-specific records.
	 *
	 * @var Extension_Data_Store
	 */
	protected Extension_Data_Store $data_store;

	/**
	 * Sets up our extension loader.
	 *
	 * @param string $name Extension name.
	 * @param string $slug Extension slug.
	 * @param string $description Extension description.
	 * @param string $parent_slug Extensions parent slug.
	 * @param string $version Extension version.
	 * @param array  $requires An array of required extensions.
	 */
	protected function __construct(
		string $name,
		string $slug,
		string $description,
		string $parent_slug,
		string $version = '1.0.0',
		array $requires = array()
	) {
		$this->setup_extension(
			$name,
			$slug,
			$description,
			$parent_slug,
			$version,
			$requires
		);
	}

	/**
	 * Setup and validate extension values.
	 *
	 * @param string $name        The human-readable name of the extension.
	 * @param string $slug        The extensions slug.
	 * @param string $description The extensions description.
	 * @param string $parent_slug The slug of the parent extension.
	 * @param string $version     The extension version.
	 * @param array  $requires    An array of required extensions.
	 *
	 * @return void
	 */
	public function setup_extension(
		string $name,
		string $slug,
		string $description,
		string $parent_slug,
		string $version,
		array $requires
	): void {
		// Setup basic extension data.
		$this->name        = $this->validate_name( $name );
		$this->slug        = $this->validate_slug( $slug );
		$this->description = $description;
		$this->parent_slug = $this->validate_parent_slug( $parent_slug );
		$this->version     = $this->validate_version( $version );
		$this->requires    = $this->validate_requires( $requires );
		/**
		 * Filter the extension data store implementation.
		 *
		 * @param Extension_Data_Store $data_store Default WordPress-backed data store instance.
		 * @param string               $slug       Extension slug.
		 * @param Skeleton             $extension  Extension object.
		 */
		$this->data_store = apply_filters( 'clanspress_extension_data_store', new Data_Store_WP(), $this->slug, $this );

		// Register extension.
		add_filter( 'clanspress_registered_extensions', array( $this, 'register_extension' ) );
	}

	/**
	 * Validate extension name.
	 *
	 * @param string $name The extension name.
	 *
	 * @return string The extension name.
	 */
	protected function validate_name( string $name ): string {
		$name = trim( $name );
		if ( empty( $name ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message; translated for localized error UI.
			throw new \InvalidArgumentException( __( 'Extension name cannot be empty.', 'clanspress' ) );
		}

		return $name;
	}

	/**
	 * Validate slug.
	 *
	 * @param string $slug The extension slug.
	 *
	 * @return string Returns the validated extension slug.
	 */
	protected function validate_slug( string $slug ): string {
		$slug = strtolower( trim( $slug ) );
		$slug = preg_replace( '/[^a-z0-9-_]/', '', $slug );
		if ( empty( $slug ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message; translated for localized error UI.
			throw new \InvalidArgumentException( __( 'Extension slug must contain only lowercase letters, numbers, dashes, or underscores.', 'clanspress' ) );
		}

		return $slug;
	}

	/**
	 * Validate parent slug.
	 *
	 * @param string $parent_slug The extension parent slug.
	 *
	 * @return string The validated extension parent slug.
	 */
	protected function validate_parent_slug( string $parent_slug ): string {
		if ( empty( $parent_slug ) ) {
			$this->type = 'parent';

			return '';
		}

		$parent_slug = strtolower( trim( $parent_slug ) );
		$parent_slug = preg_replace( '/[^a-z0-9-_]/', '', $parent_slug );

		if ( empty( $parent_slug ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message; translated for localized error UI.
			throw new \InvalidArgumentException( __( 'Extension parent slug must contain only lowercase letters, numbers, dashes, or underscores.', 'clanspress' ) );
		}

		$this->type = 'child';

		return $parent_slug;
	}

	/**
	 * Validate version string.
	 *
	 * @param string $version The extension version string.
	 *
	 * @return string The validated version string.
	 */
	protected function validate_version( string $version ): string {
		if ( ! preg_match( '/^\d+\.\d+\.\d+$/', $version ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message; translated for localized error UI.
			throw new \InvalidArgumentException( __( 'Version must be in format x.y.z', 'clanspress' ) );
		}

		return $version;
	}

	/**
	 * Validate requirements array.
	 *
	 * @param array $requires An array of required extension slugs.
	 *
	 * @return array
	 */
	protected function validate_requires( array $requires ): array {
		foreach ( $requires as $require ) {
			if ( ! is_string( $require ) || empty( trim( $require ) ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message; translated for localized error UI.
				throw new \InvalidArgumentException( __( 'All extension requirements must be non-empty strings.', 'clanspress' ) );
			}
		}

		return $requires;
	}

	/**
	 * Register extension.
	 *
	 * @param array $extensions Array of registered extensions.
	 *
	 * @return array
	 */
	public function register_extension( array $extensions ): array {
		if ( ! isset( $extensions[ $this->slug ] ) ) {
			$extensions[ $this->slug ] = $this;
		}

		return $extensions;
	}

	/**
	 * Runs first-party or custom install logic for this extension.
	 *
	 * Override in a subclass when the extension needs schema, roles, or options on install.
	 * Fires `clanspress_extension_installer_{slug}`.
	 *
	 * @return void
	 */
	public function run_installer(): void {
		/**
		 * Fires when an extension installer runs.
		 *
		 * @param Skeleton $extension Extension object.
		 */
		do_action( "clanspress_extension_installer_{$this->slug}", $this );
	}

	/**
	 * Whether this extension may be enabled when a given set of slugs is treated as installed.
	 *
	 * Used by the REST extensions save to validate a batch before persisting. Slugs may come only
	 * from the pending request (not yet stored in options).
	 *
	 * @param array<int, string> $installed_slugs Extension slugs treated as present (e.g. proposed save set).
	 * @return bool
	 */
	public function can_install_with_slugs( array $installed_slugs ): bool {
		$present     = array_fill_keys( $installed_slugs, true );
		$can_install = true;

		if ( ! empty( $this->requires ) ) {
			foreach ( $this->requires as $require ) {
				if ( ! isset( $present[ $require ] ) ) {
					$can_install = false;
					break;
				}
			}
		}

		/**
		 * Allows more specific checks to run to determine if an extension can be installed or ran.
		 *
		 * @param bool     $can_install Current install status (requires satisfied against `$installed_slugs`).
		 * @param Skeleton $extension   Extension object.
		 */
		return (bool) apply_filters( "clanspress_can_install_{$this->slug}_extension", $can_install, $this );
	}

	/**
	 * Whether this extension may be enabled against currently stored install options.
	 *
	 * @return bool
	 */
	public function can_install(): bool {
		$slugs = array_keys( Loader::read_installed_extensions_from_options() );

		return $this->can_install_with_slugs( $slugs );
	}

	/**
	 * Removes extension-scoped data from the extension data store and fires uninstall hooks.
	 *
	 * Fires `clanspress_extension_uninstaller_{slug}` after `delete_data()`.
	 *
	 * @return void
	 */
	public function run_uninstaller(): void {
		$this->delete_data();

		/**
		 * Fires when an extension uninstaller runs.
		 *
		 * @param Skeleton $extension Extension object.
		 */
		do_action( "clanspress_extension_uninstaller_{$this->slug}", $this );
	}

	/**
	 * Runs when the stored extension version is lower than the class version.
	 *
	 * Fires `clanspress_extension_updater_{slug}`. Perform migrations here.
	 *
	 * @return void
	 */
	public function run_updater(): void {
		/**
		 * Fires when an extension updater runs.
		 *
		 * @param Skeleton $extension Extension object.
		 */
		do_action( "clanspress_extension_updater_{$this->slug}", $this );
	}

	/**
	 * Main entry point after the extension is installed and requirements pass.
	 *
	 * Register post types, routes, blocks, and WordPress hooks here.
	 * The default implementation only fires `clanspress_extension_run_{slug}`; subclasses normally override this method entirely.
	 *
	 * @return void
	 */
	public function run(): void {
		/**
		 * Fires when an extension runtime boot method executes.
		 *
		 * @param Skeleton $extension Extension object.
		 */
		do_action( "clanspress_extension_run_{$this->slug}", $this );
	}

	/**
	 * Register all block types under a compiled subdirectory using WordPress metadata collection (6.8+).
	 *
	 * Expects `blocks-manifest.php` beside each block folder (from `wp-scripts build-blocks-manifest`).
	 * The path must be the **parent directory** of each block folder (manifest keys = subfolder names), e.g.
	 * `build/matches` for `match-list/` and `match-card/`, not `build/matches/match-list`.
	 * Falls back to per-folder `register_block_type_from_metadata` on older WordPress versions.
	 * If `blocks-manifest.php` is missing (e.g. webpack was run without `build-blocks-manifest`),
	 * registers each first-level subdirectory that contains `block.json` so blocks still load.
	 *
	 * @param string $relative_path Path relative to the plugin root, e.g. `build/matches`.
	 * @return void
	 */
	protected function register_extension_block_types_from_metadata_collection( string $relative_path ): void {
		$relative_path = trim( str_replace( '\\', '/', $relative_path ), '/' );
		if ( '' === $relative_path ) {
			return;
		}

		$base     = clanspress()->path . $relative_path;
		$manifest = $base . '/blocks-manifest.php';

		if ( ! is_dir( $base ) ) {
			return;
		}

		if ( ! is_readable( $manifest ) ) {
			$paths = glob( $base . '/*/block.json' );
			if ( ! is_array( $paths ) ) {
				return;
			}
			foreach ( $paths as $block_json_path ) {
				$block_dir = dirname( (string) $block_json_path );
				if ( is_dir( $block_dir ) ) {
					register_block_type_from_metadata( $block_dir );
				}
			}
			return;
		}

		if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) ) {
			wp_register_block_types_from_metadata_collection( $base, $manifest );
			return;
		}

		if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
			wp_register_block_metadata_collection( $base, $manifest );
		}

		$data = include $manifest;
		if ( ! is_array( $data ) ) {
			return;
		}

		foreach ( array_keys( $data ) as $folder ) {
			$folder = (string) $folder;
			if ( '' === $folder ) {
				continue;
			}
			$block_dir = $base . '/' . $folder;
			if ( is_dir( $block_dir ) ) {
				register_block_type_from_metadata( $block_dir );
			}
		}
	}

	/**
	 * Register one or more block directories for this extension.
	 *
	 * Extensions should call this from their own runtime hooks to keep block
	 * ownership local to the extension. This ensures block availability follows
	 * extension install/enable state.
	 *
	 * @param array<int, string> $block_directories Absolute paths to block build directories.
	 * @return void
	 */
	protected function register_extension_blocks( array $block_directories ): void {
		/**
		 * Filter block directories for a specific extension.
		 *
		 * @param array<int, string> $block_directories Block directories.
		 * @param Skeleton           $extension         Extension object.
		 */
		$block_directories = (array) apply_filters( "clanspress_extension_{$this->slug}_block_directories", $block_directories, $this );

		/**
		 * Filter block directories for all extensions.
		 *
		 * @param array<int, string> $block_directories Block directories.
		 * @param Skeleton           $extension         Extension object.
		 */
		$block_directories = (array) apply_filters( 'clanspress_extension_block_directories', $block_directories, $this );

		foreach ( $block_directories as $block_directory ) {
			if ( ! is_string( $block_directory ) || '' === $block_directory ) {
				continue;
			}

			if ( ! is_dir( $block_directory ) ) {
				continue;
			}

			register_block_type_from_metadata( $block_directory );
		}
	}

	/**
	 * Register one or more FSE block templates owned by this extension.
	 *
	 * @param array<string, array<string, string>> $templates Template map.
	 * @return void
	 */
	protected function register_extension_templates( array $templates ): void {
		/**
		 * Filter templates for a specific extension.
		 *
		 * Template array shape:
		 * - key: template slug (without namespace), e.g. "player-settings"
		 * - value: [
		 *     'title'       => 'Template Title',
		 *     'path'        => '/absolute/path/to/template.php',
		 *     'description' => 'Optional. Shown in the Site Editor.',
		 *     'post_types'  => array( 'post' ), // Optional. WP 6.7+ plugin templates only.
		 *   ]
		 *
		 * @param array<string, array<string, string>> $templates Template definitions.
		 * @param Skeleton                              $extension Extension object.
		 */
		$templates = (array) apply_filters( "clanspress_extension_{$this->slug}_templates", $templates, $this );

		/**
		 * Filter templates for all extensions.
		 *
		 * @param array<string, array<string, string>> $templates Template definitions.
		 * @param Skeleton                              $extension Extension object.
		 */
		$templates = (array) apply_filters( 'clanspress_extension_templates', $templates, $this );

		foreach ( $templates as $template_slug => $template ) {
			$title = isset( $template['title'] ) ? (string) $template['title'] : '';
			$path  = isset( $template['path'] ) ? (string) $template['path'] : '';

			if ( '' === $template_slug || '' === $title || '' === $path ) {
				continue;
			}

			if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
				continue;
			}

			$template_content = file_get_contents( $path );
			if ( false === $template_content ) {
				continue;
			}

			$template_args = array(
				'title'   => $title,
				'content' => $template_content,
			);

			if ( ! empty( $template['description'] ) ) {
				$template_args['description'] = (string) $template['description'];
			}

			if ( ! empty( $template['post_types'] ) && is_array( $template['post_types'] ) ) {
				$template_args['post_types'] = array_values( array_map( 'sanitize_key', $template['post_types'] ) );
			}

			if ( function_exists( 'register_block_template' ) ) {
				register_block_template(
					"clanspress//{$template_slug}",
					$template_args
				);
			}
		}
	}

	/**
	 * Read extension data from the configured data store.
	 *
	 * @return array<string, mixed>
	 */
	public function get_data(): array {
		return $this->data_store->read( $this->slug );
	}

	/**
	 * Persist extension data in the configured data store.
	 *
	 * @param array<string, mixed> $data Data payload.
	 * @return bool
	 */
	public function set_data( array $data ): bool {
		$existing = $this->data_store->read( $this->slug );

		if ( empty( $existing ) ) {
			return $this->data_store->create( $this->slug, $data );
		}

		return $this->data_store->update( $this->slug, $data );
	}

	/**
	 * Delete extension data from the configured data store.
	 *
	 * @return bool
	 */
	public function delete_data(): bool {
		return $this->data_store->delete( $this->slug );
	}

	/**
	 * Admin settings UI for this extension, if any (unified Clanspress React tabs).
	 *
	 * @return Abstract_Settings|null
	 */
	public function get_settings_admin(): ?Abstract_Settings {
		return null;
	}
}
