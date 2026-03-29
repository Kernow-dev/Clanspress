# Clanspress Agent Guidance

## Role And Mindset
- Act as a professional WordPress developer with a passion for gaming communities and esports products.
- Prioritize extensibility, backward compatibility, and predictable upgrade paths.
- Favor maintainable architecture over short-term hacks.
- Assume **third-party developers do not have full context** on the codebase: document public APIs, hooks, and non-obvious behavior in PHPDoc and keep this file aligned with real extension points.

## Engineering Standards
- Follow the latest WordPress Coding Standards (WPCS), modern PHP practices, and secure WP APIs.
- Use strict sanitization/escaping, capability checks, nonce verification, and i18n functions.

## Documentation Standard (Required For Third-Party Extensibility)

### PHPDoc (PHP)
- **Every** `public` and `protected` class method: short summary, `@param` for each parameter, and `@return` where applicable.
- **Interfaces**: document each method the same way.
- **Procedural helpers** (`clanspress_*()` in `functions.php` files): summary, `@param`, `@return`.
- **Hooks** (`apply_filters` / `do_action`): inline block immediately above the call listing hook name and every argument with type and meaning.
- **Classes**: file-level `@package clanspress` and a class summary describing responsibility and how extension authors should use it.

### `@since` tags
- Do **not** add `@since` while the plugin version is **1.0.0** (initial public API is treated as the baseline).
- After the plugin moves **beyond 1.0.0**, add `@since x.y.z` to **every new** public API surface (methods, functions, hooks, filter/action names) introduced in that release, and preserve backward compatibility for existing integrators.

### README And This File
- When adding or changing hooks, extension registration, or data-store contracts, update **`README.md`** (developer-facing sections) in the same change.
- Update **`AGENTS.md`** when you introduce new **categories** of extension points (e.g. a new data-store interface) or change the extension lifecycle.

## Extension Architecture (For Third-Party Authors)

### `requires` vs `parent_slug` (do not conflate)
- **`requires`**: Other extension slugs that **must be installed and enabled** before this one can run. Use this for hard dependencies (e.g. Matches **requires** Teams). This does **not** imply a parent/child relationship in the UI or in data modeling.
- **`parent_slug`**: Use **only** when this extension is intentionally a **sub-extension** of another (same product family, settings grouped under the parent in the unified admin, shared lifecycle semantics). Leave **`parent_slug` empty** for a normal top-level extension even if it lists dependencies in `requires`.

### Lifecycle
1. **Construct** your class extending `Kernowdev\Clanspress\Extensions\Skeleton` with a unique slug (lowercase, `a-z0-9_-`), name, version `x.y.z`, optional `parent_slug` (only for true sub-extensions), and `requires` slugs (hard dependencies).
2. **Register** on `clanspress_registered_extensions` (third-party) or `clanspress_official_registered_extensions` (first-party whitelist only — do not use for community extensions).
3. **Install**: site admin enables the extension in **Clanspress → Extensions**; stored in `clanspress_installed_extensions` (per-site option, or site option when saving from network admin).
4. **Boot**: `Loader::setup_extensions()` calls `can_install()`, optionally `run_updater()`, then `run()` for each installed extension.
5. **Data**: optional JSON-like blob per extension via `Skeleton::get_data()` / `set_data()` / `delete_data()` backed by `Extension_Data_Store` (filterable).

### Key Classes And Files
| Area | Class / file | Purpose |
|------|----------------|--------|
| Entry | `clanspress()` in `shortcut-function.php` | Returns `Main` singleton. |
| Core | `Kernowdev\Clanspress\Main` (`clanspress.php`) | Bootstrap, i18n, maintenance, loads `Extension_Loader`. |
| Loader | `Kernowdev\Clanspress\Extensions\Loader` | Discovers extensions, install state, runs `run()`. |
| Base extension | `Kernowdev\Clanspress\Extensions\Skeleton` | Slug, version, `run()`, installer hooks, blocks/templates helpers, extension data store. |
| Extension data | `Extension_Data_Store`, `Data_Store_WP` | Swappable persistence for `get_data()` / `set_data()`. |
| Settings UI base | `Abstract_Settings` | Option-backed admin pages for an extension. |
| Teams model | `Kernowdev\Clanspress\Extensions\Teams\Team` | In-memory team entity; persist via `Team_Data_Store` (implementations may map to `cp_team`). |
| Teams helpers | `inc/extensions/teams/functions.php` | Theme-safe `clanspress_teams_*()` wrappers. |
| Players helpers | `inc/extensions/players/functions.php` | Theme-safe `clanspress_players_*()` wrappers. |
| Matches extension | `Kernowdev\Clanspress\Extensions\Matches` | `cp_match` CPT, REST list/detail, JS blocks + editor sidebar; **requires** `cp_teams`, **not** a Teams sub-extension. |
| Matches helpers | `inc/extensions/matches/functions.php` | `clanspress_matches()` and related theme-safe helpers. |

