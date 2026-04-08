# Clanspress Agent Guidance

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
| Core | `Kernowdev\Clanspress\Main` (`clanspress.php`) | Bootstrap, i18n, maintenance, loads `Extension_Loader`. Registers core blocks (e.g. `clanspress/visibility-container` from `build/core/visibility-container`). Visibility rules: `Kernowdev\Clanspress\Blocks\Visibility_Container` (`inc/Blocks/Visibility_Container.php`); thin `clanspress_visibility_container_*()` wrappers in `inc/visibility-container.php`. Role labels for the block editor are localized on the block’s own `editorScript` handle (not `wp-block-editor`). |
| Public REST | `Kernowdev\Clanspress\Public_Rest` (`inc/class-public-rest.php`) | Unauthenticated `clanspress/v1/discovery` + `public-team` for cross-site detection and team cards. |
| Cross-site matches | `Kernowdev\Clanspress\Cross_Site_Match_Sync` (`inc/class-cross-site-match-sync.php`) | Ed25519-signed `sync-peer-match` inbound route + outbound push when a challenge is accepted; per-install keys in `clanspress_match_sync_site_keys`, public key at `GET clanspress/v1/site-sync-public-key`. Optional legacy HMAC via `clanspress_cross_site_sync_key` filter only. |
| Team challenges | `Kernowdev\Clanspress\Extensions\Teams\Team_Challenges` (`inc/extensions/teams/class-team-challenges.php`) | Internal `cp_team_challenge` CPT, challenge REST + media upload, notifications (`team_challenge_accept` / `team_challenge_decline`), ties accept flow to `cp_match` + optional events. |
| Loader | `Kernowdev\Clanspress\Extensions\Loader` | Discovers extensions, install state, runs `run()`. |
| Base extension | `Kernowdev\Clanspress\Extensions\Skeleton` | Slug, version, `run()`, installer hooks, blocks/templates helpers, extension data store. |
| Extension data | `Extension_Data_Store`, `Data_Store_WP` | Swappable persistence for `get_data()` / `set_data()`. |
| Settings UI base | `Abstract_Settings` | Option-backed admin pages for an extension. |
| Admin (unified UI) | `Kernowdev\Clanspress\Admin\Settings`, `General_Settings`, `Groups_Settings`, `Admin_Rest` (`inc/admin/`) | Clanspress → Settings React app. **`Groups_Settings`** (`clanspress_groups_settings`) registers the **Groups** tab for group profile integrations (not an extension slug). |
| Teams model | `Kernowdev\Clanspress\Extensions\Teams\Team` | In-memory team entity; persist via `Team_Data_Store` (implementations may map to `cp_team`). |
| Teams helpers | `inc/extensions/teams/functions.php` | Theme-safe `clanspress_teams_*()` wrappers. Team avatars: `clanspress_teams_get_display_team_avatar( $team_id, $suppress, $size, $context, $avatar_preset )` with presets mapped to **Teams → Team avatar image sizes**. |
| Players helpers | `inc/extensions/players/functions.php` | Theme-safe `clanspress_players_*()` wrappers; `clanspress_player_blocks_resolve_subject_user_id()` aligns player block user resolution with `clanspress_player_profile_context_user_id()`. Player avatars: resolve URLs with `clanspress_players_get_display_avatar( $user_id, $suppress, $size, $context, $avatar_preset )` (`large` / `medium` / `small` presets map to **Players → Player avatar image sizes**); build `<img>` with `clanspress_players_get_player_avatar_img_html()`; wrap with `clanspress_players_apply_player_avatar_display_markup()` (see Hook Reference). |
| Matches extension | `Kernowdev\Clanspress\Extensions\Matches` | `cp_match` CPT, REST list/detail, JS blocks + editor sidebar; **requires** `cp_teams`, **not** a Teams sub-extension. Optional team profile tab `/teams/{slug}/matches/` when `subpage_team` is on in `clanspress_matches_settings`. |
| Matches helpers | `inc/extensions/matches/functions.php` | `clanspress_matches()` and related theme-safe helpers. |
| Notifications extension | `inc/extensions/notifications/class-extension-notifications.php`, `Kernowdev\Clanspress\Extensions\Notifications` | Official slug `cp_notifications` (optional; one-time default-on migration). **Requires** `cp_players`. `run()` registers `Extensions\Notifications\Admin` settings (`clanspress_notifications_settings`) then boots `Kernowdev\Clanspress\Extensions\Notification\Notifications_Runtime`. |
| Notifications subsystem | `inc/extensions/notifications/*.php` (namespace `Kernowdev\Clanspress\Extensions\Notification`), `clanspress_*()` in `inc/extensions/notifications/functions.php` (Composer `files` autoload) | Schema, data access, REST, runtime hooks; runtime registers only when the extension is installed; procedural helpers are always loadable for graceful checks (e.g. `function_exists( 'clanspress_notify' )`). Bell long-polling: `clanspress_notifications_poll_long_polling_enabled()` (backed by `clanspress_notifications_settings.poll_long_polling`). |
| Player settings (front) | `build/players/player-settings/view.js` (source: `src/blocks/…`), `CLANSPRESSPLAYERSETTINGS` | Interactivity store; extensions use `actions.runPluginAction` + `data-cp-action-*` attributes instead of inline scripts. |
| Profile subpages (shared) | `inc/profile-subpages.php` | `clanspress_profile_subpage_registry_map()`, `clanspress_register_profile_subpage()`, `clanspress_get_profile_subpages()`, `clanspress_profile_subpages_nav_enabled()` (defaults true; extensions gate their own tabs via settings), `clanspress_profile_subpages_visible_for_nav()`. Core contexts: `player`, `team`, `group`. Wrappers: `clanspress_register_player_subpage()` / `clanspress_get_player_subpages()` (and team/group equivalents in `inc/extensions/teams/functions.php`, `inc/groups/functions.php`). **Events** profile tabs: `events_profile_subpage` on **Players** / **Teams** / **Groups** admin tabs; runtime checks `clanspress_events_subpage_*_enabled()`. **Notifications** player tab: `clanspress_notifications_subpage_player_enabled()`. **Matches** team tab: `clanspress_matches_subpage_team_enabled()`. |
| Front block templates | `templates/**/*.html` + matching `*.php`, `inc/functions-block-templates.php` (`clanspress_render_block_markup_file()`) | FSE: `register_block_template()` loads `.html` content. Classic: PHP loaders wrap markup with `do_blocks()`. On block themes, `Players::set_plugin_block_template_id_for_site_editor` / `Teams::set_plugin_block_template_id_for_site_editor` (hook `wp` 99) set `$_wp_current_template_id` so **Edit Site** targets `clanspress//…` instead of a theme fallback (e.g. Archive on author URLs). **Virtual template parts** (theme slug + slug): `clanspress-team-profile-header` (`Teams`), `clanspress-player-profile-header` (`Players`). **Groups:** no templates or blocks in core; an add-on registers `cp_group` markup and may supply a virtual part such as `clanspress-group-profile-header`. Core ships `inc/groups/functions.php` for group profile helpers (`clanspress_group_profile_nav_context`, manage URL filters). |
| Events extension | `inc/extensions/events/class-extension-events.php`, `Kernowdev\Clanspress\Extensions\Events` | Official slug `cp_events` (optional; one-time default-on migration). **Requires** `cp_players`. `run()` boots `Kernowdev\Clanspress\Events\Events` only (no extension settings tab). Profile Events subpages are toggled from **Players**, **Teams**, and **Groups** settings (`clanspress_events_subpage_*_enabled()` in `inc/extensions/events/functions.php`). Uninstaller deletes `cp_event` posts and drops the RSVP table. |
| Events runtime | `inc/events/` (`Events`, `Event_Post_Type`, `Event_Entity_Rest_Controller`, `Event_Permissions`) | Loads only when `cp_events` is installed. `cp_event` is scoped to **teams** or **groups**. **Players do not create events** — only team/group managers (see REST + `event-create-form`). A **player “my events” / calendar** UI is for the **profile owner only** (the logged-in user viewing their own player profile): aggregate visible events from their memberships; do not treat it as a public calendar of another user’s combined schedule unless product requirements explicitly call for that and privacy is addressed. |
| Forums extension (companion plugin) | `clanspress-forums` → `Kernowdev\ClanspressForums\Extension\Forums` | Official slug `cp_forums` (separate plugin; whitelisted like Social Kit). **Requires** `cp_players`. Custom tables (`Schema`), REST `clanspress-forums/v1`, front blocks `clanspress-forums/forums-index`, `clanspress-forums/forum-topics`, `clanspress-forums/forum-topic` (shared Interactivity store `clanspress-forums/board`), plugin FSE templates `clanspress//forums-index`, `clanspress//forum-topics`, `clanspress//forum-topic` + `Forums\Template_Router` on the router page, player settings tab for **forum signature** (`cp_forum_signature` user meta), topic follow + `clanspress_notify( …, 'forum_reply', … )` when **Notifications** is enabled. Site cap: `manage_clanspress_forums`. |

