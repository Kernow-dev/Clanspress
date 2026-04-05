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

### Public REST, team challenges, and cross-site match sync

These routes are **unauthenticated** (defense in depth: nonces, rate limits, and/or HMAC as documented). They exist so other Clanspress installs and the **Team challenge** block can interoperate.

| Method | Route | Purpose |
|--------|--------|---------|
| `GET` | `/wp-json/clanspress/v1/discovery` | Returns `{ clanspress, name, version }` and, when PHP sodium is available, `match_sync` hints for cross-site match signing. |
| `GET` | `/wp-json/clanspress/v1/site-sync-public-key` | Returns `{ clanspress, algorithm: ed25519, public_key }` (base64) so peer installs can verify signed `sync-peer-match` requests. |
| `GET` | `/wp-json/clanspress/v1/public-team` | Query args `slug` or `url` — public metadata for a published `cp_team` (title, permalink, logo, motto, country, short description). |
| `GET` | `/wp-json/clanspress/v1/challenge-remote-team` | Same-site proxy: `team_id`, `url`, `challenge_nonce` — server fetches discovery + `public-team` on the remote host (avoids browser CORS). |
| `POST` | `/wp-json/clanspress/v1/team-challenges` | JSON body: `team_id`, `challenge_nonce`, contact fields, optional `opponent_team_url`, `challenger_team_id`, `challenger_team_name`, `challenger_team_logo_id`, `proposed_scheduled_at`, `message`. Creates `cp_team_challenge` and notifies challenged team admins (`team_challenge` + accept/decline handlers). Requires **Matches** + **Teams**. |
| `POST` | `/wp-json/clanspress/v1/team-challenge-media` | `multipart/form-data`: `team_id`, `challenge_nonce`, `file` — optional logo (image, max 2MB) for manual challengers; returns `{ id, url }` attachment reference for `challenger_team_logo_id`. |
| `POST` | `/wp-json/clanspress/v1/sync-peer-match` | Signed JSON body (see below). Creates a **mirror** `cp_match` on the **challenger’s** site when the challenged site accepts a remote Clanspress challenge. |

**Cross-site mirror (two-way listings):** Each install generates an **Ed25519** keypair (PHP **sodium** extension required) stored in the `clanspress_match_sync_site_keys` option. When a challenge is accepted, if the snapshot came from another Clanspress site (`source: remote`, with `origin` + `remoteTeamId` from `public-team`), the challenged site POSTs to `{origin}/wp-json/clanspress/v1/sync-peer-match` with `X-Clanspress-Sync: v1:{timestamp}:{base64url_signature}` (detached Ed25519 over `{timestamp}\n{json body}`). The receiving site fetches the sender’s public key from `{source_site}wp-json/clanspress/v1/site-sync-public-key` (HTTPS), verifies the signature, then creates the mirror match. **No shared manual secret** — only Clanspress installs that expose the public-key route and accept verified requests participate. For legacy integrations, the `clanspress_cross_site_sync_key` filter can force the older `timestamp:hmac` header using a shared secret. Without sodium and without that filter, mirror push is skipped (the local match on the challenged site still works).

**Filters / actions:** `clanspress_team_challenge_button_visible`, `clanspress_team_challenge_notify_user_ids`, `clanspress_team_challenge_created`, `clanspress_team_challenge_accepted`, `clanspress_cross_site_sync_key`, `clanspress_cross_site_sync_outbound_payload`, `clanspress_cross_site_sync_incoming_payload`, `clanspress_cross_site_sync_verify_source`, `clanspress_cross_site_sync_push_succeeded`, `clanspress_cross_site_sync_push_failed`, `clanspress_cross_site_sync_push_rejected`, `clanspress_cross_site_sync_incoming_created`. See `AGENTS.md` for the hook table.

## Extension System
Extensions are registered through filter-based discovery and loaded by the extension loader.

