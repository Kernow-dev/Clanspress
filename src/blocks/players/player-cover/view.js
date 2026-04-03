/**
 * Front-end: profile cover upload / focal point when the block enables inline editing.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

const { state, actions } = store( 'clanspress-player-cover', {
	state: {
		root: null,
		activePanel: null,
		isSaving: false,
		errors: {},
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
		togglePanel() {
			const { ref, attributes } = getElement();
			if ( ! ref || ! attributes || ! ref.parentNode ) {
				return;
			}
			const panelName = attributes[ 'data-wp-args' ];
			if ( ! panelName ) {
				return;
			}
			const panel = ref.parentNode.querySelector(
				`.clanspress-player-cover__panel--${ panelName }`
			);
			if ( ! panel ) {
				return;
			}
			const willOpen = ! panel.classList.contains( 'is-open' );
			ref.parentNode
				.querySelectorAll( '.clanspress-player-cover__panel' )
				.forEach( ( p ) => p.classList.remove( 'is-open' ) );
			if ( willOpen ) {
				panel.classList.add( 'is-open' );
				state.activePanel = panelName;
			} else {
				state.activePanel = null;
			}
		},

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
			let x = 0;
			let y = 0;

			const move = ( moveEvent ) => {
				const clientX =
					moveEvent.clientX ?? moveEvent.touches?.[ 0 ]?.clientX;
				const clientY =
					moveEvent.clientY ?? moveEvent.touches?.[ 0 ]?.clientY;
				if ( clientX === undefined || clientY === undefined ) {
					return;
				}
				x = ( ( clientX - rect.left ) / rect.width ) * 100;
				y = ( ( clientY - rect.top ) / rect.height ) * 100;
				x = Math.min( 100, Math.max( 0, Math.round( x ) ) );
				y = Math.min( 100, Math.max( 0, Math.round( y ) ) );
				ref.style.left = `${ x }%`;
				ref.style.top = `${ y }%`;
				image.style.objectPosition = `${ x }% ${ y }%`;
			};

			const stop = () => {
				document.removeEventListener( 'pointermove', move );
				document.removeEventListener( 'pointerup', stop );
				document.removeEventListener( 'touchmove', move );
				document.removeEventListener( 'touchend', stop );
				ref.classList.remove( 'is-dragging' );
				const coverXInput = state.root.querySelector(
					'input[name="profile_cover_position_x"]'
				);
				const coverYInput = state.root.querySelector(
					'input[name="profile_cover_position_y"]'
				);
				if ( coverXInput ) {
					coverXInput.value = String( x / 100 );
				}
				if ( coverYInput ) {
					coverYInput.value = String( y / 100 );
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
			const { strings } = getContext();
			const badType = strings?.invalidFileType || 'Invalid file type.';

			if ( ! file ) {
				return;
			}
			if ( ! [ 'image/png', 'image/jpeg' ].includes( file.type ) ) {
				if ( window.wp?.a11y?.speak ) {
					window.wp.a11y.speak( badType, 'assertive' );
				}
				if ( state.toast.timeout ) {
					clearTimeout( state.toast.timeout );
				}
				state.toast.type = 'error';
				state.toast.heading = '';
				state.toast.message = badType;
				state.toast.visible = true;
				state.toast.timeout = setTimeout( () => {
					state.toast.visible = false;
				}, 6000 );
				event.target.value = '';
				return;
			}

			const url = URL.createObjectURL( file );
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
				posBox.style.backgroundImage = `url(${ url })`;
			}
		},

		showToast( {
			type = 'success',
			heading = '',
			message = '',
			duration = 6000,
		} ) {
			if ( state.toast.timeout ) {
				clearTimeout( state.toast.timeout );
			}
			state.toast.type = type;
			state.toast.heading = heading;
			state.toast.message = message;
			state.toast.visible = true;
			if ( duration ) {
				state.toast.timeout = setTimeout( () => {
					state.toast.visible = false;
				}, duration );
			}
		},

		hideToast() {
			if ( state.toast.timeout ) {
				clearTimeout( state.toast.timeout );
			}
			state.toast.visible = false;
		},

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
