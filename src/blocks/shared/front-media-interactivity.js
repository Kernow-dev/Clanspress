/**
 * Shared helpers for Clanspress front-end avatar/cover interactivity stores.
 */
import { getElement, store } from '@wordpress/interactivity';

/**
 * Returns a lazy accessor for store state so factories can run inside `store( ns, { actions } )`
 * without reading `state` before `const { state } = store( … )` initializes (TDZ).
 *
 * @param {string} namespace Same namespace passed to `store()`.
 * @return {() => object} Call after registration; returns reactive state proxy.
 */
export function getClanspressInteractivityStateGetter( namespace ) {
	return () => store( namespace ).state;
}

export const CLANSPRESS_INLINE_IMAGE_MIME_TYPES = Object.freeze( [
	'image/png',
	'image/jpeg',
] );

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
 * @param {() => { activePanel: string|null }} getState Lazy state accessor (see {@link getClanspressInteractivityStateGetter}).
 * @param {{ panelSelectorPrefix: string, allPanelsSelector: string }} config `panelSelectorPrefix` includes the trailing `--` (e.g. `.clanspress-team-cover__panel--`).
 * @return {() => void}
 */
export function createClanspressToolbarPanelToggler( getState, config ) {
	const { panelSelectorPrefix, allPanelsSelector } = config;

	return function togglePanel() {
		const state = getState();
		const { ref, attributes } = getElement();
		if ( ! ref || ! attributes || ! ref.parentNode ) {
			return;
		}
		const panelName = attributes[ 'data-wp-args' ];
		if ( ! panelName ) {
			return;
		}
		const panel = ref.parentNode.querySelector(
			`${ panelSelectorPrefix }${ panelName }`
		);
		if ( ! panel ) {
			return;
		}
		const willOpen = ! panel.classList.contains( 'is-open' );
		ref.parentNode
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
