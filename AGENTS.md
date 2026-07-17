# Clanbite Agent Guidance

## Role And Mindset
- Act as a professional WordPress developer with a passion for gaming communities and esports products.
- Prioritize extensibility, backward compatibility, and predictable upgrade paths.
- Favor maintainable architecture over short-term hacks.
- Assume **third-party developers do not have full context** on the codebase: document public APIs, hooks, and non-obvious behavior in PHPDoc and keep this file aligned with real extension points.

## Engineering Standards
- Follow the latest WordPress Coding Standards (WPCS), modern PHP practices, and secure WP APIs.
- Use strict sanitization/escaping, capability checks, nonce verification, and i18n functions.

## WordPress.org Plugin Check And PHPCS (Agent Checklist)

These items recur in **Plugin Check** / **PHPCS** runs. Prefer fixing **errors** before **warnings**; avoid drive-by warning sweeps unless the maintainer asks.

### Direct file access (`ABSPATH`)
- **Every** PHP file that ships with the plugin should include `defined( 'ABSPATH' ) || exit;` (or equivalent) so the file cannot be executed directly.
- **Namespaced files** (`namespace …;`): put the guard on the line **immediately after** the `namespace` statement. Only `declare()` may appear before `namespace` in PHP; do **not** place the guard before `namespace`.
- **Procedural files** (no namespace): put the guard right after `<?php`, before `use` imports or other logic (e.g. `shortcut-function.php`, `templates/**/*.php`, `languages/index.php`).
- **Block server markup**: add the guard in **`src/blocks/**/render.php`** (source of truth). Webpack copies into **`build/`**; editing only `build/` is lost on the next `npm run build`.
- **`build/**/blocks-manifest.php`**: treat like other shipped PHP; include the guard.

### Generated `*.asset.php` (webpack / `@wordpress/scripts`)
- Default output is often a **single line**: `<?php return array( … );`. If Plugin Check requires a direct-access guard, the file must be **multi-line** so the guard runs **before** `return`:
  - `<?php` → blank line → `defined( 'ABSPATH' ) || exit;` → blank line → `return array( … );`
- **Do not** append the guard **after** a one-line `return` (it never executes).
- **`npm run build`** may **regenerate** `build/**/index.asset.php` (and similar). If checks regress after a build, restore this shape, adjust the build pipeline, or exclude generated assets per project policy—do not assume a one-time manual edit sticks.

### Plugin header: `Domain Path`
- **`Domain Path`** must use a **leading slash** per WordPress / Plugin Check (e.g. `/languages`) and match a **real** folder under the plugin root (`languages/`). Keep a minimal `languages/index.php` with a direct-access guard so the directory is valid even before `.mo` files exist.

### Shipped assets: file names
- Under **`assets/`** (and anywhere in the distributable zip), use **no spaces** and **no odd characters** in file and folder names (Plugin Check `badly_named_files`). Prefer lowercase `a-z0-9._-`.
- **`node_modules/`** may contain third-party files with bad names (e.g. copied flag packs). Release artifacts should **exclude** `node_modules` (see **`.distignore`**). When running Plugin Check locally on a dev tree, **exclude `node_modules`** from the scan scope if the tool allows—do not rename files inside dependencies.

### Filesystem moves (uploads / temp files)
- Avoid bare PHP **`rename()`** for paths WordPress owns; use **`WP_Filesystem`** and **`$wp_filesystem->move()`** after `WP_Filesystem()` is initialized (satisfies `WordPress.WP.AlternativeFunctions.rename_rename`).

### Database (`$wpdb`)
- Favor **`$wpdb->prepare()`** for all dynamic **values**. Table names are not placeholders: build them from trusted sources (e.g. `$wpdb->prefix` + known suffix) and document why; use a **narrow `phpcs:ignore`** with a one-line rationale when sniffs flag “NotPrepared” for identifier-safe SQL.
- **`LIMIT` / `OFFSET`**: use integer casting or placeholders in a way your PHPCS rules accept; avoid passing unchecked user strings into raw SQL fragments.

### Admin / developer diagnostics (`_doing_it_wrong`, loader validation)
- Messages passed to **`_doing_it_wrong()`** (and similar core APIs) are **developer-facing**, not theme output. **Do not** blindly wrap them in `esc_html()` if that breaks translation placeholders.
- If **`WordPress.Security.EscapeOutput.OutputNotEscaped`** fires on those calls, use a **tight `phpcs:disable` / `phpcs:enable`** around the specific block, or an inline ignore on the line, with a short comment.

### Exceptions with translated strings
- **`InvalidArgumentException`** (and similar) with `__( … )` may trigger **`ExceptionNotEscaped`**. Prefer a documented **`phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped`** on the `throw` line over removing translations.

### Expectations: warnings you may not “fix” in one pass
- **Block `render.php`** and classic templates often trigger **`PrefixAllGlobals`** for `$block`, `$attributes`, etc.; that is normal for WordPress templates unless the project adopts file-level exclusions.
- **`load_plugin_textdomain()`** may be flagged as discouraged for WordPress.org-hosted plugins; follow product policy (keep for non.org installs or drop when fully on language packs only).
- Do **not** rename WordPress core globals (e.g. `$_wp_current_template_id`) to satisfy prefix rules.

## Documentation Standard (Required For Third-Party Extensibility)

