/**
 * Front-end: profile avatar upload when the block enables inline editing.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import {
	applyClanspressInlineMediaSavePayload,
	CLANSPRESS_MEDIA_ISLAND_ROOT_SELECTORS,
	createClanspressHideToast,
	createClanspressShowToast,
	createClanspressToolbarPanelToggler,
	getClanspressInteractivityStateGetter,
	getClanspressIslandRootFromRef,
	getClanspressToolbarPanelId,
	rejectClanspressInvalidImageFile,
	setClanspressPreviewObjectUrlFromFile,
} from '../../shared/front-media-interactivity.js';

const STORE_NAMESPACE = 'clanspress-player-avatar';

const getPlayerAvatarState =
	getClanspressInteractivityStateGetter( STORE_NAMESPACE );

const { state, actions } = store( STORE_NAMESPACE, {
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
			const { ref, attributes } = getElement();
			return this.activePanel === getClanspressToolbarPanelId( attributes, ref );
		},

		isToastSuccess() {
			return this.toast.type === 'success';
		},

		isToastError() {
			return this.toast.type === 'error';
		},
	},

	actions: {
		togglePanel: createClanspressToolbarPanelToggler(
			getPlayerAvatarState,
			{
				panelSelectorPrefix: '.clanspress-player-avatar__panel--',
				allPanelsSelector: '.clanspress-player-avatar__panel',
				islandRootSelector:
					CLANSPRESS_MEDIA_ISLAND_ROOT_SELECTORS.playerAvatar,
			}
		),

		selectAvatar() {
			const { ref } = getElement();
			const root = getClanspressIslandRootFromRef(
				ref,
				CLANSPRESS_MEDIA_ISLAND_ROOT_SELECTORS.playerAvatar
			);
			root
				?.querySelector( 'input[name="profile_avatar"]' )
				?.click();
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
			const { ref } = getElement();
			const root = getClanspressIslandRootFromRef(
				ref,
				CLANSPRESS_MEDIA_ISLAND_ROOT_SELECTORS.playerAvatar
			);
			const url = setClanspressPreviewObjectUrlFromFile( state, file );
			const preview = root?.querySelector(
				'.clanspress-player-avatar__img'
			);
			if ( preview && preview.tagName === 'IMG' ) {
				preview.classList.remove(
					'clanspress-player-avatar__img--empty'
				);
				preview.src = url;
			}
		},

		showToast: createClanspressShowToast( getPlayerAvatarState, {
			includeHeading: true,
		} ),

		hideToast: createClanspressHideToast( getPlayerAvatarState ),

		async save() {
			const { ref } = getElement();
			const { strings } = getContext();
			const root = getClanspressIslandRootFromRef(
				ref,
				CLANSPRESS_MEDIA_ISLAND_ROOT_SELECTORS.playerAvatar
			);

			if ( ! root || ! ref ) {
				return;
			}

			state.isSaving = true;
			ref.classList.remove( 'saved', 'error' );
			ref.classList.add( 'saving' );

			const data = {};
			root
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

			const nonceInput = root.querySelector(
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

			const avatarInput = root.querySelector(
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
					applyClanspressInlineMediaSavePayload(
						state,
						json.data || {},
						{
							root,
							items: [
								{
									urlKey: 'avatarUrl',
									mediaSelector:
										'.clanspress-player-avatar__img',
									requireImg: true,
									emptyClass:
										'clanspress-player-avatar__img--empty',
								},
							],
						}
					);
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
					root
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
				state.root =
					getClanspressIslandRootFromRef(
						ref,
						CLANSPRESS_MEDIA_ISLAND_ROOT_SELECTORS.playerAvatar
					) || ref;
			}
		},
	},
} );
