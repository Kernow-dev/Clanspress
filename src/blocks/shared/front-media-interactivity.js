/**
 * Shared helpers for Clanspress front-end avatar/cover interactivity stores.
 */
import { getElement, store } from '@wordpress/interactivity';

/**
 * Returns a lazy accessor for `@wordpress/interactivity` store state so factories can run inside
 * `store( ns, { actions } )` without reading `state` before `const { state } = store( … )` initializes (TDZ).
 *
 * **Call order:** Pass the same string you pass to `store( namespace, config )`. In each module,
 * define the getter, pass it into helpers (e.g. `createClanspressToolbarPanelToggler`), then call
 * `store( namespace, { state, actions, … } )` so registration finishes before the returned getter
 * runs. The getter is normally first invoked from user-driven actions after hydration; do not call
 * it synchronously during module evaluation before `store( namespace, … )` has executed.
 *
 * Note: `store( namespace )` with no config still registers an empty store in WordPress; wrong
 * ordering can yield subtle bugs rather than a hard error—keep the pattern above.
 *
 * @param {string} namespace Interactivity store namespace (must match `store()`).
 * @return {() => object} Lazy accessor; returns the reactive `state` proxy for `namespace`.
 * @throws {Error} If `namespace` is not a non-empty string.
 */
export function getClanspressInteractivityStateGetter( namespace ) {
	if ( typeof namespace !== 'string' || '' === namespace ) {
		throw new Error(
			'[clanspress] getClanspressInteractivityStateGetter: `namespace` must be a non-empty string.'
		);
	}
	return () => store( namespace ).state;
}

export const CLANSPRESS_INLINE_IMAGE_MIME_TYPES = Object.freeze( [
	'image/png',
	'image/jpeg',
] );

/**
 * Toolbar inner wrappers for front media blocks; used to scope panel open/close to the correct island.
 *
 * @type {string}
 */
export const CLANSPRESS_MEDIA_TOOLBAR_INNER_SELECTOR =
	'.clanspress-player-cover__toolbar-inner, .clanspress-team-cover__toolbar-inner, .clanspress-player-avatar__toolbar-inner, .clanspress-team-avatar__toolbar-inner';

/**
 * Root wrapper class selectors for front media Interactivity islands.
 * Align with each block’s server `get_block_wrapper_attributes` root class (typically `*-block`).
 *
 * @type {Readonly<{
 *   playerCover: string,
 *   teamCover: string,
 *   playerAvatar: string,
 *   teamAvatar: string,
 * }>}
 */
export const CLANSPRESS_MEDIA_ISLAND_ROOT_SELECTORS = Object.freeze( {
	playerCover: '.clanspress-player-cover-block',
	teamCover: '.clanspress-team-cover-block',
	playerAvatar: '.clanspress-player-avatar-block',
	teamAvatar: '.clanspress-team-avatar-block',
} );

/**
 * Block island root for the current interactive element.
 *
 * Interactivity stores are per-namespace; multiple blocks share one `state`, so `state.root` is
 * overwritten on each init. Always resolve the DOM root from `ref` for the active block.
 *
 * @param {Element|null|undefined} ref
 * @param {string}                 islandRootSelector From {@link CLANSPRESS_MEDIA_ISLAND_ROOT_SELECTORS}.
 * @return {Element|null}
 */
export function getClanspressIslandRootFromRef( ref, islandRootSelector ) {
	if ( ! ref || typeof ref.closest !== 'function' ) {
		return null;
	}
	if ( ! islandRootSelector ) {
		return null;
	}
	return ref.closest( islandRootSelector );
}

/**
 * DOM subtree used to query toolbar panels (prefers island from `ref`, then hydrated `state.root`).
 *
 * @param {{ root?: Element|null }}              state
 * @param {Element|null|undefined}               ref
 * @param {string|undefined}                     islandRootSelector From {@link CLANSPRESS_MEDIA_ISLAND_ROOT_SELECTORS}.
 * @return {Element|null}
 */
export function getClanspressToolbarScope( state, ref, islandRootSelector ) {
	if ( ! ref ) {
		return null;
	}
	if ( islandRootSelector && typeof ref.closest === 'function' ) {
		const island = ref.closest( islandRootSelector );
		if ( island ) {
			return island;
		}
	}
	if ( state?.root && typeof state.root.querySelector === 'function' ) {
		return state.root;
	}
	const scopeRoot =
		typeof ref.closest === 'function'
			? ref.closest( CLANSPRESS_MEDIA_TOOLBAR_INNER_SELECTOR )
			: null;
	return scopeRoot || ref.parentElement || ref.parentNode || null;
}

/**
 * @param {File|undefined|null} file
 * @return {boolean}
 */