### Hook Reference (Core Extension Flow)
**Filters**
- `clanspress_registered_extensions` — Register third-party extensions: `(array $slug => Skeleton)`.
- `clanspress_official_registered_extensions` — First-party only; must match whitelist in `Loader::get_official_extensions()`.
- `clanspress_extension_data_store` — Replace `Extension_Data_Store` for a given extension: args `( $data_store, $slug, Skeleton $extension )`.
- `clanspress_extension_{slug}_block_directories` / `clanspress_extension_block_directories` — Block build paths before `register_block_type_from_metadata` when using `Skeleton::register_extension_blocks()` (not used by first-party metadata-collection registration).
- `clanspress_extension_{slug}_templates` / `clanspress_extension_templates` — FSE template definitions for `register_block_template`.
- `clanspress_can_install_{slug}_extension` — Extra install gates: `(bool $can_install, Skeleton $extension)`.
- `clanspress_validate_installed_extensions` — After admin saves extension list: `( $new_installed, $requested, $available_extensions )`.

**Actions** (dynamic `{slug}` is your extension slug)
- `clanspress_extension_installer_{slug}`
- `clanspress_extension_uninstaller_{slug}`
- `clanspress_extension_updater_{slug}`
- `clanspress_extension_run_{slug}` — Main runtime hook: register CPTs, routes, blocks, etc.

Extension-specific hooks (e.g. teams, players) belong in PHPDoc next to each `apply_filters` / `do_action` in the relevant class.

## Blocks (Gutenberg)
- **Do not** register Clanspress blocks with `register_block_type()` in PHP (no PHP-only `render_callback` / attribute definitions) **unless the maintainer explicitly asks for that approach**.
- Blocks are **real JS blocks**: `block.json` with `editorScript` (and optional `style` / `editorStyle`), **built** with `@wordpress/scripts` from **`src/blocks/`** into the plugin root **`build/`** (matches / players / teams subtrees). Register with **`Skeleton::register_extension_block_types_from_metadata_collection( 'build/…' )`** (WordPress 6.8+ metadata collection API; fallbacks in `Skeleton` for older core). Use **`Skeleton::register_extension_blocks()`** only for extra third-party-style block directories.
- **Server output** for dynamic blocks belongs in **`render.php`** next to the block source under `src/blocks/…`, copied into `build/` by `--webpack-copy-php`, not in ad-hoc PHP callbacks in the extension class.
- After changing block sources, run **`npm run build:production`** (or **`npm run plugin-zip`** for a release zip).

## Block editor: custom post meta UI
- **`register_post_meta()`** with `show_in_rest` is **required** on the PHP side so meta persists through the REST API.
- **User-editable** match/post meta must also be exposed in the editor **with JavaScript** (e.g. `registerPlugin()` + `PluginDocumentSettingPanel` and `useEntityProp( 'postType', …, 'meta' )` from `@wordpress/core-data`) so fields appear in the **post sidebar** (or another clear editor surface). Do not rely on REST registration alone as the “editor UI.”

## Data Stores
- **Extension bucket store** (`Extension_Data_Store`): one record per extension slug (options / site options). Swap with `clanspress_extension_data_store`.
- **Entity stores** (e.g. `Team_Data_Store`): CRUD for domain objects; keep read/write logic out of templates and thin controllers.
- Prefer interface-driven stores with a WordPress default implementation; document new interfaces here and in README.

## Admin Extension Interface
- The unified **Clanspress** React settings screen keeps the active tab in the query string: `admin.php?page=clanspress&tab=<tabId>` (e.g. `general`, `extensions`, `ext-cp_teams`). Implementations should update `?tab=` when the user switches tabs and honor it on load so refresh and shared links reopen the same tab.
- Treat the extensions screen as critical UX: clear statuses, dependency visibility, and safe install toggles.
- Do not allow enabling extensions with unmet requirements.
- Keep admin actions idempotent and validated server-side.

## Documentation Workflow
- Keep `README.md` current whenever architecture, extension APIs, hooks, or setup steps change.
- Document new hooks and extension points in the README at the same time as code changes.
- When touching a public API surface, update PHPDoc in the same PR/commit so IDE hints and static analysis stay accurate.
