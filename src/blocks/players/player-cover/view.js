/**
 * Front-end: profile cover upload / focal point when the block enables inline editing.
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

const { state, actions } = store( 'clanspress-player-cover', {
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
			panelSelectorPrefix: '.clanspress-player-cover__panel--',
			allPanelsSelector: '.clanspress-player-cover__panel',
		} ),

		startDrag( event ) {
			event.preventDefault();

			const { ref } = getElement();
			if ( ! ref || ! state.root ) {
				return;
			}

			ref.classList.add( 'is-dragging' );

			const box = ref.closest( '.clanspress-player-cover__position-box' );
			const image = state.root.querySelector(
				'.clanspress-player-cover__media'
			);
			if ( ! box || ! image ) {
				ref.classList.remove( 'is-dragging' );
				return;
			}

			const rect = box.getBoundingClientRect();
			const coverXInput = state.root.querySelector(
				'input[name="profile_cover_position_x"]'
			);
			const coverYInput = state.root.querySelector(
				'input[name="profile_cover_position_y"]'
			);
			let posX = 50;
			let posY = 50;
			const ix = coverXInput?.value
				? parseFloat( coverXInput.value )
				: NaN;
			const iy = coverYInput?.value
				? parseFloat( coverYInput.value )
				: NaN;
			if ( ! Number.isNaN( ix ) ) {
				posX = Math.min( 100, Math.max( 0, ix * 100 ) );
			}
			if ( ! Number.isNaN( iy ) ) {
				posY = Math.min( 100, Math.max( 0, iy * 100 ) );
			}

			const move = ( moveEvent ) => {
				const clientX =
					moveEvent.clientX ?? moveEvent.touches?.[ 0 ]?.clientX;
				const clientY =
					moveEvent.clientY ?? moveEvent.touches?.[ 0 ]?.clientY;
				if ( clientX === undefined || clientY === undefined ) {
					return;
				}
				const rawX =
					( ( clientX - rect.left ) / rect.width ) * 100;
				const rawY =
					( ( clientY - rect.top ) / rect.height ) * 100;
				posX = Math.min( 100, Math.max( 0, rawX ) );
				posY = Math.min( 100, Math.max( 0, rawY ) );
				ref.style.left = `${ posX }%`;
				ref.style.top = `${ posY }%`;
				image.style.objectPosition = `${ posX }% ${ posY }%`;
			};

			const stop = () => {
				document.removeEventListener( 'pointermove', move );
				document.removeEventListener( 'pointerup', stop );
				document.removeEventListener( 'touchmove', move );
				document.removeEventListener( 'touchend', stop );
				ref.classList.remove( 'is-dragging' );
				const xRounded = Math.round( posX );
				const yRounded = Math.round( posY );
				if ( coverXInput ) {
					coverXInput.value = String( xRounded / 100 );
				}
				if ( coverYInput ) {
					coverYInput.value = String( yRounded / 100 );
				}
			};

			document.addEventListener( 'pointermove', move );
			document.addEventListener( 'pointerup', stop );
			document.addEventListener( 'touchmove', move, { passive: false } );
			document.addEventListener( 'touchend', stop );
		},

		selectCover() {
			state.root?.querySelector( 'input[name="profile_cover"]' )?.click();
		},

		updateCover( event ) {
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
				'.clanspress-player-cover__media'
			);
			if ( preview ) {
				preview.classList.remove( 'clanspress-player-cover__media--empty' );
				preview.style.opacity = '';
				preview.style.pointerEvents = '';
				preview.src = url;
			}
			const posBox = state.root?.querySelector(
				'.clanspress-player-cover__position-box'
			);
			if ( posBox ) {
				posBox.style.backgroundImage = `url(${ JSON.stringify( url ) })`;
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
			const coverInput = state.root.querySelector(
				'input[name="profile_cover"]'
			);
			if ( avatarInput?.files[ 0 ] ) {
				formData.append( 'profile_avatar', avatarInput.files[ 0 ] );
			}
			if ( coverInput?.files[ 0 ] ) {
				formData.append( 'profile_cover', coverInput.files[ 0 ] );
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
					if ( payload.coverUrl ) {
						const img = state.root.querySelector(
							'.clanspress-player-cover__media'
						);
						const posBox = state.root.querySelector(
							'.clanspress-player-cover__position-box'
						);
						if ( img ) {
							clearClanspressPreviewObjectUrl( state );
							img.src = payload.coverUrl;
							img.classList.remove(
								'clanspress-player-cover__media--empty'
							);
						}
						if ( posBox ) {
							posBox.style.backgroundImage = `url(${ JSON.stringify(
								payload.coverUrl
							) })`;
						}
					}
					if ( avatarInput ) {
						avatarInput.value = '';
					}
					if ( coverInput ) {
						coverInput.value = '';
					}
					actions.showToast( {
						type: 'success',
						heading: '',
						message:
							strings?.saveSuccess ||
							'Your changes were saved successfully.',
					} );
					state.root
						.querySelectorAll( '.clanspress-player-cover__panel' )
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
