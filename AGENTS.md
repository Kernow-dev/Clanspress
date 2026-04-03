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
| Public REST | `Kernowdev\Clanspress\Public_Rest` (`inc/class-public-rest.php`) | Unauthenticated `clanspress/v1/discovery` + `public-team` for cross-site detection and team cards. |
| Cross-site matches | `Kernowdev\Clanspress\Cross_Site_Match_Sync` (`inc/class-cross-site-match-sync.php`) | Ed25519-signed `sync-peer-match` inbound route + outbound push when a challenge is accepted; per-install keys in `clanspress_match_sync_site_keys`, public key at `GET clanspress/v1/site-sync-public-key`. Optional legacy HMAC via `clanspress_cross_site_sync_key` filter only. |
| Team challenges | `Kernowdev\Clanspress\Extensions\Teams\Team_Challenges` (`inc/extensions/teams/class-team-challenges.php`) | Internal `cp_team_challenge` CPT, challenge REST + media upload, notifications (`team_challenge_accept` / `team_challenge_decline`), ties accept flow to `cp_match` + optional events. |
| Loader | `Kernowdev\Clanspress\Extensions\Loader` | Discovers extensions, install state, runs `run()`. |
| Base extension | `Kernowdev\Clanspress\Extensions\Skeleton` | Slug, version, `run()`, installer hooks, blocks/templates helpers, extension data store. |
| Extension data | `Extension_Data_Store`, `Data_Store_WP` | Swappable persistence for `get_data()` / `set_data()`. |
| Settings UI base | `Abstract_Settings` | Option-backed admin pages for an extension. |
| Teams model | `Kernowdev\Clanspress\Extensions\Teams\Team` | In-memory team entity; persist via `Team_Data_Store` (implementations may map to `cp_team`). |
| Teams helpers | `inc/extensions/teams/functions.php` | Theme-safe `clanspress_teams_*()` wrappers. |
| Players helpers | `inc/extensions/players/functions.php` | Theme-safe `clanspress_players_*()` wrappers; `clanspress_player_blocks_resolve_subject_user_id()` aligns player block user resolution with `clanspress_player_profile_context_user_id()`. |
| Matches extension | `Kernowdev\Clanspress\Extensions\Matches` | `cp_match` CPT, REST list/detail, JS blocks + editor sidebar; **requires** `cp_teams`, **not** a Teams sub-extension. |
| Matches helpers | `inc/extensions/matches/functions.php` | `clanspress_matches()` and related theme-safe helpers. |
| Notifications extension | `inc/extensions/notifications/class-extension-notifications.php`, `Kernowdev\Clanspress\Extensions\Notifications` | Official slug `cp_notifications` (optional; one-time default-on migration). **Requires** `cp_players`. `run()` boots `Kernowdev\Clanspress\Extensions\Notification\Notifications_Runtime`. |
| Notifications subsystem | `inc/extensions/notifications/*.php` (namespace `Kernowdev\Clanspress\Extensions\Notification`), `clanspress_*()` in `inc/extensions/notifications/functions.php` (Composer `files` autoload) | Schema, data access, REST, runtime hooks; runtime registers only when the extension is installed; procedural helpers are always loadable for graceful checks (e.g. `function_exists( 'clanspress_notify' )`). |
| Player settings (front) | `build/players/player-settings/view.js` (source: `src/blocks/…`), `CLANSPRESSPLAYERSETTINGS` | Interactivity store; extensions use `actions.runPluginAction` + `data-cp-action-*` attributes instead of inline scripts. |
| Front block templates | `templates/**/*.html` + matching `*.php`, `inc/functions-block-templates.php` (`clanspress_render_block_markup_file()`) | FSE: `register_block_template()` loads `.html` content. Classic: PHP loaders wrap markup with `do_blocks()`. On block themes, `Players::set_plugin_block_template_id_for_site_editor` / `Teams::set_plugin_block_template_id_for_site_editor` (hook `wp` 99) set `$_wp_current_template_id` so **Edit Site** targets `clanspress//…` instead of a theme fallback (e.g. Archive on author URLs). **Virtual template parts** (theme slug + slug): `clanspress-team-profile-header` (`Teams`), `clanspress-player-profile-header` (`Players`). **Groups:** no templates or blocks in core; an add-on registers `cp_group` markup and may supply a virtual part such as `clanspress-group-profile-header`. Core only ships `inc/groups/functions.php` (subpage registry + `clanspress_group_profile_nav_context` / manage URL filters). |
| Events extension | `inc/extensions/events/class-extension-events.php`, `Kernowdev\Clanspress\Extensions\Events` | Official slug `cp_events` (optional; one-time default-on migration). **Requires** `cp_players`. `run()` boots `Kernowdev\Clanspress\Events\Events` (`inc/events/`: `cp_event`, RSVP table, REST, `build/events/` blocks). Uninstaller deletes `cp_event` posts and drops the RSVP table. |
| Events runtime | `inc/events/` (`Events`, `Event_Post_Type`, `Event_Entity_Rest_Controller`, `Event_Permissions`) | Loads only when `cp_events` is installed. `cp_event` is scoped to **teams** or **groups**. **Players do not create events** — only team/group managers (see REST + `event-create-form`). A **player “my events” / calendar** UI is for the **profile owner only** (the logged-in user viewing their own player profile): aggregate visible events from their memberships; do not treat it as a public calendar of another user’s combined schedule unless product requirements explicitly call for that and privacy is addressed. |

