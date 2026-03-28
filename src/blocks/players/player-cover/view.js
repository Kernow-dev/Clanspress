import { store, getContext, getElement } from '@wordpress/interactivity';

const { state, actions } = store( 'clanspress-player-cover', {
	state: {
		root: null,
		isEditing: false,
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

		/**
		 * Is the current parent nav element expanded.
		 *
		 * @returns {boolean} True if current active nav is this element.
		 */
		isThisNavExpanded() {
			const { attributes } = getElement();
			return (
				this.activeNav === JSON.parse( attributes[ 'data-wp-args' ] )
			);
		},

		/**
		 * Is the current child nav element expanded.
		 *
		 * @returns {boolean} True if current active nav is this child element.
		 */
		isNavExpanded() {
			const { attributes } = getElement();
			const isActive = this.activeNav === attributes.id;

			if ( state.root ) {
				const element = state.root.querySelector(
					`#${ attributes.id }`
				);
				if ( element ) {
					if ( isActive ) {
						// Open: set max-height to scrollHeight for smooth transition
						element.style.maxHeight = element.scrollHeight + 'px';
					} else {
						element.style.maxHeight = '0';
					}
				}
			}

			return isActive;
		},

		isThisControlActive() {

		},

		isThisPanelActive() {
			const { attributes } = getElement();
			return (
				this.activePanel === attributes[ 'data-wp-args' ]
			);
		},

		isPanelActive() {
			const { attributes } = getElement();
			return 'panel-' + this.activePanel === attributes.id;
		},

		isError() {
			const { attributes } = getElement();
			const name = attributes[ 'name' ];

			if ( ! name ) {
				return false;
			}

			return name in this.errors;
		},

		showError() {
			const { attributes } = getElement();
			const name = attributes[ 'data-wp-args' ];

			if ( ! name ) {
				return false;
			}

			return ! ( name in this.errors );
		},

		errorMessage() {
			const { attributes } = getElement();
			const name = attributes[ 'data-wp-args' ];

			if ( ! name ) {
				return null;
			}

			if ( this.errors && name in this.errors ) {
				return this.errors[ name ];
			}

			return null;
		},

		isToastSuccess() {
			return this.toast.type === 'success';
		},

		isToastError() {
			return this.toast.type === 'error';
		},
	},

	actions: {
		enableControls() {
			const { canEdit } = getContext();
			if ( ! canEdit ) {
				return;
			}

			state.isEditing = true;
		},

		disableControls() {
			const { canEdit } = getContext();
			if ( ! canEdit ) {
				return;
			}

			const openControls = state.root.querySelectorAll('.player-cover__controls-container .control-panel.active');

			openControls.forEach( ( control ) => {
				control.parentElement.querySelector( 'button' ).click();
			} );

			state.isEditing = false;
		},

		toggleControl() {
			const { ref, attributes } = getElement();

			if ( ! ref || ! attributes ) {
				return;
			}

			const panelName = attributes[ 'data-wp-args' ];
			if ( ! panelName ) {
				return;
			}

			// Find the panel inside the current control
			const panel = ref.parentNode.querySelector(
				`.control-panel.${ panelName }`
			);
			if ( ! panel ) {
				return;
			}

			//state.activePanel = panel.id;

			// Toggle the 'active' class
			panel.classList.toggle( 'active' );
		},

		startDrag( event ) {
			event.preventDefault();

			const { ref } = getElement();
			if ( ! ref ) {
				return;
			}

			ref.classList.toggle('grabbing');

			const box = ref.closest( '.position-box' );
			if ( ! box ) {
				return;
			}

			const image = state.root.querySelector(
				'.player-cover__image-background'
			);
			if ( ! image ) {
				return;
			}

			const rect = box.getBoundingClientRect();

			// define x and y in outer scope
			let x = 0;
			let y = 0;

			const move = ( moveEvent ) => {
				// support touch events
				const clientX =
					moveEvent.clientX ?? moveEvent.touches?.[ 0 ]?.clientX;
				const clientY =
					moveEvent.clientY ?? moveEvent.touches?.[ 0 ]?.clientY;

				if ( clientX === undefined || clientY === undefined ) return;

				// assign to outer x/y, do NOT redeclare with 'let'
				x = ( ( clientX - rect.left ) / rect.width ) * 100;
				y = ( ( clientY - rect.top ) / rect.height ) * 100;

				// clamp
				x = Math.min( 100, Math.max( 0, x ) );
				y = Math.min( 100, Math.max( 0, y ) );

				// round to 1% steps
				x = Math.round( x );
				y = Math.round( y );

				// update state
				state.position = { x, y };

				// update thumb
				ref.style.left = `${ x }%`;
				ref.style.top = `${ y }%`;

				// update image background
				image.style.objectPosition = `${ x }% ${ y }%`;
			};

			const stop = () => {
				document.removeEventListener( 'pointermove', move );
				document.removeEventListener( 'pointerup', stop );
				document.removeEventListener( 'touchmove', move );
				document.removeEventListener( 'touchend', stop );

				ref.classList.toggle('grabbing');

				// save normalized position (0–1)
				const coverXInput = state.root.querySelector(
					'input[name="profile_cover_position_x"]'
				);
				if ( coverXInput ) {
					coverXInput.value = x / 100;
				}

				const coverYInput = state.root.querySelector(
					'input[name="profile_cover_position_y"]'
				);
				if ( coverYInput ) {
					coverYInput.value = y / 100;
				}
			};

			// attach listeners
			document.addEventListener( 'pointermove', move );
			document.addEventListener( 'pointerup', stop );
			document.addEventListener( 'touchmove', move, { passive: false } );
			document.addEventListener( 'touchend', stop );
		},

		showPanel() {
			const { ref } = getElement();
			const panelId = ref?.attributes?.[ 'data-wp-args' ]?.value ?? null;

			if ( ! panelId ) {
				return;
			}

			state.activePanel = panelId;
		},

		selectCover() {
			const coverInput = state.root.querySelector(
				'input[name="profile_cover"]'
			);

			if ( coverInput ) {
				coverInput.click();
			}
		},

		updateCover( event ) {
			const file = event.target.files[ 0 ];
			if ( ! file ) {
				return;
			}

			// Only allow image files
			if ( ! [ 'image/png', 'image/jpeg' ].includes( file.type ) ) {
				alert( 'Only PNG or JPEG allowed.' );
				return;
			}

			// Find the cover preview inside this block
			const preview = state.root.querySelector(
				'.player-cover__image-background'
			);
			if ( preview ) {
				// Update preview using object URL
				preview.src = `${ URL.createObjectURL( file ) }`;
			}

			const focusPointer = state.root.querySelector( '.position-box' );

			if ( focusPointer ) {
				focusPointer.style.backgroundImage = `linear-gradient(to right, rgba( 255, 255, 255, 0.6) 1px, transparent 1px), linear-gradient(to bottom, rgba( 255, 255, 255, 0.6) 1px, transparent 1px), url(${ URL.createObjectURL(
					file
				) })`;
			}
		},

		showToast( {
			type = 'success',
			heading = '',
			message = '',
			duration = 6000,
		} ) {
			// Clear existing timeout
			if ( state.toast.timeout ) {
				clearTimeout( state.toast.timeout );
			}

			state.toast.type = type;
			state.toast.heading = heading;
			state.toast.message = message;
			state.toast.visible = true;

			// Auto-hide
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

			if ( ! state.root ) {
				return;
			}

			state.isSaving = true;
			ref.classList.remove( 'saved' );
			ref.classList.remove( 'error' );
			ref.classList.add( 'saving' );

			const fields = state.root.querySelectorAll(
				'input, select, textarea'
			);

			const data = {};

			fields.forEach( ( field ) => {
				if ( ! field.name || field.disabled ) {
					return;
				}

				if ( field.type === 'checkbox' ) {
					data[ field.name ] = field.checked ? '1' : '0';
				} else if ( field.type === 'radio' ) {
					if ( field.checked ) data[ field.name ] = field.value;
				} else if ( field.type !== 'file' ) {
					data[ field.name ] = field.value;
				}
			} );

			// Grab the nonce from the hidden field
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
			for ( const key in data ) {
				formData.append( key, data[ key ] );
			}

			// Add files directly from inputs
			const avatarInput = state.root.querySelector(
				'input[name="profile_avatar"]'
			);
			const coverInput = state.root.querySelector(
				'input[name="profile_cover"]'
			);

			if ( avatarInput && avatarInput.files[ 0 ] ) {
				formData.append( 'profile_avatar', avatarInput.files[ 0 ] );
			}

			if ( coverInput && coverInput.files[ 0 ] ) {
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
						heading: 'Success',
						message: 'Your changes were saved successfully.',
					} );
				} else {
					ref.classList.add( 'error' );
					state.errors = json?.data?.errors;
					actions.showToast( {
						type: 'error',
						heading: 'Error',
						message: 'There was an error while saving changes.',
					} );
				}
			} catch ( err ) {
				console.error( 'Save failed', err );
			} finally {
				state.isSaving = false;
			}
		},
	},

	callbacks: {
		init() {
			const { ref } = getElement();

			if ( ! ref ) {
				return;
			}

			state.root = ref;
		},
	},
} );
