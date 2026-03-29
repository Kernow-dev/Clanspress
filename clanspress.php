<?php
/**
 * Plugin Name: Clanspress
 * Plugin URI: https://clanspress.com
 * Description: Community management system for Gamers and Sports teams
 * Version: 1.0.0
 * Requires PHP: 8.2
 * Author: kernow.dev
 * Author URI: https://kernow.dev
 * Donate link: https://kernow.dev
 * License: See license.txt
 * Text Domain: clanspress
 * Domain Path: /languages
 *
 * @link    https://clanspress.com/
 *
 * @package clanspress
 * @version 1.0.0
 */

namespace Kernowdev\Clanspress;

use AllowDynamicProperties;
use Kernowdev\Clanspress\Admin\Settings;
use Kernowdev\Clanspress\Extensions\Loader as Extension_Loader;

// Use composer autoload.
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/inc/clanspress-private-media.php';
require_once __DIR__ . '/shortcut-function.php';

/**
 * Main initiation class.
 */
#[AllowDynamicProperties]
final class Main {
	/**
	 * Current version.
	 *
	 * @var string
	 */
	public const VERSION = '1.0.0';

	/**
	 * What version of maintenance upgrades we are at.
	 *
	 * @var int
	 */
	public const MAINTENANCE_VERSION = 3;

	/**
	 * Singleton instance of plugin.
	 *
	 * @var Main|null
	 */
	protected static ?Main $instance = null;

	/**
	 * The token, used to prefix values in DB.
	 *
	 * @var   string
	 */
	protected string $_token = 'clanspress_';

	/**
	 * URL of the plugin directory.
	 *
	 * @var string
	 */
	public string $url = '';

	/**
	 * Path of the plugin directory.
	 *
	 * @var string
	 */
	public string $path = '';

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	public string $basename = '';

	/**
	 * The main plugin file.
	 *
	 * @var string
	 */
	public string $file;

	/**
	 * Detailed activation error messages.
	 *
	 * @var array
	 */
	protected array $activation_errors = array();

	/**
	 * Extension loader (assigned in {@see init()} after textdomain load).
	 *
	 * @var Extension_Loader|null Null until {@see init()} runs.
	 */
	public ?Extension_Loader $extensions = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return Main A single instance of this class.
	 */
	public static function instance(): Main {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Sets up our plugin.
	 */
	protected function __construct() {
		$this->file     = basename( __FILE__ );
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );
	}

	/**
	 * Activate the plugin.
	 */
	public function _activate(): void {
		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		// Make sure any rewrite functionality has been loaded.
		flush_rewrite_rules();
	}

	/**
	 * Check if the plugin meets requirements and
	 * disable it if they are not present.
	 *
	 * @return boolean True if requirements met, false if not.
	 */
	public function check_requirements(): bool {
		// Bail early if plugin meets requirements.
		if ( $this->meets_requirements() ) {
			return true;
		}

		// Add a dashboard notice.
		add_action(
			'all_admin_notices',
			array( $this, 'requirements_not_met_notice' )
		);

		// Deactivate our plugin.
		add_action( 'admin_init', array( $this, 'deactivate_me' ) );

		// Didn't meet the requirements.
		return false;
	}