export function isClanspressInlineImageMimeType( file ) {
	return Boolean(
		file?.type && CLANSPRESS_INLINE_IMAGE_MIME_TYPES.includes( file.type )
	);
}

/**
 * @param {{ invalidFileType?: string }} strings
 * @return {string}
 */
export function getClanspressInvalidInlineImageMessage( strings ) {
	return strings?.invalidFileType || 'Invalid file type.';
}

/**
 * Screen-reader + optional admin snackbar + block toast for invalid image type.
 *
 * @param {string}   message
 * @param {object}   options
 * @param {Function} [options.showToast]
 * @param {object}   [options.toastPayload] Merged into showToast (e.g. heading for player stores).
 */
export function announceClanspressInvalidImageFile(
	message,
	{ showToast, toastPayload = {} } = {}
) {
	if ( window.wp?.a11y?.speak ) {
		window.wp.a11y.speak( message, 'assertive' );
	}
	const noticesDispatcher = window.wp?.data?.dispatch?.( 'core/notices' );
	if ( noticesDispatcher?.createNotice ) {
		noticesDispatcher.createNotice( 'error', message, {
			type: 'snackbar',
		} );
	}
	if ( typeof showToast === 'function' ) {
		showToast( {
			type: 'error',
			message,
			duration: 6000,
			...toastPayload,
		} );
	}
}

/**
 * @param {File|undefined|null} file
 * @param {HTMLInputElement|null|undefined} fileInput
 * @param {{ invalidFileType?: string }} strings
 * @param {object} [options]
 * @param {Function} [options.showToast]
 * @param {object} [options.toastPayload]
 * @return {boolean} True when the file is invalid (caller should return early).
 */
export function rejectClanspressInvalidImageFile(
	file,
	fileInput,
	strings,
	{ showToast, toastPayload } = {}
) {
	if ( isClanspressInlineImageMimeType( file ) ) {
		return false;
	}
	const message = getClanspressInvalidInlineImageMessage( strings );
	announceClanspressInvalidImageFile( message, { showToast, toastPayload } );
	if ( fileInput ) {
		fileInput.value = '';
	}
	return true;
}

/**
 * @param {{ previewObjectUrl: string|null }} state
 */
export function clearClanspressPreviewObjectUrl( state ) {
	if ( state.previewObjectUrl ) {
		URL.revokeObjectURL( state.previewObjectUrl );
		state.previewObjectUrl = null;
	}
}

/**
 * After a successful save, point preview media at server URLs, revoke any blob URL once, and clear named file inputs.
 *
 * @param {{ previewObjectUrl: string|null }} state
 * @param {Record<string, unknown>}         payload Response `data` (e.g. avatarUrl / coverUrl).
 * @param {{
 *   root?: Element|null,
 *   items: Array<{
 *     urlKey: string,
 *     mediaSelector: string,
 *     emptyClass?: string,
 *     requireImg?: boolean,
 *     clearInputName?: string,
 *     clearInputNames?: string[],
 *     afterApply?: ( root: Element, url: string, mediaEl: Element ) => void,
 *   }>
 * }} config
 * @return {boolean} True when at least one `urlKey` was applied to a matching element.
 */
export function applyClanspressInlineMediaSavePayload(
	state,
	payload,
	config
) {
	const root = config.root ?? state.root;
	if ( ! root || ! payload || ! Array.isArray( config.items ) ) {
		return false;
	}

	let didApply = false;
	let revokedBlob = false;

	for ( const item of config.items ) {
		const url = payload[ item.urlKey ];
		if ( ! url || typeof url !== 'string' ) {
			continue;
		}

		const media = root.querySelector( item.mediaSelector );
		if ( ! media ) {
			continue;
		}

		if ( item.requireImg && media.tagName !== 'IMG' ) {
			continue;
		}

		if ( ! revokedBlob ) {
			clearClanspressPreviewObjectUrl( state );
			revokedBlob = true;
		}

		if ( media.tagName === 'IMG' ) {
			media.src = url;
		}

		if ( item.emptyClass ) {
			media.classList.remove( item.emptyClass );
		}

		if ( typeof item.afterApply === 'function' ) {
			item.afterApply( root, url, media );
		}

		if ( item.clearInputName ) {
			const clearInput = root.querySelector(
				`input[name="${ item.clearInputName }"]`
			);
			if ( clearInput ) {
				clearInput.value = '';
			}
		}
		if ( item.clearInputNames?.length ) {
			for ( const name of item.clearInputNames ) {
				const namedInput = root.querySelector(
					`input[name="${ name }"]`
				);
				if ( namedInput ) {
					namedInput.value = '';
				}
			}
		}

		didApply = true;
	}

	return didApply;
}

/**
 * @param {{ previewObjectUrl: string|null }} state
 * @param {File} file
 * @return {string} New object URL for the preview.
 */
