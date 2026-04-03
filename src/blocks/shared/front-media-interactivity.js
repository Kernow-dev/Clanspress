/**
 * Shared helpers for Clanspress front-end avatar/cover interactivity stores.
 */
import { getElement } from '@wordpress/interactivity';

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
 * @param {object} options
 * @param {Function} options.showToast
 * @param {object} [options.toastPayload]
 * @return {boolean} True when the file is invalid (caller should return early).
 */
export function rejectClanspressInvalidImageFile(
	file,
	fileInput,
	strings,
	{ showToast, toastPayload }
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
 * @param {{ activePanel: string|null }} state
 * @param {{ panelSelectorPrefix: string, allPanelsSelector: string }} config `panelSelectorPrefix` includes the trailing `--` (e.g. `.clanspress-team-cover__panel--`).
 * @return {() => void}
 */
export function createClanspressToolbarPanelToggler( state, config ) {
	const { panelSelectorPrefix, allPanelsSelector } = config;

	return function togglePanel() {
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
 * @param {{ toast: { visible: boolean, type: string, message: string, heading?: string, timeout: ReturnType<typeof setTimeout>|null } }} state
 * @param {{ includeHeading?: boolean }} options
 * @return {(payload: { type?: string, heading?: string, message?: string, duration?: number }) => void}
 */
export function createClanspressShowToast( state, { includeHeading = false } = {} ) {
	return function showToast( {
		type = 'success',
		heading = '',
		message = '',
		duration = 6000,
	} ) {
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
 * @param {{ toast: { visible: boolean, timeout: ReturnType<typeof setTimeout>|null } }} state
 * @return {() => void}
 */
export function createClanspressHideToast( state ) {
	return function hideToast() {
		if ( state.toast.timeout ) {
			clearTimeout( state.toast.timeout );
		}
		state.toast.visible = false;
	};
}