	/**
	 * Deactivates this plugin, hook this function on admin_init.
	 */
	public function deactivate_me(): void {
		// We do a check for deactivate_plugins before calling it to protect
		// any developers from accidentally calling it too early and breaking things.
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( $this->basename );
		}
	}

	/**
	 * Check that all plugin requirements are met.
	 *
	 * @return boolean True if requirements are met.
	 */
	public function meets_requirements(): bool {
		return true;
	}

	/**
	 * Adds a notice to the dashboard if the plugin requirements are not met.
	 */
	public function requirements_not_met_notice(): void {
		// Compile default message.
		$default_message = sprintf(
			__(
				'Clanspress Plugin is missing requirements and has been <a href="%s">deactivated</a>. Please make sure all requirements are available.',
				'clanspress'
			),
			admin_url( 'plugins.php' )
		);

		// Default details to null.
		$details = null;

		// Add details if any exist.
		if ( $this->activation_errors ) {
			$details = '<small>' . implode(
				'</small><br /><small>',
				$this->activation_errors
			) . '</small>';
		}

		// Output errors.
		?>
		<div id="message" class="error">
			<p><?php echo wp_kses_post( $default_message ); ?></p>
			<?php echo wp_kses_post( $details ); ?>
		</div>
		<?php
	}

	/**
	 * Deactivate the plugin.
	 * Uninstall routines should be in uninstall.php.
	 */
	public function _deactivate(): void {
	}

	/**
	 * Hooks run at 0.
	 */
	public function early_hooks(): void {
	}

	/**
	 * Add hooks and filters.
	 * Priority needs to be
	 * < 10 for CPT_Core,
	 * < 5 for Taxonomy_Core,
	 * and 0 for Widgets because widgets_init runs at init priority 1.
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'init' ), 0 );

		add_filter( 'block_categories_all', array( $this, 'register_block_categories' ), 5, 2 );

		// Scripts and styles
		add_action(
			'admin_enqueue_scripts',
			array( $this, 'enqueue_admin_styles' )
		);
	}

	/**
	 * Register block editor categories (core Clanspress + per-extension groups).
	 *
	 * @param array<int, array<string, mixed>> $categories Existing categories.
	 * @param \WP_Block_Editor_Context|null    $context    Editor context (unused).
	 * @return array<int, array<string, mixed>>
	 */
	public function register_block_categories( array $categories, $context ): array {
		$ours = array(
			array(
				'slug'  => 'clanspress',
				'title' => __( 'Clanspress', 'clanspress' ),
			),
			array(
				'slug'  => 'clanspress-players',
				'title' => __( 'Clanspress Players', 'clanspress' ),
			),
			array(
				'slug'  => 'clanspress-teams',
				'title' => __( 'Clanspress Teams', 'clanspress' ),
			),
			array(
				'slug'  => 'clanspress-matches',
				'title' => __( 'Clanspress Matches', 'clanspress' ),
			),
		);

		return array_merge( $ours, $categories );
	}

	/**
	 * Init hooks
	 */
	public function init(): void {
		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		// Load translated strings for the plugin.
		load_plugin_textdomain(
			'clanspress',
			false,
			dirname( $this->basename ) . '/languages/'
		);

		// Perform maintenance.
		$this->maybe_run_maintenance();

		// Initialise plugin extensions before admin UI (settings tabs need extension metadata).
		$this->extensions = Extension_Loader::instance();

		// Must run on every request: REST (`/wp-json/...`) is not `is_admin()`, but the
		// React settings app calls `clanspress/v1/admin/*` from the browser.
		Settings::instance();
	}

	/**
	 * Enqueue admin CSS.
	 */
	public function enqueue_admin_styles(): void {
	}

	/**
	 * Check if any necessary maintenance tasks need to be run and execute them.
	 */
	public function maybe_run_maintenance(): void {
		$maintenance_version = (int) get_option(
			$this->_token . '_maint_version'
		);

		if ( $maintenance_version < self::MAINTENANCE_VERSION ) {
			for (
					$version = $maintenance_version + 1;
					$version <= self::MAINTENANCE_VERSION; $version++
			) {
				Maintenance::run( $version );
			}
		}

		update_option(
			$this->_token . '_maint_version',
			self::MAINTENANCE_VERSION
		);
	}

	/**
	 * Returns the current version.
	 *
	 * @return string
	 */
	public function get_version(): string {
		return self::VERSION;
	}
}

// Kick it off.
add_action( 'plugins_loaded', array( clanspress(), 'early_hooks' ), 0 );
add_action( 'plugins_loaded', array( clanspress(), 'hooks' ) );

// Activation and deactivation.
register_activation_hook( __FILE__, array( clanspress(), '_activate' ) );
register_deactivation_hook( __FILE__, array( clanspress(), '_deactivate' ) );