### Core Rules
- Every extension has a unique slug and semantic version.
- Extensions may declare dependencies (`requires`) and parent-child relationships (`parent_slug`).
- Extensions with unmet requirements must not be enabled.
- Lifecycle methods are available for installer, updater, runtime boot, and uninstaller flows.
- **Required** first-party slug (`cp_players` by default) stays enabled; third-party code may adjust the list via the `clanspress_required_extension_slugs` filter (use sparingly). **Notifications** (`cp_notifications`) and **Events** (`cp_events`) are official and enabled once by default via one-time loader migrations when missing; either can be disabled from **Extensions** like Teams/Matches.
- **Official** extensions are whitelisted in `Loader::get_official_extensions()` and register on `clanspress_official_registered_extensions` with an exact class-name match. Bundled extensions and separate first-party companion plugins both use that path; see **First-party extensions in separate plugins** below (e.g. Social Kit).
- The admin **Core** badge marks only extensions whose code ships **inside the main Clanspress plugin** (`clanspress_core_bundled_extension_slugs`: `cp_players`, `cp_notifications`, `cp_teams`, `cp_matches`, `cp_events` by default). An extension can be **Official** without being **Core** (external first-party plugin).
- Community and third-party extensions register on `clanspress_registered_extensions` instead. Adjust the bundled list with `clanspress_core_bundled_extension_slugs` if needed.

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

### Global Styles (`theme.json`) and Clanspress blocks

First-party blocks declare `supports` (spacing, color, typography, border, shadow, and link color where relevant) and `selectors` in each block’s `block.json`, so **Appearance → Editor → Styles** can target the right DOM nodes. Dynamic blocks pass the `WP_Block` instance into `get_block_wrapper_attributes( …, $block )` on the front end so those styles apply there too.

To set **defaults for every Clanspress block** from your theme, merge the `styles.blocks` entries into your theme’s `theme.json` under the existing `styles` key (add `styles.blocks` if it is missing). If a block name is already present, merge objects by hand or replace with your overrides.