### PHPDoc (PHP)
- **Every** `public` and `protected` class method: short summary, `@param` for each parameter, and `@return` where applicable.
- **Interfaces**: document each method the same way.
- **Procedural helpers** (`clanbite_*()` in `functions.php` files): summary, `@param`, `@return`.
- **Hooks** (`apply_filters` / `do_action`): inline block immediately above the call listing hook name and every argument with type and meaning.
- **Classes**: file-level `@package clanbite` and a class summary describing responsibility and how extension authors should use it.

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
1. **Construct** your class extending `Kernowdev\Clanbite\Extensions\Skeleton` with a unique slug (lowercase, `a-z0-9_-`), name, version `x.y.z`, optional `parent_slug` (only for true sub-extensions), and `requires` slugs (hard dependencies).
2. **Register** on `clanbite_registered_extensions` (third-party) or `clanbite_official_registered_extensions` (first-party whitelist only — do not use for community extensions).
3. **Install**: site admin enables the extension in **Clanbite → Extensions**; stored in `clanbite_installed_extensions` (per-site option, or site option when saving from network admin).
4. **Boot**: `Loader::setup_extensions()` calls `can_install()`, optionally `run_updater()`, then `run()` for each installed extension.
5. **Data**: optional JSON-like blob per extension via `Skeleton::get_data()` / `set_data()` / `delete_data()` backed by `Extension_Data_Store` (filterable).

