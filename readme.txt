=== Clanbite: Team Management System ===
Contributors: kernowdev
Tags: community, teams, esports, gaming
Requires at least: 6.7
Tested up to: 7.0
Requires PHP: 8.2
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Community tools for gaming teams and clubs: player profiles, teams, matches, events, notifications, and an extension system.

== Description ==

Clanbite helps you run team and player profiles, schedules, and community features inside WordPress.

* **Players** — Extended player profiles and settings.
* **Teams** — Rosters, roles, and team-facing templates and blocks.
* **Matches** — Match records with editor and front-end blocks (requires Teams).
* **Events & RSVP** — Event posts with RSVP storage for teams and groups.
* **Notifications** — In-site notifications with a block-ready bell.
* **Extensions** — Enable bundled features from **Clanbite → Extensions**; third-party plugins can register their own extensions via hooks.

The plugin follows modern WordPress APIs (blocks, REST where used, block themes). See `README.md` in the plugin package for developer hooks and architecture notes.

Human-readable JavaScript and CSS: minified files under `build/` and `assets/dist/` are produced from the sources described in the Human-readable source code section below. Clone the public repository linked there to review or fork the same sources used to build this package.

== External services ==

* **Gravatar (Automattic):** In-site notifications can show an actor portrait using WordPress `get_avatar_url()`, which may resolve to Gravatar when the user has no local Clanbite player avatar. The visitor's browser loads that image URL (standard `<img>` request). No passwords are transmitted to Gravatar. Terms: https://wordpress.com/tos/ — Privacy: https://automattic.com/privacy/

== Human-readable source code ==

JavaScript and CSS shipped in this plugin (for example `build/**/index.js`, block `view.js` bundles, and `assets/dist/clanbite-admin.js`) are **compiled and minified** for performance. Per the [WordPress plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#4-code-must-be-mostly-human-readable), the original source is publicly available so it can be reviewed, studied, and forked.

**Public repository (canonical source tree):** https://github.com/Kernow-dev/Clanbite

The WordPress.org plugin download omits the `src/` tree and Node tooling to keep installs small; those files are in the repository above at the same paths as this release’s tag/branch.

**Where to read the non-compiled code**

* **Block editor and front-end blocks** — `src/blocks/` (matches, players, teams, events, notifications, core blocks). Each block’s `index.js`, `edit.js`, `view.js`, and related modules compile into matching paths under `build/` (for example `src/blocks/events/event-calendar/` → `build/events/event-calendar/`).
* **Clanbite admin (React settings shell)** — `src/admin/` → `assets/dist/clanbite-admin.js` (see `webpack.config.cjs` in the repository).
* **Match post editor sidebar** — `src/cp-match-editor/src/` → `build/cp-match-editor/`.

**Third-party JavaScript** used at build time (for example `@wordpress/scripts` and `@wordpress/*` packages) is declared in `package.json` / `package-lock.json` in that repository. Those packages are open-source; versions are pinned in the lockfile for reproducible builds.

**Rebuild production assets** (from a clone of the repository, with Node.js and npm installed):

1. `npm ci`
2. `npm run build:production` — runs the admin webpack build, then the block build (including block manifests and related steps). Equivalent to the assets in a release ZIP.
3. Optional: `npm run build:admin` or `npm run build:blocks` to rebuild only the admin bundle or only blocks (see `package.json` `"scripts"` for other targets such as `plugin-zip`).

For PHP architecture, hooks, and REST, see the bundled `README.md`.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/clanbite` or install the ZIP from the Plugins screen.
2. Activate **Clanbite** through the **Plugins** menu.
3. Open **Clanbite** in the admin menu, review **General** settings, and enable the extensions you need (Players is a common starting point).
4. Visit **Settings → Permalinks** and click **Save** once if routes for teams or events do not resolve (the plugin flushes rules on upgrade, but a manual save fixes edge cases).

== Frequently Asked Questions ==

= Does this work with block themes? =

Yes. Clanbite registers block types and plugin block templates for full-site editing where applicable.

= Where are the settings? =

Use the **Clanbite** top-level admin menu for core options and extension toggles. On the **Plugins** screen, use the **Settings** link (administrators) or **Website** to open clanbite.com.

== Support ==

* Documentation: see the bundled `README.md` for developers (hooks, extensions, REST).
* Plugin site: https://clanbite.com
* Help and bug reports: https://github.com/Kernow-dev/Clanbite/issues
* WordPress.org: after the plugin is listed on the directory, use the support forum at https://wordpress.org/support/plugin/clanbite/ for site-owner questions.

== Screenshots ==

1. Clanbite admin settings and extension management.
2. Team and player blocks in the block editor.

== Changelog ==

= 1.1.0 =
* WordPress 7.0 compatibility verified.
* Added PHP 8.2+ and WordPress 6.7+ requirements validation.
* Fixed block category duplication prevention.
* Improved extension version comparison safety.
* Enhanced code documentation and error handling.

= 1.0.0 =
* Initial WordPress.org release.
* Unified maintenance step and database schema versioning for new installs.

== Upgrade Notice ==

= 1.1.0 =
WordPress 7.0 compatible. Includes improved requirements checking and minor bug fixes. Safe to upgrade.

= 1.0.0 =
First public release on WordPress.org. If you tested pre-release builds, visit Permalinks and save once after upgrading.