### Hook Reference (Core Extension Flow)
**Filters**
- `clanspress_registered_extensions` — Register community / third-party extensions: `(array $slug => Skeleton)`.
- `clanspress_official_registered_extensions` — First-party only; must match whitelist in `Loader::get_official_extensions()`. Whitelist entries may point at classes loaded by **bundled** code or by a **separate first-party plugin** (same validation path; **Core** badge still comes only from `clanspress_core_bundled_extension_slugs`).
- `clanspress_extension_data_store` — Replace `Extension_Data_Store` for a given extension: args `( $data_store, $slug, Skeleton $extension )`.
- `clanspress_extension_{slug}_block_directories` / `clanspress_extension_block_directories` — Block build paths before `register_block_type_from_metadata` when using `Skeleton::register_extension_blocks()` (not used by first-party metadata-collection registration).
- `clanspress_extension_{slug}_templates` / `clanspress_extension_templates` — FSE template definitions for `register_block_template`.
- `clanspress_player_settings_frontend_config` — Filter the array passed to `wp_localize_script` as `CLANSPRESSPLAYERSETTINGS` (keys: `ajax_url`, `nonce`, `rest_url`, `rest_nonce`, and on the player settings screen `settings_url_base`, `settings_initial_nav`, `settings_initial_panel`). Used by the player-settings block for REST-backed extension UI and deep-link routing; see README **Player settings (front-end): plugin actions**.
- `clanspress_should_enqueue_player_settings_frontend_assets` — Gate whether `CLANSPRESSPLAYERSETTINGS` is printed on a front request: `(bool $enqueue)` after core heuristics (player settings route, logged-in author profile, singular posts with player-settings / avatar / cover blocks). Return true to force enqueue when custom templates need the payload.
- `clanspress_can_install_{slug}_extension` — Extra install gates: `(bool $can_install, Skeleton $extension)`.
- `clanspress_validate_installed_extensions` — After admin saves extension list: `( $new_installed, $requested, $available_extensions )`.
- `clanspress_visibility_container_should_show` — Whether to render **Visibility container** inner blocks for the current visitor: `(bool $visible, array $attributes, \WP_Block|null $block)`.
- `clanspress_visibility_container_editor_script_handle` — Override the script handle used to pass role labels into the visibility-container block editor: `(string $handle)` (default: registered `editorScript` for `clanspress/visibility-container`).
- `clanspress_required_extension_slugs` — Slugs that cannot be disabled (default `cp_players` only): `(array $slugs)`.
- `clanspress_core_bundled_extension_slugs` — Slugs shipped inside the main Clanspress plugin (admin **Core** badge); excludes separate add-on plugins: `(array $slugs)`.
- `clanspress_install_notifications_extension_by_default` — If true (default), the loader runs a **one-time** migration that adds `cp_notifications` to installed extensions when missing; set false before first boot to skip: `(bool $enable)`.
- `clanspress_install_events_extension_by_default` — If true (default), the loader runs a **one-time** migration that adds `cp_events` when missing; set false before first boot to skip: `(bool $enable)`.
- `clanspress_notifications_extension_active` — Whether in-site notifications are considered available (installed `cp_notifications`): `(bool $active)`. Theme and third-party code should use `clanspress_notifications_extension_active()` or handle `WP_Error` from `clanspress_notify()` (code `notifications_inactive`).
- `clanspress_events_extension_active` — Whether scheduled events (`cp_event`, RSVP REST, event blocks) are available (installed `cp_events`): `(bool $active)`. Use `clanspress_events_extension_active()` before assuming event APIs exist.
- `clanspress_load_players_directory_template` — Resolved PHP template path for `/players/`: `(string $path, string $located_theme_path)`.
- `clanspress_players_directory_per_page` — User-query page size on the players directory shortcode: `(int $per_page)`.
- `clanspress_redirect_author_archive_to_players_url` — 301 target for `/author/…` and `?author=` → `/players/…`: `(string $target, \WP_User $user)`.
- `clanspress_players_social_profile_field_definitions` — Social fields on **Profile → Social Networks** and matching user meta keys: `(array $definitions)` map of slug → `label` / `placeholder`.
- `clanspress_players_get_display_social` — Single social field after user meta: `(string $value, string $slug, int $player_id)`.
- `clanspress_player_settings_update_social_profile_value` — Mutate or reject a value before save (return `WP_Error` to block): `(string|WP_Error $value, string $slug, int $user_id)`.
- `clanspress_players_get_display_avatar` — Player avatar image URL after attachment/default resolution: `(string $url, int $user_id, string|array $size, string $context, string $avatar_preset)`. `$avatar_preset` is `large`, `medium`, `small`, or empty when an explicit `$size` was used (presets map to **Players → Player avatar image sizes**). Use `$context` to vary behaviour by surface (`player_avatar_block`, `user_nav`, `notifications`, `profile_settings_rest`, etc.). Pair with `clanspress_players_get_display_avatar_id` for attachment-based logic.
- `clanspress_players_resolve_player_avatar_image_size` — Maps preset to registered size slug before URL resolution: `(string $size, string $preset, string $raw, string $fallback)`.
- `clanspress_players_image_size_choices_for_settings` — Slug → label map for Players/Teams avatar size dropdowns: `(array $choices)`.
- `clanspress_teams_get_display_team_avatar` — Team avatar URL: `(string $url, int $team_id, string|array $size, string $context, string $avatar_preset)`.
- `clanspress_teams_resolve_team_avatar_image_size` — Maps team preset to size slug: `(string $size, string $preset, string $raw, string $fallback)`.
- `clanspress_players_player_avatar_img_attributes` — Attribute map for the avatar `<img>` before the tag is built: `(array $attr, int $user_id, array $args, string $url)`.
- `clanspress_players_player_avatar_img_html` — Final `<img>` fragment from `clanspress_players_get_player_avatar_img_html()`: `(string $html, int $user_id, array $args, string $url)`.
- `clanspress_players_player_avatar_display_markup` — Inner avatar fragment after core builds image or empty-state markup (before profile link wrapping in blocks): `(string $inner, int $user_id, array $args)`.
- `clanspress_players_player_avatar_empty_img_markup` — Empty-state upload placeholder `<img>` in the player avatar block: `(string $html, int $user_id, array $args)`.
- `clanspress_players_player_avatar_placeholder_markup` — Text placeholder when the user has no avatar and inline upload is off: `(string $html, int $user_id, array $args)`.
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
- `clanspress_profile_subpage_registry_map` — Register extra profile contexts or override config (`globals_key`, `filter`, `template_prefix`): `(array $map)`.
- `clanspress_profile_subpage_nav_visible` — Whether a tab appears in profile nav after the capability check: `(bool $visible, string $context, string $slug, int $object_id, array $config)`.
- `clanspress_profile_subpages_nav_enabled` — Optional gate for core contexts `player` / `team` / `group` (default true; prefer extension settings for individual tabs): `(bool $enabled, string $context)`.
- `clanspress_profile_subpages_nav_enabled_for_unknown_context` — Same for custom contexts not in the map: `(bool $enabled, string $context)`.
- `clanspress_group_profile_nav_context` — Virtual group profile context when not on singular `cp_group`: `(array|null $context)`.
- `clanspress_group_events_create_url` — “Add event” URL from group calendar block: `(string $url, int $group_id)`.
- `clanspress_notification_poll_blocking_wait` — Allow `/notifications/poll` to sleep in a loop until timeout or new items: `(bool $blocking, int $user_id)`. The value passed in is the Notifications setting **Use long-polling** (`clanspress_notifications_settings.poll_long_polling`); filter to override per site or user.
- `clanspress_group_event_member_user_ids` — User IDs targeted by group-scoped event roster outreach (`notify` / `rsvp_tentative` on `POST`/`PUT` `event-posts`): `(array $user_ids, int $group_id)`. Core default empty; group plugins should populate.
- `clanspress_event_member_outreach_user_ids` — Final recipient list after core resolves team roster or group filter: `(array $user_ids, int $event_id, string $scope, int $team_id, int $group_id, string $mode)`.
- `clanspress_forums_topic_public_url` — Notification/share URL for a topic (and optional reply): `(string $url, string $forum_slug, string $topic_slug, int $reply_id)`.
- `clanspress_forums_reply_notification_recipients` — User IDs to notify on a new reply: `(array $user_ids, int $topic_id, int $reply_id, int $actor_id)`.
- `clanspress_forums_user_can_post_topic` — After core “who can post topics” policy and forum read checks (staff still bypass in core): `(bool $can, int $viewer_id, object $forum, string $policy)`. Chained before `clanspress_forums_user_can_create_topic` in REST and in serialized forum `viewer_can_post_topic`.
- `clanspress_forums_user_can_create_topic` — Final gate for creating a topic: `(bool $can, int $forum_id, int $user_id)`.
- `clanspress_forums_user_can_reply` — Whether the user may reply: `(bool $can, int $topic_id, int $user_id, object $topic_row)`.
- `clanspress_forums_block_initial_forum_slug` / `clanspress_forums_block_initial_topic_slug` / `clanspress_forums_block_initial_reply_id` — Deep-link defaults for the forums block (with query vars `cp_forums_*`).

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
- `clanspress_forums_init` — Companion plugin loaded and Clanspress available: `( \Kernowdev\ClanspressForums\Plugin $plugin )`.
- `clanspress_forums_rest_api_init` — After forums REST routes register: `( \Kernowdev\ClanspressForums\Extension\Forums $extension )`.
- `clanspress_before_social_networks_fields` — Before **Profile → Social Networks** markup on player settings: `(int $user_id)`.
- `clanspress_after_social_networks_fields` — After that section: `(int $user_id)`.
- `clanspress_player_avatar_img_before` — Before building the player avatar `<img>` (URL resolved): `(int $user_id, array $args, string $url)`.
- `clanspress_player_avatar_img_after` — After the `<img>` string is built: `(int $user_id, array $args, string $url, string $html)`.

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