### Key Classes And Files
| Area | Class / file                                                                                                                                                                              | Purpose |
|------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|
| Entry | `clanbite()` in `shortcut-function.php`                                                                                                                                                   | Returns `Main` singleton. |
| Core | `Kernowdev\Clanbite\Main` (`clanbite.php`)                                                                                                                                                | Bootstrap, i18n, maintenance, loads `Extension_Loader`. Registers core blocks (e.g. `clanbite/visibility-container` from `build/core/visibility-container`). Visibility rules: `Kernowdev\Clanbite\Blocks\Visibility_Container` (`inc/Blocks/Visibility_Container.php`); thin `clanbite_visibility_container_*()` wrappers in `inc/visibility-container.php`. Role labels for the block editor are localized on the block’s own `editorScript` handle (not `wp-block-editor`). |
| Public REST | `Kernowdev\Clanbite\Public_Rest` (`inc/class-public-rest.php`)                                                                                                                            | Unauthenticated `clanbite/v1/discovery` + `public-team` for cross-site detection and team cards. |
| Cross-site matches | `Kernowdev\Clanbite\Cross_Site_Match_Sync` (`inc/class-cross-site-match-sync.php`)                                                                                                        | Ed25519-signed `sync-peer-match` inbound route + outbound push when a challenge is accepted; per-install keys in `clanbite_match_sync_site_keys`, public key at `GET clanbite/v1/site-sync-public-key`. Optional legacy HMAC via `clanbite_cross_site_sync_key` filter only. |
| Team challenges | `Kernowdev\Clanbite\Extensions\Teams\Team_Challenges` (`inc/extensions/teams/class-team-challenges.php`)                                                                                  | Internal `clanbite_team_challenge` CPT, challenge REST + media upload, notifications (`team_challenge_accept` / `team_challenge_decline`), ties accept flow to `cp_match` + optional events. |
| Loader | `Kernowdev\Clanbite\Extensions\Loader`                                                                                                                                                    | Discovers extensions, install state, runs `run()`. |
| Base extension | `Kernowdev\Clanbite\Extensions\Skeleton`                                                                                                                                                  | Slug, version, `run()`, installer hooks, blocks/templates helpers, extension data store. |
| Extension data | `Extension_Data_Store`, `Data_Store_WP`                                                                                                                                                   | Swappable persistence for `get_data()` / `set_data()`. |
| Settings UI base | `Abstract_Settings`                                                                                                                                                                       | Option-backed admin pages for an extension. |
| Admin (unified UI) | `Kernowdev\Clanbite\Admin\Settings`, `General_Settings`, `Groups_Settings`, `Admin_Rest` (`inc/admin/`)                                                                                   | Clanbite → Settings React app. **`Groups_Settings`** (`clanbite_groups_settings`) registers the **Groups** tab for group profile integrations (not an extension slug). |
| Teams model | `Kernowdev\Clanbite\Extensions\Teams\Team`                                                                                                                                                | In-memory team entity; persist via `Team_Data_Store` (implementations may map to `cp_team`). |
| Teams helpers | `inc/extensions/teams/functions.php`                                                                                                                                                      | Theme-safe `clanbite_teams_*()` wrappers. Team avatars: `clanbite_teams_get_display_team_avatar( $team_id, $suppress, $size, $context, $avatar_preset )` with presets mapped to **Teams → Team avatar image sizes**. |
| Players helpers | `inc/extensions/players/functions.php`                                                                                                                                                    | Theme-safe `clanbite_players_*()` wrappers; `clanbite_player_profile_context_user_id()` resolves the profile owner from the main query or canonical `/players/{nicename}/` path (`clanbite_player_user_id_from_canonical_request_path()`); `clanbite_player_blocks_resolve_subject_user_id()` aligns player block user resolution with that context. Player avatars: resolve URLs with `clanbite_players_get_display_avatar( $user_id, $suppress, $size, $context, $avatar_preset )` (`large` / `medium` / `small` presets map to **Players → Player avatar image sizes**); build `<img>` with `clanbite_players_get_player_avatar_img_html()`; wrap with `clanbite_players_apply_player_avatar_display_markup()` (see Hook Reference). |
| Avatar helpers (unified) | `inc/avatar-helpers.php`                                                                                                                                                                 | **Centralized avatar rendering system**: `clanbite_render_avatar()` (main entry point), `clanbite_render_avatar_for_interactivity()` (for Interactivity API contexts like forums/social feed), `clanbite_get_avatar_url()`, `clanbite_get_avatar_profile_url()`. All avatar markup should go through these functions for consistent structure, shape support, and filter points. See inline PHPDoc for usage examples and all available filters/actions. |
| Matches extension | `Kernowdev\Clanbite\Extensions\Matches`                                                                                                                                                   | `cp_match` CPT, REST list/detail, JS blocks + editor sidebar; **requires** `cp_teams`, **not** a Teams sub-extension. Optional team profile tab `/teams/{slug}/matches/` when `subpage_team` is on in `clanbite_matches_settings`. |
| Matches helpers | `inc/extensions/matches/functions.php`                                                                                                                                                    | `clanbite_matches()` and related theme-safe helpers. |
| Notifications extension | `inc/extensions/notifications/class-extension-notifications.php`, `Kernowdev\Clanbite\Extensions\Notifications`                                                                           | Official slug `cp_notifications` (optional; one-time default-on migration). **Requires** `cp_players`. `run()` registers `Extensions\Notifications\Admin` settings (`clanbite_notifications_settings`) then boots `Kernowdev\Clanbite\Extensions\Notification\Notifications_Runtime`. |
| Notifications subsystem | `inc/extensions/notifications/*.php` (namespace `Kernowdev\Clanbite\Extensions\Notification`), `clanbite_*()` in `inc/extensions/notifications/functions.php` (Composer `files` autoload) | Schema, data access, REST, runtime hooks; runtime registers only when the extension is installed; procedural helpers are always loadable for graceful checks (e.g. `function_exists( 'clanbite_notify' )`). Bell long-polling: `clanbite_notifications_poll_long_polling_enabled()` (backed by `clanbite_notifications_settings.poll_long_polling`). Front-end bell (`notification-bell` view script) fires `wp.hooks` action `clanbite.notifications.polled` after each successful HTTP poll or WebSocket message so other UI can sync on the same cadence (see README). |
| Player settings (front) | `build/players/player-settings/view.js` (source: `src/blocks/…`), `CLANBITEPLAYERSETTINGS`                                                                                                | Interactivity store; extensions use `actions.runPluginAction` + `data-cp-action-*` attributes instead of inline scripts. |
| Profile subpages (shared) | `inc/profile-subpages.php`                                                                                                                                                                | `clanbite_profile_subpage_registry_map()`, `clanbite_register_profile_subpage()`, `clanbite_get_profile_subpages()`, `clanbite_profile_subpages_nav_enabled()` (defaults true; extensions gate their own tabs via settings), `clanbite_profile_subpages_visible_for_nav()`. Core contexts: `player`, `team`, `group`. Wrappers: `clanbite_register_player_subpage()` / `clanbite_get_player_subpages()` (and team/group equivalents in `inc/extensions/teams/functions.php`, `inc/groups/functions.php`). **Events** profile tabs: `events_profile_subpage` on **Players** / **Teams** / **Groups** admin tabs; runtime checks `clanbite_events_subpage_*_enabled()`. **Notifications** player tab: `clanbite_notifications_subpage_player_enabled()`. **Matches** team tab: `clanbite_matches_subpage_team_enabled()`. |
| Front block templates | `templates/**/*.html` + matching `*.php`, `inc/functions-block-templates.php` (`clanbite_render_block_markup_file()`)                                                                     | FSE: `register_block_template()` loads `.html` content. Classic: PHP loaders wrap markup with `do_blocks()`. On block themes, `Players::set_plugin_block_template_id_for_site_editor` / `Teams::set_plugin_block_template_id_for_site_editor` (hook `wp` 99) set `$_wp_current_template_id` so **Edit Site** targets `clanbite//…` instead of a theme fallback (e.g. Archive on author URLs). **Virtual template parts** (theme slug + slug): `clanbite-team-profile-header` (`Teams`), `clanbite-player-profile-header` (`Players`). **Groups:** no templates or blocks in core; an add-on registers `cp_group` markup and may supply a virtual part such as `clanbite-group-profile-header`. Core ships `inc/groups/functions.php` for group profile helpers (`clanbite_group_profile_nav_context`, manage URL filters). |
| Events extension | `inc/extensions/events/class-extension-events.php`, `Kernowdev\Clanbite\Extensions\Events`                                                                                                | Official slug `cp_events` (optional; one-time default-on migration). **Requires** `cp_players`. `run()` boots `Kernowdev\Clanbite\Events\Events` only (no extension settings tab). Profile Events subpages are toggled from **Players**, **Teams**, and **Groups** settings (`clanbite_events_subpage_*_enabled()` in `inc/extensions/events/functions.php`). Uninstaller deletes `cp_event` posts and drops the RSVP table. |
| Events runtime | `inc/events/` (`Events`, `Event_Post_Type`, `Event_Entity_Rest_Controller`, `Event_Permissions`)                                                                                          | Loads only when `cp_events` is installed. `cp_event` is scoped to **teams** or **groups**. **Players do not create events** — only team/group managers (see REST + `event-create-form`). A **player “my events” / calendar** UI is for the **profile owner only** (the logged-in user viewing their own player profile): aggregate visible events from their memberships; do not treat it as a public calendar of another user’s combined schedule unless product requirements explicitly call for that and privacy is addressed. |
| Forums extension (companion plugin) | `clanbite-forums` → `Kernowdev\ClanbiteForums\Extension\Forums`                                                                                                                           | Official slug `cp_forums` (separate plugin; whitelisted like Social Kit). **Requires** `cp_players`. Custom tables (`Schema`), REST `clanbite-forums/v1`, front blocks `clanbite-forums/forums-index`, `clanbite-forums/forum-topics`, `clanbite-forums/forum-topic` (shared Interactivity store `clanbite-forums/board`), plugin FSE templates `clanbite//forums-index`, `clanbite//forum-topics`, `clanbite//forum-topic` + `Forums\Template_Router` on the router page, player settings tab for **forum signature** (`cp_forum_signature` user meta), topic follow + `clanbite_notify( …, 'forum_reply', … )` when **Notifications** is enabled. Site cap: `manage_clanbite_forums`. |
| Points extension (companion plugin) | `clanbite-points` → `Kernowdev\ClanbitePoints\Extension\Points`                                                                                                                           | Official slug `cp_points` (separate plugin; whitelisted like Social Kit). **Requires** `cp_players`. Ledger table `{$wpdb->prefix}clanbite_points_ledger`, option `clanbite_points_settings` (repeaters in unified admin), user meta `clanbite_points_balances` (per-type totals). Block `clanbite-points/points-balance`. Procedural: `clanbite_points_award()`, `clanbite_points_get_balance()`, `clanbite_points_get_user_rank_label()`, `clanbite_points_extension_active()`. |
| Ranks extension (companion plugin) | `clanbite-ranks` → `Kernowdev\ClanbiteRanks\Extension\Ranks`                                                                                                                            | Official slug `cp_ranks` (separate plugin; whitelisted like Social Kit). **Requires** `cp_players`. Optional integration with Points; rank ladders, icon packs, team ranks, avatar progress. Block `clanbite-ranks/rank-progress`. Procedural helpers in `inc/functions.php` (e.g. `clanbite_ranks_extension_active()`). |

