/**
 * Front-end: profile avatar upload when the block enables inline editing.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import {
	clearClanspressPreviewObjectUrl,
	createClanspressHideToast,
	createClanspressShowToast,
	createClanspressToolbarPanelToggler,
	rejectClanspressInvalidImageFile,
	setClanspressPreviewObjectUrlFromFile,
} from '../../shared/front-media-interactivity.js';

const { state, actions } = store( 'clanspress-player-avatar', {
	state: {
		root: null,
		activePanel: null,
		isSaving: false,
		errors: {},
		previewObjectUrl: null,
		toast: {
			visible: false,
			type: 'success',
			heading: '',
			message: '',
			timeout: null,
		},

		isThisPanelActive() {
			const { attributes } = getElement();
			return this.activePanel === attributes[ 'data-wp-args' ];
		},

		isToastSuccess() {
			return this.toast.type === 'success';
		},

		isToastError() {
			return this.toast.type === 'error';
		},
	},

	actions: {
		togglePanel: createClanspressToolbarPanelToggler( state, {
			panelSelectorPrefix: '.clanspress-player-avatar__panel--',
			allPanelsSelector: '.clanspress-player-avatar__panel',
		} ),

		selectAvatar() {
			state.root?.querySelector(
				'input[name="profile_avatar"]'
			)?.click();
		},

		updateAvatar( event ) {
			const file = event.target.files[ 0 ];
			if ( ! file ) {
				return;
			}
			const { strings } = getContext();
			if (
				rejectClanspressInvalidImageFile( file, event.target, strings, {
					showToast: actions.showToast,
					toastPayload: { heading: '' },
				} )
			) {
				return;
			}
			const url = setClanspressPreviewObjectUrlFromFile( state, file );
			const preview = state.root?.querySelector(
				'.clanspress-player-avatar__img'
			);
			if ( preview && preview.tagName === 'IMG' ) {
				preview.classList.remove( 'clanspress-player-avatar__img--empty' );
				preview.src = url;
			}
		},

		showToast: createClanspressShowToast( state, { includeHeading: true } ),

		hideToast: createClanspressHideToast( state ),

		async save() {
			const { ref } = getElement();
			const { strings } = getContext();

			if ( ! state.root || ! ref ) {
				return;
			}

			state.isSaving = true;
			ref.classList.remove( 'saved', 'error' );
			ref.classList.add( 'saving' );

			const data = {};
			state.root
				.querySelectorAll( 'input, select, textarea' )
				.forEach( ( field ) => {
					if ( ! field.name || field.disabled ) {
						return;
					}
					if ( field.type === 'checkbox' ) {
						data[ field.name ] = field.checked ? '1' : '0';
					} else if ( field.type === 'radio' ) {
						if ( field.checked ) {
							data[ field.name ] = field.value;
						}
					} else if ( field.type !== 'file' ) {
						data[ field.name ] = field.value;
					}
				} );

			const nonceInput = state.root.querySelector(
				'input[name="_clanspress_profile_settings_save_nonce"]'
			);

			if ( ! nonceInput || ! window.CLANSPRESSPLAYERSETTINGS?.ajax_url ) {
				state.isSaving = false;
				ref.classList.remove( 'saving' );
				ref.classList.add( 'error' );
				return;
			}
			data.nonce = nonceInput.value;
			data.action = 'clanspress_save_player_settings';

			const formData = new FormData();
			Object.keys( data ).forEach( ( key ) => {
				formData.append( key, data[ key ] );
			} );

			const avatarInput = state.root.querySelector(
				'input[name="profile_avatar"]'
			);
			if ( avatarInput?.files[ 0 ] ) {
				formData.append( 'profile_avatar', avatarInput.files[ 0 ] );
			}

			try {
				const res = await fetch( CLANSPRESSPLAYERSETTINGS.ajax_url, {
					method: 'POST',
					credentials: 'same-origin',
					body: formData,
				} );

				const json = await res.json();
				ref.classList.remove( 'saving' );

				if ( json.success ) {
					ref.classList.add( 'saved' );
					state.errors = {};
					const payload = json.data || {};
					if ( payload.avatarUrl ) {
						const img = state.root.querySelector(
							'.clanspress-player-avatar__img'
						);
						if ( img && img.tagName === 'IMG' ) {
							clearClanspressPreviewObjectUrl( state );
							img.src = payload.avatarUrl;
							img.classList.remove(
								'clanspress-player-avatar__img--empty'
							);
						}
					}
					if ( avatarInput ) {
						avatarInput.value = '';
					}
					actions.showToast( {
						type: 'success',
						heading: '',
						message:
							strings?.saveSuccess ||
							'Your changes were saved successfully.',
					} );
					state.root
						.querySelectorAll( '.clanspress-player-avatar__panel' )
						.forEach( ( p ) => p.classList.remove( 'is-open' ) );
					state.activePanel = null;
				} else {
					ref.classList.add( 'error' );
					state.errors = json?.data?.errors;
					actions.showToast( {
						type: 'error',
						heading: '',
						message:
							strings?.saveError ||
							'There was an error while saving changes.',
					} );
				}
			} catch ( err ) {
				// eslint-disable-next-line no-console
				console.error( 'Save failed', err );
			} finally {
				state.isSaving = false;
			}
		},
	},

	callbacks: {
		init() {
			const { ref } = getElement();
			if ( ref ) {
				state.root = ref;
			}
		},
	},
} );
