# Clanspress

Clanspress is a community management plugin for gaming teams, clans, and competitive communities.

## Development Principles
- Built for extensibility first.
- Uses modern WordPress APIs and coding standards.
- Keeps extension lifecycle and data access explicit and testable.

## Static analysis and compatibility checks

From the plugin root (with Composer dev dependencies installed):

| Command | Purpose |
|--------|---------|
| `composer phpstan` | [PHPStan](https://phpstan.org/) at **level 5** (`phpstan.neon.dist`, `phpstan-bootstrap.php` defines `ABSPATH` for stubs). |
| `composer lint:php` | [PHPCS](https://github.com/squizlabs/PHP_CodeSniffer) with **PHPCompatibilityWP** only (`phpcs.xml.dist`). |

Copy `phpstan.neon.dist` to `phpstan.neon` for local overrides (the latter is gitignored).

**Note:** Full WordPress-Core style sniffs are not part of the default `lint:php` command yet, to avoid large formatting-only churn. You can still run PHPCBF against `WordPress-Core` locally if you want to normalize whitespace.

## WordPress admin (Clanspress menu)

The top-level **Clanspress** menu opens a **React** settings shell (`src/admin/index.js`, built to `assets/dist/clanspress-admin.js`).

- **Tabs:** **General** (core options, `clanspress_general_settings`), **Extensions** (enable/disable; runs uninstallers for removed slugs), then one tab per **root** extension that exposes settings via `Skeleton::get_settings_admin()`.
- **Deep links:** the active tab is stored in the query string as `?page=clanspress&tab=<id>` (e.g. `general`, `extensions`, `ext-cp_teams`). Invalid `tab` values fall back to the first tab and the URL is corrected. Save notices stay visible above the tab row when switching tabs.
- **Child extensions** (`parent_slug` set): their settings sections appear **inside the parent extension tab** (grouped with an `<h3>` heading), each group saves its own option row via REST.
- **CPT menus:** Custom post types registered by the plugin should use `'show_in_menu' => 'clanspress'` so list tables appear under this menu (e.g. **Teams** / `cp_team`).

Build after changing the admin UI:

```bash
npm run build:admin
```

Uses `webpack.config.cjs` (CopyPlugin removed so only the admin bundle is emitted into `assets/dist/`).

Block sources live under `src/blocks/` (matches, players, teams). The match post sidebar script lives under `src/cp-match-editor/src/`. Everything compiles into the plugin root `build/` (plus per-extension `blocks-manifest.php` files). From the plugin root:

```bash
npm ci
npm run build:production   # admin + blocks + manifests + match editor
# or: npm run plugin-zip   # production build then clanspress.zip
```

**REST API** (authenticated, `manage_options`):

| Method | Route | Purpose |
|--------|--------|---------|
| `GET` | `/wp-json/clanspress/v1/admin/bootstrap` | Tabs, `optionSchemas`, current `values`, extensions list. |
| `PUT` | `/wp-json/clanspress/v1/admin/settings/{option_key}` | JSON body: field map; uses each `Abstract_Settings::sanitize()`. |
| `PUT` | `/wp-json/clanspress/v1/admin/extensions` | JSON `{ "installed": ["cp_players", ...] }`; uninstalls removed slugs then saves the list. |

To restore a **standalone** PHP submenu for an extension settings class, filter `clanspress_extension_settings_register_submenu` to `true` for that `Abstract_Settings` instance.

## Extension System
Extensions are registered through filter-based discovery and loaded by the extension loader.

### Core Rules
- Every extension has a unique slug and semantic version.
- Extensions may declare dependencies (`requires`) and parent-child relationships (`parent_slug`).
- Extensions with unmet requirements must not be enabled.
- Lifecycle methods are available for installer, updater, runtime boot, and uninstaller flows.

### Extension loader bootstrap

`Main::$extensions` is **`null` until `init()`** runs (after `load_plugin_textdomain()`). Theme or plugin code that runs earlier must not call `clanspress()->extensions` without a null check.

`Skeleton::can_install()` reads installed slugs via `Extension_Loader::read_installed_extensions_from_options()` so dependency checks work while the loader singleton is still constructing (avoiding a circular access on `clanspress()->extensions`).

### Extension Data Stores
Extensions should persist extension-specific data through a PHP data store abstraction.

- Contract: `Kernowdev\Clanspress\Extensions\Extension_Data_Store`
- Default implementation: `Kernowdev\Clanspress\Extensions\Data_Store_WP`
- Base extension helper methods:
  - `get_data()`
  - `set_data( array $data )`
  - `delete_data()`

Swap implementations with the `clanspress_extension_data_store` filter for custom storage backends.

### Extension-Owned Block Registration
Each extension should register its own blocks and keep its block list local to that extension class.

Block editor categories (registered on `block_categories_all`):

| Slug | Label | Intended blocks |
|------|--------|-----------------|
| `clanspress` | Clanspress | Cross-cutting / generic (e.g. player settings, team create form) |
| `clanspress-players` | Clanspress Players | Players extension (e.g. avatar, cover) |
| `clanspress-teams` | Clanspress Teams | Teams extension (e.g. team card) |
| `clanspress-matches` | Clanspress Matches | Matches extension (match list / match card) |

Set each block’s `category` in `block.json` to one of these slugs.

First-party extensions compile blocks to **`build/{matches|players|teams}/…`** and register them with **`Skeleton::register_extension_block_types_from_metadata_collection()`** (WordPress 6.8+ `wp_register_block_types_from_metadata_collection()`, with fallbacks for older releases). Manifests are generated at `build/…/blocks-manifest.php` during `npm run build:blocks`.

For ad-hoc or third-party blocks that ship as separate compiled folders, you can still use **`Skeleton::register_extension_blocks( array $block_directories )`** (`register_block_type_from_metadata()` per directory). Filters apply only to that path:

- `clanspress_extension_{slug}_block_directories`
- `clanspress_extension_block_directories`

### Extension-Owned FSE Template Registration
Extensions can and should register their own FSE templates, so template availability follows extension activation.

- Use base helper: `Skeleton::register_extension_templates( array $templates )`
- Expected template array shape:
  - key: template slug (for example `player-settings`)
  - value:
    - `title` => translated title string
    - `path` => absolute template file path
- Support template customization through:
  - `clanspress_extension_{slug}_templates`
  - `clanspress_extension_templates`

## Admin Extension Manager
The `Clanspress > Extensions` screen should remain the source of truth for extension state.

- Shows extension metadata (name, description, version, type, requirements).
- Prevents enabling extensions with unmet dependencies.
- Supports multisite-aware storage of installed extension records.
- Offers validation hooks before persistence (`clanspress_validate_installed_extensions`).

## Teams Modes
The Teams extension now supports mode-based behavior through admin settings (`Clanspress > Teams`):

- `single_team`: single organization/team setup (for traditional sports style sites).
- `multiple_teams`: multi-team clan setup under one community.
- `team_directories`: directory mode where users can create and manage teams.
  - Includes block-based FSE templates `teams-create` (`/teams/create/`) and `teams-manage` (`/teams/{slug}/manage/`, BuddyPress-style actions). Legacy `/teams/manage/{slug}/` still resolves. Extend actions via `clanspress_team_front_action_rewrite_slugs` and `clanspress_team_action_dispatch`.

Mode helpers available on the teams extension class:
- `get_team_mode()`
- `is_single_team_mode()`
- `is_multiple_teams_mode()`
- `is_team_directories_mode()`

### Per-Team Options
Each `cp_team` post supports individual options:
- Join mode: `open_join`, `join_with_permission`, `invite_only`
- Allow player invites
- Allow front-end team editing
- Allow banning players

These options are managed in the block editor sidebar (no metaboxes) and stored as post meta. PHP-side updates from the Teams extension (for example `update_team_options()` and roster persistence) go through the team data store so all structured fields stay on one path.

### Team entity data store

Team posts (`cp_team`) and their structured meta are persisted through a small CRUD layer, separate from the extension-wide option bucket described in [Extension Data Stores](#extension-data-stores).

- **Contract:** `Kernowdev\Clanspress\Extensions\Teams\Team_Data_Store` (`read` / `create` / `update` / `delete` on `Team` entities).
- **Default implementation:** `Kernowdev\Clanspress\Extensions\Teams\Team_Data_Store_CPT` (WordPress post + post meta).
- **Shared meta helpers:** `Kernowdev\Clanspress\Extensions\Data_Stores\WP_Post_Meta_Data_Store` — optional base for CPT-backed stores that need direct meta-table reads/writes in one place.

Swap the implementation with the **`clanspress_team_data_store`** filter. The filter must return a `Team_Data_Store` instance; anything else is ignored and the default CPT store is used.

**Procedural helper:** `clanspress_get_team( int $id )` loads a `Team` via the active store (or `null` if Teams is inactive or the post is not a team).

Third parties can customize the JS UI using JavaScript hooks:
- `clanspress.teams.joinModes`
- `clanspress.teams.optionControls`

JS example (add custom option control):

```js
const { addFilter } = wp.hooks;
const { createElement: el } = wp.element;
const { ToggleControl } = wp.components;

addFilter(
	'clanspress.teams.optionControls',
	'my-plugin/team-options-control',
	( controls, context ) => {
		return [
			...controls,
			el( ToggleControl, {
				key: 'my_custom_toggle',
				label: 'Enable custom team flag',
				checked: !! context.meta.cp_team_custom_flag,
				onChange: ( value ) =>
					context.setMetaValue( 'cp_team_custom_flag', !! value ),
			} ),
		];
	}
);
```

Teams extension helper methods:
- `get_team_options( int $team_id )`
- `update_team_options( int $team_id, array $options )`
- `can_user_join_team( int $team_id, int $user_id )`
- `can_invite_players( int $team_id )`
- `can_edit_team_frontend( int $team_id )`
- `can_ban_players( int $team_id )`

## Hooking And Customization
When adding new features, expose logical hooks around:
- extension registration and validation
- extension install and runtime checks
- settings sanitization and persistence
- admin interface decision points

### Settings Extensibility
All extension settings can be extended or customized by third parties.

For an extension option key (example: `clanspress_teams_settings`) these hooks are available:
- `{option_key}_parent_menu_slug`
- `{option_key}_defaults`
- `{option_key}_sections`
- `{option_key}_section_fields`
- `{option_key}_field`
- `{option_key}_render_field` (return `true` when custom rendering is handled)
- `{option_key}_sanitize_input`
- `{option_key}_sanitize`
- `{option_key}_before_page`
- `{option_key}_after_page`

Example: add a custom Teams mode and extra settings fields from a third-party plugin:

```php
<?php
/**
 * Plugin Name: Clanspress Teams Pro Modes
 */

// 1) Add a custom teams mode option.
add_filter(
	'clanspress_teams_mode_options',
	function ( array $options ): array {
		$options['academy_mode'] = __( 'Academy mode (junior squads)', 'my-plugin' );

		return $options;
	}
);

// 2) Add fields to the Teams "general" section.
add_filter(
	'clanspress_teams_settings_section_fields',
	function ( array $fields, string $section_id ): array {
		if ( 'general' !== $section_id ) {
			return $fields;
		}

		$fields['academy_max_players'] = array(
			'label'       => __( 'Academy max players', 'my-plugin' ),
			'type'        => 'text',
			'description' => __( 'Maximum players per academy team.', 'my-plugin' ),
			'default'     => '25',
			'sanitize'    => 'absint',
		);

		return $fields;
	},
	10,
	2
);

// 3) Enforce extra save rules for teams settings.
add_filter(
	'clanspress_teams_settings_sanitize',
	function ( array $output ): array {
		if ( isset( $output['academy_max_players'] ) ) {
			$output['academy_max_players'] = max( 5, absint( $output['academy_max_players'] ) );
		}

		return $output;
	}
);

// 4) Run mode-specific logic when your mode is active.
add_action(
	'clanspress_teams_mode_academy_mode',
	function ( \Kernowdev\Clanspress\Extensions\Teams $teams_extension ): void {
		// Boot academy-specific features here.
	}
);
```

Example: fully custom render a Teams setting field with `{option_key}_render_field`:

```php
<?php
// Render a custom UI for a specific field and mark it handled.
add_filter(
	'clanspress_teams_settings_render_field',
	function ( bool $handled, string $field_id, array $field, $value ): bool {
		if ( 'academy_max_players' !== $field_id ) {
			return $handled;
		}

		printf(
			'<input type="range" min="5" max="60" step="1" name="clanspress_teams_settings[%1$s]" value="%2$d" />',
			esc_attr( $field_id ),
			absint( $value )
		);

		echo ' <span>' . esc_html( absint( $value ) ) . '</span>';
		echo '<p class="description">' . esc_html__( 'Choose max players for academy teams.', 'my-plugin' ) . '</p>';

		// Returning true prevents default field rendering.
		return true;
	},
	10,
	4
);
```

Example: register extension-owned FSE templates from a third-party extension:

```php
<?php
add_filter(
	'clanspress_extension_cp_teams_templates',
	function ( array $templates ): array {
		$templates['team-archive'] = array(
			'title' => __( 'Team Archive', 'my-plugin' ),
			'path'  => plugin_dir_path( __FILE__ ) . 'templates/team-archive.php',
		);

		return $templates;
	}
);
```

Create-team form steps are filterable so third-party plugins can add their own steps:

```php
add_filter(
	'clanspress_team_create_form_steps',
	function ( array $steps ): array {
		$steps['custom_rules'] = array(
			'label' => __( 'Step 4: Custom Rules', 'my-plugin' ),
		);

		return $steps;
	}
);

add_action(
	'clanspress_team_create_form_step_custom_rules',
	function (): void {
		echo '<p><label for="my-team-rules">Rules</label><textarea id="my-team-rules" name="my_team_rules"></textarea></p>';
	}
);
```

Create-team step labels are currently:
- `Step 1: Team Details`
- `Step 2: Team Avatar`
- `Step 3: Player invites` (with autocomplete + removable invite chips)

Complete pattern: custom step + save custom data on team creation:

```php
<?php
// 1) Add a custom step to the create-team flow.
add_filter(
	'clanspress_team_create_form_steps',
	function ( array $steps ): array {
		$steps['brand_voice'] = array(
			'label' => __( 'Step 4: Brand Voice', 'my-plugin' ),
		);

		return $steps;
	}
);

// 2) Render custom fields for your step.
add_action(
	'clanspress_team_create_form_step_brand_voice',
	function (): void {
		?>
		<p>
			<label for="my-team-tone"><?php esc_html_e( 'Team Tone', 'my-plugin' ); ?></label>
			<select id="my-team-tone" name="my_team_tone">
				<option value="competitive"><?php esc_html_e( 'Competitive', 'my-plugin' ); ?></option>
				<option value="casual"><?php esc_html_e( 'Casual', 'my-plugin' ); ?></option>
			</select>
		</p>
		<p>
			<label for="my-team-tagline"><?php esc_html_e( 'Public Tagline', 'my-plugin' ); ?></label>
			<input type="text" id="my-team-tagline" name="my_team_tagline" />
		</p>
		<?php
	}
);

// 3) Persist custom data after core team creation succeeds.
add_action(
	'clanspress_team_created',
	function ( int $team_id, int $user_id, array $request ): void {
		$tone    = sanitize_key( wp_unslash( $request['my_team_tone'] ?? '' ) );
		$tagline = sanitize_text_field( wp_unslash( $request['my_team_tagline'] ?? '' ) );

		$allowed_tones = array( 'competitive', 'casual' );
		if ( ! in_array( $tone, $allowed_tones, true ) ) {
			$tone = 'competitive';
		}

		update_post_meta( $team_id, 'my_team_tone', $tone );
		update_post_meta( $team_id, 'my_team_tagline', $tagline );
	},
	10,
	3
);
```

### Documented Hooks
- `clanspress_registered_extensions`
  - Filter returning all third-party extension objects keyed by slug.
  - Args: `array $extensions`
- `clanspress_official_registered_extensions`
  - Filter used by first-party extensions to self-register before whitelist validation.
  - Args: `array $extensions`
- `clanspress_extension_data_store`
  - Filter to swap extension data store implementation.
  - Args: `Extension_Data_Store $data_store`, `string $slug`, `Skeleton $extension`
- `clanspress_extension_{slug}_block_directories`
  - Dynamic filter for extension-specific block build directories.
  - Args: `array $block_directories`, `Skeleton $extension`
- `clanspress_extension_block_directories`
  - Global filter for extension block build directories.
  - Args: `array $block_directories`, `Skeleton $extension`
- `clanspress_extension_{slug}_templates`
  - Dynamic filter for extension-specific FSE templates.
  - Args: `array $templates`, `Skeleton $extension`
- `clanspress_extension_templates`
  - Global filter for extension FSE templates.
  - Args: `array $templates`, `Skeleton $extension`
- `clanspress_can_install_{slug}_extension`
  - Dynamic filter used for extension requirement checks.
  - Args: `bool $can_install`, `Skeleton $extension`
- `clanspress_extension_installer_{slug}`
  - Dynamic action fired by base installer lifecycle.
  - Args: `Skeleton $extension`
- `clanspress_extension_updater_{slug}`
  - Dynamic action fired by base updater lifecycle.
  - Args: `Skeleton $extension`
- `clanspress_extension_uninstaller_{slug}`
  - Dynamic action fired by base uninstaller lifecycle.
  - Args: `Skeleton $extension`
- `clanspress_extension_run_{slug}`
  - Dynamic action fired by base runtime boot lifecycle.
  - Args: `Skeleton $extension`
- `clanspress_validate_installed_extensions`
  - Filter to enforce install policy before persisting extension state.
  - Args: `array $new_installed`, `array $requested`, `array $available_extensions`
- `clanspress_teams_mode`
  - Filter resolved teams mode from teams settings.
  - Args: `string $team_mode`, `Teams $extension`
- `clanspress_teams_mode_options`
  - Filter teams mode options used by teams admin settings.
  - Args: `array $options`, `Admin $admin`
- `clanspress_teams_mode_loaded`
  - Action fired after teams mode has been resolved.
  - Args: `string $team_mode`, `Teams $extension`
- `clanspress_teams_mode_{mode}`
  - Dynamic action fired for mode-specific boot logic.
  - Args: `Teams $extension`
- `clanspress_team_join_modes`
  - Filter available per-team join modes.
  - Args: `array $modes`, `Teams $extension`
- `clanspress_team_options`
  - Filter resolved per-team option map.
  - Args: `array $options`, `int $team_id`, `Teams $extension`
- `clanspress_team_data_store`
  - Filter team entity persistence implementation (must return `Team_Data_Store`; non-instance values fall back to the default CPT store).
  - Args: `Team_Data_Store $store`, `Teams $extension`
- `clanspress_team_options_updated`
  - Action fired after team options save.
  - Args: `int $team_id`, `array $options`, `Teams $extension`
- `clanspress_can_user_join_team`
  - Filter whether a user can join a team.
  - Args: `bool $can_join`, `int $team_id`, `int $user_id`, `array $options`, `Teams $extension`
- `clanspress_team_can_invite_players`
  - Filter team invite capability.
  - Args: `bool $allowed`, `int $team_id`, `array $options`, `Teams $extension`
- `clanspress_team_can_edit_frontend`
  - Filter front-end edit capability.
  - Args: `bool $allowed`, `int $team_id`, `array $options`, `Teams $extension`
- `clanspress_team_can_ban_players`
  - Filter ban capability.
  - Args: `bool $allowed`, `int $team_id`, `array $options`, `Teams $extension`

## Maintenance Notes
- Keep this README updated when extension architecture, hooks, or setup requirements change.
- Keep public hooks documented with intent and expected arguments.