### Hook Reference (Core Extension Flow)
**Filters**
- `clanbite_registered_extensions` — Register community / third-party extensions: `(array $slug => Skeleton)`.
- `clanbite_official_registered_extensions` — First-party only; must match whitelist in `Loader::get_official_extensions()`. Whitelist entries may point at classes loaded by **bundled** code or by a **separate first-party plugin** (same validation path; **Core** badge still comes only from `clanbite_core_bundled_extension_slugs`).
- `clanbite_extension_data_store` — Replace `Extension_Data_Store` for a given extension: args `( $data_store, $slug, Skeleton $extension )`.
- `clanbite_extension_{slug}_block_directories` / `clanbite_extension_block_directories` — Block build paths before `register_block_type_from_metadata` when using `Skeleton::register_extension_blocks()` (not used by first-party metadata-collection registration).
- `clanbite_extension_{slug}_templates` / `clanbite_extension_templates` — FSE template definitions for `register_block_template`.
- `clanbite_player_settings_frontend_config` — Filter the array passed to `wp_localize_script` as `CLANBITEPLAYERSETTINGS` (keys: `ajax_url`, `nonce`, `rest_url`, `rest_nonce`, and on the player settings screen `settings_url_base`, `settings_initial_nav`, `settings_initial_panel`). Used by the player-settings block for REST-backed extension UI and deep-link routing; see README **Player settings (front-end): plugin actions**.
- `clanbite_should_enqueue_player_settings_frontend_assets` — Gate whether `CLANBITEPLAYERSETTINGS` is printed on a front request: `(bool $enqueue)` after core heuristics (player settings route, logged-in author profile, singular posts with the player-settings block). Return true to force enqueue when custom templates need the payload.
- `clanbite_can_install_{slug}_extension` — Extra install gates: `(bool $can_install, Skeleton $extension)`.
- `clanbite_validate_installed_extensions` — After admin saves extension list: `( $new_installed, $requested, $available_extensions )`.
- `clanbite_visibility_container_should_show` — Whether to render **Visibility container** inner blocks for the current visitor: `(bool $visible, array $attributes, \WP_Block|null $block)`.
- `clanbite_visibility_container_editor_script_handle` — Override the script handle used to pass role labels into the visibility-container block editor: `(string $handle)` (default: registered `editorScript` for `clanbite/visibility-container`).
- `clanbite_required_extension_slugs` — Slugs that cannot be disabled (default `cp_players` only): `(array $slugs)`.
- `clanbite_core_bundled_extension_slugs` — Slugs shipped inside the main Clanbite plugin (admin **Core** badge); excludes separate add-on plugins: `(array $slugs)`.
- `clanbite_install_notifications_extension_by_default` — If true (default), the loader runs a **one-time** migration that adds `cp_notifications` to installed extensions when missing; set false before first boot to skip: `(bool $enable)`.
- `clanbite_install_events_extension_by_default` — If true (default), the loader runs a **one-time** migration that adds `cp_events` when missing; set false before first boot to skip: `(bool $enable)`.
- `clanbite_notifications_extension_active` — Whether in-site notifications are considered available (installed `cp_notifications`): `(bool $active)`. Theme and third-party code should use `clanbite_notifications_extension_active()` or handle `WP_Error` from `clanbite_notify()` (code `notifications_inactive`).
- `clanbite_events_extension_active` — Whether scheduled events (`cp_event`, RSVP REST, event blocks) are available (installed `cp_events`): `(bool $active)`. Use `clanbite_events_extension_active()` before assuming event APIs exist.
- `clanbite_load_players_directory_template` — Resolved PHP template path for `/players/`: `(string $path, string $located_theme_path)`.
- `clanbite_players_directory_per_page` — User-query page size on the players directory shortcode: `(int $per_page)`.
- `clanbite_redirect_author_archive_to_players_url` — 301 target for `/author/…` and `?author=` → `/players/…`: `(string $target, \WP_User $user)`.
- `clanbite_player_profile_url` — Canonical URL for `/players/{nicename}/` used by {@see clanbite_get_player_profile_url()}: `(string $url, int $user_id)`.
- `clanbite_players_social_profile_field_definitions` — Social fields on **Profile → Social Networks** and matching user meta keys: `(array $definitions)` map of slug → `label` / `placeholder`.
- `clanbite_players_get_display_social` — Single social field after user meta: `(string $value, string $slug, int $player_id)`.
- `clanbite_players_social_profile_link_url` — Final resolved URL for **Player social links** / helpers: `(string $url, string $slug, int $player_id, string $raw_meta)`.
- `clanbite_players_social_profile_svg_icon` — Inline SVG for a social slug in the **Player social links** block: `(string $svg_markup, string $slug)`.
- `clanbite_player_social_links_block_items` — Ordered link rows before render: `(array $items, int $user_id, \WP_Block $block)` each item `slug`, `url`, `label`.
- `clanbite_player_settings_update_social_profile_value` — Mutate or reject a value before save (return `WP_Error` to block): `(string|WP_Error $value, string $slug, int $user_id)`.