export function setClanspressPreviewObjectUrlFromFile( state, file ) {
	clearClanspressPreviewObjectUrl( state );
	const url = URL.createObjectURL( file );
	state.previewObjectUrl = url;
	return url;
}

/**
 * Resolves the toolbar panel id from `data-cp-panel` (preferred) or legacy `data-wp-args`.
 *
 * Uses `data-cp-panel` because `data-wp-*` values are Interactivity directives; unknown names are
 * not kept as normal DOM attributes after hydration.
 *
 * @param {Record<string, unknown>|undefined} attributes From `getElement().attributes`.
 * @param {Element|null|undefined}            ref         From `getElement().ref` (Element when available).
 * @return {string|null} Panel suffix matching the BEM modifier (e.g. `edit-cover`, `edit-avatar`).
 */
export function getClanspressToolbarPanelId( attributes, ref ) {
	const fromProps =
		attributes?.[ 'data-cp-panel' ] ??
		attributes?.dataCpPanel ??
		attributes?.[ 'data-wp-args' ] ??
		attributes?.dataWpArgs;
	if ( typeof fromProps === 'string' && fromProps !== '' ) {
		return fromProps;
	}
	if ( ref && typeof ref.getAttribute === 'function' ) {
		const fromAttr =
			ref.getAttribute( 'data-cp-panel' ) ||
			ref.getAttribute( 'data-wp-args' );
		if ( fromAttr ) {
			return fromAttr;
		}
	}
	// Preact may expose `data-cp-panel` only on the live DOM (`dataset`), not on `element.props`.
	if ( ref?.dataset && typeof ref.dataset.cpPanel === 'string' ) {
		const fromDs = ref.dataset.cpPanel.trim();
		if ( fromDs !== '' ) {
			return fromDs;
		}
	}
	return null;
}

/**
 * @param {() => { activePanel: string|null, root?: Element|null }} getState Lazy state accessor (see {@link getClanspressInteractivityStateGetter}).
 * @param {{
 *   panelSelectorPrefix: string,
 *   allPanelsSelector: string,
 *   islandRootSelector?: string,
 * }} config `panelSelectorPrefix` includes the trailing `--` (e.g. `.clanspress-team-cover__panel--`). Prefer {@link CLANSPRESS_MEDIA_ISLAND_ROOT_SELECTORS} for `islandRootSelector` when `state.root` is not set yet.
 * @return {() => void}
 */
export function createClanspressToolbarPanelToggler( getState, config ) {
	const { panelSelectorPrefix, allPanelsSelector, islandRootSelector } =
		config;

	return function togglePanel() {
		const state = getState();
		const { ref, attributes } = getElement();
		const scope = getClanspressToolbarScope(
			state,
			ref,
			islandRootSelector
		);
		if ( ! scope || typeof scope.querySelector !== 'function' ) {
			return;
		}
		const panelName = getClanspressToolbarPanelId( attributes, ref );
		if ( ! panelName ) {
			return;
		}
		const panel = scope.querySelector(
			`${ panelSelectorPrefix }${ panelName }`
		);
		if ( ! panel ) {
			return;
		}
		const willOpen = ! panel.classList.contains( 'is-open' );
		scope
			.querySelectorAll( allPanelsSelector )
			.forEach( ( p ) => p.classList.remove( 'is-open' ) );
		if ( willOpen ) {
			panel.classList.add( 'is-open' );
			state.activePanel = panelName;
		} else {
			state.activePanel = null;
		}
	};
}

/**
 * @param {() => { toast: { visible: boolean, type: string, message: string, heading?: string, timeout: ReturnType<typeof setTimeout>|null } }} getState Lazy state accessor.
 * @param {{ includeHeading?: boolean }} options
 * @return {(payload: { type?: string, heading?: string, message?: string, duration?: number }) => void}
 */
export function createClanspressShowToast(
	getState,
	{ includeHeading = false } = {}
) {
	return function showToast( {
		type = 'success',
		heading = '',
		message = '',
		duration = 6000,
	} ) {
		const state = getState();
		if ( state.toast.timeout ) {
			clearTimeout( state.toast.timeout );
		}
		state.toast.type = type;
		if ( includeHeading ) {
			state.toast.heading = heading;
		}
		state.toast.message = message;
		state.toast.visible = true;
		if ( duration ) {
			state.toast.timeout = setTimeout( () => {
				state.toast.visible = false;
			}, duration );
		}
	};
}

/**
 * @param {() => { toast: { visible: boolean, timeout: ReturnType<typeof setTimeout>|null } }} getState Lazy state accessor.
 * @return {() => void}
 */
export function createClanspressHideToast( getState ) {
	return function hideToast() {
		const state = getState();
		if ( state.toast.timeout ) {
			clearTimeout( state.toast.timeout );
		}
		state.toast.visible = false;
	};
}