Nested keys under each block’s `color` object (`text`, `background`, `link`) mirror that block’s `selectors.color` map in its `block.json`—core maps them onto the inner DOM nodes those selectors describe. Where `selectors` includes `typography` or `filter.duotone`, the scaffold shows matching `styles.blocks[…].typography` / `filter` entries (see `clanspress/player-cover` for `filter.duotone`). Blocks that declare `selectors.border`, or that support spacing, shadows, and other features in `block.json`, accept the usual keys under `styles.blocks[ blockName ]` as in the [Styles reference](https://developer.wordpress.org/themes/global-settings-and-styles/styles/styles-reference/).

The **copy-paste file** below is valid `theme.json`. It adds a small **`cp-scaffold-*`** palette, font-size, and duotone presets under `settings` so every `var:preset|color|…`, `var:preset|font-size|…`, and `var:preset|duotone|…` value resolves. Merge into an existing theme by combining those presets with yours (rename slugs if they collide) and merging `styles.blocks`, or use the file as a new block theme starter and swap tokens for your design.

One-off override example (uses CSS variables instead of `var:preset|…`):

```json
"clanspress/team-name": {
	"color": { "text": "var(--wp--preset--color--contrast)" },
	"typography": { "fontSize": "var(--wp--preset--font-size--x-large)" },
	"spacing": { "margin": { "bottom": "var(--wp--preset--spacing--40)" } }
}
```

**Copy-paste scaffold** (full `theme.json`; trim or merge `settings` / `styles` as needed):

```json
{
	"$schema": "https://schemas.wp.org/trunk/theme.json",
	"version": 2,
	"settings": {
		"appearanceTools": true,
		"color": {
			"palette": [
				{
					"color": "#1e1e1e",
					"name": "Clanspress scaffold text",
					"slug": "cp-scaffold-text"
				},
				{
					"color": "#ffffff",
					"name": "Clanspress scaffold background",
					"slug": "cp-scaffold-bg"
				},
				{
					"color": "#3858e9",
					"name": "Clanspress scaffold accent (links)",
					"slug": "cp-scaffold-accent"
				}
			],
			"duotone": [
				{
					"colors": [
						"#1e1e1e",
						"#ffffff"
					],
					"name": "Clanspress scaffold grayscale",
					"slug": "cp-scaffold-grayscale"
				}
			]
		},
		"typography": {
			"fontSizes": [
				{
					"name": "Clanspress scaffold medium",
					"slug": "cp-scaffold-m",
					"size": "1rem"
				}
			]
		}
	},
	"styles": {
		"blocks": {
			"clanspress/event-calendar": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/event-create-form": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/event-detail": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/event-list": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/event-rsvp": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/match-card": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg",
					"link": "var:preset|color|cp-scaffold-accent"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/match-list": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg",
					"link": "var:preset|color|cp-scaffold-accent"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/notification-bell": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/player-avatar": {
				"color": {
					"background": "var:preset|color|cp-scaffold-bg"
				}
			},
			"clanspress/player-country": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/player-cover": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				},
				"filter": {
					"duotone": "var:preset|duotone|cp-scaffold-grayscale"
				}
			},
			"clanspress/player-display-name": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg",
					"link": "var:preset|color|cp-scaffold-accent"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/player-profile-nav": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg",
					"link": "var:preset|color|cp-scaffold-accent"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/player-query": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/player-settings": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/player-settings-link": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/player-template": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg",
					"link": "var:preset|color|cp-scaffold-accent"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/team-avatar": {
				"color": {
					"background": "var:preset|color|cp-scaffold-bg"
				}
			},
			"clanspress/team-card": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/team-challenge-button": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/team-code": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/team-country": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/team-cover": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/team-create-form": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/team-description": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/team-draws": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/team-losses": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/team-manage-link": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/team-members-count": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/team-motto": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/team-name": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg",
					"link": "var:preset|color|cp-scaffold-accent"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/team-profile-nav": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg",
					"link": "var:preset|color|cp-scaffold-accent"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/team-wins": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			},
			"clanspress/user-nav": {
				"color": {
					"text": "var:preset|color|cp-scaffold-text",
					"background": "var:preset|color|cp-scaffold-bg",
					"link": "var:preset|color|cp-scaffold-accent"
				},
				"typography": {
					"fontSize": "var:preset|font-size|cp-scaffold-m"
				}
			}
		}
	}
}
```

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

### Player settings (front-end): plugin actions

The **Player settings** block (`clanspress/player-settings`) uses the Interactivity API store `clanspress-player-settings`. Core exposes a generic **`actions.runPluginAction`** handler so extensions can add buttons or links inside player settings panels **without** inline scripts: the click runs `fetch()` against your URL, sends the WordPress REST nonce, and shows the block’s existing success/error toast.

**Localized config** is attached as `window.CLANSPRESSPLAYERSETTINGS` when the Players extension enqueues scripts. Default keys:

| Key | Purpose |
|-----|---------|
| `ajax_url` | Admin AJAX URL (legacy profile save). |
| `nonce` | Nonce for `clanspress_profile_settings_save_action`. |
| `rest_url` | Site REST root (for building route URLs in PHP). |
| `rest_nonce` | Nonce for `wp_rest` (sent as `X-WP-Nonce` on plugin actions). |
| `settings_url_base` | (Player settings page only.) Trailing-slash base URL, e.g. `https://example.com/players/settings/`. |
| `settings_initial_nav` | (Player settings page only.) Resolved parent tab slug (`profile`, `account`, `teams`, …). |
| `settings_initial_panel` | (Player settings page only.) Resolved panel slug (`profile-info`, `account-info`, …). |

**Profile save AJAX (`clanspress_save_player_settings`).** POST to `ajax_url` with `action` set to `clanspress_save_player_settings`, the nonce in `nonce` (same value as localized `nonce`, verified with `clanspress_profile_settings_save_action`), plus the form fields your UI collects (including optional multipart `profile_avatar` / `profile_cover` when uploading inline). Domain saving is delegated to `clanspress_save_player_settings` and related filters; the handler itself does not persist fields.

On **success**, the JSON body matches `wp_send_json_success()`: `success` is true and `data` is an object with at least:

| Key | Purpose |
|-----|---------|
| `avatarUrl` | Resolved display URL for the logged-in user’s avatar (attachments + defaults, after `clanspress_players_get_display_avatar` filters). |
| `coverUrl` | Resolved display URL for the logged-in user’s cover (`clanspress_players_get_display_cover`). |

The **player-avatar** and **player-cover** interactivity modules use these values after save to point `<img>` / background previews at the server URL and revoke temporary object URLs. Custom front-end code calling the same action should do the same if you show blob previews.

On **failure**, `wp_send_json_error()` returns `success: false` and `data` typically includes an `errors` object from `clanspress_save_player_settings_errors`.

Extend or override the object with filter **`clanspress_player_settings_frontend_config`** (same array shape as above).

By default, that script is **only** enqueued on the player settings route, on **logged-in** author/player profile views, and on singular content that contains the player-settings / avatar / cover blocks (to avoid nonce + inline script work on unrelated front pages). Use filter **`clanspress_should_enqueue_player_settings_frontend_assets`** with `(bool $enqueue)` to force or extend that behavior for custom templates.

**Deep links:** Each tab and sub-page has a canonical URL: `/players/settings/{nav}/{panel}/` (e.g. `/players/settings/account/account-info/`). `/players/settings/{nav}/` redirects to that nav’s first panel. Invalid slugs redirect to a valid default. The block updates the address bar when you switch tabs (history `pushState` / `replaceState`). After adding or changing rewrite rules, save **Settings → Permalinks** once (or flush rewrite rules) so WordPress routes new paths.

**Markup contract** (on the clicked element, e.g. `<button type="button">`):

| Attribute | Required | Description |
|-----------|----------|-------------|
| `data-wp-on--click="actions.runPluginAction"` | Yes | Wires the Interactivity action. |
| `data-cp-action-url` | Yes | Full request URL (typically `rest_url( 'your-namespace/v1/...' )` from PHP). |
| `data-cp-action-method` | No | HTTP method; default `POST`. `GET` / `HEAD` omit body. |
| `data-cp-action-body` | No | JSON string for the request body (parsed client-side; empty object if omitted). |
| `data-cp-action-confirm` | No | If set, `window.confirm()` must pass before the request runs. |
| `data-cp-action-remove-closest` | No | CSS selector; on success, `closest(selector)` on the clicked element is removed (e.g. list row). |
| `data-cp-action-success-message` / `data-cp-action-error-message` | No | Toast copy; sensible defaults if omitted. |

Your REST route must validate `X-WP-Nonce`, check capabilities, and return appropriate HTTP status codes (`runPluginAction` treats non-OK responses as errors).

### First-party extensions in separate plugins

Official extensions that ship outside the main package register on **`clanspress_official_registered_extensions`** and must match the slug → class map in `Loader::get_official_extensions()`. Core validates the class name only; it does not `require` add-on files (the companion plugin must load before Clanspress so the class exists). Those extensions get the **Official** badge but not the **Core** badge unless their slug is also listed in `clanspress_core_bundled_extension_slugs`.

**Example — Clanspress Social Kit (`cp_social_kit`):** the separate plugin registers the extension and may mirror domain events (matches, RSVPs, team actions) into an activity feed using the same hooks documented for third-party integrations (`clanspress_match_*`, `clanspress_event_rsvp_updated`, team lifecycle actions, etc.). There is no second registration API — only `clanspress_official_registered_extensions` plus the whitelist entry in `Loader::get_official_extensions()`.

### Community extensions

Unaffiliated or custom extensions register on **`clanspress_registered_extensions`**, own their blocks and FSE templates, and document their own hooks.

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
  - **Template files:** Serialized block markup for the Site Editor lives in `templates/**/*.html`. Companion `*.php` files in the same folder call `get_header()` / `get_footer()` and `clanspress_render_block_markup_file()` so classic themes never print raw `<!-- wp:... -->` comments. `Skeleton::register_extension_templates()` reads the `.html` path for `register_block_template()`.
  - **Edit Site (block themes):** Player/team virtual routes still look like author or generic archives to core’s block-template resolver, so the admin bar’s Site Editor link is aligned to the correct `clanspress//…` template via `$_wp_current_template_id` on the `wp` hook (see Players/Teams `set_plugin_block_template_id_for_site_editor`).

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

## Notifications System

Clanspress includes a core notifications system that supports both simple notifications and interactive notifications with action buttons. The system uses HTTP long polling for real-time updates with filters to swap in WebSocket transport.

The **Notifications** extension (`cp_notifications`) must be enabled under **Clanspress → Extensions** for REST routes, the bell block, and persistence to run. Third-party code should call `clanspress_notifications_extension_active()` before assuming notifications exist, or treat a `WP_Error` from `clanspress_notify()` with code `notifications_inactive` as “extension off.”

The **Events** extension (`cp_events`) gates the `cp_event` post type, RSVP database table, event REST endpoints, and event blocks. Use `clanspress_events_extension_active()` (or `function_exists( 'clanspress_events_are_globally_enabled' )` for feature flags that load with the extension) before relying on event behavior. Team virtual URLs under `/teams/{slug}/events/` are registered only when both **Teams** and **Events** are enabled.

Player merged calendars (`player_user_id` on `GET clanspress/v1/event-posts`) omit team-scoped events when the **Teams** extension is disabled, and omit group-scoped events when no group product is active (default: `cp_group` is not registered). Override group detection with `clanspress_groups_feature_active`.

Event list pagination limits are shared with the REST layer: `clanspress_events_rest_default_per_page_paginated`, `clanspress_events_rest_max_per_page_paginated`, and for calendar range queries `clanspress_events_rest_default_per_page_range`, `clanspress_events_rest_max_per_page_range` (SSR and the event calendar block read the default range size from the same helpers).

**Event REST (`POST`/`PUT` `clanspress/v1/event-posts`):** optional JSON field `member_outreach` — `none` (default), `notify` (in-app notification to each roster member), or `rsvp_tentative` (add tentative RSVP rows for members without an existing RSVP, plus notify). Team rosters use the Teams extension membership map (banned users excluded). Group rosters use the `clanspress_group_event_member_user_ids` filter (core supplies an empty list until a groups integration fills it). Adjust recipients with `clanspress_event_member_outreach_user_ids`. After a run, `clanspress_event_member_outreach_completed` fires with counts (`notified`, `rsvp_set`, `skipped`).

### Notification Bell Block

Add the `clanspress/notification-bell` block to display a bell icon with unread count and dropdown. Block attributes:

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `showDropdown` | boolean | `true` | Show dropdown on click |
| `dropdownCount` | number | `10` | Number of notifications in dropdown |

### Sending Notifications

```php
// Simple notification
clanspress_notify( $user_id, 'mention', 'You were mentioned in a post', [
    'url'      => $post_url,
    'actor_id' => $mentioner_id,
] );

// Interactive notification with actions
clanspress_notify( $user_id, 'team_invite', sprintf( '%s invited you to join %s', $inviter_name, $team_name ), [
    'actor_id'    => $inviter_id,
    'object_type' => 'team',
    'object_id'   => $team_id,
    'url'         => $team_url,
    'actions'     => [
        [
            'key'             => 'accept',
            'label'           => __( 'Accept', 'clanspress' ),
            'style'           => 'primary',
            'handler'         => 'my_team_invite_accept',
            'status'          => 'accepted',
            'success_message' => __( 'You have joined the team!', 'clanspress' ),
        ],
        [
            'key'             => 'decline',
            'label'           => __( 'Decline', 'clanspress' ),
            'style'           => 'secondary',
            'handler'         => 'my_team_invite_decline',
            'status'          => 'declined',
            'success_message' => __( 'Invitation declined.', 'clanspress' ),
        ],
    ],
] );
```

### Action Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `key` | string | Yes | Unique action identifier (e.g., 'accept', 'decline') |
| `label` | string | Yes | Button label |
| `style` | string | No | 'primary', 'secondary', or 'danger'. Default 'secondary' |
| `handler` | string | Yes | Handler identifier for the action |
| `status` | string | No | Status to set after action ('accepted', 'declined', 'dismissed') |
| `success_message` | string | No | Message to show on success |
| `confirm` | string/false | No | Confirmation message, or false for no confirm |

### Handling Actions

Extensions register their own action handlers. For example, the Teams extension registers handlers for `team_invite_accept` and `team_invite_decline`. Third-party plugins can register handlers using filters:

```php
// Handle actions by handler identifier (recommended)
add_filter( 'clanspress_notification_action_handler', function( $result, $handler, $notification, $action, $user_id ) {
    // Return early if another handler already processed this
    if ( null !== $result ) {
        return $result;
    }

    if ( 'my_custom_handler' === $handler ) {
        // Perform your action logic
        $object_id = $notification->object_id;
        
        // Return result array
        return [
            'success'  => true,
            'message'  => __( 'Action completed!', 'my-plugin' ),
            'redirect' => get_permalink( $object_id ), // Optional redirect
        ];
    }

    // Return null to pass to next handler
    return null;
}, 10, 5 );

// Or handle by notification type (fires before generic handler)
add_filter( 'clanspress_notification_action_group_invite', function( $result, $notification, $action, $user_id ) {
    if ( 'accept' === $action['key'] ) {
        // Add user to group
        return [
            'success' => true,
            'message' => __( 'You have joined the group!', 'my-plugin' ),
        ];
    }
    return $result;
}, 10, 4 );
```

**Handler registration pattern for extensions:**

```php
class My_Extension {
    public function run(): void {
        // Register notification action handlers
        add_filter( 'clanspress_notification_action_handler', [ $this, 'handle_notification_actions' ], 10, 5 );
    }

    public function handle_notification_actions( $result, $handler, $notification, $action, $user_id ) {
        if ( null !== $result ) {
            return $result;
        }

        switch ( $handler ) {
            case 'my_invite_accept':
                return $this->handle_invite_accept( $notification, $user_id );
            case 'my_invite_decline':
                return $this->handle_invite_decline( $notification, $user_id );
            default:
                return null;
        }
    }
}
```

### Helper Functions

| Function | Description |
|----------|-------------|
| `clanspress_notifications_extension_active()` | Whether `cp_notifications` is enabled (use before UI or optional features) |
| `clanspress_notify( $user_id, $type, $title, $args )` | Send a notification |
| `clanspress_get_notifications( $user_id, $page, $per_page, $unread_only )` | Get notifications for a user |
| `clanspress_get_notification( $id )` | Get a single notification |
| `clanspress_get_unread_notification_count( $user_id )` | Get unread count |
| `clanspress_mark_notification_read( $id, $user_id )` | Mark as read |
| `clanspress_mark_all_notifications_read( $user_id )` | Mark all as read |
| `clanspress_delete_notification( $id, $user_id )` | Delete a notification |
| `clanspress_delete_all_notifications( $user_id )` | Delete all for a user |
| `clanspress_delete_notifications_for_object( $type, $id )` | Delete by object |
| `clanspress_execute_notification_action( $id, $action_key, $user_id )` | Execute an action |
| `clanspress_dismiss_notification( $id, $user_id )` | Dismiss a notification |
| `clanspress_get_notifications_url( $user_id )` | Get notifications page URL |
| `clanspress_render_notification( $notification, $compact )` | Render notification HTML |

### REST API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/clanspress/v1/notifications` | List notifications |
| `GET` | `/clanspress/v1/notifications/poll` | Long polling for real-time updates |
| `GET` | `/clanspress/v1/notifications/count` | Get unread count |
| `GET` | `/clanspress/v1/notifications/{id}` | Get single notification |
| `DELETE` | `/clanspress/v1/notifications/{id}` | Delete notification |
| `POST` | `/clanspress/v1/notifications/{id}/read` | Mark as read |
| `POST` | `/clanspress/v1/notifications/{id}/action` | Execute action |
| `POST` | `/clanspress/v1/notifications/read-all` | Mark all as read |
| `GET` | `/clanspress/v1/notifications/transport` | Get transport config |

### Real-Time Updates (Long Polling)

The notification bell uses HTTP long polling by default. The poll endpoint (`/notifications/poll`) may block until new notifications arrive or the timeout elapses (default cap **25** seconds; overridable via `clanspress_notification_poll_timeout`). The handler does **not** flush the object cache on each iteration (that would clear the entire site cache). Hosts that need lower PHP worker occupancy can set **`clanspress_notification_poll_blocking_wait`** to `false` to perform a single read and return immediately (client still uses `next_poll` spacing).

**Polling parameters:**
- `since` - ISO timestamp to get notifications after
- `last_id` - Get notifications with ID greater than this
- `timeout` - Max wait time in seconds (capped by server default, typically 25)

**Response includes:**
- `notifications` - Array of new notifications
- `unread_count` - Current unread count
- `timestamp` - Server timestamp for next poll
- `next_poll` - Recommended interval until next poll (ms)

### WebSocket Support

The system is designed for WebSocket upgrade. Use these filters to provide WebSocket transport:

```js
// JavaScript: Enable WebSocket transport
wp.hooks.addFilter(
    'clanspress.notifications.useWebSocket',
    'my-plugin/websocket',
    ( useWs, context ) => {
        // Return true if WebSocket is available
        return myWebSocketService.isConnected();
    }
);

// Provide WebSocket configuration
wp.hooks.addFilter(
    'clanspress.notifications.webSocketConfig',
    'my-plugin/websocket-config',
    ( config, context ) => {
        return {
            url: 'wss://example.com/notifications',
            authMessage: { token: myAuthToken },
        };
    }
);
```

```php
// PHP: Override polling transport entirely
add_filter( 'clanspress_notification_poll_transport', function( $response, $user_id, $since, $last_id, $request ) {
    // Return a WP_REST_Response to bypass polling
    // Useful for WebSocket-only setups
    return new WP_REST_Response( [
        'transport' => 'websocket',
        'message'   => 'Use WebSocket connection instead',
    ] );
}, 10, 5 );

// Customize transport configuration
add_filter( 'clanspress_notification_transport_config', function( $config, $user_id ) {
    if ( my_websocket_available() ) {
        $config['type'] = 'websocket';
        $config['websocket_url'] = 'wss://example.com/notifications';
    }
    return $config;
}, 10, 2 );
```

### JavaScript Hooks

| Hook | Description |
|------|-------------|
| `clanspress.notifications.useWebSocket` | Return true to use WebSocket transport |
| `clanspress.notifications.webSocketConfig` | Provide WebSocket URL and auth config |
| `clanspress.notifications.received` | Fired when new notifications arrive |
| `clanspress.notifications.showToast` | Customize toast notification display |

### PHP Filters

| Filter | Description |
|--------|-------------|
| `clanspress_notification_poll_timeout` | Modify poll timeout |
| `clanspress_notification_poll_blocking_wait` | Set `false` to skip the sleep loop (single query per request) |
| `clanspress_notification_poll_interval` | Modify poll check interval |
| `clanspress_notification_poll_transport` | Override polling with custom transport |
| `clanspress_notification_next_poll_interval` | Modify next poll interval |
| `clanspress_notification_transport_config` | Customize transport configuration |
| `clanspress_notification_action_{type}` | Handle actions for a notification type |
| `clanspress_notification_action_handler` | Generic action handler |
| `clanspress_notification_types` | Register custom notification types |
| `clanspress_render_notification` | Customize notification HTML |
| `clanspress_format_notification_response` | Customize API response format |

### Built-in Notification Types

| Type | Description |
|------|-------------|
| `team_invite` | Team invitation with Accept/Decline actions |
| `team_join` | User joined a team |
| `team_role` | Team role changed |
| `team_removed` | Removed from team |
| `team_challenge` | Match challenge with Accept/Decline |
| `team_match_event` | Match-related scheduled event (e.g. after accept) |
| `team_event` / `group_event` | Roster outreach for manual `cp_event` posts |
| `mention` | Mentioned in content |
| `system` | System notifications |

Third-party plugins can register additional types via the `clanspress_notification_types` filter.

## Maintenance Notes
- Keep this README updated when extension architecture, hooks, or setup requirements change.
- Keep public hooks documented with intent and expected arguments.