**Unified Avatar System** (`inc/avatar-helpers.php`)
All avatar rendering should go through `clanbite_render_avatar()` or `clanbite_render_avatar_for_interactivity()` for consistent structure and filter points. See inline PHPDoc for full usage examples.

- `clanbite_avatar_html` — Modify complete avatar HTML: `(string $html, array $args)`. `$args` includes `type` (player/team/group), `id`, `size`, `context`, `shape`, etc. This is the main customization point.
- `clanbite_avatar_html_{context}` — Context-specific variant (e.g., `clanbite_avatar_html_forum`, `clanbite_avatar_html_social_feed`): `(string $html, array $args)`.
- `clanbite_{type}_avatar_html` — Type-specific variant (e.g., `clanbite_player_avatar_html`, `clanbite_team_avatar_html`): `(string $html, array $args)`.
- `clanbite_avatar_classes` — Modify CSS classes array: `(array $classes, array $args)`.
- `clanbite_avatar_classes_{context}` — Context-specific classes: `(array $classes, array $args)`.
- `clanbite_{type}_avatar_classes` — Type-specific classes: `(array $classes, array $args)`.
- `clanbite_avatar_img_attributes` — Modify `<img>` attributes: `(array $img_attrs, array $args)`.
- `clanbite_avatar_img_attributes_{context}` — Context-specific image attributes: `(array $img_attrs, array $args)`.
- `clanbite_avatar_interactivity_html` — Complete HTML for Interactivity API contexts (forums, social feed): `(string $html, array $args)`.
- `clanbite_avatar_interactivity_html_{context}` — Context-specific Interactivity HTML: `(string $html, array $args)`.
- `clanbite_avatar_interactivity_classes` — CSS classes for Interactivity avatars: `(array $classes, array $args)`.
- `clanbite_avatar_interactivity_classes_{context}` — Context-specific Interactivity classes: `(array $classes, array $args)`.
- `clanbite_avatar_interactivity_img_attributes` — Image attributes for Interactivity avatars: `(array $img_attrs, array $args)`.
- `clanbite_avatar_interactivity_img_attributes_{context}` — Context-specific Interactivity image attributes: `(array $img_attrs, array $args)`.

**Avatar Actions**
- `clanbite_before_avatar_inner` — Before avatar inner content: `(array $args)`.
- `clanbite_before_{type}_avatar_inner` — Type-specific before inner: `(array $args)`.
- `clanbite_avatar_overlays` — Add custom overlays (badges, status, etc.): `(array $args)`.
- `clanbite_avatar_overlays_{context}` — Context-specific overlays: `(array $args)`.
- `clanbite_{type}_avatar_overlays` — Type-specific overlays: `(array $args)`.
- `clanbite_after_avatar_inner` — After avatar inner content: `(array $args)`.
- `clanbite_after_{type}_avatar_inner` — Type-specific after inner: `(array $args)`.
- `clanbite_avatar_interactivity_content` — Add custom content to Interactivity avatars: `(array $args)`.
- `clanbite_avatar_interactivity_content_{context}` — Context-specific Interactivity content: `(array $args)`.