### Hook Reference (Core Extension Flow)
**Filters**
- `clanspress_registered_extensions` — Register community / third-party extensions: `(array $slug => Skeleton)`.
- `clanspress_official_registered_extensions` — First-party only; must match whitelist in `Loader::get_official_extensions()`. Whitelist entries may point at classes loaded by **bundled** code or by a **separate first-party plugin** (same validation path; **Core** badge still comes only from `clanspress_core_bundled_extension_slugs`).
- `clanspress_extension_data_store` — Replace `Extension_Data_Store` for a given extension: args `( $data_store, $slug, Skeleton $extension )`.
- `clanspress_extension_{slug}_block_directories` / `clanspress_extension_block_directories` — Block build paths before `register_block_type_from_metadata` when using `Skeleton::register_extension_blocks()` (not used by first-party metadata-collection registration).
- `clanspress_extension_{slug}_templates` / `clanspress_extension_templates` — FSE template definitions for `register_block_template`.
- `clanspress_player_settings_frontend_config` — Filter the array passed to `wp_localize_script` as `CLANSPRESSPLAYERSETTINGS` (keys: `ajax_url`, `nonce`, `rest_url`, `rest_nonce`, and on the player settings screen `settings_url_base`, `settings_initial_nav`, `settings_initial_panel`). Used by the player-settings block for REST-backed extension UI and deep-link routing; see README **Player settings (front-end): plugin actions**.
- `clanspress_can_install_{slug}_extension` — Extra install gates: `(bool $can_install, Skeleton $extension)`.
- `clanspress_validate_installed_extensions` — After admin saves extension list: `( $new_installed, $requested, $available_extensions )`.
- `clanspress_required_extension_slugs` — Slugs that cannot be disabled (default `cp_players` only): `(array $slugs)`.
- `clanspress_core_bundled_extension_slugs` — Slugs shipped inside the main Clanspress plugin (admin **Core** badge); excludes separate add-on plugins: `(array $slugs)`.
- `clanspress_install_notifications_extension_by_default` — If true (default), the loader runs a **one-time** migration that adds `cp_notifications` to installed extensions when missing; set false before first boot to skip: `(bool $enable)`.
- `clanspress_install_events_extension_by_default` — If true (default), the loader runs a **one-time** migration that adds `cp_events` when missing; set false before first boot to skip: `(bool $enable)`.
- `clanspress_notifications_extension_active` — Whether in-site notifications are considered available (installed `cp_notifications`): `(bool $active)`. Theme and third-party code should use `clanspress_notifications_extension_active()` or handle `WP_Error` from `clanspress_notify()` (code `notifications_inactive`).
- `clanspress_events_extension_active` — Whether scheduled events (`cp_event`, RSVP REST, event blocks) are available (installed `cp_events`): `(bool $active)`. Use `clanspress_events_extension_active()` before assuming event APIs exist.
- `clanspress_load_players_directory_template` — Resolved PHP template path for `/players/`: `(string $path, string $located_theme_path)`.
- `clanspress_players_directory_per_page` — User-query page size on the players directory shortcode: `(int $per_page)`.
- `clanspress_redirect_author_archive_to_players_url` — 301 target for `/author/…` and `?author=` → `/players/…`: `(string $target, \WP_User $user)`.
- `clanspress_team_challenge_button_visible` — Show the **Team challenge** block UI: `(bool $visible, int $team_id, \WP_Block $block)`.
- `clanspress_team_challenge_notify_user_ids` — Recipients for new challenge notifications: `(array $user_ids, int $team_id)`.
- `clanspress_cross_site_sync_key` — Legacy shared HMAC secret (optional): `(string $key)` non-empty forces legacy `timestamp:hmac` headers instead of Ed25519 `v1:…` (for old integrations only).
- `clanspress_cross_site_sync_outbound_payload` — Mutate signed body before push to peer: `(array $body, int $challenge_id, int $match_id, int $challenged_team_id, array $snapshot)`.
- `clanspress_cross_site_sync_incoming_payload` — Mutate decoded JSON after signature check: `(array $body, string $source_host)`.
- `clanspress_cross_site_sync_verify_source` — Reject peer by host after valid signature: `(bool $allow, string $source_host, array $body)`.
- `clanspress_public_team_response` — Public team card for `public-team` REST: `(array $data, \WP_Post $team_post)`.
- `clanspress_user_managed_team_ids` — Teams a user may manage on the front end: `(array $ids, int $user_id, Teams $extension)`.
- `clanspress_groups_manage_url` — Group settings URL for profile nav: `(string $url, int $group_id)`.
- `clanspress_groups_user_can_manage` — Whether the user may manage the group (events REST, forms): `(bool $can, int $group_id, int $user_id)`. The plugin that owns `cp_group` should filter this for group admins/editors.
- `clanspress_groups_user_is_member` — Whether the user counts as a member for members-only event visibility: `(bool $is_member, int $group_id, int $user_id)`.
- `clanspress_group_subpages` — Group profile tab registry: `(array $registry)`.
- `clanspress_group_profile_nav_context` — Virtual group profile context when not on singular `cp_group`: `(array|null $context)`.
- `clanspress_group_events_create_url` — “Add event” URL from group calendar block: `(string $url, int $group_id)`.
- `clanspress_notification_poll_blocking_wait` — Allow `/notifications/poll` to sleep in a loop until timeout or new items: `(bool $blocking, int $user_id)`. Default `true`; set `false` for one-shot polls (less PHP worker hold time).
- `clanspress_group_event_member_user_ids` — User IDs targeted by group-scoped event roster outreach (`notify` / `rsvp_tentative` on `POST`/`PUT` `event-posts`): `(array $user_ids, int $group_id)`. Core default empty; group plugins should populate.
- `clanspress_event_member_outreach_user_ids` — Final recipient list after core resolves team roster or group filter: `(array $user_ids, int $event_id, string $scope, int $team_id, int $group_id, string $mode)`.