**Legacy Avatar Filters** (Deprecated; migrate to unified system)
- `clanbite_players_get_display_avatar` — Player avatar image URL after attachment/default resolution: `(string $url, int $user_id, string|array $size, string $context, string $avatar_preset)`. `$avatar_preset` is `large`, `medium`, `small`, or empty when an explicit `$size` was used (presets map to **Players → Player avatar image sizes**). Use `$context` to vary behaviour by surface (`player_avatar_block`, `user_nav`, `notifications`, `profile_settings_rest`, etc.). Pair with `clanbite_players_get_display_avatar_id` for attachment-based logic.
- `clanbite_players_resolve_player_avatar_image_size` — Maps preset to registered size slug before URL resolution: `(string $size, string $preset, string $raw, string $fallback)`.
- `clanbite_players_image_size_choices_for_settings` — Slug → label map for Players/Teams avatar size dropdowns: `(array $choices)`.
- `clanbite_teams_get_display_team_avatar` — Team avatar URL: `(string $url, int $team_id, string|array $size, string $context, string $avatar_preset)`.
- `clanbite_teams_resolve_team_avatar_image_size` — Maps team preset to size slug: `(string $size, string $preset, string $raw, string $fallback)`.
- `clanbite_players_player_avatar_img_attributes` — Attribute map for the avatar `<img>` before the tag is built: `(array $attr, int $user_id, array $args, string $url)`.
- `clanbite_players_player_avatar_img_html` — Final `<img>` fragment from `clanbite_players_get_player_avatar_img_html()`: `(string $html, int $user_id, array $args, string $url)`.
- `clanbite_players_player_avatar_display_markup` — Inner avatar fragment after core builds image or empty-state markup (before profile link wrapping in blocks): `(string $inner, int $user_id, array $args)`.
- `clanbite_players_player_avatar_empty_img_markup` — Empty-state upload placeholder `<img>` in the player avatar block: `(string $html, int $user_id, array $args)`.
- `clanbite_players_player_avatar_placeholder_markup` — Text placeholder when the user has no avatar and inline upload is off: `(string $html, int $user_id, array $args)`.

**Other Filters**
- `clanbite_team_challenge_button_visible` — Show the **Team challenge** block UI: `(bool $visible, int $team_id, \WP_Block $block)`.
- `clanbite_team_challenge_notify_user_ids` — Recipients for new challenge notifications: `(array $user_ids, int $team_id)`.
- `clanbite_team_challenge_rest_response` — Mutate JSON bodies from challenge REST routes before encoding: `(array $payload, string $route_key, \WP_REST_Request $request)` (`route_key`: `remote_team`, `upload_media`, `create_challenge`).
- `clanbite_matches_rest_query_args` — `WP_Query` arguments for `GET clanbite/v1/matches`: `(array $args, \WP_REST_Request $request)`.
- `clanbite_match_rest_prepare` — Match row for REST list/detail: `(array $item, \WP_Post $post, \WP_REST_Request $request, string $context)` (`collection`|`single`).
- `clanbite_matches_rest_collection_data` — Full collection response body (`matches`, `total`, `pages`): `(array $body, \WP_REST_Request $request)`.
- `clanbite_viewer_can_see_match` — Final visibility for a match post: `(bool $can, \WP_Post $post, int $viewer_id)`.
- `clanbite_match_card_block_inner_markup` — Inner SSR markup for **Match card**: `(string $markup, array $attributes, \WP_Block $block)`.
- `clanbite_match_list_block_inner_markup` — Inner SSR markup for **Match list**: `(string $markup, array $attributes, \WP_Block $block)`.
- `clanbite_match_block_wrapper_attributes` — Attribute map for {@see get_block_wrapper_attributes()} on Match blocks: `(array $wrapper_attrs, string $block_name, \WP_Block $block)`.
- `clanbite_block_markup_before_do_blocks` — Template file markup before {@see do_blocks()} in {@see clanbite_render_block_markup_file()}: `(string $markup, string $markup_file)`.
- `clanbite_block_fragment_allowed_html` — Extend {@see wp_kses()} allow-list for block fragments (return a modified copy): `(array $allowed)`.
- `clanbite_admin_rest_manage_capability` — Capability checked by `clanbite/v1/admin/*` REST routes (default `manage_options`): `(string $capability)`.
- `clanbite_extension_capabilities` — Optional hints merged into each extension row as `capabilities` in admin bootstrap JSON: `(array $capabilities, string $slug, Skeleton $extension)`.
- `clanbite_admin_rest_settings_registry` — Extra `Abstract_Settings` handlers after core registration: `(array<string, Abstract_Settings> $registry, Loader $loader)`. Invalid entries ignored.
- `clanbite_admin_rest_tabs` — Tab list for the React shell: `(array<int, array<string, mixed>> $tabs, Loader $loader)`. Types: `general`, `extensions`, `extension`, `integration`.
- `clanbite_admin_rest_bootstrap` — `GET clanbite/v1/admin/bootstrap` payload: `(array $data, Loader $loader)`. React mirrors into `window.clanbiteAdmin.bootstrap` after fetch.
- `clanbite_admin_icon_packs` — Bootstrap `iconPacks`; companions merge normalized rows here (Points/Ranks source filters **`clanbite_points_icon_packs`**, **`clanbite_ranks_icon_packs`**).
- `clanbite_admin_infer_icon_pack_scope` — When a pack row has no valid `scope`, infer from sanitized pack id (default `all`): `(string $scope, string $pack_id)`.
- `clanbite_cross_site_sync_key` — Legacy shared HMAC secret (optional): `(string $key)` non-empty forces legacy `timestamp:hmac` headers instead of Ed25519 `v1:…` (for old integrations only).
- `clanbite_cross_site_sync_outbound_payload` — Mutate signed body before push to peer: `(array $body, int $challenge_id, int $match_id, int $challenged_team_id, array $snapshot)`.
- `clanbite_cross_site_sync_incoming_payload` — Mutate decoded JSON after signature check: `(array $body, string $source_host)`.
- `clanbite_cross_site_sync_verify_source` — Reject peer by host after valid signature: `(bool $allow, string $source_host, array $body)`.
- `clanbite_public_team_response` — Public team card for `public-team` REST: `(array $data, \WP_Post $team_post)`.
- `clanbite_user_managed_team_ids` — Teams a user may manage on the front end: `(array $ids, int $user_id, Teams $extension)`.
- `clanbite_groups_manage_url` — Group settings URL for profile nav: `(string $url, int $group_id)`.
- `clanbite_groups_user_can_manage` — Whether the user may manage the group (events REST, forms): `(bool $can, int $group_id, int $user_id)`. The plugin that owns `cp_group` should filter this for group admins/editors.
- `clanbite_groups_user_is_member` — Whether the user counts as a member for members-only event visibility: `(bool $is_member, int $group_id, int $user_id)`.
- `clanbite_group_subpages` — Group profile tab registry: `(array $registry)`.
- `clanbite_profile_subpage_registry_map` — Register extra profile contexts or override config (`globals_key`, `filter`, `template_prefix`): `(array $map)`.
- `clanbite_profile_subpage_nav_visible` — Whether a tab appears in profile nav after the capability check: `(bool $visible, string $context, string $slug, int $object_id, array $config)`.
- `clanbite_profile_subpages_nav_enabled` — Optional gate for core contexts `player` / `team` / `group` (default true; prefer extension settings for individual tabs): `(bool $enabled, string $context)`.
- `clanbite_profile_subpages_nav_enabled_for_unknown_context` — Same for custom contexts not in the map: `(bool $enabled, string $context)`.
- `clanbite_group_profile_nav_context` — Virtual group profile context when not on singular `cp_group`: `(array|null $context)`.
- `clanbite_group_events_create_url` — “Add event” URL from group calendar block: `(string $url, int $group_id)`.
- `clanbite_notification_poll_blocking_wait` — Allow `/notifications/poll` to sleep in a loop until timeout or new items: `(bool $blocking, int $user_id)`. The value passed in is the Notifications setting **Use long-polling** (`clanbite_notifications_settings.poll_long_polling`); filter to override per site or user.
- `clanbite_group_event_member_user_ids` — User IDs targeted by group-scoped event roster outreach (`notify` / `rsvp_tentative` on `POST`/`PUT` `event-posts`): `(array $user_ids, int $group_id)`. Core default empty; group plugins should populate.
- `clanbite_event_member_outreach_user_ids` — Final recipient list after core resolves team roster or group filter: `(array $user_ids, int $event_id, string $scope, int $team_id, int $group_id, string $mode)`.
- `clanbite_forums_topic_public_url` — Notification/share URL for a topic (and optional reply): `(string $url, string $forum_slug, string $topic_slug, int $reply_id)`.
- `clanbite_forums_reply_notification_recipients` — User IDs to notify on a new reply: `(array $user_ids, int $topic_id, int $reply_id, int $actor_id)`.
- `clanbite_forums_user_can_post_topic` — After core “who can post topics” policy and forum read checks (staff still bypass in core): `(bool $can, int $viewer_id, object $forum, string $policy)`. Chained before `clanbite_forums_user_can_create_topic` in REST and in serialized forum `viewer_can_post_topic`.
- `clanbite_forums_user_can_create_topic` — Final gate for creating a topic: `(bool $can, int $forum_id, int $user_id)`.
- `clanbite_forums_user_can_reply` — Whether the user may reply: `(bool $can, int $topic_id, int $user_id, object $topic_row)`.
- `clanbite_forums_block_initial_forum_slug` / `clanbite_forums_block_initial_topic_slug` / `clanbite_forums_block_initial_reply_id` — Deep-link defaults for the forums block (with query vars `cp_forums_*`).
- `clanbite_points_actions` — Register earning-action slugs for the Points admin UI: `(array $actions)` keyed by slug, values `array( 'label' => string )`.

**Actions**
- `clanbite_team_challenge_created` — `(int $challenge_id, int $challenged_team_id)`.
- `clanbite_team_challenge_accepted` — `(int $challenge_id, int $match_id, int $challenged_team_id)`.
- `clanbite_team_challenge_declined` — `(int $challenge_id, int $challenged_team_id, int $user_id)` after status set to declined.
- `clanbite_render_block_markup_file` — `(string $markup_file, string $markup)` immediately before {@see do_blocks()} output from {@see clanbite_render_block_markup_file()}.
- `clanbite_cross_site_sync_push_succeeded` — `(int $challenge_id, int $local_match_id, int $peer_match_id, string $peer_origin, array $body)`.
- `clanbite_cross_site_sync_push_failed` — `(\WP_Error $error, string $url, array $body)`.
- `clanbite_cross_site_sync_push_rejected` — `(int $http_code, array $data, string $url, array $body)`.
- `clanbite_cross_site_sync_incoming_created` — `(int $match_id, array $body, int $home_team_id)` after peer creates a local `cp_match`.
- `clanbite_event_member_outreach_completed` — After `member_outreach` runs for a `cp_event`: `(int $event_id, string $mode, array $result)` with `notified`, `rsvp_set`, `skipped`.
- `clanbite_extension_installer_{slug}` — Dynamic `{slug}`: extension install hook.
- `clanbite_extension_uninstaller_{slug}`
- `clanbite_extension_updater_{slug}`
- `clanbite_extension_run_{slug}` — Main runtime hook: register CPTs, routes, blocks, etc.
- `clanbite_forums_init` — Companion plugin loaded and Clanbite available: `( \Kernowdev\ClansbiteForums\Plugin $plugin )`.
- `clanbite_forums_rest_api_init` — After forums REST routes register: `( \Kernowdev\ClanbiteForums\Extension\Forums $extension )`.
- `clanbite_before_social_networks_fields` — Before **Profile → Social Networks** markup on player settings: `(int $user_id)`.
- `clanbite_after_social_networks_fields` — After that section: `(int $user_id)`.
- `clanbite_player_avatar_img_before` — Before building the player avatar `<img>` (URL resolved): `(int $user_id, array $args, string $url)`.
- `clanbite_player_avatar_img_after` — After the `<img>` string is built: `(int $user_id, array $args, string $url, string $html)`.
- `clanbite_points_awarded` — After ledger insert and balance meta update: `(int $user_id, string $points_type_key, string $action_key, int $amount, int $ledger_id, array $context)`.
- `clanbite_player_avatar_updated` — After successful player-settings avatar upload: `(int $user_id, int $attachment_id)`.
- `clanbite_player_cover_updated` — After successful player-settings cover upload: `(int $user_id, int $attachment_id)`.