**Actions**
- `clanspress_team_challenge_created` — `(int $challenge_id, int $challenged_team_id)`.
- `clanspress_team_challenge_accepted` — `(int $challenge_id, int $match_id, int $challenged_team_id)`.
- `clanspress_cross_site_sync_push_succeeded` — `(int $challenge_id, int $local_match_id, int $peer_match_id, string $peer_origin, array $body)`.
- `clanspress_cross_site_sync_push_failed` — `(\WP_Error $error, string $url, array $body)`.
- `clanspress_cross_site_sync_push_rejected` — `(int $http_code, array $data, string $url, array $body)`.
- `clanspress_cross_site_sync_incoming_created` — `(int $match_id, array $body, int $home_team_id)` after peer creates a local `cp_match`.
- `clanspress_event_member_outreach_completed` — After `member_outreach` runs for a `cp_event`: `(int $event_id, string $mode, array $result)` with `notified`, `rsvp_set`, `skipped`.
- `clanspress_extension_installer_{slug}` — Dynamic `{slug}`: extension install hook.
- `clanspress_extension_uninstaller_{slug}`
- `clanspress_extension_updater_{slug}`
- `clanspress_extension_run_{slug}` — Main runtime hook: register CPTs, routes, blocks, etc.

Extension-specific hooks (e.g. teams, players) belong in PHPDoc next to each `apply_filters` / `do_action` in the relevant class.

## Blocks (Gutenberg)
- **Do not** register Clanspress blocks with `register_block_type()` in PHP (no PHP-only `render_callback` / attribute definitions) **unless the maintainer explicitly asks for that approach**.
- Blocks are **real JS blocks**: `block.json` with `editorScript` (and optional `style` / `editorStyle`), **built** with `@wordpress/scripts` from **`src/blocks/`** into the plugin root **`build/`** (matches / players / teams subtrees). Register with **`Skeleton::register_extension_block_types_from_metadata_collection( 'build/…' )`** (WordPress 6.8+ metadata collection API; fallbacks in `Skeleton` for older core). Use **`Skeleton::register_extension_blocks()`** only for extra third-party-style block directories.
- **Server output** for dynamic blocks belongs in **`render.php`** next to the block source under `src/blocks/…`, copied into `build/` by `--webpack-copy-php`, not in ad-hoc PHP callbacks in the extension class.
- **`get_block_wrapper_attributes()`** returns an already-escaped attribute string: echo it directly (with a `phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` and a short rationale). Do not pass it through `wp_kses_post()` / `wp_kses_data()` (risk to `data-wp-*` directives). Pass the **`$block`** instance as the second argument when available.
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