Extension-specific hooks (e.g. teams, players) belong in PHPDoc next to each `apply_filters` / `do_action` in the relevant class.

## Blocks (Gutenberg)
- **Block inserter icons:** Per-block modules in `src/blocks/shared/block-icons/icons/{slug}.js` (Heroicons 24 solid via `@heroicons/react`). Each block’s `index.js` imports `clanbiteBlockIcon` from its slug file and passes `icon: clanbiteBlockIcon()` to `registerBlockType( metadata, … )`. Regenerate mapping with `node scripts/generate-block-icons.mjs`; then `npm run build:blocks`.
- **Do not** register Clanbite blocks with `register_block_type()` in PHP (no PHP-only `render_callback` / attribute definitions) **unless the maintainer explicitly asks for that approach**.
- **Unified class naming system:** All Clanbite blocks follow a consistent BEM-based naming convention documented in **`DESIGN-SYSTEM.md`**. Buttons use WordPress core classes (`wp-block-button`, `wp-element-button`) for theme.json compatibility plus Clanbite variants (`clanbite-button`, `clanbite-button--primary`). Avatars, covers, and other components use unified base classes (e.g., `clanbite-avatar`, `clanbite-cover`) with type modifiers (e.g., `clanbite-avatar--player`, `clanbite-cover--team`). Shared styles are in **`src/blocks/shared/styles/`** (`_buttons.scss`, `_avatars.scss`, `_covers.scss`) and imported by individual block styles. Backward compatibility is maintained for legacy class names via SCSS `@extend`.
- Blocks are **real JS blocks**: `block.json` with `editorScript` (and optional `style` / `editorStyle`), **built** with `@wordpress/scripts` from **`src/blocks/`** into the plugin root **`build/`** (matches / players / teams subtrees). Register with **`Skeleton::register_extension_block_types_from_metadata_collection( 'build/…' )`** (WordPress 6.8+ metadata collection API; fallbacks in `Skeleton` for older core). Use **`Skeleton::register_extension_blocks()`** only for extra third-party-style block directories.
- **Server output** for dynamic blocks belongs in **`render.php`** next to the block source under `src/blocks/…`, copied into `build/` by `--webpack-copy-php`, not in ad-hoc PHP callbacks in the extension class.
- **`get_block_wrapper_attributes()`** returns an already-escaped attribute string: echo it directly (with a `phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` and a short rationale). Do not pass it through `wp_kses_post()` / `wp_kses_data()` (risk to `data-wp-*` directives). Pass the **`$block`** instance as the second argument when available.
- After changing block sources, run **`npm run build:production`** (or **`npm run plugin-zip`** for a release zip).

## Block editor: custom post meta UI
- **`register_post_meta()`** with `show_in_rest` is **required** on the PHP side so meta persists through the REST API.
- **User-editable** match/post meta must also be exposed in the editor **with JavaScript** (e.g. `registerPlugin()` + `PluginDocumentSettingPanel` and `useEntityProp( 'postType', …, 'meta' )` from `@wordpress/core-data`) so fields appear in the **post sidebar** (or another clear editor surface). Do not rely on REST registration alone as the “editor UI.”

## Data Stores
- **Extension bucket store** (`Extension_Data_Store`): one record per extension slug (options / site options). Swap with `clanbite_extension_data_store`.
- **Entity stores** (e.g. `Team_Data_Store`): CRUD for domain objects; keep read/write logic out of templates and thin controllers.
- Prefer interface-driven stores with a WordPress default implementation; document new interfaces here and in README.

## Admin Extension Interface
- The unified **Clanbite** React settings screen keeps the active tab in the query string: `admin.php?page=clanbite&tab=<tabId>` (e.g. `general`, `extensions`, `ext-cp_teams`). Implementations should update `?tab=` when the user switches tabs and honor it on load so refresh and shared links reopen the same tab.
- Treat the extensions screen as critical UX: clear statuses, dependency visibility, and safe install toggles.
- Do not allow enabling extensions with unmet requirements.
- Keep admin actions idempotent and validated server-side.

## Documentation Workflow
- Keep `README.md` current whenever architecture, extension APIs, hooks, or setup steps change.
- Document new hooks and extension points in the README at the same time as code changes.
- When touching a public API surface, update PHPDoc in the same PR/commit so IDE hints and static analysis stay accurate.
